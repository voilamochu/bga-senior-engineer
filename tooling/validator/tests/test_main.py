"""CLI integration tests for the Runtime Validator entry point."""

import json
import os
import sys
import tempfile
from io import StringIO
from pathlib import Path

import pytest

from tooling._shared.types import ExitCode
from tooling.validator.src.__main__ import VERSION, build_parser, main

RULES_DIR = Path(__file__).parent.parent.parent.parent / "bga-senior-engineer-skill" / "rules"


def _run(argv: list[str]) -> tuple[int, str, str]:
    """Run main() with *argv* and return (exit_code, stdout, stderr).

    Catches ``SystemExit`` raised by argparse so tests can inspect
    the exit code without being terminated.
    """
    old_stdout = sys.stdout
    old_stderr = sys.stderr
    sys.stdout = StringIO()
    sys.stderr = StringIO()
    try:
        try:
            code = main(argv)
        except SystemExit as e:
            code = e.code if e.code is not None else 0
        out = sys.stdout.getvalue()
        err = sys.stderr.getvalue()
    finally:
        sys.stdout = old_stdout
        sys.stderr = old_stderr
    return code, out, err


# ======================================================================
# Basic execution
# ======================================================================


class TestBasicExecution:
    def test_all_validators_run(self):
        """Running all validators; crossref finds cycles → exit 1."""
        code, out, err = _run(["--rules", str(RULES_DIR)])
        # crossref has findings — exit is 1 (FAILURE)
        assert code == ExitCode.FAILURE, f"stderr: {err}"
        assert "Runtime Specification Validator" in out

    def test_ci_mode(self):
        """CI mode exits on first failure (crossref)."""
        code, out, err = _run(["--rules", str(RULES_DIR), "--ci"])
        assert code == ExitCode.FAILURE
        assert "FAIL validator" in out

    def test_ci_with_valid_only(self):
        """CI mode with only passing validators succeeds."""
        code, out, err = _run(
            ["--rules", str(RULES_DIR),
             "--ci",
             "--validators", "schema,priority"]
        )
        assert code == ExitCode.SUCCESS, f"stderr: {err}"

    def test_validator_name_in_output(self):
        code, out, err = _run(
            ["--rules", str(RULES_DIR),
             "--validators", "schema,priority"]
        )
        assert "Schema Validation" in out
        assert "Priority Validation" in out
        assert "Statistics" in out


# ======================================================================
# Individual validator selection
# ======================================================================


class TestValidatorSelection:
    def test_single_validator(self):
        code, out, err = _run(
            ["--rules", str(RULES_DIR), "--validators", "schema"]
        )
        assert code == ExitCode.SUCCESS
        assert "Schema Validation" in out

    def test_multiple_validators(self):
        code, out, err = _run(
            ["--rules", str(RULES_DIR),
             "--validators", "schema,priority"]
        )
        assert code == ExitCode.SUCCESS
        assert "Schema Validation" in out
        assert "Priority Validation" in out

    def test_invalid_validator_name(self):
        code, out, err = _run(
            ["--rules", str(RULES_DIR), "--validators", "nonexistent"]
        )
        assert code == ExitCode.ERROR
        assert "Unknown validator" in err

    def test_validator_list_with_spaces(self):
        code, out, err = _run(
            ["--rules", str(RULES_DIR),
             "--validators", "  schema , priority  "]
        )
        assert code == ExitCode.SUCCESS
        assert "Schema Validation" in out
        assert "Priority Validation" in out


# ======================================================================
# Report formats
# ======================================================================


class TestReportFormats:
    def test_json_report(self):
        code, out, err = _run(
            ["--rules", str(RULES_DIR),
             "--validators", "schema", "--report", "json"]
        )
        assert code == ExitCode.SUCCESS, f"stderr: {err}"
        data = json.loads(out)
        assert data["version"] == VERSION
        assert "summary" in data
        assert "results" in data
        assert "statistics" in data

    def test_json_report_structure(self):
        code, out, err = _run(
            ["--rules", str(RULES_DIR),
             "--validators", "schema", "--report", "json"]
        )
        data = json.loads(out)
        assert data["runtime_version"] == "1.1"
        assert data["summary"]["total_validators"] == 1

    def test_output_file(self):
        with tempfile.NamedTemporaryFile(
            mode="w", suffix=".txt", delete=False
        ) as f:
            outpath = f.name
        try:
            code, out, err = _run(
                ["--rules", str(RULES_DIR),
                 "--validators", "schema",
                 "--report", "human",
                 "--output", outpath]
            )
            assert code == ExitCode.SUCCESS, f"stderr: {err}"
            with open(outpath) as f:
                content = f.read()
            assert "Runtime Specification Validator" in content
        finally:
            os.unlink(outpath)

    def test_output_file_json(self):
        with tempfile.NamedTemporaryFile(
            mode="w", suffix=".json", delete=False
        ) as f:
            outpath = f.name
        try:
            code, out, err = _run(
                ["--rules", str(RULES_DIR),
                 "--validators", "schema",
                 "--report", "json",
                 "--output", outpath]
            )
            assert code == ExitCode.SUCCESS, f"stderr: {err}"
            with open(outpath) as f:
                data = json.load(f)
            assert data["version"] == VERSION
        finally:
            os.unlink(outpath)

    def test_output_to_unwritable_path(self):
        code, out, err = _run(
            ["--rules", str(RULES_DIR),
             "--output", "/nonexistent_dir/output.txt"]
        )
        assert code == ExitCode.ERROR
        assert "Error writing" in err


