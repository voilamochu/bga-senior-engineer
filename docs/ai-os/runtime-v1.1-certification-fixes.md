# Runtime Specification v1.1 — Certification Fixes

**Type:** Engineering fix report
**Date:** 2026-07-29
**Runtime Specification Version:** 1.1 (Frozen)
**Base Audit:** docs/ai-os/runtime-v1.1-final-certification-audit.md
**Decision:** PASS WITH RECOMMENDATIONS

---

## Summary

This report documents the exact changes applied to the Runtime Specification v1.1 following the final certification audit.

Only the five accepted fixes were applied. No other certification recommendations were implemented.

---

## Files Modified

| File | Change Type | Rules Affected |
|---|---|---|
| rules/testing.json | see_also fix, applies_to normalization | TEST-001 through TEST-017 |
| rules/migration.json | see_also fix, applies_to normalization | MIGR-005, MIGR-007 through MIGR-019 |
| rules/undo-replay.json | ordering alignment, applies_to normalization, see_also fix | UNDO-002 through UNDO-014 |
| rules/notifications.json | applies_to normalization | NOTF-007 |

---

## Fix 1 — TEST-014: Correct see_also reference

**Rationale:** TEST-014 (invariant tests) referenced CORE-002 (Game.php orchestration), which is unrelated to invariant verification. CORE-013 (correctness is the highest priority) is the constitutional precedent for testing correctness.

**File:** rules/testing.json

**Rule:** TEST-014

**Change:**
```
- "see_also": ["CORE-002", "PERS-003"]
+ "see_also": ["CORE-013", "PERS-003"]
```

**Rationale:** CORE-013 governs correctness priority, which invariant tests directly verify. CORE-002 governs Game.php orchestration — unrelated.

---

## Fix 2 — MIGR-008: Correct see_also reference

**Rationale:** MIGR-008 (tests before migration) referenced TEST-009 (edge case testing), which is unrelated to pre-migration coverage adequacy. TEST-010 (coverage thresholds specifying Managers 90%, Actions 80%, Notifications 80%) is the correct target for determining whether coverage is adequate before migration.

**File:** rules/migration.json

**Rule:** MIGR-008

**Change:**
```
- "see_also": ["TEST-001", "TEST-009"]
+ "see_also": ["TEST-001", "TEST-010"]
```

**Rationale:** TEST-010 defines the coverage thresholds that MIGR-008 checks before migrating. TEST-009 covers edge case scenarios — weak fit.

---

## Fix 3 — UNDO-011: Align ordering with SYNC-008

**Rationale:** UNDO-011 specified clearTurn AFTER refreshUI/refreshHand. SYNC-008, which owns notification ordering, requires clearTurn BEFORE refreshUI/refreshHand. Synchronization owns notification ordering; UNDO-011 must conform.

**File:** rules/undo-replay.json

**Rule:** UNDO-011

**Changes:**

Violation item 2:
```
- "clearTurn is sent before refreshUI, causing a flash of empty state"
+ "clearTurn is sent after refreshUI — the signal arrives too late and stale state contaminates the fresh state"
```

Check:
```
- "Assert clearTurn is sent after refreshUI/refreshHand"
+ "Assert clearTurn is sent before refreshUI/refreshHand"
```

Fix:
```
- "Insert clearTurn notification after refreshUI and refreshHand"
+ "Insert clearTurn notification before refreshUI and refreshHand"
```

**Rationale:** SYNC-008 is authoritative for notification sequence ordering during undo. clearTurn must precede the state refresh signals.

---

## Fix 4 — Normalize applies_to vocabulary

**Rationale:** Multiple files used non-canonical applies_to values (Undo, Persistence, Testing, StateMachine, Setup, Synchronization, SimultaneousTurns, Globals, Migration, Rules, CI, TestSuite). These were replaced with the canonical component vocabulary from the architecture: Game.php, Actions, States, Managers, Models, Notifications, Client, Database, Engine, All components.

**Mapping:**

| Non-canonical Value | Canonical Replacement |
|---|---|
| Undo | Actions |
| Persistence | Database |
| Testing | All components |
| TestSuite | All components |
| StateMachine | States |
| Setup | Game.php |
| Synchronization | Client |
| SimultaneousTurns | States |
| Globals | Database |
| Migration | All components |
| Rules | All components |
| CI | All components |

### File: rules/notifications.json

