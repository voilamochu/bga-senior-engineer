"""Final verification (MVB-024): harness §13 four-item check at P9.

At P9 (archive), the harness re-verifies the repository-safety
invariant immediately before ``ARCHIVED`` is reached:

1. **FV-1** — no files in the reference repository were modified:
   ``git status --porcelain`` tracked-change lines match the P0
   baseline (§13 item 1, §12.3).
2. **FV-2** — no files were created in the reference repository:
   the untracked-file set matches the P0 baseline (§13 item 2).
3. **FV-3** — no git metadata changed: HEAD and reflog top match the
   P0 baseline (§13 item 3, §12.3).
4. **FV-4** — generated artifacts exist only in the run archive: the
   run directory contains exactly the canonical §9.1 layout and no
   caches or temp files (§13 item 4, artifact inventory).

The four items are recorded pass/fail in
``validation/final-verification.json`` (deterministic — no wall-clock
values, mirroring ``validation.json``) and in the frozen manifest
through the sanctioned errata channel.  Any failed item blocks
``ARCHIVED``; the run remains in its pre-archive state (``VERDICTED``
or ``REJECTED``) and a re-run after the cause is fixed overwrites the
record (validation re-run semantics, harness §5.1).

The reference repository is only ever opened read-only; the same three
§12.3 git commands as G0 are re-run, never a write.
"""

from __future__ import annotations

import json
from pathlib import Path

from tooling.harness.archive.packaging import verify_packaging
from tooling.harness.runtime.run_dir import RunDir
from tooling.harness.safety.baseline import BaselineError, capture_baseline, load_baseline
from tooling.harness.util.clock import utc_now_iso
from tooling.harness.util.hash import sha256_file

FINAL_VERIFICATION_SCHEMA = "benchmark-harness-final-verification/1.0"
FINAL_VERIFICATION_RELPATH = "validation/final-verification.json"

# The four §13 items, in document order.
FV_ITEMS = (
    ("FV-1", "No files in bga-mercurio were modified"),
    ("FV-2", "No files were created in bga-mercurio"),
    ("FV-3", "No git metadata changed"),
    ("FV-4", "Generated artifacts exist only in the run archive"),
)

UNTRACKED_PREFIX = "??"


class FinalVerificationError(Exception):
    """The final verification record is invalid or cannot be written."""


def final_verification_record_path(run: RunDir) -> Path:
    """Path of the §13 record inside the run directory."""
    return run.root / FINAL_VERIFICATION_RELPATH


def _blocked_items(reason: str) -> list[dict]:
    return [
        {
            "id": item_id,
            "name": name,
            "verdict": "BLOCKED",
            "detail": reason,
        }
        for item_id, name in FV_ITEMS[:3]
    ]


def _split_status(status_porcelain: str) -> tuple[list[str], list[str]]:
    """Split ``git status --porcelain`` into (tracked changes, untracked).

    Untracked entries are prefixed ``??``; every other line is a change
    to a tracked file (M/A/D/R/C or staged variants).
    """
    tracked: list[str] = []
    untracked: list[str] = []
    for line in status_porcelain.splitlines():
        if line.startswith(UNTRACKED_PREFIX):
            untracked.append(line)
        else:
            tracked.append(line)
    return tracked, untracked


def _diff_lines(expected: list[str], actual: list[str]) -> list[str]:
    """Lines present in one set but not the other (preserving order)."""
    expected_set, actual_set = set(expected), set(actual)
    missing = [line for line in expected if line not in actual_set]
    added = [line for line in actual if line not in expected_set]
    differences = [f"missing: {line!r}" for line in missing]
    differences += [f"added: {line!r}" for line in added]
    return differences


def _compare_status_porcelain(expected: str, actual: str, name: str) -> tuple[str, str]:
    """Compare one side of the status split; returns (verdict, detail)."""
    if name == "tracked":
        expected_lines, actual_lines = _split_status(expected)[0], _split_status(actual)[0]
        subject = "tracked changes"
    else:
        expected_lines, actual_lines = _split_status(expected)[1], _split_status(actual)[1]
        subject = "untracked files"
    if expected_lines == actual_lines:
        return "PASS", f"git status --porcelain {subject} match the P0 baseline"
    differences = _diff_lines(expected_lines, actual_lines)
    return (
        "FAIL",
        f"git status --porcelain {subject} differ from the P0 baseline: "
        + "; ".join(differences),
    )


def _compare_baseline_items(baseline: dict, current: dict) -> list[dict]:
    items: list[dict] = []
    tracked_verdict, tracked_detail = _compare_status_porcelain(
        baseline["status_porcelain"], current["status_porcelain"], "tracked"
    )
    items.append(
        {
            "id": "FV-1",
            "name": FV_ITEMS[0][1],
            "verdict": tracked_verdict,
            "detail": tracked_detail,
        }
    )
    untracked_verdict, untracked_detail = _compare_status_porcelain(
        baseline["status_porcelain"], current["status_porcelain"], "untracked"
    )
    items.append(
        {
            "id": "FV-2",
            "name": FV_ITEMS[1][1],
            "verdict": untracked_verdict,
            "detail": untracked_detail,
        }
    )
    metadata_differences: list[str] = []
    if current["head"] != baseline["head"]:
        metadata_differences.append(
            f"HEAD differs: expected {baseline['head']!r}, actual {current['head']!r}"
        )
    if current["reflog_top"] != baseline["reflog_top"]:
        metadata_differences.append(
            "reflog top differs: expected "
            f"{baseline['reflog_top']!r}, actual {current['reflog_top']!r}"
        )
    if metadata_differences:
        items.append(
            {
                "id": "FV-3",
                "name": FV_ITEMS[2][1],
                "verdict": "FAIL",
                "detail": "; ".join(metadata_differences),
            }
        )
    else:
        items.append(
            {
                "id": "FV-3",
                "name": FV_ITEMS[2][1],
                "verdict": "PASS",
                "detail": "HEAD and reflog top match the P0 baseline",
            }
        )
    return items


