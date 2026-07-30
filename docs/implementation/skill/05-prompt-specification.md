# BGA Senior Engineer Skill — Prompt Specification

**Date:** 2026-07-30
**Status:** APPROVED
**Version:** 1.0.0
**Authority:** BGA Senior Engineer — Skill Architecture (v1.0.0)

**Prerequisite:** `docs/implementation/skill/04-loading-strategy-revision.md` (lazy-load model)

---

## Table of Contents

1. [Purpose](#1-purpose)
2. [Prompt Anatomy](#2-prompt-anatomy)
3. [Frontmatter Schema](#3-frontmatter-schema)
4. [Required Sections](#4-required-sections)
5. [Section Specifications](#5-section-specifications)
6. [Workflow Structure](#6-workflow-structure)
7. [Phase Structure](#7-phase-structure)
8. [Rule References](#8-rule-references)
9. [Lazy-Rule Usage](#9-lazy-rule-usage)
10. [Example Loading](#10-example-loading)
11. [Checklist Integration](#11-checklist-integration)
12. [Stop Conditions](#12-stop-conditions)
13. [Escalation Rules](#13-escalation-rules)
14. [Self-Validation Sequence](#14-self-validation-sequence)
15. [Complete Templates](#15-complete-templates)
16. [Validation Rules](#16-validation-rules)
17. [Invariants](#17-invariants)

---

## 1. Purpose

This document defines the canonical prompt architecture for the BGA Senior Engineer Skill. Every prompt file in `bga-senior-engineer-skill/prompts/` must conform to this specification.

The prompt is the agent's primary execution instruction. It orchestrates the task: tells the agent what to load, what steps to follow, which rules to apply, when to lazy-load supplementary rules, when to load examples, how to validate output, and how to signal completion.

Prompts are written in Markdown with YAML frontmatter. They reference rule IDs, file paths, and checklist items. They never embed rule content.

---

## 2. Prompt Anatomy

Every prompt follows this exact structure:

```
---                             ← YAML frontmatter delimiter
...                             ← Frontmatter metadata
---                             ← YAML frontmatter delimiter

# <Task Title>                   ← Title (H1, matches task description)

## Prerequisites                 ← Required section
...                             ← Confirm files are loaded

## Lazy-Load Rules              ← Required section (may be empty)
...                             ← List lazy-loadable rules and triggers

## Workflow                     ← Required section
...                             ← Step-by-step instructions

OR

## Phase 1: <Name>              ← For phased prompts
...                             ← Phase-specific workflow
## Phase 2: <Name>
...

## Edge Cases                   ← Required section
...                             ← Common edge cases and resolutions

## Stop Conditions              ← Required section
...                             ← Binary completion checklist

## Escalation                   ← Required section
...                             ← When to stop and ask the user

## Self-Validation              ← Required section
...                             ← How to validate output before declaring done
```

### 2.1 Section Order

The section order is **fixed**. Every prompt must have these sections in this exact order:

1. Frontmatter (metadata)
2. Title (H1)
3. Prerequisites
4. Lazy-Load Rules
5. Workflow (or phased workflow sections)
6. Edge Cases
7. Stop Conditions
8. Escalation
9. Self-Validation

No section may be reordered, renamed, or omitted. Unused sections (e.g., Lazy-Load Rules with no lazy rules) must still exist with the content "None."

### 2.2 File Naming

| Rule | Detail |
|---|---|
| Filename | `<task-id>.md` — matches the task ID in `index.json` |
| Task ID pattern | `^[a-z][a-z0-9-]+$` (lowercase, hyphens) |
| Location | `bga-senior-engineer-skill/prompts/<task-id>.md` |
| Max lines | 120 lines (soft), 150 lines (hard) |

---

## 3. Frontmatter Schema

Every prompt begins with a YAML frontmatter block delimited by `---`.

### 3.1 Field Definitions

| Field | Required | Type | Description |
|---|---|---|---|
| `task` | Yes | String | Task ID. Must match the filename (without `.md`) and an `index.json` task key. |
| `version` | Yes | String | Semantic version of this prompt. Format: `MAJOR.MINOR.PATCH`. |
| `last_updated` | Yes | String | ISO 8601 date of last modification. Format: `YYYY-MM-DD`. |
| `source` | Yes | String | Path to the source document this prompt was derived from. Relative to repository root. |
| `required_rules` | Yes | Array of String | Relative paths to mandatory rule files. Must be a subset of the task's `rules` in `index.json`. |
| `lazy_rules` | No | Array of String | Relative paths to lazy-loadable rule files. Must match the task's `lazy_rules` keys in `index.json`. |
| `required_examples` | No | Array of String | Relative paths to example files. Must be a subset of the task's `examples` in `index.json`. |
| `required_checklists` | Yes | Array of String | Relative paths to checklist files. Must match the task's `checklists` in `index.json`. |
| `phases` | No | Array of Object | Phase definitions for phased prompts. See §3.2. |
| `max_tokens` | No | Integer | Estimated token budget for this prompt's Tier 1 load. Informational only. |

### 3.2 Phase Field

Each entry in `phases`:

| Field | Required | Type | Description |
|---|---|---|---|
| `name` | Yes | String | Phase name. Matches the phase group key in `index.json`. |
| `rules` | Yes | Array of String | Rule files to load in this phase. |
| `examples` | No | Array of String | Example files to load in this phase. |
| `checklists` | No | Array of String | Checklist files to load in this phase. |

### 3.3 Complete Example (Simple)

```yaml
---
task: review-action
version: 1.0.0
last_updated: 2026-07-30
source: docs/ai-os/bga-senior-engineer-doctrine.md
required_rules:
  - rules/constitution.json
  - rules/actions.json
lazy_rules:
  - rules/undo-replay.json
required_examples:
  - examples/action-example.json
required_checklists:
  - checklists/pre-review.json
max_tokens: 2940
---
```

### 3.4 Complete Example (Phased)

```yaml
---
task: new-feature
version: 1.0.0
last_updated: 2026-07-30
source: docs/ai-os/bga-senior-engineer-doctrine.md
required_rules:
  - rules/constitution.json
required_checklists:
  - checklists/pre-commit.json
required_examples:
  - examples/manager-example.json
  - examples/action-example.json
  - examples/model-example.json
  - examples/notification-example.json
  - examples/client-manager-example.json
  - examples/state-example.json
  - examples/undo-example.json
phases:
  - name: design
    rules:
      - rules/architecture.json
      - rules/state-machine.json
  - name: implementation
    rules:
      - rules/persistence.json
      - rules/actions.json
    examples:
      - examples/manager-example.json
      - examples/action-example.json
      - examples/model-example.json
  - name: integration
    rules:
      - rules/notifications.json
      - rules/client.json
    examples:
      - examples/notification-example.json
      - examples/client-manager-example.json
      - examples/state-example.json
    checklists:
      - checklists/pre-commit.json
  - name: undo
    rules:
      - rules/undo-replay.json
    examples:
      - examples/undo-example.json
    checklists:
      - checklists/pre-commit.json
max_tokens: 3140
---
```

### 3.5 Frontmatter Validation Rules

| # | Rule | Severity |
|---|---|---|
| F01 | `task` must match the filename stem | ERROR |
| F02 | `task` must be a key in `index.json` | ERROR |
| F03 | `version` must match `^\d+\.\d+\.\d+$` | ERROR |
| F04 | `last_updated` must match `^\d{4}-\d{2}-\d{2}$` | ERROR |
| F05 | `source` must reference an existing doc | WARNING |
| F06 | Every path in `required_rules` must exist in `rules/` | ERROR |
| F07 | Every path in `required_rules` must be in the task's `index.json` `rules` array | ERROR |
| F08 | Every path in `lazy_rules` must be in the task's `index.json` `lazy_rules` keys | WARNING |
| F09 | Every path in `required_examples` must be in the task's `index.json` `examples` array | WARNING |
| F10 | Every path in `required_checklists` must be in the task's `index.json` `checklists` array | ERROR |
| F11 | `required_checklists` must be non-empty | ERROR |
| F12 | `phases` must not be empty if present | ERROR |
| F13 | Every phase `name` must be a key in the task's `phase_groups` in `index.json` | ERROR |
| F14 | Phase `rules` must be subsets of the phase group's `rules` in `index.json` | ERROR |

---

## 4. Required Sections

### 4.1 Mandatory Section List

Every prompt must contain exactly these sections in this order:

| # | Section | Heading | Required Content |
|---|---|---|---|
| 1 | Title | `# <Task Title>` | Human-readable title matching the task description |
| 2 | Prerequisites | `## Prerequisites` | Confirmation that required files are loaded |
| 3 | Lazy-Load Rules | `## Lazy-Load Rules` | List of lazy-loadable rules and their triggers |
| 4 | Workflow | `## Workflow` or `## Phase N: <Name>` | Step-by-step execution instructions |
| 5 | Edge Cases | `## Edge Cases` | Common edge cases and how to handle them |
| 6 | Stop Conditions | `## Stop Conditions` | Binary checklist of completion criteria |
| 7 | Escalation | `## Escalation` | When to stop and ask the user |
| 8 | Self-Validation | `## Self-Validation` | How to validate output before declaring done |

### 4.2 Section Headings

Section headings are **fixed**. Every prompt uses exactly these heading texts:

```
## Prerequisites
## Lazy-Load Rules
## Workflow                 (for non-phased prompts)
## Phase 1: <Name>          (for phased prompts, first phase)
## Edge Cases
## Stop Conditions
## Escalation
## Self-Validation
```

Phased prompts replace the single `## Workflow` with `## Phase 1: <Name>`, `## Phase 2: <Name>`, etc. All other sections remain the same.

---

## 5. Section Specifications

### 5.1 Title (H1)

```
# <Task Title>
```

The title is a single H1 heading. It must match the task `description` from `index.json` or be a concise paraphrase. Under 80 characters.

### 5.2 Prerequisites

Purpose: Confirm that the mandatory files listed in `required_rules` and `required_checklists` are loaded before proceeding.

Content:
- A bullet list of every file in `required_rules`
- A bullet list of every file in `required_checklists`
- A confirmation statement

Template:

```
## Prerequisites

Before proceeding, confirm the following files are loaded:
- rules/constitution.json
- rules/architecture.json

Do not continue until all files are confirmed loaded.
```

Rules:
- Every file in `required_rules` must be listed
- Every file in `required_checklists` must be listed
- The confirmation statement must be present

### 5.3 Lazy-Load Rules

Purpose: List every lazy-loadable rule file and describe the condition that triggers its loading.

Content:
- A lead sentence explaining the purpose
- A bullet list with each lazy rule file and its trigger condition

Template:

```
## Lazy-Load Rules

The following rule files are available for conditional loading.
Load them only when the described situation occurs:

- rules/undo-replay.json — Load when reviewing actions that
  mutate undoable state. Check UNDO-001 through UNDO-005.
```

Rules:
- Every file in `lazy_rules` (frontmatter) must appear in this section
- Each entry must include the trigger condition
- Each entry must reference specific rule IDs to check
- If there are no lazy rules, use: `None.` as the content
- This section is NOT optional — if there are no lazy rules, state "None."

### 5.4 Workflow (Non-Phased)

Purpose: Step-by-step execution instructions for the task. The agent follows these steps in order.

Content:
- Numbered or bullet steps
- Each step references specific rule IDs
- Each step that touches a lazy-loaded concern includes a load instruction

See §6 (Workflow Structure) for the detailed specification.

### 5.5 Phase Sections (Phased)

Purpose: Phase-specific workflow for phased tasks. Each phase is a separate H2 section.

Format: `## Phase N: <Name>`

Content:
- Phase description
- List of rule files to load for this phase
- Phase-specific workflow steps
- Phase-specific checklist (if applicable)

See §7 (Phase Structure) for the detailed specification.

### 5.6 Edge Cases

Purpose: Anticipate common edge cases the agent might encounter and provide resolution guidance. This section is read after the workflow — the agent does not need to pre-load edge case knowledge.

Content:
- A bullet list or Q&A format
- Each entry describes an edge case and how to resolve it
- Each entry references rule IDs for authoritative guidance

Template:

```
## Edge Cases

- Manager has dependencies on another table?
  Cross-Manager coordination happens in the action,
  not the Manager. See ARCH-007.

- The action exceeds 15 lines?
  Extract validation logic into Manager::validate* methods.
  See ACTN-001.
```

Rules:
- At least 3 edge cases per prompt
- Each edge case references at least one rule ID
- Edge cases cover the most common failure modes for this task

### 5.7 Stop Conditions

Purpose: Define binary pass/fail criteria that determine when the task is complete. The agent checks every condition before declaring done.

Content:
- A checklist of completion criteria
- Each criterion is a checkbox item
- Each criterion is verifiable (file exists, grep pattern, line count)

Template:

```
## Stop Conditions

This task is complete when:
- [ ] All mandatory rules are loaded and applied
- [ ] Checklist items pass
- [ ] <task-specific condition 1>
- [ ] <task-specific condition 2>
- [ ] All lazy-loaded rules that were triggered are applied
```

Rules:
- At least 3 stop conditions per prompt
- Every condition must be verifiable (binary pass/fail)
- Every checklist from `required_checklists` must be listed as a condition
- Conditions do not reference lazy-rule content (since lazy rules may not be loaded)

### 5.8 Escalation

Purpose: Define when the agent should stop and ask the user for guidance rather than proceeding autonomously.

Content:
- A table or bullet list of escalation scenarios
- Each scenario defines the question the agent should ask

Template:

```
## Escalation

Stop and ask the user when:

| Situation | Question to Ask |
|---|---|
| Game rules ambiguous or contradictory | "The rulebook says X but implies Y. Which interpretation should I follow?" |
| Two standards conflict for this case | "ARCH-003 says X but PERS-007 says Y. Which takes priority?" |
| Migration risks data loss | "This change would alter stored game format. How should I handle live games?" |
```

Rules:
- At least 3 escalation scenarios per prompt
- Scenarios are specific to the task domain (not generic)
- Each scenario includes the exact question the agent should ask
- Escalation must always result in a question, not an assumption

### 5.9 Self-Validation

Purpose: Define how the agent validates its own output before declaring the task complete. This is the final gate before the agent signals completion.

Content:
- Instructions to run the checklist
- Instructions to verify each stop condition
- Instructions to verify lazy-rule compliance (if any were loaded)
- Instructions to verify against the source codebase

Template:

```
## Self-Validation

Before declaring the task complete:

1. Run checklists/pre-review.json
   - Load the checklist file if not already loaded
   - Verify every item passes
   - If any item fails, fix the violation and re-run

2. Verify each stop condition is met
   - Re-read the Stop Conditions section
   - Confirm every condition passes

3. If any lazy rules were loaded:
   - Verify the triggered concerns are correctly addressed
   - Confirm no rule violations were introduced

4. Re-read the modified files
   - Verify no debugging artifacts remain
   - Verify naming is consistent with the codebase conventions

Do not declare the task complete until all validation steps pass.
```

Rules:
- Every prompt must include at least the checklist execution step
- The sequence must reference the specific checklist file(s)
- The sequence must reference the Stop Conditions section
- The sequence must include a verification step for lazy rules (conditional)

---

## 6. Workflow Structure

### 6.1 Step Format

Each workflow step follows this structure:

```
### Step N: <Action>

<Conditional load instruction if applicable>

<Action description referencing specific rule IDs>

<Expected output or verification>
```

### 6.2 Step Numbering

Steps are numbered sequentially: `Step 1`, `Step 2`, etc. There are no sub-steps.

### 6.3 Rule References in Steps

Every step that applies an engineering rule must reference the specific rule ID:

```
Follow ARCH-004 through ARCH-012 for Manager structure.
```

```
Check PERS-001 for schema design rules before creating tables.
```

Rules:
- Every step must reference at least one rule ID
- Use ranges when applicable: `ARCH-004 through ARCH-012`
- Do not reference lazy-loaded rules without a load instruction

### 6.4 Lazy-Rule Load Instructions in Steps

When a step touches a concern covered by a lazy-loaded rule file, the step must include an explicit load instruction:

```
### Step 3: Extract SQL

If the legacy Game.php contains SQL:
1. Load rules/persistence.json
2. Follow PERS-001 through PERS-005 for atomic conditional UPDATE patterns
3. Extract SQL into Manager methods following ARCH-006
```

The load instruction is:
```
Load rules/<filename>.json
```

This instruction tells the agent to perform a Tier 2 lazy-load before referencing the rule IDs in that file.

### 6.5 Example Load Instructions in Steps

When a step needs a canonical example, the step must include an explicit load instruction:

```
### Step 2: Review Canonical Pattern

Load examples/action-example.json before reviewing the target action handler.
```

The load instruction is:
```
Load examples/<filename>.json
```

### 6.6 Step Output

Each step should describe what the agent produces at that step:

```
### Step 4: Extract Models

Load examples/model-example.json.
Create Model classes for the Manager's data following ARCH-011 through ARCH-013.

Output: One Model file per database table owned by this Manager.
```

The output description helps the agent know when the step is complete.

---

## 7. Phase Structure

### 7.1 When to Use Phases

A prompt uses phases (replacing the single `## Workflow` section) when the task has `phase_groups` defined in `index.json`.

Currently: `review-full` (6 phases), `new-feature` (4 phases).

### 7.2 Phase Section Format

Each phase is an H2 section:

```
## Phase 1: <Phase Name>

### Phase Rules

Before starting this phase, load:
- rules/architecture.json

### Steps

#### Step 1: <Action>
...

#### Step 2: <Action>
...

### Phase Checklist

Run before declaring this phase complete:
- Load checklists/pre-commit.json
- Verify all items pass
```

### 7.3 Phase Section Structure

Each phase section contains:

| Subsection | Required | Content |
|---|---|---|
| Phase Rules | Yes | List of rule files to load for this phase |
| Steps | Yes | Phase-specific workflow steps |
| Phase Checklist | Yes | Checklist items for this phase (may reference a checklist file) |

### 7.4 Phase Transition

Between phases, the agent:

1. Runs the current phase's checklist
2. Declares the phase complete
3. Loads the next phase's rule files
4. Reads the next phase's workflow
5. Proceeds with execution

Constitution.json is retained across all phases and should not be re-loaded.

### 7.5 First Phase Must Load Remaining Mandatory Rules

The first phase must load any mandatory rules listed in `required_rules` that are not already loaded:

```
## Phase 1: Architecture and State Design

### Phase Rules

Before starting this phase, load:
- rules/architecture.json
- rules/state-machine.json
```

Constitution.json is always already loaded (Tier 1 mandatory).

---

## 8. Rule References

### 8.1 Reference Format

All rule references in prompts use the following format:

```
See <RULE-ID>.
Follow <RULE-ID> through <RULE-ID>.
Check <RULE-ID>.
```

Examples:
```
See ARCH-003.
Follow ARCH-004 through ARCH-012.
Check ACTN-001.
```

### 8.2 Referencing Loaded Rules

Rules in mandatory files (listed in `required_rules`) can be referenced directly without a load instruction:

```
Follow ARCH-005 for Manager ownership rules.
```

The agent already has these rules in context.

### 8.3 Referencing Lazy Rules

Rules in lazy files must be preceded by a load instruction in the same step:

```
### Step 3: Check Undo Safety

If the action mutates undoable state:
1. Load rules/undo-replay.json
2. Check UNDO-001 through UNDO-005
```

Without the load instruction, the agent cannot reference the rule.

### 8.4 References Must Be Specific

Rule references must be specific. Never write "see the architecture rules" — always write `"see ARCH-003"`.

Good:
```
Follow ARCH-004 through ARCH-012 for Manager structure.
```

Bad:
```
Follow the architecture rules for Manager structure.
```

---

## 9. Lazy-Rule Usage

### 9.1 Declaration

Lazy rules are declared in two places:
1. Frontmatter `lazy_rules` field (metadata)
2. `## Lazy-Load Rules` section (agent instructions)

### 9.2 Lazy-Rule Section Content

```
## Lazy-Load Rules

The following rule files are available for conditional loading.
Load them only when the described situation occurs:

- rules/undo-replay.json — Load when reviewing actions that
  mutate undoable state. Check UNDO-001 through UNDO-005.
- rules/persistence.json — Load when the Manager contains
  SQL queries. Check PERS-001 through PERS-005.
```

### 9.3 Trigger Conditions

Each lazy rule entry must describe:
1. The situation that triggers loading (when condition)
2. What to check after loading (rule IDs to inspect)

Format:
```
- rules/<filename>.json — Load when <situation>. Check <rule-IDs>.
```

### 9.4 Not Loaded By Default

Lazy rules are not loaded in Tier 1. The agent must not pre-load them "just in case." They are loaded only when the trigger condition in a workflow step is met.

### 9.5 Optional Compliance

The agent can complete the task without loading any lazy rules. However, if the trigger condition is met and the agent does not load the rule, the output may violate the rules in that file.

---

## 10. Example Loading

### 10.1 Declaration

Examples are declared in two places:
1. Frontmatter `required_examples` field (metadata)
2. Workflow steps or Lazy-Load Rules section (load instructions)

### 10.2 When to Load Examples

Examples are Tier 2 (lazy-load). They are not loaded by default. The prompt must include an explicit load instruction:

```
Load examples/action-example.json before reviewing the target action handler.
```

### 10.3 Example Reference Format

```
Load examples/<filename>.json to see the canonical <component> pattern.
```

### 10.4 Example Annotation Usage

Examples contain annotations that reference rule IDs. After loading an example, the agent checks the annotations to understand why the pattern is correct:

```
Load examples/manager-example.json.
Review the annotations to see which ARCH rules this pattern satisfies.
```

---

## 11. Checklist Integration

### 11.1 Declaration

Checklists are declared in two places:
1. Frontmatter `required_checklists` field (metadata)
2. Prerequisites section (confirmation of loading)
3. Self-Validation section (execution instruction)

### 11.2 Loading

Checklists are Tier 1 (loaded with task). They are listed in the Prerequisites section and confirmed as loaded.

### 11.3 Execution

The Self-Validation section instructs the agent to run the checklist:

```
1. Load checklists/pre-review.json
   - Verify every item passes
   - If any item fails, fix the violation and re-run
```

### 11.4 Checklist Item Verification

For each checklist item, the agent:
1. Reads the `check` field
2. Applies it to the output
3. If PASS: moves to next item
4. If FAIL: reads the `fix` field, applies the fix, re-checks

### 11.5 Multiple Checklists

When a task has multiple checklists (e.g., `pre-commit.json` and `pre-review.json`), the Self-Validation section lists them in order:

```
1. Run checklists/pre-commit.json
   - Verify every item passes
2. Run checklists/pre-review.json
   - Verify every item passes
```

---

## 12. Stop Conditions

### 12.1 Format

Stop conditions use a Markdown checkbox list:

```
This task is complete when:
- [ ] All mandatory rules are loaded and applied
- [ ] checklists/pre-commit.json passes all items
- [ ] Extracted Manager file exists at modules/php/Managers/<Name>Manager.php
- [ ] No SQL remains in Game.php for the extracted aggregate
```

### 12.2 Verification

Each condition must be verifiable:
- File existence: `"[ ] File exists at path"`
- Pattern match: `"[ ] No SQL in Game.php for this aggregate"`
- Checklist pass: `"[ ] <checklist file> passes all items"`

### 12.3 Relationship to Self-Validation

Stop conditions define WHAT must be true. Self-Validation defines HOW to check those conditions. They are complementary:

- Stop Conditions: the criteria
- Self-Validation: the procedure to verify the criteria

---

## 13. Escalation Rules

### 13.1 When to Escalate

The agent must escalate to the user when:

| Situation | Example Question |
|---|---|
| Game rules ambiguous or contradictory | "The rulebook says X but implies Y. Which interpretation?" |
| Design decision affects user-facing behavior | "Option A matches physical game. Option B is more playable online." |
| Two standards conflict for this case | "Standard A says X, Standard B says Y. Which takes priority?" |
| Migration risks data loss | "This changes stored game format. How to handle live games?" |
| Feature would break architectural invariant | "This would require Manager A writing Manager B's table." |
| Cannot classify the task | "The request does not match any known task type." |

### 13.2 When NOT to Escalate

The agent must NOT escalate for:
- Naming conventions (follow ARCH-020)
- File organization (follow ARCH-018, ARCH-019)
- Code style (follow existing codebase conventions)
- Test coverage decisions (follow TEST-001 through TEST-011)
- Implementation details covered by loaded rules

### 13.3 Escalation Format

Each escalation scenario in the prompt must include the exact question the agent should ask:

```
| Situation | Question to Ask |
|---|---|
| Two standards conflict | "ARCH-003 says Manager X owns table Y, but MIGR-005 recommends splitting. Which takes priority?" |
```

### 13.4 Fallback Escalation

If the agent cannot classify the task using `index.json` keyword matching, it loads the fallback task (`review-full`) and includes this escalation in the prompt:

```
If the user's request does not match any workflow step above,
ask: "I have loaded the full review prompt. Which specific
domain should I focus on? (architecture, state-machine, actions,
persistence, notifications, client, synchronization, animations,
testing, undo-replay, migration)"
```

---

## 14. Self-Validation Sequence

### 14.1 Standard Sequence

Every prompt's Self-Validation section follows this sequence:

```
## Self-Validation

Before declaring the task complete:

1. Run <checklist file>
   - Load the checklist file if not already loaded
   - Verify every item passes
   - If any item fails, fix the violation and re-run

2. Verify each stop condition is met
   - Re-read the Stop Conditions section
   - Confirm every condition passes

3. If any lazy rules were loaded:
   - Verify the triggered concerns are correctly addressed
   - Confirm no rule violations were introduced

4. Re-read the modified files
   - Verify no debugging artifacts remain
   - Verify naming is consistent with codebase conventions

Do not declare the task complete until all steps pass.
```

### 14.2 Fixed Steps

Steps 1, 2, and the closing statement are **fixed** — every prompt must include them verbatim.

Step 3 is conditional (only if lazy rules were loaded).

Step 4 is recommended but may be customized per prompt.

### 14.3 Checklist File Reference

The checklist file referenced in Step 1 must match the first entry in `required_checklists`:

```
1. Run checklists/pre-commit.json
```

If there are multiple checklists, list them in order:

```
1. Run checklists/pre-commit.json
   Verify every item passes.
2. Run checklists/pre-review.json
   Verify every item passes.
```

---

## 15. Complete Templates

### 15.1 Simple Prompt Template

```markdown
---
task: review-action
version: 1.0.0
last_updated: 2026-07-30
source: docs/ai-os/bga-senior-engineer-doctrine.md
required_rules:
  - rules/constitution.json
  - rules/actions.json
lazy_rules:
  - rules/undo-replay.json
required_examples:
  - examples/action-example.json
required_checklists:
  - checklists/pre-review.json
max_tokens: 2940
---

# Review a Single Action Handler

## Prerequisites

Before proceeding, confirm the following files are loaded:
- rules/constitution.json
- rules/actions.json
- checklists/pre-review.json

Do not continue until all files are confirmed loaded.

## Lazy-Load Rules

The following rule files are available for conditional loading.
Load them only when the described situation occurs:

- rules/undo-replay.json — Load when reviewing actions that
  mutate undoable state. Check UNDO-001 through UNDO-005.

## Workflow

### Step 1: Load the Canonical Example

Load examples/action-example.json to see the standard
action handler pattern. Review the annotations.

### Step 2: Verify Action Structure

Check that the action method follows ACTN-001 (under 15 lines)
and ACTN-002 (five ordered responsibilities: validate, execute,
persist, notify, transition).

### Step 3: Check Validation Layers

Verify the action validates against all five layers
from ACTN-003 through ACTN-004 before any mutation.
Confirm ACTN-005: validation completes before mutation begins.

### Step 4: Verify Delegation

Confirm the action delegates all domain work to the owning
Manager (ACTN-006). Zero SQL in the action (ACTN-010).
Zero domain logic in the action (ACTN-011).

### Step 5: Check Notification Pattern

Verify notifications are sent through the centralized
Notifications class (ACTN-007). Sent after all mutations,
before the transition.

### Step 6: Verify Transition

Confirm the action returns a valid transition string
(ACTN-008). The transition must match a key in the
current state's transitions array.

### Step 7: Check Undo Safety

If the action mutates undoable state:
1. Load rules/undo-replay.json
2. Check UNDO-001 through UNDO-005
3. Verify old values are logged before mutation

## Edge Cases

- Action exceeds 15 lines? Extract validation into Manager::validate*
  methods. See ACTN-001.
- Action contains SQL? Move all SQL to Manager methods. See ACTN-010.
- Action returns wrong transition? Verify the transition key
  exists in the current state's transitions. See ACTN-008.
- Action misses a validation layer? Add the missing check.
  See ACTN-003 for the five required layers.
- Notification sent before mutation completes? Reorder:
  validate all, mutate all, notify all. See ACTN-007.

## Stop Conditions

This task is complete when:
- [ ] Action method is under 15 lines
- [ ] All five validation layers are present and in order
- [ ] Action delegates to Manager, contains zero SQL and zero domain logic
- [ ] Notifications are sent through Notifications class after mutations
- [ ] checklists/pre-review.json passes all items
- [ ] If undoable state was found, undo-replay rules are satisfied

## Escalation

Stop and ask the user when:

| Situation | Question to Ask |
|---|---|
| Action has no owning Manager | "This action operates on data that has no clear Manager owner. Which Manager should receive the delegation?" |
| Action mixes concerns from multiple domains | "This action handles both scoring and resource management. Should I split it into two actions?" |
| Undo behavior is unclear | "The action mutates state but there's no undo log. Should I add undo logging now?" |

## Self-Validation

Before declaring the task complete:

1. Run checklists/pre-review.json
   - Load the checklist file if not already loaded
   - Verify every item passes
   - If any item fails, fix the violation and re-run

2. Verify each stop condition is met
   - Re-read the Stop Conditions section
   - Confirm every condition passes

3. If any lazy rules were loaded:
   - Verify the triggered concerns are correctly addressed
   - Confirm no rule violations were introduced

4. Re-read the modified files
   - Verify no debugging artifacts remain
   - Verify naming is consistent with codebase conventions

Do not declare the task complete until all validation steps pass.
```

### 15.2 Phased Prompt Template

```markdown
---
task: new-feature
version: 1.0.0
last_updated: 2026-07-30
source: docs/ai-os/bga-senior-engineer-doctrine.md
required_rules:
  - rules/constitution.json
required_checklists:
  - checklists/pre-commit.json
required_examples:
  - examples/manager-example.json
  - examples/action-example.json
  - examples/model-example.json
  - examples/notification-example.json
  - examples/client-manager-example.json
  - examples/state-example.json
  - examples/undo-example.json
phases:
  - name: design
    rules:
      - rules/architecture.json
      - rules/state-machine.json
  - name: implementation
    rules:
      - rules/persistence.json
      - rules/actions.json
    examples:
      - examples/manager-example.json
      - examples/action-example.json
      - examples/model-example.json
  - name: integration
    rules:
      - rules/notifications.json
      - rules/client.json
    examples:
      - examples/notification-example.json
      - examples/client-manager-example.json
      - examples/state-example.json
    checklists:
      - checklists/pre-commit.json
  - name: undo
    rules:
      - rules/undo-replay.json
    examples:
      - examples/undo-example.json
    checklists:
      - checklists/pre-commit.json
max_tokens: 3140
---

# Add a New Game Feature

## Prerequisites

Before proceeding, confirm the following files are loaded:
- rules/constitution.json
- checklists/pre-commit.json

Do not continue until all files are confirmed loaded.

## Lazy-Load Rules

None.

## Phase 1: Architecture and State Design

### Phase Rules

Before starting this phase, load:
- rules/architecture.json
- rules/state-machine.json

### Steps

#### Step 1: Define Domain Boundary

Determine the aggregate root for the new feature.
Follow ARCH-005 for Manager ownership rules.
Follow the decision hierarchy in ARCH-022:
Is this a new aggregate? Create a new Manager.
Can it be handled by an existing Manager? Extend it.

#### Step 2: Design DB Schema

Design the database schema for the new feature.
Follow PERS-001 (schema before code), PERS-002 (naming),
PERS-003 (InnoDB + FK), PERS-004 (atomic UPDATEs).

Output: Schema design for new tables.

#### Step 3: Design State Flow

Design the state flow for the new feature.
Identify if a new state class is needed (STAT-001 through STAT-003).
Define transition keys (STAT-005).
Plan args payloads (STAT-006).
Identify zombie handling (STAT-009, STAT-010).

Output: State class design with transitions.

### Phase Checklist

Run before declaring this phase complete:
- Verify the domain boundary is clear (one Manager, one aggregate)
- Verify the schema supports all required operations
- Verify the state flow covers all transitions

## Phase 2: Persistence and Actions Implementation

### Phase Rules

Before starting this phase, load:
- rules/persistence.json
- rules/actions.json

Load examples/manager-example.json to see the canonical Manager pattern.
Load examples/action-example.json to see the canonical action handler.
Load examples/model-example.json to see the canonical Model pattern.

### Steps

#### Step 1: Implement Manager

Create the Manager class following ARCH-005 through ARCH-010.
Implement Manager::getAllDatas following ARCH-003.
Implement mutation methods with old-value logging (UNDO-001).
Implement validation methods.

#### Step 2: Implement Models

Create Model classes for the Manager's data.
Follow ARCH-011 through ARCH-013.
Models wrap rows, compute derived values, have zero framework dependencies.

#### Step 3: Implement Actions

Create action handlers following ACTN-001 through ACTN-008.
Each action under 15 lines. Five responsibilities in order.
Delegate to Manager. Notify through Notifications class.

### Phase Checklist

Run checklists/pre-commit.json.
Verify all items pass.

## Phase 3: Notifications and Client Wiring

### Phase Rules

Before starting this phase, load:
- rules/notifications.json
- rules/client.json

Load examples/notification-example.json to see the canonical notification pattern.
Load examples/client-manager-example.json to see the canonical client Manager.
Load examples/state-example.json to see the canonical state class.

### Steps

#### Step 1: Define Notifications

Create notification methods in the centralized Notifications class.
Follow NOTF-001 through NOTF-004.
One static method per notification type.
Use updateArgs() for i18n resolution.
Define public/private split for hidden information (NOTF-008, NOTF-009).

#### Step 2: Wire Client Handlers

Create client Manager following CLNT-001 through CLNT-003.
Register notification handlers in setupPromiseNotifications (CLNT-006).
Handlers are idempotent (CLNT-007).
Use bga.actions.performAction() for all server actions (CLNT-008).

#### Step 3: Wire State Args

Wire state args through the client Manager (CLNT-014).
The client Manager owns the state args for its domain.

### Phase Checklist

Run checklists/pre-commit.json.
Verify all items pass.

## Phase 4: Undo Integrity Wiring

### Phase Rules

Before starting this phase, load:
- rules/undo-replay.json

Load examples/undo-example.json to see the canonical undo pattern.

### Steps

#### Step 1: Wire Undo Logging

Verify every mutation method logs old values (UNDO-001).
Verify undo reverses in LIFO order (UNDO-002).
Verify checkpoints at commit boundaries (UNDO-003).

#### Step 2: Wire Undo Notifications

After undo, send refreshUI for public state (UNDO-010).
After undo, send refreshHand for per-player state (UNDO-010).
After undo, send clearTurn to clear stale indicators (UNDO-011).

#### Step 3: Verify Replay Safety

Verify notification payloads carry absolute values (UNDO-007).
Verify replay handlers render without domain logic (UNDO-008).

### Phase Checklist

Run checklists/pre-commit.json.
Verify all items pass.

## Edge Cases

- New feature creates a new aggregate with no existing Manager?
  Create a new Manager following ARCH-008.
- Existing Manager needs extension but risks exceeding 800 lines?
  Consider splitting into sub-Managers following ARCH-018.
- New feature requires cross-Manager coordination?
  Handle in the action, not by making Managers call each other.
  See ARCH-014.
- New state is needed but the feature is simple?
  Consider using existing states with data-driven choices.
  Follow the decision hierarchy in ARCH-022.
- Game rules for the new feature are ambiguous?
  Escalate to user before implementing.

## Stop Conditions

This task is complete when:
- [ ] Domain boundary is defined with one Manager per aggregate
- [ ] DB schema is designed and supports all operations
- [ ] Manager is implemented with validation and mutation methods
- [ ] Actions are under 15 lines with five ordered responsibilities
- [ ] Notifications are centralized with one method per type
- [ ] Client handlers are idempotent and registered
- [ ] Undo logging is wired with LIFO reversal and checkpoints
- [ ] checklists/pre-commit.json passes all items

## Escalation

Stop and ask the user when:

| Situation | Question to Ask |
|---|---|
| Design affects existing features | "The new feature requires changing <existing>. Should I refactor first?" |
| Game rules ambiguous | "The rulebook says <X> but implies <Y>. Which interpretation?" |
| Performance trade-off | "This design is correct but may be slow for <scenario>. Optimize now or later?" |
| Cross-Manager dependency | "The new Manager needs data from <existing Manager>. Should it read directly or via action?" |

## Self-Validation

Before declaring the task complete:

1. Run checklists/pre-commit.json
   - Load the checklist file if not already loaded
   - Verify every item passes
   - If any item fails, fix the violation and re-run

2. Verify each stop condition is met
   - Re-read the Stop Conditions section
   - Confirm every condition passes

3. If any lazy rules were loaded:
   - Verify the triggered concerns are correctly addressed
   - Confirm no rule violations were introduced

4. Re-read the modified files
   - Verify no debugging artifacts remain
   - Verify naming is consistent with codebase conventions

Do not declare the task complete until all validation steps pass.
```

---

## 16. Validation Rules

### 16.1 Mechanical Checks

Every prompt must pass these checks:

| # | Rule | Severity | How to Check |
|---|---|---|---|
| P01 | File exists in `prompts/<task-id>.md` | ERROR | File system check |
| P02 | Filename matches a task ID in `index.json` | ERROR | Compare stem against `index.json` task keys |
| P03 | Frontmatter is valid YAML | ERROR | Parse with YAML parser |
| P04 | Every frontmatter field from §3.1 that is required is present | ERROR | Schema validation |
| P05 | `task` equals filename stem | ERROR | String comparison |
| P06 | `version` matches SemVer | ERROR | Regex `^\d+\.\d+\.\d+$` |
| P07 | `last_updated` matches ISO 8601 | ERROR | Regex `^\d{4}-\d{2}-\d{2}$` |
| P08 | Every path in `required_rules` exists in `rules/` | ERROR | File system check |
| P09 | Every path in `required_rules` is in the task's `index.json` `rules` | ERROR | Cross-reference `index.json` |
| P10 | Every path in `lazy_rules` is in the task's `lazy_rules` keys in `index.json` | WARNING | Cross-reference `index.json` |
| P11 | Every path in `required_examples` exists in the task's `index.json` `examples` | WARNING | Cross-reference `index.json` |
| P12 | Every path in `required_checklists` is in the task's `index.json` `checklists` | ERROR | Cross-reference `index.json` |
| P13 | `required_checklists` is non-empty | ERROR | Array length check |
| P14 | All 8 required sections from §4.1 are present in order | ERROR | Heading regex match in order |
| P15 | Section headings match exactly (case-sensitive) | ERROR | String comparison |
| P16 | Title (H1) is under 80 characters | WARNING | Character count |
| P17 | At least 3 edge cases | WARNING | Count list items in Edge Cases section |
| P18 | Every edge case references at least one rule ID | WARNING | Regex `[A-Z]{3,5}-\d{3}` in each edge case |
| P19 | At least 3 escalation scenarios | WARNING | Count table rows or list items |
| P20 | At least 3 stop conditions | WARNING | Count checkbox items |
| P21 | Every stop condition is a checkbox | WARNING | Regex `- \[ \]` |
| P22 | Self-Validation section includes Step 1 (run checklist) | ERROR | Contains "Run" + checklist filename |
| P23 | Self-Validation section includes Step 2 (verify stop conditions) | ERROR | Contains "Stop Conditions" |
| P24 | Self-Validation section includes the closing statement | WARNING | Contains "Do not declare" |
| P25 | Every workflow step references at least one rule ID | WARNING | Regex `[A-Z]{3,5}-\d{3}` in each step |
| P26 | Prompt does not exceed 150 lines | WARNING | Line count |
| P27 | Frontmatter `required_rules` matches the `## Prerequisites` list | ERROR | Set comparison |
| P28 | Frontmatter `lazy_rules` matches the `## Lazy-Load Rules` list | WARNING | Set comparison |
| P29 | Frontmatter `required_checklists` matches the checklists in `## Self-Validation` Step 1 | WARNING | Set comparison |
| P30 | Phased prompts have `phases` in frontmatter | ERROR | Presence check for tasks with `phase_groups` in `index.json` |
| P31 | Non-phased prompts do not have `phases` in frontmatter | WARNING | Absence check for tasks without `phase_groups` |
| P32 | Lazy-Load Rules section has at least one entry if `lazy_rules` is non-empty | ERROR | Array length check against frontmatter |
| P33 | Lazy-Load Rules section states "None." if `lazy_rules` is empty or absent | WARNING | String match |

### 16.2 Quick Validation Command

These checks are designed for automation. A future validator could take a prompt file path and return pass/fail for each rule.

---

## 17. Invariants

| Invariant | Description |
|---|---|
| **Section order is fixed** | Prerequisites → Lazy-Load Rules → Workflow/Phases → Edge Cases → Stop Conditions → Escalation → Self-Validation |
| **Every task has a prompt** | All 13 task IDs in `index.json` have a corresponding file in `prompts/` |
| **Every prompt has a task** | Every file in `prompts/` has a corresponding entry in `index.json` |
| **Frontmatter matches index** | `required_rules`, `lazy_rules`, `required_examples`, `required_checklists` are subsets of the task's index entry |
| **Prerequisites match frontmatter** | The Prerequisites section lists exactly the files in `required_rules` + `required_checklists` |
| **Lazy-Load matches frontmatter** | The Lazy-Load Rules section lists exactly the files in `lazy_rules` |
| **Self-Validation includes checklist** | The Self-Validation section always runs at least one checklist file |
| **No rule content embedded** | Prompts never contain rule text — only rule IDs and file paths |
| **Every step references a rule** | Each workflow step applies at least one engineering rule |
| **Lazy rules have triggers** | Every lazy-loaded rule file has a specific condition that triggers its loading |

---

*End of prompt specification. Every prompt produced for the BGA Senior Engineer Skill must conform to this document. Implementation follows in the next milestone.*
