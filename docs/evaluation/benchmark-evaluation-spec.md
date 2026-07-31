# BGA Senior Engineer — Benchmark Evaluation Specification

**Repository:** `bga-senior-engineer`
**Source Codebase:** `bga-mercurio` (read-only reference)
**Companion Document:** `docs/evaluation/benchmark-task-corpus.md`
**Version:** 1.0
**Date:** 2026-07-31
**Status:** Canonical — evaluation authority for the benchmark corpus

---

## Table of Contents

- [1. Purpose and Scope](#1-purpose-and-scope)
- [2. Evaluation Model](#2-evaluation-model)
  - [2.1 Evaluation Workflow](#21-evaluation-workflow)
  - [2.2 Verdicts](#22-verdicts)
  - [2.3 Critical Failure Conditions](#23-critical-failure-conditions)
  - [2.4 Rubric Categories and Scale](#24-rubric-categories-and-scale)
  - [2.5 Category Weight Families](#25-category-weight-families)
  - [2.6 Evidence Standard](#26-evidence-standard)
  - [2.7 Automatic vs Manual Validation](#27-automatic-vs-manual-validation)
- [3. Task Specifications](#3-task-specifications)
  - [3.1 ARC-01 — Extract Notification Layer from Monolithic Game.php](#31-arc-01)
  - [3.2 ARC-02 — Extract Manager Classes from Game.php](#32-arc-02)
  - [3.3 ARC-03 — Implement Generic Board Interaction Framework](#33-arc-03)
  - [3.4 MIG-01 — Migrate Legacy Action Handler to #[PossibleAction]](#34-mig-01)
  - [3.5 MIG-02 — Migrate Dojo Client Module to ES Module](#35-mig-02)
  - [3.6 MIG-03 — Migrate Legacy State Machine to State Classes](#36-mig-03)
  - [3.7 DBG-01 — Fix Notification-After-State-Transition Ordering](#37-dbg-01)
  - [3.8 DBG-02 — Implement stReplay / restoreReplay for Replay Support](#38-dbg-02)
  - [3.9 DBG-03 — Fix Silent Exception Swallowing in Tech Modifier Dispatch](#39-dbg-03)
  - [3.10 NOT-01 — Migrate Deprecated notifyAllPlayers to Modern BGA API](#310-not-01)
  - [3.11 NOT-02 — Consolidate Duplicated Notification Blocks](#311-not-02)
  - [3.12 SYNC-01 — Fix Reconnect State Inconsistency for Drawing Phase](#312-sync-01)
  - [3.13 SYNC-02 — Add Spectator State Projection](#313-sync-02)
  - [3.14 CLI-01 — Implement Client-Side Undo UI Feedback](#314-cli-01)
  - [3.15 CLI-02 — Extract Client Manager Modules from Monolithic Game.js](#315-cli-02)
  - [3.16 STM-01 — Implement ResolvePirateRaid Client State](#316-stm-01)
  - [3.17 STM-02 — Fix Undefined Client State Transitions](#317-stm-02)
  - [3.18 PER-01 — Normalize State Blob into Structured Tables](#318-per-01)
  - [3.19 PER-02 — Implement Game Statistics System](#319-per-02)
  - [3.20 CRV-01 — Review Exception Handling Semantics](#320-crv-01)
  - [3.21 CRV-02 — Review SQL Injection and Type Safety](#321-crv-02)
  - [3.22 TST-01 — Write Server-Side Unit Tests for Tech Modifier Pipeline](#322-tst-01)
  - [3.23 TST-02 — Write Client-Side Notification Handler Tests](#323-tst-02)
- [4. Automatic Validation Catalog](#4-automatic-validation-catalog)
- [5. Repository Safety Protocol](#5-repository-safety-protocol)
- [6. Final Verification](#6-final-verification)
- [Appendix A: Evidence Templates](#appendix-a-evidence-templates)
- [Appendix B: Session Setup](#appendix-b-session-setup)

---

## 1. Purpose and Scope

This document is the canonical evaluation specification for the benchmark corpus defined in `docs/evaluation/benchmark-task-corpus.md`.

The corpus defines **what** tasks exist. This document defines **how** task outputs are evaluated: what constitutes an excellent, acceptable, poor, or incorrect implementation, what evidence a solution must demonstrate, and which properties can be checked mechanically versus by human review.

### 1.1 Design Constraints

The specification is:

- **Implementation independent** — criteria describe observable properties of a solution, not the internal shape of the code. Any valid approach that satisfies the outcomes is acceptable.
- **Reusable across AI systems** — criteria are written as machine-checkable and human-checkable assertions, not as instructions tied to one agent architecture.
- **Human- and automation-friendly** — every task section separates automatic checks (Section 4) from manual review.
- **Stable as Mercurio evolves** — line numbers quoted from `bga-mercurio` are historical anchors only; criteria are structural and remain valid if the reference codebase changes.

### 1.2 Supporting References

| Reference | Role |
|---|---|
| `docs/evaluation/benchmark-task-corpus.md` | Task definitions (background, objectives, criteria) |
| `docs/standards/*` (11 engineering standards) | The engineering standard the codebase must satisfy |
| `bga-senior-engineer-skill/rules/*.json` (12 files, 185 rules) | The Runtime Specification v1.1 — executable rule set for framework compliance |
| `bga-senior-engineer-skill/checklists/*.json` | Pre-commit / pre-review / pre-release self-validation checklists |
| `docs/foundation/reference-project-analysis.md` | Canonical patterns from Agricola, Ark Nova, Arnak, Earth |
| `docs/implementation/runtime-validator/10-runtime-validator-v1.0-release.md` | Runtime validator tooling |
| `official-docs/*` | Official BGA framework documentation |

### 1.3 Evaluation Subject

The unit of evaluation is a **task submission**: the complete set of changes, evidence documents, and test results produced by one agent for one corpus task against a checkout of `bga-mercurio`.

**Repository safety applies at all times.** The reference codebase `bga-mercurio` is READ-ONLY. See Section 5.

---

## 2. Evaluation Model

### 2.1 Evaluation Workflow

Evaluation proceeds through six gates in order. Any gate failure short-circuits later gates.

| Gate | Name | Nature | Failure Consequence |
|---|---|---|---|
| G0 | Repository safety | Automatic | Submission rejected (Section 5) |
| G1 | Build / syntax | Automatic | Rejected before review |
| G2 | Automatic validation | Automatic | Rejected if a blocking check fails |
| G3 | Behavioral verification | Manual + scripted | Findings feed rubric |
| G4 | Evidence review | Manual | Findings feed rubric |
| G5 | Scoring | Manual | Verdict assigned (Section 2.2) |

**G0 — Repository safety.** Verify `bga-mercurio` is unmodified: no file modifications, no created files, no deleted files, no git metadata changes (see Section 5). Any violation rejects the submission outright.

**G1 — Build / syntax.** All changed files must pass the build gate (Section 4, catalog rows B1–B4). A submission that does not parse is not evaluated further.

**G2 — Automatic validation.** Run every automatic check listed in the task's "Automatic Validation Opportunities" section, plus any applicable global checks from Section 4. Each check has a blocking/non-blocking classification in Section 4. Blocking failures reject the submission; non-blocking failures cap the Framework Compliance category score.

**G3 — Behavioral verification.** The evaluator (human or harness) exercises the game in a controlled environment: action dispatch, notification replay, reconnect simulation, spectator flow, replay generation, undo flow. The exact scenarios are listed per task. Where the environment cannot fully simulate BGA, the evaluator substitutes static verification against the gamelog schema and documented scenarios; this substitution must be recorded in the evaluation report.

**G4 — Evidence review.** Assess the required evidence (Section 2.6, per-task lists): reasoning documents, architecture explanation, modified subsystem inventory, testing evidence, validation evidence.

**G5 — Scoring.** Apply the per-task rubric (weights in Section 2.5, anchors in Section 2.4). Assign a verdict (Section 2.2).

### 2.2 Verdicts

| Verdict | Condition | Meaning |
|---|---|---|
| **Excellent** | Total ≥ 90 AND no critical failure condition triggered | Production-ready implementation with outstanding engineering |
| **Acceptable** | Total ≥ 75 AND no critical failure condition triggered | Correct implementation; may have minor gaps |
| **Poor** | Total ≥ 60 AND < 75, OR any category scores below 50 | Functional but materially deficient in quality |
| **Incorrect** | Any critical failure condition triggered (Section 2.3), OR total < 60 | Fails evaluation |

Additional rule: a category that scores 50–59 caps the total at 85 ("Acceptable" ceiling) regardless of other categories. This prevents a critically weak dimension being hidden by strong others.

### 2.3 Critical Failure Conditions

The following conditions **fail evaluation automatically** (verdict Incorrect) when observed in a submission, regardless of rubric score. Per-task sections refine these with task-specific instances.

| ID | Condition | Definition and Detection |
|---|---|---|
| C1 | **Framework violation** | The implementation bypasses or breaks a BGA framework contract: state machine lifecycle, `#[PossibleAction]` dispatch, notification API semantics, zombie/time rules, or state-class API. Detected by framework load failure, runtime errors, or review against the runtime rules. |
| C2 | **Replay regression** | Replay support is broken or produces client state different from original play. Detected by replay generation and state comparison. |
| C3 | **Synchronization regression** | A reconnect or spectator path leaves client state inconsistent with server state. Detected by reconnect/spectator simulation. |
| C4 | **Hidden information leak** | Private data (hand contents, deck order, unrevealed cards, hidden counts, draft picks) is delivered to any recipient not entitled to it — via notifications, `getAllDatas()`, state args, or logs. Detected by payload inspection. |
| C5 | **Duplicated architecture** | The submission introduces a second parallel implementation of a component that already exists, instead of extending the existing one (e.g., a new interaction system beside the Interaction Framework, a second state-handling path beside registered state classes). Detected by review and structural analysis. |
| C6 | **Notification ordering bug** | Notifications are dispatched after `nextState()` or before state persistence in a way that loses payloads for reconnecting clients. Detected by call-order audit and reconnect simulation. |
| C7 | **Persistence corruption** | Data loss, type corruption, partial mutation on failure, or non-atomic writes. Detected by failure-injection and state inspection. |
| C8 | **Maintainability regression** | The net structural quality of the codebase decreases (monolith grows, duplication is added, dead code accumulates, dependencies become circular) without task-justified reason. Detected by size/duplication metrics and review. |
| C9 | **Repository safety violation** | Any modification, creation, or deletion inside `bga-mercurio`. Detected by G0. |

### 2.4 Rubric Categories and Scale

Five categories. Each is scored 0–100 on the anchors below, then multiplied by its task weight; weights always total 100.

| Category | What it measures |
|---|---|
| **Correctness** | Observable behavior matches the task objective; existing behavior preserved; edge cases handled |
| **Architecture** | Boundaries, ownership, dependency direction, absence of duplication, testability |
| **Framework Compliance** | Conformance to the Runtime Specification v1.1 rules and official BGA framework contracts |
| **Maintainability** | Readability, naming, size discipline, documentation, absence of dead code |
| **Testing** | Existence, meaningfulness, and reproducibility of tests for the changed behavior |

| Score | Anchor description (all categories) |
|---|---|
| 100 | All success criteria satisfied; no regressions; edge cases explicitly handled; evidence complete |
| 75 | Primary criteria satisfied; minor gaps with documented justification |
| 50 | Majority satisfied; one or more observable deviations or missing evidence items |
| 25 | Substantial deviation from the objective; core behavior incorrect or evidence absent |
| 0 | Not attempted, or the category is wholly failed |

### 2.5 Category Weight Families

Each task's rubric table references one family below; weights always sum to 100.

| Family | Correctness | Architecture | Framework Compliance | Maintainability | Testing | Used by |
|---|---|---|---|---|---|---|
| ARCH | 30 | 35 | 10 | 15 | 10 | ARC-01, ARC-02, ARC-03 |
| MIGR | 35 | 15 | 30 | 10 | 10 | MIG-01, MIG-02, MIG-03 |
| DEBUG | 45 | 10 | 15 | 10 | 20 | DBG-01, DBG-02, DBG-03 |
| NOTIF | 40 | 10 | 25 | 15 | 10 | NOT-01, NOT-02 |
| SYNC | 45 | 15 | 15 | 10 | 15 | SYNC-01, SYNC-02 |
| CLIENT | 35 | 25 | 15 | 10 | 15 | CLI-01, CLI-02 |
| STATE | 40 | 15 | 20 | 10 | 15 | STM-01, STM-02 |
| PERS | 40 | 25 | 10 | 10 | 15 | PER-01, PER-02 |
| REVIEW | 45 | 15 | 15 | 15 | 10 | CRV-01, CRV-02 |
| TEST | 25 | 15 | 5 | 10 | 45 | TST-01, TST-02 |

Per-task sections list the specific evaluation questions applied within each category.

### 2.6 Evidence Standard

Every submission must include the following evidence. Per-task sections list task-specific additions.

| Evidence | Requirement |
|---|---|
| **Reasoning** | A document explaining the diagnosis/decision path: what was investigated, which alternatives were considered and rejected, why the chosen approach was selected |
| **Architecture explanation** | Description of the resulting structure: component boundaries, ownership, dependency injection, and how it satisfies the relevant standards |
| **Modified subsystems** | Explicit inventory of every file created, modified, and deleted, grouped by subsystem, with a one-line purpose per file |
| **Testing evidence** | Commands run, results, and artifacts (test output, coverage reports) for every test executed |
| **Validation evidence** | Output of the automatic checks from Section 4 that were run, with pass/fail per check |

Evidence quality is assessed in G4. A submission whose claims cannot be verified from evidence scores 25 or below in Testing and Maintainability regardless of code quality.

### 2.7 Automatic vs Manual Validation

| Class | Means | Used for |
|---|---|---|
| **Automatic** | Commands, greps, static analysis, validators, build gates (Section 4) | Mechanical properties: syntax, counts, presence/absence of patterns, rule compliance |
| **Manual** | Human review of design, evidence, and edge-case reasoning | Quality properties: architecture soundness, evidence credibility, edge-case judgment |

A property is "automatic" only if the check is defined in Section 4 with an unambiguous pass criterion. Everything else is manual and must be recorded as such in the evaluation report.

---

## 3. Task Specifications

Each subsection defines, for one corpus task: Overview, Expected Outcomes, Success Criteria, Failure Conditions, Required Evidence, Scoring Rubric, Automatic Validation Opportunities, and Common Failure Modes.

---

### 3.1 ARC-01

**Extract Notification Layer from Monolithic Game.php**

#### Overview

| Attribute | Value |
|---|---|
| Task ID | ARC-01 |
| Category | Architecture |
| Difficulty / Effort | Hard / 4–6h |
| Affected subsystems | Game.php, Notifications, Server Architecture |
| Primary rules | NOTF-001 … NOTF-014, CORE-001, CORE-002, architecture.json |
| Key standards | `notification-patterns.md`, `project-architecture.md` |

#### Expected Outcomes

A centralized notification layer owns all notification construction and dispatch. Action handlers express state changes through typed notification method calls; Game.php contains no inline notification construction. Behavior — types, payloads, ordering, and recipients — is preserved exactly.

#### Success Criteria

| # | Criterion | Measurable requirement |
|---|---|---|
| 1 | All notification calls removed from Game.php | `grep -c 'notifyAllPlayers\|notifyPlayer' modules/php/Game.php` returns 0 |
| 2 | One method per notification type | Notifications class exposes exactly one method per notification type (38 types); no two methods emit the same type; no method is unused |
| 3 | Injectable without global state | The Notifications class is constructed with a Game reference and injected; no static state, no globals, no framework-side-channel lookup |
| 4 | Programmatic payload construction | No duplicated literal notification arrays across call sites; payload construction is parameterized in the class |
| 5 | No game logic in the layer | Notifications class contains no state mutation, no validation, no SQL, no `DbQuery`, no `Deck` calls |
| 6 | Identical payloads | For every type, the emitted payload deep-equals the pre-migration payload (gamelog diff or golden-record test) |
| 7 | Identical ordering and recipients | Per-action notification sequence and player targeting (all vs player) unchanged; `_private` handling preserved |

#### Failure Conditions

- C1 — notification methods called from Managers or from outside the action layer
- C1 — Notifications class performs framework calls other than notify dispatch
- C4 — any private notification becomes public, or public payloads gain hidden fields
- C5 — two notification paths coexist (partial migration with straggler calls)
- C6 — ordering relative to save/transition changes
- C8 — extraction leaves dead notification code, or Game.php still contains inline payload arrays
- Task-specific: any notification type dropped, renamed, or merged

#### Required Evidence

Reasoning (extraction boundary decisions, injection design); architecture explanation (class API, dependency direction); modified subsystems (Notifications class, Game.php deltas); testing evidence (payload parity run, playthrough gamelog diff); validation evidence (grep outputs, validator run).

#### Scoring Rubric (family ARCH: 30 / 35 / 10 / 15 / 10)

| Category | Evaluation questions |
|---|---|
| Correctness | All 38 types preserved with identical payloads? Ordering/recipients preserved? No regressions in any action that notifies? |
| Architecture | Clean boundary? No game logic in the layer? Injection without global state? No duplicated construction? |
| Framework Compliance | NOTF rules honored (centralization, absolute values, i18n, refresh patterns, no hidden info)? |
| Maintainability | Naming, parameterization, docblocks, absence of dead code? |
| Testing | Payload parity demonstrated mechanically? Reconnect ordering verified? |

#### Automatic Validation Opportunities

| Check | Method | Blocking |
|---|---|---|
| Zero notify calls in Game.php | `grep` on `modules/php/Game.php` | Yes |
| One method per type | Script comparing notification-type registry against method map | Yes |
| No SQL/framework calls in Notifications | `grep` for `DbQuery|Deck|globals|nextState` in Notifications class | Yes |
| Payload parity | Gamelog record/diff harness (pre vs post) | Yes |
| Build gate | B1–B3 | Yes |
| Rule compliance | `python -m tooling.validator` + NOTF checklist items (pre-review.json PR-005) | Non-blocking |

#### Common Failure Modes

- 1:1 mechanical wrap of call sites without de-duplicating payload construction (fails criteria 4 and 8)
- Notifications class built on a static singleton that leaks between tables
- Copying validation or resource arithmetic into the layer
- Renaming payload keys during extraction, breaking client handlers
- Missing `clienttranslate` wrappers, breaking i18n
- Leaving a straggler notification inline in one handler ("it was only one")

---

### 3.2 ARC-02

**Extract Manager Classes from Game.php**

#### Overview

| Attribute | Value |
|---|---|
| Task ID | ARC-02 |
| Category | Architecture |
| Difficulty / Effort | Hard / 6–8h |
| Affected subsystems | Game.php, Architecture, Domain Logic |
| Primary rules | ARCH-001 … , CORE-001 … CORE-004, persistence.json |
| Key standards | `domain-architecture.md`, `project-architecture.md`, `action-architecture.md` |

#### Expected Outcomes

Domain-specific Manager classes own discrete slices of the serialized state and all domain rules over them. Game.php becomes a thin coordinator: validate → delegate → save → notify. Game behavior is preserved identically.

#### Success Criteria

| # | Criterion | Measurable requirement |
|---|---|---|
| 1 | Minimum manager extraction | At least 4 domain Manager classes (target: Planet, Tech, Governor, Resource, Contract) |
| 2 | State slice ownership | Each Manager reads/writes only its aggregate; `grep` audit shows no cross-manager table/state writes |
| 3 | Thin action handlers | Game.php action handlers are validation + single delegation call, under the ACTN-001 line budget |
| 4 | No notifications from Managers | `grep` shows 0 `notifyAllPlayers\|notifyPlayer` in Manager classes; Managers return diffs or emit events consumed by Game.php |
| 5 | Isolated testability | Each Manager constructs with typed dependencies only; no framework base-class or Game.php method calls required |
| 6 | Behavior preserved | Full regression of all actions produces identical game state and gamelog |

#### Failure Conditions

- C1 — Managers call framework notification, deck, or state-transition APIs directly
- C4 — Manager `getAllDatas`-style accessors leak private state
- C5 — overlapping ownership (two Managers mutating the same slice)
- C7 — state slice splitting corrupts serialization (missing/duplicated fields)
- C8 — Game.php still contains domain logic after extraction
- Task-specific: fewer than 4 Managers, or a Manager that is a passive container with logic remaining in Game.php

#### Required Evidence

Reasoning (domain boundary identification, dependency design); architecture explanation (Manager APIs, ownership matrix); modified subsystems (Manager files, Game.php deltas); testing evidence (isolation test run, regression run); validation evidence (grep audits, validator).

#### Scoring Rubric (family ARCH)

| Category | Evaluation questions |
|---|---|
| Correctness | Identical behavior across all actions? State serialization round-trip stable? |
| Architecture | Ownership clear? Cross-manager coupling zero? Action handlers thin? Managers testable in isolation? |
| Framework Compliance | ARCH/CORE rules (Game.php orchestration only, actions validate→delegate→notify)? PERS rules for table access? |
| Maintainability | API naming, docblocks, aggregate boundaries documented? |
| Testing | Isolation tests prove Managers run without Game.php? |

#### Automatic Validation Opportunities

| Check | Method | Blocking |
|---|---|---|
| ≥4 Managers | File inventory | Yes |
| No notify in Managers | `grep` per Manager file | Yes |
| No cross-manager writes | Ownership matrix script against state keys | Yes |
| Action handler size | ACTN-001 line-count check | Yes |
| Build gate | B1–B3 | Yes |
| Rule compliance | Runtime validator + pre-commit checklist | Non-blocking |

#### Common Failure Modes

- Managers that accept the Game object and keep calling framework methods (isolation only nominal)
- Slicing state by file layout instead of domain aggregate, creating entangled getters
- Handlers that still mutate state before delegating ("fast path" shortcuts)
- Notification code moved into Managers with events bolted on afterward
- Changing serialization key names during the move, breaking `getAllDatas` and reconnect

---

### 3.3 ARC-03

**Implement Generic Board Interaction Framework**

#### Overview

| Attribute | Value |
|---|---|
| Task ID | ARC-03 |
| Category | Architecture |
| Difficulty / Effort | Medium / 3–5h |
| Affected subsystems | Game.js, Client Architecture, UI Framework |
| Primary rules | CLNT-001 … , animations.json, client.json |
| Key standards | `client-ui-architecture.md`, `client-synchronization-architecture.md`, `animation-architecture.md` |

#### Expected Outcomes

BEAM, TAP, and SELL_RESOURCE are first-class interaction modes in the existing `_modeRegistry` framework, supporting multi-step selection, server-driven target filtering, and complete cleanup on cancel, transition, or completion.

#### Success Criteria

| # | Criterion | Measurable requirement |
|---|---|---|
| 1 | Modes registered | BEAM, TAP, SELL_RESOURCE registered in `_modeRegistry`, each with a `discoverLegalTargets` function |
| 2 | Server-driven filtering | Each mode filters legal targets using server legal-action data (`getBeamActions`, `getTapActions`, sell actions); no client-computed legality |
| 3 | Multi-step selection | Source selection restricts destination candidates; phase transitions within one interaction work |
| 4 | Clean exit | Cancel, state transition, or completion removes all highlighting and event listeners (DOM inspection after each exit path) |
| 5 | No duplicated logic | State classes contain no parallel highlighting/selection code (grep audit for duplicated handlers) |
| 6 | BUY_RESOURCE unchanged | Existing mode behavior preserved; no regression in its flows |

#### Failure Conditions

- C3 — interaction state leaks across state transitions (stale mode active in a later state)
- C4 — legal-target projection reveals hidden information (e.g., face-down card selection targets)
- C5 — per-state interaction code duplicates framework machinery instead of registering modes
- C8 — highlighting logic duplicated across BEAM/TAP/SELL instead of parameterized
- Task-specific: cancel leaves orphaned DOM listeners; mode active during another player's turn

#### Required Evidence

Reasoning (mode lifecycle design); architecture explanation (registry API, mode object shape); modified subsystems (Game.js, any state classes); testing evidence (manual interaction script for all exit paths); validation evidence (grep audits, console-error sweep).

#### Scoring Rubric (family ARCH)

| Category | Evaluation questions |
|---|---|
| Correctness | All three modes functional end-to-end? Multi-step filtering correct? Cancel/transition cleanup verified? |
| Architecture | Modes extend the framework; no per-state duplication? |
| Framework Compliance | CLNT rules (handlers, DOM contracts); ANIM rules for highlighting timing? |
| Maintainability | Mode definitions declarative and small? |
| Testing | Exit-path coverage (cancel, transition, complete) demonstrated? |

#### Automatic Validation Opportunities

| Check | Method | Blocking |
|---|---|---|
| Modes registered | `grep` for mode names in registry | Yes |
| No Dojo-era duplication | `grep` for duplicated selection handlers in state classes | Yes |
| Cleanup on cancel | Playwright/DOM assertion script (no leftover listeners/highlights) | Yes |
| Build gate | B2 (JS syntax), B1 | Yes |
| Console clean | Browser console-error sweep | Non-blocking |

#### Common Failure Modes

- Implementing selection logic inside each state class and calling the framework only for show
- Filtering legal targets with client-side copies of legality rules (divergence from server)
- Forgetting to clear highlighting on `onLeavingState`, visible on reconnect
- Single-step modes forced through a multi-step API (or vice versa), breaking UX flow
- Registering modes with names that collide with server action names

---

### 3.4 MIG-01

**Migrate Legacy Action Handler to #[PossibleAction]**

#### Overview

| Attribute | Value |
|---|---|
| Task ID | MIG-01 |
| Category | Migration |
| Difficulty / Effort | Medium / 3–5h |
| Affected subsystems | mercurio.action.php, Game.php, States, Architecture |
| Primary rules | STAT-001 …, MIGR-001 …, actions.json, state-machine.json |
| Key standards | `game-flow-architecture.md`, `state-machine-architecture.md`, `action-architecture.md` |

#### Expected Outcomes

The legacy action handler file is eliminated. All 29 actions are declared via `#[PossibleAction]` attributes on state classes, dispatch through the framework, use typed parameters, and are authorized by state membership. All 29 actions behave identically.

#### Success Criteria

| # | Criterion | Measurable requirement |
|---|---|---|
| 1 | Legacy file eliminated | `mercurio.action.php` removed or emptied; no references remain in Game.php, states, or templates |
| 2 | State classes exist | `modules/php/States/` contains classes for all game states that host actions |
| 3 | Attribute coverage | All 29 actions have `#[PossibleAction]` on the correct state class (1:1 action→state mapping table verified) |
| 4 | Typed arguments | `grep -c 'getArg'` over migrated code returns 0; handlers use typed method parameters |
| 5 | State-membership authorization | An action invoked from any state other than its declaring state is rejected (dispatch test per action) |
| 6 | Behavior parity | All 29 actions produce identical state mutations and notifications |

#### Failure Conditions

- C1 — actions declared on the wrong state, or framework dispatch bypassed by leftover `action.php` routing
- C1 — zombie/`giveExtraTime` semantics lost during state-class creation
- C6 — argument extraction or validation ordering changes
- C8 — duplicate handler paths (framework-dispatched AND legacy-dispatched)
- Task-specific: `arg*` methods left orphaned; actions missing from the mapping; client `action` names changed

#### Required Evidence

Reasoning (action-to-state mapping decisions); architecture explanation (state class structure); modified subsystems (action.php removal, States/ creation, Game.php); testing evidence (per-action dispatch matrix); validation evidence (grep outputs).

#### Scoring Rubric (family MIGR)

| Category | Evaluation questions |
|---|---|
| Correctness | 29/29 actions identical in behavior? Authorization enforced? |
| Architecture | State classes well-formed; no dead code from removal? |
| Framework Compliance | Framework dispatch used exclusively; state class API honored; typed args per MIGR rules? |
| Maintainability | Legacy artifacts fully removed (no commented-out legacy code)? |
| Testing | Dispatch matrix evidence for every action? |

#### Automatic Validation Opportunities

| Check | Method | Blocking |
|---|---|---|
| action.php absent | File existence + reference `grep` | Yes |
| No `getArg(` | `grep` across `modules/php/` | Yes |
| 29 attributes mapped | Script comparing action list vs `#[PossibleAction]` declarations | Yes |
| Action→state mapping | Mapping table vs state class declarations | Yes |
| Build gate | B1, B3 | Yes |
| Framework load | Studio load smoke (states load without fatal) | Yes |

#### Common Failure Modes

- Leaving `action.php` in place but "unused" (dead routing surface, C8)
- Moving validation into state classes and out of the action pipeline
- Declaring actions on the wrong state because the state machine mapping was not re-read
- Renaming action methods or notification types during the move
- Forgetting `arg*` methods, breaking client `onEnteringState` args

---

### 3.5 MIG-02

**Migrate Dojo Client Module to ES Module**

#### Overview

| Attribute | Value |
|---|---|
| Task ID | MIG-02 |
| Category | Migration |
| Difficulty / Effort | Hard / 4–6h |
| Affected subsystems | mercurio.js, modules/js/Game.js, Client Architecture |
| Primary rules | CLNT-001 …, MIGR-001 …, client.json, migration.json |
| Key standards | `client-ui-architecture.md`, `project-architecture.md` |

#### Expected Outcomes

The client loads as a modern ES module: `export class Game extends GameGui`, no Dojo dependencies, no bridge file. All state classes, notification handlers, and renderers behave identically.

#### Success Criteria

| # | Criterion | Measurable requirement |
|---|---|---|
| 1 | ES module syntax | `modules/js/Game.js` uses `export class`; no `define(`/`declare(` |
| 2 | Bridge removed | Root `mercurio.js` deleted; no references to `bgagame.mercurio` global remain |
| 3 | No Dojo dependencies | `grep` for `dojo/`, `declare`, `ebg.core.gamegui`, `_base/` returns 0 across the client |
| 4 | Framework loads the game | Game loads in the modern framework (browser smoke: no module errors, game board renders) |
| 5 | Handler parity | All 38 notification handlers, state classes, and renderers function identically |
| 6 | No client regressions | Console clean; interaction, undo, and reconnect flows verified |

#### Failure Conditions

- C1 — ES module that still relies on Dojo globals at runtime (silent reliance on load order)
- C3 — module refactor breaks `setupPromiseNotifications` or state registration (client desync)
- C4 — refactor changes what data handlers write to the DOM
- C5 — a second class definition duplicated for "compatibility"
- C8 — bridge retained "just in case"
- Task-specific: `import` paths broken after file moves; `this` binding regressions from `declare` semantics removal

#### Required Evidence

Reasoning (module split decisions); architecture explanation (import graph); modified subsystems (Game.js, removed bridge, dependent files); testing evidence (browser smoke, handler parity run); validation evidence (grep outputs).

#### Scoring Rubric (family MIGR)

| Category | Evaluation questions |
|---|---|
| Correctness | Game loads and plays identically? All flows verified? |
| Architecture | Import graph clean, no circular imports? |
| Framework Compliance | Modern module pattern per CLNT/MIGR rules; no Dojo remnants? |
| Maintainability | Dead bridge removed; no commented legacy code? |
| Testing | Browser smoke and flow evidence? |

#### Automatic Validation Opportunities

| Check | Method | Blocking |
|---|---|---|
| No Dojo patterns | `grep -rE 'dojo/|declare\\(|gamegui|bgagame\\.' modules/js/` | Yes |
| Bridge gone | File existence + reference `grep` | Yes |
| ES module syntax | `node --check` (B2) | Yes |
| Import graph | Static import resolution script (no unresolved/circular) | Yes |
| Console clean | Browser console-error sweep | Non-blocking |

#### Common Failure Modes

- Mechanically replacing `define` with `import` and missing dependency-order assumptions
- Breaking `this` context: `declare` auto-binds differently than class methods
- Forgetting `setupNotifications`/`setupPromiseNotifications` registration
- Leaving Dojo shim files referenced from `.tpl` or framework config
- Importing Game.js from itself or creating a cycle when splitting helpers

---

### 3.6 MIG-03

**Migrate Legacy State Machine to State Classes**

#### Overview

| Attribute | Value |
|---|---|
| Task ID | MIG-03 |
| Category | Migration |
| Difficulty / Effort | Medium / 3–5h |
| Affected subsystems | states.inc.php, Game.php, material.inc.php, Architecture |
| Primary rules | STAT-001 …, MIGR-001 …, state-machine.json |
| Key standards | `state-machine-architecture.md`, `game-flow-architecture.md` |

#### Expected Outcomes

The state machine is defined by state classes under `modules/php/States/` instead of `states.inc.php`. Every state — server and client overlay — has a class; transitions, args, and action declarations are preserved.

#### Success Criteria

| # | Criterion | Measurable requirement |
|---|---|---|
| 1 | Legacy file replaced | `states.inc.php` removed; no `$machinestates` references remain |
| 2 | State classes complete | One class per state: gameSetup, playerTurn, resolvePendingObligation, endTurn, finalScoring, gameEnd, plus all 7 client overlay states (100–106) |
| 3 | Transitions preserved | Transition graph old→new mapped and verified identical (automated transition map diff) |
| 4 | Constants replaced | `material.inc.php` numeric state-ID constants replaced by class/enum references |
| 5 | Args relocated | Each state's `arg*` method lives in its state class |
| 6 | KNOWN_ISSUES BV-002 | The missing-state defect is resolved (all 7 client states defined) |

#### Failure Conditions

- C1 — state classes that don't extend the framework base or violate the state API
- C1 — transition names renamed, breaking `nextState()` strings in Game.php
- C2/C3 — client overlay states misdeclared, breaking client state registration
- C8 — both `states.inc.php` and classes coexist
- Task-specific: `arg*` methods duplicated between Game.php and state classes; zombie semantics lost

#### Required Evidence

Reasoning (state class design, transition mapping); architecture explanation (state class template); modified subsystems (States/, material.inc.php, Game.php); testing evidence (transition graph diff, state traversal playthrough); validation evidence (grep audits).

#### Scoring Rubric (family MIGR)

| Category | Evaluation questions |
|---|---|
| Correctness | Transition graph identical? Every state reachable and functional? |
| Architecture | State classes uniform, metadata-driven? |
| Framework Compliance | STAT rules (base class, attributes, args, zombie, giveExtraTime)? |
| Maintainability | Constants centralized; no legacy artifacts? |
| Testing | Transition map diff and traversal evidence? |

#### Automatic Validation Opportunities

| Check | Method | Blocking |
|---|---|---|
| states.inc.php absent | File existence + `$machinestates` grep | Yes |
| All states classed | Script comparing old state IDs vs classes | Yes |
| Transition parity | Transition-map diff script | Yes |
| No numeric literals | `grep` for state IDs in Game.php transitions | Non-blocking |
| Build gate | B1, B3 | Yes |
| Studio load | States load without fatal | Yes |

#### Common Failure Modes

- Transitions copied with renamed keys while Game.php still returns old transition strings (runtime crash)
- Client overlay states declared as server states (or omitted), breaking client dispatch
- `arg*` left in Game.php, causing double-definition or shadowing
- Hard-coding state IDs in Game.php despite constants existing
- Copying `$machinestates` structure into a single mega-class instead of one class per state

---

### 3.7 DBG-01

**Fix Notification-After-State-Transition Ordering**

#### Overview

| Attribute | Value |
|---|---|
| Task ID | DBG-01 |
| Category | Debugging |
| Difficulty / Effort | Medium / 2–3h |
| Affected subsystems | Game.php, Notifications, Synchronization, Reconnect |
| Primary rules | NOTF-011 (notification timing), SYNC-002, notifications.json |
| Key standards | `notification-patterns.md`, `client-synchronization-architecture.md` |

#### Expected Outcomes

In every action handler, state is saved before notifications, and all notifications are dispatched before `nextState()`. Reconnecting clients during any action receive both correct state and complete notification payloads. No handler retains the defect.

#### Success Criteria

| # | Criterion | Measurable requirement |
|---|---|---|
| 1 | Known handlers fixed | The 4 documented handlers (buyResource, research, beam, useProject) dispatch notifications before `nextState()` |
| 2 | Save-before-notify | In each handler, state persistence precedes notification dispatch |
| 3 | Full audit | A systematic audit of all notification sites (not only the 4 known) is performed and documented with 0 remaining violations |
| 4 | Rationale documented | Each fixed handler carries a comment explaining the ordering requirement |
| 5 | No regression | Non-reconnect play is byte-identical in behavior; gamelog ordering per action unchanged |

#### Failure Conditions

- C6 — any handler still transitions before notifying (audit finds a missed site)
- C3 — fix changes what a reconnecting client sees (getAllDatas vs notification divergence)
- C4 — payloads made "safe" by stripping fields instead of fixing ordering
- C8 — duplicate notification code introduced while "fixing" (see NOT-02)
- Task-specific: save moved after notifications (loses state for mid-notification reconnects)

#### Required Evidence

Reasoning (root-cause analysis, ordering contract); architecture explanation (notify/save/transition protocol); modified subsystems (Game.php, any helper extracted); testing evidence (reconnect simulation at each fixed site); validation evidence (audit grep, ordering script).

#### Scoring Rubric (family DEBUG)

| Category | Evaluation questions |
|---|---|
| Correctness | Ordering correct at every site; reconnect simulation passes at all 4 known sites and spot-checked others? |
| Architecture | Ordering enforced by structure (helper/centralization) or consistent comments? |
| Framework Compliance | NOTF-011 timing rule satisfied everywhere? |
| Maintainability | Rationale comments present and accurate? |
| Testing | Audit evidence + reconnect simulation? |

#### Automatic Validation Opportunities

| Check | Method | Blocking |
|---|---|---|
| Ordering audit | Script scanning handler bodies: notify calls vs `nextState` position | Yes |
| No remaining violations | Same script over full Game.php | Yes |
| Save-before-notify | Script scanning `saveState` vs first notify position | Yes |
| Build gate | B1, B3 | Yes |
| Reconnect simulation | Harness: cut connection at transition point, replay notifications, compare client state | Non-blocking (strong evidence when run) |

#### Common Failure Modes

- Fixing only the 4 named handlers and declaring the task done
- Moving `nextState` before notifications "because tests pass"
- Removing intermediate notifications instead of reordering them
- Reordering inside helpers but not at the call sites
- Assuming `getAllDatas` covers everything (not addressing the payload-loss mechanism)

---

### 3.8 DBG-02

**Implement stReplay / restoreReplay for Replay Support**

#### Overview

| Attribute | Value |
|---|---|
| Task ID | DBG-02 |
| Category | Debugging |
| Difficulty / Effort | Medium / 3–5h |
| Affected subsystems | Game.php, Replay, State Machine, Persistence |
| Primary rules | UNDO-005 … UNDO-014, TEST-005, undo-replay.json |
| Key standards | `game-flow-architecture.md`, `persistence-architecture.md` |

#### Expected Outcomes

Replay mode is functional: `stReplay`/`restoreReplay` restore state at any move; replay is deterministic (seeded RNG); games of varying length and action mix replay correctly; normal gameplay is unaffected.

#### Success Criteria

| # | Criterion | Measurable requirement |
|---|---|---|
| 1 | Framework hooks implemented | `stReplay()` and replay-restore logic exist and integrate with the framework replay lifecycle |
| 2 | State reconstruction | State is correctly restored for any replay step, including mid-action steps |
| 3 | Coverage | Replay verified for games of varying length and action combinations (explore, settle, research, beam, tap, etc.) |
| 4 | Determinism | RNG is seeded and the seed persisted so replay reproduces the original game |
| 5 | Edge cases | Mid-action replay, replay-to-end, and (if applicable) undo-during-replay handled |
| 6 | No regression | Normal gameplay byte-identical; no replay hooks leak into live play |

#### Failure Conditions

- C2 — replay produces different final state than original execution
- C2 — replay breaks at any point in the action log (incomplete reconstruction)
- C7 — replay path mutates live tables or loses notifications
- C4 — replay leaks hidden information into public logs
- Task-specific: blob-state approach retained without a reconstruction strategy; RNG unseeded

#### Required Evidence

Reasoning (state reconstruction strategy selection — move-log vs snapshot; documented evaluation of the blob limitation); architecture explanation (replay lifecycle, seed persistence); modified subsystems (Game.php, state machine, persistence); testing evidence (replay matrix: N moves × action mixes, state comparison); validation evidence (seed determinism run).

#### Scoring Rubric (family DEBUG)

| Category | Evaluation questions |
|---|---|
| Correctness | Replay correct from multiple points and game lengths? Final state identical? |
| Architecture | Reconstruction strategy coherent with the persistence model? |
| Framework Compliance | UNDO/REPLAY rules (cancel filtering, order, determinism)? |
| Maintainability | Replay code isolated from live-path logic? |
| Testing | Replay matrix evidence, seed determinism evidence? |

#### Automatic Validation Opportunities

| Check | Method | Blocking |
|---|---|---|
| Replay hooks exist | `grep` for `stReplay|restoreReplay` | Yes |
| Determinism | Seeded run ×2 → state hash comparison | Yes |
| Replay matrix | Scripted replay from multiple points | Yes |
| Build gate | B1, B3 | Yes |
| No non-deterministic calls | `grep -E 'time\\(|date\\(|rand\\('` in game logic | Non-blocking |

#### Common Failure Modes

- Implementing replay by re-running actions without restoring intermediate state (divergent replay)
- Seeding RNG but not persisting the seed (replay differs across servers)
- Using wall-clock values anywhere in resolution logic
- Restoring state after notifications fire (client sees stale board)
- Testing replay only from move 1, missing mid-game corruption

---

### 3.9 DBG-03

**Fix Silent Exception Swallowing in Tech Modifier Dispatch**

#### Overview

| Attribute | Value |
|---|---|
| Task ID | DBG-03 |
| Category | Debugging |
| Difficulty / Effort | Medium / 2–3h |
| Affected subsystems | Game.php, Technology System, Error Handling |
| Primary rules | TEST-004 …, testing.json, action-architecture (exception contract) |
| Key standards | `testing-debugging-architecture.md`, `domain-architecture.md` |

#### Expected Outcomes

Modifier dispatch never swallows handler exceptions: a handler either completes and returns a modified value or the exception propagates and the transaction rolls back. The 3 no-op handlers are implemented or removed. Failures are visible.

#### Success Criteria

| # | Criterion | Measurable requirement |
|---|---|---|
| 1 | No swallowing in `applyTechModifier` | No catch-all around handler invocation; exceptions propagate |
| 2 | No swallowing in `applyEventModifier` | Same, verified |
| 3 | Handler registry honest | All registered handlers are either correctly implemented or removed from the registry; no no-op entries |
| 4 | Visible failure | An erroring handler causes a user/system-visible failure, not silent continuation |
| 5 | End-to-end verification | A test or verification demonstrates modifier composition works end-to-end |

#### Failure Conditions

- C7 — handler exception leaves partial mutation (handler mutated `$player`/`$context` before throwing)
- C8 — catch-log-continue pattern reintroduced in any wrapper
- Task-specific: no-op handlers retained in the registry; behavior silently changes for techs that depended on the no-op
- Task-specific: fix changes modifier values for techs that previously "worked" via the swallow

#### Required Evidence

Reasoning (trace of the dispatch chain; decision per no-op handler: implement vs remove); architecture explanation (handler contract); modified subsystems (Game.php, registry, tests); testing evidence (exception-propagation test, composition test); validation evidence (registry audit).

#### Scoring Rubric (family DEBUG)

| Category | Evaluation questions |
|---|---|
| Correctness | Exceptions propagate; no-op handlers resolved; no behavior regressions for working techs? |
| Architecture | Handler contract explicit (return value or throw)? |
| Framework Compliance | Exception semantics per standards (UserException/SystemException)? |
| Maintainability | No catch-log-continue patterns remain? |
| Testing | Propagation test + end-to-end composition test? |

#### Automatic Validation Opportunities

| Check | Method | Blocking |
|---|---|---|
| No catch-all dispatch | `grep` for `catch (\\Throwable` in dispatch methods | Yes |
| No-op handler audit | Script listing registered handlers vs implemented bodies | Yes |
| Propagation test | Unit test with throwing handler | Yes |
| Build gate | B1, B3 | Yes |
| Composition test | `scripts/test-modifier-composition.php` equivalent passes | Non-blocking |

#### Common Failure Modes

- Removing the try/catch but leaving handlers that are expected to throw for control flow
- Deleting no-op handlers without checking which techs depend on them
- Re-wrapping in a different swallowing site ("the other dispatch")
- Fixing dispatch but leaving the partially-mutated-context hazard (validation of handler atomicity)
- "Fixing" by moving the swallow into the caller

---

### 3.10 NOT-01

**Migrate Deprecated notifyAllPlayers to Modern BGA API**

#### Overview

| Attribute | Value |
|---|---|
| Task ID | NOT-01 |
| Category | Notification |
| Difficulty / Effort | Medium / 2–4h |
| Affected subsystems | Game.php, Notifications, Framework Migration |
| Primary rules | NOTF-001 …, notifications.json |
| Key standards | `notification-patterns.md` |

#### Expected Outcomes

All 73 notification call sites use the modern `$this->bga->notify->all()` / `->player()` API with identical payloads and message strings. This task is strictly an API migration; no architectural extraction occurs (that is ARC-01).

#### Success Criteria

| # | Criterion | Measurable requirement |
|---|---|---|
| 1 | Zero deprecated calls | `grep -c 'notifyAllPlayers\|notifyPlayer'` over migrated code returns 0 |
| 2 | Full type coverage | All 38 notification types use the modern API |
| 3 | Payload parity | Payload structure identical before/after (gamelog diff or golden records) |
| 4 | Message parity | Message strings (client-side display) preserved exactly |
| 5 | Behavior parity | No behavioral changes beyond the API call; recipients and `_private` handling identical |

#### Failure Conditions

- C1 — mixing old and new APIs for the same type (inconsistent ordering semantics)
- C6 — argument-order mistakes (`type`/`message`/`payload` positional swap) change payloads
- C8 — migration performed by adding a wrapper that reintroduces the old signature
- Task-specific: `_private` semantics lost in the new API translation

#### Required Evidence

Reasoning (API mapping table old→new per type); architecture explanation (not applicable beyond mapping — document explicitly); modified subsystems (Game.php); testing evidence (parity diff run); validation evidence (grep outputs).

#### Scoring Rubric (family NOTIF)

| Category | Evaluation questions |
|---|---|
| Correctness | Payloads/messages identical across all 38 types? |
| Architecture | No architectural change introduced (scope discipline)? |
| Framework Compliance | Modern API used exclusively; NOTF rules preserved? |
| Maintainability | No duplicate wrapper layers added? |
| Testing | Parity evidence for all 73 sites? |

#### Automatic Validation Opportunities

| Check | Method | Blocking |
|---|---|---|
| Zero deprecated calls | `grep` | Yes |
| New API call count | `grep -c 'bga->notify->'` ≥ 73 | Yes |
| Payload parity | Gamelog diff harness | Yes |
| Build gate | B1, B3 | Yes |
| Type inventory | 38-type registry vs call sites | Non-blocking |

#### Common Failure Modes

- Swapping argument positions (`$this->bga->notify->all($type, $payload)` instead of `($payload, $type)`)
- Dropping the `$message` argument during migration (empty game log)
- Migrating in chunks and leaving deprecated calls behind
- Writing a thin `notifyAllPlayers()` wrapper (defeats the migration)
- Changing payload key names "for consistency" — breaking client handlers

---

### 3.11 NOT-02

**Consolidate Duplicated Notification Blocks**

#### Overview

| Attribute | Value |
|---|---|
| Task ID | NOT-02 |
| Category | Notification |
| Difficulty / Effort | Easy / 1–2h |
| Affected subsystems | Game.php, Notifications, Maintainability |
| Primary rules | NOTF-001, NOTF-003, notifications.json |
| Key standards | `notification-patterns.md` |

#### Expected Outcomes

Each duplicated notification pattern is emitted from exactly one helper. Call sites invoke helpers; no duplicated construction code remains; payloads are identical.

#### Success Criteria

| # | Criterion | Measurable requirement |
|---|---|---|
| 1 | `labOutputActivated` single source | One helper method; 4 call sites invoke it |
| 2 | Market milestone single source | One helper; buy and sell call sites |
| 3 | Synergy milestone single source | One helper; beam and tap call sites |
| 4 | `cardKept` single source | One helper; all 3 paths in `actChooseKeep` |
| 5 | Payload parity | Every consolidated payload identical to current behavior |
| 6 | No duplication remains | Audit shows no other duplicated notification construction blocks |

#### Failure Conditions

- C8 — helpers added but call sites left inline (or only some call sites consolidated)
- C6 — consolidation changes notification order within an action
- Task-specific: helper parameter mismatch produces different payloads for different call sites
- Task-specific: extraction merges two "identical" blocks that actually differed

#### Required Evidence

Reasoning (duplication identification method); architecture explanation (helper API); modified subsystems (Game.php); testing evidence (payload parity run); validation evidence (duplication scan).

#### Scoring Rubric (family NOTIF)

| Category | Evaluation questions |
|---|---|
| Correctness | All four patterns single-source with identical payloads? |
| Architecture | Helpers positioned correctly (no logic in helpers)? |
| Framework Compliance | NOTF rules preserved through extraction? |
| Maintainability | Duplication eliminated; helper naming clear? |
| Testing | Parity demonstrated for all consolidated sites? |

#### Automatic Validation Opportunities

| Check | Method | Blocking |
|---|---|---|
| Single-source check | Script: each consolidated type → exactly one sending method | Yes |
| Call-site count | `grep -c` per helper invocation | Yes |
| Parity | Gamelog diff harness | Yes |
| Duplication scan | Block-similarity scan of Game.php | Non-blocking |
| Build gate | B1, B3 | Yes |

#### Common Failure Modes

- Consolidating only the visible blocks and missing the fourth `cardKept` variant
- Two blocks that "look identical" but differ in one payload key — merged incorrectly
- Helper methods placed in Game.php with game logic creeping in
- Changing variable naming conventions inside payloads during extraction

---

### 3.12 SYNC-01

**Fix Reconnect State Inconsistency for Drawing Phase**

#### Overview

| Attribute | Value |
|---|---|
| Task ID | SYNC-01 |
| Category | Synchronization |
| Difficulty / Effort | Medium / 3–5h |
| Affected subsystems | Game.php, Game.js, Synchronization, Reconnect |
| Primary rules | SYNC-001 … SYNC-011, CLNT-006, synchronization.json |
| Key standards | `client-synchronization-architecture.md`, `notification-patterns.md` (reconnect sections) |

#### Expected Outcomes

A player reconnecting during `clientChooseKeep` sees the correct draw-selection UI and drawn cards, can complete selection, and leaves no stale state behind. Spectators see a pending-draw indicator without private data. The flow is reload-safe.

#### Success Criteria

| # | Criterion | Measurable requirement |
|---|---|---|
| 1 | Reconnect UI correct | Reconnect during `clientChooseKeep` initializes the keep-selection UI from `gamedatas`, not stale notification cache |
| 2 | Correct cards shown | The reconnecting player sees exactly the drawn cards |
| 3 | Completion after reconnect | Keep selection completes successfully post-reconnect (server accepts action, state advances) |
| 4 | Spectator view | Spectators reconnecting during drawing see at minimum a pending-draw indicator; no card identities leak |
| 5 | Cleanup | `drawingState` is cleaned up when the phase ends (no stale state on re-reconnect) |
| 6 | Pattern compliance | Verified against the reload-safe state pattern (`ops/04-patterns/reload-safe-state.md`) |

#### Failure Conditions

- C3 — reconnect shows stale or partial drawing state (board desync)
- C4 — drawn card identities reach spectators or non-drawing players
- C3 — completing selection after reconnect is rejected or double-applied
- C8 — reconnect handling added only to this state while the mechanism stays fragile elsewhere
- Task-specific: `onEnteringState` diverges between initial transition and `getAllDatas` reconstruction

#### Required Evidence

Reasoning (state lifecycle analysis across the reconnect boundary); architecture explanation (initialization source of truth); modified subsystems (Game.php, Game.js, state class); testing evidence (reconnect simulation: mid-state, complete-selection, re-reconnect); validation evidence (reload-safe checklist).

#### Scoring Rubric (family SYNC)

| Category | Evaluation questions |
|---|---|
| Correctness | All 4 reconnect scenarios correct? Cards correct, selection completes? |
| Architecture | Single initialization path (no divergence between transition args and gamedatas)? |
| Framework Compliance | SYNC rules (getAllDatas delegation, notification replay order, private-data rules)? |
| Maintainability | Cleanup centralized and documented? |
| Testing | Scenario matrix evidence? |

#### Automatic Validation Opportunities

| Check | Method | Blocking |
|---|---|---|
| drawingState cleanup | `grep` for cleanup on phase-end path | Yes |
| No private data in public args | Payload inspection of `argChooseKeep`/notifications | Yes |
| Reload-safe pattern | Checklist against `reload-safe-state.md` | Yes |
| Build gate | B1–B3 | Yes |
| Reconnect simulation | Harness: disconnect in `clientChooseKeep`, reload, compare DOM/state | Non-blocking (strong evidence when run) |

#### Common Failure Modes

- Initializing UI from notification cache on reconnect (works first time, stale on reconnect)
- Fixing only `clientChooseKeep` while leaving the underlying `onEnteringState` divergence pattern
- Sending drawn cards in a public notification to fix the spectator view (C4 leak)
- Forgetting to clear `drawingState`, so a second reconnect shows ghost cards
- Server-side args changing shape, breaking the client reconstruction path

---

### 3.13 SYNC-02

**Add Spectator State Projection**

#### Overview

| Attribute | Value |
|---|---|
| Task ID | SYNC-02 |
| Category | Synchronization |
| Difficulty / Effort | Hard / 4–6h |
| Affected subsystems | Game.php, Game.js, Spectator Mode, Synchronization |
| Primary rules | SYNC-001 …, SYNC-006 (spectator), synchronization.json |
| Key standards | `client-synchronization-architecture.md`, `notification-patterns.md` |

#### Expected Outcomes

Spectators can load and follow a game in progress. `getAllDatas()` projects spectator-appropriate state (including a spectator flag and safe drawing-phase handling). Refreshing restores the spectator view. The framework-level failure is diagnosed and addressed.

#### Success Criteria

| # | Criterion | Measurable requirement |
|---|---|---|
| 1 | Spectator load | A spectator can load and view a game in progress (framework-level failure resolved or documented workaround) |
| 2 | Live updates | Spectator UI updates correctly as the game progresses (notification path works for viewers) |
| 3 | Drawing-phase projection | Spectator projection shows pending-draw state without private card data |
| 4 | Refresh | Refreshing as spectator restores the view via `getAllDatas` projection |
| 5 | Both layers addressed | Framework-level and application-level issues both addressed; diagnosis documented |
| 6 | `viewerRole = null` handling | Client handles null viewer role gracefully (no crashes, correct generic UI) |

#### Failure Conditions

- C3 — spectator view diverges from actual board state
- C4 — any private data in spectator projection (drawing phase, hands, hidden choices)
- C1 — projection bypasses Manager `getAllDatas` boundaries (direct SQL in projection)
- Task-specific: spectator flag computed from player session rather than viewer context (wrong viewer treated as player)
- Task-specific: fix works for fresh spectators but breaks on refresh

#### Required Evidence

Reasoning (root-cause of framework failure; projection design); architecture explanation (viewer-role handling, projection structure); modified subsystems (Game.php, Game.js); testing evidence (spectator flow: join, observe turns, refresh); validation evidence (payload inspection for drawing phase).

#### Scoring Rubric (family SYNC)

| Category | Evaluation questions |
|---|---|
| Correctness | Spectator flow end-to-end; refresh correct; no leaks? |
| Architecture | Projection delegates to Managers; viewer-context clean? |
| Framework Compliance | SYNC spectator/private rules; NOTF `_private` semantics? |
| Maintainability | Diagnosis documented; no spectator-specific hacks in player paths? |
| Testing | Join/observe/refresh scenario evidence? |

#### Automatic Validation Opportunities

| Check | Method | Blocking |
|---|---|---|
| Spectator flag in projection | `grep` for `isSpectator`/viewer-role key in `getAllDatas` output | Yes |
| No private data in spectator args | Payload inspection of drawing-phase data | Yes |
| `viewerRole = null` guard | `grep` for null-handling in client setup | Yes |
| Build gate | B1–B3 | Yes |
| Spectator smoke | Harness: spectator session load + refresh | Non-blocking (strong evidence when run) |

#### Common Failure Modes

- Fixing only the client, leaving the "Game not found" framework failure unaddressed
- Projecting player boards via the player path and filtering on the client (leak surface)
- Treating `null` viewer as "everyone sees everything" (fine for open-info, fatal in drawing phase)
- Sending hand/drawn data in a broadcast "because spectators exist" (C4)
- Spectator refresh regressing because state is reconstructed only on initial load

---

### 3.14 CLI-01

**Implement Client-Side Undo UI Feedback**

#### Overview

| Attribute | Value |
|---|---|
| Task ID | CLI-01 |
| Category | Client |
| Difficulty / Effort | Medium / 2–3h |
| Affected subsystems | Game.js, Client UI, Undo System |
| Primary rules | ANIM-001 …, CLNT-001 …, animations.json, undo-replay.json |
| Key standards | `client-ui-architecture.md`, `animation-architecture.md` |

#### Expected Outcomes

Undo actions are visually distinct, confirmed before execution, and animate the affected elements back to their original positions. Server-authoritative undo logic is untouched. No external animation library is used.

#### Success Criteria

| # | Criterion | Measurable requirement |
|---|---|---|
| 1 | Distinct buttons | Undo buttons visually distinct from primary actions (styling class/icon) |
| 2 | Confirmation | BGA-standard confirmation dialog shown before undo executes; cancel path does nothing |
| 3 | Highlight | The element being undone (settled card, researched tech) is highlighted during the operation |
| 4 | Animation | The element animates to its original position; animation completes before the board state refresh |
| 5 | Handler wiring | `settleUndone` / `researchUndone` handlers trigger the animations |
| 6 | No regression | Undo functionality, legality, and rollback unchanged (server-side untouched) |
| 7 | Standards | BGA animation APIs only; no external animation library |

#### Failure Conditions

- C1 — confirmation/animations implemented server-side (state is client presentation only)
- C3 — animation leaves board desynced (animation and refresh race)
- C8 — undo logic duplicated client-side (client "previews" the rollback)
- Task-specific: confirm dialog on actions that should not confirm; cancel path fires the action
- Task-specific: animations break fast-mode / instant-speed preference

#### Required Evidence

Reasoning (UX flow design, animation sequencing); architecture explanation (button/state wiring); modified subsystems (Game.js, CSS); testing evidence (scripted undo flow, cancel path, fast-mode); validation evidence (console sweep).

#### Scoring Rubric (family CLIENT)

| Category | Evaluation questions |
|---|---|
| Correctness | All 5 UX requirements present; cancel path safe; undo parity? |
| Architecture | Presentation-only changes; server untouched? |
| Framework Compliance | ANIM/CLNT rules (queue, fast-mode, no external libs)? |
| Maintainability | CSS/handler organization; no dead styling? |
| Testing | Scripted scenarios: confirm, cancel, animate, fast-mode? |

#### Automatic Validation Opportunities

| Check | Method | Blocking |
|---|---|---|
| Server diff empty | Diff of server-side files | Yes |
| No external libs | `grep` for animation libraries in client | Yes |
| Confirm wiring | `grep` for dialog call on undo actions | Yes |
| Fast-mode handling | `grep` for speed preference in animation path | Non-blocking |
| Build gate | B2, B1 | Yes |
| Browser flow | Playwright: undo → confirm → animation → refresh | Non-blocking (strong evidence when run) |

#### Common Failure Modes

- Adding a confirm dialog that also fires on the cancel button (double-send)
- Animating from a stale position (element already re-rendered by refresh)
- Using `setTimeout` race instead of the animation queue, breaking fast-mode
- Styling undo buttons identically to primary actions "to keep visual consistency"
- Touching server-side undo validation "to make the animation easier"

---

### 3.15 CLI-02

**Extract Client Manager Modules from Monolithic Game.js**

#### Overview

| Attribute | Value |
|---|---|
| Task ID | CLI-02 |
| Category | Client |
| Difficulty / Effort | Hard / 5–7h |
| Affected subsystems | Game.js, Client Architecture, Module Structure |
| Primary rules | CLNT-001 …, MIGR-001 …, client.json, migration.json |
| Key standards | `client-ui-architecture.md`, `project-architecture.md` |

#### Expected Outcomes

Game.js is a thin coordinator; at least 4 manager modules own board zones, are clean ES modules with documented APIs, have no circular dependencies, and preserve behavior exactly.

#### Success Criteria

| # | Criterion | Measurable requirement |
|---|---|---|
| 1 | Minimum extraction | At least 4 manager modules extracted (target set: Planet, Tech, Governor, Resource, Contract + Notification/Interaction) |
| 2 | Thin coordinator | Game.js imports managers and delegates zone-specific work; size reduced toward ~2,500 lines from 6,061 |
| 3 | Clean modules | Each manager is a clean ES module: no `define`, no implicit globals, no `bgagame.` references |
| 4 | Documented API | Each manager exposes a well-defined, documented API surface |
| 5 | No circular imports | Import graph is acyclic (static check) |
| 6 | Behavior preserved | All flows, handlers, and renderers behave identically |

#### Failure Conditions

- C3 — extraction breaks state registration or notification handler discovery (client desync)
- C5 — manager modules depend on Game.js internals (import cycle or undocumented coupling)
- C8 — modules are thin slices of one file with shared mutable state passed everywhere
- Task-specific: managers reach into each other's DOM zones
- Task-specific: `this` binding broken for extracted render methods

#### Required Evidence

Reasoning (module boundary decisions, dependency design); architecture explanation (module API, import graph); modified subsystems (module files, Game.js deltas); testing evidence (flow regression run); validation evidence (import graph check, line-count report).

#### Scoring Rubric (family CLIENT)

| Category | Evaluation questions |
|---|---|
| Correctness | Behavior preserved across all flows? |
| Architecture | Clean boundaries, no cycles, documented APIs, thin Game.js? |
| Framework Compliance | CLNT/MIGR module rules; handler registration preserved? |
| Maintainability | Line-count reduction, naming, docblocks? |
| Testing | Regression evidence for extracted zones? |

#### Automatic Validation Opportunities

| Check | Method | Blocking |
|---|---|---|
| ≥4 modules | File inventory | Yes |
| Acyclic imports | Import-graph script | Yes |
| No Dojo patterns | `grep -rE 'define\\(|declare\\(' modules/js/` | Yes |
| No implicit globals | `grep` for undeclared `bgagame.` | Yes |
| Line count | `wc -l` on Game.js | Non-blocking (target ~2,500) |
| Build gate | B2 | Yes |

#### Common Failure Modes

- Extracting "modules" that all import Game.js and call `this.game.xxx` (no real decoupling)
- Sharing one mutable `gamedatas`/`state` object across modules without ownership rules
- Breaking `setupNotifications` auto-discovery by moving handlers off the class
- Circular imports between managers that render into each other's zones
- Splitting files but leaving all logic in Game.js (line count unchanged)

---

### 3.16 STM-01

**Implement ResolvePirateRaid Client State**

#### Overview

| Attribute | Value |
|---|---|
| Task ID | STM-01 |
| Category | State Machine |
| Difficulty / Effort | Medium / 2–4h |
| Affected subsystems | Game.php, Game.js, State Machine, Pirate Raid Mechanic |
| Primary rules | STAT-001 …, CLNT-006, state-machine.json, client.json |
| Key standards | `state-machine-architecture.md`, `client-synchronization-architecture.md` |

#### Expected Outcomes

The `ResolvePirateRaidState` client class is complete: entering highlights affected outputs and plays the raid flash, leaving cleans up, action buttons present resolution options, and reload restores the state. Server args are complete.

#### Success Criteria

| # | Criterion | Measurable requirement |
|---|---|---|
| 1 | UI functional | Resolution UI fully functional (affected planet and outputs shown, selection works) |
| 2 | Entry effects | Affected outputs highlighted on entering; `mc-pirate-raid-flash` animation plays |
| 3 | Resolution | Player selects and disables an output; action fires correctly |
| 4 | Cleanup | `onLeavingState`/cancel removes highlighting and animation classes (DOM verified) |
| 5 | Reload safety | Refreshing during raid resolution restores the state correctly |
| 6 | Server args complete | `argResolvePirateRaid()` returns complete args for the client |

#### Failure Conditions

- C3 — reload during raid resolution loses UI state or shows wrong options
- C4 — raid details in public args leak opponent information
- C5 — resolution implemented outside the registered state class (parallel path)
- C8 — animation classes left applied after leaving (stale DOM)
- Task-specific: action buttons generated without server legal-action data

#### Required Evidence

Reasoning (state lifecycle + reload design); architecture explanation (state class wiring, arg contract); modified subsystems (Game.js, Game.php args); testing evidence (entry/leave/cancel/reload scenarios); validation evidence (DOM class checks).

#### Scoring Rubric (family STATE)

| Category | Evaluation questions |
|---|---|
| Correctness | All 6 criteria met; selection/disable round-trip works? |
| Architecture | Implementation lives in the registered state class? |
| Framework Compliance | STAT/CLNT rules (state lifecycle, args, reload-safe pattern)? |
| Maintainability | Cleanup centralized; no CSS litter? |
| Testing | Scenario matrix incl. reload? |

#### Automatic Validation Opportunities

| Check | Method | Blocking |
|---|---|---|
| Server args complete | `argResolvePirateRaid` output schema check | Yes |
| Cleanup on leave | `grep` for class removal in `onLeavingState` | Yes |
| Registered state | `grep` for `ResolvePirateRaid` registration | Yes |
| Build gate | B1–B3 | Yes |
| Reload simulation | Harness: refresh mid-state, verify UI | Non-blocking |

#### Common Failure Modes

- Copy-pasting another state class and renaming (missing raid specifics)
- Animation applied on entry but never removed (stale class on reconnect)
- Buttons built from client-side assumptions instead of server legal actions
- Leaving `argResolvePirateRaid()` incomplete and hiding the gap in the client
- Handling reload in `setup` only, not in the state class

---

### 3.17 STM-02

**Fix Undefined Client State Transitions**

#### Overview

| Attribute | Value |
|---|---|
| Task ID | STM-02 |
| Category | State Machine |
| Difficulty / Effort | Easy / 1–2h |
| Affected subsystems | states.inc.php, State Machine, KNOWN_ISSUES |
| Primary rules | STAT-001 …, state-machine.json |
| Key standards | `state-machine-architecture.md` |

#### Expected Outcomes

All 7 client states (100–106) are defined with correct type, action, and transitions; every obligation kind routes to an existing state; dead transitions are removed; KNOWN_ISSUES BV-002 is resolved.

#### Success Criteria

| # | Criterion | Measurable requirement |
|---|---|---|
| 1 | All states defined | All 7 client states (100–106) present in the state machine with correct properties |
| 2 | Correct wiring | Each state has correct type, action, and transitions |
| 3 | Obligation routing | `planetInputs` obligation routes to `clientResolvePlanetInputs`; every obligation kind in `stResolvePendingObligation()` maps to a defined state |
| 4 | No dead transitions | No transitions reference undefined states; no orphaned state entries |
| 5 | BV-002 resolved | The KNOWN_ISSUES entry is resolved and the issue tracker updated |

#### Failure Conditions

- C1 — state definitions with wrong type (client vs server) or wrong action method names
- C1 — transition names that Game.php does not return (or returns but is undefined)
- C8 — dead entries left in the state machine "for safety"
- Task-specific: fixing the state machine without verifying `stResolvePendingObligation` routing logic

#### Required Evidence

Reasoning (audit methodology); architecture explanation (state definition template); modified subsystems (states.inc.php or state classes, KNOWN_ISSUES); testing evidence (routing table verification); validation evidence (cross-check script).

#### Scoring Rubric (family STATE)

| Category | Evaluation questions |
|---|---|
| Correctness | All states defined, all routes resolve, BV-002 resolved? |
| Architecture | Definitions consistent with routing code? |
| Framework Compliance | STAT rules for state definitions and transitions? |
| Maintainability | KNOWN_ISSUES updated; no dead entries? |
| Testing | Routing verification evidence? |

#### Automatic Validation Opportunities

| Check | Method | Blocking |
|---|---|---|
| 7 client states defined | Script: state IDs 100–106 present | Yes |
| Obligation↔state map | Script cross-checking `stResolvePendingObligation` kinds vs states | Yes |
| Dead transitions | Script: every transition target defined | Yes |
| Build gate | B1, B3 | Yes |
| BV-002 status | `grep` KNOWN_ISSUES for BV-002 closure | Non-blocking |

#### Common Failure Modes

- Adding the missing state with copy-pasted properties from an unrelated state
- Fixing the state machine but not the routing code (obligation kind still unhandled)
- Removing "dead" transitions that are actually reached dynamically
- Updating states.inc.php but leaving `material.inc.php` constants stale
- Not updating KNOWN_ISSUES, leaving the defect documented as open

---

### 3.18 PER-01

**Normalize State Blob into Structured Tables**

#### Overview

| Attribute | Value |
|---|---|
| Task ID | PER-01 |
| Category | Persistence |
| Difficulty / Effort | Hard / 6–8h |
| Affected subsystems | dbmodel.sql, Game.php, Persistence Architecture, Migration |
| Primary rules | PERS-001 …, persistence.json |
| Key standards | `persistence-architecture.md`, `domain-architecture.md` |

#### Expected Outcomes

The monolithic state blob is replaced by normalized tables; all operations use targeted SQL; `loadState()`/`saveState()` are removed; migration is verified; behavior is unchanged.

#### Success Criteria

| # | Criterion | Measurable requirement |
|---|---|---|
| 1 | Blob replaced | `mercurio_state` blob table replaced by normalized tables in `dbmodel.sql` |
| 2 | No full-state loads | No game operation loads the entire state; `grep` for blob reads returns 0 |
| 3 | Accessors removed | `loadState()` / `saveState()` removed (no call sites) |
| 4 | Normalization | Schema at least 3NF: no JSON blobs in relational tables, no duplicated columns across tables |
| 5 | Migration verified | Migration path for existing games (or verified with test data) demonstrated |
| 6 | No regression | All actions produce identical observable behavior |

#### Failure Conditions

- C7 — schema change loses data on migration (unmigrated rows, dropped columns)
- C7 — non-atomic multi-table updates on failure (partial mutation)
- C1 — schema violates BGA conventions (table naming, `player_id` keys, InnoDB/FK rules)
- C8 — normalization with an ORM-like layer that reintroduces a virtual blob
- Task-specific: `getAllDatas` still reconstructs a blob-shaped object (hidden blob)

#### Required Evidence

Reasoning (schema design, migration strategy, concurrency handling); architecture explanation (table model, CRUD ownership per Manager); modified subsystems (dbmodel.sql, Game.php, Managers); testing evidence (migration run, regression run, failure-injection for atomicity); validation evidence (schema checks).

#### Scoring Rubric (family PERS)

| Category | Evaluation questions |
|---|---|
| Correctness | All operations correct post-normalization; migration verified? |
| Architecture | Tables owned by Managers; CRUD boundaries clean; no hidden blob? |
| Framework Compliance | PERS rules (InnoDB, FK, atomic conditional updates, conventions)? |
| Maintainability | Schema documented; no dead migration code? |
| Testing | Migration + regression + atomicity evidence? |

#### Automatic Validation Opportunities

| Check | Method | Blocking |
|---|---|---|
| Blob table absent | `grep` dbmodel.sql for `mercurio_state`/blob column | Yes |
| No loadState/saveState | `grep` for both method names | Yes |
| No JSON columns | `grep` for `json` column types in dbmodel.sql | Yes |
| InnoDB/FK | Schema inspection script | Yes |
| Build gate | B1, B3 | Yes |
| Migration smoke | Scripted migration on fixture data | Non-blocking (strong evidence when run) |

#### Common Failure Modes

- Normalizing into tables but keeping the blob "for compatibility" (dual-write corruption)
- Table-per-entity with no aggregate ownership (Manager writes sprawl)
- `loadState`/`saveState` renamed and kept as `loadGameState`/`saveGameState`
- JSON columns used "temporarily" for fields that should be relational
- Migration script that drops data on failure instead of rolling back

---

### 3.19 PER-02

**Implement Game Statistics System**

#### Overview

| Attribute | Value |
|---|---|
| Task ID | PER-02 |
| Category | Persistence |
| Difficulty / Effort | Medium / 2–4h |
| Affected subsystems | Game.php, stats.jsonc, Persistence, Framework Configuration |
| Primary rules | PERS-010 …, persistence.json |
| Key standards | `persistence-architecture.md`, `game-flow-architecture.md` |

#### Expected Outcomes

A complete statistics system: ≥10 meaningful stats defined in `stats.jsonc`, initialized in `setupNewGame()`, updated at the correct points, correctly aggregated per player and per game, and displayed on the results page.

#### Success Criteria

| # | Criterion | Measurable requirement |
|---|---|---|
| 1 | Definitions | `stats.jsonc` contains ≥10 meaningful table and player stats (turns, actions by type, resources, techs, governors, contracts, scoring breakdown) |
| 2 | Initialization | Every defined stat is initialized in `setupNewGame()` |
| 3 | Update points | Stats updated at the correct point in each relevant action handler (counter increments, not mid-mutation) |
| 4 | Aggregation | Per-player and per-game aggregates tracked correctly |
| 5 | Display | Stats display on the BGA results page |
| 6 | No regression | No behavioral change from stat instrumentation |

#### Failure Conditions

- C7 — stat updates in the middle of mutations (non-atomic or lost on rollback)
- C1 — stats schema violates BGA conventions (`stats.jsonc` format, player vs table types)
- C8 — instrumentation duplicated across call sites instead of centralized
- Task-specific: stats defined but never initialized (framework error at setup)
- Task-specific: counters that increment on failed actions (validation not passed)

#### Required Evidence

Reasoning (stat taxonomy selection); architecture explanation (where updates live); modified subsystems (stats.jsonc, Game.php, Managers); testing evidence (stat accuracy run, results-page check); validation evidence (framework load).

#### Scoring Rubric (family PERS)

| Category | Evaluation questions |
|---|---|
| Correctness | Stats accurate; initialized; no regressions? |
| Architecture | Updates centralized at correct layer (Managers/actions)? |
| Framework Compliance | PERS/stats conventions; correct stat types? |
| Maintainability | Definitions documented; naming consistent? |
| Testing | Accuracy verification evidence? |

#### Automatic Validation Opportunities

| Check | Method | Blocking |
|---|---|---|
| ≥10 stats defined | `stats.jsonc` parse + count | Yes |
| All initialized | Script: defined IDs vs `setupNewGame` init calls | Yes |
| No mid-mutation updates | Review of update call positions | Yes |
| Build gate | B1, B3 | Yes |
| Framework load | Studio setup smoke | Non-blocking |

#### Common Failure Modes

- Defining stats but forgetting `setStat` initialization (framework error only at end-game)
- Updating stats before validation (failed actions counted)
- Duplicating counter logic in every handler instead of centralizing per Manager
- Using player stats for table-level facts or vice versa
- Guessing stat IDs that don't match `stats.jsonc` (silently dropped by the framework)

---

### 3.20 CRV-01

**Review Exception Handling Semantics**

#### Overview

| Attribute | Value |
|---|---|
| Task ID | CRV-01 |
| Category | Code Review |
| Difficulty / Effort | Easy / 1–2h |
| Affected subsystems | Game.php, Error Handling, BGA Best Practices |
| Primary rules | action-architecture exception contract (UserException vs SystemException) |
| Key standards | `action-architecture.md` (exception sections), `game-flow-architecture.md` |

#### Expected Outcomes

Every throw site is reviewed and categorized; misclassified exceptions are corrected; findings are documented with recommendations for ambiguous cases. Zero `VisibleSystemException` uses for semantic `UserException` cases.

#### Success Criteria

| # | Criterion | Measurable requirement |
|---|---|---|
| 1 | Complete review | Every `throw` statement in Game.php reviewed and categorized (correct / misclassified / ambiguous) |
| 2 | Fixes applied | All misclassified exceptions corrected to the semantically correct type |
| 3 | Documentation | Review summary documents findings — not just code fixes |
| 4 | Ambiguity noted | Ambiguous cases documented with recommendations |
| 5 | Zero visible-system misuse | 0 `VisibleSystemException` throws for player-validation semantics |

#### Failure Conditions

- C8 — fixes change user-visible error behavior (message text altered, popup suppressed)
- Task-specific: misclassified exceptions "fixed" by reclassifying the semantics to match the code
- Task-specific: review incomplete (throw sites missed); no documented findings
- C1 — behavior of the error contract violated (UserException where an internal error should surface)

#### Required Evidence

Reasoning (classification rubric application); architecture explanation (n/a — scope note); modified subsystems (Game.php, review doc); testing evidence (error-path smoke for changed sites); validation evidence (throw-site inventory).

#### Scoring Rubric (family REVIEW)

| Category | Evaluation questions |
|---|---|
| Correctness | Every site correctly classified and fixed? Error-path behavior preserved? |
| Architecture | Classification aligns with framework exception contract? |
| Framework Compliance | Exception semantics per standards? |
| Maintainability | Findings documented; code changes minimal and focused? |
| Testing | Smoke of changed error paths? |

#### Automatic Validation Opportunities

| Check | Method | Blocking |
|---|---|---|
| Throw-site inventory | `grep -c 'throw new'` vs review doc count | Yes |
| Zero misuse | `grep` for `VisibleSystemException` in validation paths | Yes |
| Message parity | Diff of user-facing strings | Yes |
| Build gate | B1, B3 | Yes |

#### Common Failure Modes

- Grep-only classification ("any throw inside an action is UserException")
- Rewriting messages while changing types (breaks translations and UX)
- Changing types to make lint pass instead of matching semantics
- Review doc that lists sites but gives no per-site classification
- "Fixing" by making everything SystemException to avoid log noise

---

### 3.21 CRV-02

**Review SQL Injection and Type Safety**

#### Overview

| Attribute | Value |
|---|---|
| Task ID | CRV-02 |
| Category | Code Review |
| Difficulty / Effort | Medium / 1–2h |
| Affected subsystems | Game.php, Persistence, Security, Type Safety |
| Primary rules | PERS-003 …, persistence.json, CORE-001 |
| Key standards | `persistence-architecture.md`, `action-architecture.md` |

#### Expected Outcomes

All `DbQuery` calls are parameterized or safely formatted; no raw interpolation remains; action handler parameters are type-hinted; findings are documented with before/after examples.

#### Success Criteria

| # | Criterion | Measurable requirement |
|---|---|---|
| 1 | Parameterized or safe | All `DbQuery` calls use parameterized queries or explicit `intval()` / `sprintf('%d')` formatting |
| 2 | No raw interpolation | `grep` for variable interpolation in SQL strings returns 0 |
| 3 | Type hints | All action handler parameters have proper type hints (no cast-only bodies) |
| 4 | Vulnerabilities fixed | Every identified SQL-injection risk fixed |
| 5 | Documentation | Review documents before/after examples per fix |

#### Failure Conditions

- C1 — casts that silently convert invalid input to 0 where an error should surface
- C7 — "fix" changes query semantics (e.g., `sprintf` of a string column)
- C4 — type hardening leaks internal error text to users
- Task-specific: only the two known lines fixed, no systematic audit
- Task-specific: parameterization introduced for some calls while others remain

#### Required Evidence

Reasoning (audit methodology, risk prioritization); architecture explanation (n/a — scope note); modified subsystems (Game.php, any helpers); testing evidence (query behavior smoke, invalid-input tests); validation evidence (interpolation grep, type-hint audit).

#### Scoring Rubric (family REVIEW)

| Category | Evaluation questions |
|---|---|
| Correctness | All queries safe; behavior identical for valid input; invalid input handled correctly? |
| Architecture | Query construction centralized (helpers/parameterization) where sensible? |
| Framework Compliance | PERS rules for queries and typing? |
| Maintainability | Before/after documentation; no dead casts left? |
| Testing | Invalid-input and valid-input smoke evidence? |

#### Automatic Validation Opportunities

| Check | Method | Blocking |
|---|---|---|
| No interpolation | `grep` for `$` inside `DbQuery` strings | Yes |
| All calls safe | Script categorizing each `DbQuery` call (parameterized/intval/sprintf/raw) | Yes |
| Type hints | Reflection or AST scan of action handlers | Yes |
| Build gate | B1, B3 | Yes |
| Invalid-input tests | Fuzz-lite: non-numeric args to numeric params | Non-blocking (strong evidence when run) |

#### Common Failure Modes

- Fixing the two documented lines and stopping (no full audit)
- Casting everything to `(int)` — masking invalid input as 0
- Replacing interpolation with `sprintf('%d')` on columns that are strings (semantic change)
- Adding parameterization helpers but leaving raw calls elsewhere
- Documenting findings without before/after examples

---

### 3.22 TST-01

**Write Server-Side Unit Tests for Tech Modifier Pipeline**

#### Overview

| Attribute | Value |
|---|---|
| Task ID | TST-01 |
| Category | Testing |
| Difficulty / Effort | Medium / 3–5h |
| Affected subsystems | Game.php, Technology System, Testing Infrastructure |
| Primary rules | TEST-001 … TEST-017, testing.json |
| Key standards | `testing-debugging-architecture.md`, `domain-architecture.md` |

#### Expected Outcomes

The modifier pipeline is extracted into a testable form; unit tests cover every modifier type, stacking, edge cases, and exception propagation; tests run standalone with one command.

#### Success Criteria

| # | Criterion | Measurable requirement |
|---|---|---|
| 1 | Framework set up | PHPUnit (or equivalent) configured; single command runs the suite |
| 2 | Coverage | ≥10 test cases covering all modifier types (cost discount, hand limit, explore draw, substitution, auto-satisfaction, 5 activate-instant techs) |
| 3 | Isolation | Tests run without the BGA framework (no database, no game instance) |
| 4 | Stacking | Multiple-modifier stacking tested (composition order and totals) |
| 5 | Propagation | Test verifies exceptions propagate (not swallowed) |
| 6 | No-op documentation | The 3 no-op handlers have tests documenting their current behavior (post-DBG-03: their resolution) |
| 7 | Edge cases | No-effect modifier, empty tech list, and boundary values tested |

#### Failure Conditions

- C1 — tests bootstrap the real framework (fragile, not standalone)
- C8 — production code contorted to satisfy tests (testability hacks)
- Task-specific: tests assert implementation internals instead of composition behavior
- Task-specific: suite passes but no assertion is meaningful (vacuous tests)
- Task-specific: tests cannot run with one command

#### Required Evidence

Reasoning (extraction boundary for testability); architecture explanation (test harness, dependency injection); modified subsystems (extracted class, tests, config); testing evidence (full suite run + coverage); validation evidence (command-line reproducibility).

#### Scoring Rubric (family TEST)

| Category | Evaluation questions |
|---|---|
| Correctness | Tests meaningful, edge cases covered, exceptions propagate? |
| Architecture | Extraction enables isolation without contorting production code? |
| Framework Compliance | TEST rules (seeded RNG, reproducibility)? |
| Maintainability | Test naming/organization; single command? |
| Testing | ≥10 cases, all modifier types, stacking, propagation, no-op coverage? |

#### Automatic Validation Opportunities

| Check | Method | Blocking |
|---|---|---|
| Single command | `phpunit` (or documented command) runs green | Yes |
| Test count | ≥10 modifier test cases | Yes |
| No framework bootstrap | `grep` for framework includes in test path | Yes |
| Propagation test | Test with throwing handler exists | Yes |
| Build gate | B1 | Yes |
| Coverage report | `--coverage` output ≥ stated thresholds | Non-blocking |

#### Common Failure Modes

- Writing tests against the original Game.php via partial mocks (test couples to framework)
- Asserting only "no exception" (vacuous)
- Testing each handler in isolation but never composition
- Skipping the 3 no-op handlers entirely
- Hard-coding expected values that mirror the implementation bug
- Tests that pass locally but require a database or fixtures not in the repo

---

### 3.23 TST-02

**Write Client-Side Notification Handler Tests**

#### Overview

| Attribute | Value |
|---|---|
| Task ID | TST-02 |
| Category | Testing |
| Difficulty / Effort | Medium / 3–5h |
| Affected subsystems | Game.js, Client Architecture, Notification System |
| Primary rules | TEST-009 …, TEST-011 …, testing.json, CLNT-006 |
| Key standards | `testing-debugging-architecture.md`, `notification-patterns.md` |

#### Expected Outcomes

A client test harness mocks `gamedatas`, renderers, and notification args; ≥5 notification handlers are tested across multiple scenarios; tests verify both `gamedatas` mutation and renderer calls; suite runs standalone with one command.

#### Success Criteria

| # | Criterion | Measurable requirement |
|---|---|---|
| 1 | Harness | Test harness with mock `gamedatas`, mock renderers, mock notification args |
| 2 | Handler coverage | ≥5 handlers tested (cardKept, resourcePurchased, techResearched, beamCompleted, governorInstalled), multiple scenarios each |
| 3 | State verification | Tests verify `gamedatas` mutation matches expected server state (not just renderer calls) |
| 4 | Renderer verification | Tests verify renderers are called with correct data |
| 5 | Edge cases | Different-player notification, non-viewer turn, empty payload scenarios covered |
| 6 | Isolation | Tests run without the BGA framework (mocked dependencies) |
| 7 | Single command | Tests run from the CLI with one command |

#### Failure Conditions

- C8 — harness mocks so much that handlers are untestable and tests are vacuous
- C4 — tests codify a handler that leaks hidden info (test documents the bug as expected)
- Task-specific: tests assert DOM strings instead of state updates (brittle)
- Task-specific: suite requires a browser or framework server to run
- Task-specific: renderers called with wrong data but tests pass (mock misconfiguration)

#### Required Evidence

Reasoning (harness design, mock boundaries); architecture explanation (harness API); modified subsystems (test files, harness, any extraction needed); testing evidence (suite run, per-handler scenario list); validation evidence (CLI reproducibility).

#### Scoring Rubric (family TEST)

| Category | Evaluation questions |
|---|---|
| Correctness | Assertions meaningful; gamedatas mutation verified; renderer args verified? |
| Architecture | Harness clean; extraction minimal and justified? |
| Framework Compliance | CLNT-006 handler registration respected by tests? |
| Maintainability | Scenario organization; single command? |
| Testing | ≥5 handlers × multiple scenarios + edge cases? |

#### Automatic Validation Opportunities

| Check | Method | Blocking |
|---|---|---|
| Single command | Documented test command runs green | Yes |
| Handler coverage | ≥5 handlers with ≥1 scenario each | Yes |
| Mutation assertions | `grep` for gamedatas assertions in tests | Yes |
| No framework dependency | Harness inspection (no BGA includes/imports) | Yes |
| Build gate | B2 | Yes |

#### Common Failure Modes

- Mocking renderers to `() => {}` and asserting nothing about their args
- Testing only the happy path for one player
- Harness that imports the real DOM and fails in CI
- Copying `gamedatas` expectations from the handler's own code (tautological)
- Testing "handler was called" without verifying the state it mutated

---

## 4. Automatic Validation Catalog

Global mechanical checks referenced by per-task sections. All are run against the **submission**, never against `bga-mercurio` itself.

| ID | Check | Command / Method | Pass criterion | Class |
|---|---|---|---|---|
| B1 | PHP syntax | `php -l` over all changed PHP files | 0 errors | Blocking |
| B2 | JS syntax | `node --check` over changed JS files (module-aware) | 0 errors | Blocking |
| B3 | JSON validity | JSON parse of all changed `.json`/`.jsonc`/`.sql` artifacts | 0 errors | Blocking |
| B4 | Artifact inventory | Diff of submission vs baseline | Only declared files changed | Blocking |
| V1 | Runtime validator | `python -m tooling.validator --rules bga-senior-engineer-skill/rules/ --validators schema,rule_id,ownership,priority,release` | PASS (crossref excluded per documented limitation) | Blocking |
| V2 | Rule compliance | Per-task rule checks (grep/script as listed in the task) | As specified per task | Blocking |
| V3 | Checklist compliance | `bga-senior-engineer-skill/checklists/*.json` items mapped to the task | All mapped items pass | Non-blocking |
| V4 | Notification registry | Script: every notification type has exactly one sender | 1:1 | Blocking (ARC-01, NOT-02) |
| V5 | Ordering audit | Script: in each handler, save → notify → transition | Order holds | Blocking (DBG-01) |
| V6 | Duplication scan | Block-similarity scan of Game.php / Game.js | No duplicated blocks | Non-blocking |
| V7 | Import graph | Static resolution of client module imports | Acyclic, resolved | Blocking (CLI-02, MIG-02) |
| V8 | Schema audit | InnoDB/FK/type inspection of `dbmodel.sql` | Per PERS rules | Blocking (PER-01) |
| V9 | Hidden-info scan | Payload/args inspection for private keys in public paths | No leaks | Blocking (SYNC-*, ARC-01) |
| V10 | Determinism | Seeded run twice → state hash comparison | Identical | Blocking (DBG-02) |

**Class semantics.** Blocking: failure rejects the submission (or, for B1–B3, stops evaluation). Non-blocking: failure is recorded as a Framework Compliance or Testing finding and cannot be offset by other categories.

---

## 5. Repository Safety Protocol

`bga-mercurio` is a READ-ONLY reference repository. It is never evaluated, modified, or extended by any task, by this specification, or by evaluation tooling.

**Prohibited operations:** file modification, file creation, file deletion, file renaming, staging, committing, index/metadata writes, and any git operation that alters repository state.

**Permitted operations:** read-only inspection (reading files, running read-only greps/analyses, `git log`/`git status`/`git diff` queries that do not write).

**Verification procedure (performed at G0 and at final verification):**

```
cd bga-mercurio
git status --porcelain        # must show no modified/untracked/deleted entries beyond pre-existing state
git diff --stat               # must be empty
git log -1 --oneline          # HEAD must be unchanged from the evaluation baseline
```

A baseline snapshot of `bga-mercurio` git state is recorded at evaluation start; any divergence at any gate is a critical failure (C9) for the submission.

---

## 6. Final Verification

For every evaluation session, the evaluator confirms and records:

1. No files in `bga-mercurio` were modified.
2. No files were created in `bga-mercurio`.
3. No git metadata changed in `bga-mercurio`.
4. All generated artifacts (submissions, evidence, reports, test results) exist only inside the `bga-senior-engineer` repository.

These four checks are recorded in the evaluation report alongside the verdict.

---

## Appendix A: Evidence Templates

**Reasoning document (required content):**
- Problem statement in the agent's own words
- Investigation performed (files read, hypotheses tested)
- Alternatives considered with rejection reasons
- Selected approach with justification mapped to standards/rules
- Risks and mitigations

**Architecture explanation (required content):**
- Component diagram (text) of the resulting structure
- Ownership matrix (component → state/zone/concern)
- Dependency direction statement
- How the design satisfies the referenced standards

**Modified subsystems inventory (required format):**

| File | Status (A/M/D) | Subsystem | Purpose |
|---|---|---|---|

**Testing evidence (required format):** command run, expected result, actual result, artifact path (test output, coverage file, script logs).

**Validation evidence (required format):** per check ID from Section 4 — command, output summary, pass/fail.

## Appendix B: Session Setup

1. Select tasks per the corpus Task Selection Guide (Appendix of `benchmark-task-corpus.md`).
2. Snapshot `bga-mercurio` git state (Section 5 baseline).
3. Provide the agent the task description, the read-only `bga-mercurio` checkout, this specification, and the skill package (`bga-senior-engineer-skill/`).
4. Run gates G0–G2 automatically; G3–G5 per the task's rubric.
5. Record the evaluation report: per-task gate results, category scores, verdict, evidence checklist, and Section 6 verification.

---

*End of evaluation specification. Version 1.0 is the canonical authority for evaluating benchmark-task-corpus.md tasks.*
