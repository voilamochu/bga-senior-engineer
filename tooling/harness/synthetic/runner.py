"""Synthetic end-to-end validation (MS-09, MVB-025).

Runs the complete benchmark pipeline — P0 through P9 — deterministically
with no LLM, no opencode, and no external AI provider.  A deterministic
stub execution produces the submission (MS-04), and deterministic
fixture submissions drive every downstream stage (MS-05 … MS-08) with
no manual intervention.

Deterministic execution mode (documented deviation from real-run
behavior): the runner pins the wall clock to a fixed timestamp across
every module, fixes ``time.monotonic`` (command-log wall times), and
normalizes the runtime validator's own timestamp line — the only
externally generated raw-log value a real run records.  Real runs keep
their natural variability; synthetic runs are byte-deterministic end to
end, so the milestone's determinism checks compare identical bytes.
"""

from __future__ import annotations

import json
import re
import subprocess
import time as _time
from dataclasses import replace
from datetime import datetime, timezone
from pathlib import Path

from tooling.harness.config import repo_root
from tooling.harness.util.clock import format_iso

# The fixed instant of the synthetic run (all recorded timestamps).
SYNTHETIC_NOW = datetime(2026, 1, 1, 12, 0, 0, tzinfo=timezone.utc)
SYNTHETIC_ISO = format_iso(SYNTHETIC_NOW)
SYNTHETIC_DATETIME = SYNTHETIC_NOW

# The fixture's known scores (family NOTIF): total = 80.25 -> ACCEPTABLE.
SYNTHETIC_SCORES = {
    "Correctness": 80,
    "Architecture": 90,
    "Framework_Compliance": 85,
    "Maintainability": 70,
    "Testing": 75,
}
SYNTHETIC_REVIEWER = "synthetic-evaluator"

# Citations used in the filled manual-review.md (frozen evidence paths).
SYNTHETIC_CITATIONS = (
    "evidence/e1-transcript.txt; evidence/e8-diff-bundle/modules/php/Game.php"
)

# The runtime validator's human report carries its own timestamp line;
# it is the only externally generated raw-log value in the run data.
_VALIDATOR_TIMESTAMP = re.compile(
    r"Timestamp: \d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z"
)

# Default validation-dataset seed location (implementation plan E10).
DEFAULT_DATASET_PATH = (
    repo_root() / "docs" / "evaluation" / "validation-dataset" / "pilot-0.json"
)

# Clock bindings per module, keyed by attribute name.
_CLOCK_PATCHES = (
    ("tooling.harness.cli", "utc_now"),
    ("tooling.harness.runtime.run_dir", "utc_now"),
    ("tooling.harness.evidence.collect", "utc_now"),
    ("tooling.harness.evidence.freeze", "utc_now"),
    ("tooling.harness.agent.runtime", "utc_now"),
    ("tooling.harness.agent.stub_adapter", "utc_now"),
    ("tooling.harness.agent.opencode_adapter", "utc_now"),
    ("tooling.harness.runtime.manifest", "utc_now_iso"),
    ("tooling.harness.runtime.status", "utc_now_iso"),
    ("tooling.harness.util.log", "utc_now_iso"),
    ("tooling.harness.util.proc", "utc_now_iso"),
    ("tooling.harness.validation.gates", "utc_now_iso"),
    ("tooling.harness.report.generator", "utc_now_iso"),
    ("tooling.harness.archive.manager", "utc_now_iso"),
    ("tooling.harness.scoring.runner", "utc_now_iso"),
    ("tooling.harness.safety.baseline", "utc_now_iso"),
)


class SyntheticError(Exception):
    """The synthetic benchmark failed (a stage exited nonzero or a
    verification check failed)."""


