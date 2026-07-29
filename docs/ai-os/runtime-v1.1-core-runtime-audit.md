# Runtime Specification v1.1 — Core Runtime Audit

## Scope

This audit covers the first eight runtime rule files:

- `rules/constitution.json`
- `rules/architecture.json`
- `rules/state-machine.json`
- `rules/actions.json`
- `rules/persistence.json`
- `rules/notifications.json`
- `rules/client.json`
- `rules/synchronization.json`

The remaining four runtime files (`undo-replay.json`, `testing.json`, `animations.json`, `migration.json`) were intentionally excluded. They are pending generation and carry 12 unresolved forward references that this audit records for verification upon creation.

**Date:** 2026-07-29
**Runtime Specification Version:** 1.1 (Frozen)
**Total rules audited:** 121
**Total lines:** 2,813

---

## Executive Summary

The eight runtime rule files are structurally sound. Schema Version 1.1 is uniformly followed. The hierarchical refinement model (constitutional law runtime implementation review guidance) is correctly applied across all files. Ownership boundaries are clean for the majority of concepts.

Five issues require resolution before the runtime is fully consistent:

1. An unresolved cross-reference fix that the reconciliation document claims was applied but was not (CORE-008 see_also PERS-004).
2. A wrong cross-reference in a constitutional rule (CORE-009 see_also STAT-006).
3. An overlapping spectator-filtering rule between notifications.json and synchronization.json (NOTF-013 / SYNC-005).
4. A constitutional-runtime contradiction regarding Manager-to-Manager communication (CORE-012 / ARCH-014).
5. A missing contract between persistence transaction boundaries and notification emission timing.

The runtime is ready to proceed with `rules/undo-replay.json` after the critical and high-priority findings are resolved.

---

## Health Score

**68 / 100**

| Category | Weight | Score |
|---|---|---|
| Schema consistency | 20% | 19 |
| Ownership boundaries | 20% | 12 |
| Cross-reference integrity | 15% | 11 |
| Dependency graph | 10% | 8 |
| Coverage | 15% | 14 |
| Consistency | 10% | 7 |
| Notification / Client / Synchronization contract | 5% | 4 |
| Persistence / Notification contract | 5% | 3 |

---

## Findings

### Critical

**CRIT-01 — CORE-008 see_also references PERS-004 (wrong target, unresolved reconciliation fix)**

- **Reason:** Reconciliation v1.1 documents this reference as corrected to PERS-009/PERS-010, but the runtime file was never modified. PERS-004 (atomic conditional UPDATEs) is unrelated to CORE-008 (data-driven configuration).
- **Impact:** Agent retrieving config-as-data guidance lands on atomic UPDATE rules instead.
- **Action:** Replace `"PERS-004"` with `["PERS-009", "PERS-010"]` in CORE-008 see_also.

**CRIT-02 — CORE-009 see_also references STAT-006 (wrong target)**

- **Reason:** CORE-009 mandates giveExtraTime and zombie. STAT-006 governs args() privacy and _private keys — unrelated. Should reference STAT-009 (zombie requirement) and STAT-011 (giveExtraTime).
- **Impact:** Agent directed to irrelevant rule for two high-priority framework requirements.
- **Action:** Replace `"STAT-006"` with `["STAT-009", "STAT-011"]` in CORE-009 see_also.

### High

**HIGH-01 — NOTF-013 and SYNC-005 duplicate spectator-filtering rule (ownership overlap)**

- **Reason:** Both rules state "Spectators must never receive private notifications" with near-identical check, violation, and fix text. The partition plan assigns spectator filtering exclusively to synchronization.json. notifications.json has a boundary violation.
- **Impact:** Dual ownership dilutes enforcement. Divergent updates produce conflicting guidance.
- **Action:** Remove NOTF-013; replace with see_also reference to SYNC-005. Alternatively remove SYNC-005 and retain NOTF-013. First option aligns with partition plan ownership.

**HIGH-02 — CLNT-007 assigned priority 2 in a priority-4 domain**

- **Reason:** Partition plan assigns client.json file-level priority 4. CLNT-007 (handler idempotency) is priority 2. This contradicts the intended priority scale.
- **Impact:** Undermines the priority system. Idempotency is important but priority 2 is hard architectural constraint level.
- **Action:** Downgrade CLNT-007 to priority 3, or update partition plan priority assignments.

