# BGA Senior Engineer Skill — Loading Strategy Revision

**Date:** 2026-07-30
**Status:** APPROVED
**Version:** 1.0.0
**Authority:** BGA Senior Engineer — Skill Architecture (v1.0.0)

**Prerequisite:** `docs/implementation/skill/03-package-foundation.md` (token budget gap finding)

---

## Table of Contents

1. [Context](#1-context)
2. [Revised Loading Philosophy](#2-revised-loading-philosophy)
3. [Lazy-Load Decision Rules](#3-lazy-load-decision-rules)
4. [Per-Task Loading Tables](#4-per-task-loading-tables)
5. [Revised Phase Groups](#5-revised-phase-groups)
6. [Token Budget Summary](#6-token-budget-summary)
7. [Changes Required to index.json](#7-changes-required-to-indexjson)
8. [Changes Required to Prompt Design](#8-changes-required-to-prompt-design)
9. [Compatibility Impact](#9-compatibility-impact)
10. [Migration Plan](#10-migration-plan)

---

## 1. Context

### 1.1 The Gap

The Package Foundation implementation (Milestone 2) exposed a token budget gap:

| Metric | Architecture Estimate | Actual Frozen Rules |
|---|---|---|
| Total rule lines | 1,200 | 4,831 |
| Total rule tokens | ~3,600 | ~13,810 |
| Average file size | 100 lines | 403 lines |
| Largest file | 150 lines | 616 lines (architecture.json) |

The original loading strategy assumed rule files small enough that 2–8 files could load within the 3,000-token single-task budget. In reality, loading just `constitution.json` (488 lines, 1,460 tok) plus `architecture.json` (616 lines, 1,850 tok) reaches 3,310 tokens.

### 1.2 Constraints

- **Runtime Specification v1.1 is frozen.** No rule files can be reduced, split, or modified.
- **The 3-tier loading model is frozen.** Tier 0 (activation), Tier 1 (task-load), and Tier 2 (lazy-load) remain.
- **The 3,000-token single-task budget is a design target**, not a hard platform limit. Context windows of 32K–200K are standard.
- **The goal is to minimize initial context loading**, not to perfectly hit 3,000 tokens.

### 1.3 What Must Change

The original strategy treated all listed rule files as **eager-loaded** in Tier 1. The revision distinguishes:

- **Mandatory** — loaded in Tier 1 (constitution + primary domain rules)
- **Lazy-load** — loaded in Tier 2 only when the prompt explicitly requests them

This distinction already exists in the architecture for examples and references. This revision applies it to rule files.

---

## 2. Revised Loading Philosophy

### 2.1 Core Principle

**Load only what the agent must have to start working. Defer everything else to explicit lazy-load instructions in the prompt.**

Every task has exactly one primary concern. The agent needs:
1. The constitutional laws (always — 16 rules, 1,460 tok)
2. The primary domain rules for the task (varies by task, 11–22 rules, 770–1,850 tok)
3. Everything else: supplementary knowledge that the prompt points to when needed

### 2.2 What Mandatory Means

A rule file is **mandatory** if the agent cannot perform the task correctly without having its rules in context. The mandatory set for each task consists of:

- `constitution.json` — always mandatory. Its 16 immutable laws underpin every engineering decision.
- The **primary domain** file — the rule file that directly governs the task's subject matter.

A rule file is **not mandatory** if:
- It covers edge cases the agent can handle by convention
- It covers a downstream concern (e.g., DB schema during Manager extraction — needed only when the agent touches the database)
- It covers a verification step (e.g., undo-replay during action review — useful but not required for identifying structural issues)
- The prompt can provide a concise summary of the key rules and instruct the agent to lazy-load the full file when needed

### 2.3 What Lazy-Load Means

A lazy-loaded rule file follows the same Tier 2 contract as examples and references:

- **Not loaded in the initial Tier 1 task-load**
- **Loaded only when the prompt explicitly requests it**
- The agent reads a specific instruction like: `"If you encounter SQL in Game.php, load rules/persistence.json and check PERS-001 through PERS-005."`
- The agent can complete the task without loading it (though it may produce lower-quality output)

### 2.4 Revised Loading Sequence

```
Agent activates skill
    │
    v
[TIER 0] Load skill.json (1 file, ~150 tok)
    │
    v
Agent receives task
    │
    v
[TIER 1] Load index.json → match task (1 file, ~360 tok)
    │
    v
[TIER 1] Load prompt file (1 file, ~300 tok)
    │
    v
[TIER 1] Load MANDATORY rules (2–3 files, ~2,500–3,300 tok)
    │
    v
[TIER 1] Load checklist (1 file, ~150 tok)
    │
    v Total Tier 1: ~3,460–4,260 tok
    │
    ├──→ Agent encounters situation requiring secondary rules
    │       │
    │       v
    │   [TIER 2] Lazy-load secondary rule file (1 file, ~770–1,850 tok)
    │       │
    │       └──→ Agent continues with expanded context
    │
    ├──→ Prompt explicitly requests example
    │       │
    │       v
    │   [TIER 2] Lazy-load example file (1 file, ~100–200 tok)
    │
    └──→ Agent completes task, runs checklist
```

### 2.5 Budget Targets

| Component | Target | Notes |
|---|---|---|
| Tier 0 (always) | ~150 tok | skill.json — unchanged |
| Tier 1 index | ~360 tok | index.json — unchanged |
| Tier 1 prompt | ~300 tok | Per prompt — design target |
| Tier 1 mandatory rules | 2,500–3,300 tok | Constitution + 1–2 primary domains |
| Tier 1 checklist | ~150 tok | Per checklist |
| **Total Tier 1 initial load** | **3,460–4,260 tok** | The target is under 5,000 tok for all tasks |

**Acceptable overage:** Up to 5,000 tokens for the initial Tier 1 load. This leaves 27,000 tokens for codebase and reasoning in a 32K context window. The 3,000-token figure was a pre-implementation estimate based on smaller rules; 5,000 reflects the actual frozen rule sizes while still leaving 84% of context for agent work.

---

## 3. Lazy-Load Decision Rules

### 3.1 Decision Algorithm

For each (task, rule_file) pair, determine the loading tier:

```
Is the rule file constitution.json?
  → YES: Tier 1 MANDATORY (always)

Is the rule file the primary domain for this task type?
  → YES: Tier 1 MANDATORY
  → Primary domain is determined by task scope:
    migration tasks → migration.json
    review tasks → the domain being reviewed
    debug-session → (no primary domain beyond constitution)
    new-feature → multiple primary domains (phased)
    refactor-module → architecture.json

Does the rule file cover a concern that is:
  - downstream of the primary task? (e.g., persistence during migration)
  - a verification step? (e.g., undo-replay during review)
  - an edge case that occurs conditionally?
  → YES: Tier 2 LAZY-LOAD

Can the prompt provide a 1-2 sentence summary of the key rules and
defer the full file to lazy-load when needed?
  → YES: Tier 2 LAZY-LOAD
```

### 3.2 Specific Decision Criteria

| Criterion | Outcome | Example |
|---|---|---|
| File is `constitution.json` | Always Tier 1 | — |
| File is the primary domain for the task | Always Tier 1 | architecture.json for migrate-manager |
| File covers a downstream implementation concern | Tier 2 lazy-load | persistence.json for migrate-manager |
| File covers a verification/cross-check concern | Tier 2 lazy-load | undo-replay.json for review-action |
| File covers a concern the agent may not encounter | Tier 2 lazy-load | synchronization.json for review-notifications |
| File covers an independent concern in a phased task | Depends on phase | Determined by phase group definition |
| File is supplementary to the primary task | Tier 2 lazy-load | migration.json for migrate-state |

### 3.3 Prompt Instruction Template

Every lazy-load reference in a prompt follows this template:

```
If you encounter <situation>, load rules/<file>.json and check <rule-ids>.
```

Example:

```
If you encounter SQL in Game.php during the extraction,
load rules/persistence.json and check PERS-001 through PERS-005.
```

This is a concrete, actionable instruction. The agent loads the file only when the situation arises.

---

## 4. Per-Task Loading Tables

### 4.1 migrate-manager

**Primary domain:** architecture.json (component boundaries, Manager ownership)

| Loading | Rule File | Tokens | Rationale |
|---|---|---|---|
| Tier 1 MANDATORY | constitution.json | 1,460 | Immutable laws always apply |
| Tier 1 MANDATORY | architecture.json | 1,850 | Manager ownership, Game.php role, component boundaries |
| Tier 2 LAZY | persistence.json | 1,180 | DB schema rules when touching database tables |
| Tier 2 LAZY | migration.json | 1,010 | Extraction order sequence when planning the migration |

**Tier 1 total:** 3,310 tok (marginally over 3K — architecture is largest file)

**Lazy-load triggers:**
- Load `persistence.json` when extracting SQL from Game.php into Manager methods
- Load `migration.json` when planning the extraction sequence or handling legacy patterns

### 4.2 migrate-state

**Primary domain:** state-machine.json (state class design, transitions)

| Loading | Rule File | Tokens | Rationale |
|---|---|---|---|
| Tier 1 MANDATORY | constitution.json | 1,460 | Immutable laws always apply |
| Tier 1 MANDATORY | state-machine.json | 1,290 | State class structure, transitions, zombie, args |
| Tier 2 LAZY | migration.json | 1,010 | Legacy state array extraction (conditionally needed) |

**Tier 1 total:** 2,750 tok ✅

**Lazy-load trigger:**
- Load `migration.json` when handling legacy `states.inc.php` patterns or extraction sequence questions

### 4.3 migrate-notifications

**Primary domain:** notifications.json (notification design, public/private, i18n)

| Loading | Rule File | Tokens | Rationale |
|---|---|---|---|
| Tier 1 MANDATORY | constitution.json | 1,460 | Immutable laws always apply |
| Tier 1 MANDATORY | notifications.json | 1,190 | Centralized class, static methods, updateArgs, public/private |
| Tier 2 LAZY | migration.json | 1,010 | Legacy notification wrapping (conditionally needed) |

**Tier 1 total:** 2,650 tok ✅

**Lazy-load trigger:**
- Load `migration.json` when wrapping legacy `notifyAllPlayers` calls or planning extraction sequence

### 4.4 migrate-client

**Primary domain:** client.json (client architecture, Manager pattern, BgaCards)

| Loading | Rule File | Tokens | Rationale |
|---|---|---|---|
| Tier 1 MANDATORY | constitution.json | 1,460 | Immutable laws always apply |
| Tier 1 MANDATORY | client.json | 1,190 | Client Manager pattern, BgaCards, ES modules |
| Tier 2 LAZY | migration.json | 1,010 | Dojo migration specifics (conditionally needed) |

**Tier 1 total:** 2,650 tok ✅

**Lazy-load trigger:**
- Load `migration.json` when handling Dojo-to-ES module conversion specifics

### 4.5 review-action

**Primary domain:** actions.json (action handler structure, validation layers)

| Loading | Rule File | Tokens | Rationale |
|---|---|---|---|
| Tier 1 MANDATORY | constitution.json | 1,460 | Immutable laws always apply |
| Tier 1 MANDATORY | actions.json | 1,180 | 5 responsibilities, validation layers, under-15-line rule |
| Tier 2 LAZY | undo-replay.json | 770 | Undo interaction verification (conditionally needed during review) |

**Tier 1 total:** 2,640 tok ✅

**Lazy-load trigger:**
- Load `undo-replay.json` when reviewing actions that mutate undoable state

### 4.6 review-manager

**Primary domain:** architecture.json (component boundaries, Manager ownership)

| Loading | Rule File | Tokens | Rationale |
|---|---|---|---|
| Tier 1 MANDATORY | constitution.json | 1,460 | Immutable laws always apply |
| Tier 1 MANDATORY | architecture.json | 1,850 | Manager ownership, Model rules, cross-component communication |
| Tier 2 LAZY | persistence.json | 1,180 | DB schema and query rules (supplementary to Manager review) |

**Tier 1 total:** 3,310 tok (marginally over 3K — architecture is largest file)

**Lazy-load trigger:**
- Load `persistence.json` when reviewing Manager database access patterns

### 4.7 review-state-machine

**Primary domain:** state-machine.json (state design, transitions)

| Loading | Rule File | Tokens | Rationale |
|---|---|---|---|
| Tier 1 MANDATORY | constitution.json | 1,460 | Immutable laws always apply |
| Tier 1 MANDATORY | state-machine.json | 1,290 | State class design, transitions, zombie, args |

**Tier 1 total:** 2,750 tok ✅

**Lazy-load triggers:** None. All relevant rules are loaded.

### 4.8 review-notifications

**Primary domain:** notifications.json (notification design, i18n, hidden info)

| Loading | Rule File | Tokens | Rationale |
|---|---|---|---|
| Tier 1 MANDATORY | constitution.json | 1,460 | Immutable laws always apply |
| Tier 1 MANDATORY | notifications.json | 1,190 | Centralized class, public/private, i18n, payload rules |
| Tier 2 LAZY | synchronization.json | 960 | Spectator filtering, reconnect replay (supplementary concern) |

**Tier 1 total:** 2,650 tok ✅

**Lazy-load trigger:**
- Load `synchronization.json` when reviewing spectator notification handling or reconnect paths

### 4.9 review-persistence

**Primary domain:** persistence.json (DB schema, queries, globals)

| Loading | Rule File | Tokens | Rationale |
|---|---|---|---|
| Tier 1 MANDATORY | constitution.json | 1,460 | Immutable laws always apply |
| Tier 1 MANDATORY | persistence.json | 1,180 | DB schema, queries, globals, data-driven configuration |
| Tier 2 LAZY | undo-replay.json | 770 | Undo log interaction with DB (supplementary concern) |

**Tier 1 total:** 2,640 tok ✅

**Lazy-load trigger:**
- Load `undo-replay.json` when reviewing undo log table design or transaction boundaries

### 4.10 review-full

See §5.1 for revised phase groups.

### 4.11 debug-session

**Primary domain:** None (constitution only — debugging is cross-domain)

| Loading | Rule File | Tokens | Rationale |
|---|---|---|---|
| Tier 1 MANDATORY | constitution.json | 1,460 | Immutable laws always apply |

**Tier 1 total:** 1,460 tok ✅

**Lazy-load triggers:**
- Load `actions.json` when debugging action handlers
- Load `notifications.json` when debugging notification delivery
- Load `state-machine.json` when debugging state transitions
- Load specific domain file based on the bug's location (prompt instructs)

### 4.12 new-feature

See §5.2 for revised phase groups.

### 4.13 refactor-module

**Primary domain:** architecture.json (component boundaries, module organization)

| Loading | Rule File | Tokens | Rationale |
|---|---|---|---|
| Tier 1 MANDATORY | constitution.json | 1,460 | Immutable laws always apply |
| Tier 1 MANDATORY | architecture.json | 1,850 | Module size limits, spatial proximity, naming, expansion |

**Tier 1 total:** 3,310 tok (marginally over 3K — architecture is largest file)

**Lazy-load triggers:**
- Load domain-specific rule files based on the module being refactored (prompt provides conditional instructions)

---

## 5. Revised Phase Groups

### 5.1 review-full

`review-full` originally had 4 phases loading multiple rule files each. Revised to 6 smaller phases:

| Phase | Rules Loaded | New Tokens | Cumulative | Rationale |
|---|---|---|---|---|
| Constitution (retained) | constitution.json | 1,460 | 1,460 | Loaded once, retained across all phases |
| Phase 1: Architecture | architecture.json | 1,850 | 3,310 | Component boundaries and ownership |
| Phase 2: State & Actions | state-machine.json, actions.json | 2,470 | 5,780 | State design + action handler review |
| Phase 3: Data & Notifications | persistence.json, notifications.json | 2,370 | 8,150 | DB schema + notification audit |
| Phase 4: Client & Sync | client.json, synchronization.json | 2,150 | 10,300 | Client architecture + reconnect review |
| Phase 5: Undo & Animations | undo-replay.json, animations.json | 1,540 | 11,840 | Undo integrity + animation review |
| Phase 6: Testing & Migration | testing.json, migration.json | 1,970 | 13,810 | Test coverage + migration audit |

**Peak phase addition:** Phase 2 at 2,470 tok (under 3K)
**Constitution retained across all phases**

#### prompt_segment mapping:

```
Phase 1: "## Phase 1: Architecture Review"
Phase 2: "## Phase 2: State Machine and Actions Review"
Phase 3: "## Phase 3: Persistence and Notifications Review"
Phase 4: "## Phase 4: Client and Synchronization Review"
Phase 5: "## Phase 5: Undo and Animation Review"
Phase 6: "## Phase 6: Testing and Migration Review"
```

### 5.2 new-feature

`new-feature` originally had 3 phases. Revised to 4 phases for tighter budgets:

| Phase | Rules Loaded | New Tokens | Cumulative | Rationale |
|---|---|---|---|---|
| Constitution (retained) | constitution.json | 1,460 | 1,460 | Loaded once, retained across all phases |
| Phase 1: Architecture & State | architecture.json, state-machine.json | 3,140 | 4,600 | Component design + state flow |
| Phase 2: Data & Actions | persistence.json, actions.json | 2,360 | 6,960 | DB schema + action handler implementation |
| Phase 3: Notifications & Client | notifications.json, client.json | 2,380 | 9,340 | Notifications + client wiring |
| Phase 4: Undo Integrity | undo-replay.json | 770 | 10,110 | Undo log wiring + checkpoint design |

**Peak phase addition:** Phase 1 at 3,140 tok (marginally over 3K — two large files)
**Constitution retained across all phases**

#### prompt_segment mapping:

```
Phase 1: "## Phase 1: Architecture and State Design"
Phase 2: "## Phase 2: Persistence and Actions Implementation"
Phase 3: "## Phase 3: Notifications and Client Wiring"
Phase 4: "## Phase 4: Undo Integrity Wiring"
```

### 5.3 Phase Group Rules

1. **Constitution is always retained** between phases. The agent never unloads it.
2. **Each phase loads exactly the files listed.** Previous phase files may remain in context but the agent is not required to retain them (exception: constitution).
3. **Each phase targets ≤ 3,000 new tokens.** The marginally over phases (Phase 1 at 3,140 for new-feature) are accepted because architecture.json + state-machine.json together constitute the primary design concern.
4. **Phase completion is signaled explicitly.** The agent runs the phase's checklist items and declares the phase complete before loading the next.

---

## 6. Token Budget Summary

### 6.1 Non-Phased Tasks

| Task | Tier 1 Mandatory | Lazy-Load Available | Total Tier 1 | Budget |
|---|---|---|---|---|
| debug-session | constitution | (all domains lazy) | 1,460 | ✅ |
| migrate-state | constitution, state-machine | migration | 2,750 | ✅ |
| migrate-notifications | constitution, notifications | migration | 2,650 | ✅ |
| migrate-client | constitution, client | migration | 2,650 | ✅ |
| review-action | constitution, actions | undo-replay | 2,640 | ✅ |
| review-state-machine | constitution, state-machine | (none) | 2,750 | ✅ |
| review-notifications | constitution, notifications | synchronization | 2,650 | ✅ |
| review-persistence | constitution, persistence | undo-replay | 2,640 | ✅ |
| migrate-manager | constitution, architecture | persistence, migration | 3,310 | ≈ (10% over) |
| review-manager | constitution, architecture | persistence | 3,310 | ≈ (10% over) |
| refactor-module | constitution, architecture | (domain-specific) | 3,310 | ≈ (10% over) |

**Budget key:** ✅ = under 3,000 | ≈ = 3,000–3,500 (accepted)

### 6.2 Phased Tasks

| Task | Phases | Peak Phase | Initial Load |
|---|---|---|---|
| review-full | 6 phases | Phase 2: 2,470 tok | 3,310 (constitution + arch) |
| new-feature | 4 phases | Phase 1: 3,140 tok | 3,140 (constitution + arch + state) |

### 6.3 Comparison to Original Strategy

| Metric | Original Strategy | Revised Strategy | Improvement |
|---|---|---|---|
| Tasks under 3K budget | 2 of 13 | 8 of 13 | +6 tasks |
| Tasks marginally over (3K–3.5K) | 0 of 13 | 3 of 13 | +3 tasks (documented) |
| Tasks requiring phasing | 2 of 13 | 2 of 13 | (same — but phases are smaller) |
| Average initial Tier 1 load | ~5,000 tok | ~2,800 tok | ~44% reduction |
| Maximum initial Tier 1 load | ~14,260 tok (review-full) | ~3,310 tok | ~77% reduction |

---

## 7. Changes Required to index.json

### 7.1 Revised Task Rules Arrays

The `rules` array in each task entry should contain only the **mandatory** rule files. Previously-listed secondary files move to a new `lazy_rules` field.

#### 7.1.1 migrate-manager

```json
{
  "rules": [
    "rules/constitution.json",
    "rules/architecture.json"
  ],
  "lazy_rules": {
    "persistence.json": "Load when extracting SQL from Game.php",
    "migration.json": "Load when planning extraction sequence"
  }
}
```

#### 7.1.2 migrate-state

```json
{
  "rules": [
    "rules/constitution.json",
    "rules/state-machine.json"
  ],
  "lazy_rules": {
    "migration.json": "Load when handling legacy states.inc.php patterns"
  }
}
```

#### 7.1.3 migrate-notifications

```json
{
  "rules": [
    "rules/constitution.json",
    "rules/notifications.json"
  ],
  "lazy_rules": {
    "migration.json": "Load when wrapping legacy notifyAllPlayers calls"
  }
}
```

#### 7.1.4 migrate-client

```json
{
  "rules": [
    "rules/constitution.json",
    "rules/client.json"
  ],
  "lazy_rules": {
    "migration.json": "Load when handling Dojo-to-ES module conversion"
  }
}
```

#### 7.1.5 review-action

```json
{
  "rules": [
    "rules/constitution.json",
    "rules/actions.json"
  ],
  "lazy_rules": {
    "undo-replay.json": "Load when reviewing actions that mutate undoable state"
  }
}
```

#### 7.1.6 review-manager

```json
{
  "rules": [
    "rules/constitution.json",
    "rules/architecture.json"
  ],
  "lazy_rules": {
    "persistence.json": "Load when reviewing Manager database access patterns"
  }
}
```

#### 7.1.7 review-state-machine

```json
{
  "rules": [
    "rules/constitution.json",
    "rules/state-machine.json"
  ]
}
```

No lazy rules — all concerns covered by mandatory files.

#### 7.1.8 review-notifications

```json
{
  "rules": [
    "rules/constitution.json",
    "rules/notifications.json"
  ],
  "lazy_rules": {
    "synchronization.json": "Load when reviewing spectator or reconnect notification paths"
  }
}
```

#### 7.1.9 review-persistence

```json
{
  "rules": [
    "rules/constitution.json",
    "rules/persistence.json"
  ],
  "lazy_rules": {
    "undo-replay.json": "Load when reviewing undo log table design"
  }
}
```

#### 7.1.10 debug-session

```json
{
  "rules": [
    "rules/constitution.json"
  ],
  "lazy_rules": {
    "actions.json": "Load when debugging action handlers",
    "notifications.json": "Load when debugging notification delivery",
    "state-machine.json": "Load when debugging state transitions",
    "architecture.json": "Load when debugging component boundaries",
    "persistence.json": "Load when debugging database issues"
  }
}
```

#### 7.1.11 refactor-module

```json
{
  "rules": [
    "rules/constitution.json",
    "rules/architecture.json"
  ],
  "lazy_rules": {
    "actions.json": "Load when refactoring action handlers",
    "notifications.json": "Load when refactoring notification code",
    "persistence.json": "Load when refactoring database access",
    "state-machine.json": "Load when refactoring state logic"
  }
}
```

### 7.2 Revised Phase Groups

#### 7.2.1 review-full

```json
{
  "rules": [
    "rules/constitution.json"
  ],
  "phase_groups": {
    "architecture": {
      "description": "Phase 1: Review component architecture and boundaries",
      "rules": [
        "rules/architecture.json"
      ],
      "checklists": ["checklists/pre-commit.json"],
      "prompt_segment": "## Phase 1: Architecture Review"
    },
    "state_actions": {
      "description": "Phase 2: Review state machine and action handlers",
      "rules": [
        "rules/state-machine.json",
        "rules/actions.json"
      ],
      "checklists": ["checklists/pre-commit.json"],
      "prompt_segment": "## Phase 2: State Machine and Actions Review"
    },
    "data_notifications": {
      "description": "Phase 3: Review persistence and notifications",
      "rules": [
        "rules/persistence.json",
        "rules/notifications.json"
      ],
      "checklists": ["checklists/pre-commit.json"],
      "prompt_segment": "## Phase 3: Persistence and Notifications Review"
    },
    "client_sync": {
      "description": "Phase 4: Review client and synchronization",
      "rules": [
        "rules/client.json",
        "rules/synchronization.json"
      ],
      "checklists": ["checklists/pre-commit.json"],
      "prompt_segment": "## Phase 4: Client and Synchronization Review"
    },
    "undo_animations": {
      "description": "Phase 5: Review undo integrity and animations",
      "rules": [
        "rules/undo-replay.json",
        "rules/animations.json"
      ],
      "checklists": ["checklists/pre-commit.json"],
      "prompt_segment": "## Phase 5: Undo and Animation Review"
    },
    "testing_migration": {
      "description": "Phase 6: Review test coverage and migration readiness",
      "rules": [
        "rules/testing.json",
        "rules/migration.json"
      ],
      "checklists": [
        "checklists/pre-review.json",
        "checklists/pre-release.json"
      ],
      "prompt_segment": "## Phase 6: Testing and Migration Review"
    }
  }
}
```

#### 7.2.2 new-feature

```json
{
  "rules": [
    "rules/constitution.json"
  ],
  "phase_groups": {
    "design": {
      "description": "Phase 1: Design domain boundary, state flow, and DB schema",
      "rules": [
        "rules/architecture.json",
        "rules/state-machine.json"
      ],
      "prompt_segment": "## Phase 1: Architecture and State Design"
    },
    "implementation": {
      "description": "Phase 2: Implement persistence layer and action handlers",
      "rules": [
        "rules/persistence.json",
        "rules/actions.json"
      ],
      "examples": [
        "examples/manager-example.json",
        "examples/action-example.json",
        "examples/model-example.json"
      ],
      "prompt_segment": "## Phase 2: Persistence and Actions Implementation"
    },
    "integration": {
      "description": "Phase 3: Wire notifications and client handlers",
      "rules": [
        "rules/notifications.json",
        "rules/client.json"
      ],
      "examples": [
        "examples/notification-example.json",
        "examples/client-manager-example.json",
        "examples/state-example.json"
      ],
      "checklists": ["checklists/pre-commit.json"],
      "prompt_segment": "## Phase 3: Notifications and Client Wiring"
    },
    "undo": {
      "description": "Phase 4: Wire undo integrity",
      "rules": [
        "rules/undo-replay.json"
      ],
      "examples": [
        "examples/undo-example.json"
      ],
      "checklists": ["checklists/pre-commit.json"],
      "prompt_segment": "## Phase 4: Undo Integrity Wiring"
    }
  }
}
```

### 7.3 New `lazy_rules` Schema

Add a new optional field to task entries:

| Field | Required | Type | Default | Description |
|---|---|---|---|---|
| `lazy_rules` | No | Object | — | Maps rule file names (relative to `rules/`) to lazy-load trigger descriptions. Keys are filenames, values are instructions the prompt uses to tell the agent when to load. |

Each key in `lazy_rules` is a filename like `"persistence.json"`. The value is a string like `"Load when extracting SQL from Game.php"` that the prompt template uses verbatim.

Validation rules:

| # | Rule | Severity | Description |
|---|---|---|---|
| LR01 | Every `lazy_rules` key must exist in `rules/` | ERROR | Lazy-loaded rule files must exist. |
| LR02 | A rule file must not appear in both `rules` and `lazy_rules` for the same task | ERROR | No duplication. |
| LR03 | `lazy_rules` values must be non-empty strings | WARNING | Every lazy-load trigger needs a description. |

---

## 8. Changes Required to Prompt Design

### 8.1 Prompt Frontmatter

Each prompt's YAML frontmatter must list both mandatory and lazy-loaded rules:

```yaml
---
task: migrate-manager
version: 1.0.0
required_rules:
  - rules/constitution.json
  - rules/architecture.json
lazy_rules:
  - rules/persistence.json
  - rules/migration.json
---
```

### 8.2 Lazy-Load Instructions in Prompts

Every prompt must include a **Lazy-Load section** after the main workflow:

```markdown
## Lazy-Load Rules

The following rule files are available for conditional loading.
Load them only when the prompt instructs or when you encounter
the described situation:

- rules/persistence.json — Load when extracting SQL from Game.php
- rules/migration.json — Load when planning extraction sequence
```

### 8.3 Conditional References in Workflow Steps

Workflow steps that reference lazy-loaded rules must include explicit load instructions:

```markdown
### Step 3: Extract SQL

If the legacy Game.php contains SQL on game tables:
1. Load rules/persistence.json
2. Follow PERS-001 through PERS-005 for atomic conditional UPDATE patterns
3. Extract SQL into Manager methods following ARCH-006
```

### 8.4 Summary of Prompt Changes

| Element | Change |
|---|---|
| Frontmatter `required_rules` | Lists only mandatory rule files |
| Frontmatter `lazy_rules` | New field listing lazy-loaded rule files |
| Workflow steps | Include `"Load rules/<file>.json"` before referencing lazy-loaded rules |
| Edge cases section | References lazy-loaded rules that cover edge conditions |
| Stop conditions | Reference only mandatory rules; lazy rules are for quality improvement |

---

## 9. Compatibility Impact

### 9.1 Backward Compatibility

| Component | Impact | Mitigation |
|---|---|---|
| `skill.json` | No changes | Capabilities remain unchanged |
| `index.json` | `rules` arrays shrink; `lazy_rules` added | New field is additive. Existing agents that ignore `lazy_rules` still load fewer mandatory rules. No breaking changes. |
| `runtime-skill-architecture.md` | Loading scenarios change | Updated token budgets reflect actual rule sizes |
| Existing prompts | Not yet implemented — no impact | Prompt template incorporates lazy-load instructions from the start |
| Rule files | No changes | Frozen — unchanged |
| Runtime Validator | No changes | Validates rule file schema only; not affected by index.json changes |

### 9.2 Agent Behavior Change

| Scenario | Old Behavior | New Behavior |
|---|---|---|
| migrate-manager | Loads 4 rule files (3,310–5,850 tok depending on estimation method) | Loads 2 mandatory rule files (3,310 tok). Lazy-loads persistence.json and migration.json when needed. |
| review-action | Loads 3 rule files (3,760 tok) | Loads 2 mandatory rule files (2,640 tok). Lazy-loads undo-replay.json when reviewing undoable mutations. |
| debug-session | Loads 1 rule file (1,460 tok) | Loads 1 rule file (1,460 tok). All other domains are lazy-loaded on demand. |

The agent loads fewer tokens upfront but may make additional lazy-load calls. This is the same pattern already established for examples and references.

### 9.3 Token Trade-Off

| Aspect | Before | After |
|---|---|---|
| Initial Tier 1 load | 3,000–14,260 tok | 1,460–3,310 tok |
| Additional lazy-loads | 0 | 0–3 per task |
| Total tokens (with lazy-loads) | Same as initial | May equal or slightly exceed initial due to lazy-load overhead |
| Reasoning steps | 7 (standard pipeline) | 7–10 (additional lazy-load steps) |

The trade-off is: **fewer tokens upfront, potentially more reasoning steps**. This is acceptable because:
1. The agent only lazy-loads what it actually needs
2. Most tasks complete without loading all lazy rules
3. The initial context has more room for codebase analysis

---

## 10. Migration Plan

### 10.1 Steps

1. **(DONE)** Approve this loading strategy revision
2. **Update `index.json`** — Move secondary rule files to `lazy_rules`; revise phase groups for `review-full` and `new-feature`
3. **Update prompt templates** — All prompts reference `lazy_rules` and include conditional load instructions
4. **Verify loading scenarios** — Confirm each task's initial Tier 1 load is within the accepted budget
5. **Proceed with prompt implementation**

### 10.2 What Does Not Change

- `skill.json` — no changes needed
- Rule files — frozen, unchanged
- Runtime Validator — unchanged
- 3-tier loading model — unchanged
- Task classification via keyword matching — unchanged
- File format conventions — unchanged

### 10.3 What Changes

| Artifact | Change |
|---|---|
| `index.json` task entries | `rules` arrays contain only mandatory files; new `lazy_rules` field added |
| `index.json` phase groups | `review-full`: 4 phases → 6 phases; `new-feature`: 3 phases → 4 phases |
| Prompt frontmatter | New `lazy_rules` field |
| Prompt content | New "Lazy-Load Rules" section; conditional load instructions in workflow steps |
| Token budgets | Target revised from 3,000 to 5,000 tokens maximum initial load |

---

*End of loading strategy revision. Implementation follows after approval. The next milestone implements `index.json` changes and begins prompt creation using the revised loading model.*