def run_synthetic(
    *,
    task: str = "NOT-02",
    runs_root: str | Path,
    variant: str = "passing",
    dataset_path: str | Path | None = None,
) -> dict:
    """Execute the full pipeline deterministically; verify and summarize.

    Returns a summary dict ``{run_id, runs_root, run_root, status,
    verdict, variant, artifacts, verification}``.  Raises
    :class:`SyntheticError` when a stage fails or the run does not
    verify.  All patches (deterministic clock, sanitizer, scratch
    material) are restored before returning.
    """
    if variant not in ("passing", "failing"):
        raise SyntheticError(f"unknown variant {variant!r} (expected passing|failing)")

    restorers = [
        _patch_clock(),
        _patch_monotonic(),
        _patch_run_cmd_sanitizer(Path(runs_root)),
        _patch_material_roots(Path(runs_root)),
    ]
    try:
        reference = _make_reference_repo(Path(runs_root))
        from tooling.harness.cli import main

        stages = [
            ("p0-init", ["init", task, "demo-model", "--runs-root", str(runs_root)]),
            ("p1-prepare", ["prepare", None, "--reference", str(reference),
                            "--runs-root", str(runs_root)]),
            ("p2-prompt", ["prompt", None, "--runs-root", str(runs_root)]),
            ("p3-session", ["session", None, "--platform", "stub", "--timeout-min", "1",
                            "--runs-root", str(runs_root)]),
        ]
        _run_init(stages[0][1], runs_root, task)
        run_id = _discover_run_id(runs_root)
        for _, argv in stages[1:]:
            argv[1] = run_id
            _run_stage(_, argv)

        run_root = Path(runs_root) / run_id
        _install_submission(run_root, variant=variant)

        for name, argv in (
            ("p4-evidence", ["collect", run_id, "--freeze", "--runs-root", str(runs_root)]),
            ("p5-gates", ["gates", run_id, "--reference", str(reference),
                          "--runs-root", str(runs_root)]),
        ):
            _run_stage(name, argv)

        if variant == "passing":
            for name, argv in (
                ("p6-review", ["review", run_id, "--scaffold", "--runs-root", str(runs_root)]),
            ):
                _run_stage(name, argv)
            _fill_review_md(run_root)
            for name, argv in (
                ("p7-score", ["score", run_id, "--scores",
                              json.dumps(SYNTHETIC_SCORES), "--reviewer",
                              SYNTHETIC_REVIEWER, "--runs-root", str(runs_root)]),
            ):
                _run_stage(name, argv)
        for name, argv in (
            ("p8-report", ["report", run_id, "--runs-root", str(runs_root)]),
            ("p9-archive", ["archive", run_id, "--runs-root", str(runs_root)]),
        ):
            _run_stage(name, argv)

        verification = _verify_run(run_root, runs_root, variant=variant)
        if not verification["passed"]:
            raise SyntheticError(
                "synthetic run failed verification: "
                + "; ".join(verification["divergences"])
            )
        if variant == "passing":
            seed = _record_pilot0_seed(
                run_root, runs_root, dataset_path=dataset_path
            )
        else:
            seed = None
    finally:
        for restore in restorers:
            restore()

    return {
        "run_id": run_id,
        "runs_root": str(runs_root),
        "run_root": str(run_root),
        "status": "ARCHIVED",
        "variant": variant,
        "verification": verification,
        "seed": seed,
        "reference": str(reference),
    }


# ----------------------------------------------------------------------
# Deterministic setup
# ----------------------------------------------------------------------

def _patch_clock() -> callable:
    """Pin every module's bound clock to the fixed synthetic instant."""
    import importlib

    saved = []
    for module_name, attribute in _CLOCK_PATCHES:
        module = importlib.import_module(module_name)
        saved.append((module, attribute, getattr(module, attribute)))
        if attribute == "utc_now":
            setattr(module, attribute, lambda: SYNTHETIC_NOW)
        else:
            setattr(module, attribute, lambda: SYNTHETIC_ISO)

    def restore():
        for module, attribute, original in saved:
            setattr(module, attribute, original)

    return restore


def _patch_monotonic() -> callable:
    """Fix ``time.monotonic`` so command-log wall times are deterministic."""
    original = _time.monotonic
    _time.monotonic = lambda: 1.0

    def restore():
        _time.monotonic = original

    return restore


