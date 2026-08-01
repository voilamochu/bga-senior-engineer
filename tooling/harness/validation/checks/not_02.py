"""NOT-02 task checks (MS-06, evaluation spec §3.11).

The four automatic validation opportunities of the NOT-02 task:

- **NOT02-A single-source** (blocking) — each consolidated notification
  pattern is sent from exactly one method (evaluation spec §3.11
  "Single-source check"; catalog row V4 for NOT-02).
- **NOT02-B call-site count** (blocking) — each consolidation helper is
  invoked from the expected number of call sites: ``labOutputActivated``
  4 (stPlayerTurn, actBuyResource, applyBeam, applyTap), market
  milestone 2 (buy and sell), synergy milestone 2 (beam and tap),
  ``cardKept`` 3 (paths in actChooseKeep).
- **NOT02-C duplication scan** (non-blocking) — no duplicated
  notification construction blocks remain in Game.php (catalog row V6).
- **NOT02-D payload parity** (blocking) — gamelog diff harness output,
  or a recorded substitution when no gamelog is available (evaluation
  spec §2.1 G3 rule; implementation plan R5).

The pattern definitions below are the MS-06 interpretation of the
corpus task entry (``benchmark-task-corpus.md`` NOT-02) and the
evaluation spec §3.11 success criteria; the expected call-site counts
come from the corpus's enumeration of the duplicated locations.
"""

from __future__ import annotations

import re
from pathlib import Path

from tooling.harness.validation.checks.php_scan import (
    mask_text,
    normalize_payload,
    notify_calls,
)
from tooling.harness.validation.result import CheckResult

# Game.php relative path inside the submission's diff bundle (E8).
GAME_PHP_RELPATH = "modules/php/Game.php"

# The four consolidated patterns: their anchor (how a sending site of the
# pattern is recognized) and the expected number of helper call sites.
#   * labOutputActivated — the notification type itself.
#   * market milestone — the market milestone switch always emits an
#     ``influenceChanged`` notification whose message mentions "market
#     activity" (the synergy switch's equivalent mentions "synergy").
#   * synergy milestone — the synergy switch always emits a
#     ``synergyMilestoneReached`` notification.
#   * cardKept — the notification type itself.
CONSOLIDATED_PATTERNS = (
    {
        "id": "labOutputActivated",
        "anchor_types": ("labOutputActivated",),
        "anchor_message": None,
        "expected_call_sites": 4,
    },
    {
        "id": "marketMilestone",
        "anchor_types": ("influenceChanged",),
        "anchor_message": "market activity",
        "expected_call_sites": 2,
    },
    {
        "id": "synergyMilestone",
        "anchor_types": ("synergyMilestoneReached",),
        "anchor_message": None,
        "expected_call_sites": 2,
    },
    {
        "id": "cardKept",
        "anchor_types": ("cardKept",),
        "anchor_message": None,
        "expected_call_sites": 3,
    },
)


def _is_anchor(call: dict, pattern: dict) -> bool:
    if call["type"] not in pattern["anchor_types"]:
        return False
    if pattern["anchor_message"] is not None:
        return pattern["anchor_message"] in (call.get("message") or "")
    return True


def _senders(calls: list[dict], pattern: dict) -> list[str]:
    anchors = [call for call in calls if _is_anchor(call, pattern)]
    return sorted({call["method"] or "top-level" for call in anchors})


def run_single_source(game_php: str) -> CheckResult:
    """NOT02-A: each consolidated type has exactly one sending method."""
    calls = notify_calls(game_php)
    findings: list[str] = []
    for pattern in CONSOLIDATED_PATTERNS:
        senders = _senders(calls, pattern)
        if len(senders) == 1:
            continue
        if not senders:
            findings.append(
                f"{pattern['id']}: no sending method found (no anchor "
                "notification call in Game.php)"
            )
        else:
            findings.append(
                f"{pattern['id']}: expected exactly one sending method, "
                f"found {len(senders)}: {', '.join(senders)}"
            )
    return CheckResult(
        id="NOT02-A",
        name="Single-source notification patterns",
        verdict="PASS" if not findings else "FAIL",
        blocking=True,
        detail=(
            "each consolidated pattern sent from exactly one method "
            f"({len(CONSOLIDATED_PATTERNS)} patterns)"
            if not findings
            else "; ".join(findings)
        ),
        findings=findings,
        raw_text="\n".join(findings) if findings else "PASS: single source per consolidated pattern\n",
    )


def run_call_site_counts(game_php: str) -> CheckResult:
    """NOT02-B: each consolidation helper is invoked from the expected sites."""
    calls = notify_calls(game_php)
    masked = mask_text(game_php)
    findings: list[str] = []
    for pattern in CONSOLIDATED_PATTERNS:
        senders = _senders(calls, pattern)
        if len(senders) != 1:
            findings.append(
                f"{pattern['id']}: call-site count not evaluated "
                f"(single-source check failed: senders {senders})"
            )
            continue
        helper = senders[0]
        if helper == "top-level":
            findings.append(
                f"{pattern['id']}: sender is not a method; cannot count call sites"
            )
            continue
        invocation = re.compile(r"\$this\s*->\s*" + re.escape(helper) + r"\s*\(")
        count = len(invocation.findall(masked))
        if count != pattern["expected_call_sites"]:
            findings.append(
                f"{pattern['id']}: expected {pattern['expected_call_sites']} "
                f"call sites of helper {helper!r}, found {count}"
            )
    return CheckResult(
        id="NOT02-B",
        name="Call-site counts per consolidation helper",
        verdict="PASS" if not findings else "FAIL",
        blocking=True,
        detail="; ".join(findings) if findings else "all helper call-site counts match",
        findings=findings,
        raw_text="\n".join(findings) if findings else "PASS: call-site counts match\n",
    )


