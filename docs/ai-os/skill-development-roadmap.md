# BGA Senior Engineer — Skill Development Roadmap

**Purpose:** Master project plan for transforming the documentation corpus (~26K lines) into a compact, production-quality AI skill optimized for Mercurio migration agents.

**Status:** Draft — Phase 0 (Planning)

**Input:** Completed documentation corpus (foundation + standards + pattern catalog + doctrine)

**Target Output:** A single installable skill package that fits within a typical AI agent context window (~8K-32K tokens) and enables an agent to produce correct, production-grade BGA game implementations.

---

## Table of Contents

1. [Vision](#1-vision)
2. [Design Principles](#2-design-principles)
3. [Target Skill Architecture](#3-target-skill-architecture)
4. [Development Phases](#4-development-phases)
5. [Milestones](#5-milestones)
6. [Deliverables](#6-deliverables)
7. [Validation Strategy](#7-validation-strategy)
8. [Risk Assessment](#8-risk-assessment)
9. [Suggested Development Order](#9-suggested-development-order)
10. [Progress Tracker](#10-progress-tracker)

---

## 1. Vision

### 1.1 Purpose

Transform the 26,000-line documentation corpus into a compact AI skill that teaches Mercurio migration agents to produce correct, maintainable, production-grade BGA game implementations. The skill replaces the need for an agent to load and reason about the full documentation corpus. Instead, the skill provides exactly what the agent needs, exactly when it needs it, in the most token-efficient form possible.

### 1.2 Target Users

**Primary:** Mercurio migration agents — AI agents that migrate legacy BGA game implementations to modern BGA architecture. These agents work against an existing codebase (not from scratch), need to make architectural decisions, must never break existing functionality, and produce code that passes BGA Studio's pre-release checklist.

**Secondary:** Any AI agent tasked with BGA game development (new builds, refactors, bug fixes, code review).

### 1.3 Primary Goals

| Goal | Metric |
|---|---|
| **Correctness-first output** | Agent-produced code passes all validation rules before human review |
| **Minimal context cost** | Skill consumes under 12K tokens when loaded for a single-task session |
| **Fast retrieval** | Agent finds the relevant rule in under 3 reasoning steps |
| **No conflicting guidance** | Zero contradictory rules within the skill |
| **Self-validating** | Every output checked against review rules before agent declares done |
| **Maintainable** | Adding a new standard requires editing under 3 files |
| **Traceable** | Every rule and prompt traces back to a source document and standard |

### 1.4 Explicit Non-Goals

- **Not** a tutorial. The skill does not teach BGA concepts from scratch. It assumes the agent already has foundational BGA knowledge.
- **Not** a replacement for the documentation corpus. Source documents remain authoritative. The skill is a runtime distillation.
- **Not** a code generator. The skill guides architecture and reviews output. It does not template code.
- **Not** a BGA framework reference. It does not duplicate the official BGA API documentation.
- **Not** an exhaustive encyclopedia. It prioritizes what agents get wrong most often, not what they already know.
- **Not** a human-readable manual. It is structured for machine parsing and agent reasoning.

---

## 2. Design Principles

### 2.1 Runtime Principles

| Principle | Definition | Implementation Constraint |
|---|---|---|
| **Small runtime footprint** | Skill loads only what the current task needs | Progressive disclosure: index → category → rule |
| **Fast context loading** | Agent reaches actionable guidance in 1-2 steps | Flat, tagged rule structure with deterministic lookup |
| **Minimal token usage** | Every token in the skill earns its place | Each rule must prevent at least one class of real-world bug |
| **High consistency** | Same input always produces the same guidance path | No conditional branches dependent on agent state |
| **Deterministic behaviour** | No randomness in rule selection or priority | Fixed priority ordering, explicit conflict resolution |
| **Easy maintenance** | Updating a standard is a single-file edit | One rule file per domain, one prompt file per task |
| **Easy future updates** | New BGA features added without restructuring | Category-tagged rules; new categories are additive |
| **Traceability** | Every runtime artifact cites its source document | `source:` field on every rule, prompt, and example |

### 2.2 Design Philosophy

```
Documentation Corpus (26K lines, human-readable)
        │
        │  DISTILL: Extract actionable rules, discard exposition
        │  COMPRESS: Collapse patterns into checklists
        │  RESTRUCTURE: Tag by retrieval key, not by source document
        │  VALIDATE: Test every rule against reference projects
        │
        ▼
Runtime Skill Package (<12K tokens, machine-readable)
```

The skill is **not** a condensed version of the documentation. It is a new artifact designed for a fundamentally different purpose. It answers "What should I do right now?" rather than "What should I understand about this topic?"

---

## 3. Target Skill Architecture

### 3.1 Package Structure

```
bga-senior-engineer-skill/
├── skill.json                          # Skill manifest (metadata, version, capabilities)
├── index.json                          # Master index: task → required rules + prompts
├── rules/                              # Distilled engineering rules
│   ├── constitution.json               # Immutable laws (the "never" rules)
│   ├── architecture.json               # Component responsibilities, boundaries
│   ├── state-machine.json              # State design rules
│   ├── actions.json                    # Action handler rules
│   ├── persistence.json                # Database + globals rules
│   ├── notifications.json              # Notification design rules
│   ├── client.json                     # Client architecture rules
│   ├── synchronization.json            # Reconnect + spectator rules
│   ├── animations.json                 # Animation system rules
│   ├── testing.json                    # Test strategy rules
│   ├── undo-replay.json               # Undo + replay integrity rules
│   └── migration.json                  # Migration-specific rules
├── prompts/                            # Task-specific agent prompts
│   ├── migrate-manager.md              # Extract a Manager from legacy code
│   ├── migrate-state.md                # Convert state array to State classes
│   ├── migrate-notifications.md        # Extract centralized Notification class
│   ├── migrate-client.md               # Convert Dojo to ES modules + BgaCards
│   ├── review-action.md                # Review a single action handler
│   ├── review-manager.md               # Review a Manager class
│   ├── review-state-machine.md         # Review entire state machine
│   ├── review-notifications.md         # Audit notification system
│   ├── review-persistence.md           # Audit database schema + globals
│   ├── review-full.md                  # Full pre-release audit
│   ├── debug-session.md                # Systematic debugging workflow
│   ├── new-feature.md                  # Add a new game feature
│   └── refactor-module.md              # Refactor a module to standard
├── examples/                           # Minimal worked examples
│   ├── manager-example.json            # Canonical Manager (annotated)
│   ├── action-example.json             # Canonical Action handler (annotated)
│   ├── model-example.json              # Canonical Model (annotated)
│   ├── notification-example.json       # Canonical Notification method (annotated)
│   ├── state-example.json             # Canonical State class (annotated)
│   ├── client-manager-example.json     # Canonical client Manager (annotated)
│   └── undo-example.json              # Canonical undo flow (annotated)
├── checklists/                         # Agent self-validation checklists
│   ├── pre-commit.json                # Run before every file write
│   ├── pre-review.json                # Run before declaring task complete
│   └── pre-release.json               # Full BGA release readiness
├── references/                         # Compact cross-references
│   ├── reference-project-matrix.json   # Which project to consult for what
│   ├── anti-patterns.json             # Condensed anti-pattern catalog
│   └── migration-mapping.json          # Legacy → modern mapping table
└── README.md                           # Package documentation for integrators
```

### 3.2 Artifact Descriptions

#### 3.2.1 `skill.json` — Skill Manifest

| Attribute | Value |
|---|---|
| **Purpose** | Declares the skill to the agent platform. Defines capabilities, version, and compatibility. |
| **Expected size** | ~200 lines (JSON) |
| **Responsibilities** | Skill identity, version tracking, capability declaration, dependency declarations, loading instructions |
| **Dependencies** | None (root artifact) |

#### 3.2.2 `index.json` — Master Index

| Attribute | Value |
|---|---|
| **Purpose** | A lookup table that maps every task an agent might perform to the minimal set of rules, prompts, examples, and checklists needed. |
| **Expected size** | ~150 lines (JSON) |
| **Responsibilities** | Task-to-artifact mapping, progressive-disclosure gateway, ensures agents load only what they need |
| **Dependencies** | All rules/, prompts/, examples/, checklists/ subdirectories |

Structure:
```json
{
  "tasks": {
    "migrate-manager": {
      "rules": ["constitution", "architecture", "persistence", "migration"],
      "prompt": "migrate-manager",
      "examples": ["manager-example", "model-example"],
      "checklist": "pre-commit"
    }
  }
}
```

#### 3.2.3 `rules/` — Distilled Engineering Rules

Each file is a compact, structured rule set for one domain. Rules are prioritized, tagged, and non-redundant across files.

| File | Purpose | Expected Size | Source Documents |
|---|---|---|---|
| `constitution.json` | Immutable laws. Rules that always apply, regardless of task. | ~100 lines | Doctrine §§2,12,15 |
| `architecture.json` | Component ownership, boundaries, layering, dependency rules. | ~150 lines | domain-architecture.md, project-architecture.md, game-flow-architecture.md |
| `state-machine.json` | State design, transition, args, zombie, _no_notify rules. | ~100 lines | state-machine-architecture.md |
| `actions.json` | Action handler structure, validation order, delegation rules. | ~100 lines | action-architecture.md |
| `persistence.json` | DB schema, queries, transactions, globals rules. | ~100 lines | persistence-architecture.md |
| `notifications.json` | Notification design, public/private, payload, i18n rules. | ~100 lines | notification-patterns.md |
| `client.json` | Client architecture, Manager pattern, widgets, rendering rules. | ~100 lines | client-ui-architecture.md |
| `synchronization.json` | Reconnect, spectator, getAllDatas, refreshUI rules. | ~80 lines | client-synchronization-architecture.md |
| `animations.json` | Animation architecture, queue, fast-mode rules. | ~80 lines | animation-architecture.md |
| `testing.json` | Test strategy, coverage requirements, PHPUnit rules. | ~80 lines | testing-debugging-architecture.md |
| `undo-replay.json` | Undo safety, log tables, checkpoints, replay determinism rules. | ~80 lines | game-flow-architecture.md, persistence-architecture.md |
| `migration.json` | Legacy-to-modern mapping, extraction order, safety rules. | ~80 lines | Doctrine §10, migration references in each standard |

**Expected total rules/ size:** ~1,200 lines (~12KB)

**Rule format:**
```json
{
  "domain": "architecture",
  "rules": [
    {
      "id": "ARCH-001",
      "priority": 1,
      "rule": "Game.php is a switchboard. Under 300 lines. Zero SQL.",
      "check": "grep -c 'function' game.php | assert < 15",
      "violation": "Game.php contains domain logic, SQL queries, or exceeds 300 lines",
      "fix": "Extract to a Manager. Follow manager-example.",
      "source": "doctrine:§15.4, project-architecture:§3.1",
      "tags": ["architecture", "game.php", "migration"]
    }
  ]
}
```

#### 3.2.4 `prompts/` — Task-Specific Agent Prompts

Each prompt is a self-contained instruction set that the agent can load to perform exactly one task. Prompts are designed to be concatenated with the relevant rules and examples.

| File | Purpose | Expected Size | Required Rules | Required Examples |
|---|---|---|---|---|
| `migrate-manager.md` | Extract a single Manager from legacy Game.php | ~100 lines | constitution, architecture, persistence, migration | manager-example, model-example |
| `migrate-state.md` | Convert legacy states.inc.php to State classes | ~100 lines | constitution, state-machine, migration | state-example |
| `migrate-notifications.md` | Extract centralized Notifications class | ~80 lines | constitution, notifications, migration | notification-example |
| `migrate-client.md` | Convert Dojo client to ES modules + BgaCards | ~100 lines | constitution, client, migration | client-manager-example |
| `review-action.md` | Review a single action handler against standards | ~80 lines | constitution, actions, undo-replay | action-example |
| `review-manager.md` | Review a Manager class against standards | ~80 lines | constitution, architecture, persistence | manager-example |
| `review-state-machine.md` | Review entire state machine | ~80 lines | constitution, state-machine | state-example |
| `review-notifications.md` | Audit notification system | ~80 lines | constitution, notifications, synchronization | notification-example |
| `review-persistence.md` | Audit database schema + globals | ~80 lines | constitution, persistence, undo-replay | — |
| `review-full.md` | Full pre-release audit | ~120 lines | all rules | all examples |
| `debug-session.md` | Systematic debugging workflow | ~80 lines | constitution | — |
| `new-feature.md` | Add a new game feature end-to-end | ~120 lines | all rules | all examples |
| `refactor-module.md` | Refactor a module to canonical standards | ~100 lines | constitution, architecture | manager-example |

**Expected total prompts/ size:** ~1,200 lines (~15KB)

#### 3.2.5 `examples/` — Canonical Examples

Minimal, annotated examples showing the canonical form of each component type. Each example is a single file, stripped of all non-essential code, with inline annotations explaining why each decision was made.

| File | Purpose | Expected Size | Source |
|---|---|---|---|
| `manager-example.json` | Canonical Manager class with all standard methods | ~60 lines | Reference projects (primarily Arnak + Earth) |
| `action-example.json` | Canonical action handler under 15 lines | ~30 lines | Reference projects |
| `model-example.json` | Canonical Model with computed properties | ~30 lines | Reference projects |
| `notification-example.json` | Canonical centralized notification method | ~25 lines | Reference projects |
| `state-example.json` | Canonical State class | ~30 lines | Reference projects |
| `client-manager-example.json` | Canonical client-side Manager | ~40 lines | Reference projects |
| `undo-example.json` | Canonical undo flow (log + checkpoint + reverse) | ~40 lines | Reference projects (Earth preferred) |

**Expected total examples/ size:** ~300 lines (~4KB)

#### 3.2.6 `checklists/` — Self-Validation Checklists

Structured checklists the agent runs against its own output before declaring a task complete. Each checklist item is a verifiable condition (pass/fail) with a fix instruction.

| File | Purpose | Expected Size |
|---|---|---|
| `pre-commit.json` | Run before writing any file. Checks: correct layer, no anti-pattern introduced, no duplicated code. | ~50 lines |
| `pre-review.json` | Run before declaring a task complete. Checks: all rules for the task domain passed. | ~60 lines |
| `pre-release.json` | Full BGA Studio pre-release checklist. Mirrors official checklist but as verifiable rules. | ~80 lines |

**Expected total checklists/ size:** ~200 lines (~3KB)

#### 3.2.7 `references/` — Compact Cross-References

Quick-lookup tables that help the agent find relevant reference implementations without loading the full analysis document.

| File | Purpose | Expected Size |
|---|---|---|
| `reference-project-matrix.json` | "I need to implement X → look at project Y, file Z" | ~80 lines |
| `anti-patterns.json` | Condensed catalog of common mistakes with detection rules | ~100 lines |
| `migration-mapping.json` | Legacy construct → modern equivalent table | ~60 lines |

**Expected total references/ size:** ~250 lines (~3KB)

### 3.3 Aggregate Size Budget

| Component | Lines | Estimated Tokens |
|---|---|---|
| `skill.json` | 200 | ~600 |
| `index.json` | 150 | ~450 |
| `rules/` (12 files) | 1,200 | ~3,600 |
| `prompts/` (13 files) | 1,200 | ~3,600 |
| `examples/` (7 files) | 300 | ~900 |
| `checklists/` (3 files) | 200 | ~600 |
| `references/` (3 files) | 250 | ~750 |
| `README.md` | 100 | ~300 |
| **Total** | **~3,600 lines** | **~11,000 tokens** |

**Design target:** Under 12,000 tokens for the full skill. Under 3,000 tokens for any single-task load (index + relevant rules + prompt + example + checklist).

This represents a **7x compression** from the 26,000-line documentation corpus while retaining all engineering decision-making capability.

---

## 4. Development Phases

### Phase 1: Rule Distillation

**Purpose:** Extract every actionable engineering rule from the documentation corpus and express it in the compact, machine-parseable format defined in §3.2.3.

**Inputs:**
- All 17 documentation files (~26,000 lines)
- The canonical skill architecture (§3)
- Design principles (§2)

**Outputs:**
- `rules/constitution.json`
- `rules/architecture.json`
- `rules/state-machine.json`
- `rules/actions.json`
- `rules/persistence.json`
- `rules/notifications.json`
- `rules/client.json`
- `rules/synchronization.json`
- `rules/animations.json`
- `rules/testing.json`
- `rules/undo-replay.json`
- `rules/migration.json`

**Success criteria:**
- Every rule has a unique ID
- Every rule has a source citation
- Zero duplicate rules across files
- Zero contradictory rules (all conflicts resolved)
- All rules are verifiable (check is concrete)
- Total rules/ size under 1,200 lines

**Dependencies:** None (documentation corpus is complete)

**Risks:**
- **Over-extraction.** Including every guideline instead of only rules that prevent real bugs. → Mitigation: Every rule must cite at least one real anti-pattern from the reference project analysis.
- **Under-extraction.** Missing rules that agents commonly violate. → Mitigation: Cross-reference the anti-pattern list in every standard against the extracted rules. Every anti-pattern must have at least one rule that prevents it.
- **Ambiguous rules.** Rules that read well to humans but confuse agents. → Mitigation: Every rule must have a concrete `check` field (grep pattern, regex, assertion).

**Estimated complexity:** Medium
**Estimated effort:** 3-4 focused sessions
**Recommended review:** Review each rule file against its source standard before proceeding to Phase 2.

---

### Phase 2: Cross-Reference Construction

**Purpose:** Build the compact lookup tables that allow agents to find the right rule, example, and reference project without loading the full documentation.

**Inputs:**
- All rules from Phase 1
- `reference-project-analysis.md`
- The 11 anti-pattern sections across the standards

**Outputs:**
- `references/reference-project-matrix.json`
- `references/anti-patterns.json`
- `references/migration-mapping.json`

**Success criteria:**
- Matrix covers all 20+ implementation problems from the original analysis
- Anti-patterns catalog cross-references the rule IDs that prevent each
- Migration mapping covers every row in Doctrine §10 table
- Total references/ size under 250 lines

**Dependencies:** Phase 1 (needs rule IDs)

**Risks:**
- **Incomplete matrix.** Missing reference project entries for some problem types. → Mitigation: Verify coverage against the full problem matrix in reference-project-analysis.md.
- **Stale anti-pattern list.** The standards have evolved; the anti-pattern list may be missing newer ones. → Mitigation: Scan every standard's anti-pattern section during construction.

**Estimated complexity:** Low
**Estimated effort:** 1 focused session
**Recommended review:** Spot-check 5 random matrix entries against the actual reference project code.

---

### Phase 3: Example Curation

**Purpose:** Extract canonical examples from the reference projects, strip them to absolute essentials, and annotate with decision rationale.

**Inputs:**
- All 4 reference projects (Agricola, Ark Nova, Arnak, Earth)
- Rules from Phase 1 (to ensure examples demonstrate rule compliance)
- Architecture rules (to select the most standard-conformant project for each example)

**Outputs:**
- `examples/manager-example.json`
- `examples/action-example.json`
- `examples/model-example.json`
- `examples/notification-example.json`
- `examples/state-example.json`
- `examples/client-manager-example.json`
- `examples/undo-example.json`

**Success criteria:**
- Each example compiles (syntax-valid PHP or JS)
- Each example demonstrates compliance with all relevant rules
- Each annotation explains WHY, not just WHAT
- Total examples/ size under 300 lines (each example is small)

**Dependencies:** Phase 1 (need rules to validate against)

**Risks:**
- **Over-annotating.** Adding too much explanation defeats the token budget. → Mitigation: Annotations limited to one line per design decision. Max 3 annotations per example.
- **Wrong reference project choice.** Some projects use outdated patterns (e.g., Agricola's legacy PHP). → Mitigation: Prefer Earth (newest, cleanest architecture) and Arnak (best conventions) for examples. Only use Agricola and Ark Nova for Engine-specific patterns.

**Estimated complexity:** Medium
**Estimated effort:** 2 focused sessions
**Recommended review:** Have an agent attempt to reproduce each example from the rules. The example is valid if the agent can reconstruct it.

---

### Phase 4: Prompt Engineering

**Purpose:** Write the 13 task-specific prompts that orchestrate the agent's work. Each prompt loads the minimal rule set, provides a step-by-step workflow, and includes a self-validation checkpoint.

**Inputs:**
- All rules from Phase 1
- All examples from Phase 3
- All checklists (to be built in Phase 5, but the prompts need to reference them)
- The Doctrine's problem-solving workflows (§5) and decision hierarchy (§4)

**Outputs:**
- All 13 files in `prompts/` (see §3.2.4)

**Success criteria:**
- Each prompt loads under 3,000 tokens total (prompt + rules + example + checklist)
- Each prompt produces deterministic output (same input rules → same guidance path)
- Each prompt includes an explicit STOP condition (when the task is complete)
- Each prompt references specific rule IDs for every guidance assertion
- Total prompts/ size under 1,200 lines

**Dependencies:** Phases 1-3 (prompts orchestrate rules, examples, and references)

**Risks:**
- **Prompt bloat.** Loading rules the task doesn't need. → Mitigation: The `index.json` restricts each task to exactly the rules it requires. Prompts never load rules outside their index entry.
- **Prompt ambiguity.** Natural language instructions that agents misinterpret. → Mitigation: Every prompt instruction must have a corresponding rule ID. "Extract the Manager" → "Follow ARCH-004 through ARCH-012."
- **Missing edge cases.** Prompts that succeed on happy path but fail on edge cases. → Mitigation: Every prompt includes an explicit "What about ___?" section covering the 3 most common edge cases for that task.

**Estimated complexity:** High
**Estimated effort:** 4-5 focused sessions
**Recommended review:** Run each prompt through a test agent against a known migration task. Measure: did the agent produce correct output? Did it need to be corrected?

---

### Phase 5: Checklist Construction

**Purpose:** Build the self-validation checklists the agent runs against its own output. These are the runtime quality gates.

**Inputs:**
- All rules from Phase 1 (every rule that can be auto-checked becomes a checklist item)
- BGA Studio's official pre-release checklist
- The 17.4-point notification checklist from `notification-patterns.md`
- The 20-point action review checklist from `action-architecture.md`
- The 11-point Manager audit from `domain-architecture.md`

**Outputs:**
- `checklists/pre-commit.json`
- `checklists/pre-review.json`
- `checklists/pre-release.json`

**Success criteria:**
- Every checklist item is verifiable (binary pass/fail)
- Every fail condition has a fix instruction referencing a rule ID
- `pre-commit.json` runs in under 5 reasoning steps
- `pre-review.json` covers all task domains
- `pre-release.json` covers the official BGA Studio pre-release requirements
- Total checklists/ size under 200 lines

**Dependencies:** Phase 1 (needs rule IDs), Phase 4 (prompts reference checklists)

**Risks:**
- **Too many items.** Overwhelms the agent and causes checklist fatigue. → Mitigation: `pre-commit.json` max 10 items. `pre-review.json` max 15 items. `pre-release.json` max 20 items.
- **Unverifiable items.** "Is the architecture clean?" is not verifiable. → Mitigation: Every item must decompose to a concrete check (file size, naming convention, grep pattern).

**Estimated complexity:** Low
**Estimated effort:** 1 focused session
**Recommended review:** Run `pre-commit.json` against a known-broken Manager. Verify it catches every violation.

---

### Phase 6: Index Construction

**Purpose:** Build the master index that maps every task to its minimal required artifacts. This is the progressive-disclosure gateway.

**Inputs:**
- All prompts from Phase 4 (know which rules each references)
- All rules from Phase 1 (know their domain tags)
- All examples from Phase 3 (know which patterns they demonstrate)

**Outputs:**
- `index.json`

**Success criteria:**
- Every task in the prompts/ directory has an index entry
- Every index entry specifies the exact rule files needed (no "all rules" entries except for `review-full`)
- Index loads under 450 tokens
- Agent can resolve "I need to migrate a Manager" → load index → load 4 rule files + 1 prompt + 2 examples + 1 checklist in under 3 reasoning steps

**Dependencies:** Phases 1-5 (index maps everything)

**Risks:**
- **Wrong rule set for task.** Missing a critical rule or including irrelevant ones. → Mitigation: Validate each index entry by running the corresponding prompt through an agent. Measure: did it need a rule that wasn't loaded?
- **Overly broad `review-full`.** Tries to load all rules and exceeds token budget. → Mitigation: `review-full` is tiered: Phase 1 review (constitution + architecture), Phase 2 review (domain-specific), etc.

**Estimated complexity:** Low
**Estimated effort:** 1 focused session
**Recommended review:** Test 3 random index entries against their corresponding prompts.

---

### Phase 7: Manifest & Packaging

**Purpose:** Create the skill manifest, write integration documentation, and assemble the final package.

**Inputs:**
- All artifacts from Phases 1-6
- The skill architecture (§3)
- Mercurio integration requirements (to be determined)

**Outputs:**
- `skill.json`
- `README.md`

**Success criteria:**
- `skill.json` is valid JSON, parseable by the target agent platform
- `skill.json` declares all capabilities and version
- `README.md` explains how to install, load, and use the skill
- README under 100 lines
- Package installs as a single directory

**Dependencies:** Phases 1-6

**Risks:**
- **Platform incompatibility.** Skill format doesn't match Mercurio's agent platform. → Mitigation: Define the format requirements with the Mercurio team before packaging.
- **Missing integration hooks.** Agents can't discover the skill automatically. → Mitigation: README includes explicit loading instructions.

**Estimated complexity:** Low
**Estimated effort:** 1 focused session
**Recommended review:** Have a fresh agent install and use the skill for a simple task.

---

### Phase 8: Validation & Quality Assurance

**Purpose:** End-to-end validation of the complete skill package. Test every prompt, every rule, every checklist. Measure token usage, consistency, and correctness.

**Inputs:**
- Complete skill package (Phases 1-7)
- Test cases: 3 migration scenarios (simple Manager, complex state machine, notification extraction)
- Reference project code (as ground truth)

**Outputs:**
- Validation report (metrics, issues found, fixes applied)
- Final skill package (post-fix)

**Success criteria:**
- All 13 prompts produce correct output for their target tasks
- Zero conflicting rules detected in cross-file validation
- Token budget met (<12K full load, <3K single-task load)
- Agent self-validation catches 100% of intentional errors in test inputs
- Skill produces identical guidance for identical inputs (determinism verified)

**Dependencies:** Phase 7

**Risks:**
- **Test scenarios not representative.** Migration tasks used for testing don't cover real-world complexity. → Mitigation: Use actual legacy BGA code (not synthetic examples) if available. Otherwise, use the reference projects as "legacy" targets (e.g., "migrate Agricola to modern architecture").
- **Token measurement inaccurate.** Different agent platforms count tokens differently. → Mitigation: Report token counts in both gpt-4 and claude tokenizers.

**Estimated complexity:** High
**Estimated effort:** 3-4 focused sessions (includes fix iterations)
**Recommended review:** This is the final gate before production. All stakeholders review the validation report.

---

### Phase 9: Production Release

**Purpose:** Tag the release, publish to the Mercurio agent platform, and document the release.

**Inputs:**
- Validated skill package (Phase 8)
- Mercurio platform requirements

**Outputs:**
- Git tag `v1.0.0`
- Published skill on target platform
- Release notes

**Success criteria:**
- Skill is loadable by Mercurio agents in production
- First production task completed successfully
- Feedback loop established (agent performance metrics collected)

**Dependencies:** Phase 8

**Estimated complexity:** Low
**Estimated effort:** 1 session

---

## 5. Milestones

| # | Milestone | Phase(s) | Definition of Done |
|---|---|---|---|
| M1 | **Architecture Finalized** | Pre-work | Skill architecture (§3) is reviewed, approved, and frozen. No new artifacts added after this point. |
| M2 | **Knowledge Distillation Complete** | Phase 1 | All 12 rule files extracted, validated, cross-checked for duplicates and conflicts. Under 1,200 lines total. |
| M3 | **Example Library Complete** | Phase 2, 3 | All 7 examples curated and annotated. Cross-references built. Under 550 lines total for references + examples. |
| M4 | **Prompt Library Complete** | Phase 4 | All 13 prompts written and load-tested for token budget. Under 1,200 lines total. |
| M5 | **Review System Complete** | Phase 5, 6 | All 3 checklists built. Index constructed. Agent can self-validate output. Under 350 lines total. |
| M6 | **Runtime Package Complete** | Phase 7 | All artifacts assembled. Skill loads on target platform. README written. |
| M7 | **Skill Ready for Validation** | Phase 8 | Validation test cases defined. Validation environment ready. |
| M8 | **Validation Complete** | Phase 8 | All tests pass. Token budget confirmed. Zero conflicts. Determinism verified. |
| M9 | **Production Ready** | Phase 9 | Skill published. First production task scheduled. |

---

## 6. Deliverables

### Phase 1 Deliverables

| File | Purpose | Est. Size | Required Inputs | Completion Criteria |
|---|---|---|---|---|
| `rules/constitution.json` | Immutable engineering laws | ~100 lines | Doctrine §§2,12,15 | Every "never" rule from Doctrine captured. Every law verifiable. |
| `rules/architecture.json` | Component ownership, boundaries | ~150 lines | domain-architecture.md, project-architecture.md, game-flow-architecture.md | Covers all component types. Every anti-pattern has a prevention rule. |
| `rules/state-machine.json` | State design rules | ~100 lines | state-machine-architecture.md | Covers state types, transitions, args, zombie, _no_notify. |
| `rules/actions.json` | Action handler rules | ~100 lines | action-architecture.md | Covers all 5 action responsibilities. Under-15-line rule enforced. |
| `rules/persistence.json` | DB + globals rules | ~100 lines | persistence-architecture.md | Covers schema design, queries, transactions, globals, undo logging. |
| `rules/notifications.json` | Notification design rules | ~100 lines | notification-patterns.md | Covers public/private, payload, i18n, sequencing, undo interaction. |
| `rules/client.json` | Client architecture rules | ~100 lines | client-ui-architecture.md | Covers Manager pattern, widgets, BgaCards, action buttons. |
| `rules/synchronization.json` | Reconnect + spectator rules | ~80 lines | client-synchronization-architecture.md | Covers getAllDatas, refreshUI, notification replay, spectator filtering. |
| `rules/animations.json` | Animation system rules | ~80 lines | animation-architecture.md | Covers queue, fast-mode, promise architecture, reconnect behaviour. |
| `rules/testing.json` | Test strategy rules | ~80 lines | testing-debugging-architecture.md | Covers test hierarchy, PHPUnit setup, replay testing, zombie testing. |
| `rules/undo-replay.json` | Undo + replay rules | ~80 lines | game-flow-architecture.md, persistence-architecture.md | Covers log tables, checkpoints, LIFO undo, replay determinism. |
| `rules/migration.json` | Migration-specific rules | ~80 lines | Doctrine §10, all standards' migration sections | Covers extraction order, safety rules, legacy → modern mapping. |

### Phase 2 Deliverables

| File | Purpose | Est. Size | Required Inputs | Completion Criteria |
|---|---|---|---|---|
| `references/reference-project-matrix.json` | Which project to consult for what | ~80 lines | reference-project-analysis.md | Covers all 20+ implementation problems. Each entry cites specific file. |
| `references/anti-patterns.json` | Condensed anti-pattern catalog | ~100 lines | All standards' anti-pattern sections | Each anti-pattern cross-references prevention rule IDs. |
| `references/migration-mapping.json` | Legacy → modern mapping table | ~60 lines | Doctrine §10 | Covers all rows in the legacy → modern table. |

### Phase 3 Deliverables

| File | Purpose | Est. Size | Required Inputs | Completion Criteria |
|---|---|---|---|---|
| `examples/manager-example.json` | Canonical Manager | ~60 lines | Reference projects, architecture rules | Passes all ARCH rules. Annotations explain design decisions. |
| `examples/action-example.json` | Canonical action handler | ~30 lines | Reference projects, action rules | Under 15 lines. Demonstrates all 5 responsibilities. |
| `examples/model-example.json` | Canonical Model | ~30 lines | Reference projects, architecture rules | Immutable. Computed properties. No DB access. |
| `examples/notification-example.json` | Canonical notification | ~25 lines | Reference projects, notification rules | Centralized static method. updateArgs() pattern. |
| `examples/state-example.json` | Canonical State class | ~30 lines | Reference projects, state-machine rules | Includes args, action, transitions, zombie. |
| `examples/client-manager-example.json` | Canonical client Manager | ~40 lines | Reference projects, client rules | Constructor injection. Notification handlers. |
| `examples/undo-example.json` | Canonical undo flow | ~40 lines | Reference projects (Earth), undo-replay rules | Log → execute → checkpoint → reverse. LIFO. |

### Phase 4 Deliverables

| File | Purpose | Est. Size | Completion Criteria |
|---|---|---|---|
| `prompts/migrate-manager.md` | Extract Manager from legacy | ~100 lines | Loads <3K tokens. Agent produces correct Manager without corrections. |
| `prompts/migrate-state.md` | Convert states to classes | ~100 lines | Loads <3K tokens. Agent produces correct State class. |
| `prompts/migrate-notifications.md` | Extract Notifications class | ~80 lines | Loads <3K tokens. Agent centralizes all notification calls. |
| `prompts/migrate-client.md` | Convert to ES modules + BgaCards | ~100 lines | Loads <3K tokens. Agent produces correct client structure. |
| `prompts/review-action.md` | Review action handler | ~80 lines | Loads <3K tokens. Catches all intentional errors in test input. |
| `prompts/review-manager.md` | Review Manager class | ~80 lines | Loads <3K tokens. Catches all intentional errors in test input. |
| `prompts/review-state-machine.md` | Review state machine | ~80 lines | Loads <3K tokens. Catches all intentional errors in test input. |
| `prompts/review-notifications.md` | Audit notification system | ~80 lines | Loads <3K tokens. Catches all intentional errors in test input. |
| `prompts/review-persistence.md` | Audit DB schema + globals | ~80 lines | Loads <3K tokens. Catches all intentional errors in test input. |
| `prompts/review-full.md` | Full pre-release audit | ~120 lines | Loads <3K tokens per tier. Covers all BGA Studio requirements. |
| `prompts/debug-session.md` | Debugging workflow | ~80 lines | Follows the 8-step debugging doctrine. Deterministic. |
| `prompts/new-feature.md` | Add new feature end-to-end | ~120 lines | Covers DB → Manager → Action → Notification → Client. |
| `prompts/refactor-module.md` | Refactor to standards | ~100 lines | Follows extraction order from Doctrine §8. |

### Phase 5 Deliverables

| File | Purpose | Est. Size | Completion Criteria |
|---|---|---|---|
| `checklists/pre-commit.json` | Pre-write validation | ~50 lines | Max 10 items. Every item verifiable. Every fail has fix instruction. |
| `checklists/pre-review.json` | Pre-completion validation | ~60 lines | Max 15 items. Covers all task domains. |
| `checklists/pre-release.json` | Full BGA release readiness | ~80 lines | Max 20 items. Mirrors official BGA checklist as verifiable rules. |

### Phase 6 Deliverables

| File | Purpose | Est. Size | Completion Criteria |
|---|---|---|---|
| `index.json` | Master task-to-artifact mapping | ~150 lines | Every task has entry. Every entry specifies exact rules/files. Under 450 tokens. |

### Phase 7 Deliverables

| File | Purpose | Est. Size | Completion Criteria |
|---|---|---|---|
| `skill.json` | Skill manifest | ~200 lines | Valid JSON. Declares version, capabilities, loading instructions. |
| `README.md` | Integration documentation | ~100 lines | Explains install, load, and use. Under 100 lines. |

---

## 7. Validation Strategy

### 7.1 Per-Phase Validation

| Phase | Completeness | Consistency | Duplication | Runtime Usefulness | Token Efficiency | Agent Usability |
|---|---|---|---|---|---|---|
| **Phase 1** | Every standard's rules extracted. Anti-pattern cross-check: every anti-pattern has ≥1 prevention rule. | Cross-file rule comparison script: no contradictory rules. Priority ordering resolves all conflicts. | Rule hash comparison: no rule appears in multiple files (except constitution which is intentionally redundant). | Every rule has concrete `check` field. Agent can execute the check. | Token count per file tracked. Phase 1 total under 1,200 lines. | N/A (rules don't execute standalone) |
| **Phase 2** | Matrix covers all 20+ problem types from reference analysis. | Matrix entries validated against actual reference project file structure. | No overlap between matrix and migration mapping. | Agent can resolve "How to implement X?" → matrix lookup → file reference in <2 steps. | Under 250 lines total. | N/A |
| **Phase 3** | One example per component type. | Examples validated against all relevant rules. Zero rule violations. | No example duplicates another's pattern. | Agent can use example as template. 1:1 correspondence to rules. | Under 300 lines total. Each example under 60 lines. | Agent can reproduce example from rules alone. |
| **Phase 4** | One prompt per task type. All migration + review tasks covered. | Prompt instructions consistent with referenced rules. No contradictory guidance. | Task boundaries clear. No prompt duplicates another's scope. | Agent completes task without intervention. | Each prompt load <3K tokens. Full prompt/ total <1,200 lines. | Prompt load → task completion in one session. Agent self-corrects. |
| **Phase 5** | All rule violations detectable by automation are in checklists. | Checklist items consistent with rules. Every item cross-references a rule ID. | No duplicate checks across pre-commit/pre-review/pre-release. | Agent catches own errors. Intentional test errors caught 100%. | Pre-commit <50 lines. All checklists <200 lines. | Agent runs checklist autonomously before declaring done. |
| **Phase 6** | Every task has index entry. Every entry covers all required artifacts. | Index entries tested with actual prompts: loaded rules match prompt's referenced rules. | No orphaned tasks or artifacts. Every artifact referenced by ≥1 index entry. | Agent resolves task → loads index → loads artifacts in <3 steps. | Under 150 lines. | Agent finds correct artifacts without human direction. |
| **Phase 7** | All artifacts in package. README covers all entry points. | skill.json capabilities match actual artifact inventory. | N/A | Package installs and loads. | Total package under 3,600 lines. | Fresh agent installs and uses skill for simple task. |
| **Phase 8** | All prompts tested. All checklists run. Token budget verified. | Cross-validation: no contradictory guidance across any two artifacts. | Full package scan for duplicate rule text. | Test scenarios pass. Agent output is correct for all 13 prompts. | Token count confirmed with real tokenizer. <12K full load. | Agent completes real migration task. Human review finds ≤3 issues. |

### 7.2 Cross-Cutting Validation

**Completeness Audit:** After Phase 4, run a script that maps every source standard section to at least one rule, one example, or one prompt instruction. Any section with zero coverage is flagged.

**Consistency Audit:** After Phase 6, compare every rule across all files. If rule ARCH-003 in `architecture.json` and rule PERS-007 in `persistence.json` say different things about the same concern, flag it.

**Duplication Audit:** After Phase 7, hash every rule's `rule` field. Any hash collision means duplicated guidance. Deduplicate or justify.

**Self-Validation Smoke Test:** After Phase 8, feed the complete skill to a clean agent and ask it to review a deliberately broken code sample. Verify the agent catches every issue.

---

## 8. Risk Assessment

| # | Risk | Severity | Likelihood | Impact | Mitigation |
|---|---|---|---|---|---|
| R1 | **Over-documentation.** Skill includes too much reference material, exceeding token budget. | High | Medium | Skill unusable in production. Agents run out of context. | Strict token budget per file. Every artifact must justify inclusion. If it doesn't prevent a bug, cut it. |
| R2 | **Large context windows.** Even a single-task load uses too many tokens, leaving insufficient context for the agent's task. | High | Medium | Agent can't complete actual work after loading the skill. | Progressive disclosure via index.json. Single-task load must be <3K tokens. Tested with real tokenizer. |
| R3 | **Duplicated guidance.** Same rule appears in multiple files with slightly different wording, creating ambiguity. | Medium | High | Agent follows one version, violates another. Output inconsistent. | Rule hash comparison in Phase 1. Cross-check automation in Phase 8. Constitution is only redundant file (by design). |
| R4 | **Conflicting rules.** Two rules prescribe different approaches to the same situation. | High | Low | Agent paralyzed or produces contradictory output. | Priority ordering resolves all conflicts. Conflict detection script in Phase 1 cross-checks all rule files. |
| R5 | **Poor discoverability.** Agent can't find the right rule for the current situation without loading too many files. | Medium | Medium | Agent makes decisions without relevant rules loaded. | Index.json is the progressive-disclosure gateway. Flat rule structure with tagged domains. Tested in Phase 6. |
| R6 | **Knowledge drift.** BGA framework evolves, making some rules obsolete. | High | High (over time) | Skill produces guidance that violates updated BGA requirements. | Version field in skill.json. Source citations allow bulk updates. Scheduled review cycle (quarterly). |
| R7 | **Maintenance burden.** Updating a standard requires editing many skill files, discouraging updates. | Medium | Medium | Skill goes stale. Engineers avoid updates. | One rule file per domain. Adding a standard = adding one rule file + updating index.json. Max 3 files touched per standard update. |
| R8 | **Prompt fragility.** Prompts produce correct output for happy path but fail on edge cases. | High | Medium | Agent produces broken code for real-world scenarios. | Every prompt includes edge case section. Phase 8 tests with real legacy code. |
| R9 | **Example staleness.** Reference projects evolve, making examples obsolete. | Low | Low | Examples show outdated patterns. | Examples cite specific reference project versions. Minor pattern changes don't invalidate structural examples. |
| R10 | **Token measurement error.** Actual token usage exceeds estimates because different tokenizers count differently. | Medium | Low | Skill rejected by target platform for exceeding limits. | Measure with both gpt-4 and claude tokenizers during Phase 8. Report both counts. |
| R11 | **Checklist incompleteness.** Self-validation misses a class of common errors. | Medium | Medium | Agents ship code with known anti-patterns. | Cross-reference every anti-pattern with a checklist item. Test checklists against intentionally broken code. |
| R12 | **Platform lock-in.** Skill format is too tightly coupled to one agent platform. | Low | Medium | Can't port skill to different agent platforms. | Use portable formats (JSON for structured data, Markdown for prompts). Avoid platform-specific features. |

---

## 9. Suggested Development Order

The phases are designed to minimize rework:

```
Phase 1: Rule Distillation         ← Foundation. Everything depends on rules.
    │
    ├──→ Phase 2: Cross-References ← Needs rule IDs from Phase 1
    │
    ├──→ Phase 3: Example Curation ← Needs rules to validate examples against
    │
    ├──→ Phase 4: Prompt Engineering ← Needs rules + examples + checklists
    │         │
    │         └──→ Phase 5: Checklists ← Needs rule IDs; prompts reference them
    │                   │
    │                   └──→ Phase 6: Index ← Needs all artifacts mapped
    │                             │
    │                             └──→ Phase 7: Packaging ← Assembles everything
    │                                       │
    │                                       └──→ Phase 8: Validation ← End-to-end test
    │                                                 │
    │                                                 └──→ Phase 9: Release
```

**Within each phase:**

1. Review the phase specification in this roadmap
2. Create the first artifact in the phase
3. Validate it against the success criteria
4. Use the first artifact as a template for the remaining artifacts in the phase
5. Cross-validate all artifacts in the phase against each other
6. Mark the phase milestone complete

**Parallel work possible:**
- Phase 2 and Phase 3 can run in parallel (both depend only on Phase 1)
- Within Phase 1, individual rule files can be extracted in parallel (they're independent)
- Within Phase 4, individual prompts can be written in parallel (they share rules but are independent)

**Sequence to minimize rework:**

1. **Phase 1 first, always.** Rules are the foundation. Everything else references rule IDs. Changing a rule late forces updates to examples, prompts, and checklists.

2. **Phase 2 and 3 before Phase 4.** Prompts reference specific examples and cross-references. If examples or references change, prompts need updating.

3. **Phase 5 (checklists) before Phase 4 is complete.** Prompts invoke checklists. But checklists can be drafted earlier and finalized alongside prompts.

4. **Phase 6 last before packaging.** The index is the final integration point. Building it earlier risks rework as artifacts change.

5. **Phase 8 after everything.** Validation catches issues that require fixes in earlier phases. Do not validate incrementally and move on — validate the complete package.

---

## 10. Progress Tracker

### Legend
- Status: ⬜ Not Started | 🔄 In Progress | ✅ Complete | ⚠️ Blocked | ❌ Cancelled
- Each artifact also shows: files created, dependencies satisfied, completion date, validation notes

### Phase 1: Rule Distillation

| # | Artifact | Status | Files | Depends On | Completed | Notes |
|---|---|---|---|---|---|---|
| 1.1 | `rules/constitution.json` | ⬜ | 1 | — | — | |
| 1.2 | `rules/architecture.json` | ⬜ | 1 | — | — | |
| 1.3 | `rules/state-machine.json` | ⬜ | 1 | — | — | |
| 1.4 | `rules/actions.json` | ⬜ | 1 | — | — | |
| 1.5 | `rules/persistence.json` | ⬜ | 1 | — | — | |
| 1.6 | `rules/notifications.json` | ⬜ | 1 | — | — | |
| 1.7 | `rules/client.json` | ⬜ | 1 | — | — | |
| 1.8 | `rules/synchronization.json` | ⬜ | 1 | — | — | |
| 1.9 | `rules/animations.json` | ⬜ | 1 | — | — | |
| 1.10 | `rules/testing.json` | ⬜ | 1 | — | — | |
| 1.11 | `rules/undo-replay.json` | ⬜ | 1 | — | — | |
| 1.12 | `rules/migration.json` | ⬜ | 1 | — | — | |

**Phase 1 Milestone:** Knowledge Distillation Complete → ⬜

### Phase 2: Cross-Reference Construction

| # | Artifact | Status | Files | Depends On | Completed | Notes |
|---|---|---|---|---|---|---|
| 2.1 | `references/reference-project-matrix.json` | ⬜ | 1 | Phase 1 | — | |
| 2.2 | `references/anti-patterns.json` | ⬜ | 1 | Phase 1 | — | |
| 2.3 | `references/migration-mapping.json` | ⬜ | 1 | Phase 1 | — | |

**Phase 2 Milestone:** Cross-References Complete → ⬜

### Phase 3: Example Curation

| # | Artifact | Status | Files | Depends On | Completed | Notes |
|---|---|---|---|---|---|---|
| 3.1 | `examples/manager-example.json` | ⬜ | 1 | Phase 1 | — | |
| 3.2 | `examples/action-example.json` | ⬜ | 1 | Phase 1 | — | |
| 3.3 | `examples/model-example.json` | ⬜ | 1 | Phase 1 | — | |
| 3.4 | `examples/notification-example.json` | ⬜ | 1 | Phase 1 | — | |
| 3.5 | `examples/state-example.json` | ⬜ | 1 | Phase 1 | — | |
| 3.6 | `examples/client-manager-example.json` | ⬜ | 1 | Phase 1 | — | |
| 3.7 | `examples/undo-example.json` | ⬜ | 1 | Phase 1 | — | |

**Phase 3 Milestone:** Example Library Complete → ⬜

### Phase 4: Prompt Engineering

| # | Artifact | Status | Files | Depends On | Completed | Notes |
|---|---|---|---|---|---|---|
| 4.1 | `prompts/migrate-manager.md` | ⬜ | 1 | Phases 1-3, 5 | — | |
| 4.2 | `prompts/migrate-state.md` | ⬜ | 1 | Phases 1-3, 5 | — | |
| 4.3 | `prompts/migrate-notifications.md` | ⬜ | 1 | Phases 1-3, 5 | — | |
| 4.4 | `prompts/migrate-client.md` | ⬜ | 1 | Phases 1-3, 5 | — | |
| 4.5 | `prompts/review-action.md` | ⬜ | 1 | Phases 1-3, 5 | — | |
| 4.6 | `prompts/review-manager.md` | ⬜ | 1 | Phases 1-3, 5 | — | |
| 4.7 | `prompts/review-state-machine.md` | ⬜ | 1 | Phases 1-3, 5 | — | |
| 4.8 | `prompts/review-notifications.md` | ⬜ | 1 | Phases 1-3, 5 | — | |
| 4.9 | `prompts/review-persistence.md` | ⬜ | 1 | Phases 1-3, 5 | — | |
| 4.10 | `prompts/review-full.md` | ⬜ | 1 | Phases 1-3, 5 | — | |
| 4.11 | `prompts/debug-session.md` | ⬜ | 1 | Phases 1-3, 5 | — | |
| 4.12 | `prompts/new-feature.md` | ⬜ | 1 | Phases 1-3, 5 | — | |
| 4.13 | `prompts/refactor-module.md` | ⬜ | 1 | Phases 1-3, 5 | — | |

**Phase 4 Milestone:** Prompt Library Complete → ⬜

### Phase 5: Checklist Construction

| # | Artifact | Status | Files | Depends On | Completed | Notes |
|---|---|---|---|---|---|---|
| 5.1 | `checklists/pre-commit.json` | ⬜ | 1 | Phase 1 | — | |
| 5.2 | `checklists/pre-review.json` | ⬜ | 1 | Phase 1 | — | |
| 5.3 | `checklists/pre-release.json` | ⬜ | 1 | Phase 1 | — | |

**Phase 5 Milestone:** Review System Complete → ⬜

### Phase 6: Index Construction

| # | Artifact | Status | Files | Depends On | Completed | Notes |
|---|---|---|---|---|---|---|
| 6.1 | `index.json` | ⬜ | 1 | Phases 1-5 | — | |

**Phase 6 Milestone:** Index Complete → ⬜

### Phase 7: Manifest & Packaging

| # | Artifact | Status | Files | Depends On | Completed | Notes |
|---|---|---|---|---|---|---|
| 7.1 | `skill.json` | ⬜ | 1 | Phases 1-6 | — | |
| 7.2 | `README.md` | ⬜ | 1 | Phases 1-6 | — | |

**Phase 7 Milestone:** Runtime Package Complete → ⬜

### Phase 8: Validation & QA

| # | Activity | Status | Depends On | Completed | Notes |
|---|---|---|---|---|---|
| 8.1 | Completeness audit | ⬜ | Phase 7 | — | |
| 8.2 | Consistency audit | ⬜ | Phase 7 | — | |
| 8.3 | Duplication audit | ⬜ | Phase 7 | — | |
| 8.4 | Token budget verification | ⬜ | Phase 7 | — | |
| 8.5 | Prompt testing (13 prompts) | ⬜ | Phase 7 | — | |
| 8.6 | Checklist testing (3 checklists) | ⬜ | Phase 7 | — | |
| 8.7 | End-to-end migration scenario | ⬜ | Phase 7 | — | |
| 8.8 | Determinism verification | ⬜ | Phase 7 | — | |
| 8.9 | Fix issues found | ⬜ | Phase 7 | — | |
| 8.10 | Re-validation | ⬜ | 8.9 | — | |

**Phase 8 Milestone:** Validation Complete → ⬜

### Phase 9: Production Release

| # | Activity | Status | Depends On | Completed | Notes |
|---|---|---|---|---|---|
| 9.1 | Git tag v1.0.0 | ⬜ | Phase 8 | — | |
| 9.2 | Platform publish | ⬜ | Phase 8 | — | |
| 9.3 | Release notes | ⬜ | Phase 8 | — | |
| 9.4 | First production task | ⬜ | 9.2 | — | |
| 9.5 | Feedback loop established | ⬜ | 9.4 | — | |

**Phase 9 Milestone:** Production Ready → ⬜

---

### Overall Progress

| Milestone | Status | Date |
|---|---|---|
| M1 — Architecture Finalized | ⬜ | — |
| M2 — Knowledge Distillation Complete | ⬜ | — |
| M3 — Example Library Complete | ⬜ | — |
| M4 — Prompt Library Complete | ⬜ | — |
| M5 — Review System Complete | ⬜ | — |
| M6 — Runtime Package Complete | ⬜ | — |
| M7 — Skill Ready for Validation | ⬜ | — |
| M8 — Validation Complete | ⬜ | — |
| M9 — Production Ready | ⬜ | — |

---

## Appendix A: Source Document → Rule File Mapping

| Source Document | Primary Rule File(s) | Lines (Source) |
|---|---|---|
| `foundation/bga-developer-handbook.md` | constitution, architecture, all domain rules (reference) | 752 |
| `foundation/bga-ai-implementation-reference.md` | constitution, testing | 639 |
| `foundation/reference-project-analysis.md` | references/reference-project-matrix.json | 301 |
| `ai-os/bga-senior-engineer-doctrine.md` | constitution, migration, all rules (authority) | 400 |
| `standards/domain-architecture.md` | architecture, persistence | 2,839 |
| `standards/project-architecture.md` | architecture, client | 1,491 |
| `standards/state-machine-architecture.md` | state-machine | 1,874 |
| `standards/persistence-architecture.md` | persistence, undo-replay | 2,145 |
| `standards/notification-patterns.md` | notifications, synchronization | 1,435 |
| `standards/game-flow-architecture.md` | architecture, undo-replay | 1,442 |
| `standards/action-architecture.md` | actions, undo-replay | 2,295 |
| `standards/client-ui-architecture.md` | client | 2,332 |
| `standards/client-synchronization-architecture.md` | synchronization | 1,758 |
| `standards/animation-architecture.md` | animations | 1,921 |
| `standards/testing-debugging-architecture.md` | testing | 2,226 |
| `patterns/game-mechanics-pattern-catalog.md` | all domain rules (mechanic-specific checks) | 1,840 |

---

## Appendix B: Decision Log

This section is populated as decisions are made during implementation.

| Date | Decision | Rationale | Impact |
|---|---|---|---|
| — | — | — | — |

---

*End of roadmap. This document is the master plan. All implementation work references this document. Update the Progress Tracker (§10) as each phase and milestone completes.*
