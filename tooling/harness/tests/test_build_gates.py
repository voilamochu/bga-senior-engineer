"""Unit tests for the build gates B1-B4 (MS-06, MVB-016)."""

import json
from pathlib import Path

import pytest

from tooling.harness.util.proc import CommandLog
from tooling.harness.validation.build_gates import (
    parse_inventory,
    run_build_gates,
)
from tooling.harness.tests.validation_fixtures import (
    CHANGED_JS,
    EXTRA_FILE_PHP,
    MALFORMED_JSON,
    PASSING_GAME_PHP,
    PASSING_SUBSYSTEMS_MD,
)

PHP = pytest.importorskip("tooling.harness.tests")  # noqa: F401


def _run(tmp_path, *, changed_files, subsystems_md=None, evidence=None):
    diff_bundle = tmp_path / "diff"
    if changed_files is not None:
        diff_bundle.mkdir()
        for relpath, content in changed_files.items():
            path = diff_bundle / relpath
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_text(content, encoding="utf-8")
    command_log = CommandLog(tmp_path / "command.log")
    return run_build_gates(
        diff_bundle=diff_bundle if changed_files is not None else None,
        changed_paths=sorted(changed_files or []),
        subsystems_md=subsystems_md,
        subsystems_md_evidence=evidence or [],
        command_log=command_log,
    ), command_log


def _verdict(checks, check_id):
    return next(c for c in checks if c.id == check_id).verdict


class TestB1:
    def test_valid_php_passes(self, tmp_path):
        checks, _ = _run(
            tmp_path,
            changed_files={"modules/php/Game.php": PASSING_GAME_PHP},
            subsystems_md=PASSING_SUBSYSTEMS_MD,
        )
        b1 = checks[0]
        assert b1.verdict == "PASS"
        assert b1.tool == "php"
        assert b1.detail.endswith("0 with errors")

    def test_invalid_php_fails(self, tmp_path):
        checks, _ = _run(
            tmp_path,
            changed_files={"modules/php/broken.php": "<?php\nfunction broken( {\n"},
        )
        assert _verdict(checks, "B1") == "FAIL"
        assert any("broken.php" in f for f in checks[0].findings)

    def test_no_changed_php_trivially_passes(self, tmp_path):
        checks, _ = _run(tmp_path, changed_files={"assets/skin.txt": "x"})
        assert _verdict(checks, "B1") == "PASS"

    def test_missing_php_blocks(self, tmp_path, monkeypatch):
        monkeypatch.setattr(
            "tooling.harness.validation.build_gates.shutil.which",
            lambda name: None if name == "php" else f"/usr/bin/{name}",
        )
        checks, _ = _run(
            tmp_path,
            changed_files={"modules/php/Game.php": PASSING_GAME_PHP},
        )
        assert _verdict(checks, "B1") == "BLOCKED"
        assert "php" in checks[0].detail


class TestB2:
    def test_valid_js_passes(self, tmp_path):
        checks, _ = _run(tmp_path, changed_files={"modules/js/Game.js": CHANGED_JS})
        assert _verdict(checks, "B2") == "PASS"

    def test_invalid_js_fails(self, tmp_path):
        checks, _ = _run(
            tmp_path, changed_files={"modules/js/broken.js": "function f( {}\n"}
        )
        assert _verdict(checks, "B2") == "FAIL"
        assert any("broken.js" in f for f in checks[1].findings)

    def test_no_changed_js_trivially_passes(self, tmp_path):
        checks, _ = _run(tmp_path, changed_files={"modules/php/Game.php": PASSING_GAME_PHP})
        assert _verdict(checks, "B2") == "PASS"

    def test_missing_node_blocks(self, tmp_path, monkeypatch):
        monkeypatch.setattr(
            "tooling.harness.validation.build_gates.shutil.which",
            lambda name: None if name == "node" else f"/usr/bin/{name}",
        )
        checks, _ = _run(tmp_path, changed_files={"modules/js/Game.js": CHANGED_JS})
        assert _verdict(checks, "B2") == "BLOCKED"


