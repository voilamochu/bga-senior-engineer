"""Score orchestration (MS-07, MVB-020 + MVB-021).

``python -m tooling.harness score <run-id> --scores JSON`` drives P7:

1. validates the run state (COMPLETED/TIMEOUT, frozen evidence,
   scaffolded review, rubric version consistency, validation results),
2. resolves the per-category records from ``manual-review.md`` and the
   ``--scores`` JSON (plain numbers or full records), validating every
   evidence citation against the frozen evidence catalog,
3. computes total and verdict with the §7.5 double computation — a
   mismatch is recorded in ``score-verification.json`` and invalidates
   the run (never silent),
4. persists ``scores.json`` + ``score-verification.json``, completes the
   review record, transitions the run to VERDICTED (§2.0.2), and records
   reviewer metadata, review timestamps, review version, and review
   artifact hashes in the frozen manifest via the errata channel.

``scores.json`` and ``score-verification.json`` contain no wall-clock
values: identical inputs produce byte-identical records.
"""

from __future__ import annotations

import json
from pathlib import Path

from tooling.harness.config import repo_root, read_pinned_versions
from tooling.harness.evidence.collect import load_evidence_catalog
from tooling.harness.review.parser import (
    parse_category_table,
    parse_critical_codes,
    parse_reviewer,
)
from tooling.harness.review.state import ReviewState, ReviewStateError, review_json_path
from tooling.harness.runtime.manifest import RunManifest
from tooling.harness.runtime.run_dir import RunDir
from tooling.harness.runtime.status import RunStatus
from tooling.harness.scoring.calculator import (
    ScoreError,
    double_compute,
    validate_scores,
)
from tooling.harness.scoring.persist import (
    CategoryRecord,
    build_scores_document,
    build_verification_document,
    write_scores,
    write_verification,
)
from tooling.harness.scoring.rubric import CATEGORIES, RubricError
from tooling.harness.util.clock import utc_now_iso
from tooling.harness.util.hash import sha256_file

SCORABLE_STATUSES = ("COMPLETED", "TIMEOUT")

VALID_VERDICTS = ("EXCELLENT", "ACCEPTABLE", "POOR", "INCORRECT")


class ScoringError(Exception):
    """The run cannot be scored; the review state is left untouched."""


def run_scoring(
    run: RunDir,
    manifest: RunManifest,
    status: RunStatus,
    *,
    scores_json: dict | None = None,
    reviewer: str | None = None,
    recompute: bool = False,
    eval_doc: str | Path | None = None,
) -> dict:
    """Validate, compute, and persist the review's scores (P7).

    Returns a summary dict ``{scores, verification, review, status}``.
    Raises :class:`ScoringError` for any invalid state or input; a
    double-computation mismatch raises with the recorded verification.
    """
    _preflight(run, manifest, status, recompute=recompute)

    state = ReviewState.load(review_json_path(run))
    md_text = (run.review / "manual-review.md").read_text(encoding="utf-8")
    catalog = load_evidence_catalog(run)
    validation = _load_validation(run)

    rubric = _check_rubric(state, eval_doc=eval_doc)
    weights = rubric["weights"]
    md_records = {record.category: record for record in parse_category_table(md_text)}
    normalized_json = _normalize_scores_json(scores_json)
    try:
        resolved = _resolve_records(
            scores_json=normalized_json,
            md_records=md_records,
            weights=weights,
            catalog=catalog,
        )
        scores = {category: record.score for category, record in resolved.items()}
        try:
            validate_scores(scores, weights)
        except ScoreError as exc:
            raise ScoringError(str(exc)) from exc
    except ScoringError:
        # a failed attempt is recorded (partial completion), never silent
        if state.status != "COMPLETED":
            state.note_attempt(reviewer)
            state.save(review_json_path(run))
        raise

    critical_flags = [
        record.category for record in resolved.values() if record.critical_failure
    ]
    critical_codes = parse_critical_codes(md_text)
    critical_failures = sorted(set(critical_codes + [f"category:{c}" for c in critical_flags]))

    non_blocking = validation.get("summary", {}).get("non_blocking_findings", [])
    first, second = double_compute(
        scores,
        weights=weights,
        critical_failures=critical_failures,
        non_blocking_findings=non_blocking,
    )
    matched = first.to_dict() == second.to_dict()

    verification_doc = build_verification_document(
        run_id=run.run_id, first=first, second=second, matched=matched
    )
    if not matched:
        write_verification(run, verification_doc)
        if state.status != "COMPLETED":
            state.note_attempt(reviewer)
        state.save(review_json_path(run))
        raise ScoringError(
            "double computation mismatch: total/verdict differ between the "
            "two independent calculations; scores invalidated and recorded in "
            "score-verification.json (harness §7.5) until reconciled"
        )

    scores_doc = build_scores_document(
        run_id=run.run_id,
        task_id=(manifest.task or {}).get("id"),
        family=rubric["family"],
        weights=weights,
        categories=[resolved[category] for category in CATEGORIES],
        computation=first,
        critical_failures=critical_failures,
        non_blocking_findings=non_blocking,
    )
    write_scores(run, scores_doc)
    write_verification(run, verification_doc)

    reviewer_name = reviewer or parse_reviewer(md_text) or "anonymous"
    artifact_hashes = {
        "review/manual-review.md": sha256_file(run.review / "manual-review.md"),
        "review/g3-not-02.md": sha256_file(run.review / "g3-not-02.md"),
        "review/onboarding.md": sha256_file(run.review / "onboarding.md"),
        "review/scoring/scores.json": sha256_file(run.review_scoring / "scores.json"),
        "review/scoring/score-verification.json": sha256_file(
            run.review_scoring / "score-verification.json"
        ),
    }
    state.complete(
        reviewer=reviewer_name,
        category_scores=scores,
        total=first.capped_total,
        verdict=first.verdict,
        critical_failures=critical_failures,
        artifact_hashes=artifact_hashes,
        recompute=recompute,
    )
    state.save(review_json_path(run))

    now = utc_now_iso()
    if not recompute:
        status.transition("VERDICTED", checkpoint="p7", at=now)
    status.save(run.status_path)

    _record_completion(
        manifest,
        run=run,
        state=state,
        reviewer=reviewer_name,
        completed_at=now,
        hashes=artifact_hashes,
        recompute=recompute,
    )

    return {
        "scores": scores_doc,
        "verification": verification_doc,
        "review": state.to_dict(),
        "status": status.status,
    }


