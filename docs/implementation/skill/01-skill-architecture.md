# BGA Senior Engineer Skill — Architecture & Implementation Plan

**Date:** 2026-07-30
**Status:** APPROVED
**Version:** 1.0.0
**Authority:** BGA Senior Engineer — Runtime Specification v1.1

---

## Table of Contents

1. [Repository Architecture Review](#1-repository-architecture-review)
2. [Runtime Assets Inventory](#2-runtime-assets-inventory)
3. [Skill Architecture Definition](#3-skill-architecture-definition)
4. [Execution Pipeline](#4-execution-pipeline)
5. [Component Boundaries](#5-component-boundaries)
6. [Extension Points](#6-extension-points)
7. [Implementation Roadmap](#7-implementation-roadmap)
8. [Dependency Map](#8-dependency-map)
9. [Risk Mitigation](#9-risk-mitigation)
10. [Definition of Done](#10-definition-of-done)

---

## 1. Repository Architecture Review

### 1.1 Top-Level Structure

```
bga-senior-engineer/
├── bga-senior-engineer-skill/        # Runtime Specification skill package
│   └── rules/                         # 12 JSON rule files (POPULATED — frozen v1.1)
├── docs/                              # All project documentation
│   ├── 00-documentation-architecture.md
│   ├── foundation/                    # Foundation reference documents
│   ├── standards/                     # Engineering standards
│   ├── ai-os/                         # Runtime Specification & operating docs
│   ├── patterns/                      # Reusable mechanic patterns
│   └── implementation/               # Implementation reports
├── tooling/                           # Python tooling platform
│   ├── _shared/                       # Shared infrastructure (released)
│   └── validator/                     # Runtime Validator v1.0 (released)
├── reference-projects/               # 4 BGA reference implementations
├── official-docs/                     # Raw BGA documentation HTML
├── .github/workflows/                # CI configuration
├── LICENSE (MIT)                      # Project license
└── .gitignore                         # Git ignore rules
```

### 1.2 Key Architectural Properties

| Property | Observation |
|---|---|
| **Language** | Python 3.10+ for tooling, PHP for reference projects |
| **Dependencies** | Zero external dependencies (stdlib only) |
| **Testing** | pytest, 346 tests passing across tooling platform |
| **Documentation maturity** | Foundation (3 docs), Standards (11 docs), AI/OS (13 docs), Patterns (1 doc) |
| **Runtime Specification** | Frozen v1.1 — 12 files, 227 rules, 4,831 lines |
| **Runtime Validator** | Released v1.0 — 7 validators, 99% coverage |

### 1.3 Documentation Architecture

The documentation follows a tiered maturity model:

| Tier | Content | Maturity |
|---|---|---|
| Tier 1 — Foundation | Official BGA docs, project analysis | Stable |
| Tier 2 — Standards | Engineering standards (11 files) | Stable |
| Tier 3 — Architecture | AI/OS docs, runtime spec | Stable |
| Tier 4 — Implementation | Validator report, skill plan | In progress |

### 1.4 Delimitation

The following are **stable infrastructure** and must not be modified:

- **Runtime Specification v1.1** — All 12 rule files in `bga-senior-engineer-skill/rules/`
- **Runtime Validator v1.0** — All code in `tooling/validator/` and `tooling/_shared/`
- **All architecture documents** in `docs/ai-os/` (doctrine, skill architecture, tooling architecture, rule partition plan, development roadmap)

The following are **authoritative source documents** for the skill implementation:

- `docs/ai-os/runtime-skill-architecture.md` — Frozen blueprint for all 41 skill artifacts
- `docs/ai-os/bga-senior-engineer-doctrine.md` — Operating doctrine for prompt design
- `docs/ai-os/rule-partition-plan.md` — Ownership boundaries for every rule domain
- `docs/ai-os/skill-development-roadmap.md` — Phase definitions and success criteria
- `docs/standards/*.md` — Engineering standards for rule and example derivation
- `docs/foundation/reference-project-analysis.md` — Reference project patterns
- `reference-projects/*/` — Canonical code for example extraction

---

## 2. Runtime Assets Inventory

### 2.1 Completed Assets (Frozen, No Changes)

| Asset | Location | Lines | Status |
|---|---|---|---|
| Constitution rules | `bga-senior-engineer-skill/rules/constitution.json` | 488 | Frozen |
| Architecture rules | `bga-senior-engineer-skill/rules/architecture.json` | 616 | Frozen |
| State machine rules | `bga-senior-engineer-skill/rules/state-machine.json` | 431 | Frozen |
| Actions rules | `bga-senior-engineer-skill/rules/actions.json` | 392 | Frozen |
| Persistence rules | `bga-senior-engineer-skill/rules/persistence.json` | 394 | Frozen |
| Notifications rules | `bga-senior-engineer-skill/rules/notifications.json` | 397 | Frozen |
| Client rules | `bga-senior-engineer-skill/rules/client.json` | 396 | Frozen |
| Synchronization rules | `bga-senior-engineer-skill/rules/synchronization.json` | 319 | Frozen |
| Undo/Replay rules | `bga-senior-engineer-skill/rules/undo-replay.json` | 255 | Frozen |
| Testing rules | `bga-senior-engineer-skill/rules/testing.json` | 319 | Frozen |
| Animations rules | `bga-senior-engineer-skill/rules/animations.json` | 256 | Frozen |
| Migration rules | `bga-senior-engineer-skill/rules/migration.json` | 337 | Frozen |
| **Total rules** | **12 files** | **4,831** | **Frozen** |

### 2.2 Planned Assets (To Be Implemented)

These are the 29 remaining artifacts defined by the frozen runtime skill architecture. Every field, schema, and constraint is defined in `docs/ai-os/runtime-skill-architecture.md`.

#### Tier 0 — Always-Loaded (1 file)

| Artifact | Path | Max Lines | Schema Source |
|---|---|---|---|
| Skill manifest | `bga-senior-engineer-skill/skill.json` | 50 | §5.1 |

#### Tier 1 — Task-Loaded (17 files)

| Artifact | Path | Max Lines | Schema Source |
|---|---|---|---|
| Master index | `bga-senior-engineer-skill/index.json` | 120 | §5.2 |
| Prompt: migrate-manager | `bga-senior-engineer-skill/prompts/migrate-manager.md` | 120 | §5.4 |
| Prompt: migrate-state | `bga-senior-engineer-skill/prompts/migrate-state.md` | 120 | §5.4 |
| Prompt: migrate-notifications | `bga-senior-engineer-skill/prompts/migrate-notifications.md` | 80 | §5.4 |
| Prompt: migrate-client | `bga-senior-engineer-skill/prompts/migrate-client.md` | 100 | §5.4 |
| Prompt: review-action | `bga-senior-engineer-skill/prompts/review-action.md` | 80 | §5.4 |
| Prompt: review-manager | `bga-senior-engineer-skill/prompts/review-manager.md` | 80 | §5.4 |
| Prompt: review-state-machine | `bga-senior-engineer-skill/prompts/review-state-machine.md` | 80 | §5.4 |
| Prompt: review-notifications | `bga-senior-engineer-skill/prompts/review-notifications.md` | 80 | §5.4 |
| Prompt: review-persistence | `bga-senior-engineer-skill/prompts/review-persistence.md` | 80 | §5.4 |
| Prompt: review-full | `bga-senior-engineer-skill/prompts/review-full.md` | 120 | §5.4 |
| Prompt: debug-session | `bga-senior-engineer-skill/prompts/debug-session.md` | 80 | §5.4 |
| Prompt: new-feature | `bga-senior-engineer-skill/prompts/new-feature.md` | 120 | §5.4 |
| Prompt: refactor-module | `bga-senior-engineer-skill/prompts/refactor-module.md` | 100 | §5.4 |
| Checklist: pre-commit | `bga-senior-engineer-skill/checklists/pre-commit.json` | 50 | §5.6 |
| Checklist: pre-review | `bga-senior-engineer-skill/checklists/pre-review.json` | 60 | §5.6 |
| Checklist: pre-release | `bga-senior-engineer-skill/checklists/pre-release.json` | 80 | §5.6 |

#### Tier 2 — Lazy-Loaded (10 files)

| Artifact | Path | Max Lines | Schema Source |
|---|---|---|---|
| Example: manager | `bga-senior-engineer-skill/examples/manager-example.json` | 60 | §5.5 |
| Example: action | `bga-senior-engineer-skill/examples/action-example.json` | 30 | §5.5 |
| Example: model | `bga-senior-engineer-skill/examples/model-example.json` | 30 | §5.5 |
| Example: notification | `bga-senior-engineer-skill/examples/notification-example.json` | 25 | §5.5 |
| Example: state | `bga-senior-engineer-skill/examples/state-example.json` | 30 | §5.5 |
| Example: client-manager | `bga-senior-engineer-skill/examples/client-manager-example.json` | 40 | §5.5 |
| Example: undo | `bga-senior-engineer-skill/examples/undo-example.json` | 40 | §5.5 |
| Reference: project matrix | `bga-senior-engineer-skill/references/reference-project-matrix.json` | 80 | §5.7 |
| Reference: anti-patterns | `bga-senior-engineer-skill/references/anti-patterns.json` | 100 | §5.7 |
| Reference: migration mapping | `bga-senior-engineer-skill/references/migration-mapping.json` | 60 | §5.7 |

#### Documentation (1 file)

| Artifact | Path | Max Lines | Schema Source |
|---|---|---|---|
| README | `bga-senior-engineer-skill/README.md` | 80 | §5.8 |

### 2.3 Aggregate Budget

| Component | Files | Lines | Tokens (est.) |
|---|---|---|---|
| `skill.json` | 1 | 50 | 150 |
| `index.json` | 1 | 120 | 360 |
| `rules/` (12 files) | 12 | 4,831 | ~13,810 |
| `prompts/` (13 files) | 13 | 1,300 | 3,900 |
| `checklists/` (3 files) | 3 | 200 | 600 |
| `examples/` (7 files) | 7 | 350 | 1,050 |
| `references/` (3 files) | 3 | 240 | 600 |
| `README.md` | 1 | 80 | 240 |
| **Total** | **41** | **~7,171** | **~20,710** |

**Note:** No single task loads all 12 rule files. The tiered loading model ensures per-task consumption stays under 3,000 tokens. The full package is never loaded in its entirety.

---

## 3. Skill Architecture Definition

### 3.1 Three-Tier Loading Model

The skill uses a progressive-disclosure loading model with three explicit tiers.

```
TIER 0 — ALWAYS-LOADED (Activation)
  Loaded when the skill is first activated
  Contains: skill.json only
  Size: ~150 tokens, 1 file

TIER 1 — TASK-LOADED (Execution)
  Loaded when the task type is identified
  Contains: index.json, prompt for task, relevant rules, checklist
  Size: ~2,500 tokens, 4–8 files

TIER 2 — LAZY-LOADED (Reference)
  Loaded only when explicitly referenced by a prompt or rule
  Contains: examples, references, supplementary rules
  Size: ~500 tokens per load, 1–3 files
```

### 3.2 Loading Sequence

```
Agent activates skill
    |
    v
[TIER 0] Load skill.json      → identity, version, entry point
    |
    v
Agent receives task
    |
    v
[TIER 1] Load index.json      → match task to artifact list
    |
    v
[TIER 1] Load task prompt     → step-by-step workflow
    |
    v
[TIER 1] Load required rules  → 2–8 domain rule files
    |
    v
[TIER 1] Load checklist       → self-validation gate
    |
    v
[TIER 2] Lazy-load examples   → only if prompt explicitly requests
    |
    v
Agent executes task → runs checklist → PASS/FAIL
```

### 3.3 Task Classification

Task matching is deterministic: keyword substring matching against `index.json` task entries. The agent:

1. Loads `index.json`
2. Compares user request against each task's `keywords[]`
3. Selects the task with the highest match count
4. If tie, selects the first matching entry (index order is intentional)
5. If no match, falls back to `review-full` (most comprehensive prompt)

### 3.4 Rule Loading Rules

- Rules are **never searched**. They are explicitly listed in the index entry for each task.
- The agent loads exactly the files in `task.rules[]`, no more, no less.
- If a task needs a rule outside its entry, the prompt explicitly requests a lazy-load.
- `constitution.json` is always loaded (every task depends on constitutional laws).

### 3.5 Artifact Format

| Artifact Type | Format | Why |
|---|---|---|
| `skill.json` | JSON | Platform auto-discovery, structured metadata |
| `index.json` | JSON | Deterministic parsing, agent can navigate by key |
| `rules/*.json` | JSON | Structured rule retrieval, machine-verifiable |
| `prompts/*.md` | Markdown + YAML frontmatter | Natural language instructions, structured metadata |
| `examples/*.json` | JSON | Code with annotations, structured reference |
| `checklists/*.json` | JSON | Binary pass/fail items with fix instructions |
| `references/*.json` | JSON | Lookup tables with structured entries |
| `README.md` | Markdown | Human-readable integration docs |

### 3.6 Metadata Standard

Every artifact (except README.md) carries:
- `version` (semantic, MAJOR.MINOR.PATCH)
- `last_updated` (ISO 8601, YYYY-MM-DD)
- `source` (path to source documentation)

Artifact-specific metadata fields are defined in `docs/ai-os/runtime-skill-architecture.md §6.2`.

---

## 4. Execution Pipeline

### 4.1 End-to-End Task Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                        AGENT SESSION                            │
│                                                                 │
│  ┌──────────────────┐                                           │
│  │  1. ACTIVATION    │  Load skill.json                          │
│  │  (always)         │  Platform injects Tier 0 into context     │
│  └────────┬─────────┘                                           │
│           │                                                      │
│           v                                                      │
│  ┌──────────────────┐                                           │
│  │  2. TASK INPUT    │  User sends request                       │
│  │                   │  e.g. "Extract the ResourceManager"       │
│  └────────┬─────────┘                                           │
│           │                                                      │
│           v                                                      │
│  ┌──────────────────┐                                           │
│  │  3. CLASSIFY      │  Load index.json                          │
│  │                   │  Match keywords → select task entry       │
│  │                   │  Identify prompt, rules, checklist paths  │
│  └────────┬─────────┘                                           │
│           │                                                      │
│           v                                                      │
│  ┌──────────────────┐                                           │
│  │  4. LOAD          │  Load Tier 1 artifacts:                   │
│  │                   │  - Prompt file (.md)                      │
│  │                   │  - Rule files (2–8 .json)                │
│  │                   │  - Checklist file (.json)                 │
│  └────────┬─────────┘                                           │
│           │                                                      │
│           v                                                      │
│  ┌──────────────────┐                                           │
│  │  5. LAZY-LOAD     │  If prompt requests examples:             │
│  │  (optional)       │  Load Tier 2 artifacts optionally         │
│  └────────┬─────────┘                                           │
│           │                                                      │
│           v                                                      │
│  ┌──────────────────┐                                           │
│  │  6. EXECUTE       │  Apply prompt + rules to codebase         │
│  │                   │  Produce output (code, review, fix)       │
│  └────────┬─────────┘                                           │
│           │                                                      │
│           v                                                      │
│  ┌──────────────────┐                                           │
│  │  7. SELF-VALIDATE │  Run checklist items against output       │
│  │                   │  PASS → declare complete                  │
│  │                   │  FAIL → fix and re-check                  │
│  └──────────────────┘                                           │
└─────────────────────────────────────────────────────────────────┘
```

### 4.2 Token Budget by Phase

| Phase | Files | Tokens | Budget OK? |
|---|---|---|---|
| Activation (Tier 0) | 1 | ~150 | Yes |
| Classification (Tier 1) | 1 | ~360 | Yes |
| Migrate Manager | 7 | ~2,340 | Yes (margin 660) |
| Bug Fix (Undo) | 8 | ~1,800 | Yes |
| Code Review | 6 | ~1,650 | Yes |
| New Feature (phased) | 4–6 | ~900–2,000 | Yes |
| Full Review (phased) | 3–5 | ~600–1,800 | Yes |

### 4.3 Phased Execution for Large Tasks

For tasks that would exceed the 3,000-token single-task budget (new-feature, review-full), execution is divided into phases:

```
PHASE 1: Design
  Load: constitution + architecture + state-machine
  Deliverable: domain boundary, DB schema, state flow

PHASE 2: Implementation
  Load: persistence + actions + undo-replay
  Deliverable: Manager, Actions, undo wiring

PHASE 3: Integration
  Load: notifications + client
  Deliverable: notifications, client handlers, animations
```

Each phase stays under 2,000 tokens. The agent signals phase completion before proceeding to the next.

### 4.4 Self-Validation Pipeline

After producing output, the agent runs the loaded checklist:

1. Load checklist items from `checklists/*.json`
2. For each item:
   - Check condition against output
   - If PASS: continue
   - If FAIL: apply fix instruction, re-check
3. All items PASS → declare task complete
4. Any item FAIL after fix attempt → escalate to user

---

## 5. Component Boundaries

### 5.1 Artifact Ownership Domains

Each artifact type owns a distinct concern:

| Artifact | Owns | Does NOT Own |
|---|---|---|
| `skill.json` | Identity, version, capability declaration | Rules, prompts, content |
| `index.json` | Task-to-artifact mapping, keyword classification | Rule content, prompt content |
| `rules/*.json` | Domain-specific engineering rules | Prose explanations, task workflows |
| `prompts/*.md` | Task execution instructions, workflow steps | Rule content (references by ID only) |
| `examples/*.json` | Canonical code patterns with annotations | Task instructions, rules |
| `checklists/*.json` | Self-validation items, pass/fail conditions | Prose explanations |
| `references/*.json` | Supplementary lookup data | Rules, instructions |

### 5.2 Rule Domain Boundaries

Each rule file owns exactly one domain, as defined by the rule partition plan:

| File | Owns | Boundary |
|---|---|---|
| `constitution.json` | Immutable laws, engineering constitution | Priority 1 only. No implementation detail. |
| `architecture.json` | Component boundaries, ownership, layering | Ends where state design, action structure begins |
| `state-machine.json` | State class design, transitions, args, zombie | Ends where action delegation, notification design begins |
| `actions.json` | Action handler structure, validation layers | Ends where Manager internals, notification payload begins |
| `persistence.json` | DB schema, queries, globals, data-driven config | Ends where undo logging, notification data begins |
| `notifications.json` | Notification design, public/private, i18n | Ends where client handler wiring begins |
| `client.json` | Client architecture, Manager pattern, BgaCards | Ends where animation mechanics, reconnect begins |
| `synchronization.json` | Reconnect, spectator, getAllDatas, refreshUI | Ends where notification payload, undo mechanics begins |
| `undo-replay.json` | Undo safety, log tables, checkpoints, replay | Ends where DB transaction boundaries begin |
| `animations.json` | Animation queue, fast-mode, BgaAnimations | Ends where notification handler integration begins |
| `testing.json` | Test strategy, coverage, PHPUnit, replay tests | Ends where implementation guidance begins |
| `migration.json` | Legacy-to-modern extraction order, safety | Sequential dependency on all other domains |

### 5.3 Prompt Boundaries

Each prompt maps to exactly one task type. Prompts do not overlap:

| Prompt | Task | Rule Files Loaded | Tier 2 References |
|---|---|---|---|
| `migrate-manager.md` | Extract Manager from Game.php | constitution, architecture, persistence, migration | manager-example, model-example |
| `migrate-state.md` | Convert states.inc.php to State classes | constitution, state-machine, migration | state-example |
| `migrate-notifications.md` | Extract Notifications class | constitution, notifications, migration | notification-example |
| `migrate-client.md` | Convert Dojo to ES modules + BgaCards | constitution, client, migration | client-manager-example |
| `review-action.md` | Review single action handler | constitution, actions, undo-replay | action-example |
| `review-manager.md` | Review Manager class | constitution, architecture, persistence | manager-example |
| `review-state-machine.md` | Review state machine | constitution, state-machine | state-example |
| `review-notifications.md` | Audit notification system | constitution, notifications, synchronization | notification-example |
| `review-persistence.md` | Audit DB schema + globals | constitution, persistence, undo-replay | (none) |
| `review-full.md` | Full pre-release audit | all (phased) | all (phased) |
| `debug-session.md` | Systematic debugging | constitution | (none) |
| `new-feature.md` | Add new feature end-to-end | all (phased) | all (phased) |
| `refactor-module.md` | Refactor to canonical standards | constitution, architecture | manager-example |

### 5.4 Checklist Boundaries

| Checklist | Scope | Items | When |
|---|---|---|---|
| `pre-commit.json` | Layer correctness, anti-pattern introduction | Max 10 | Before writing any file |
| `pre-review.json` | Task domain compliance | Max 15 | Before declaring task complete |
| `pre-release.json` | Full BGA Studio release requirements | Max 20 | Before declaring release ready |

### 5.5 Dependency Hierarchy

```
skill.json (Tier 0)
    │
    v
index.json (Tier 1 gateway)
    │
    ├──→ prompts/*.md        (reference rules/ by ID)
    │         │
    │         ├──→ rules/*.json    (loaded in index-defined groups)
    │         │
    │         ├──→ checklists/*.json  (loaded after execution)
    │         │
    │         └──→ examples/*.json    (lazy-load, Tier 2)
    │
    ├──→ references/*.json   (lazy-load, Tier 2)
    │
    └──→ README.md           (human integration docs, not agent-loaded)
```

---

## 6. Extension Points

### 6.1 Adding a New Task

**Files to create:** 1 (`prompts/<new-task>.md`)
**Files to modify:** 1 (`index.json` — add task entry)
**Impact:** Zero changes to existing prompts, rules, or checklists.

### 6.2 Adding a New Rule to an Existing Domain

**Files to modify:** 1 (`rules/<domain>.json` — add rule with next available ID)
**Impact:** Zero changes to other rule files. Prompts may optionally reference the new ID.

### 6.3 Adding a New Rule Domain

**Files to create:** 1 (`rules/<new-domain>.json`)
**Files to modify:** 1 (`index.json` — add to relevant task entries)
**Impact:** Zero changes to existing rule files. Adds new ID prefix.

### 6.4 Adding a New Example

**Files to create:** 1 (`examples/<name>.json`)
**Files to modify:** 1 (`index.json` — add to relevant task entries)
**Impact:** Zero changes to prompts or rules.

### 6.5 Adding a New Checklist

**Files to create:** 1 (`checklists/<name>.json`)
**Files to modify:** 1 (`index.json` — add to relevant task entries)
**Impact:** Zero changes to prompts or rules.

### 6.6 Adding a New Reference

**Files to create:** 1 (`references/<name>.json`)
**Files to modify:** 1 (`index.json` — add to relevant task entries)
**Impact:** Zero changes to prompts or rules.

### 6.7 Deprecating an Artifact

**Process:**
1. Add `"deprecated": true` and `"replaced_by": "<path>"` to the artifact
2. Keep for one MINOR version cycle
3. Remove in next MAJOR version
4. Update `index.json` to point to replacement

### 6.8 Extensibility Principles

| Principle | Enforcement |
|---|---|
| Additive, not destructive | New artifacts never require deleting existing ones |
| Backward compatible | Existing index entries and prompt references continue to work |
| Self-documenting | New artifacts carry all required metadata |
| Isolated | A new artifact in one directory does not affect other directories |
| Versioned independently | Each artifact tracks its own version |

---

## 7. Implementation Roadmap

### 7.1 Milestone Structure

Each milestone follows the pattern established by the Runtime Validator:

1. **Design** — Define the specific artifacts and their content
2. **Implement** — Create all files for the milestone
3. **Validate** — Run the Runtime Validator, check token budgets, verify cross-references
4. **Release** — Tag milestone, update CHANGELOG, write implementation report

### 7.2 Milestone Sequence

```
Milestone 1: Architecture (THIS DOCUMENT)
├── Repository architecture review
├── Runtime assets inventory
├── Skill architecture definition
├── Execution pipeline definition
├── Component boundary definition
├── Extension point definition
└── Implementation roadmap

Milestone 2: Manifest & Index
├── bga-senior-engineer-skill/skill.json              (50 lines, Tier 0 manifest)
├── bga-senior-engineer-skill/index.json              (120 lines, Tier 1 task map)
└── bga-senior-engineer-skill/README.md               (80 lines, integration docs)

Milestone 3: Migration Prompts (4 files)
├── bga-senior-engineer-skill/prompts/migrate-manager.md
├── bga-senior-engineer-skill/prompts/migrate-state.md
├── bga-senior-engineer-skill/prompts/migrate-notifications.md
└── bga-senior-engineer-skill/prompts/migrate-client.md

Milestone 4: Review Prompts (5 files)
├── bga-senior-engineer-skill/prompts/review-action.md
├── bga-senior-engineer-skill/prompts/review-manager.md
├── bga-senior-engineer-skill/prompts/review-state-machine.md
├── bga-senior-engineer-skill/prompts/review-notifications.md
└── bga-senior-engineer-skill/prompts/review-persistence.md

Milestone 5: Special Prompts (4 files)
├── bga-senior-engineer-skill/prompts/review-full.md
├── bga-senior-engineer-skill/prompts/debug-session.md
├── bga-senior-engineer-skill/prompts/new-feature.md
└── bga-senior-engineer-skill/prompts/refactor-module.md

Milestone 6: Examples (7 files)
├── bga-senior-engineer-skill/examples/manager-example.json
├── bga-senior-engineer-skill/examples/action-example.json
├── bga-senior-engineer-skill/examples/model-example.json
├── bga-senior-engineer-skill/examples/notification-example.json
├── bga-senior-engineer-skill/examples/state-example.json
├── bga-senior-engineer-skill/examples/client-manager-example.json
└── bga-senior-engineer-skill/examples/undo-example.json

Milestone 7: References (3 files)
├── bga-senior-engineer-skill/references/reference-project-matrix.json
├── bga-senior-engineer-skill/references/anti-patterns.json
└── bga-senior-engineer-skill/references/migration-mapping.json

Milestone 8: Checklists (3 files)
├── bga-senior-engineer-skill/checklists/pre-commit.json
├── bga-senior-engineer-skill/checklists/pre-review.json
└── bga-senior-engineer-skill/checklists/pre-release.json

Milestone 9: Validation & Release
├── Token budget verification (all 13 task scenarios)
├── Cross-reference validation (all rule IDs resolvable)
├── Consistency audit (zero conflicting guidance)
├── Self-validation smoke test (intentional errors caught)
├── CHANGELOG.md
└── Release notes
```

### 7.3 Dependency Order

```
Milestone 1: Architecture
    │
    v
Milestone 2: Manifest & Index  ← Foundation. Everything references these.
    │
    ├──────────────────────────────────────────────┐
    │                                              │
    v                                              v
Milestone 3: Migration Prompts         Milestone 6: Examples
    │                                              │
    v                                              │
Milestone 4: Review Prompts                       │
    │                                              │
    v                                              │
Milestone 5: Special Prompts                      │
    │                                              │
    ├──────────────────────────────────────────────┘
    │
    v
Milestone 7: References
    │
    v
Milestone 8: Checklists
    │
    v
Milestone 9: Validation & Release
```

**Parallel work possible:**
- Milestones 3–5 (prompts) can run in parallel with Milestone 6 (examples) — both depend on Milestone 2 but not on each other
- Milestone 7 (references) can run in parallel with Milestone 8 (checklists) — both depend only on Milestone 2
- Within each milestone, individual files can be created in parallel

### 7.4 Per-Milestone Deliverable Template

Each milestone implementation report follows the Runtime Validator pattern:

```
## <Milestone Name>
**Date:** YYYY-MM-DD
**Status:** RELEASED

### Files Created
| File | Lines | Purpose |

### Files Modified
| File | Change |

### Validation Results
| Criterion | Status | Detail |

### Token Budget Verification
| Scenario | Loaded Files | Tokens | Budget OK? |

### Test Summary
(if applicable)

### Known Limitations

### Follow-Up Recommendations
```

### 7.5 Validation Gates

| Gate | When | Method |
|---|---|---|
| Per-file validation | After each file | Runtime Validator (schema, rule_id, ownership, priority) |
| Cross-reference check | After each milestone | Runtime Validator (crossref — expected findings noted) |
| Token budget check | After each milestone | Count lines × 3 (estimated tokens), verify under 3K per task |
| Consistency audit | After Milestone 8 | Cross-file scan for contradictory guidance |
| Self-validation test | After Milestone 8 | Feed prepared output with intentional errors to each checklist |
| Full package validation | Milestone 9 | All validators, all scenarios, budget verified with real tokenizer |

---

## 8. Dependency Map

### 8.1 Artifact Dependency Graph

```
skill.json ──→ (standalone, Tier 0)

index.json ──→ (depends on: all artifact paths exist)
    │
    ├──→ prompts/*.md ──→ (depends on: rule IDs, file paths in frontmatter)
    │         │
    │         ├──→ rules/*.json ──→ (standalone, frozen)
    │         │
    │         └──→ checklists/*.json ──→ (depends on: rule IDs)
    │
    ├──→ examples/*.json ──→ (depends on: rule IDs in annotations)
    │
    └──→ references/*.json ──→ (depends on: rule IDs in entries)

README.md ──→ (standalone, human documentation)
```

### 8.2 External Dependencies

| Dependency | Type | Impact |
|---|---|---|
| Runtime Specification v1.1 rules | Content | All prompts reference rule IDs. Rule files frozen — no changes expected. |
| BGA Senior Engineer Doctrine | Content | All prompts follow the doctrine's problem-solving workflows |
| Reference projects | Content | All examples derive from reference projects |
| Runtime Validator | Tool | Validates all rule files. Already released. |
| Mercurio agent platform | Platform | Skill loads via file-based discovery. No platform-specific code. |

---

## 9. Risk Mitigation

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| **Token budget exceeded** by large tasks | Medium | High | Phased loading for new-feature and review-full. Each phase <2,000 tokens. |
| **Prompt ambiguity** leads to incorrect agent output | Medium | High | Every prompt instruction references a specific rule ID. Edge case sections cover common failure modes. |
| **Cross-file reference drift** — prompts reference wrong rule IDs | Low | Medium | Runtime Validator crossref check catches unresolved references. |
| **Example code out of date** with reference projects | Low | Low | Examples are minimal patterns, not full code. Structural patterns change rarely. |
| **Duplicate guidance** across prompts | Medium | Medium | Each prompt has distinct scope. Index.json maps each task to its domain. |
| **Checklist misses a violation** | Medium | Medium | Cross-reference every anti-pattern in the standards with a checklist item. |
| **Platform incompatibility** — agent can't load JSON+Markdown | Low | High | JSON and Markdown are universally supported. No platform-specific features used. |

---

## 10. Definition of Done

The skill architecture is complete when:

| Criterion | Evidence |
|---|---|
| Architecture documented | This document reviewed and approved |
| All 41 artifact paths defined | Directory structure created, file paths documented |
| All 13 task scenarios enumerated | Each prompt mapped to its task, rules, examples, and checklist |
| Token budget verified per scenario | Each task scenario loaded, measured, confirmed under 3,000 tokens |
| All 8 artifact types specified | Schemas defined for manifest, index, rules, prompts, examples, checklists, references, README |
| Extension points documented | Adding new tasks, rules, examples, checklists, references requires no structural changes |
| Implementation milestones defined | 9 milestones with dependency order, validation gates, and deliverable templates |

---

*End of skill architecture document. Implementation proceeds by milestone, in order, with each milestone following the Runtime Validator pattern: design → implement → validate → release.*
