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

The following rule files are available for conditional loading. Load them only when the described situation occurs:

- rules/undo-replay.json — Load when reviewing actions that mutate undoable state. Check UNDO-001 through UNDO-005.

## Workflow

### Step 1: Load the Canonical Example

Load examples/action-example.json to see the standard action handler pattern. Review the annotations to understand why each element exists.

### Step 2: Verify Action Size

Check that the action method body is under 15 lines of executable logic (ACTN-001). Count only logic lines — exclude opening braces, blank lines, and comments. If the action exceeds 15 lines, identify which responsibility can be extracted into a Manager::validate* or Manager::execute* method.

### Step 3: Verify Five Responsibilities

Confirm the action follows the five ordered responsibilities from ACTN-002: validate, execute, persist, notify, transition. Each responsibility must appear in exactly this order.

### Step 4: Check Validation Layers

Verify the action validates against all five layers (ACTN-003) before any mutation begins (ACTN-005). The layers are: framework argument types, state permission, game rule preconditions, domain invariants, and persistence constraints.

### Step 5: Verify Delegation

Confirm the action delegates all domain work to the owning Manager (ACTN-006). The action calls Manager::validate*, receives results, then calls Manager::execute*. Zero SQL in the action (ACTN-010). Zero domain logic in the action (ACTN-011).

### Step 6: Check Notification Pattern

Verify notifications are sent through the centralized Notifications class (ACTN-007). Notifications must be sent after all mutations are complete and before the transition return. Follow NOTF-011 for notification timing.

### Step 7: Verify Transition

Confirm the action returns a valid transition string (ACTN-008). The transition must match a key in the current state's transitions array. Transition keys are semantic (cardPlayed, not nextState) following STAT-005.

### Step 8: Check Undo Safety

If the action mutates undoable state:
1. Load rules/undo-replay.json
2. Check UNDO-001: old values are logged before mutation
3. Check UNDO-002: undo reverses in LIFO order
4. Check UNDO-003: checkpoints are set at commit boundaries

## Edge Cases

- Action exceeds 15 lines? Extract validation logic into Manager::validate* methods following ARCH-007. Extract execution logic into Manager::execute* methods.
- Action contains SQL? Move all SQL to Manager methods. The action must contain zero database access. See ACTN-010.
- Action returns wrong transition? Verify the transition key exists in the current state's transitions array. Transition keys are semantic strings. See STAT-005.
- Action misses a validation layer? Add the missing layer in the correct position. See ACTN-003 for the five-layer specification.
- Notification sent before mutation completes? Reorder: validate all, mutate all, notify all. See ACTN-005 and ACTN-007.
- Action handles multiple concerns? If it validates and mutates across aggregate boundaries, split into separate actions. See ARCH-014 for cross-Manager coordination rules.

## Stop Conditions

This task is complete when:
- [ ] Action method body is under 15 lines of executable logic
- [ ] All five ordered responsibilities are present and in the correct sequence
- [ ] All five validation layers are present and complete before any mutation
- [ ] Action delegates all domain work to the owning Manager
- [ ] Action contains zero SQL and zero domain logic
- [ ] Notifications are sent through the Notifications class after mutations and before transition
- [ ] Action returns a valid semantic transition string
- [ ] If undoable state was mutated, old values are logged and undo rules are satisfied
- [ ] checklists/pre-review.json passes all items

## Escalation

Stop and ask the user when:

| Situation | Question to Ask |
|---|---|
| Action has no clear owning Manager | "This action operates on data with no clear Manager owner. Which Manager should receive the delegation?" |
| Action spans multiple aggregates | "This action touches tables owned by different Managers. Should I split it into separate actions, or is there a coordination pattern I should follow?" |
| Undo behavior is undefined for this action | "The action mutates state but there is no undo log. Should I add undo logging now, or is this mutation intentionally irreversible?" |

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
