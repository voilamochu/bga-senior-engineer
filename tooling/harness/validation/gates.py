"""Gate runner G0-G2 (MS-06, MVB-015 + MVB-018).

Executes automatic validation (P5) against the run's frozen evidence:

- **G0** — repository safety comparison (MVB-004 ``verify_baseline``):
  failure → ``REJECTED``; G1/G2 short-circuited (NOT_RUN).
- **G1** — build gates B1-B4 (MVB-016): any failure → ``REJECTED``;
  G2 short-circuited.
- **G2** — catalog checks V1 + NOT-02 + V9 (MVB-017): every check
  executes and is recorded; a blocking failure → ``REJECTED``, a
  non-blocking failure is recorded as a Framework-Compliance finding.

Gate verdicts: ``PASS`` / ``FAIL`` / ``BLOCKED`` / ``NOT_RUN``.
``BLOCKED`` (missing tool, missing rules, or missing evidence) never
short-circuits later gates and never rejects the run — the run remains
re-runnable per §5.1 (tooling-error re-run), recorded in
``validation.json`` and the manifest errata.

Outputs (MVB-018): ``validation/validation.json`` (deterministic —
byte-identical for identical frozen evidence; no wall-clock values) and
one raw file per check ID under ``validation/raw/``.  The validation
artifacts are appended to the frozen evidence tree as
``evidence/reruns/e4/`` with a hash-recorded reruns catalog (harness
§6.3), since P5 runs after the P4 freeze.
"""

from __future__ import annotations

import json
import shutil
from pathlib import Path

from tooling.harness.config import default_skill_root
from tooling.harness.evidence.collect import (
    EVIDENCE_SCHEMA,
    load_evidence_catalog,
)
from tooling.harness.evidence.freeze import verify_frozen_evidence
from tooling.harness.runtime.manifest import RunManifest
from tooling.harness.runtime.run_dir import RunDir
from tooling.harness.runtime.status import RunStatus
from tooling.harness.safety.baseline import BaselineError, load_baseline, verify_baseline
from tooling.harness.util.clock import utc_now_iso
from tooling.harness.util.hash import sha256_file
from tooling.harness.util.log import HarnessLog
from tooling.harness.util.proc import CommandLog
from tooling.harness.validation.build_gates import run_build_gates
from tooling.harness.validation.checks.not_02 import run_task_checks
from tooling.harness.validation.checks.v1 import run_v1
from tooling.harness.validation.checks.v9 import run_v9_from_diff_bundle
from tooling.harness.validation.result import VERDICTS, CheckResult

VALIDATION_SCHEMA = "benchmark-harness-validation/1.0"
RERUNS_CATALOG_SCHEMA = "benchmark-harness-evidence-reruns/1.0"

# Validation order per the published specifications: gates G0 → G1 → G2
# (evaluation spec §2.1), build gates B1→B4 (catalog order), catalog
# checks V1 → NOT-02 task checks → V9 (evaluation spec §4, §3.11).
GATE_ORDER = ("G0", "G1", "G2")

GATE_NAMES = {
    "G0": "Repository safety",
    "G1": "Build gates",
    "G2": "Catalog checks",
}

# Statuses from which P5 validation may run (P3 ended; partial
# submissions proceed through evaluation per harness §3.6).
GATEABLE_STATUSES = ("COMPLETED", "TIMEOUT")

# Diff bundle directory inside the evidence tree (E8).
E8_DIRNAME = "e8-diff-bundle"

# The E8 entry's files are copies of the submission's ``changes/``.
E8_PREFIX = f"{E8_DIRNAME}/"


class ValidationError(Exception):
    """Gates cannot run (preconditions not met); the run is untouched."""


