# Runtime Specification v1.1 — Release

**Type:** Official release document
**Version:** 1.1
**Status:** FROZEN
**Publication Date:** 2026-07-29
**Authority:** BGA Senior Engineer engineering program

---

## 1. Overview

### Purpose

The Runtime Specification defines the canonical engineering rules for Board Game Arena (BGA) Senior Engineer game implementations. It encodes architectural invariants, implementation patterns, and verification requirements into an executable rule set that an AI agent or human engineer applies when building, reviewing, or migrating BGA game projects.

### Scope

The specification covers:

- **Constitutional law** — immutable engineering invariants that cannot be overridden
- **Architecture** — component boundaries, ownership, layering, and dependency rules
- **State Machine** — state class design, transitions, args, zombie, and time management
- **Actions** — handler structure, validation layers, delegation, and lifecycle
- **Persistence** — DB schema, query patterns, transactions, globals, and configuration
- **Notifications** — payload design, public/private split, i18n, and sequencing
- **Client** — client-side architecture, widgets, handlers, and DOM contracts
- **Synchronization** — reconnect, spectator, getAllDatas, and refresh protocol
- **Undo/Replay** — undo log, checkpoints, LIFO reversal, replay determinism
- **Testing** — test hierarchy, coverage, replay testing, zombie testing
- **Animations** — animation queue, fast-mode, sequencing, and cancellation
- **Migration** — legacy-to-modern extraction order, safety rules, and parity

### Audience

- AI agents executing BGA game engineering tasks
- BGA Senior Engineer practitioners
- Code reviewers and auditors
- Engineers maintaining or extending the runtime specification

---

## 2. Release Summary

| Attribute | Value |
|---|---|
| Specification | Runtime Specification v1.1 |
| Status | **FROZEN** |
| Publication Date | 2026-07-29 |
| Schema Version | 1.1 (frozen) |
| Total Runtime Files | 12 |
| Total Rules | 227 |
| Total Lines | 4,595 |
| Total Cross-References | 185 |
| Certification Result | PASS WITH RECOMMENDATIONS |
| Certification Score | 84 / 100 |

---

## 3. Architecture Summary

### Constitutional Hierarchy

The specification uses three layers of rule expression:

| Layer | Priority | File | Purpose |
|---|---|---|---|
| Constitutional | 1 | constitution.json | Immutable engineering law. States WHAT must be true. |
| Runtime | 2–4 | Domain files (architecture, actions, etc.) | Implementation guidance. States HOW to achieve it. |
| Review Guidance | 2–4 | Embedded in runtime rule checks | Verification and auditing instructions. |

A concept may appear at multiple layers. This is hierarchical refinement, not duplication. The constitutional rule states the invariant; the runtime rule provides actionable checks, violations, and fixes.

### Runtime Layering

```
Layer 0:  constitution.json                     (immutable foundation)
Layer 1:  architecture.json                     (structural rules)
Layer 2:  state-machine.json, actions.json,     (system design)
          persistence.json
Layer 3:  notifications.json, client.json,      (interaction)
          synchronization.json
Layer 4:  undo-replay.json, testing.json,        (cross-cutting)
          animations.json
Layer 5:  migration.json                         (transformation)
```

Dependencies flow downward only. No runtime file depends on a file at a higher layer.

### Loading Model

The runtime uses a three-tier loading model designed for AI agent context windows:

| Tier | Trigger | Contents | Typical Size |
|---|---|---|---|
| 0 — Activation | Skill activation | skill.json | ~150 tokens |
| 1 — Task-load | Task identified | index.json, prompt, 2–8 rule files, checklist | ~2,500 tokens |
| 2 — Lazy-load | Explicit reference | Examples, references | ~500 tokens per load |

No single task loads all 12 rule files simultaneously. Peak per-task consumption stays under 3,000 tokens.

### Schema (Version 1.1)

Every rule in every runtime file conforms to Schema v1.1:

| Field | Required | Type | Description |
|---|---|---|---|
| id | Yes | String | Globally unique rule identifier (DOMAIN-NNN) |
| priority | Yes | Integer | 1 (immutable) through 5 (style preference) |
| rule | Yes | String | The rule itself. One sentence. Actionable. |
| violation | Yes | Array of Strings | Concrete examples of what a violation looks like |
| check | Yes | String | How to verify compliance |
| fix | Yes | String | How to correct a violation |
| tags | Yes | Array of Strings | Searchable tags for cross-domain retrieval |
| applies_to | Yes | Array of Strings | Components this rule governs |
| exceptions | No | Array of Strings | Documented exceptions |
| see_also | No | Array of Strings | Related rule IDs |
| rationale | No | String | Why the rule exists (required for constitutional rules) |
| source | No | String | Override of file-level source for this rule |

### Ownership Philosophy

Each domain concept has exactly one canonical owner at each layer:

