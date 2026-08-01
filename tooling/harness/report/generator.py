"""Report generator (MS-08, MVB-022).

Renders both canonical reports from the single in-memory
:class:`~tooling.harness.report.data.ReportData` — ``report.md`` (the
eleven §8.2 sections) and ``evaluation-report.json`` (the §8.3 metadata
groups) — so the two renderings can never diverge.  The generator then
validates the output against the section/field-group checklists before
accepting it (§8.2/§8.3).

Rendering is deterministic: no wall-clock values are introduced; all
timestamps come from the recorded run data.  Regenerating reports for
unchanged run data produces byte-identical files.
"""

from __future__ import annotations

import json
from pathlib import Path

from tooling.harness.evidence.collect import EVIDENCE_TYPES
from tooling.harness.runtime.manifest import RunManifest
from tooling.harness.runtime.run_dir import RunDir
from tooling.harness.runtime.status import RunStatus
from tooling.harness.report.data import (
    REPORT_SCHEMA,
    REPORT_VERSION,
    REQUIRED_METADATA_GROUPS,
    REQUIRED_SECTIONS,
    ReportData,
    ReportError,
    assemble_report_data,
)
from tooling.harness.util.clock import utc_now_iso
from tooling.harness.util.hash import sha256_file


def render_report_md(data: ReportData) -> str:
    """Render the eleven §8.2 sections of ``report.md``."""
    lines: list[str] = []
    lines.append("# BGA Senior Engineer — Evaluation Report")
    lines.append("")
    lines.append(f"Run: {data.run_id}")

    lines.append("")
    lines.append("## 1. Header")
    lines.append(f"- Run ID: {data.identity['run_id']}")
    lines.append(f"- Model: {data.identity['model']} "
                 f"(version {data.identity['model_version']})")
    lines.append(f"- Task: {data.identity['task_id']} "
                 f"(difficulty {data.identity['task_difficulty']})")
    lines.append(f"- Corpus version: {data.versions.get('corpus')}")
    lines.append(f"- Evaluation version: {data.versions.get('evaluation')}")
    lines.append(f"- Harness version: {data.versions.get('harness')}")
    lines.append(f"- Runtime version: {data.versions.get('runtime')}")
    lines.append(f"- Validator version: {data.versions.get('validator')}")
    lines.append(f"- Reference HEAD: {data.versions.get('reference_head')}")
    lines.append(f"- Started: {data.timing['started_at']}")
    lines.append(f"- Ended: {data.timing['ended_at']}")

    lines.append("")
    lines.append("## 2. Run Status")
    lines.append(f"- Status: {data.status}")
    lines.append(f"- Verdict: {data.verdict['verdict']}")
    if data.verdict.get("rejection_reason"):
        lines.append(f"- Rejection reason: {data.verdict['rejection_reason']}")

    lines.append("")
    lines.append("## 3. Environment Summary")
    for tool in data.environment.get("tools", []):
        state = "present" if tool.get("present") else "missing"
        version = tool.get("version") or "?"
        lines.append(f"- {tool.get('name')}: {version} ({state}, "
                     f"required {tool.get('required_version')})")
    lines.append(f"- Network policy: {data.environment.get('network')}")
    lines.append(f"- Validator version output: {data.versions.get('validator')}")
    deviations = [
        tool.get("name") for tool in data.environment.get("tools", [])
        if tool.get("present") and not tool.get("version_ok")
    ]
    if deviations:
        lines.append(f"- Deviations: tools below §4.2 minimum: {', '.join(deviations)}")
    else:
        lines.append("- Deviations: none")

    lines.append("")
    lines.append("## 4. Gate Results")
    for gate_id in ("G0", "G1", "G2"):
        gate = data.gates["gates"][gate_id]
        lines.append(f"- {gate_id} {gate['name']}: {gate['verdict']}")
        for check in gate["checks"]:
            raw = check.get("raw_output") or ""
            lines.append(f"  - {check['id']}: {check['verdict']}"
                         f"{f' (raw: validation/{raw})' if raw else ''}")
    summary = data.gates["summary"]
    lines.append(f"- Summary: {summary['verdict']}")
    for check_id in summary["blocking_failures"]:
        lines.append(f"  - Blocking failure: {check_id}")
    for check_id in summary["non_blocking_findings"]:
        lines.append(f"  - Non-blocking finding: {check_id}")
    for substitution in summary["substitutions"]:
        lines.append(f"  - Substitution: {substitution.get('check')}")
    lines.append(f"- Validation record: {data.gates['validation_file']}")

    lines.append("")
    lines.append("## 5. Evidence Index")
    lines.append(f"- Evidence manifest hash: {data.evidence['evidence_manifest_hash']}")
    lines.append(f"- Evidence root hash: {data.evidence['evidence_root_hash']}")
    lines.append("- Artifact index (see evidence/evidence.json for sizes and "
                 "SHA-256 per artifact):")
    for etype, entry in data.evidence_types.items():
        if entry.get("status") == "present":
            paths = ", ".join(f"`evidence/{f['path']}`" for f in entry.get("files", []))
            lines.append(f"  - {etype}: present ({paths})")
        else:
            lines.append(f"  - {etype}: absent ({entry.get('reason', 'not collected')})")

    lines.append("")
    lines.append("## 6. Category Scores")
    weights = data.scores.get("weights", {})
    categories = data.scores.get("category_scores", {})
    if data.rejected:
        lines.append("- Not scored: the run was rejected at P5 (G0/G1 or "
                     "blocking G2 failure)")
    else:
        for category, record in sorted(categories.items()):
            weight = weights.get(category, "?")
            lines.append(f"- {category}: {record.get('score', '?')} "
                         f"(weight {weight}) — evidence: "
                         + ", ".join(f"`{c}`" for c in record.get("evidence", []) or ["not cited"])
                         + (f" — comments: {record.get('comments')}" if record.get("comments") else ""))

    lines.append("")
    lines.append("## 7. Total and Verdict")
    arithmetic = data.scores.get("capped_total") if not data.rejected else None
    lines.append(f"- Formula: total = sum(score × weight) / 100 (harness §7.3)")
    if data.rejected:
        lines.append("- Total: not computed (rejected run)")
        lines.append(f"- Verdict: {data.verdict['verdict']}")
    else:
        lines.append(f"- Total: {data.scores.get('total')} "
                     f"(capped: {arithmetic})")
        if data.scores.get("caps"):
            lines.append(f"- Caps applied: {data.scores['caps']}")
        lines.append(f"- Verdict: {data.scores['verdict']}")
    if data.verdict.get("critical_failures"):
        lines.append(f"- Critical failures: {', '.join(data.verdict['critical_failures'])}")

    lines.append("")
    lines.append("## 8. Manual Review Notes")
    review = data.review_notes
    if review.get("reviewed"):
        lines.append(f"- Reviewer: {review.get('reviewer') or 'anonymous'}")
        lines.append(f"- Review status: {review.get('review_status')} "
                     f"(review version {review.get('review_version')})")
        lines.append(f"- Working file: {review.get('manual_review_file')}")
        lines.append("- G3/G4 findings:")
        for finding in review.get("findings", []):
            lines.append(f"  - {finding}")
        if not review.get("findings"):
            lines.append("  - (none recorded)")
    else:
        lines.append(f"- {review.get('review_status') or 'no manual review recorded'}")

    lines.append("")
    lines.append("## 9. Common Failure Mode Notes")
    modes = data.common_failure_modes
    if modes:
        lines.append("Common failure modes for this task (evaluation spec §3.11); "
                     "observations are recorded in the G3/G4 findings of "
                     "review/manual-review.md:")
        for mode in modes:
            lines.append(f"- {mode}")
    else:
        lines.append("- (task common failure modes not available)")

    lines.append("")
    lines.append("## 10. Safety Verification")
    g0 = data.gates["gates"]["G0"]
    lines.append(f"- Reference repository: bga-mercurio (HEAD "
                 f"{data.versions.get('reference_head')})")
    lines.append(f"- G0 repository safety at P5: {g0['verdict']} — the three "
                 "§12.3 checks (head, status_porcelain, reflog_top) were "
                 "recorded in validation/validation.json and compared against "
                 "the P0 baseline")
    final_verification = data.final_verification
    if final_verification is None:
        lines.append("- Final verification (§13): not yet performed (recorded "
                     "at P9 during archive)")
    else:
        verdict = "PASS" if final_verification["passed"] else "FAIL"
        lines.append(f"- Final verification (§13): {verdict} — the four items "
                     "were recorded in validation/final-verification.json and "
                     "compared against the P0 baseline")
        for item in final_verification["items"]:
            detail = item.get("detail") or ""
            lines.append(f"  - {item['id']} {item['name']}: {item['verdict']}"
                         + (f" — {detail}" if detail else ""))

    lines.append("")
    lines.append("## 11. Errata History")
    if data.errata:
        for entry in data.errata:
            lines.append(f"- [{entry.get('recorded_at')}] {entry.get('message')}")
    else:
        lines.append("- (none)")

    return "\n".join(lines) + "\n"