def run_gates(
    run: RunDir,
    manifest: RunManifest,
    status: RunStatus,
    *,
    reference_root: str | Path,
    command_log: CommandLog,
    log: HarnessLog,
    skill_root: str | Path | None = None,
    python: str | None = None,
) -> dict:
    """Execute gates G0-G2 and persist ``validation/validation.json``.

    Returns a summary dict ``{"summary", "gates", "rejected",
    "validation_path", "exit_code"}``.  Raises :class:`ValidationError`
    (run untouched) when the run is not in a gateable, frozen state.
    """
    _preflight(run, manifest, status)

    catalog = load_evidence_catalog(run)
    diff_bundle, changed_paths = _diff_bundle(run, catalog)
    subsystems_md, subsystems_md_evidence = _subsystems_md(run, catalog)
    gamelogs = _gamelogs(run, catalog)
    log.info(
        f"gates inputs: diff_bundle={'present' if diff_bundle else 'absent'} "
        f"({len(changed_paths)} files), subsystems.md "
        f"{'present' if subsystems_md is not None else 'absent'}, "
        f"{len(gamelogs)} gamelog(s)"
    )

    g0 = _run_g0(run, reference_root)
    check_groups: dict[str, list[CheckResult]] = {"G0": [g0]}
    rejected = g0.verdict == "FAIL"

    if rejected:
        g1_not_run = _not_run_checks("G1", "G0 failed")
        g2_not_run = _not_run_checks("G2", "G0 failed")
        check_groups["G1"] = g1_not_run
        check_groups["G2"] = g2_not_run
        _log_checks(log, [g0] + g1_not_run + g2_not_run)
    else:
        build_checks = run_build_gates(
            diff_bundle=diff_bundle,
            changed_paths=changed_paths,
            subsystems_md=subsystems_md,
            subsystems_md_evidence=subsystems_md_evidence,
            command_log=command_log,
        )
        check_groups["G1"] = build_checks
        _log_checks(log, build_checks)
        if any(check.verdict == "FAIL" for check in build_checks):
            rejected = True
            check_groups["G2"] = _not_run_checks("G2", "G1 failed")
        else:
            catalog_checks = _run_catalog_checks(
                run, diff_bundle, changed_paths, gamelogs, command_log, skill_root, python
            )
            check_groups["G2"] = catalog_checks
            _log_checks(log, catalog_checks)
            if any(
                check.verdict == "FAIL" and check.blocking
                for check in catalog_checks
            ):
                rejected = True

    gates = {
        gate_id: _gate_record(gate_id, checks)
        for gate_id, checks in check_groups.items()
    }
    summary = _build_summary(gates, rejected)
    _persist(run, manifest, status, check_groups, summary, log)
    return {
        "summary": summary,
        "gates": gates,
        "rejected": rejected,
        "validation_path": run.root / "validation" / "validation.json",
        "exit_code": 0 if summary["verdict"] != "BLOCKED" else 1,
    }


# ----------------------------------------------------------------------
# Pre-flight
# ----------------------------------------------------------------------

def _preflight(run: RunDir, manifest: RunManifest, status: RunStatus) -> None:
    if status.status not in GATEABLE_STATUSES:
        raise ValidationError(
            f"cannot run gates for {run.run_id} in status {status.status} "
            f"(expected {' or '.join(GATEABLE_STATUSES)})"
        )
    if not manifest.frozen:
        raise ValidationError(
            "run manifest is not frozen; complete P4 with "
            "'collect --freeze' before running gates (P5)"
        )
    catalog = load_evidence_catalog(run)
    if not catalog.get("frozen"):
        raise ValidationError(
            "evidence is not frozen; complete P4 with 'collect --freeze' "
            "before running gates (P5)"
        )
    verification = verify_frozen_evidence(run, manifest)
    if not verification["passed"]:
        raise ValidationError(
            "frozen evidence verification failed: "
            + "; ".join(verification["divergences"])
        )


# ----------------------------------------------------------------------
# G0 — repository safety
# ----------------------------------------------------------------------

def _run_g0(run: RunDir, reference_root: str | Path) -> CheckResult:
    baseline_path = run.root / "protocol" / "baseline" / "safety-baseline.json"
    evidence = ["protocol/baseline/safety-baseline.json"]
    if not baseline_path.is_file():
        reason = "evidence: protocol/baseline/safety-baseline.json is missing"
        return CheckResult(
            id="G0",
            name="Repository safety comparison",
            verdict="BLOCKED",
            blocking=True,
            detail=reason,
            raw_text=f"BLOCKED: {reason}\n",
            evidence=evidence,
        )
    baseline = None
    try:
        baseline = load_baseline(baseline_path)
        verification = verify_baseline(reference_root, baseline)
    except (BaselineError, OSError, ValueError) as exc:
        return CheckResult(
            id="G0",
            name="Repository safety comparison",
            verdict="BLOCKED",
            blocking=True,
            detail=f"cannot verify the reference repository: {exc}",
            raw_text=f"BLOCKED: cannot verify the reference repository: {exc}\n",
            evidence=evidence,
        )
    if verification.passed:
        return CheckResult(
            id="G0",
            name="Repository safety comparison",
            verdict="PASS",
            blocking=True,
            detail="head, status_porcelain, and reflog_top match the baseline",
            raw_text="PASS: head, status_porcelain, and reflog_top match the "
            "baseline\n",
            evidence=evidence,
        )
    findings = [
        f"{divergence.check}: expected {divergence.expected!r}, "
        f"actual {divergence.actual!r}"
        for divergence in verification.divergences
    ]
    return CheckResult(
        id="G0",
        name="Repository safety comparison",
        verdict="FAIL",
        blocking=True,
        detail="; ".join(findings),
        findings=findings,
        raw_text="FAIL:\n" + "\n".join(findings) + "\n",
        evidence=evidence,
    )


