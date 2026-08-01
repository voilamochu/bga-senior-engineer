"""Manual review kit — scaffold assembly (MS-07, MVB-019).

``python -m tooling.harness review <run-id> --scaffold`` assembles the
per-run review package in ``review/``:

- ``manual-review.md`` — the reviewer's working file: run metadata,
  rubric (family + weights), the frozen evidence index, the P5
  automatic validation results, the per-category scoring table with
  evidence-citation fields, the required review checklist, and
  reviewer instructions.  It references frozen evidence by path —
  nothing is duplicated.
- ``g3-not-02.md`` — the NOT-02 G3 scenario script, executable from the
  frozen diff bundle alone.
- ``onboarding.md`` — evaluator onboarding (rubric anchors verbatim).
- ``review.json`` — the versioned machine review record (SCAFFOLDED).

The scaffold is deterministic: identical runs produce identical review
package files (no wall-clock values).
"""

from __future__ import annotations

import json
import shutil
from pathlib import Path

from tooling.harness.config import repo_root, read_pinned_versions
from tooling.harness.evidence.collect import EVIDENCE_TYPES, load_evidence_catalog
from tooling.harness.review.state import ReviewState
from tooling.harness.runtime.manifest import RunManifest
from tooling.harness.runtime.run_dir import RunDir
from tooling.harness.runtime.status import RunStatus
from tooling.harness.scoring.rubric import RubricError, task_family, task_weights

TEMPLATES_DIR = Path(__file__).resolve().parent / "templates"
MANUAL_REVIEW_TEMPLATE = TEMPLATES_DIR / "manual-review.md"
G3_TEMPLATE = TEMPLATES_DIR / "g3-not-02.md"
ONBOARDING_FILE = Path(__file__).resolve().parent / "onboarding.md"

# Run statuses from which a manual review may be scaffolded (P5 done;
# REJECTED runs are never reviewed — G0/G1 rejections produce REJECTED,
# not a scoring verdict, harness §7.4).
REVIEWABLE_STATUSES = ("COMPLETED", "TIMEOUT")


class ReviewKitError(Exception):
    """The review package cannot be assembled."""


def scaffold_review(
    run: RunDir,
    manifest: RunManifest,
    status: RunStatus,
    *,
    eval_doc: str | Path | None = None,
) -> dict:
    """Assemble the review package into ``review/``.

    Returns a summary dict ``{files, rubric}``.  Raises
    :class:`ReviewKitError` when the run is not in a reviewable state or
    a review package already exists (the existing package is the resume
    point).
    """
    if status.status not in REVIEWABLE_STATUSES:
        raise ReviewKitError(
            f"cannot scaffold a review for {run.run_id} in status "
            f"{status.status} (expected {' or '.join(REVIEWABLE_STATUSES)})"
        )
    if not manifest.frozen:
        raise ReviewKitError(
            "run manifest is not frozen; complete P4 with 'collect --freeze' first"
        )
    review_path = run.review / "review.json"
    if review_path.is_file():
        raise ReviewKitError(
            f"a review package already exists ({review_path}); it is the "
            "resume point — do not re-scaffold"
        )
    validation_path = run.root / "validation" / "validation.json"
    if not validation_path.is_file():
        raise ReviewKitError(
            "automatic validation results are missing; run 'gates' (P5) first"
        )
    catalog = load_evidence_catalog(run)
    if not catalog.get("frozen"):
        raise ReviewKitError("evidence is not frozen; complete P4 first")

    rubric = _resolve_rubric(manifest, eval_doc=eval_doc)
    validation = json.loads(validation_path.read_text(encoding="utf-8"))

    files = {
        "manual-review.md": _render_manual_review(run, manifest, catalog, validation, rubric),
        "g3-not-02.md": _render_template(G3_TEMPLATE, {"RUN_ID": run.run_id}),
        "onboarding.md": ONBOARDING_FILE.read_text(encoding="utf-8"),
    }
    for name, content in files.items():
        (run.review / name).write_text(content, encoding="utf-8")

    state = ReviewState(
        run_id=run.run_id,
        task_id=(manifest.task or {}).get("id"),
        rubric=rubric,
    )
    state.save(review_path)

    return {
        "files": sorted(files),
        "rubric": rubric,
        "review_path": review_path,
    }