def run_duplication_scan(game_php: str) -> CheckResult:
    """NOT02-C (non-blocking): no duplicated notification construction blocks.

    Two notification calls are considered duplicated construction when
    their type, message, and payload are identical after normalization
    (comments removed, variable references replaced by a placeholder,
    whitespace collapsed); see :func:`normalize_payload`.  The message
    is part of the construction identity — two calls of the same type
    with different messages (e.g. the market and synergy milestone
    blocks both emitting ``influenceChanged``) are distinct blocks.
    """
    calls = notify_calls(game_php)
    groups: dict[tuple[str, str, str], list[dict]] = {}
    for call in calls:
        if not call["payload"]:
            continue
        normalized = normalize_payload(call["payload"])
        key = (call["type"], call.get("message") or "", normalized)
        groups.setdefault(key, []).append(call)
    findings: list[str] = []
    for (ntype, _message, _normalized), group in sorted(groups.items()):
        if len(group) < 2:
            continue
        sites = sorted(
            f"{call['method'] or 'top-level'}@line{call['line']}" for call in group
        )
        findings.append(
            f"duplicated construction of type {ntype}: " + ", ".join(sites)
        )
    return CheckResult(
        id="NOT02-C",
        name="Duplication scan (Game.php)",
        verdict="PASS" if not findings else "FAIL",
        blocking=False,
        detail="; ".join(findings) if findings else "no duplicated notification blocks",
        findings=findings,
        raw_text="\n".join(findings) if findings else "PASS: no duplicated notification blocks\n",
    )


def run_payload_parity(gamelog_paths: list[str]) -> CheckResult:
    """NOT02-D: payload parity via gamelog diff, or recorded substitution.

    The MVB has no gamelog record/diff harness (deferred, plan FUT-04),
    so the check is recorded as a substitution per evaluation spec §2.1
    and implementation plan R5 — parity is delegated to the G3 static
    review of the frozen diff bundle.  The substitution is recorded in
    ``validation.json``, never silent.
    """
    if gamelog_paths:
        reason = (
            "gamelog evidence found (" + ", ".join(sorted(gamelog_paths)) + ") "
            "but the MVB has no gamelog diff harness (deferred, FUT-04); "
            "parity recorded as substitution, delegated to G3 static "
            "review of the diff bundle (eval spec §2.1, plan R5)"
        )
    else:
        reason = (
            "no gamelog evidence in this run; parity recorded as "
            "substitution, delegated to G3 static review of the diff "
            "bundle (eval spec §2.1, plan R5)"
        )
    return CheckResult(
        id="NOT02-D",
        name="Payload parity (gamelog diff)",
        verdict="PASS",
        blocking=True,
        detail="recorded substitution: " + reason,
        substituted=True,
        substitution_reason=reason,
        raw_text=f"SUBSTITUTED: {reason}\n",
        evidence=list(gamelog_paths),
    )


def run_task_checks(
    *,
    diff_bundle: Path | None,
    changed_paths: list[str],
    gamelogs: list[str],
) -> list[CheckResult]:
    """Run all four NOT-02 checks against the frozen diff bundle.

    Evidence resolution: when the submission has no diff bundle (E8
    absent) every content check is BLOCKED with a precise reason; when
    Game.php is absent from a present diff bundle, checks NOT02-A/B/C
    FAIL (a submission that does not touch Game.php cannot satisfy the
    consolidation criteria).
    """
    if diff_bundle is None:
        blocked = "evidence: the submission has no diff bundle (E8 absent)"
        return [
            CheckResult(
                id="NOT02-A",
                name="Single-source notification patterns",
                verdict="BLOCKED",
                blocking=True,
                detail=blocked,
                raw_text=f"BLOCKED: {blocked}\n",
            ),
            CheckResult(
                id="NOT02-B",
                name="Call-site counts per consolidation helper",
                verdict="BLOCKED",
                blocking=True,
                detail=blocked,
                raw_text=f"BLOCKED: {blocked}\n",
            ),
            CheckResult(
                id="NOT02-C",
                name="Duplication scan (Game.php)",
                verdict="BLOCKED",
                blocking=False,
                detail=blocked,
                raw_text=f"BLOCKED: {blocked}\n",
            ),
            run_payload_parity(gamelogs),
        ]
    if GAME_PHP_RELPATH not in changed_paths:
        reason = (
            f"evidence: the diff bundle does not contain {GAME_PHP_RELPATH}; "
            "the consolidation criteria cannot be satisfied"
        )
        return [
            CheckResult(
                id="NOT02-A",
                name="Single-source notification patterns",
                verdict="FAIL",
                blocking=True,
                detail=reason,
                findings=[reason],
                raw_text=f"FAIL: {reason}\n",
            ),
            CheckResult(
                id="NOT02-B",
                name="Call-site counts per consolidation helper",
                verdict="FAIL",
                blocking=True,
                detail=reason,
                findings=[reason],
                raw_text=f"FAIL: {reason}\n",
            ),
            CheckResult(
                id="NOT02-C",
                name="Duplication scan (Game.php)",
                verdict="FAIL",
                blocking=False,
                detail=reason,
                findings=[reason],
                raw_text=f"FAIL: {reason}\n",
            ),
            run_payload_parity(gamelogs),
        ]
    game_php = (diff_bundle / GAME_PHP_RELPATH).read_text(encoding="utf-8")
    evidence = [f"evidence/e8-diff-bundle/{GAME_PHP_RELPATH}"]
    results = [
        run_single_source(game_php),
        run_call_site_counts(game_php),
        run_duplication_scan(game_php),
        run_payload_parity(gamelogs),
    ]
    for result in results:
        if result.id != "NOT02-D":
            result.evidence = list(evidence)
    return results