def render_report_json(data: ReportData) -> dict:
    """Render the §8.3 metadata groups of ``evaluation-report.json``."""
    return {
        "schema": REPORT_SCHEMA,
        "report_version": REPORT_VERSION,
        "identity": dict(data.identity),
        "versions": dict(data.versions),
        "timing": dict(data.timing),
        "usage": dict(data.usage),
        "evidence": dict(data.evidence),
        "scores": {
            "category_scores": data.scores.get("category_scores", {}),
            "weights": data.scores.get("weights", {}),
            "total": data.scores.get("total"),
            "capped_total": data.scores.get("capped_total"),
            "caps": data.scores.get("caps"),
        },
        "verdict": dict(data.verdict),
        "attribution": dict(data.attribution),
        "gates": data.gates,
        "review": {
            "reviewed": data.review_notes.get("reviewed"),
            "reviewer": data.review_notes.get("reviewer"),
            "review_status": data.review_notes.get("review_status"),
            "review_version": data.review_notes.get("review_version"),
            "manual_review_file": data.review_notes.get("manual_review_file"),
        },
        "final_verification": data.final_verification,
        "errata": [dict(entry) for entry in data.errata],
    }


def validate_report(md_text: str, json_doc: dict) -> list[str]:
    """Validate the reports against the §8.2/§8.3 checklists.

    Returns a list of precise divergences (empty when both reports are
    complete).
    """
    divergences: list[str] = []
    for index, section in enumerate(REQUIRED_SECTIONS, start=1):
        heading = f"## {index}. {section}"
        if heading not in md_text:
            divergences.append(f"report.md is missing section {section!r}")
    for group in REQUIRED_METADATA_GROUPS:
        if group not in json_doc:
            divergences.append(
                f"evaluation-report.json is missing metadata group {group!r}"
            )
    return divergences