| Rule | Before | After |
|---|---|---|
| NOTF-007 | Notifications, Undo | Notifications, Actions |

### File: rules/undo-replay.json

| Rule | Before | After |
|---|---|---|
| UNDO-002 | Undo | Actions |
| UNDO-003 | Actions, Undo | Actions |
| UNDO-004 | Persistence, Undo | Database, Actions |
| UNDO-005 | Actions, Undo, SimultaneousTurns | Actions, States |
| UNDO-006 | Game.php, Setup | Game.php |
| UNDO-009 | Notifications, Synchronization | Notifications, Client |
| UNDO-010 | Notifications, Undo | Notifications, Actions |
| UNDO-011 | Notifications, Undo | Notifications, Actions |
| UNDO-012 | Actions, Undo | Actions |
| UNDO-013 | Undo, Persistence | Actions, Database |
| UNDO-014 | Testing | All components |

### File: rules/testing.json

| Rule | Before | After |
|---|---|---|
| TEST-001 | Testing, TestSuite | All components |
| TEST-002 | Managers, Testing | Managers |
| TEST-003 | Managers, Testing | Managers |
| TEST-004 | Testing | All components |
| TEST-005 | Testing, Undo | All components, Actions |
| TEST-006 | Testing | All components |
| TEST-007 | Testing, TestSuite | All components |
| TEST-008 | Testing, StateMachine | All components, States |
| TEST-009 | Testing | All components |
| TEST-011 | Testing, TestSuite | All components |
| TEST-012 | Testing, Actions | All components, Actions |
| TEST-013 | Testing, Notifications | All components, Notifications |
| TEST-014 | Testing | All components |
| TEST-015 | Testing, Persistence | All components, Database |
| TEST-016 | Rules, Testing | All components |
| TEST-017 | Rules, CI | All components |

### File: rules/migration.json

| Rule | Before | After |
|---|---|---|
| MIGR-005 | Globals, Persistence | Database |
| MIGR-007 | StateMachine | States |
| MIGR-008 | Testing, Migration | All components |
| MIGR-009 | Migration | All components |
| MIGR-010 | Migration | All components |
| MIGR-012 | Migration | All components |
| MIGR-013 | Migration | All components |
| MIGR-014 | Migration | All components |
| MIGR-015 | Testing, Migration | All components |
| MIGR-016 | Migration | All components |
| MIGR-017 | Migration | All components |
| MIGR-018 | Migration | All components |
| MIGR-019 | Migration | All components |

---

## Fix 5 — Audit weak see_also references

**Rationale:** Three see_also references used objectively worse targets where obviously better alternatives existed. Two were already corrected by Fix 1 (TEST-014) and Fix 2 (MIGR-008). One remaining weak reference was corrected.

**File:** rules/undo-replay.json

**Rule:** UNDO-002 (LIFO undo ordering)

**Change:**
```
- "see_also": ["CORE-007", "UNDO-003"]
+ "see_also": ["CORE-006", "UNDO-003"]
```

**Rationale:** CORE-007 (notifications carry absolute values, never deltas) is unrelated to LIFO undo ordering. CORE-006 ("Every mutation must be reversible... Undo reverses mutations in LIFO order") directly addresses the concept. CORE-006 is the constitutional precedent for undo mechanics.

**Evaluated and left unchanged:**

| Rule | Reference | Assessment |
|---|---|---|
| STAT-007 → CORE-010 | Layer responsibility rule for _no_notify states | No obviously better target exists. _no_notify concerns notification layer discipline, which is tangentially related to CORE-010 (layer boundaries). |
| ACTN-012 → CORE-006 | Old-value logging for undo | Already correct. CORE-006 is the constitutional precedent. This is a direct match. |

---

## Confirmation

No other certification audit recommendations were applied. Specifically:

- No rules were merged
- No rules were split
- No priorities were changed
- No partition plan updates were made
- No ownership overlaps were removed
- No files were consolidated
- No rule wording was changed except where required by the accepted fixes
- No new runtime concepts were introduced
- No redesign or refactoring was performed
- No improvements beyond the five accepted fixes were made

---

## Verification

All changes are contained within 4 JSON files:
- rules/testing.json
- rules/migration.json
- rules/undo-replay.json
- rules/notifications.json

No other runtime files were modified.

---

*End of certification fixes report. This is a permanent engineering artifact of Runtime Specification v1.1.*
