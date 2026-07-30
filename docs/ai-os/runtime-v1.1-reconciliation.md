# Runtime Specification v1.1 Reconciliation

## Background

After the first five runtime rule files reached implementation maturity (constitution.json, architecture.json, state-machine.json, actions.json, persistence.json), a cross-file consistency audit revealed several tensions between the planning documents and the implemented runtime.

The core conflict was architectural: the planning documents asserted that every concept has exactly one destination file and that rule text must never be duplicated. Yet the planning documents themselves assigned overlapping concepts (Game.php orchestration, Manager ownership, layer boundaries) to both constitution.json and architecture.json simultaneously. The runtime implementation followed the planning assignments and created these overlapping pairs.

A reconciliation pass was performed to resolve this as a documentation issue rather than a runtime issue. The planning documents were updated to reflect the architectural model the runtime actually implements.

## Inputs

- Cross-File Consistency Audit Report (2026-07-29)
- Runtime v1.1 Reconciliation Plan (approved)
- Documentation Reconciliation Summary
- `docs/ai-os/rule-partition-plan.md` (pre-reconciliation)
- `docs/ai-os/runtime-skill-architecture.md` (pre-reconciliation)
- `docs/ai-os/bga-senior-engineer-doctrine.md`
- Implemented runtime artifacts (constitution.json, architecture.json, state-machine.json, actions.json, persistence.json)

---

## Accepted Decisions

### Hierarchical Refinement

**Decision:** The runtime recognises three layers of rule expression — Constitutional, Runtime, and Review. A single engineering concept may legitimately appear at multiple layers. This is intentional design, not duplication.

**Reason:** The original partition plan claimed single-file ownership and forbade rule text duplication. However, the doctrine itself (§15) states immutable laws that are inherently accompanied by implementation rules. For example, §15 law 4 ("Game.php is a switchboard under 300 lines") is both a constitutional invariant and an architectural constraint. Separating them into two files with different checks, violations, and fixes is more useful than forcing all content into one file or stripping one file of actionable guidance. The audit confirmed six such hierarchical pairs across the five implemented files.

**Impact:**
- Constitutional rules (priority 1) state the law. Runtime rules (priority 2–4) provide implementation checks. Both coexist for the same concept.
- Runtime rules MUST reference their constitutional precedent via `see_also`.
- Runtime rules MUST NOT weaken or contradict constitutional rules.
- The cross-file reference protocol (§4.3) was updated to exempt hierarchical pairs from the no-duplication rule.
- The ownership section (§5.2) was renamed from "No Duplicated Ownership" to "Hierarchical Ownership Model" and lists the specific pairs as examples of refinement rather than defects.

---

### Schema Version 1.1

**Decision:** The rule schema is frozen at Version 1.1. The proven fields from the five implemented files are now canonical.

**Reason:** The pre-implementation schema in `runtime-skill-architecture.md` defined `violation` as a String and did not include `applies_to`, `exceptions`, or `rationale`. All five implemented files use `violation` as an Array (multiple examples are more useful for AI detection), include `applies_to` as a required field on every rule, include `exceptions` as an optional field (empty array when none), and include `rationale` on constitutional rules. The planning document schema was corrected to match.

**Impact:**
- `violation`: changed from String to Array of Strings.
- `applies_to`: added as Required field (Array of Strings).
- `exceptions`: added as Optional field (Array of Strings).
- `rationale`: added as Optional field (String; required for constitutional rules).
- No new fields may be introduced without a schema version change.
- Future rule files MUST follow Schema v1.1. Existing files already conform.

---

### File Size Guidance

**Decision:** The 150-line hard maximum is replaced with a 500-line soft limit and 800-line hard limit.

**Reason:** The original 150-line limit was based on pre-implementation estimates. Five implemented files range from 392 to 616 lines. Each rule is more thorough than anticipated, including violation arrays (3–5 examples), exception lists, applies_to arrays, and detailed check/fix instructions. The 800-line hard limit aligns with ARCH-018 (Managers under 800 lines) and provides a realistic boundary. Files exceeding 800 lines should be split by sub-domain rather than reducing rule quality.

**Impact:**
- Both planning documents updated with the new limits.
- No existing file exceeds 800 lines (largest is architecture.json at 616 lines).
- The token budget for the full 12-file set will exceed the original 12K estimate, but tiered loading keeps per-task consumption within the 3K budget.

---

### Runtime vs Planning Precedence