def _patch_run_cmd_sanitizer(runs_root: Path) -> callable:
    """Normalize volatile values in command records and outputs.

    Two externally generated values differ across synthetic runs and are
    normalized (documented): the runtime validator's own timestamp line,
    and the runs-root absolute paths embedded in commands and tool
    outputs (the stub's ``mkdir``/``cp`` commands, ``php -l``/``node
    --check`` diagnostics).  The wrapper runs the real command and
    rewrites those values, then logs the normalized record, so the E3
    command log, raw outputs, and derived hashes are byte-deterministic.
    """
    from tooling.harness.util.proc import CommandLog, run_cmd

    original = run_cmd
    root_text = str(runs_root.resolve())

    def _normalize(text: str) -> str:
        text = _VALIDATOR_TIMESTAMP.sub(f"Timestamp: {SYNTHETIC_ISO}", text)
        return text.replace(root_text, "<runs-root>")

    def wrapped(cmd, *, log_file=None, cwd=None, timeout=None, env=None):
        result = original(cmd, log_file=None, cwd=cwd, timeout=timeout, env=env)
        sanitized = replace(
            result,
            command=_normalize(result.command),
            stdout=_normalize(result.stdout or ""),
            stderr=_normalize(result.stderr or ""),
        )
        if log_file is not None:
            CommandLog(log_file).record(sanitized)
        return sanitized

    import importlib

    # Every module that bound run_cmd at import time must see the
    # sanitizer (CommandLog resolves the module global dynamically, so
    # tooling.harness.util.proc is patched directly).
    saved = []
    for module_name in (
        "tooling.harness.util.proc",
        "tooling.harness.environment.collect",
        "tooling.harness.safety.baseline",
        "tooling.harness.workspace.provision",
    ):
        module = importlib.import_module(module_name)
        saved.append((module, getattr(module, "run_cmd")))
        setattr(module, "run_cmd", wrapped)

    def restore():
        proc_module = importlib.import_module("tooling.harness.util.proc")
        proc_module.run_cmd = original
        for module, bound in saved:
            if module.__name__ != "tooling.harness.util.proc":
                setattr(module, "run_cmd", bound)

    return restore


def _patch_material_roots(runs_root: Path) -> callable:
    """Provision scratch material roots (never the real repository trees)."""
    import tooling.harness.workspace.provision as provision

    material = runs_root / "synthetic-material"
    for name in ("docs", "bga-senior-engineer-skill", "tooling",
                 "official-docs", "reference-projects"):
        directory = material / name
        directory.mkdir(parents=True, exist_ok=True)
        (directory / f"{name.replace('-', '_')}.txt").write_text(
            name, encoding="utf-8"
        )
    original = provision.default_material_roots
    provision.default_material_roots = lambda: {
        name: material / name
        for name in ("docs", "bga-senior-engineer-skill", "tooling",
                     "official-docs", "reference-projects")
    }

    def restore():
        provision.default_material_roots = original

    return restore


def _make_reference_repo(runs_root: Path) -> Path:
    """Deterministic scratch reference repository (stands in for bga-mercurio)."""
    repo = runs_root / "synthetic-reference"
    if repo.is_dir():
        return repo
    repo.mkdir(parents=True)
    env = {
        **_os_environ(),
        "GIT_CONFIG_GLOBAL": "/dev/null",
        "GIT_CONFIG_SYSTEM": "/dev/null",
        "GIT_AUTHOR_NAME": "synthetic",
        "GIT_AUTHOR_EMAIL": "synthetic@example.invalid",
        "GIT_COMMITTER_NAME": "synthetic",
        "GIT_COMMITTER_EMAIL": "synthetic@example.invalid",
        # pinned commit timestamps: the reference HEAD must be
        # byte-deterministic across synthetic runs
        "GIT_AUTHOR_DATE": "2026-01-01T12:00:00Z",
        "GIT_COMMITTER_DATE": "2026-01-01T12:00:00Z",
    }
    subprocess.run(["git", "init", "-b", "main"], cwd=repo, env=env,
                   check=True, capture_output=True, text=True)
    (repo / "file.txt").write_text("one\n", encoding="utf-8")
    subprocess.run(["git", "add", "."], cwd=repo, env=env,
                   check=True, capture_output=True, text=True)
    subprocess.run(
        ["git", "-c", "user.name=synthetic", "-c", "user.email=synthetic@example.invalid",
         "commit", "-m", "synthetic reference commit"],
        cwd=repo, env=env, check=True, capture_output=True, text=True,
    )
    return repo


def _os_environ() -> dict:
    import os

    return dict(os.environ)


# ----------------------------------------------------------------------
# Pipeline orchestration
# ----------------------------------------------------------------------

def _run_init(argv, runs_root, task) -> str:
    from tooling.harness.cli import main

    exit_code = main(list(argv))
    if exit_code != 0:
        raise SyntheticError(f"stage init failed with exit {exit_code}")
    return _discover_run_id(runs_root)


