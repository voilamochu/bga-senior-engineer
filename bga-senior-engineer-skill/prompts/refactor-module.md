---
task: refactor-module
version: 1.0.0
last_updated: 2026-07-30
source: docs/ai-os/bga-senior-engineer-doctrine.md
required_rules:
  - rules/constitution.json
  - rules/architecture.json
lazy_rules:
  - rules/actions.json
  - rules/notifications.json
  - rules/persistence.json
  - rules/state-machine.json
required_examples:
  - examples/manager-example.json
required_checklists:
  - checklists/pre-commit.json
  - checklists/pre-review.json
max_tokens: 3310
---

# Refactor a Module to Canonical Standards

## Prerequisites

Before proceeding, confirm the following files are loaded:
- rules/constitution.json
- rules/architecture.json
- checklists/pre-commit.json
- checklists/pre-review.json

Do not continue until all files are confirmed loaded.

## Lazy-Load Rules

The following rule files are available for conditional loading. Load them only when the described situation occurs:

- rules/actions.json — Load when refactoring action handlers. Check ACTN-001 through ACTN-014.
- rules/notifications.json — Load when refactoring notification code. Check NOTF-001 through NOTF-014.
- rules/persistence.json — Load when refactoring database access. Check PERS-001 through PERS-014.
- rules/state-machine.json — Load when refactoring state logic. Check STAT-001 through STAT-016.

## Workflow

### Step 1: Assess the Module

Review the module against ARCH-018 (module size) and ARCH-019 (spatial proximity). Identify which concerns are mixed together. Determine the target architecture: which Managers, Models, and supporting classes should exist after the refactor.

Load examples/manager-example.json to see the canonical Manager pattern.

### Step 2: Wrap with Tests

If the module lacks test coverage, add tests before refactoring. Follow the extraction safety rule: no refactor without tests. One concern per commit.

### Step 3: Extract Manager Boundaries

Identify the aggregate root for each distinct concern following ARCH-005. Each Manager owns one table group. Extract the simplest Manager first.

If the module contains database access patterns, load rules/persistence.json and check PERS-001 through PERS-014.

### Step 4: Extract Models

Create Model classes wrapping database rows following ARCH-011 through ARCH-013. Replace raw array usage with typed Models. Models have zero framework dependencies.

### Step 5: Refactor Actions

If the module contains action handlers, load rules/actions.json and check ACTN-001 through ACTN-014. Actions must be under 15 lines with five ordered responsibilities. Actions delegate to Managers.

### Step 6: Refactor Notifications

If the module contains notification logic, load rules/notifications.json and check NOTF-001 through NOTF-014. Notifications must be centralized with one static method per type.

### Step 7: Refactor State Logic

If the module contains state transition logic, load rules/state-machine.json and check STAT-001 through STAT-016. State classes must have args, action, transition, and zombie methods.

### Step 8: Verify Post-Refactor

Run checklists/pre-commit.json. Verify the refactored module compiles and passes all existing tests. Delete dead code last following the refactoring doctrine.

## Edge Cases

- Module exceeds 800 lines with tightly coupled concerns? Extract one concern at a time. Start with the most independent table group.
- Module has no existing tests? Wrap with tests before refactoring. Focus on the public API methods that will change.
- Module mixes action, state, and notification logic? Extract in order: Manager first, then Models, then Actions, then Notifications, then States.
- Refactoring would break existing callers? Use temporary adapters following MIGR-017. Maintain backward compatibility during incremental extraction.
- Module uses global_variables directly? Extract a typed Globals wrapper following MIGR-005.

## Stop Conditions

This task is complete when:
- [ ] Module is organized into clear Manager boundaries with one aggregate per Manager
- [ ] All data access uses Model objects instead of raw arrays
- [ ] Action handlers delegate to Managers and are under 15 lines
- [ ] Notifications are centralized in the Notifications class
- [ ] State logic uses dedicated State classes with zombie handling
- [ ] All existing tests pass after the refactor
- [ ] Dead code from the pre-refactor module is removed
- [ ] checklists/pre-commit.json and checklists/pre-review.json pass all items

## Escalation

Stop and ask the user when:

| Situation | Question to Ask |
|---|---|
| Module has no clear aggregate boundary | "The module's tables are interrelated and do not form a clean aggregate. How should I split them?" |
| Refactoring would break a live game feature | "This refactoring changes the schema or API of a feature currently in use. Should I proceed or defer?" |
| Module requires a new pattern not in the codebase | "The module would benefit from the Engine tree pattern, but it is not used anywhere in the codebase. Should I introduce it?" |

## Self-Validation

Before declaring the task complete:

1. Run checklists/pre-commit.json
   - Load the checklist file if not already loaded
   - Verify every item passes
   - If any item fails, fix the violation and re-run

2. Run checklists/pre-review.json
   - Verify every item passes

3. Verify each stop condition is met
   - Re-read the Stop Conditions section
   - Confirm every condition passes

4. If any lazy rules were loaded:
   - Verify the triggered concerns are correctly addressed
   - Confirm no rule violations were introduced

5. Re-read the modified files
   - Verify no debugging artifacts remain
   - Verify naming is consistent with codebase conventions

Do not declare the task complete until all validation steps pass.