**Decision:** When a planning document estimate or constraint contradicts the implemented runtime, the runtime takes precedence unless an obvious runtime defect exists.

**Reason:** The planning documents were written before implementation. Estimates for line counts, field types, and file sizes proved inaccurate. Rather than reducing rule quality to match outdated estimates, the planning documents were updated to reflect the working implementation. The only runtime change was a single cross-reference fix (CORE-008 see_also PERS-004 → PERS-009/PERS-010).

**Impact:**
- Planning documents are now descriptive of the runtime, not prescriptive against it.
- Future planning-document estimates should be treated as targets, not hard limits, until validated by implementation.
- If a future runtime change contradicts a planning document, the planning document must be updated before the runtime change is approved.

---

### Runtime Ownership Model

**Decision:** Concept ownership is hierarchical rather than flat. A concept may be owned at the constitutional layer (invariant, priority 1) and separately at the runtime layer (implementation, priority 2–4). Both are canonical owners within their layer.

**Reason:** The flat "one concept, one file" model could not accommodate concepts like "Game.php is a switchboard" which is simultaneously an immutable law (§15) and an architectural constraint. The hierarchical model explicitly names three layers: Constitutional Rule (what must be true), Runtime Rule (how to achieve it), and Review Guidance (how to verify it). Review guidance is embedded in runtime rule checks rather than forming a separate file.

**Impact:**
- The partition plan §1.1 was rewritten to describe canonical ownership by layer.
- Section §1.3 (Hierarchical Refinement) was added with five governing rules.
- The six constitutional-to-runtime pairs in the current implementation are now documented as intentional design.
- Future concept additions must specify which layer they belong to.

---

### Cross-Reference Policy

**Decision:** Cross-references between rule files are informative, not normative. The agent loads the owning file for authoritative rule text. Cross-references must point to existing rule IDs.

**Reason:** The original policy forbade rule text duplication but was contradicted by the planning documents themselves. The reconciliation clarified that hierarchical pairs are exempt; peer-file duplication remains forbidden. The canonical reference map was corrected to remove STAT-011 from architecture.json (no natural home) and to annotate indirect references (ARCH-010 → UNDO-001, PERS-014 → UNDO-001, PERS-009 → NOTF-003).

**Impact:**
- §4.2 (Canonical Reference Map) updated.
- §4.3 item 1 updated to exempt hierarchical pairs.
- One runtime cross-reference fixed (CORE-008 → PERS-004 → PERS-009/PERS-010).
- Deferred cross-references (to not-yet-created files) were verified when the remaining seven files were created. All resolve.

---

## Runtime Changes

| Change | File | Rule | Impact |
|---|---|---|---|
| Fix cross-reference | constitution.json | CORE-008 | `see_also` PERS-004 replaced with PERS-009/PERS-010. PERS-004 (atomic conditional UPDATE) is unrelated to the config-as-data concept. PERS-009 (material.inc.php) and PERS-010 (no hardcoded values) are the correct persistence counterparts. |

No runtime JSON files were rewritten, restructured, or split. No new rules were added. No rule IDs changed.

---

## Planning Document Changes

### `docs/ai-os/rule-partition-plan.md`

- **§1.1** — Rewrote ownership theorem to describe layered ownership.
- **§1.3** — Added Hierarchical Refinement section (new).
- **§1.4–1.5** — Renumbered (no content change).
- **§1.6** — Replaced estimated token budget with implemented file sizes and 500/800-line guidance.
- **§3.1** — CORE-000 → CORE-013.
- **§3.3** — CORE-000 → CORE-013; ARCH-000 → ARCH-001/005/016; UNDO-000 → UNDO-001/002.
- **§3.7** — ARCH-000 → ARCH-001/005/016.
- **§3.12** — CORE-000 → CORE-013; corrected "don't commit secrets" and "don't silently change behavior" mappings.
- **§3.15** — Updated rule count to CORE-001..016.
- **§4.2** — Removed STAT-011 from architecture row; annotated indirect references.
- **§4.3** — Exempted hierarchical pairs from no-duplication rule.
- **§5.2** — Rewrote to describe hierarchical ownership model.
- **§5.4** — Added note about constitutional rules including check/violation/fix fields.
- **§5.5–5.7** — Replaced estimates with implemented values.
- **Status** — Updated to v1.1.
- **Footer** — Updated to reference v1.1 and §1.3.

### `docs/ai-os/runtime-skill-architecture.md`