**HIGH-03 — Priority scale misalignment in notifications.json and synchronization.json**

- **Reason:** Partition plan assigns notifications.json priority 3 and synchronization.json priority 4. Actual files contain multiple priority-2 rules (8 in notifications, 2 in synchronization).
- **Impact:** Planning document is outdated as prescriptive reference.
- **Action:** Update partition plan 1.4 to document actual priority distributions.

**HIGH-04 — ARCH-014 exception contradicts CORE-012 constitutional law**

- **Reason:** CORE-012 states "No Manager may call another Manager directly" with no exceptions. ARCH-014 permits read-only get* calls through injected interfaces. Per partition plan 1.3 rule 5, a runtime rule must not weaken its constitutional precedent.
- **Impact:** Constitutional law and runtime guidance are in direct conflict.
- **Action:** Either (a) amend CORE-012 to add the read-only exception, or (b) remove the ARCH-014 exception. Option (a) is recommended.

**HIGH-05 — No contract between persistence transactions and notification timing**

- **Reason:** PERS-014 requires transaction boundaries for multi-table mutations. NOTF-011 requires notifications after execution before transition. No rule states whether notifications are inside or outside the transaction. If inside: clients see state before commit, and rollback shows stale state.
- **Impact:** Implementers make inconsistent choices. Replay safety requires notifications reflect committed state (outside transaction).
- **Action:** Add a rule specifying notifications are sent after transaction commit.

### Medium

**MED-01 — 12 forward references to 3 unimplemented files**

- **Reason:** Existing files reference UNDO-001 through UNDO-011 (12 refs to undo-replay.json), TEST-001 and TEST-002 (2 refs to testing.json), ANIM-001 (2 refs to animations.json). Expected forward references.
- **Impact:** All must be verified when target files are created.
- **Action:** No action now. Verify on creation.

**MED-02 — Client.json and synchronization.json bidirectional see_also references**

- **Reason:** CLNT-001/006 reference SYNC rules. SYNC-002/003/011 reference CLNT rules. Creates mutual dependency cycle.
- **Impact:** Files are tightly coupled. Changes in one must consider the other.
- **Action:** No action required. Cycle is architecturally legitimate.

**MED-03 — ACTN-002 lists "persist" as separate step redundant with "execute"**

- **Reason:** Five-step lifecycle defines step 2 (execute) and step 3 (persist) as separate. Description says mutations are applied inside execute, making step 3 redundant.
- **Impact:** Formal documentation states five steps but describes four.
- **Action:** Remove step 3 or merge with step 2.

### Low

**LOW-01 — ACTN-014 is a review checklist embedded as a runtime rule**

- **Reason:** Specifies a 10-item process checklist. This is meta-guidance, not an implementation rule.
- **Impact:** Inflates rule count. No functional harm.
- **Action:** No action required.

---

## Verified Architectural Patterns

| Pattern | Status |
|---|---|
| Hierarchical Refinement (6 constitutional-to-runtime pairs) | Verified |
| One table, one Manager (CORE-016 + ARCH-005/006) | Verified |
| Actions under 15 lines, strict lifecycle (ACTN-001/002) | Verified |
| Centralized Notifications (CORE-011 + NOTF-001/002/010) | Verified |
| Idempotent handlers (CORE-007 + NOTF-012 + CLNT-007 + SYNC-003) | Verified |
| Validation before mutation (CORE-004 + ACTN-002/005) | Verified |
| All persistence through Managers (CORE-010 + ARCH-016 + ACTN-006/010) | Verified |
| getAllDatas delegates to Managers (ARCH-003 + SYNC-001) | Verified |
| refreshUI + refreshHand + clearTurn trio (NOTF-005/006/007 + SYNC-002/006/007/008) | Verified |
| No entity data in globals (PERS-006/008 + STAT-013) | Verified |
| Data-driven configuration (CORE-008 + PERS-009/010) | Verified |
| Zombie on every non-GAME state (CORE-009 + STAT-009/010) | Verified |
| giveExtraTime on every turn (CORE-009 + STAT-011) | Verified |
| Spectator never receives private notifications (NOTF-013 + SYNC-005) | Overlap flagged HIGH-01 |

---

## Outstanding Forward References

### To rules/undo-replay.json (12 references)

