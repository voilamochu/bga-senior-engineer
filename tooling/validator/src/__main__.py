"""
Runtime Specification Validator — CLI entry point.

Usage::

    python -m tooling.validator --rules RULES [--report {human,json}]
                                [--output FILE] [--ci]
                                [--validators LIST] [--release RELEASE]
"""

from __future__ import annotations

import argparse
import json
import sys
from datetime import datetime, timezone
from typing import Any

from tooling._shared.loader import load_rules
from tooling._shared.types import (
    ExitCode,
    RuleCollection,
    ValidationError,
    ValidatorResult,
)
from tooling.validator.src.validators import (
    crossref_validator,
    ownership_validator,
    priority_validator,
    release_validator,
    rule_id_validator,
    schema_validator,
    stats_generator,
)

PROG = "runtime_validator"
VERSION = "1.0.0"
RUNTIME_VERSION = "1.1"

VALIDATOR_NAMES: dict[str, str] = {
    "schema": "Schema Validation",
    "rule_id": "Rule ID Validation",
    "crossref": "Cross-Reference Validation",
    "ownership": "Ownership Validation",
    "priority": "Priority Validation",
    "stats": "Statistics",
    "release": "Release Validation",
}

_VALIDATOR_MODULES = {
    "schema": schema_validator,
    "rule_id": rule_id_validator,
    "crossref": crossref_validator,
    "ownership": ownership_validator,
    "priority": priority_validator,
    "stats": stats_generator,
    "release": release_validator,
}


def build_parser() -> argparse.ArgumentParser:
    p = argparse.ArgumentParser(
        prog=PROG,
        description="Runtime Specification Validator",
    )
    p.add_argument(
        "--rules",
        required=True,
        help="Path to rules/ directory",
    )
    p.add_argument(
        "--schema",
        help="Path to schema definition (optional, embedded defaults)",
    )
    p.add_argument(
        "--release",
        help="Path to release document for release validation",
    )
    p.add_argument(
        "--report",
        choices=["human", "json"],
        default="human",
        help="Output format (default: human)",
    )
    p.add_argument(
        "--output",
        help="Write report to file (default: stdout)",
    )
    p.add_argument(
        "--ci",
        action="store_true",
        help="CI mode: exit 1 on first failure, no report",
    )
    p.add_argument(
        "--validators",
        help="Comma-separated list of validators to run "
             "(default: all). Options: schema, rule_id, crossref, "
             "ownership, priority, stats, release",
    )
    return p


def main(argv: list[str] | None = None) -> int:
    parser = build_parser()
    args = parser.parse_args(argv)

    # Resolve validator names
    selected = _resolve_validators(args.validators)
    if selected is None:
        return ExitCode.ERROR

    # Always include stats when other validators are selected so the
    # report can reference them.
    always_includes = set(selected)
    if selected != {"stats"} and "stats" not in always_includes:
        pass  # stats are always computed in the report section

    # Load rules
    try:
        collection = load_rules(args.rules)
    except FileNotFoundError:
        print(f"Error: Rules directory not found: {args.rules}", file=sys.stderr)
        return ExitCode.ERROR
    except ValueError as exc:
        print(f"Error parsing rule file: {exc}", file=sys.stderr)
        return ExitCode.ERROR

    # Run validators
    results: list[ValidatorResult] = []
    has_error = False

    for name in _execution_order(selected):
        try:
            module = _VALIDATOR_MODULES[name]
        except KeyError:
            print(f"Error: Unknown validator '{name}'", file=sys.stderr)
            return ExitCode.ERROR

        try:
            if name == "release":
                result = module.validate(
                    collection, release_doc_path=args.release or "",
                )
            else:
                result = module.validate(collection)
        except Exception as exc:
            print(
                f"Runtime error in validator '{name}': {exc}",
                file=sys.stderr,
            )
            return ExitCode.ERROR

        results.append(result)
        if result.status == "fail":
            has_error = True
            if args.ci:
                _print_ci(result)
                return ExitCode.FAILURE

    # Always compute statistics for the report.
    stats_data = stats_generator.generate_statistics(collection)

    # Generate output
    if not args.ci:
        report = _build_report(results, stats_data, args.report)
        if args.output:
            try:
                with open(args.output, "w", encoding="utf-8") as f:
                    f.write(report)
            except OSError as exc:
                print(
                    f"Error writing output file: {exc}", file=sys.stderr,
                )
                return ExitCode.ERROR
        else:
            print(report)

    return ExitCode.FAILURE if has_error else ExitCode.SUCCESS


# ------------------------------------------------------------------
# Validator dispatch helpers
# ------------------------------------------------------------------