def generate_reports(
    run: RunDir,
    manifest: RunManifest,
    status: RunStatus,
    *,
    eval_doc: str | Path | None = None,
) -> dict:
    """Generate and validate both reports; record them in the manifest.

    Returns ``{md_path, json_path, md_sha256, json_sha256, sections,
    divergences}``.  Raises :class:`ReportError` when the run's recorded
    artifacts are incomplete or the rendered reports fail the §8.2/§8.3
    checklist.
    """
    data = assemble_report_data(run, manifest, status, eval_doc=eval_doc)
    md_text = render_report_md(data)
    json_doc = render_report_json(data)

    divergences = validate_report(md_text, json_doc)
    if divergences:
        raise ReportError(
            "generated reports fail the §8.2/§8.3 checklist: "
            + "; ".join(divergences)
        )

    reports_dir = run.reports
    reports_dir.mkdir(parents=True, exist_ok=True)
    md_path = reports_dir / "report.md"
    json_path = reports_dir / "evaluation-report.json"
    md_path.write_text(md_text, encoding="utf-8")
    json_path.write_text(
        json.dumps(json_doc, indent=2, sort_keys=True) + "\n", encoding="utf-8"
    )
    md_sha = sha256_file(md_path)
    json_sha = sha256_file(json_path)

    _record_report_errata(manifest, run, md_sha, json_sha)
    return {
        "md_path": md_path,
        "json_path": json_path,
        "md_sha256": md_sha,
        "json_sha256": json_sha,
        "sections": list(REQUIRED_SECTIONS),
        "divergences": [],
    }


def _record_report_errata(manifest: RunManifest, run: RunDir, md_sha: str, json_sha: str) -> None:
    """Record report generation once; regenerate only when bytes changed.

    The reports are deterministic: regenerating unchanged run data
    produces identical bytes and the originally recorded hashes stay
    valid, so no second errata entry is written (keeps the Errata
    History section stable across regeneration).
    """
    marker = "P8 reports generated:"
    for entry in manifest.errata:
        if marker in entry.get("message", "") and json_sha in entry["message"] and md_sha in entry["message"]:
            return
    message = (
        f"{marker} report_version={REPORT_VERSION} "
        f"evaluation-report.json sha256={json_sha} "
        f"report.md sha256={md_sha}"
    )
    manifest.add_errata(message, at=utc_now_iso())
    manifest.save(run.manifest_path)