- **Constitutional ownership** — constitution.json owns immutable engineering law
- **Runtime ownership** — each domain file owns implementation for its subsystem
- **Cross-cutting concepts** — split by concern: mechanics in one file, delegation in another, verification in a third

No Manager may own another Manager's table. No Engine node may contain domain logic. No runtime rule may weaken its constitutional precedent.

---

## 4. Runtime Inventory

| File | Domain | Rules | Lines | Priority Range | Purpose |
|---|---|---|---|---|---|
| constitution.json | Constitutional law | 16 | 487 | 1 | Immutable engineering invariants |
| architecture.json | Component architecture | 22 | 615 | 2–4 | Boundaries, ownership, layering, dependencies |
| state-machine.json | State machine | 16 | 430 | 2–3 | State class design, transitions, zombie, time |
| actions.json | Action handlers | 14 | 391 | 2–4 | Handler structure, validation, delegation |
| persistence.json | Database and data | 14 | 393 | 2–4 | Schema, queries, globals, configuration |
| notifications.json | Notifications | 14 | 397 | 2–4 | Payload design, i18n, public/private split |
| client.json | Client architecture | 14 | 396 | 2–5 | Manager pattern, widgets, handlers, DOM |
| synchronization.json | State recovery | 11 | 319 | 2–4 | Reconnect, spectator, getAllDatas, refresh |
| undo-replay.json | Undo and replay | 14 | 255 | 3 | Log tables, checkpoints, determinism |
| testing.json | Testing strategy | 17 | 319 | 4 | Hierarchy, coverage, replay/zombie testing |
| animations.json | Animation system | 14 | 256 | 4 | Queue, fast-mode, sequencing, cancellation |
| migration.json | Legacy migration | 19 | 337 | 5 | Extraction order, safety, parity, deprecation |
| **Total** | **12 files** | **227** | **4,595** | **1–5** | |

---

## 5. Implementation Statistics

### Runtime Specification

| Metric | Value |
|---|---|
| Runtime files | 12 |
| Total rules | 227 |
| Total lines | 4,595 |
| Cross-references (see_also) | 185 |
| Schema version | 1.1 (frozen) |
| Files under 800-line hard limit | 12 / 12 |
| Largest file | architecture.json (615 lines) |
| Smallest file | undo-replay.json (255 lines) |
| Priority 1 (constitutional) rules | 16 |
| Priority 2–5 (runtime) rules | 211 |

### Supporting Documents

| Document | Lines | Purpose |
|---|---|---|
| bga-senior-engineer-doctrine.md | 400 | Operating doctrine — engineering values, workflow, anti-goals |
| rule-partition-plan.md | 771 | Ownership mapping, concept map, file specifications |
| runtime-skill-architecture.md | 1,391 | Runtime packaging, loading model, directory structure |
| runtime-v1.1-reconciliation.md | 293 | Cross-file consistency reconciliation |
| runtime-v1.1-core-runtime-audit.md | 252 | Interim audit of first 8 files |
| runtime-v1.1-final-certification-audit.md | 574 | Final certification audit |
| runtime-v1.1-certification-fixes.md | 236 | Post-certification fix report |
| runtime-v1.1-documentation-synchronization.md | 187 | Planning document synchronization report |
| runtime-v1.1-release.md | — | This document |

### Certification History

| Milestone | Date | Result |
|---|---|---|
| Implementation complete | 2026-07-29 | 12 files, 227 rules |
| Reconciliation complete | 2026-07-29 | Cross-file consistency resolved |
| Interim core audit | 2026-07-29 | 2 critical, 5 high findings |
| Final certification audit | 2026-07-29 | PASS WITH RECOMMENDATIONS (84/100) |
| Certification fixes applied | 2026-07-29 | 5 accepted fixes |
| Planning synchronization | 2026-07-29 | 3 documents updated |
| **RELEASE** | **2026-07-29** | **v1.1 FROZEN** |

---

## 6. Certification History

### Implementation

The runtime was implemented in two phases. Phase 1 (core) produced 5 files: constitution.json, architecture.json, state-machine.json, actions.json, persistence.json. Phase 2 produced the remaining 7 files: notifications.json, client.json, synchronization.json, undo-replay.json, testing.json, animations.json, migration.json.

All 12 files were implemented against the specifications in rule-partition-plan.md and the schema in runtime-skill-architecture.md.

### Reconciliation

A cross-file consistency audit during Phase 1 revealed tensions between planning documents and the implemented runtime. The reconciliation (runtime-v1.1-reconciliation.md) resolved these through:

- Adoption of hierarchical refinement model for constitutional-to-runtime pairs
- Schema v1.1 freeze with three field additions (applies_to, exceptions, rationale)
- File size guidance update from 150-line hard limit to 500/800-line soft/hard limits
- Runtime-over-planning precedence rule for future conflicts

### Certification

