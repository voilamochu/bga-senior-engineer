---
task: review-state-machine
version: 1.0.0
last_updated: 2026-07-30
source: docs/ai-os/bga-senior-engineer-doctrine.md
required_rules:
  - rules/constitution.json
  - rules/state-machine.json
required_examples:
  - examples/state-example.json
required_checklists:
  - checklists/pre-review.json
max_tokens: 2750
---

# Review the State Machine

## Prerequisites

Before proceeding, confirm the following files are loaded:
- rules/constitution.json
- rules/state-machine.json
- checklists/pre-review.json

Do not continue until all files are confirmed loaded.

## Lazy-Load Rules

None.

## Workflow

### Step 1: Load the Canonical Example

Load examples/state-example.json to see the standard State class pattern. Review the annotations.

### Step 2: Verify State Class Structure

Every state must be a dedicated PHP class following STAT-001 through STAT-003. Each class defines four public methods in order: args(), action(), transition(), zombie(). No exceptions.

### Step 3: Check StateIds Constants

All state IDs must be defined as named constants in a StateIds.php file following STAT-004. No numeric state IDs should appear inline.

### Step 4: Verify Transition Keys

Transition keys must be semantic strings describing the event (cardPlayed, passed, resolved) following STAT-005. Generic keys like nextState or ok are violations.

### Step 5: Verify Args Payloads

Each args() method must return only data the active player is permitted to see following STAT-006. Use the _private key for per-player state. Verify no hidden information leaks in args.

### Step 6: Verify Zombie Handlers

Every non-GAME state must implement zombie() following STAT-009 and STAT-010. zombie() must actually advance the game for the disconnected player — an empty zombie() body is a violation.

### Step 7: Verify giveExtraTime

onEnteringState must call giveExtraTime for every state that begins or continues a player turn following STAT-011.

### Step 8: Check State Types

Verify the correct state type is used: PUBLIC for shared states, PRIVATE for per-player decision states, MULTIPLE_ACTIVE_PLAYER for simultaneous turns following STAT-008 and STAT-016. Use _no_notify for auto-skipped states following STAT-007.

### Step 9: Check Engine Usage

If the game uses the Engine tree, verify Engine nodes contain zero domain logic following STAT-012. Nodes delegate to Managers. Engine state is serialized to global_variables following STAT-013.

### Step 10: Verify No State-per-Variant Anti-Pattern

Verify there is not one state per card, space, or action variant following STAT-015. States model flow phases. Variants are data-driven.

## Edge Cases

- State has no zombie handler? Add one. Every non-GAME state requires it. See STAT-009.
- State uses numeric state IDs in transitions? Replace with StateIds constants. See STAT-004.
- State has a single state handling multiple phases? Split into separate State classes. Each state has one purpose.
- State uses Engine tree but has domain logic in nodes? Move domain logic to Managers. Engine nodes orchestrate only. See STAT-012.
- Simultaneous action state does not use PRIVATE? Change to PRIVATE state type. See STAT-016.

## Stop Conditions

This task is complete when:
- [ ] Every state is a dedicated class with args, action, transition, and zombie methods
- [ ] All state IDs use named constants in StateIds.php
- [ ] All transition keys are semantic strings
- [ ] Every non-GAME state has a zombie handler that advances the game
- [ ] giveExtraTime is called on every turn transition
- [ ] Correct state types are used for all states
- [ ] Engine nodes contain zero domain logic
- [ ] No state-per-variant anti-pattern exists
- [ ] checklists/pre-review.json passes all items

## Escalation

Stop and ask the user when:

| Situation | Question to Ask |
|---|---|
| State machine flow is non-standard | "The state flow uses a pattern I have not seen in the reference projects. Should I standardize it or keep the custom flow?" |
| Zombie behavior must handle game-specific rules | "The zombie handler for this state needs game-specific logic to advance. What should happen to the disconnected player's resources?" |
| Engine usage versus manual states is unclear | "The current implementation mixes Engine tree nodes with manual state transitions. Should I fully migrate to one pattern?" |

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
