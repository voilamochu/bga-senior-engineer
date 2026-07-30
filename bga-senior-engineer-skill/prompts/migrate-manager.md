---
task: migrate-manager
version: 1.0.0
last_updated: 2026-07-30
source: docs/ai-os/bga-senior-engineer-doctrine.md
required_rules:
  - rules/constitution.json
  - rules/architecture.json
lazy_rules:
  - rules/persistence.json
  - rules/migration.json
required_examples:
  - examples/manager-example.json
  - examples/model-example.json
required_checklists:
  - checklists/pre-commit.json
max_tokens: 3310
---

# Extract a Manager from Legacy Game.php

## Prerequisites

Before proceeding, confirm the following files are loaded:
- rules/constitution.json
- rules/architecture.json
- checklists/pre-commit.json

Do not continue until all files are confirmed loaded.

## Lazy-Load Rules

The following rule files are available for conditional loading. Load them only when the described situation occurs:

- rules/persistence.json — Load when extracting SQL from Game.php. Check PERS-001 through PERS-005.
- rules/migration.json — Load when planning extraction sequence. Check MIGR-001 through MIGR-014.

## Workflow

### Step 1: Identify the Aggregate

Determine the database table group this Manager will own. Each Manager owns one aggregate root following ARCH-005. Identify the primary table and all related tables that share invariants with it.

### Step 2: Load the Canonical Example

Load examples/manager-example.json to see the standard Manager structure. Load examples/model-example.json to see the standard Model pattern.

### Step 3: Extract the Manager Class

Create the Manager class following ARCH-005 through ARCH-010. The Manager constructor receives Game reference, DB connection, and typed interfaces (ARCH-010). Move domain methods from Game.php into the Manager following ARCH-004.

If the legacy Game.php contains SQL, load rules/persistence.json and check PERS-001 through PERS-005 for atomic conditional UPDATE patterns.

### Step 4: Extract Models

Create Model classes for the Manager's data following ARCH-011 through ARCH-013. Models wrap database rows, compute derived values, and have zero framework dependencies. Replace raw array usage with typed Model objects following ARCH-009.

### Step 5: Wire the Action

Create or update the action handler to delegate to the new Manager following ACTN-001 through ACTN-005. The action validates preconditions, calls Manager::validate*, calls Manager::execute*, sends notifications through the Notifications class, and returns a transition.

### Step 6: Plan Migration Sequence

If the extraction is part of a larger migration, load rules/migration.json and plan the sequence following MIGR-014 (config files first, then Managers, then state machine). Extract one concern per commit following MIGR-009.

## Edge Cases

- Manager has dependencies on another table? Cross-Manager coordination happens in the action, not the Manager. See ARCH-014.
- Legacy code uses raw arrays? Extract Models first before extracting the Manager methods. See Step 4.
- Original file has inline SQL? Move all SQL to Manager methods using atomic conditional UPDATE patterns. See PERS-004.
- Game.php already has multiple concerns? Extract the simplest aggregate first. Start with the table that has the fewest dependencies.
- Manager would exceed 800 lines? Split into sub-Managers for separate concerns. See ARCH-018.

## Stop Conditions

This task is complete when:
- [ ] Extracted Manager class exists with correct class structure
- [ ] Manager owns exactly one aggregate root
- [ ] All Manager methods delegate to Models, not raw arrays
- [ ] No SQL remains in the original Game.php for this aggregate
- [ ] Action handler delegates to the Manager correctly
- [ ] checklists/pre-commit.json passes all items

## Escalation

Stop and ask the user when:

| Situation | Question to Ask |
|---|---|
| Aggregate boundary is unclear | "The legacy code mixes tables from multiple domains. Which tables should this Manager own?" |
| Migration sequence affects live games | "This extraction changes the database schema. Should I create a migration path for live games?" |
| Manager depends on another unextracted Manager | "The new Manager needs data from a Manager that has not been extracted yet. Should I create a temporary adapter?" |

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