# ----------------------------------------------------------------------
# G2 — catalog checks
# ----------------------------------------------------------------------

def _run_catalog_checks(
    run, diff_bundle, changed_paths, gamelogs, command_log, skill_root, python
) -> list[CheckResult]:
    rules_root = Path(skill_root) if skill_root is not None else default_skill_root()
    checks: list[CheckResult] = [
        run_v1(rules_root=rules_root / "rules", command_log=command_log, python=python),
    ]
    checks += run_task_checks(
        diff_bundle=diff_bundle,
        changed_paths=changed_paths,
        gamelogs=gamelogs,
    )
    checks.append(run_v9_from_diff_bundle(diff_bundle, changed_paths))
    return checks


# ----------------------------------------------------------------------
# Evidence resolution
# ----------------------------------------------------------------------

def _diff_bundle(run: RunDir, catalog: dict) -> tuple[Path | None, list[str]]:
    entry = catalog["types"].get("E8")
    if not entry or entry.get("status") != "present":
        return None, []
    changed: list[str] = []
    for file_entry in entry.get("files", []):
        path = file_entry["path"]
        if path.startswith(E8_PREFIX):
            changed.append(path[len(E8_PREFIX):])
    return run.evidence / E8_DIRNAME, sorted(changed)


def _subsystems_md(run: RunDir, catalog: dict) -> tuple[str | None, list[str]]:
    entry = catalog["types"].get("E2")
    if not entry or entry.get("status") != "present":
        return None, []
    for file_entry in entry.get("files", []):
        if file_entry["path"].endswith("subsystems.md"):
            path = run.evidence / file_entry["path"]
            try:
                return path.read_text(encoding="utf-8"), [f"evidence/{file_entry['path']}"]
            except OSError:
                return None, []
    return None, []


def _gamelogs(run: RunDir, catalog: dict) -> list[str]:
    found: list[str] = []
    for etype, entry in catalog["types"].items():
        for file_entry in entry.get("files", []):
            if "gamelog" in file_entry["path"].lower():
                found.append(f"evidence/{file_entry['path']}")
    return sorted(found)


# ----------------------------------------------------------------------
# Records, summary, persistence
# ----------------------------------------------------------------------

def _gate_record(gate_id: str, checks: list[CheckResult]) -> dict:
    if any(check.verdict == "FAIL" for check in checks):
        verdict = "FAIL"
    elif any(check.verdict == "BLOCKED" for check in checks):
        verdict = "BLOCKED"
    elif all(check.verdict == "NOT_RUN" for check in checks):
        verdict = "NOT_RUN"
    elif all(check.verdict == "PASS" for check in checks):
        verdict = "PASS"
    else:
        verdict = "FAIL"
    return {
        "id": gate_id,
        "name": GATE_NAMES[gate_id],
        "verdict": verdict,
        "checks": [check.to_dict() for check in checks],
    }


def _not_run_checks(gate_id: str, reason: str) -> list[CheckResult]:
    template = {
        "G1": [
            ("B1", "PHP syntax"),
            ("B2", "JS syntax"),
            ("B3", "JSON validity"),
            ("B4", "Artifact inventory"),
        ],
        "G2": [
            ("V1", "Runtime validator"),
            ("NOT02-A", "Single-source notification patterns"),
            ("NOT02-B", "Call-site counts per consolidation helper"),
            ("NOT02-C", "Duplication scan (Game.php)"),
            ("NOT02-D", "Payload parity (gamelog diff)"),
            ("V9", "Hidden-info scan (payload keys)"),
        ],
    }[gate_id]
    short = reason or f"short-circuited by an earlier gate failure"
    checks = [
        CheckResult(
            id=check_id,
            name=name,
            verdict="NOT_RUN",
            blocking=True,
            detail=f"not executed: {short}",
            raw_text=f"NOT_RUN: not executed: {short}\n",
        )
        for check_id, name in template
    ]
    return checks


def _build_summary(gates: dict, rejected: bool) -> dict:
    checks = [check for gate in gates.values() for check in gate["checks"]]
    blocking_failures = sorted(
        check["id"] for check in checks if check["verdict"] == "FAIL" and check["blocking"]
    )
    non_blocking = sorted(
        check["id"] for check in checks if check["verdict"] == "FAIL" and not check["blocking"]
    )
    blocked = sorted(check["id"] for check in checks if check["verdict"] == "BLOCKED")
    not_run = sorted(check["id"] for check in checks if check["verdict"] == "NOT_RUN")
    substitutions = sorted(
        {"check": check["id"], "reason": check["substitution_reason"]}
        for check in checks
        if check.get("substituted")
    )
    if rejected:
        verdict = "REJECTED"
    elif blocked:
        verdict = "BLOCKED"
    else:
        verdict = "PASS"
    return {
        "verdict": verdict,
        "rejected": rejected,
        "blocking_failures": blocking_failures,
        "non_blocking_findings": non_blocking,
        "blocked_checks": blocked,
        "not_run_checks": not_run,
        "substitutions": substitutions,
        "check_count": len(checks),
        "executed_check_count": sum(
            1 for check in checks if check["verdict"] in ("PASS", "FAIL")
        ),
    }