def _preflight(run, manifest, status, *, recompute: bool) -> None:
    if status.status == "REJECTED":
        raise ScoringError(
            f"cannot score {run.run_id}: the run was REJECTED by G0/G1 or a "
            "blocking G2 check; rejected runs are never scored (harness §7.4)"
        )
    if not manifest.frozen:
        raise ScoringError(
            "run manifest is not frozen; complete P4 with 'collect --freeze' first"
        )
    review_path = review_json_path(run)
    if not review_path.is_file():
        raise ScoringError(
            f"no review package for {run.run_id}; run 'review --scaffold' first"
        )
    state = ReviewState.load(review_path)
    if state.status == "COMPLETED" and not recompute:
        raise ScoringError(
            f"review {run.run_id} is already completed (verdict "
            f"{state.verdict}); recalculation requires --recompute "
            "(harness §5.1, recorded as a correction)"
        )
    scorable = status.status in SCORABLE_STATUSES or (
        recompute and status.status == "VERDICTED"
    )
    if not scorable:
        raise ScoringError(
            f"cannot score {run.run_id} in status {status.status} "
            f"(expected {' or '.join(SCORABLE_STATUSES)}; REJECTED runs are "
            "never scored)"
        )
    if not (run.root / "validation" / "validation.json").is_file():
        raise ScoringError(
            "automatic validation results are missing; run 'gates' (P5) first"
        )


def _load_validation(run: RunDir) -> dict:
    path = run.root / "validation" / "validation.json"
    return json.loads(path.read_text(encoding="utf-8"))


def _check_rubric(state: ReviewState, *, eval_doc) -> dict:
    if not state.rubric:
        raise ScoringError("review record carries no rubric; re-scaffold")
    doc = Path(eval_doc) if eval_doc is not None else (
        repo_root() / "docs" / "evaluation" / "benchmark-evaluation-spec.md"
    )
    from tooling.harness.scoring.rubric import task_weights

    try:
        current = task_weights(doc, state.task_id)
    except RubricError as exc:
        raise ScoringError(str(exc)) from exc
    if current != state.rubric["weights"]:
        raise ScoringError(
            "rubric version mismatch: the review's rubric weights "
            f"{state.rubric['weights']} differ from the current evaluation "
            f"specification's {current}; the review is invalidated"
        )
    pinned = read_pinned_versions()
    if state.rubric.get("evaluation_version") != pinned.get("evaluation"):
        raise ScoringError(
            "rubric version mismatch: the review was scaffolded against "
            f"evaluation version {state.rubric.get('evaluation_version')}, "
            f"but the current specification is {pinned.get('evaluation')}"
        )
    return state.rubric