- **Status** — Updated to v1.1.
- **§2.1** — File size limits changed from 150 to 500/800 (soft/hard).
- **§2.3** — Budget allocation updated with implemented/projected values; added note about tiered loading mitigation.
- **§5.3** — Schema example updated to real ARCH-001 with correct fields. Rule field definitions replaced with Schema v1.1 (violation→Array, added applies_to/exceptions/rationale). Added schema freeze statement. Updated forbidden clause to exempt hierarchical pairs. Updated max size to 500/800.

---

## Deferred Items (Historical)

| Item | Resolution | Status |
|---|---|---|
| Cross-references to pending files (NOTF-*, SYNC-*, UNDO-*, TEST-*, CLNT-*, ANIM-*, MIGR-*) | All 12 files are now implemented. All forward references resolve. | RESOLVED |
| "Don't commit secrets" concept absent from runtime | No dedicated rule. Covered by CORE-013 correctness mandate. | CARRIED FORWARD |
| Full 12-file token budget exceedance | 12 files consume ~13,810 tokens. Tiered loading keeps per-task usage under 3K. Budget exceedance accepted. | ACCEPTED |
| Potential file splitting for constitution.json and architecture.json | Both files remain under 800 lines (487 and 615 respectively). No split needed. | NO ACTION REQUIRED |

---

## Rejected Recommendations

### ARCH-001 / CORE-002 classified as a duplication defect

The audit recommended treating ARCH-001 and CORE-002 as a duplicate requiring removal of one. The reconciliation determined this is a hierarchical pair (constitutional law + architectural implementation) and is intentionally preserved.

**Rationale:** CORE-002 states the constitutional invariant. ARCH-001 provides architecture-specific checks (line count, SQL grep, method review) and applies_to scope (Game.php only). Removing either would lose either the constitutional mandate or the actionable guidance.

### ARCH-006 / CORE-016 classified as a duplication defect

Same pattern. CORE-016 is the constitutional "one table, one Manager" law. ARCH-006 is the architectural write-exclusivity rule with different checks and violations. Preserved as hierarchical refinement.

### ACTN-002 / ACTN-005 / CORE-004 classified as triple duplication

CORE-004 states "validate before mutation" as a constitutional law. ACTN-002 defines the five-step action lifecycle that includes validate-then-execute as a step. ACTN-005 enforces all-validation-before-any-mutation as a grouping rule. These are three distinct levels (law, lifecycle definition, grouping enforcement) with different checks. Preserved.

### STAT-013 → PERS-006/PERS-008 classified as a dependency-order violation

The Engine (state-machine.json) references globals constraints (persistence.json) directly. The audit argued this violates the strict dependency chain (state machine → actions → persistence). The reconciliation determined the Engine has a legitimate, direct dependency on globals that cannot be mediated through actions. The see_also reference is informative and architecturally correct. No change.

### STAT-016 → UNDO-005 classified as a dependency-order violation

Simultaneous turns and undo mechanics are intrinsically coupled (cross-player invalidation via reevaluate). The doctrine itself pairs them in §6 Simultaneous Turns. The reference is intentional and necessary. No change.

### CORE-007 and CORE-010 classified as bundling multiple invariants

Both rules bundle multiple related concepts (CORE-007: absolute values + idempotency + seeding; CORE-010: five layer prohibitions). The constitutional layer is intentionally broad — these are single constitutional laws with multiple observable manifestations. The implementation-level split happens in downstream files. No change.

---

## Runtime Specification Status

**Version:** 1.1

**Status:** Frozen

**Frozen runtime artifacts:**
- `rules/constitution.json`
- `rules/architecture.json`
- `rules/state-machine.json`
- `rules/actions.json`
- `rules/persistence.json`

**All runtime artifacts (implemented):**
- `rules/constitution.json`
- `rules/architecture.json`
- `rules/state-machine.json`
- `rules/actions.json`
- `rules/persistence.json`
- `rules/notifications.json`
- `rules/client.json`
- `rules/synchronization.json`
- `rules/undo-replay.json`
- `rules/testing.json`
- `rules/animations.json`
- `rules/migration.json`

---

## Future Reconciliation Triggers

Runtime Specification v1.1 may be reopened for reconciliation if any of the following occur:

1. **Contradiction discovered** — A planning document instruction directly contradicts an implemented runtime rule in a way that cannot be resolved by documentation update alone.