class TestB3:
    def test_valid_json_and_jsonc_pass(self, tmp_path):
        checks, _ = _run(
            tmp_path,
            changed_files={
                "gameinfos.json": json.dumps({"a": 1}),
                "gameoptions.jsonc": '{\n  // comment\n  "b": 2\n}\n',
            },
        )
        assert _verdict(checks, "B3") == "PASS"

    def test_malformed_json_fails(self, tmp_path):
        checks, _ = _run(tmp_path, changed_files={"bad.json": MALFORMED_JSON})
        assert _verdict(checks, "B3") == "FAIL"
        assert any("bad.json" in f for f in checks[2].findings)

    def test_sql_files_excluded_with_note(self, tmp_path):
        checks, _ = _run(tmp_path, changed_files={"dbmodel.sql": "CREATE TABLE t ();"})
        b3 = checks[2]
        assert b3.verdict == "PASS"
        assert "sql" in b3.raw_text.lower()


class TestB4:
    def test_all_changed_files_declared_passes(self, tmp_path):
        checks, _ = _run(
            tmp_path,
            changed_files={"modules/php/Game.php": PASSING_GAME_PHP},
            subsystems_md=PASSING_SUBSYSTEMS_MD,
        )
        assert _verdict(checks, "B4") == "PASS"

    def test_undeclared_file_fails(self, tmp_path):
        checks, _ = _run(
            tmp_path,
            changed_files={
                "modules/php/Game.php": PASSING_GAME_PHP,
                "modules/php/extra.php": EXTRA_FILE_PHP,
            },
            subsystems_md=PASSING_SUBSYSTEMS_MD,
        )
        b4 = checks[3]
        assert b4.verdict == "FAIL"
        assert any("extra.php" in f for f in b4.findings)

    def test_missing_inventory_blocks(self, tmp_path):
        checks, _ = _run(
            tmp_path, changed_files={"modules/php/Game.php": PASSING_GAME_PHP}
        )
        assert _verdict(checks, "B4") == "BLOCKED"
        assert "subsystems.md" in checks[3].detail


class TestMissingDiffBundle:
    def test_all_gates_blocked(self, tmp_path):
        checks, _ = _run(tmp_path, changed_files=None)
        assert [c.verdict for c in checks] == ["BLOCKED", "BLOCKED", "BLOCKED", "BLOCKED"]
        assert all("diff bundle" in c.detail for c in checks)

    def test_build_gates_never_touch_a_reference_repo(self, tmp_path):
        reference = tmp_path / "reference-repo"
        reference.mkdir()
        (reference / "module.php").write_text("<?php\n", encoding="utf-8")
        checks, _ = _run(
            tmp_path,
            changed_files={"modules/php/Game.php": PASSING_GAME_PHP},
            subsystems_md=PASSING_SUBSYSTEMS_MD,
        )
        # the reference repository contains only its original file
        assert sorted(p.name for p in reference.iterdir()) == ["module.php"]
        assert (reference / "module.php").read_text() == "<?php\n"


class TestInventoryParsing:
    def test_parses_appendix_a_table(self):
        declared = parse_inventory(PASSING_SUBSYSTEMS_MD)
        assert declared == {"modules/php/Game.php"}

    def test_ignores_header_and_separator_rows(self):
        md = "| File | Status (A/M/D) | Subsystem | Purpose |\n|---|---|---|---|\n"
        assert parse_inventory(md) == set()

    def test_normalizes_path_prefixes(self):
        md = "| ./modules/php/Game.php | M | Game.php | x |\n"
        assert parse_inventory(md) == {"modules/php/Game.php"}

    def test_accepts_all_statuses(self):
        md = (
            "| a.php | A | x | y |\n"
            "| b.php | M | x | y |\n"
            "| c.php | D | x | y |\n"
        )
        assert parse_inventory(md) == {"a.php", "b.php", "c.php"}
