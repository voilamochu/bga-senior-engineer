# BGA Senior Engineer Skill — Manifest & Index Specification

**Date:** 2026-07-30
**Status:** APPROVED
**Version:** 1.0.0
**Authority:** BGA Senior Engineer — Runtime Specification v1.1

---

## Table of Contents

1. [Package Layout](#1-package-layout)
2. [Skill Manifest (`skill.json`)](#2-skill-manifest)
3. [Master Index (`index.json`)](#3-master-index)
4. [Validation Rules](#4-validation-rules)
5. [Backward Compatibility](#5-backward-compatibility)
6. [Extension Strategy](#6-extension-strategy)
7. [Future Validation](#7-future-validation)

---

## 1. Package Layout

### 1.1 Single Root

The BGA Senior Engineer Skill exists as a **single self-contained package** rooted at:

```
bga-senior-engineer-skill/
```

There is no second top-level `skill/` directory anywhere in the repository. All artifact paths in all documents, diagrams, tables, dependency graphs, and roadmaps use `bga-senior-engineer-skill/` as the root.

### 1.2 Final Directory Structure

```
bga-senior-engineer-skill/
├── skill.json                        Tier 0: Manifest (always loaded)
├── index.json                        Tier 1: Task-to-artifact map
├── README.md                         Integration documentation
│
├── rules/                            Tier 1: Distilled engineering rules
│   ├── constitution.json             Immutable laws
│   ├── architecture.json             Component boundaries and ownership
│   ├── state-machine.json            State design and transitions
│   ├── actions.json                  Action handler structure
│   ├── persistence.json              DB schema and globals
│   ├── notifications.json            Notification patterns
│   ├── client.json                   Client architecture
│   ├── synchronization.json          Reconnect and spectator
│   ├── animations.json               Animation system
│   ├── testing.json                  Test strategy
│   ├── undo-replay.json              Undo and replay integrity
│   └── migration.json                Legacy-to-modern extraction
│
├── prompts/                          Tier 1: Task-specific execution instructions
│   ├── migrate-manager.md            Extract Manager from legacy Game.php
│   ├── migrate-state.md              Convert states.inc.php to State classes
│   ├── migrate-notifications.md      Extract centralized Notifications class
│   ├── migrate-client.md             Convert Dojo to ES modules
│   ├── review-action.md              Review single action handler
│   ├── review-manager.md             Review Manager class
│   ├── review-state-machine.md       Review entire state machine
│   ├── review-notifications.md       Audit notification system
│   ├── review-persistence.md         Audit DB schema and globals
│   ├── review-full.md                Full pre-release audit
│   ├── debug-session.md              Systematic debugging workflow
│   ├── new-feature.md                Add new game feature end-to-end
│   └── refactor-module.md            Refactor module to canonical standards
│
├── examples/                         Tier 2: Canonical code patterns (lazy-load)
│   ├── manager-example.json          Complete Manager class
│   ├── action-example.json           Under-15-line action handler
│   ├── model-example.json            Immutable model with computed properties
│   ├── notification-example.json     Centralized static notification method
│   ├── state-example.json            State class with args/action/transition/zombie
│   ├── client-manager-example.json   Client-side Manager pattern
│   └── undo-example.json             Undo flow: log, checkpoint, reverse
│
├── checklists/                       Tier 1: Self-validation quality gates
│   ├── pre-commit.json               Run before writing any file
│   ├── pre-review.json               Run before declaring task complete
│   └── pre-release.json              Full BGA Studio release requirements
│
└── references/                       Tier 2: Supplementary lookup tables (lazy-load)
    ├── reference-project-matrix.json Which reference project for which problem
    ├── anti-patterns.json            Condensed common mistakes catalog
    └── migration-mapping.json        Legacy construct to modern equivalent
```

### 1.3 Package Invariants

| Invariant | Rule |
|---|---|
| **Single root** | All artifacts live under `bga-senior-engineer-skill/` |
| **Flat directory names** | `rules/`, `prompts/`, `examples/`, `checklists/`, `references/` — no nesting |
| **Root files** | `skill.json`, `index.json`, `README.md` at package root |
| **Maximum files** | 41 files total (hard limit from runtime architecture) |
| **Maximum file size** | 500 lines soft, 800 lines hard per file |
| **No orphaned artifacts** | Every file (except README) must be referenced by at least one index entry |

---

## 2. Skill Manifest (`skill.json`)

### 2.1 Purpose

`skill.json` is the skill's identity document. It is the only Tier 0 artifact — always loaded by the agent platform when the skill is activated. It does not contain rules, prompts, or content. It is a signpost that tells the agent:

- This skill exists and is active
- What version is loaded
- What capabilities are available
- Which file to load next (`index.json`)
- What platform and runtime versions are required

### 2.2 Schema

#### 2.2.1 Top-Level Fields

| Field | Required | Type | Default | Description |
|---|---|---|---|---|
| `$schema` | No | String (URI) | — | JSON Schema URI for tooling validation. Must begin with `https://` or be a relative path. |
| `name` | Yes | String | — | Skill identifier. Must match the package directory name. Pattern: `^[a-z][a-z0-9-]+$`. |
| `version` | Yes | String | — | Semantic version of the skill package. Format: `MAJOR.MINOR.PATCH` per SemVer 2.0.0. |
| `description` | Yes | String | — | One-line description of the skill's purpose. Under 200 characters. |
| `runtime` | Yes | String | — | Version of the Runtime Specification this skill implements. Format: `vMAJOR.MINOR`. |
| `validator` | Yes | String | — | Minimum version of the Runtime Validator required to validate this skill's rule files. Format: `^MAJOR.MINOR.PATCH`. |
| `entry_point` | Yes | String | — | Relative path from package root to the first artifact the agent should load after activation. Always `index.json`. |
| `capabilities` | Yes | Array of String | — | List of task capabilities this skill supports. Each entry matches a task ID in `index.json`. |
| `loading_model` | Yes | Object | — | Declares the tiered loading model configuration. See §2.2.2. |
| `compatibility` | Yes | Object | — | Declares platform and engine compatibility requirements. See §2.2.3. |
| `metadata` | Yes | Object | — | Declares package metadata for discovery and provenance. See §2.2.4. |
| `extension` | No | Object | — | Reserved for platform-specific extensions. See §2.2.5. |

#### 2.2.2 `loading_model` Fields

| Field | Required | Type | Default | Description |
|---|---|---|---|---|
| `tiers` | Yes | Object | — | Maps tier IDs to their configuration. |
| `tiers.tier_0` | Yes | Object | — | Tier 0 configuration. |
| `tiers.tier_0.description` | Yes | String | — | Human-readable description of Tier 0. |
| `tiers.tier_0.files` | Yes | Array of String | — | Relative paths of files in this tier. Always `["skill.json"]`. |
| `tiers.tier_0.max_tokens` | Yes | Integer | — | Maximum token budget for this tier. |
| `tiers.tier_1` | Yes | Object | — | Tier 1 configuration. |
| `tiers.tier_1.description` | Yes | String | — | Human-readable description of Tier 1. |
| `tiers.tier_1.max_tokens` | Yes | Integer | — | Maximum token budget for a single Tier 1 load. |
| `tiers.tier_1.max_files` | Yes | Integer | — | Maximum number of files loaded in a single Tier 1 load. |
| `tiers.tier_2` | Yes | Object | — | Tier 2 configuration. |
| `tiers.tier_2.description` | Yes | String | — | Human-readable description of Tier 2. |
| `tiers.tier_2.max_tokens` | Yes | Integer | — | Maximum token budget for a single Tier 2 lazy-load. |
| `tiers.tier_2.max_files` | Yes | Integer | — | Maximum number of files per lazy-load. |
| `loading_instructions` | Yes | String | — | Instructions the agent follows to load artifacts from each tier. |

#### 2.2.3 `compatibility` Fields

| Field | Required | Type | Default | Description |
|---|---|---|---|---|
| `platform` | Yes | String | — | Target agent platform name. |
| `min_platform_version` | Yes | String | — | Minimum platform version required. Format: `MAJOR.MINOR.PATCH`. |
| `max_platform_version` | No | String | — | Maximum platform version supported. Format: `MAJOR.MINOR.PATCH`. Omit if no upper bound. |
| `runtime_version` | Yes | String | — | Frozen version of the Runtime Specification this skill targets. Format: `vMAJOR.MINOR`. |

#### 2.2.4 `metadata` Fields

| Field | Required | Type | Default | Description |
|---|---|---|---|---|
| `last_updated` | Yes | String | — | ISO 8601 date of last modification. Format: `YYYY-MM-DD`. |
| `source` | Yes | String | — | Path to the source doctrine document. Relative to repository root. |
| `authors` | No | Array of String | — | List of authors or maintainers. |
| `license` | No | String | — | SPDX license identifier. |
| `repository` | No | String | — | URL to the source repository. |
| `changelog` | No | String | — | Relative path to CHANGELOG file. |

#### 2.2.5 `extension` Fields

| Field | Required | Type | Default | Description |
|---|---|---|---|---|
| (any) | No | Any | — | Reserved for platform-specific metadata. The extension object must not contain fields that conflict with the top-level schema. |

### 2.3 Complete Example

```json
{
  "$schema": "../schemas/skill-manifest-schema.json",
  "name": "bga-senior-engineer",
  "version": "1.0.0",
  "description": "Production-grade BGA game implementation guidance for AI agents",
  "runtime": "v1.1",
  "validator": "^1.0.0",
  "entry_point": "index.json",
  "capabilities": [
    "migrate-manager",
    "migrate-state",
    "migrate-notifications",
    "migrate-client",
    "review-action",
    "review-manager",
    "review-state-machine",
    "review-notifications",
    "review-persistence",
    "review-full",
    "debug-session",
    "new-feature",
    "refactor-module"
  ],
  "loading_model": {
    "tiers": {
      "tier_0": {
        "description": "Always-loaded activation artifacts",
        "files": [
          "skill.json"
        ],
        "max_tokens": 200
      },
      "tier_1": {
        "description": "Task-loaded execution artifacts (prompt + rules + checklist)",
        "max_tokens": 3000,
        "max_files": 10
      },
      "tier_2": {
        "description": "Lazy-loaded reference artifacts (examples + references)",
        "max_tokens": 600,
        "max_files": 3
      }
    },
    "loading_instructions": "Load Tier 0 on activation (skill.json). On task receipt, load index.json and match task keywords. Load the prompt, rules, and checklist listed in the matching index entry. Load examples and references only when the prompt explicitly requests them."
  },
  "compatibility": {
    "platform": "mercurio",
    "min_platform_version": "1.0.0",
    "runtime_version": "v1.1"
  },
  "metadata": {
    "last_updated": "2026-07-30",
    "source": "docs/ai-os/bga-senior-engineer-doctrine.md",
    "authors": [
      "BGA Senior Engineer Team"
    ],
    "license": "MIT",
    "repository": "https://github.com/voilamochu/bga-senior-engineer",
    "changelog": "CHANGELOG.md"
  }
}
```

### 2.4 Field Validation Rules

| # | Rule | Severity | Description |
|---|---|---|---|
| M01 | `name` must match `^[a-z][a-z0-9-]+$` | ERROR | Names must be lowercase, start with a letter, and contain only letters, digits, and hyphens. |
| M02 | `version` must match `^\d+\.\d+\.\d+$` | ERROR | Must be valid SemVer. |
| M03 | `runtime` must match `^v\d+\.\d+$` | ERROR | Runtime version format is `vMAJOR.MINOR`. |
| M04 | `validator` must match `^\^\d+\.\d+\.\d+$` | ERROR | Validator constraint format is `^MAJOR.MINOR.PATCH`. |
| M05 | `entry_point` must be `"index.json"` | ERROR | The entry point is fixed. |
| M06 | `capabilities` must be non-empty | WARNING | A skill with no declared capabilities is unusable. |
| M07 | Every `capabilities` entry must exist as a task ID in `index.json` | ERROR | Capabilities must be implementable. |
| M08 | `loading_model.tiers.tier_0.files` must contain `"skill.json"` | ERROR | Tier 0 always contains the manifest. |
| M09 | `loading_model.tiers.tier_1.max_tokens` must be ≤ 5000 | WARNING | Exceeding 5,000 tokens per Tier 1 load reduces context available for agent work. |
| M10 | `compatibility.min_platform_version` must match `^\d+\.\d+\.\d+$` | ERROR | Must be valid SemVer. |
| M11 | `metadata.last_updated` must match `^\d{4}-\d{2}-\d{2}$` | ERROR | Must be valid ISO 8601 date. |
| M12 | `metadata.source` must reference an existing document | WARNING | Source citation should be verifiable. |

### 2.5 Invariants

| Invariant | Explanation |
|---|---|
| **`name` equals directory name** | The `name` field must match the package directory name (`bga-senior-engineer`) for platform auto-discovery. |
| **`version` is canonical** | `skill.json` version is the authoritative package version. All other artifact versions are subordinate. |
| **`entry_point` is fixed** | Always `"index.json"`. Changing this would break the loading model. |
| **`capabilities` matches index** | Every capability must have a corresponding task entry in `index.json`. No orphaned capabilities. |
| **Tier 0 is minimal** | Must never exceed 200 tokens. No rules, prompts, or content in `skill.json`. |

### 2.6 Extension Fields

The `extension` object allows platform-specific metadata without modifying the core schema. Platforms may define their own fields within `extension`:

- Platform-specific activation hooks
- Custom capability metadata
- Integration configuration

**Rules for `extension`:**
1. Fields within `extension` must not conflict with top-level field names
2. Unknown fields at the top level must be rejected by validators (strict schema)
3. Unknown fields within `extension` must be accepted (permissive)
4. `extension` content must not be required for basic skill operation

---

## 3. Master Index (`index.json`)

### 3.1 Purpose

`index.json` is the skill's navigation document. It is loaded first in Tier 1 and maps every task an agent might perform to the minimal set of artifacts needed. It is the progressive-disclosure gateway — the agent never loads more artifacts than the index specifies for the matched task.

### 3.2 Schema

#### 3.2.1 Top-Level Fields

| Field | Required | Type | Default | Description |
|---|---|---|---|---|
| `$schema` | No | String (URI) | — | JSON Schema URI for tooling validation. |
| `version` | Yes | String | — | Version of the index. Matches `skill.json` version when the task map changes. |
| `last_updated` | Yes | String | — | ISO 8601 date of last modification. |
| `source` | Yes | String | — | Path to the source architecture document. |
| `loading_instructions` | Yes | String | — | Instructions the agent follows to use this index. |
| `fallback_task` | Yes | String | — | Task ID to use when no keyword match is found. Must be a key in `tasks`. |
| `task_order` | Yes | Array of String | — | Ordered list of task IDs. Determines tie-breaking order. |
| `tasks` | Yes | Object | — | Maps task IDs to their artifact specifications. See §3.2.2. |

#### 3.2.2 `tasks` Entry Fields

Each key in `tasks` is a task ID. Task IDs must match `^[a-z][a-z0-9-]+$`.

| Field | Required | Type | Default | Description |
|---|---|---|---|---|
| `description` | Yes | String | — | One-line description of the task. Under 150 characters. |
| `priority` | Yes | Integer | — | Task evaluation priority. 1 = highest. Lower number = higher priority for keyword matching. |
| `keywords` | Yes | Array of String | — | Keywords for task classification. Agent matches these against user request text. Case-insensitive substring matching. |
| `prompt` | Yes | String | — | Relative path to the prompt file. Must exist in `prompts/`. |
| `rules` | Yes | Array of String | — | Relative paths to rule files to load. Must exist in `rules/`. `rules/constitution.json` must always be included. |
| `checklists` | Yes | Array of String | — | Relative paths to checklist files to load. Must exist in `checklists/`. |
| `examples` | No | Array of String | — | Relative paths to example files (Tier 2). Must exist in `examples/` if specified. |
| `references` | No | Array of String | — | Relative paths to reference files (Tier 2). Must exist in `references/` if specified. |
| `phase_groups` | No | Object | — | Optional phased execution groups for tasks that exceed the single-load token budget. See §3.2.3. |

#### 3.2.3 `phase_groups` Fields

| Field | Required | Type | Default | Description |
|---|---|---|---|---|
| (group name) | Yes | Object | — | A named phase group. |
| (group name).`description` | Yes | String | — | Description of this execution phase. |
| (group name).`rules` | Yes | Array of String | — | Rule files to load in this phase. |
| (group name).`examples` | No | Array of String | — | Example files to load in this phase. |
| (group name).`checklists` | No | Array of String | — | Checklist files to load in this phase. |
| (group name).`prompt_segment` | No | String | — | Section of the prompt relevant to this phase. The agent reads only this segment in the current phase. |

### 3.3 Complete Example

```json
{
  "$schema": "../schemas/skill-index-schema.json",
  "version": "1.0.0",
  "last_updated": "2026-07-30",
  "source": "docs/ai-os/runtime-skill-architecture.md",
  "loading_instructions": "Read the user request. For each task, count how many keywords appear as case-insensitive substrings in the request. Select the task with the highest match count. Ties go to the first task in task_order. Load the listed prompt, rules, and checklist. Load examples and references only when the prompt requests them.",
  "fallback_task": "review-full",
  "task_order": [
    "debug-session",
    "migrate-manager",
    "migrate-state",
    "migrate-notifications",
    "migrate-client",
    "review-action",
    "review-manager",
    "review-state-machine",
    "review-notifications",
    "review-persistence",
    "review-full",
    "new-feature",
    "refactor-module"
  ],
  "tasks": {
    "migrate-manager": {
      "description": "Extract a Manager from legacy Game.php into a dedicated Manager class",
      "priority": 1,
      "keywords": [
        "extract",
        "manager",
        "migrate",
        "legacy",
        "game.php",
        "refactor"
      ],
      "prompt": "prompts/migrate-manager.md",
      "rules": [
        "rules/constitution.json",
        "rules/architecture.json",
        "rules/persistence.json",
        "rules/migration.json"
      ],
      "checklists": [
        "checklists/pre-commit.json"
      ],
      "examples": [
        "examples/manager-example.json",
        "examples/model-example.json"
      ]
    },
    "migrate-state": {
      "description": "Convert legacy states.inc.php array to State classes",
      "priority": 1,
      "keywords": [
        "state",
        "migrate",
        "states.inc.php",
        "convert",
        "transition"
      ],
      "prompt": "prompts/migrate-state.md",
      "rules": [
        "rules/constitution.json",
        "rules/state-machine.json",
        "rules/migration.json"
      ],
      "checklists": [
        "checklists/pre-commit.json"
      ],
      "examples": [
        "examples/state-example.json"
      ]
    },
    "migrate-notifications": {
      "description": "Extract a centralized Notifications class from scattered notifyAllPlayers calls",
      "priority": 1,
      "keywords": [
        "notifications",
        "migrate",
        "notify",
        "centralize",
        "extract"
      ],
      "prompt": "prompts/migrate-notifications.md",
      "rules": [
        "rules/constitution.json",
        "rules/notifications.json",
        "rules/migration.json"
      ],
      "checklists": [
        "checklists/pre-commit.json"
      ],
      "examples": [
        "examples/notification-example.json"
      ]
    },
    "migrate-client": {
      "description": "Convert legacy Dojo client to ES modules and BgaCards",
      "priority": 1,
      "keywords": [
        "client",
        "migrate",
        "dojo",
        "es module",
        "bgacards",
        "javascript"
      ],
      "prompt": "prompts/migrate-client.md",
      "rules": [
        "rules/constitution.json",
        "rules/client.json",
        "rules/migration.json"
      ],
      "checklists": [
        "checklists/pre-commit.json"
      ],
      "examples": [
        "examples/client-manager-example.json"
      ]
    },
    "review-action": {
      "description": "Review a single action handler against engineering standards",
      "priority": 2,
      "keywords": [
        "review",
        "action",
        "handler",
        "audit",
        "check"
      ],
      "prompt": "prompts/review-action.md",
      "rules": [
        "rules/constitution.json",
        "rules/actions.json",
        "rules/undo-replay.json"
      ],
      "checklists": [
        "checklists/pre-review.json"
      ],
      "examples": [
        "examples/action-example.json"
      ]
    },
    "review-manager": {
      "description": "Review a Manager class against architecture and persistence standards",
      "priority": 2,
      "keywords": [
        "review",
        "manager",
        "class",
        "audit"
      ],
      "prompt": "prompts/review-manager.md",
      "rules": [
        "rules/constitution.json",
        "rules/architecture.json",
        "rules/persistence.json"
      ],
      "checklists": [
        "checklists/pre-review.json"
      ],
      "examples": [
        "examples/manager-example.json"
      ]
    },
    "review-state-machine": {
      "description": "Review the entire state machine for correct state design and transitions",
      "priority": 2,
      "keywords": [
        "review",
        "state",
        "machine",
        "state machine",
        "transition",
        "zombie"
      ],
      "prompt": "prompts/review-state-machine.md",
      "rules": [
        "rules/constitution.json",
        "rules/state-machine.json"
      ],
      "checklists": [
        "checklists/pre-review.json"
      ],
      "examples": [
        "examples/state-example.json"
      ]
    },
    "review-notifications": {
      "description": "Audit the notification system for correctness, i18n, and hidden information",
      "priority": 2,
      "keywords": [
        "review",
        "notification",
        "audit",
        "i18n",
        "notify"
      ],
      "prompt": "prompts/review-notifications.md",
      "rules": [
        "rules/constitution.json",
        "rules/notifications.json",
        "rules/synchronization.json"
      ],
      "checklists": [
        "checklists/pre-review.json"
      ],
      "examples": [
        "examples/notification-example.json"
      ]
    },
    "review-persistence": {
      "description": "Audit database schema, globals, and data-driven configuration",
      "priority": 2,
      "keywords": [
        "review",
        "database",
        "persistence",
        "schema",
        "globals",
        "sql"
      ],
      "prompt": "prompts/review-persistence.md",
      "rules": [
        "rules/constitution.json",
        "rules/persistence.json",
        "rules/undo-replay.json"
      ],
      "checklists": [
        "checklists/pre-review.json"
      ]
    },
    "review-full": {
      "description": "Full pre-release audit covering all BGA Studio requirements",
      "priority": 3,
      "keywords": [
        "review",
        "audit",
        "complete",
        "full",
        "pre-release",
        "release",
        "prerelease"
      ],
      "prompt": "prompts/review-full.md",
      "rules": [
        "rules/constitution.json",
        "rules/architecture.json",
        "rules/state-machine.json",
        "rules/actions.json",
        "rules/persistence.json",
        "rules/notifications.json",
        "rules/client.json",
        "rules/synchronization.json",
        "rules/animations.json",
        "rules/testing.json",
        "rules/undo-replay.json",
        "rules/migration.json"
      ],
      "checklists": [
        "checklists/pre-commit.json",
        "checklists/pre-review.json",
        "checklists/pre-release.json"
      ],
      "examples": [
        "examples/manager-example.json",
        "examples/action-example.json",
        "examples/model-example.json",
        "examples/notification-example.json",
        "examples/state-example.json",
        "examples/client-manager-example.json",
        "examples/undo-example.json"
      ],
      "references": [
        "references/reference-project-matrix.json",
        "references/anti-patterns.json",
        "references/migration-mapping.json"
      ],
      "phase_groups": {
        "architecture_review": {
          "description": "Phase 1: Review architecture, state machine, and actions",
          "rules": [
            "rules/constitution.json",
            "rules/architecture.json",
            "rules/state-machine.json",
            "rules/actions.json"
          ],
          "checklists": [
            "checklists/pre-commit.json"
          ],
          "prompt_segment": "## Phase 1: Architecture Review"
        },
        "persistence_review": {
          "description": "Phase 2: Review persistence, notifications, and undo",
          "rules": [
            "rules/constitution.json",
            "rules/persistence.json",
            "rules/notifications.json",
            "rules/undo-replay.json"
          ],
          "checklists": [
            "checklists/pre-commit.json"
          ],
          "prompt_segment": "## Phase 2: Persistence and Notifications Review"
        },
        "client_review": {
          "description": "Phase 3: Review client, synchronization, animations, testing",
          "rules": [
            "rules/constitution.json",
            "rules/client.json",
            "rules/synchronization.json",
            "rules/animations.json",
            "rules/testing.json"
          ],
          "checklists": [
            "checklists/pre-commit.json"
          ],
          "prompt_segment": "## Phase 3: Client and Testing Review"
        },
        "final_validation": {
          "description": "Phase 4: Run full release checklists",
          "rules": [
            "rules/constitution.json",
            "rules/migration.json"
          ],
          "checklists": [
            "checklists/pre-review.json",
            "checklists/pre-release.json"
          ],
          "prompt_segment": "## Phase 4: Final Validation"
        }
      }
    },
    "debug-session": {
      "description": "Systematic debugging workflow for identifying and fixing bugs",
      "priority": 1,
      "keywords": [
        "debug",
        "bug",
        "fix",
        "trace",
        "diagnose",
        "error",
        "issue"
      ],
      "prompt": "prompts/debug-session.md",
      "rules": [
        "rules/constitution.json"
      ],
      "checklists": [
        "checklists/pre-review.json"
      ]
    },
    "new-feature": {
      "description": "Add a new game feature following the full implementation pipeline",
      "priority": 3,
      "keywords": [
        "add",
        "feature",
        "new",
        "implement",
        "create",
        "phase"
      ],
      "prompt": "prompts/new-feature.md",
      "rules": [
        "rules/constitution.json",
        "rules/architecture.json",
        "rules/state-machine.json",
        "rules/actions.json",
        "rules/persistence.json",
        "rules/notifications.json",
        "rules/client.json",
        "rules/undo-replay.json"
      ],
      "checklists": [
        "checklists/pre-commit.json"
      ],
      "examples": [
        "examples/manager-example.json",
        "examples/action-example.json",
        "examples/model-example.json",
        "examples/notification-example.json",
        "examples/state-example.json",
        "examples/client-manager-example.json",
        "examples/undo-example.json"
      ],
      "phase_groups": {
        "design": {
          "description": "Phase 1: Design domain boundary, DB schema, and state flow",
          "rules": [
            "rules/constitution.json",
            "rules/architecture.json",
            "rules/state-machine.json"
          ],
          "prompt_segment": "## Phase 1: Design"
        },
        "implementation": {
          "description": "Phase 2: Implement Manager, Actions, and undo wiring",
          "rules": [
            "rules/constitution.json",
            "rules/persistence.json",
            "rules/actions.json",
            "rules/undo-replay.json"
          ],
          "examples": [
            "examples/manager-example.json",
            "examples/action-example.json",
            "examples/model-example.json",
            "examples/undo-example.json"
          ],
          "prompt_segment": "## Phase 2: Implementation"
        },
        "integration": {
          "description": "Phase 3: Wire notifications and client handlers",
          "rules": [
            "rules/constitution.json",
            "rules/notifications.json",
            "rules/client.json"
          ],
          "examples": [
            "examples/notification-example.json",
            "examples/client-manager-example.json",
            "examples/state-example.json"
          ],
          "checklists": [
            "checklists/pre-commit.json"
          ],
          "prompt_segment": "## Phase 3: Integration"
        }
      }
    },
    "refactor-module": {
      "description": "Refactor an existing module to canonical BGA engineering standards",
      "priority": 2,
      "keywords": [
        "refactor",
        "module",
        "restructure",
        "clean",
        "rewrite",
        "improve"
      ],
      "prompt": "prompts/refactor-module.md",
      "rules": [
        "rules/constitution.json",
        "rules/architecture.json"
      ],
      "checklists": [
        "checklists/pre-commit.json",
        "checklists/pre-review.json"
      ],
      "examples": [
        "examples/manager-example.json"
      ]
    }
  }
}
```

### 3.4 Field Validation Rules

| # | Rule | Severity | Description |
|---|---|---|---|
| I01 | `version` must match `^\d+\.\d+\.\d+$` | ERROR | Must be valid SemVer. |
| I02 | `fallback_task` must be a key in `tasks` | ERROR | The fallback task must exist. |
| I03 | `task_order` must contain every key in `tasks` | ERROR | Every task must appear in the order list. |
| I04 | Every task ID must match `^[a-z][a-z0-9-]+$` | ERROR | Task IDs must be lowercase with hyphens. |
| I05 | `prompt` path must reference an existing file in `prompts/` | WARNING | Currently unreferenced paths are bookkeeping; at implementation time they must resolve. |
| I06 | Every entry in `rules` must exist in `rules/` | ERROR | Rule files must exist. |
| I07 | Every entry in `checklists` must exist in `checklists/` | ERROR | Checklist files must exist. |
| I08 | Every entry in `examples` must exist in `examples/` | WARNING | Example files may be lazy-loaded; must exist at package assembly time. |
| I09 | Every entry in `references` must exist in `references/` | WARNING | Reference files may be lazy-loaded; must exist at package assembly time. |
| I10 | `rules` array must include `rules/constitution.json` | ERROR | Constitutional rules always apply. |
| I11 | `rules` array must contain at least one file | ERROR | Every task needs at least constitution.json. |
| I12 | `checklists` array must contain at least one file | ERROR | Every task must self-validate. |
| I13 | `priority` must be an integer ≥ 1 | WARNING | Lower priority numbers (1, 2) indicate higher priority tasks for keyword matching. |
| I14 | `description` must be under 150 characters | WARNING | Descriptions should be concise. |
| I15 | `keywords` must not be empty | WARNING | A task with no keywords cannot be matched. |
| I16 | `phase_groups` must not overlap in `rules` for the same phase | WARNING | A rule file should not appear in multiple phase groups unless the phases are sequential (user cannot load a rule twice). Overlap is permitted when the agent loads each phase independently. |
| I17 | Every `phase_groups` entry must reference rule files that are also in the parent task's `rules` array | ERROR | Phase groups must be a subset of the parent task's rule set. |

### 3.5 Invariants

| Invariant | Explanation |
|---|---|
| **Every task has `constitution.json`** | Constitutional rules apply to every engineering task. No exceptions. |
| **Every task has at least one checklist** | Self-validation is mandatory, not optional. |
| **No orphaned artifacts** | Every file in `rules/`, `prompts/`, `examples/`, `checklists/`, `references/` must be referenced by at least one task entry. |
| **`task_order` is authoritative** | Tie-breaking order is the declaration order in `task_order`, not insertion order in `tasks`. |
| **`fallback_task` always resolves** | When no keywords match, the agent always has a fallback path. |
| **Phased tasks never exceed budget** | Any task with `phase_groups` must ensure each phase's combined token load stays under 3,000 tokens. |
| **All paths are relative to package root** | Every path in `index.json` is relative to `bga-senior-engineer-skill/`. |

### 3.6 Task Matching Algorithm

The agent follows this deterministic algorithm:

```
function match_task(user_request, index):
    best_task = index.fallback_task
    best_count = 0

    for task_id in index.task_order:
        task = index.tasks[task_id]
        count = 0
        for keyword in task.keywords:
            if keyword.lower() in user_request.lower():
                count += 1
        if count > best_count:
            best_count = count
            best_task = task_id

    return best_task
```

Properties:
- **Case-insensitive:** `"Manager"` matches `"manager"`, `"MANAGER"`, etc.
- **Substring matching:** `"extract"` matches `"extraction"`, `"extracted"`, etc.
- **Deterministic:** Same input always produces the same output.
- **Tie-breaking by order:** The first task in `task_order` with the highest count wins.
- **Zero-match fallback:** If no keywords match, returns `fallback_task` (`"review-full"`).

### 3.7 Loading Rules

The agent must follow these rules when loading artifacts:

1. **Load exactly what the index specifies.** Do not add or remove files.
2. **Load the prompt file.** It contains the execution workflow.
3. **Load all listed rule files.** Do not skip any.
4. **Load all listed checklist files.** Self-validation is mandatory.
5. **Do not load examples or references unless the prompt explicitly requests it.** These are Tier 2 (lazy-load).
6. **If the task has `phase_groups`, load only the current phase's subset.** Signal completion before loading the next phase.
7. **If a file path does not resolve, escalate.** Do not guess or substitute.

---

## 4. Validation Rules

### 4.1 Cross-File Validation

These rules apply across both `skill.json` and `index.json`:

| # | Rule | Severity | Description |
|---|---|---|---|
| C01 | `skill.json` version must match `index.json` version when task map changes | ERROR | Version drift between manifest and index indicates synchronization error. |
| C02 | Every capability in `skill.json` must exist as a task ID in `index.json` | ERROR | Capabilities must be implementable tasks. |
| C03 | Every task ID in `index.json` must be declared as a capability in `skill.json` | WARNING | Undeclared tasks are hidden from platform discovery. |
| C04 | `skill.json` `metadata.last_updated` must be ≥ `index.json` `last_updated` | WARNING | Manifest should reflect the most recent change. |
| C05 | Rule files referenced in `index.json` must pass Runtime Validator schema validation | ERROR | Rule files must be schema-compliant. |

### 4.2 Budget Validation

| # | Rule | Severity | Description |
|---|---|---|---|
| B01 | Total line count across all files must not exceed budget per `runtime-skill-architecture.md` | WARNING | Aggregate budget is a soft constraint. |
| B02 | For each task, estimated tokens (lines × 3) for Tier 1 load must be ≤ 3,000 | WARNING | Per-task budget is a hard constraint. Tasks exceeding this must use `phase_groups`. |
| B03 | Any task with estimated Tier 1 load > 3,000 tokens must define `phase_groups` | ERROR | Phased execution is required for oversized tasks. |

---

## 5. Backward Compatibility

### 5.1 Guarantees

| Component | Compatibility Level | Guarantee |
|---|---|---|
| `skill.json` schema | MAJOR-version stable | Breaking changes require a MAJOR version bump. MINOR versions may add optional fields. PATCH versions may clarify field descriptions. |
| `index.json` task entries | MAJOR-version stable | Removing a task ID requires a MAJOR version bump. Adding new task IDs is MINOR. |
| `index.json` keyword matching | PATCH-version stable | Adding keywords is PATCH. Removing keywords is MINOR. Changing the matching algorithm is MAJOR. |
| `index.json` fallback behavior | MAJOR-version stable | Changing the fallback task requires a MAJOR version bump. |
| Path conventions | MAJOR-version stable | Changing the package root or subdirectory names requires a MAJOR version bump. |

### 5.2 Breaking Changes

The following are considered breaking:

- Removing a required field from `skill.json` or `index.json`
- Changing a field's type (e.g., String to Integer)
- Changing the matching algorithm from keyword substring matching
- Changing the package root directory
- Removing or renaming a subdirectory (`rules/`, `prompts/`, etc.)
- Removing a task ID from `tasks`
- Changing the `fallback_task` value
- Changing the `entry_point` value

### 5.3 Non-Breaking Changes

The following are considered backward-compatible:

- Adding a new optional field to any schema
- Adding a new task ID to `tasks`
- Adding keywords to an existing task entry
- Adding new examples, references, or checklists
- Adding `extension` fields
- Adding `phase_groups` to an existing task
- Clarifying field descriptions
- Adding `$schema` references

---

## 6. Extension Strategy

### 6.1 Adding a New Task

1. Add the task ID to `capabilities` in `skill.json`
2. Add the task ID to `task_order` in `index.json`
3. Create the task entry in `index.json` `tasks`
4. Create the corresponding prompt file in `prompts/`
5. Bump `skill.json` MINOR version, `index.json` MINOR version

**Zero existing files modified** (except `skill.json` and `index.json` which are expected to change).

### 6.2 Adding a New Field

1. Add the field to this specification document
2. Mark it as Optional (not Required)
3. Update validation rules if necessary
4. No existing files need modification

### 6.3 Platform Extensions

Platforms may add custom fields in the `extension` object of `skill.json`. These are ignored by standard validators and do not affect portability.

### 6.4 Schema Evolution

| Change Type | skill.json Bump | index.json Bump | Notes |
|---|---|---|---|
| Add optional field | PATCH | PATCH | Backward compatible |
| Add required field | MAJOR | MAJOR | Breaking change |
| Add task ID | — | MINOR | Requires capability declaration in skill.json MINOR |
| Add keyword | — | PATCH | Always backward compatible |
| Add phase_group | — | PATCH | Always backward compatible |
| Remove task ID | MAJOR | MAJOR | Breaking change |
| Change field type | MAJOR | MAJOR | Breaking change |
| Add $schema | PATCH | PATCH | Tooling only |

---

## 7. Future Validation

### 7.1 Validator Extension Points

The following validators could be added to the Runtime Tooling Platform to validate `skill.json` and `index.json`:

#### 7.1.1 Manifest Validator

Would validate `skill.json` against the schema in §2:

| Check | What It Validates |
|---|---|
| `manifest_required_fields` | All required fields present |
| `manifest_field_types` | Field types match schema |
| `manifest_semver_format` | `version` matches SemVer |
| `manifest_date_format` | `last_updated` matches ISO 8601 |
| `manifest_capabilities_match_index` | Every capability has a corresponding task in `index.json` |
| `manifest_entry_point_exists` | `entry_point` file exists |
| `manifest_loading_model_tiers` | Tier configuration is valid |
| `manifest_compatibility` | Compatibility fields are valid SemVer |

#### 7.1.2 Index Validator

Would validate `index.json` against the schema in §3:

| Check | What It Validates |
|---|---|
| `index_required_fields` | All required fields present |
| `index_field_types` | Field types match schema |
| `index_fallback_task_exists` | `fallback_task` is a key in `tasks` |
| `index_task_order_complete` | `task_order` contains every task ID |
| `index_task_ids_format` | Task IDs match `^[a-z][a-z0-9-]+$` |
| `index_every_task_has_constitution` | Every task includes `rules/constitution.json` |
| `index_every_task_has_checklist` | Every task has at least one checklist |
| `index_no_orphaned_rules` | Every rule file is referenced by at least one task |
| `index_no_orphaned_prompts` | Every prompt file is referenced by at least one task |
| `index_phase_groups_subset` | Phase group rules are a subset of parent task rules |
| `index_token_budget` | Estimated tokens per task ≤ 3,000 (or `phase_groups` defined) |
| `index_paths_exist` | All relative paths resolve to existing files |

#### 7.1.3 Cross-File Validator

Would validate consistency between `skill.json` and `index.json`:

| Check | What It Validates |
|---|---|
| `crossfile_version_match` | `skill.json` version matches `index.json` version |
| `crossfile_capabilities_match` | Every capability maps to a task and vice versa |
| `crossfile_date_consistency` | Date fields are consistent |

### 7.2 Integration with Runtime Validator

The Runtime Validator (`tooling/validator/`) currently validates only rule files. It could be extended to also validate the manifest and index by:

1. Adding a `--manifest` flag pointing to `skill.json`
2. Adding a `--index` flag pointing to `index.json`
3. Implementing new validators following the existing `validate(rules: RuleCollection) -> ValidatorResult` contract

The manifest and index validators would use their own data types and would not depend on `RuleCollection`. A new shared module `tooling/_shared/manifest.py` and `tooling/_shared/index_types.py` could provide the data models.

### 7.3 Manual Validation Checklist

Until a validator is implemented, manual validation of manifest and index includes:

- [ ] `skill.json` — all required fields present and correctly typed
- [ ] `skill.json` — `capabilities` array contains every task ID from `index.json`
- [ ] `skill.json` — `version` matches `index.json` `version`
- [ ] `index.json` — `fallback_task` is a valid task key
- [ ] `index.json` — `task_order` contains every task key
- [ ] `index.json` — every task includes `rules/constitution.json`
- [ ] `index.json` — every task has at least one checklist
- [ ] `index.json` — every referenced file path resolves to an existing file
- [ ] `index.json` — phased tasks have `phase_groups` defined
- [ ] `index.json` — no orphaned artifacts (every file referenced somewhere)
- [ ] Token budget: estimated Tier 1 load ≤ 3,000 tokens per task
- [ ] Token budget: phased tasks ≤ 2,000 tokens per phase

---

*End of manifest and index specification. This document defines the public API of the BGA Senior Engineer Skill. Implementation follows in the next milestone.*
