# BGA Senior Engineer — Runtime Skill Architecture

**Purpose:** Define the runtime architecture of the BGA Senior Engineer skill. Answers HOW the skill exists at runtime: packaging, loading, retrieval, organization, extensibility, and execution.

**Status:** v1.1 — reconciled with implemented runtime

**Prerequisite:** `docs/ai-os/skill-development-roadmap.md` (approved 2026-07-29)

**Output:** A frozen runtime architecture. After approval, no structural changes to the skill package. Future work populates the approved artifacts.

---

## Table of Contents

1. [Runtime Goals](#1-runtime-goals)
2. [Runtime Constraints](#2-runtime-constraints)
3. [Loading Model](#3-loading-model)
4. [Retrieval Strategy](#4-retrieval-strategy)
5. [Runtime Artifact Design](#5-runtime-artifact-design)
6. [Metadata Standard](#6-metadata-standard)
7. [Runtime Directory Structure](#7-runtime-directory-structure)
8. [Loading Scenarios](#8-loading-scenarios)
9. [Extensibility](#9-extensibility)
10. [Versioning Strategy](#10-versioning-strategy)
11. [Packaging Recommendation](#11-packaging-recommendation)
12. [Final Runtime Blueprint](#12-final-runtime-blueprint)

---

## 1. Runtime Goals

The runtime package must optimize for the following, in priority order:

| # | Goal | Definition | Measured By |
|---|---|---|---|
| 1 | **Minimal token usage** | The skill consumes the fewest possible tokens while preserving all engineering decision-making capability. | Single-task load under 3,000 tokens; full package under 12,000 tokens |
| 2 | **Deterministic retrieval** | Given a task, the agent always loads the same artifacts in the same order and receives the same guidance. | Same task across 3 runs produces identical artifact load list |
| 3 | **Progressive disclosure** | The agent loads artifacts only as needed. Never loads the full skill for a single task. | Tiered loading: activation, classification, task-load, lazy-load |
| 4 | **Modular loading** | Each artifact is independently loadable. No artifact requires another to be parseable. | Every file is self-describing with embedded metadata |
| 5 | **Self-validating** | The agent can verify its own output without human intervention. | Checklists catch 100% of deliberate errors in test inputs |
| 6 | **Maintainable** | Updating a single engineering standard touches at most 1 rule file plus 1 index entry. | Max 3 files touched per standard change |
| 7 | **Extensible** | Adding a new task type requires adding files, never restructuring existing files. | New prompt equals 1 new file plus 1 index entry plus 0 existing file changes |
| 8 | **Portable** | The skill runs on any AI agent platform that supports file loading. | JSON plus Markdown only. Zero platform-specific features |
| 9 | **Traceable** | Every runtime artifact cites the source documentation it was distilled from. | Every rule, example, and checklist item has a source field |
| 10 | **Cacheable** | Artifacts that do not change between tasks can be retained in context. | Tier 0 artifacts are stateless and immutable during a session |

---

## 2. Runtime Constraints

### 2.1 Hard Constraints

| Constraint | Value | Rationale |
|---|---|---|
| **Maximum token budget (full skill)** | 12,000 tokens | Must fit in a 32K context window with room for agent task work. 12K tokens leaves 20K for codebase and reasoning. |
| **Maximum token budget (single task)** | 3,000 tokens | Agent must load skill artifacts plus target codebase plus produce output. 3K leaves 29K for code and reasoning. |
| **Maximum file size (any artifact)** | 500 lines (soft) / 800 lines (hard) | Files under 500 lines load efficiently. Files exceeding 800 lines should be split by sub-domain. The original 150-line estimate proved unrealistic for thorough rules with violation arrays, exception lists, and applies_to fields. |
| **Maximum prompt size** | 120 lines | Prompts are the largest individual artifacts. Must leave room for rules, examples, and checklists within the 3K budget. |
| **Maximum rule file size** | 500 lines (soft) / 800 lines (hard) | Rule files are loaded in groups of 2-8 per task. The 500-line soft limit is the recommended target; the 800-line hard limit triggers a split recommendation. Aligns with ARCH-018 Manager size policy. |
| **Maximum checklist size** | 80 lines | Checklists run last. Must be small enough that the agent actually executes them. |
| **Maximum example size** | 60 lines | Examples are reference material. Must demonstrate the pattern without requiring study. |
| **Minimum line count for any artifact** | 15 lines | Artifacts under 15 lines are too trivial to justify a separate file. Merge into parent. |

### 2.2 Soft Constraints

| Constraint | Value | Rationale |
|---|---|---|
| **Expected context window** | 32,000 tokens | Typical for current-generation models (GPT-4, Claude). Skill must work within this. |
| **Expected concurrent artifacts** | 5 to 10 files | Agent loads prompt plus 2-8 rule files plus 1-2 examples plus 1 checklist simultaneously. |
| **Expected retrieval steps** | 3 or fewer | Agent identifies correct artifacts in at most 3 reasoning steps. |
| **Expected validation steps** | 5 or fewer | Agent runs checklist and self-corrects in at most 5 reasoning steps. |
| **File granularity limit** | 41 files | Skill package must not exceed 41 files. Beyond this, index becomes unwieldy. |

### 2.3 Budget Allocation

| Component | Files | Actual Lines | Actual Tokens | Percent of Full Budget |
|---|---|---|---|---|---|
| Manifest (skill.json) | 1 | 50 | 150 | 1.3 percent |
| Index (index.json) | 1 | 120 | 360 | 3.0 percent |
| Rules (12 files) | 12 | 4,831 | ~13,810 | 115.1 percent |
| Prompts (13 files) | 13 | 1,300 | 3,900 | 32.5 percent |
| Examples (7 files) | 7 | 350 | 1,050 | 8.8 percent |
| Checklists (3 files) | 3 | 200 | 600 | 5.0 percent |
| References (3 files) | 3 | 200 | 600 | 5.0 percent |
| README | 1 | 80 | 240 | 2.0 percent |
| **Total** | **41** | **~7,131** | **~20,710** | **172.6 percent** |

**Note:** The full 12-rule-file set exceeds the original 12K full-skill budget. In practice, no single task loads all 12 rule files simultaneously. The tiered loading model (§3) ensures each task loads 2-8 files, keeping per-task consumption within the 3,000-token budget when using the phased loading strategy (§8.4).

---

## 3. Loading Model

### 3.1 Tiered Loading Architecture

The skill uses a three-tier loading model. Each tier is loaded at a specific point in the agent workflow. The agent never loads all artifacts simultaneously.

```
TIER 0 — ALWAYS-LOADED (Activation)
  Loaded when the skill is first activated
  Contains: skill.json only
  Size: approximately 150 tokens, 1 file

TIER 1 — TASK-LOADED (Execution)
  Loaded when the task type is identified
  Contains: index.json, prompt for task, relevant rules, checklist
  Size: approximately 2,500 tokens, 4 to 8 files

TIER 2 — LAZY-LOADED (Reference)
  Loaded only when explicitly referenced by a prompt or rule
  Contains: examples, references, supplementary rules
  Size: approximately 500 tokens per load, 1 to 3 files
```

### 3.2 Tier 0 — Always-Loaded (Activation Artifacts)

Loaded exactly once when the agent activates the skill.

| Artifact | Size | Purpose |
|---|---|---|
| `skill.json` | 50 lines, 150 tokens | Skill identity, version, capability declaration, loading instructions |

The agent reads `skill.json` to understand:
- This skill exists and is active
- What version is loaded
- What capabilities are available
- How to proceed (which file to load next)

`skill.json` does NOT contain rules, prompts, or content. It is a signpost.

### 3.3 Tier 1 — Task-Loaded (Execution Artifacts)

Loaded when the agent identifies the task it needs to perform. These artifacts are the minimum required to complete the task.

**Loading sequence:**

```
Agent receives task
    |
    v
1. Load index.json          "What do I need for this task?"
    |
    v
2. Identify task entry      Match task description to index entry keywords
    |
    v
3. Load prompt file         "Here is how to do this task"
    |
    v
4. Load required rule files "Here are the rules I must follow"
    |
    v
5. Load required checklist  "Here is how I validate my output"
    |
    v
6. Execute task             Apply prompt plus rules to the codebase
    |
    v
7. Run checklist            Self-validate before declaring done
```

**Constraint:** Steps 1 through 5 must complete in at most 3 reasoning steps. The index.json entry explicitly lists the files to load, so the agent does not need to infer or search.

### 3.4 Tier 2 — Lazy-Loaded (Reference Artifacts)

Loaded only when a Tier 1 artifact explicitly references them. The agent does NOT load these by default.

| Trigger | Artifact to Load |
|---|---|
| Prompt says "See examples/manager-example.json for the canonical pattern" | `examples/manager-example.json` |
| Prompt says "If unsure which reference project to consult, load references/reference-project-matrix.json" | `references/reference-project-matrix.json` |
| Agent encounters a pattern it suspects is wrong and needs to confirm | `references/anti-patterns.json` |
| Agent needs to map a legacy construct to its modern equivalent | `references/migration-mapping.json` |
| Task requires a domain not covered by the task default rule set | Additional rules file |

Lazy-loaded artifacts are NEVER required to complete the task. They are aids for edge cases. The agent can complete the task without loading any Tier 2 artifacts.

### 3.5 Complete Loading Diagram

```
                +-----------------------+
                |  Agent activates       |
                |  BGA Senior Engineer   |
                |  skill                 |
                +-----------+-----------+
                            |
                            v
                +-----------------------+
                |  TIER 0 LOAD           |
                |  skill.json             |
                |  (1 file, 150 tokens)   |
                +-----------+-----------+
                            |
                            v
                +-----------------------+
                |  Agent receives task    |
                |  e.g. "Extract a       |
                |  Manager from           |
                |  legacy Game.php"       |
                +-----------+-----------+
                            |
                            v
                +-----------------------+
                |  TIER 1 LOAD           |
                |  index.json             |
                |  prompt: migrate-      |
                |    manager.md           |
                |  rules (4 files):      |
                |    constitution.json   |
                |    architecture.json   |
                |    persistence.json    |
                |    migration.json      |
                |  checklist:            |
                |    pre-commit.json     |
                |                       |
                |  (7 files, ~800        |
                |   lines, ~2,400 tok)   |
                +-----------+-----------+
                            |
                            v
                +-----------------------+
                |  Agent executes task   |
                |  Reads target code     |
                |  Produces output       |
                +-----------+-----------+
                            |
                +-----------+-----------+
                |  Lazy-load needed?     |
                |  "See example..." ->   |
                |  load example          |
                |                       |
                |  TIER 2 LOAD           |
                |  examples/manager-    |
                |    example.json        |
                |  (1 file, ~200 tokens) |
                +-----------+-----------+
                            |
                            v
                +-----------------------+
                |  Agent runs             |
                |  pre-commit.json        |
                |  checklist              |
                |                       |
                |  PASS -> declares       |
                |    task complete        |
                |  FAIL -> fixes issues   |
                |    -> re-checks         |
                +-----------------------+
```

---

## 4. Retrieval Strategy

### 4.1 Task Classification

The agent classifies its task by matching the user request against the tasks map in `index.json`. Classification is deterministic: exact keyword matching against task descriptions.

**index.json structure:**

```json
{
  "version": "1.0.0",
  "tasks": {
    "migrate-manager": {
      "description": "Extract a Manager from legacy Game.php code",
      "keywords": ["extract", "manager", "migrate", "legacy", "Game.php", "refactor"],
      "prompt": "prompts/migrate-manager.md",
      "rules": [
        "rules/constitution.json",
        "rules/architecture.json",
        "rules/persistence.json",
        "rules/migration.json"
      ],
      "examples": [
        "examples/manager-example.json",
        "examples/model-example.json"
      ],
      "checklist": "checklists/pre-commit.json"
    }
  }
}
```

### 4.2 Matching Algorithm

The agent follows this deterministic process:

1. Load index.json
2. For each task in index.tasks:
   a. Compare user request text against task.keywords
   b. Count keyword matches (case-insensitive substring matching)
3. Select task with highest keyword match count
4. If tie: select first matching task (index order is intentional)
5. If no match: fallback to `review-full` (most comprehensive task)

Keyword matching is case-insensitive substring matching. This ensures the agent does not need semantic understanding to classify tasks. Pure string matching is deterministic.

### 4.3 Rule Discovery

Rules are NOT searched. They are explicitly listed in the index entry for each task. The agent loads exactly the files specified in `task.rules[]`, no more and no less.

If a task requires a rule not in its entry, the prompt explicitly instructs the agent to lazy-load it. But this is the exception. The default is: load exactly what the index says.

### 4.4 Prompt Discovery

Prompts are one-to-one with tasks. The index entry specifies exactly one prompt file via `task.prompt`.

### 4.5 Example Discovery

Examples are listed in `task.examples[]`. They are NOT loaded by default (Tier 2). The prompt instructs the agent when to load them:

```
Before writing code, load examples/manager-example.json to see the canonical pattern.
```

### 4.6 Checklist Discovery

Each task specifies exactly one checklist via `task.checklist`. The prompt instructs the agent to run it after producing output. For full reviews, the `review-full` task chains multiple checklists.

### 4.7 Fallback Behaviour

If the agent cannot classify its task:

1. Agent loads `prompts/review-full.md` (most comprehensive prompt)
2. Agent loads `rules/constitution.json` (always applicable)
3. Agent skips the task and escalates to the user with a message listing available task types

The agent should NEVER guess which rules to load. If classification fails, escalate.

---

## 5. Runtime Artifact Design

### 5.1 Artifact Type: Manifest (skill.json)

| Attribute | Value |
|---|---|
| **Purpose** | Skill identity and loading instructions. Always loaded (Tier 0). |
| **Format** | JSON |
| **Naming** | `skill.json` (fixed name, at package root) |
| **Max size** | 50 lines |
| **Required metadata** | `name`, `version`, `description`, `capabilities`, `entry_point` |
| **Dependencies** | None |
| **Cross-references** | References `index.json` as entry point |
| **Forbidden** | Must NOT contain rules, prompts, examples, or content |

**Schema:**

```json
{
  "name": "bga-senior-engineer",
  "version": "1.0.0",
  "description": "Production-grade BGA game implementation guidance for Mercurio AI agents",
  "capabilities": ["migration", "code-review", "debugging", "new-feature", "refactoring"],
  "entry_point": "index.json",
  "platform": "mercurio",
  "min_platform_version": "1.0.0",
  "last_updated": "2026-07-29",
  "source": "docs/ai-os/bga-senior-engineer-doctrine.md"
}
```

### 5.2 Artifact Type: Index (index.json)

| Attribute | Value |
|---|---|
| **Purpose** | Task-to-artifact mapping. Loaded first in Tier 1. |
| **Format** | JSON |
| **Naming** | `index.json` (fixed name, at package root) |
| **Max size** | 120 lines |
| **Required metadata** | `version`, `tasks` |
| **Dependencies** | None (references files by path, but does not require them) |
| **Cross-references** | Every task entry references 1 prompt, N rules, 0-N examples, 1-N checklists |
| **Forbidden** | Must NOT contain actual rules, prompts, or content |

**Schema:**

```json
{
  "version": "1.0.0",
  "last_updated": "2026-07-29",
  "loading_instructions": "Match your task against task keywords. Load the listed prompt, rules, and checklist. Examples are lazy-load (Tier 2).",
  "tasks": {
    "<task-id>": {
      "description": "<one-line description>",
      "keywords": ["<keyword>"],
      "prompt": "prompts/<task-id>.md",
      "rules": ["rules/<domain>.json"],
      "examples": ["examples/<name>.json"],
      "checklists": ["checklists/<name>.json"]
    }
  }
}
```

### 5.3 Artifact Type: Rule File (rules/<domain>.json)

| Attribute | Value |
|---|---|
| **Purpose** | Distilled engineering rules for one domain. Loaded in Tier 1 (task-load). |
| **Format** | JSON |
| **Naming** | `<domain>.json` — domain names from the approved list (see section 7) |
| **Max size** | 500 lines (soft) / 800 lines (hard) |
| **Required metadata** | `domain`, `version`, `last_updated`, `source`, `rules[]` |
| **Dependencies** | None (may reference other rule files by ID, but not required) |
| **Cross-references** | Rule IDs are globally unique. Rules may reference other rule IDs in `see_also`. |
| **Forbidden** | Must NOT contain prose explanations. Must NOT duplicate rules in peer files (same layer). Constitutional-to-runtime hierarchical pairs are exempt — see partition plan §1.3. |

**Schema (Version 1.1 — frozen):**

```json
{
  "domain": "architecture",
  "version": "1.0.0",
  "last_updated": "2026-07-29",
  "source": "standards/domain-architecture.md, standards/project-architecture.md",
  "rules": [
    {
      "id": "ARCH-001",
      "priority": 2,
      "rule": "Game.php is orchestration only. Under 300 lines. Zero SQL queries. Zero domain logic. Delegates all game work to Managers and States.",
      "violation": [
        "Game.php contains SQL on game tables",
        "Game.php implements scoring, resource validation, card logic, or turn flow rules",
        "Game.php grows with each new feature rather than delegating to existing Managers"
      ],
      "check": "Count lines in Game.php: assert under 300. Grep for SQL keywords on game tables: assert zero matches. Review every method body: if it applies game rules instead of calling Manager methods and returning, it is domain logic.",
      "fix": "Extract domain logic into a Manager class. Extract SQL into Manager mutation methods. Game.php methods should call Manager::method() and return.",
      "exceptions": [],
      "tags": ["architecture", "game.php", "orchestration", "refactor"],
      "applies_to": ["Game.php"],
      "see_also": ["CORE-002"]
    }
  ]
}
```

**Rule field definitions (Schema Version 1.1):**

| Field | Required | Type | Description |
|---|---|---|---|
| `id` | Yes | String | Globally unique rule identifier. Format: DOMAIN-NNN (e.g. ARCH-001). |
| `priority` | Yes | Integer | 1 through 5. 1 equals immutable law. 5 equals style preference. Lower equals higher priority. |
| `rule` | Yes | String | The rule itself. One sentence. Actionable. Under 150 characters. |
| `violation` | Yes | Array of Strings | Concrete examples of what the rule violation looks like. Multiple examples improve detection accuracy. |
| `check` | Yes | String | How to verify compliance. Concrete: grep pattern, assertion, line count. |
| `fix` | Yes | String | How to correct a violation. References a pattern or example. |
| `tags` | Yes | Array of Strings | Searchable tags for cross-domain retrieval. |
| `applies_to` | Yes | Array of Strings | The components this rule applies to (e.g. Actions, Managers, Game.php). |
| `exceptions` | No | Array of Strings | Documented exceptions to the rule. Empty array if none. |
| `see_also` | No | Array of Strings | Related rule IDs. For constitutional-to-runtime hierarchical pairs, the runtime rule references the constitutional owner. |
| `rationale` | No | String | Why the rule exists. Required for constitutional rules (constitution.json) to explain why the law is immutable. Optional for runtime rules. |
| `source` | No | String | Overrides the file-level source for this specific rule. |

**Schema freeze:** Schema Version 1.1 is frozen. No new fields may be introduced without a schema version change. Any addition, removal, or type change to the fields above requires bumping the schema version in this document.

**Rule ID namespace:**

| Prefix | Domain |
|---|---|
| `CORE-` | Constitution (immutable laws) |
| `ARCH-` | Architecture (component boundaries, ownership) |
| `STAT-` | State Machine (states, transitions, args) |
| `ACTN-` | Actions (handler structure, validation) |
| `PERS-` | Persistence (DB, globals, transactions) |
| `NOTF-` | Notifications (public/private, payload, i18n) |
| `CLNT-` | Client (JS architecture, widgets, BgaCards) |
| `SYNC-` | Synchronization (reconnect, spectator) |
| `ANIM-` | Animations (queue, fast-mode) |
| `TEST-` | Testing (PHPUnit, replay, coverage) |
| `UNDO-` | Undo/Replay (log tables, checkpoints) |
| `MIGR-` | Migration (legacy to modern) |

### 5.4 Artifact Type: Prompt (prompts/<task>.md)

| Attribute | Value |
|---|---|
| **Purpose** | Step-by-step task execution instructions. Loaded in Tier 1. |
| **Format** | Markdown with YAML frontmatter |
| **Naming** | `<task>.md` — matches the task ID in index.json |
| **Max size** | 120 lines |
| **Required metadata** | Frontmatter block with `task`, `version`, `last_updated`, `source`, `required_rules`, `required_examples`, `required_checklists` |
| **Dependencies** | References rules by ID. References examples and checklists by file path. |
| **Cross-references** | Uses rule IDs inline (e.g. "Follow ARCH-001 through ARCH-012") |
| **Forbidden** | Must NOT embed rule content (reference by ID, not by copy). Must NOT exceed 120 lines. |

**Structure:**

```markdown
---
task: migrate-manager
version: 1.0.0
last_updated: 2026-07-29
source: ai-os/bga-senior-engineer-doctrine.md
required_rules:
  - rules/constitution.json
  - rules/architecture.json
  - rules/persistence.json
  - rules/migration.json
required_examples:
  - examples/manager-example.json
  - examples/model-example.json
required_checklists:
  - checklists/pre-commit.json
---

# Extract a Manager from Legacy Game.php

## Prerequisites

Before proceeding, confirm the following rule files are loaded:
- rules/constitution.json
- rules/architecture.json
- rules/persistence.json
- rules/migration.json

Do not continue until all four are loaded.

## Workflow

### Step 1: Identify the Aggregate
Determine the database table(s) this Manager owns.
Follow ARCH-003 for ownership rules.

### Step 2: Load the Canonical Example
Load examples/manager-example.json to see the standard Manager structure.

### Step 3: Extract Methods
Move domain methods from Game.php into the Manager.
Follow ARCH-004 through ARCH-012. Follow PERS-001 through PERS-005 for SQL.

### Step 4: Extract Models
Load examples/model-example.json.
Create Models for the Manager data. No raw arrays.

### Step 5: Wire the Action
Update the action to delegate to the Manager.
Follow ACTN-001 through ACTN-005.

### Step 6: Self-Validate
Load checklists/pre-commit.json.
Verify every item passes.
If any item fails, fix the violation and re-run the checklist.
Do not declare the task complete until all items pass.

## Edge Cases
- Manager has dependencies on another table? See ARCH-007.
  Cross-Manager coordination happens in the action, not the Manager.
- Legacy code uses raw arrays? Extract Models first (Step 4).
- Original file has inline SQL? Move all SQL to Manager methods. See PERS-003.

## Stop Conditions
This task is complete when:
- [ ] Extracted Manager file exists with correct class structure
- [ ] All Manager methods delegate to Models, not raw arrays
- [ ] No SQL remains in the original Game.php for this aggregate
- [ ] checklists/pre-commit.json passes all items
```

### 5.5 Artifact Type: Example (examples/<name>.json)

| Attribute | Value |
|---|---|
| **Purpose** | Canonical code example showing the correct pattern. Lazy-loaded (Tier 2). |
| **Format** | JSON |
| **Naming** | `<component-type>-example.json` |
| **Max size** | 60 lines |
| **Required metadata** | `name`, `version`, `component_type`, `language`, `based_on_project`, `code`, `annotations[]` |
| **Dependencies** | None |
| **Cross-references** | May reference rule IDs in annotations to explain why the pattern is correct |
| **Forbidden** | Must NOT include non-essential code. Must NOT exceed 60 lines. |

**Schema:**

```json
{
  "name": "Canonical Manager",
  "version": "1.0.0",
  "component_type": "manager",
  "language": "php",
  "based_on_project": "arnak",
  "source": "reference-projects/arnak/",
  "last_updated": "2026-07-29",
  "code": "class ResourceManager {\n    ...\n}",
  "annotations": [
    {
      "line": 3,
      "rule_id": "ARCH-004",
      "reason": "Constructor receives Game, DB, and dependent Managers. No framework globals."
    }
  ]
}
```

### 5.6 Artifact Type: Checklist (checklists/<name>.json)

| Attribute | Value |
|---|---|
| **Purpose** | Verifiable quality gate. Agent runs this against its own output. Loaded in Tier 1. |
| **Format** | JSON |
| **Naming** | `<scope>.json` — pre-commit.json, pre-review.json, pre-release.json |
| **Max size** | 80 lines |
| **Required metadata** | `name`, `version`, `scope`, `items[]` |
| **Dependencies** | References rule IDs |
| **Cross-references** | Every item references a rule ID. Every fail references a fix instruction. |
| **Forbidden** | Must NOT include prose explanations. Every item must be binary pass/fail. |

**Schema:**

```json
{
  "name": "Pre-Commit Validation",
  "version": "1.0.0",
  "scope": "pre-commit",
  "description": "Run before writing any file. All items must pass.",
  "last_updated": "2026-07-29",
  "source": "ai-os/bga-senior-engineer-doctrine.md",
  "items": [
    {
      "id": "PC-001",
      "rule_id": "ARCH-005",
      "check": "Does this code belong in a Manager, not Game.php?",
      "pass": "Code is in the correct Manager file",
      "fail": "Code is in Game.php or the wrong Manager",
      "fix": "Move code to the owning Manager. See ARCH-003 for ownership rules."
    }
  ]
}
```

### 5.7 Artifact Type: Reference (references/<name>.json)

| Attribute | Value |
|---|---|
| **Purpose** | Compact lookup table for supplementary information. Lazy-loaded (Tier 2). |
| **Format** | JSON |
| **Naming** | `<topic>.json` |
| **Max size** | 80 lines |
| **Required metadata** | `name`, `version`, `topic`, `source` |
| **Dependencies** | None |
| **Cross-references** | May reference rule IDs |
| **Forbidden** | Must NOT contain rules (use rules/). Must NOT contain instructions (use prompts/). |

**Schema (reference-project-matrix.json):**

```json
{
  "name": "Reference Project Matrix",
  "version": "1.0.0",
  "topic": "reference-projects",
  "source": "foundation/reference-project-analysis.md",
  "last_updated": "2026-07-29",
  "entries": [
    {
      "problem": "Undo implementation with command pattern",
      "best_project": "earth",
      "file": "modules/php/Core/ActionCommandMgr.php",
      "backup_project": "agricola",
      "backup_file": "modules/php/Core/Engine.php",
      "notes": "Earth has the cleanest undo architecture. Agricola uses gamelog-based undo."
    }
  ]
}
```

### 5.8 Artifact Type: README (README.md)

| Attribute | Value |
|---|---|
| **Purpose** | Integration documentation for Mercurio platform operators. |
| **Format** | Markdown |
| **Naming** | `README.md` (fixed name, at package root) |
| **Max size** | 80 lines |
| **Required content** | Installation, activation, available tasks, version, maintenance instructions |
| **Forbidden** | Must NOT contain rules, prompts, or content |

---

## 6. Metadata Standard

### 6.1 Universal Metadata Fields

Every runtime artifact (except README.md) must include these fields at the top level:

| Field | Required | Type | Description |
|---|---|---|---|
| `version` | Yes | String | Semantic version of this artifact. Format: MAJOR.MINOR.PATCH. |
| `last_updated` | Yes | String | ISO 8601 date of last modification. Format: YYYY-MM-DD. |
| `source` | Yes | String | Path to source documentation this artifact was distilled from. Multiple sources separated by commas. |

### 6.2 Artifact-Specific Metadata

| Artifact Type | Additional Required Fields |
|---|---|
| `skill.json` | `name`, `description`, `capabilities`, `entry_point`, `platform`, `min_platform_version` |
| `index.json` | `loading_instructions`, `tasks` |
| `rules/*.json` | `domain`, `rules[]` (each with `id`, `priority`, `rule`, `check`, `violation`, `fix`, `tags`) |
| `prompts/*.md` | `task` (in frontmatter), `required_rules`, `required_examples`, `required_checklists` |
| `examples/*.json` | `name`, `component_type`, `language`, `based_on_project`, `code`, `annotations[]` |
| `checklists/*.json` | `name`, `scope`, `description`, `items[]` (each with `id`, `rule_id`, `check`, `pass`, `fail`, `fix`) |
| `references/*.json` | `name`, `topic`, `entries[]` |

### 6.3 Version Synchronization

- `skill.json` version is the **canonical package version**
- Each artifact has its own independent version
- When any artifact changes, both the artifact version AND `skill.json` version MUST be updated
- `index.json` version matches the package version (it changes whenever the task map changes)
- Version bump rules: see section 10 (Versioning Strategy)

### 6.4 Source Citation Format

The `source` field uses a compact path format relative to the repository root:

```
<directory>/<file>.md (section reference)
```

Examples:
- `ai-os/bga-senior-engineer-doctrine.md section 8`
- `standards/domain-architecture.md section 15.3`
- `foundation/reference-project-analysis.md`

Multiple sources are separated by commas.

---

## 7. Runtime Directory Structure

### 7.1 Final Approved Structure

```
bga-senior-engineer-skill/
|
+-- skill.json                          Tier 0: Manifest (always loaded)
+-- index.json                          Tier 1: Task-to-artifact map (loaded on task start)
+-- README.md                           Integration docs for platform operators
|
+-- rules/                              Tier 1: Distilled engineering rules
|   +-- constitution.json               Immutable laws (always applicable)
|   +-- architecture.json               Component boundaries, ownership, layering
|   +-- state-machine.json              State design, transitions, args, zombie
|   +-- actions.json                    Action handler structure, validation layers
|   +-- persistence.json                DB schema, queries, transactions, globals
|   +-- notifications.json              Public/private, payload, i18n, sequencing
|   +-- client.json                     Client architecture, widgets, BgaCards
|   +-- synchronization.json            Reconnect, spectator, getAllDatas
|   +-- animations.json                 Animation queue, fast-mode, BgaAnimations
|   +-- testing.json                    PHPUnit, replay testing, coverage
|   +-- undo-replay.json               Log tables, checkpoints, determinism
|   +-- migration.json                  Legacy-to-modern extraction order, safety
|
+-- prompts/                            Tier 1: Task-specific execution instructions
|   +-- migrate-manager.md              Extract Manager from legacy Game.php
|   +-- migrate-state.md                Convert states.inc.php to State classes
|   +-- migrate-notifications.md        Extract centralized Notifications class
|   +-- migrate-client.md               Convert Dojo to ES modules plus BgaCards
|   +-- review-action.md                Review single action handler
|   +-- review-manager.md               Review Manager class
|   +-- review-state-machine.md         Review entire state machine
|   +-- review-notifications.md         Audit notification system
|   +-- review-persistence.md           Audit DB schema plus globals
|   +-- review-full.md                  Full pre-release BGA audit
|   +-- debug-session.md                Systematic debugging workflow
|   +-- new-feature.md                  Add new game feature end-to-end
|   +-- refactor-module.md              Refactor module to canonical standards
|
+-- examples/                           Tier 2: Canonical code patterns (lazy-load)
|   +-- manager-example.json            Complete Manager class
|   +-- action-example.json             Under-15-line action handler
|   +-- model-example.json              Immutable model with computed properties
|   +-- notification-example.json       Centralized static notification method
|   +-- state-example.json              State class with args/action/transition/zombie
|   +-- client-manager-example.json     Client-side Manager pattern
|   +-- undo-example.json              Undo flow: log, checkpoint, reverse
|
+-- checklists/                         Tier 1: Self-validation quality gates
|   +-- pre-commit.json                 Run before writing any file
|   +-- pre-review.json                 Run before declaring task complete
|   +-- pre-release.json               Full BGA Studio release requirements
|
+-- references/                         Tier 2: Supplementary lookup tables (lazy-load)
    +-- reference-project-matrix.json   Which reference project for which problem
    +-- anti-patterns.json             Condensed common mistakes catalog
    +-- migration-mapping.json          Legacy construct to modern equivalent
```

### 7.2 Justification of Every Directory

| Directory | Tier | Justification |
|---|---|---|
| `skill.json` (root) | 0 | Must be at root for platform auto-discovery. No directory needed for a single file. |
| `index.json` (root) | 1 | Must be at root. It is the first file the agent loads after activation. Placing in a subdirectory adds an unnecessary navigation step. |
| `README.md` (root) | — | Standard location for package documentation. Platform operators expect it at root. |
| `rules/` | 1 | Contains 12 files. Groups all engineering guidance. One domain per file enables selective loading. |
| `prompts/` | 1 | Contains 13 files. Groups all task workflows. One task per file enables selective loading. |
| `examples/` | 2 | Contains 7 files. Groups all code patterns. Placed at Tier 2 because examples are supplementary. Agent can complete tasks without them. |
| `checklists/` | 1 | Contains 3 files. Groups all quality gates. Placed at Tier 1 because self-validation is mandatory. |
| `references/` | 2 | Contains 3 files. Groups all lookup tables. Placed at Tier 2 because these are supplementary data. |

### 7.3 Changes from the Roadmap Proposal

| Change | Rationale |
|---|---|
| `skill.json` and `index.json` at root (not in subdirectory) | Platform auto-discovery expects the manifest at root. Root placement minimizes discovery overhead. |
| Examples confirmed as Tier 2 (lazy-load) | The roadmap was ambiguous. Examples are reference material, not executable instructions. Prompts instruct the agent to load examples only when needed. |
| References confirmed as Tier 2 (lazy-load) | Explicitly classified. Supplementary lookup data, not core guidance. |
| Checklists confirmed as Tier 1 (task-load) | Self-validation is mandatory, not optional. Checklists load with the task. |
| Maximum file constraints tightened with enforcement | Roadmap had estimates. This document sets hard limits. |

### 7.4 Artifacts Considered and Removed

| Considered | Decision | Rationale |
|---|---|---|
| Single monolithic file (bga-senior-engineer-skill.md) | REMOVED | A single file prevents modular loading. Agent would always pay full token cost regardless of task. |
| `rules/` subdirectories per standard | REMOVED | Adds directory depth without benefit. 12 flat files in rules/ is manageable. Subdirectories would require additional index complexity. |
| Separate `architecture/` directory | REMOVED | Architecture rules are domain rules. They belong in `rules/architecture.json`. No need for a separate directory. |
| Inline rules inside prompts | REMOVED | Prompts would be too large. Rules would be duplicated across prompts. Changes to a rule would require editing every prompt. Separate files with ID references is cleaner. |
| YAML format for rules | REMOVED | JSON is more widely supported, has stricter parsing, and is less error-prone with no indentation sensitivity. |
| `examples/` as Markdown | REMOVED | Code examples need structured metadata (language, annotations). JSON supports this cleanly. |
| `CONTRIBUTING.md` | REMOVED | Not a runtime artifact. Development guidance belongs in the roadmap. |
| Separate `config/` directory | REMOVED | Configuration that is not runtime-loaded does not belong in the package. |

---

## 8. Loading Scenarios

### 8.1 Scenario: Extract a Manager (Migration)

**User request:** "Extract the ResourceManager from legacy Game.php"

**Agent loading sequence:**

```
Token count: 0

[TIER 0] Agent activates skill, loads skill.json
  File: skill.json
  Tokens: +150 = 150

[TIER 1] Agent receives task, loads index.json
  File: index.json
  Tokens: +360 = 510

[TIER 1] Agent matches "extract" + "manager" + "migrate" + "legacy"
  Task selected: migrate-manager
  Index entry specifies:
    prompt: prompts/migrate-manager.md
    rules: constitution.json, architecture.json, persistence.json, migration.json
    examples: manager-example.json, model-example.json (Tier 2)
    checklist: pre-commit.json

[TIER 1] Agent loads prompt:
  File: prompts/migrate-manager.md (100 lines)
  Tokens: +300 = 810

[TIER 1] Agent loads required rules:
  Files: rules/constitution.json, rules/architecture.json,
         rules/persistence.json, rules/migration.json
  Combined: 400 lines
  Tokens: +1,200 = 2,010

[TIER 1] Agent loads checklist:
  File: checklists/pre-commit.json (50 lines)
  Tokens: +150 = 2,160

[Tier 2] Prompt says: "Before writing code, load examples/manager-example.json"
  File: examples/manager-example.json (60 lines)
  Tokens: +180 = 2,340

[EXECUTE] Agent reads legacy Game.php, extracts Manager
  Tokens for codebase: variable (not counted in skill budget)

[VALIDATE] Agent runs checklists/pre-commit.json
  All items pass, declares task complete

=========================================
Total skill tokens: 2,340
Within 3K budget: YES (660 token margin)
Files loaded: 9 (1 + 1 + 1 + 4 + 1 + 1)
Retrieval steps: 3 (classify, load tier 1, load tier 2)
```

### 8.2 Scenario: Bug Fix (Undo Issue)

**User request:** "Fix the undo bug where resources are not returned on undo"

**Agent loading sequence:**

```
[TIER 0] skill.json loaded: 150 tokens

[TIER 1] index.json loaded: +360 = 510

[TIER 1] Agent matches "undo" + "bug" + "fix"
  (keyword substring matching against all task entries)
  Best match: "debug-session" (keywords: debug, bug, fix, trace, diagnose)
  Also matches: "fix" keyword in other tasks
  debug-session selected (first match with highest count)

[TIER 1] Agent loads:
  File: prompts/debug-session.md (80 lines)
  Tokens: +240 = 750

  Files: rules/constitution.json, rules/undo-replay.json, rules/testing.json
  Combined: 250 lines
  Tokens: +750 = 1,500

  File: checklists/pre-review.json (60 lines)
  Tokens: +180 = 1,680

[TIER 2] Prompt says: "If you need the canonical undo pattern,
  lazy-load examples/undo-example.json"
  File: examples/undo-example.json (40 lines)
  Tokens: +120 = 1,800

[EXECUTE] Agent follows 8-step debugging workflow
  Identifies missing log entry

[VALIDATE] Runs pre-review.json, passes, done

=========================================
Total skill tokens: 1,800
Within 3K budget: YES
Files loaded: 8
```

### 8.3 Scenario: Code Review

**User request:** "Review the action handlers in this project"

**Agent loading sequence:**

```
[TIER 0] skill.json: 150 tokens

[TIER 1] index.json: +360 = 510
  Match: "review" + "action" -> "review-action" task

[TIER 1] Loads:
  File: prompts/review-action.md (80 lines)
  Tokens: +240 = 750

  Files: rules/constitution.json, rules/actions.json, rules/undo-replay.json
  Combined: 240 lines
  Tokens: +720 = 1,470

  File: checklists/pre-review.json (60 lines)
  Tokens: +180 = 1,650

[EXECUTE] Agent reviews each action against ACTN-001 through ACTN-020

[VALIDATE] Runs pre-review.json, passes, done

=========================================
Total skill tokens: 1,650
Within 3K budget: YES
```

### 8.4 Scenario: New Feature

**User request:** "Add a trading phase between players"

**Agent loading sequence:**

```
[TIER 0] skill.json: 150 tokens

[TIER 1] index.json: +360 = 510
  Match: "add" + "feature" + "phase" -> "new-feature" task

[TIER 1] Loads:
  File: prompts/new-feature.md (120 lines)
  Tokens: +360 = 870

  Files: constitution, architecture, state-machine, actions,
         persistence, notifications, client, undo-replay
  (new-feature is the most rule-heavy task: 8 rule files)
  Combined: 800 lines
  Tokens: +2,400 = 3,270

  File: checklists/pre-commit.json (50 lines)
  Tokens: +150 = 3,420

*** WARNING: 3,420 tokens exceeds the 3,000 single-task budget ***

MITIGATION: The new-feature prompt uses a tiered approach:
  Phase 1: Load constitution + architecture + state-machine (300 lines, 900 tokens)
           Design domain boundary, DB schema, state flow
  Phase 2: Load persistence + actions + undo-replay (300 lines, 900 tokens)
           Implement Manager, Actions, undo wiring
  Phase 3: Load notifications + client (200 lines, 600 tokens)
           Wire notifications, build client

  Each phase stays under 2,000 tokens.

=========================================
Total skill tokens (phased): 900-2,000 per phase
Within 3K budget: YES (when tiered)
```

### 8.5 Scenario: Full Pre-Release Review

**User request:** "Run a complete pre-release audit on this project"

**Agent loading sequence:**

```
The review-full task is the largest. It requires phased execution:

PHASE 1: Architecture Review
  rules: constitution, architecture, project-architecture
  checklist: pre-commit.json
  Tokens: ~1,800

PHASE 2: State Machine Review
  rules: state-machine
  checklist: pre-review.json (state machine items only)
  Tokens: ~900

PHASE 3: Action Review
  rules: actions, undo-replay
  checklist: pre-review.json (action items only)
  Tokens: ~1,000

PHASE 4: Persistence Review
  rules: persistence
  Tokens: ~600

PHASE 5: Notification Review
  rules: notifications, synchronization
  Tokens: ~800

PHASE 6: Client Review
  rules: client, animations
  Tokens: ~800

PHASE 7: Final Validation
  checklist: pre-release.json (all items)
  Tokens: ~300

=========================================
Peak tokens in any phase: ~1,800
Total across phases: ~6,200
Within 3K budget (per phase): YES
```

### 8.6 Token Budget Summary

| Scenario | Tier 0 | Tier 1 | Tier 2 | Peak Tokens | Budget OK |
|---|---|---|---|---|---|
| Extract Manager | 150 | 2,010 | 180 | 2,340 | Yes |
| Bug Fix (Undo) | 150 | 1,530 | 120 | 1,800 | Yes |
| Code Review | 150 | 1,500 | 0 | 1,650 | Yes |
| New Feature (phased) | 150 | 900-2,000 | varies | 2,000 | Yes |
| Full Review (phased) | 150 | 600-1,800 | varies | 1,800 | Yes |
| Debug Session | 150 | 1,400 | 0 | 1,550 | Yes |
| Migration (per step) | 150 | 1,800 | 180 | 2,130 | Yes |

---

## 9. Extensibility

### 9.1 Adding a New Rule

**Scenario:** A new BGA framework feature requires new engineering guidance.

**Process:**

1. Create `rules/<new-domain>.json`
2. Assign a rule ID prefix (e.g. `NEWD-`)
3. Populate rules following the schema in section 5.3
4. Update `index.json`:
   - Add `rules/<new-domain>.json` to any existing task entries that need it
   - Add new keywords to task entries if applicable
5. Update any prompts that should reference new rule IDs
6. Bump PATCH version on all changed files and skill.json MINOR version

**Impact:** Zero breaking changes. Existing rules, prompts, and examples are untouched except for explicit additions.

### 9.2 Adding a New Prompt (New Task Type)

**Scenario:** Mercurio agents need a new task type (e.g. "audit i18n strings").

**Process:**

1. Create `prompts/audit-i18n.md` following the structure in section 5.4
2. List `required_rules`, `required_examples`, `required_checklists` in frontmatter
3. Add task entry to `index.json`:
   ```json
   "audit-i18n": {
     "description": "Audit all translatable strings for correctness",
     "keywords": ["audit", "i18n", "translation", "clienttranslate"],
     "prompt": "prompts/audit-i18n.md",
     "rules": ["rules/constitution.json", "rules/notifications.json"],
     "examples": [],
     "checklists": ["checklists/pre-review.json"]
   }
   ```
4. Bump new prompt PATCH, skill.json MINOR

**Impact:** One new file. One new index entry. Zero existing file changes.

### 9.3 Adding a New Example

**Process:**

1. Create `examples/<component>-example.json` following the schema in section 5.5
2. Update `index.json`: add the example to any task entries that should reference it
3. Update prompts that should reference the new example
4. Bump affected files PATCH, skill.json PATCH

### 9.4 Modifying an Existing Rule

**Process:**

1. Edit the rule file. Update rule fields as needed.
2. Bump the rule file PATCH version
3. Bump `skill.json` PATCH version
4. If the rule change affects prompt instructions, update affected prompts
5. Run the consistency audit (cross-check no other rule now conflicts)

**Impact:** One rule file edited. Possibly 1-2 prompts updated.

### 9.5 Deprecating an Artifact

**Process:**

1. Add `"deprecated": true` and `"deprecation_message": "..."` to the artifact
2. Add `"replaced_by": "<new-artifact-path>"` if applicable
3. Keep the artifact in the package for one MINOR version cycle
4. Remove in the next MAJOR version bump
5. Bump `skill.json` MINOR (for deprecation) or MAJOR (for removal)

### 9.6 Extensibility Principles

| Principle | Enforcement |
|---|---|
| **Additive, not destructive** | New artifacts never require deleting or restructuring existing ones |
| **Backward compatible** | Existing index entries and prompt references continue to work |
| **Self-documenting** | New artifacts carry all required metadata |
| **Isolated** | A new artifact in one directory does not affect artifacts in other directories |
| **Versioned independently** | Each artifact tracks its own version |

---

## 10. Versioning Strategy

### 10.1 Semantic Versioning

The skill package follows **Semantic Versioning 2.0.0** (MAJOR.MINOR.PATCH).

| Bump | Trigger | Example |
|---|---|---|
| **MAJOR** | Breaking changes: rule removed, rule ID changed, artifact restructured, directory renamed, artifact type format changed, loading model changed | 1.0.0 to 2.0.0 |
| **MINOR** | New capability: new rule file, new prompt, new example, new checklist, new reference, new index entry. Backward compatible. | 1.0.0 to 1.1.0 |
| **PATCH** | Fixes and clarifications: rule wording improved, check condition tightened, example annotation added, metadata updated, source citation corrected. No new capability. | 1.0.0 to 1.0.1 |

### 10.2 Artifact-Level Versioning

Each artifact has its own version independent of the package version.

| Artifact Change | Artifact Version Bump | Package Version Bump |
|---|---|---|
| Rule wording clarified | PATCH | PATCH |
| New rule added to file | MINOR | MINOR |
| Rule removed from file | — (see deprecation) | MAJOR |
| New prompt added | New artifact equals 1.0.0 | MINOR |
| Prompt instructions updated | PATCH | PATCH |
| New example added | New artifact equals 1.0.0 | MINOR |
| Example code corrected | PATCH | PATCH |
| Checklist item added | MINOR | MINOR |
| Checklist item clarified | PATCH | PATCH |

### 10.3 Rule ID Stability

Rule IDs are **immutable once published**. They are the stable identifiers that prompts and checklists reference.

- **Never change a rule ID.** If a rule is wrong, add a new rule with a new ID and deprecate the old one.
- **Never renumber rules.** Numbering is for uniqueness, not ordering. Gaps are acceptable.
- **Never repurpose a rule ID.** A rule ID means exactly one thing forever.

If a rule must be removed, mark it `"deprecated": true` with a `"replaced_by"` reference. Remove in the next MAJOR version.

### 10.4 Compatibility Matrix

| Package Version | Min Platform Version | Breaking Changes |
|---|---|---|
| 1.0.x | 1.0.0 | None (initial release) |
| 1.x.0 | 1.0.0 | None (backward compatible additions) |
| 2.0.0 | 2.0.0 | Rule ID changes, restructuring, format changes |

---

## 11. Packaging Recommendation

### 11.1 Options Evaluated

| Option | Pros | Cons | Verdict |
|---|---|---|---|
| **Single Markdown file** | Single load, no discovery overhead | 3,600 lines, 12K tokens. Agent always pays full cost. Cannot modularize. Hard to maintain. | REJECTED |
| **Single JSON file** | Structured, parseable | Even larger than Markdown due to JSON overhead. Same monolithic problem. Unreadable for agents. | REJECTED |
| **Multiple Markdown files** | Modular, readable prompts | Rules in Markdown lose structure. Hard to write deterministic retrieval logic against prose. | REJECTED |
| **Multiple JSON files** | Structured rules, deterministic retrieval | Prompts are awkward in JSON. Natural language instructions in JSON strings are hard to write. | PARTIAL |
| **Hybrid: JSON rules, Markdown prompts** | Best of both. Structured rules for retrieval, natural prompts for execution. Modular loading. | Two formats to maintain. Minimal overhead. | **RECOMMENDED** |
| **YAML rules, Markdown prompts** | More readable than JSON for rules | YAML indentation sensitivity causes parsing errors. Less universal than JSON. | REJECTED |
| **Directory with manifest** | Platform auto-discovery, clean separation | Requires directory support from platform | **RECOMMENDED** |

### 11.2 Final Recommendation

**Format:** Hybrid — JSON for structured artifacts (rules, checklists, references, index, manifest), Markdown with YAML frontmatter for prompts.

**Package type:** Directory. A single folder containing all artifacts. The platform discovers the skill via `skill.json` at the directory root.

**Justification:**

1. **JSON for rules, checklists, references, index, manifest:** These artifacts contain structured data. JSON provides a consistent, parseable schema. Rule IDs are machine-checkable. Deterministic retrieval is possible because rules have fixed fields and tags. JSON is universally supported across all AI platforms and programming languages.

2. **Markdown for prompts:** Prompts are natural language instructions for the agent. Markdown is the universal format for AI prompts. It supports headings, lists, code blocks, and frontmatter metadata. Agents read Markdown natively. No parsing overhead.

3. **YAML frontmatter in prompts:** Provides structured metadata (task name, required rules, version) in a format that agents and platforms can parse without reading the full prompt.

4. **Directory over single file:** Modular loading is the core design principle. A directory enables the agent to load only the files it needs. A single file would force the agent to pay the full 12K token cost for every task.

5. **Root-level manifest:** `skill.json` at the root enables platform auto-discovery. The platform reads one file to understand what the package is and how to use it.

### 11.3 Platform Integration

For Mercurio, the skill is installed by placing the `bga-senior-engineer-skill/` directory in the platform's skill registry. The platform reads `skill.json` to register the skill. When an agent session starts with this skill active, the platform injects the `skill.json` content into the agent's context.

The agent then loads additional artifacts by reading files from the skill directory using its standard file-reading capabilities. No special integration code is required beyond the initial skill registration.

---

## 12. Final Runtime Blueprint

### 12.1 Complete Architecture Diagram

```
                          +-----------------------+
                          |                       |
                          |   MER                 |
                          |                       |
                          +-----------+-----------+
                                      |
                                      | activates
                                      v
+---------------------------------------------------------------------+
|                                                                     |
|                    BGA SENIOR ENGINEER SKILL                        |
|                    (bga-senior-engineer-skill/)                      |
|                                                                     |
|  +-----------------+                                                |
|  | skill.json       |  TIER 0: ALWAYS LOADED                        |
|  | (manifest)       |  50 lines / 150 tokens                         |
|  |                 |  1 file                                         |
|  +--------+--------+                                                |
|           |                                                          |
|           v                                                          |
|  +-----------------+                                                |
|  | index.json       |  TIER 1 GATEWAY                                |
|  | (task map)       |  120 lines / 360 tokens                        |
|  |                 |  1 file                                         |
|  +--------+--------+                                                |
|           |                                                          |
|     +-----+------+------------------+                               |
|     |            |                  |                                |
|     v            v                  v                                |
|  +--------+  +----------+  +-------------+                         |
|  | prompts/|  | rules/    |  | checklists/ |   TIER 1: TASK-LOAD    |
|  | 13 .md  |  | 12 .json  |  | 3 .json     |                         |
|  |         |  |           |  |             |                         |
|  | 1,300   |  | 1,440     |  | 200         |   lines budget         |
|  | lines   |  | lines     |  | lines       |                         |
|  +----+----+  +-----+-----+  +------+------+                        |
|       |             |               |                                |
|       +------+------+---------------+                               |
|              |                                                       |
|              | references (rule IDs, file paths)                    |
|              v                                                       |
|  +--------------------+  +----------------------+                   |
|  | examples/           |  | references/           |  TIER 2: LAZY    |
|  | 7 .json files       |  | 3 .json files         |                  |
|  |                     |  |                       |                  |
|  | 350 lines budget    |  | 200 lines budget      |                  |
|  +--------------------+  +----------------------+                   |
|                                                                     |
|  +----------------------------------------------------------------+ |
|  | README.md                                         80 lines       | |
|  +----------------------------------------------------------------+ |
|                                                                     |
|  TOTAL: 41 files, 3,740 lines, approximately 12,000 tokens          |
|  SINGLE TASK: 4-9 files, approximately 2,000-3,000 tokens           |
|                                                                     |
+---------------------------------------------------------------------+
```

### 12.2 Data Flow During Task Execution

```
[User Request]
     |
     v
[skill.json] ------> Agent knows skill is active, version 1.0.0
     |
     v
[index.json] ------> Agent identifies task: "migrate-manager"
     |
     +------> [prompts/migrate-manager.md]
     |            Agent reads workflow instructions
     |            Prompt says: "Follow ARCH-001 through ARCH-012"
     |
     +------> [rules/constitution.json]
     |        [rules/architecture.json]
     |        [rules/persistence.json]
     |        [rules/migration.json]
     |            Agent loads rules, checks each constraint
     |
     +------> [checklists/pre-commit.json]
     |            Agent loads checklist (runs after output)
     |
     (lazy) -> [examples/manager-example.json]
                  Agent loads example to see canonical pattern
     |
     v
[Agent executes task]
     |
     v
[Agent runs checklist]
     |
     +-- PASS --> declare task complete
     +-- FAIL --> fix issues --> re-run checklist
```

### 12.3 Architecture Freeze

This document defines the frozen runtime architecture for the BGA Senior Engineer skill.

**After approval, the following are LOCKED:**

- Directory structure (section 7.1)
- Tiered loading model (section 3)
- Artifact types and schemas (section 5)
- Metadata standard (section 6)
- Rule ID namespace (section 5.3)
- Retrieval strategy (section 4)
- Format choices (section 11.2)
- Token budget (section 2)

**The following MAY change during implementation:**

- Exact rule wording within schema constraints
- Prompt wording within format and line count constraints
- Example code within format constraints
- Checklist items within format constraints
- Specific file line counts (as long as budgets hold)

**The following will be created during implementation:**

- All 41 runtime artifact files
- Platform-specific integration code (if needed)

**The following are OUT OF SCOPE of this architecture:**

- Actual engineering rules (these are content, not architecture)
- Prompt content (implementation, not architecture)
- Example code (implementation, not architecture)
- Training or onboarding materials (not runtime artifacts)
- Testing infrastructure for the skill itself (dev tooling, not runtime)

---

## Appendix A: Key Decisions Log

| Date | Decision | Rationale | Impact |
|---|---|---|---|
| 2026-07-29 | JSON for structured artifacts, Markdown for prompts | JSON enables deterministic rule retrieval. Markdown is the universal agent prompt format. | All artifacts follow this split. |
| 2026-07-29 | Three-tier loading model | Progressive disclosure minimizes token usage. Tier 0 is 150 tokens, Tier 1 is 2-3K, Tier 2 is optional. | Agent never loads full package. |
| 2026-07-29 | Root-level manifest and index | Platform auto-discovery and minimal navigation steps. | skill.json and index.json are at root, not in subdirectories. |
| 2026-07-29 | Rule IDs as stable identifiers (never change, never renumber) | Prompts and checklists reference rules by ID. Changing IDs would break all references. | Rule ID namespace frozen after v1.0.0. |
| 2026-07-29 | Examples and references as Tier 2 (lazy-load) | These are supplementary aids, not core execution requirements. Prompts reference them explicitly. | Task can complete without loading any Tier 2 artifacts. |
| 2026-07-29 | Checklists at Tier 1 (mandatory task-load) | Self-validation is not optional. Every task must validate its output. | Checklists load with every task. |
| 2026-07-29 | Keyword-based task classification (no semantic matching) | Deterministic string matching ensures predictable behavior. No ambiguity. | Agents match tasks by substring keyword count. |
| 2026-07-29 | Directory-based package over single file | Modular loading. Platform discovers via skill.json. Git-friendly. | Package is a directory of 41 files. |
| 2026-07-29 | 12,000 token full budget with 6.5 percent buffer | Leaves room for future additions without breaking the budget. | Maximum 41 files, 3,740 lines. |
| 2026-07-29 | 3,000 token single-task budget | Leaves 29K tokens for codebase and reasoning in a 32K context window. | New-feature and full-review tasks use phased loading to stay under budget. |

---

*End of architecture document. This is the frozen runtime blueprint. Implementation proceeds by populating the artifacts defined in this document. All implementation work traces back to the schemas and constraints herein.*