The final certification audit (runtime-v1.1-final-certification-audit.md) evaluated all 12 files across 10 dimensions:

- Schema compliance: 96 / 100
- Cross-reference integrity: 92 / 100
- Ownership boundaries: 78 / 100
- Architectural consistency: 85 / 100
- Priority model: 72 / 100
- Rule quality: 80 / 100
- Coverage: 94 / 100
- Dependency graph: 95 / 100
- Implementation readiness: 85 / 100
- Long-term maintainability: 80 / 100
- **Overall: 84 / 100 — PASS WITH RECOMMENDATIONS**

### Certification Fixes

Five fixes were applied post-certification (runtime-v1.1-certification-fixes.md):

1. TEST-014: corrected see_also reference (CORE-002 → CORE-013)
2. MIGR-008: corrected see_also reference (TEST-009 → TEST-010)
3. UNDO-011: aligned notification ordering with SYNC-008
4. applies_to: normalized vocabulary across 4 files
5. UNDO-002: corrected see_also reference (CORE-007 → CORE-006)

### Planning Synchronization

Three planning documents were updated to reflect the final runtime (runtime-v1.1-documentation-synchronization.md):

- rule-partition-plan.md: statistics, rule counts, concept maps, footer
- runtime-v1.1-reconciliation.md: deferred items, runtime status, certification appendix
- runtime-skill-architecture.md: budget allocation table

---

## 7. Known Deferred Items

The following items are intentionally deferred from v1.1. They are not defects. They are scoping decisions.

| Item | Status | Notes |
|---|---|---|
| "Don't commit secrets" as an explicit rule | Carried forward | Covered by CORE-013 correctness mandate. No dedicated rule needed. |
| 12-file full-skill budget exceedance | Accepted | Full load exceeds 12K tokens. Tiered loading keeps per-task under 3K. No change needed. |
| Notification-transaction timing contract | Ambiguous | No rule specifies whether notifications are inside or outside the DB transaction. Implementers must decide per-project. |
| Coverage measurement tooling | Not specified | TEST-010 thresholds require tooling. Implementation guidance exists but no tool is specified. |
| Performance profiling methodology | Out of scope | Covered implicitly by PERS-013. No dedicated profiling rule. |
| Code security rules (secrets, injection) | Out of scope | Platform framework responsibility. Doctrine anti-goal prohibits commits secrets. |

---

## 8. Governance

### Freeze Declaration

Runtime Specification v1.1 is **frozen** as of 2026-07-29.

No further changes will be made to v1.1. This includes:

- No new rules
- No rule ID changes
- No rule removals
- No field additions or removals from Schema v1.1
- No file restructuring
- No directory changes

### Canonical Source of Truth

The 12 runtime JSON files in `rules/*.json` are the canonical source of truth for the Runtime Specification.

Planning documents describe the runtime but are not authoritative over it. If a planning document contradicts a runtime JSON file, the runtime JSON file prevails.

### Change Process

Any change to the frozen specification requires a formal change proposal:

1. **Proposal** — Document the proposed change, rationale, and impact.
2. **Review** — Evaluate against doctrine engineering priorities.
3. **Approval** — Obtain approval for the next version target.
4. **Implementation** — Update runtime JSON files.
5. **Synchronization** — Update all affected planning documents.
6. **Release** — Publish the new version.

### Planning Document Maintenance

Planning documents must remain synchronized with the runtime. Any change to a runtime JSON file must be accompanied by updates to:

- rule-partition-plan.md (if rule counts, IDs, or ownership change)
- runtime-skill-architecture.md (if loading model, schema, or structure changes)
- runtime-v1.1-reconciliation.md (if reconciliation decisions change)

---

## 9. Future Versioning

### Patch (v1.1.x)

Patch releases may contain:

- Rule wording clarifications that do not change meaning
- Check or fix improvements
- Metadata updates (tags, source citations)
- Cross-reference corrections
- Documentation synchronization

**No breaking changes.** No new rules. No rule ID changes.

### Minor (v1.x.0)

Minor releases may contain:

- New rule files for new domains
- New rules within existing files
- New prompts, examples, or checklists
- Planning document additions

**Backward compatible.** Existing rule IDs remain stable. Existing prompts and references continue to work. Existing rule meanings do not change.

### Major (v2.0.0)

Major releases may contain:

- Rule ID changes or renumbering
- Schema field additions, removals, or type changes
- File restructuring or directory changes
- Loading model changes
- Breaking constitutional changes

**May break existing references.** Requires full re-synchronization of all planning documents.

### Rule ID Stability

Rule IDs are **immutable once published**:

- Never change a rule ID
- Never renumber rules
- Never repurpose a rule ID
- For corrections: add a new rule with a new ID and deprecate the old one
- Remove deprecated rules in the next MAJOR version

---

*End of release document. Runtime Specification v1.1 is frozen as the canonical engineering specification for BGA Senior Engineer projects.*