2. **Major schema evolution** — A new required field is proposed for the rule schema (e.g. a `deprecated` field or a `replaced_by` field). This would require Schema Version 1.2.

3. **Partition ownership conflict** — A remaining runtime file (e.g. undo-replay.json) naturally references a concept whose ownership is ambiguous between two domains, and the hierarchical refinement model does not resolve it.

4. **Final full-runtime audit** — When all 12 rule files are implemented, a complete cross-file consistency audit should be performed. That audit may identify issues that require updating v1.1.

5. **Token budget violation in practice** — If an agent task exceeds the 3K per-task budget during normal operation despite tiered loading, the reconciliation may need to address file splitting or rule consolidation.

6. **Constitutional change** — If the doctrine (§15) is amended, added to, or reprioritised in a way that affects the existing constitutional-to-runtime pairs.

---

## 7. Certification and Finalization

### 7.1 Certification Completed

Runtime Specification v1.1 underwent a final certification audit (2026-07-29). The audit evaluated all 12 rule files (227 rules, 4,831 lines) across 10 dimensions: schema compliance, cross-reference integrity, ownership analysis, architectural consistency, priority model, rule quality, coverage, dependency graph, implementation readiness, and long-term maintainability.

**Result:** PASS WITH RECOMMENDATIONS (84/100)

### 7.2 Accepted Certification Fixes

Five fixes were applied following the certification audit:

1. **TEST-014 see_also** — Replaced incorrect reference CORE-002 (Game.php orchestration) with CORE-013 (correctness priority).
2. **MIGR-008 see_also** — Replaced weak reference TEST-009 (edge case testing) with TEST-010 (coverage thresholds).
3. **UNDO-011 ordering** — Aligned clearTurn ordering with SYNC-008: clearTurn precedes refreshUI/refreshHand.
4. **applies_to vocabulary** — Normalized non-canonical applies_to values (Undo, Persistence, Testing, StateMachine, Setup, Synchronization, SimultaneousTurns, Globals, Migration, Rules, CI, TestSuite) to canonical architectural components (Actions, Database, All components, States, Game.php, Client).
5. **UNDO-002 see_also** — Replaced CORE-007 (notification payloads) with CORE-006 (undo safety) for LIFO ordering precedent.

All fixes are documented in `docs/ai-os/runtime-v1.1-certification-fixes.md`.

### 7.3 Rejected Certification Recommendations

The following certification audit recommendations were rejected and are not applied:

| Recommendation | Reason for Rejection |
|---|---|
| Merge ANIM-007 and ANIM-010 | Same-file overlap that does not affect correctness. Defers to v1.2. |
| Elevate UNDO-006 and UNDO-013 to priority 2 | Priority consistency deferral. Current priorities are functionally correct. |
| Extract ACTN-014 to checklist file | Checklist-as-rule pattern acceptable. No functional defect. |
| Remove NOTF-013 boundary overlap | NOTF-013 was partially mitigated by deferring to SYNC-005. Acceptable. |
| Update partition plan §1.4 priority scale | Partition-plan priority guidance is informational, not normative. |
| Fix various LOW findings | Issues are cosmetic or deferrable. No functional impact. |

### 7.4 Runtime Frozen

Runtime Specification v1.1 is now **frozen**.

All runtime JSON files in `rules/*.json` are the canonical source of truth. No further structural changes, rule additions, or ID changes will be made to v1.1.

Future work targets v1.2 (new capabilities) or v2.0 (breaking changes).

### 7.5 Planning Documents Synchronized

The following planning documents were synchronized with the final runtime:

| Document | Synchronization |
|---|---|
| `docs/ai-os/rule-partition-plan.md` | Rule counts, file specs, statistics, ownership summaries, and footer updated to reflect all 12 implemented files. |
| `docs/ai-os/runtime-skill-architecture.md` | Budget allocation table updated from 5 implemented files to 12. Projected values replaced with actuals. |
| `docs/ai-os/runtime-v1.1-reconciliation.md` | This section appended documenting certification, fixes, and finalization. |
| `docs/ai-os/runtime-v1.1-core-runtime-audit.md` | Historical record. No changes — findings are superseded by final certification. |
| `docs/ai-os/runtime-v1.1-final-certification-audit.md` | Historical record. No changes — recommendations document the pre-fix state. |
| `docs/ai-os/runtime-v1.1-certification-fixes.md` | Fix report. No changes — written as a permanent engineering artifact. |

No runtime JSON files were modified during synchronization.