def _resolve_rubric(manifest: RunManifest, *, eval_doc) -> dict:
    doc = Path(eval_doc) if eval_doc is not None else (
        repo_root() / "docs" / "evaluation" / "benchmark-evaluation-spec.md"
    )
    task_id = (manifest.task or {}).get("id")
    if not task_id:
        raise ReviewKitError("run manifest carries no task id")
    try:
        weights = task_weights(doc, task_id)
        family = task_family(doc, task_id)
    except RubricError as exc:
        raise ReviewKitError(str(exc)) from exc
    pinned = read_pinned_versions()
    recorded = (manifest.versions or {}).get("evaluation")
    if recorded is not None and recorded != pinned.get("evaluation"):
        raise ReviewKitError(
            "rubric version mismatch: the run was pinned to evaluation "
            f"version {recorded}, but the current evaluation specification "
            f"is {pinned.get('evaluation')}"
        )
    return {
        "family": family,
        "weights": {category: weights[category] for category in (
            "Correctness", "Architecture", "Framework Compliance",
            "Maintainability", "Testing",
        )},
        "weights_sum": sum(weights.values()),
        "evaluation_version": pinned.get("evaluation"),
    }


def _render_manual_review(run, manifest, catalog, validation, rubric) -> str:
    tokens = {
        "RUN_ID": run.run_id,
        "TASK_ID": (manifest.task or {}).get("id"),
        "TASK_TITLE": _task_title(manifest),
        "DIFFICULTY": (manifest.task or {}).get("difficulty") or "unknown",
        "MODEL": (manifest.model or {}).get("id") or "unknown",
        "CORPUS_VERSION": _version(manifest, "corpus"),
        "EVALUATION_VERSION": _version(manifest, "evaluation"),
        "HARNESS_VERSION": _version(manifest, "harness"),
        "RUNTIME_VERSION": _version(manifest, "runtime"),
        "VALIDATOR_VERSION": _version(manifest, "validator"),
        "REFERENCE_HEAD": _version(manifest, "reference_head") or "unknown",
        "RUBRIC_FAMILY": rubric["family"],
        "WEIGHTS_ROW": _render_weights(rubric),
        "EVIDENCE_ROOT_HASH": catalog.get("root_hash") or "",
        "EVIDENCE_INDEX": _render_evidence_index(catalog),
        "VALIDATION_SUMMARY": _render_validation_summary(validation),
    }
    return _render_template(MANUAL_REVIEW_TEMPLATE, tokens)


def _render_template(path: Path, tokens: dict) -> str:
    text = path.read_text(encoding="utf-8")
    for name, value in tokens.items():
        text = text.replace("{{" + name + "}}", value)
    return text


def _task_title(manifest: RunManifest) -> str:
    task = manifest.task or {}
    if task.get("title"):
        return task["title"]
    return task.get("id") or "unknown"


def _version(manifest: RunManifest, key: str) -> str:
    value = (manifest.versions or {}).get(key)
    return str(value) if value is not None else "unknown"


def _render_weights(rubric: dict) -> str:
    return "\n".join(
        f"- {category}: {rubric['weights'][category]}"
        for category in ("Correctness", "Architecture", "Framework Compliance",
                         "Maintainability", "Testing")
    )


def _render_evidence_index(catalog: dict) -> str:
    lines: list[str] = []
    for etype in EVIDENCE_TYPES:
        entry = catalog["types"].get(etype, {})
        if entry.get("status") == "present":
            paths = ", ".join(f"`evidence/{f['path']}`" for f in entry.get("files", []))
            lines.append(f"- {etype}: present ({paths})")
        else:
            reason = entry.get("reason", "not collected")
            lines.append(f"- {etype}: absent ({reason})")
    return "\n".join(lines)


def _render_validation_summary(validation: dict) -> str:
    lines: list[str] = []
    for gate_id in ("G0", "G1", "G2"):
        gate = validation.get("gates", {}).get(gate_id, {})
        lines.append(
            f"- {gate_id} {gate.get('name', '')}: {gate.get('verdict', 'NOT_RUN')}"
        )
    summary = validation.get("summary", {})
    lines.append(
        f"- Summary: {summary.get('verdict', 'unknown')} "
        f"({summary.get('executed_check_count', 0)} checks executed)"
    )
    for check_id in summary.get("non_blocking_findings", []):
        lines.append(f"- Non-blocking finding: {check_id}")
    for substitution in summary.get("substitutions", []):
        lines.append(f"- Substitution: {substitution.get('check')}")
    return "\n".join(lines)
