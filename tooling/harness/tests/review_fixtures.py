"""Shared deterministic fixtures for MS-07 review/scoring tests.

Builds on the MS-06 fixtures: a run at the P4 frozen stage with a
submission, run through the real G0-G2 gates (P5), then scaffolded for
manual review.  ``fill_review_md`` produces the reviewed working file
deterministically.
"""

from __future__ import annotations

from tooling.harness.runtime.manifest import RunManifest
from tooling.harness.runtime.status import RunStatus
from tooling.harness.tests.validation_fixtures import (
    build_frozen_run,
    passing_submission,
)
from tooling.harness.util.log import harness_log
from tooling.harness.util.proc import CommandLog
from tooling.harness.validation.gates import run_gates

# The MS-07 acceptance example scores (evaluation family NOTIF):
# total = 80×0.40 + 90×0.10 + 85×0.25 + 70×0.15 + 75×0.10 = 80.25
ACCEPTANCE_SCORES = {
    "Correctness": 80,
    "Architecture": 90,
    "Framework_Compliance": 85,
    "Maintainability": 70,
    "Testing": 75,
}

# Frozen evidence artifacts present in every build_frozen_run fixture.
DEFAULT_CITATIONS = (
    "evidence/e1-transcript.txt; evidence/e8-diff-bundle/modules/php/Game.php"
)


def make_gated_run(tmp_path, *, reference_repo, submission=None, task="NOT-02"):
    """A run with P0-P4 frozen evidence and completed P5 gates (G0-G2)."""
    run, manifest, status = build_frozen_run(
        tmp_path,
        reference_repo=reference_repo,
        work_files=submission if submission is not None else passing_submission(),
        run_task=task,
    )
    command_log = CommandLog(run.root / "protocol" / "command.log")
    run_gates(
        run,
        manifest,
        status,
        reference_root=reference_repo,
        command_log=command_log,
        log=harness_log(None),
    )
    return run, RunManifest.load(run.manifest_path), RunStatus.load(run.status_path)


def fill_review_md(
    run,
    *,
    reviewer: str = "evaluator-1",
    citations: str = DEFAULT_CITATIONS,
    scores: dict[str, int] | None = None,
    critical: dict[str, bool] | None = None,
    comments: str = "solid evidence",
    critical_codes: list[str] | None = None,
) -> None:
    """Fill the scaffolded manual-review.md deterministically.

    *scores* maps canonical category names to scores (only filled rows);
    *critical* marks per-category critical-failure flags; *critical_codes*
    are C1-C9 entries in the critical-failures section.
    """
    path = run.review / "manual-review.md"
    md = path.read_text(encoding="utf-8")
    md = md.replace("| Reviewer |  |", f"| Reviewer | {reviewer} |")
    for category in ("Correctness", "Architecture", "Framework Compliance",
                     "Maintainability", "Testing"):
        score = ""
        if scores and category in scores:
            score = str(scores[category])
        flag = "yes" if critical and critical.get(category) else "no"
        md = md.replace(
            f"| {category} |  |  |  |  |  | no |",
            f"| {category} | {score} | {citations} | {comments} | | | {flag} |",
        )
    if critical_codes:
        md = md.replace(
            "- none",
            "\n".join(f"- {code}" for code in critical_codes),
        )
    path.write_text(md, encoding="utf-8")


def scaffolded_run(tmp_path, *, reference_repo, task="NOT-02", submission=None):
    """A gated run with the review package scaffolded (P6 start)."""
    from tooling.harness.review.kit import scaffold_review

    run, manifest, status = make_gated_run(
        tmp_path, reference_repo=reference_repo, submission=submission, task=task
    )
    scaffold_review(run, manifest, status)
    return run, RunManifest.load(run.manifest_path), RunStatus.load(run.status_path)


def reviewed_run(
    tmp_path, *, reference_repo, task="NOT-02", submission=None,
    scores=None, reviewer="evaluator-1", critical_codes=None,
):
    """A gated + scaffolded + scored run (P7 complete, status VERDICTED)."""
    from tooling.harness.review.kit import scaffold_review
    from tooling.harness.scoring.runner import run_scoring

    run, manifest, status = make_gated_run(
        tmp_path, reference_repo=reference_repo, submission=submission, task=task
    )
    scaffold_review(run, manifest, status)
    fill_review_md(
        run, reviewer=reviewer, critical_codes=critical_codes,
    )
    run_scoring(
        run, manifest, status,
        scores_json=scores if scores is not None else ACCEPTANCE_SCORES,
        reviewer=reviewer,
    )
    return run, RunManifest.load(run.manifest_path), RunStatus.load(run.status_path)


def rejected_run(tmp_path, *, reference_repo):
    """A gated run that was REJECTED by a blocking G2 check (no review)."""
    from tooling.harness.tests.validation_fixtures import duplicated_submission

    run, manifest, status = make_gated_run(
        tmp_path, reference_repo=reference_repo, submission=duplicated_submission()
    )
    assert status.status == "REJECTED"
    return run, RunManifest.load(run.manifest_path), RunStatus.load(run.status_path)