def _discover_run_id(runs_root) -> str:
    candidates = [
        p.name for p in Path(runs_root).iterdir()
        if p.is_dir() and p.name.startswith("run-")
    ]
    if len(candidates) != 1:
        raise SyntheticError(
            f"expected exactly one run directory under {runs_root}, "
            f"found {len(candidates)}"
        )
    return candidates[0]


def _run_stage(name: str, argv: list[str]) -> None:
    from tooling.harness.cli import main

    exit_code = main(list(argv))
    if exit_code != 0:
        raise SyntheticError(f"stage {name} failed with exit {exit_code}")


def _fill_review_md(run_root: Path) -> None:
    """Fill the scaffolded manual-review.md deterministically.

    The review stage consumes the scaffolded template: per-category
    evidence citations and the synthetic reviewer identity.  The scores
    themselves are supplied via ``--scores`` (the fixture's known
    scores).
    """
    path = run_root / "review" / "manual-review.md"
    md = path.read_text(encoding="utf-8")
    md = md.replace("| Reviewer |  |", f"| Reviewer | {SYNTHETIC_REVIEWER} |")
    for category in ("Correctness", "Architecture", "Framework Compliance",
                     "Maintainability", "Testing"):
        md = md.replace(
            f"| {category} |  |  |  |  |  | no |",
            f"| {category} |  | {SYNTHETIC_CITATIONS} | "
            "synthetic fixture: consolidated helper verified | | | no |",
        )
    path.write_text(md, encoding="utf-8")


def _install_submission(run_root: Path, *, variant: str) -> None:
    """Install the deterministic fixture submission into the work area.

    The stub execution (MS-04) writes its own deterministic submission;
    the synthetic fixture replaces the diff bundle with the NOT-02
    fixture pair (passing or failing), exercising every downstream stage
    with known-good inputs.
    """
    import shutil

    from tooling.harness.tests.validation_fixtures import (
        DUPLICATED_GAME_PHP,
        PASSING_GAME_PHP,
        PASSING_SUBSYSTEMS_MD,
    )

    work = run_root / "workspace" / "work"
    changes = work / "changes"
    shutil.rmtree(changes)
    game = DUPLICATED_GAME_PHP if variant == "failing" else PASSING_GAME_PHP
    for relpath, content in (
        ("changes/modules/php/Game.php", game),
        ("subsystems.md", PASSING_SUBSYSTEMS_MD),
    ):
        path = work / relpath
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(content, encoding="utf-8")


# ----------------------------------------------------------------------
# Verification
# ----------------------------------------------------------------------

def _verify_run(run_root: Path, runs_root, *, variant: str) -> dict:
    """Verify every stage's artifact; returns ``{passed, divergences}``."""
    from tooling.harness.archive.manager import verify_archive
    from tooling.harness.report.generator import validate_report
    from tooling.harness.runtime.manifest import RunManifest
    from tooling.harness.runtime.run_dir import load_run_dir
    from tooling.harness.runtime.status import RunStatus

    divergences: list[str] = []
    run = load_run_dir(run_root.name, runs_root)
    manifest = RunManifest.load(run.manifest_path)
    status = RunStatus.load(run.status_path)
    if status.status != "ARCHIVED":
        divergences.append(f"run status is {status.status}, expected ARCHIVED")

    validation = json.loads((run.validation / "validation.json").read_text(encoding="utf-8"))
    summary = validation.get("summary", {})
    if variant == "passing":
        if summary.get("verdict") != "PASS":
            divergences.append(f"validation summary verdict is {summary.get('verdict')}")
        if not all(
            validation["gates"][g].get("verdict") == "PASS" for g in ("G0", "G1", "G2")
        ):
            divergences.append("not every gate (G0-G2) passed")
        scores = json.loads((run.review_scoring / "scores.json").read_text(encoding="utf-8"))
        if scores.get("verdict") != "ACCEPTABLE" or scores["arithmetic"]["total"] != 80.25:
            divergences.append("scores.json does not carry the fixture's known verdict")
        review = json.loads((run.review / "review.json").read_text(encoding="utf-8"))
        if review.get("status") != "COMPLETED":
            divergences.append("review record is not COMPLETED")
        execution = manifest.execution or {}
        if execution.get("platform") != "stub":
            divergences.append(
                "no LLM expected: execution platform must be the stub adapter"
            )
    else:
        if summary.get("verdict") != "REJECTED":
            divergences.append("failing variant must be REJECTED at P5")
        if "NOT02-A" not in summary.get("blocking_failures", []):
            divergences.append("failing check NOT02-A not identified")
        if (run.review / "review.json").exists():
            divergences.append("REJECTED runs must not be reviewed")

    for report_name in ("evaluation-report.json", "report.md"):
        if not (run.reports / report_name).is_file():
            divergences.append(f"missing report: reports/{report_name}")
    md_path = run.reports / "report.md"
    json_path = run.reports / "evaluation-report.json"
    if md_path.is_file() and json_path.is_file():
        report_divergences = validate_report(
            md_path.read_text(encoding="utf-8"),
            json.loads(json_path.read_text(encoding="utf-8")),
        )
        divergences.extend(report_divergences)

    archive = verify_archive(run, manifest, status, runs_root=runs_root)
    if not archive["passed"]:
        divergences.extend(archive["divergences"])
    return {"passed": not divergences, "divergences": divergences}