| Source | Rule ID | Referenced ID |
|---|---|---|
| constitution.json | CORE-006 | UNDO-001 |
| architecture.json | ARCH-010 | UNDO-001 |
| actions.json | ACTN-012 | UNDO-001 |
| persistence.json | PERS-014 | UNDO-001 |
| state-machine.json | STAT-016 | UNDO-005 |
| notifications.json | NOTF-012 | UNDO-007 |
| synchronization.json | SYNC-009 | UNDO-009 |
| notifications.json | NOTF-007 | UNDO-010, UNDO-011 |
| synchronization.json | SYNC-006 | UNDO-010 |
| synchronization.json | SYNC-007 | UNDO-010 |
| synchronization.json | SYNC-008 | UNDO-011 |

### To rules/testing.json (2 references)

| Source | Rule ID | Referenced ID |
|---|---|---|
| constitution.json | CORE-013 | TEST-001 |
| architecture.json | ARCH-007 | TEST-002 |

### To rules/animations.json (2 references)

| Source | Rule ID | Referenced ID |
|---|---|---|
| client.json | CLNT-003 | ANIM-001 |
| client.json | CLNT-004 | ANIM-001 |

---

## Recommended Actions

### Runtime modifications

| Priority | File | Change |
|---|---|---|
| Critical | constitution.json CORE-008 | see_also: replace PERS-004 with PERS-009, PERS-010 |
| Critical | constitution.json CORE-009 | see_also: replace STAT-006 with STAT-009, STAT-011 |
| High | notifications.json / synchronization.json | Remove NOTF-013; add see_also to SYNC-005 |
| High | constitution.json CORE-012 / architecture.json ARCH-014 | Amend CORE-012 to permit read-only get* calls via injected interfaces |
| High | notifications.json or persistence.json | Add rule: notifications sent after transaction commit |
| Medium | actions.json ACTN-002 | Remove or merge redundant step 3 (persist) |

### Planning modifications

| Priority | File | Change |
|---|---|---|
| High | rule-partition-plan.md 1.4 | Update priority scale to match actual file priorities |
| Medium | rule-partition-plan.md 1.6 | Update rule counts to reflect 8 files, 121 rules |
| Low | runtime-v1.1-reconciliation.md | Add note that CORE-008 fix was documented but not applied |

### No action required

| Item | Rationale |
|---|---|
| 12 forward references to unimplemented files | Expected. Verify on target file creation. |
| Client-Sync bidirectional see_also | Legitimate mutual dependency. Accept. |
| STAT-007 see_also CORE-010 tenuous | Low impact. Accept. |
| CORE-010 bundles five prohibitions | Accepted architectural pattern per reconciliation. |

---

## Go / No-Go

**Recommendation: GO**

**with conditions.**

Proceed with `rules/undo-replay.json` once the following conditions are met:

1. **CRIT-01 resolved** — CORE-008 see_also updated from PERS-004 to PERS-009/PERS-010
2. **CRIT-02 resolved** — CORE-009 see_also updated from STAT-006 to STAT-009/STAT-011
3. **HIGH-01 resolved** — NOTF-013 / SYNC-005 ownership overlap resolved (recommend removing NOTF-013)
4. **HIGH-04 resolved** — CORE-012 / ARCH-014 contradiction resolved (recommend amending CORE-012)

Items HIGH-02, HIGH-03, HIGH-05, and all medium/low items are not blockers for undo-replay.json generation but should be resolved before proceeding with the remaining three runtime files (testing.json, animations.json, migration.json).

**Rationale:** All 12 forward references to undo-replay.json are known and can be resolved in one pass. The prerequisites for undo mechanics (old-value logging, LIFO reversal, checkpoints, notification cleanup, post-undo state restoration, replay determinism) are fully defined in the existing eight files. The three blocking issues are isolated cross-reference and ownership defects that do not affect the architectural foundation.

---

## Next Milestone

**Next runtime artifact:** `rules/undo-replay.json`

**Prerequisites:**

- Resolve CRIT-01 and CRIT-02 (incorrect cross-references)
- Resolve HIGH-01 (ownership overlap between notifications and synchronization)
- Resolve HIGH-04 (constitutional-runtime contradiction on Manager-to-Manager calls)
- Verify all 12 incoming forward references from existing files resolve to UNDO rule IDs
- Follow the partition plan specification (2.10) for rule content and scope boundaries
