"""V9 — Hidden-info scan (MS-06, evaluation spec §4 row V9).

Basic payload-key inspection (MVB-017): every notification payload
constructed by the submission's changed PHP files is inspected for
payload keys that carry private data into public paths.  The blocklist
is the MS-06 interpretation of critical failure C4's private-data
enumeration — hand contents, unrevealed cards, hidden counts, draft
picks.  ``_private`` (the framework's sanctioned mechanism for
recipient-restricted payload keys) and informational keys such as deck
*counts* are not flagged.

The scan is deliberately heuristic and documented as such; the pass
criterion is "no leaks" (no blocklist key in any public payload of the
changed files).
"""

from __future__ import annotations

from pathlib import Path

from tooling.harness.validation.checks.php_scan import notify_calls, payload_keys
from tooling.harness.validation.result import CheckResult

# Private-data payload keys per C4: hand contents, unrevealed cards,
# hidden counts, draft picks.  Each entry is a payload key that must not
# appear in a notification delivered on a public path.
PRIVATE_PAYLOAD_KEYS = ("hand", "hidden", "unrevealed", "draft")


def run_v9(changed_php_files: list[tuple[str, str]]) -> CheckResult:
    """Scan the submission's changed PHP files for private payload keys.

    Parameters
    ----------
    changed_php_files:
        ``(repo-relative path, file text)`` pairs from the frozen diff
        bundle.

    Returns
    -------
    CheckResult
        PASS when no private key appears in any notification payload;
        FAIL (blocking) with one finding per leak site otherwise.
    """
    findings: list[str] = []
    inspected = 0
    for relpath, text in changed_php_files:
        for call in notify_calls(text):
            inspected += 1
            for key in payload_keys(call["payload"] or ""):
                if key not in PRIVATE_PAYLOAD_KEYS:
                    continue
                findings.append(
                    f"{relpath}:{call['line']} type {call['type']!r} "
                    f"payload key {key!r} may leak private data "
                    f"(method {call['method'] or 'top-level'})"
                )
    if findings:
        return CheckResult(
            id="V9",
            name="Hidden-info scan (payload keys)",
            verdict="FAIL",
            blocking=True,
            detail=f"{len(findings)} potential hidden-information leak(s)",
            findings=sorted(findings),
            raw_text="\n".join(sorted(findings)) + "\n",
        )
    return CheckResult(
        id="V9",
        name="Hidden-info scan (payload keys)",
        verdict="PASS",
        blocking=True,
        detail=f"{inspected} notification payload(s) inspected, 0 leaks",
        raw_text=f"PASS: {inspected} notification payload(s) inspected, 0 leaks\n",
    )


def run_v9_from_diff_bundle(
    diff_bundle: Path | None, changed_paths: list[str]
) -> CheckResult:
    """V9 driven by the frozen diff bundle (evidence resolution)."""
    if diff_bundle is None:
        reason = "evidence: the submission has no diff bundle (E8 absent)"
        return CheckResult(
            id="V9",
            name="Hidden-info scan (payload keys)",
            verdict="BLOCKED",
            blocking=True,
            detail=reason,
            raw_text=f"BLOCKED: {reason}\n",
        )
    changed_php = []
    for relpath in sorted(changed_paths):
        if not relpath.endswith(".php"):
            continue
        changed_php.append(
            (relpath, (diff_bundle / relpath).read_text(encoding="utf-8"))
        )
    result = run_v9(changed_php)
    result.evidence = [
        f"evidence/e8-diff-bundle/{relpath}" for relpath, _ in changed_php
    ]
    return result