# ======================================================================
# Error handling
# ======================================================================


class TestErrorHandling:
    def test_missing_rules_dir(self):
        code, out, err = _run(["--rules", "/nonexistent/path"])
        assert code == ExitCode.ERROR
        assert "not found" in err

    def test_missing_release_doc(self):
        code, out, err = _run(
            ["--rules", str(RULES_DIR),
             "--release", "/nonexistent/release.md"]
        )
        assert code == ExitCode.FAILURE

    def test_invalid_report_format(self):
        code, out, err = _run(
            ["--rules", str(RULES_DIR), "--report", "xml"]
        )
        assert code == ExitCode.ERROR
        assert "invalid choice" in err

    def test_malformed_json_file(self):
        """A directory with an invalid JSON file should raise an error."""
        import tempfile, os

        tmpdir = tempfile.mkdtemp()
        try:
            bad_file = os.path.join(tmpdir, "bad.json")
            with open(bad_file, "w") as f:
                f.write("{invalid json}")
            code, out, err = _run(["--rules", tmpdir])
            assert code == ExitCode.ERROR
            assert "Error parsing" in err
        finally:
            import shutil
            shutil.rmtree(tmpdir)


# ======================================================================
# Release validator integration
# ======================================================================


class TestReleaseValidator:
    def test_release_validator_with_doc(self):
        release_doc = (
            Path(__file__).parent.parent.parent.parent /
            "docs" / "ai-os" / "runtime-v1.1-release.md"
        )
        code, out, err = _run(
            ["--rules", str(RULES_DIR),
             "--release", str(release_doc),
             "--validators", "release"]
        )
        assert code == ExitCode.SUCCESS, f"stderr: {err}"

    def test_release_with_all_validators(self):
        release_doc = (
            Path(__file__).parent.parent.parent.parent /
            "docs" / "ai-os" / "runtime-v1.1-release.md"
        )
        code, out, err = _run(
            ["--rules", str(RULES_DIR),
             "--release", str(release_doc),
             "--validators", "schema,release"]
        )
        assert code == ExitCode.SUCCESS, f"stderr: {err}"


# ======================================================================
# CI mode
# ======================================================================


class TestCIMode:
    def test_ci_exit_on_first_failure(self):
        """CI mode should exit on first failure."""
        # Use a single fixture that crossref will fail on
        fixture = str(
            Path(__file__).parent / "fixtures" / "crossref" / "direct_cycle.json"
        )
        code, out, err = _run(
            ["--rules", fixture,
             "--ci",
             "--validators", "crossref"]
        )
        assert code == ExitCode.FAILURE, f"stdout: {out}"
        assert "FAIL" in out

    def test_ci_with_pass_validators(self):
        code, out, err = _run(
            ["--rules", str(RULES_DIR),
             "--ci",
             "--validators", "schema"]
        )
        assert code == ExitCode.SUCCESS
        assert out.strip() == ""


# ======================================================================
# Help / edge
# ======================================================================


class TestHelp:
    def test_help_text(self):
        code, out, err = _run(["--help"])
        assert code in (ExitCode.SUCCESS, ExitCode.ERROR)
        assert "usage:" in out or "usage:" in err

    def test_empty_argv_shows_error(self):
        """Running with no --rules should show error."""
        code, out, err = _run([])
        assert code == ExitCode.ERROR
        assert "required" in err


# ======================================================================
# Parser smoke tests
# ======================================================================


class TestParser:
    def test_parser_rejects_unknown_args(self):
        code, out, err = _run(["--rules", str(RULES_DIR), "--nonexistent"])
        assert code == ExitCode.ERROR

    def test_parser_default_report_is_human(self):
        p = build_parser()
        ns = p.parse_args(["--rules", "/tmp"])
        assert ns.report == "human"

    def test_parser_default_output_is_none(self):
        p = build_parser()
        ns = p.parse_args(["--rules", "/tmp"])
        assert ns.output is None

    def test_parser_default_ci_is_false(self):
        p = build_parser()
        ns = p.parse_args(["--rules", "/tmp"])
        assert ns.ci is False