def _persist(run, manifest, status, check_groups, summary, log) -> None:
    validation_dir = run.root / "validation"
    raw_dir = validation_dir / "raw"
    raw_dir.mkdir(parents=True, exist_ok=True)

    for checks in check_groups.values():
        for check in checks:
            (raw_dir / f"{check.id}.txt").write_text(
                check.raw_text or check.detail + "\n", encoding="utf-8"
            )

    gates = {
        gate_id: _gate_record(gate_id, checks)
        for gate_id, checks in check_groups.items()
    }
    document = {
        "schema": VALIDATION_SCHEMA,
        "run_id": run.run_id,
        "task_id": (manifest.task or {}).get("id"),
        "evidence_root_hash": manifest.evidence_root_hash,
        "evidence_catalog": EVIDENCE_SCHEMA,
        "gates": gates,
        "summary": summary,
    }
    validation_path = validation_dir / "validation.json"
    validation_path.write_text(
        json.dumps(document, indent=2, sort_keys=True) + "\n", encoding="utf-8"
    )
    log.info(
        f"validation recorded: {validation_path.relative_to(run.root)} "
        f"(summary verdict {summary['verdict']})"
    )

    _record_reruns(run, validation_path, log)

    # Status and manifest updates.  The manifest is frozen at P4, so the
    # P5 outcome is appended through the sanctioned errata channel; phase
    # timing is recorded in status.json's checkpoint index.
    now = utc_now_iso()
    if summary["rejected"]:
        status.transition("REJECTED", checkpoint="p5", at=now)
    else:
        status.record_checkpoint("p5", at=now)
    status.save(run.status_path)

    if summary["rejected"]:
        message = (
            "P5 automatic validation recorded in validation/validation.json "
            f"(REJECTED: blocking failures {', '.join(summary['blocking_failures'])})"
        )
    else:
        message = (
            "P5 automatic validation recorded in validation/validation.json "
            f"(summary verdict {summary['verdict']}, "
            f"{summary['check_count']} checks)"
        )
    manifest.add_errata(message, at=now)
    manifest.save(run.manifest_path)


def _record_reruns(run: RunDir, validation_path: Path, log: HarnessLog) -> None:
    """Append the P5 validation artifacts to ``evidence/reruns/e4/`` (§6.3).

    The reruns directory is the only writable part of the frozen
    evidence tree; E4 (validation logs) is produced after the P4 freeze
    and lands here with a hash-recorded catalog, never altering frozen
    artifacts.  Each gates invocation replaces the canonical ``e4`` copy
    (the reruns catalog therefore stays deterministic for identical
    runs; the manifest errata records re-runs with timestamps).
    """
    reruns = run.evidence / "reruns"
    e4 = reruns / "e4"
    try:
        e4.mkdir(parents=True, exist_ok=True)
        for child in e4.iterdir():
            if child.is_dir():
                shutil.rmtree(child)
            else:
                child.unlink()
        shutil.copy2(validation_path, e4 / "validation.json")
        raw_dst = e4 / "raw"
        raw_dst.mkdir(parents=True)
        for raw in sorted((validation_path.parent / "raw").iterdir()):
            if raw.is_file():
                shutil.copy2(raw, raw_dst / raw.name)
        entries: dict[str, dict] = {}
        for path in sorted(e4.rglob("*")):
            if path.is_file():
                relative = str(path.relative_to(reruns))
                entries[relative] = {
                    "size": path.stat().st_size,
                    "sha256": sha256_file(path),
                }
        catalog = {
            "schema": RERUNS_CATALOG_SCHEMA,
            "run_id": run.run_id,
            "entries": entries,
        }
        (reruns / "catalog.json").write_text(
            json.dumps(catalog, indent=2, sort_keys=True) + "\n", encoding="utf-8"
        )
    except OSError as exc:
        log.warning(f"could not record validation reruns evidence: {exc}")


def _log_checks(log: HarnessLog, checks: list[CheckResult]) -> None:
    for check in checks:
        log.info(
            f"check {check.id} {check.verdict} in {check.execution_time:.3f}s"
        )