# ----------------------------------------------------------------------
# Pilot-0 validation-dataset seed (implementation plan E10)
# ----------------------------------------------------------------------

def _record_pilot0_seed(run_root: Path, runs_root, *, dataset_path) -> Path | None:
    """Record the synthetic fixture run as the ``pilot-0`` dataset seed."""
    from tooling.harness.report.data import REPORT_SCHEMA  # noqa: F401
    from tooling.harness.runtime.manifest import RunManifest

    path = Path(dataset_path) if dataset_path is not None else DEFAULT_DATASET_PATH
    manifest = RunManifest.load(run_root / "manifest.json")
    scores = json.loads((run_root / "review" / "scoring" / "scores.json").read_text(encoding="utf-8"))
    verification = json.loads(
        (run_root / "review" / "scoring" / "score-verification.json").read_text(encoding="utf-8")
    )
    validation = json.loads((run_root / "validation" / "validation.json").read_text(encoding="utf-8"))
    g0 = validation.get("gates", {}).get("G0", {}).get("verdict", "unknown")

    versions = manifest.versions or {}
    seed = {
        "schema": "benchmark-validation-dataset/1.0",
        "cycle": {
            "id": "pilot-0",
            "type": "pilot",
            "version_tuple": {
                "corpus": versions.get("corpus"),
                "evaluation": versions.get("evaluation"),
                "harness": versions.get("harness"),
            },
            "date": SYNTHETIC_ISO,
            "seed": True,
            "note": "synthetic fixture run (MS-09, MVB-025); statistics, "
            "coverage, anchors, and certification fields are deferred post-MVB",
        },
        "runs": [
            {
                "run_id": run_root.name,
                "task": (manifest.task or {}).get("id"),
                "system": "synthetic-stub",
                "status": "ARCHIVED",
                "verdict": scores.get("verdict"),
                "total": scores.get("arithmetic", {}).get("capped_total"),
                "category_scores": scores.get("category_scores", {}),
            }
        ],
        "scoring_pairs": [
            {
                "run_id": run_root.name,
                "first": verification.get("double_computation", {}).get("first"),
                "second": verification.get("double_computation", {}).get("second"),
                "matched": verification.get("double_computation", {}).get("matched"),
            }
        ],
        "safety": {
            "reference_repo": "scratch synthetic reference (synthetic-reference/)",
            "g0": g0,
            "note": "synthetic runs verify against a scratch reference "
            "repository; bga-mercurio is never touched",
        },
        "stats": {},
        "evaluators": [],
        "coverage": {},
        "anchors": [],
        "revisions": [],
        "tooling": {},
        "certification": {},
    }
    path.parent.mkdir(parents=True, exist_ok=True)
    if path.is_file():
        existing = json.loads(path.read_text(encoding="utf-8"))
        if existing != seed:
            raise SyntheticError(
                f"validation-dataset seed {path} already exists with "
                "different content; entries are immutable (errata for "
                "corrections)"
            )
        return path
    path.write_text(json.dumps(seed, indent=2, sort_keys=True) + "\n", encoding="utf-8")
    return path