def _resolve_validators(
    raw: str | None,
) -> list[str] | None:
    """Parse the ``--validators`` argument into a name list.

    Returns ``None`` on invalid input.
    """
    if not raw:
        return list(VALIDATOR_NAMES.keys())
    names = [n.strip() for n in raw.split(",") if n.strip()]
    for n in names:
        if n not in _VALIDATOR_MODULES:
            print(f"Error: Unknown validator '{n}'. "
                  f"Valid options: {', '.join(_VALIDATOR_MODULES.keys())}",
                  file=sys.stderr)
            return None
    return names


def _execution_order(selected: list[str]) -> list[str]:
    """Return validators in execution order (schema first, stats always)."""
    ordered = [
        n for n in (
            "schema", "rule_id", "crossref", "ownership",
            "priority", "stats", "release",
        )
        if n in selected
    ]
    return ordered


# ------------------------------------------------------------------
# Report builders
# ------------------------------------------------------------------


def _build_report(
    results: list[ValidatorResult],
    stats_data: dict[str, Any],
    fmt: str,
) -> str:
    if fmt == "json":
        return _build_json_report(results, stats_data)
    return _build_human_report(results, stats_data)


def _build_human_report(
    results: list[ValidatorResult],
    stats_data: dict[str, Any],
) -> str:
    lines: list[str] = []
    timestamp = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")

    lines.append("=== Runtime Specification Validator ===")
    lines.append(f"Version: {VERSION}")
    lines.append(f"Runtime: v{RUNTIME_VERSION}")
    lines.append(f"Timestamp: {timestamp}")
    lines.append("")

    validator_results = [r for r in results if r.name != "stats"]
    total = len(validator_results)
    failed_count = 0

    for result in validator_results:
        display_name = VALIDATOR_NAMES.get(result.name, result.name)
        err_count = _error_count(result)
        status_text = "PASS" if result.status == "pass" else "FAIL"
        lines.append(
            f"--- {display_name}: {status_text} ({err_count} errors) ---"
        )
        if result.errors:
            for err in result.errors:
                if err.severity == "error":
                    lines.append(f"  ERROR: {err.reason}")
                else:
                    lines.append(f"  WARN: {err.reason}")
        if result.status == "fail":
            failed_count += 1
        lines.append("")

    # Statistics section
    lines.append("=== Statistics ===")
    lines.append(f"Total files: {stats_data.get('total_files', 0)}")
    lines.append(f"Total rules: {stats_data.get('total_rules', 0)}")
    lines.append(f"Total lines: {stats_data.get('total_lines', 0):,}")
    lines.append(
        f"Cross-references: {stats_data.get('cross_reference_count', 0)}"
    )
    lines.append("")
    lines.append(
        f"=== Result: {'FAIL' if failed_count > 0 else 'PASS'} "
        f"({failed_count} of {total} validators failed) ==="
    )

    return "\n".join(lines)


def _build_json_report(
    results: list[ValidatorResult],
    stats_data: dict[str, Any],
) -> str:
    timestamp = datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")

    validator_results = [r for r in results if r.name != "stats"]
    total = len(validator_results)
    passed = sum(1 for r in validator_results if r.status == "pass")
    failed = sum(1 for r in validator_results if r.status == "fail")

    results_dict: dict[str, dict[str, Any]] = {}
    for r in validator_results:
        results_dict[r.name] = {
            "status": r.status,
            "errors": [
                {
                    "rule_id": e.rule_id,
                    "file": e.file,
                    "reason": e.reason,
                    "severity": e.severity,
                }
                for e in r.errors
            ],
        }

    report = {
        "version": VERSION,
        "timestamp": timestamp,
        "runtime_version": RUNTIME_VERSION,
        "summary": {
            "total_validators": total,
            "passed": passed,
            "failed": failed,
            "skipped": 0,
        },
        "results": results_dict,
        "statistics": {
            "total_rules": stats_data.get("total_rules", 0),
            "total_files": stats_data.get("total_files", 0),
            "total_lines": stats_data.get("total_lines", 0),
        },
    }
    return json.dumps(report, indent=2)


def _print_ci(result: ValidatorResult) -> None:
    """Print CI-formatted output for a failed validator."""
    errors = result.errors[:3]
    for err in errors:
        detail = err.reason
        rule_part = f" rule_id={err.rule_id}" if err.rule_id else ""
        print(
            f"FAIL validator={result.name}{rule_part} "
            f'detail="{detail}"'
        )


def _error_count(result: ValidatorResult) -> int:
    return sum(1 for e in result.errors if e.severity == "error")


if __name__ == "__main__":
    sys.exit(main())