def _artifact_inventory_item(run: RunDir) -> dict:
    divergences = verify_packaging(run)
    if not divergences:
        return {
            "id": "FV-4",
            "name": FV_ITEMS[3][1],
            "verdict": "PASS",
            "detail": "run directory contains exactly the canonical §9.1 layout; "
            "no caches or temp files",
        }
    return {
        "id": "FV-4",
        "name": FV_ITEMS[3][1],
        "verdict": "FAIL",
        "detail": "artifact inventory found non-packageable contents: "
        + "; ".join(divergences),
    }


def run_final_verification(
    run: RunDir,
    *,
    reference_root: str | Path,
    baseline_path: str | Path | None = None,
) -> dict:
    """Execute the four §13 checks and return the deterministic record.

    The record is a pure function of the run directory and the current
    reference-repository state: identical inputs produce byte-identical
    records (no wall-clock values).  Items whose checks cannot execute
    (missing baseline, missing repository, git failure) are recorded
    ``BLOCKED`` and the overall result is failed — safety is never
    silently assumed.
    """
    baseline_path = Path(baseline_path) if baseline_path is not None else (
        run.baseline / "safety-baseline.json"
    )
    reference_root = Path(reference_root)

    items: list[dict] = []
    reference_head = None
    if not reference_root.is_dir() or not (reference_root / ".git").is_dir():
        items = _blocked_items(
            f"reference repository is not a git checkout: {reference_root}"
        )
    else:
        try:
            baseline = load_baseline(baseline_path)
        except (OSError, ValueError, BaselineError) as exc:
            items = _blocked_items(
                f"cannot load the P0 safety baseline: {exc}"
            )
        else:
            try:
                current = capture_baseline(reference_root)
            except (BaselineError, OSError) as exc:
                items = _blocked_items(
                    f"cannot verify the reference repository: {exc}"
                )
            else:
                reference_head = current["head"]
                items = _compare_baseline_items(baseline, current)
    items.append(_artifact_inventory_item(run))

    divergences = [
        f"{item['id']} {item['verdict']}: {item['detail']}"
        for item in items
        if item["verdict"] != "PASS"
    ]
    return {
        "schema": FINAL_VERIFICATION_SCHEMA,
        "run_id": run.run_id,
        "passed": not divergences,
        "reference_head": reference_head,
        "items": items,
        "divergences": divergences,
    }


def save_final_verification(
    run: RunDir,
    manifest,
    record: dict,
    *,
    at: str | None = None,
) -> Path:
    """Persist the §13 record and record it through the errata channel.

    The manifest is frozen at P4; ``add_errata`` is the only sanctioned
    post-freeze mutation.  Re-runs overwrite the record file (validation
    re-run semantics) while the errata history keeps every attempt.
    """
    _validate_record(record, run)
    path = final_verification_record_path(run)
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_text(json.dumps(record, indent=2, sort_keys=True) + "\n", encoding="utf-8")

    verdicts = ", ".join(
        f"{item['id']}={item['verdict']}" for item in record["items"]
    )
    message = (
        "P9 final verification: "
        f"passed={record['passed']} "
        f"({verdicts}) "
        f"record sha256={sha256_file(path)} "
        f"reference_head={record['reference_head']}"
    )
    manifest.add_errata(message, at=at if at is not None else utc_now_iso())
    manifest.save(run.manifest_path)
    return path


def load_final_verification(run: RunDir) -> dict | None:
    """Load and validate the §13 record; ``None`` when it was not recorded."""
    path = final_verification_record_path(run)
    if not path.is_file():
        return None
    try:
        data = json.loads(path.read_text(encoding="utf-8"))
    except (json.JSONDecodeError, OSError) as exc:
        raise FinalVerificationError(f"cannot read {path}: {exc}") from exc
    if not isinstance(data, dict):
        raise FinalVerificationError(f"{path} must be a JSON object")
    _validate_record(data, run)
    return data


def _validate_record(record: dict, run: RunDir) -> None:
    if record.get("schema") != FINAL_VERIFICATION_SCHEMA:
        raise FinalVerificationError(
            f"unsupported final-verification schema: {record.get('schema')!r}"
        )
    if record.get("run_id") != run.run_id:
        raise FinalVerificationError(
            f"final-verification record run_id {record.get('run_id')!r} does not "
            f"match run {run.run_id!r}"
        )
    items = record.get("items")
    if not isinstance(items, list) or len(items) != len(FV_ITEMS):
        raise FinalVerificationError(
            f"final-verification record must carry {len(FV_ITEMS)} items"
        )
    for item in items:
        if item.get("verdict") not in ("PASS", "FAIL", "BLOCKED"):
            raise FinalVerificationError(
                f"final-verification item {item.get('id')!r} has an invalid "
                f"verdict: {item.get('verdict')!r}"
            )