def _normalize_scores_json(scores_json: dict | None) -> dict:
    """Normalize the --scores keys to canonical category names."""
    from tooling.harness.scoring.rubric import normalize_category

    if scores_json is None:
        return None  # type: ignore[return-value]
    normalized: dict = {}
    for key, value in scores_json.items():
        try:
            normalized[normalize_category(key)] = value
        except RubricError as exc:
            raise ScoringError(str(exc)) from exc
    return normalized


def _resolve_records(*, scores_json, md_records, weights, catalog) -> dict[str, CategoryRecord]:
    """Merge the --scores JSON with the manual-review.md records.

    JSON forms: plain ``{"Correctness": 80}`` or full
    ``{"Correctness": {"score": 80, "evidence": [...], "comments": ...,
    "deductions": ..., "uncertainty": ..., "critical_failure": bool}}``.
    A plain number inherits its citations and notes from the md record;
    a full record is self-contained.  Every category's final record must
    cite at least one frozen evidence artifact (§7.5).
    """
    valid_paths = _evidence_paths(catalog)
    resolved: dict[str, CategoryRecord] = {}
    for category in CATEGORIES:
        md = md_records.get(category) or CategoryRecord(category=category)
        if scores_json is None:
            if md.score is None:
                raise ScoringError(
                    f"category {category} has no score; enter it in "
                    "manual-review.md or pass --scores"
                )
            record = CategoryRecord(
                category=category,
                score=md.score,
                evidence=list(md.evidence),
                comments=md.comments,
                deductions=md.deductions,
                uncertainty=md.uncertainty,
                critical_failure=md.critical_failure,
            )
        elif category in scores_json:
            value = scores_json[category]
            if isinstance(value, dict):
                record = _record_from_json(category, value, md)
            else:
                if md.score is not None and md.score != value:
                    raise ScoringError(
                        f"category {category}: --scores value {value} differs "
                        f"from manual-review.md score {md.score}"
                    )
                record = CategoryRecord(
                    category=category,
                    score=value,
                    evidence=list(md.evidence),
                    comments=md.comments,
                    deductions=md.deductions,
                    uncertainty=md.uncertainty,
                    critical_failure=md.critical_failure,
                )
        else:
            if md.score is None:
                raise ScoringError(
                    f"category {category} is missing from --scores and has "
                    "no score in manual-review.md"
                )
            record = CategoryRecord(
                category=category,
                score=md.score,
                evidence=list(md.evidence),
                comments=md.comments,
                deductions=md.deductions,
                uncertainty=md.uncertainty,
                critical_failure=md.critical_failure,
            )
        missing_citations = [
            citation for citation in record.evidence if citation not in valid_paths
        ]
        if not record.evidence:
            raise ScoringError(
                f"category {category} has no evidence citation; every "
                "category score must cite at least one frozen evidence "
                "artifact (harness §7.5)"
            )
        if missing_citations:
            raise ScoringError(
                f"category {category} cites non-frozen paths: "
                + ", ".join(missing_citations)
            )
        resolved[category] = record
    return resolved


def _record_from_json(category: str, value: dict, md: CategoryRecord) -> CategoryRecord:
    score = value.get("score", md.score)
    evidence = value.get("evidence")
    if evidence is not None and not isinstance(evidence, list):
        raise ScoringError(f"category {category}: 'evidence' must be a list")
    return CategoryRecord(
        category=category,
        score=score,
        evidence=[str(item) for item in evidence] if evidence is not None else list(md.evidence),
        comments=str(value.get("comments", md.comments or "")),
        deductions=str(value.get("deductions", md.deductions or "")),
        uncertainty=str(value.get("uncertainty", md.uncertainty or "")),
        critical_failure=bool(value.get("critical_failure", md.critical_failure)),
    )


def _evidence_paths(catalog: dict) -> set[str]:
    paths: set[str] = set()
    for entry in catalog.get("types", {}).values():
        for file_entry in entry.get("files", []):
            paths.add(f"evidence/{file_entry['path']}")
    return paths


def _record_completion(
    manifest: RunManifest, *, run, state, reviewer, completed_at, hashes, recompute=False
) -> None:
    hashes_text = ", ".join(f"{name} sha256={digest}" for name, digest in sorted(hashes.items()))
    action = "recomputed" if recompute else "completed"
    message = (
        f"P7 review {action}: "
        f"reviewer={reviewer} "
        f"status={state.status} "
        f"review_version={state.review_version} "
        f"completed_at={completed_at} "
        f"verdict={state.verdict} total={state.total} "
        f"review.json sha256={sha256_file(review_json_path(run))} "
        f"{hashes_text}"
    )
    manifest.add_errata(message, at=completed_at)
    manifest.save(run.manifest_path)
