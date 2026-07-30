---
task: migrate-state
version: 1.0.0
last_updated: 2026-07-30
source: docs/ai-os/bga-senior-engineer-doctrine.md
required_rules:
  - rules/constitution.json
  - rules/state-machine.json
lazy_rules:
  - rules/migration.json
required_examples:
  - examples/state-example.json
required_checklists:
  - checklists/pre-commit.json
max_tokens: 2750
---

# Convert Legacy States to State Classes

## Prerequisites

Before proceeding, confirm the following files are loaded:
- rules/constitution.json
- rules/state-machine.json
- checklists/pre-commit.json

Do not continue until all files are confirmed loaded.

## Lazy-Load Rules

The following rule files are available for conditional loading. Load them only when the described situation occurs:

- rules/migration.json — Load when handling legacy states.inc.php patterns. Check MIGR-007.

## Workflow

### Step 1: Load the Canonical Example

Load examples/state-example.json to see the standard State class structure.

### Step 2: Create StateIds Constants

Define named constants for every state ID in a StateIds.php file following STAT-004. Map each numeric state ID from the legacy states.inc.php to a descriptive constant name (PLAYER_TURN, RESOLVE_CHOICE, GAME_END).

### Step 3: Create State Classes

Create one PHP class per state following STAT-001 through STAT-003. Each class defines four public methods in order: args(), action(), transition(), zombie().

Name each class by its purpose following STAT-001. Use semantic transition keys following STAT-005. Ensure args() returns only data the active player is permitted to see following STAT-006.

### Step 4: Implement Zombie Handlers

Every non-GAME state class must implement zombie() following STAT-009 and STAT-010. zombie() receives the disconnected player ID and advances the game. An empty zombie() body is a violation.

### Step 5: Wire giveExtraTime

Call giveExtraTime in onEnteringState for every state that begins or continues a player turn following STAT-011.

### Step 6: Handle Legacy Patterns

If the legacy code uses numeric state IDs inline or non-semantic transition keys, load rules/migration.json and check MIGR-007 for migration guidance.

### Step 7: Clean Up Legacy

Remove the legacy states.inc.php references. Update Game.php to use StateIds constants instead of numeric IDs. Verify all transition references are updated.

## Edge Cases

- Legacy has a single monolithic state with all logic? Break it into purpose-named classes. If the logic is flow control, consider using the Engine tree. See STAT-012.
- State has no zombie handler? Add one. Every non-GAME state requires zombie handling. See STAT-009.
- State args expose hidden information? Restrict args to data the active player is permitted to see. Use the _private key for per-player state. See STAT-006.
- Auto-skipped states that need no player action? Use _no_notify following STAT-007. The state processes and transitions without client notification.
- Simultaneous player actions needed? Use MULTIPLE_ACTIVE_PLAYER with PRIVATE state type. See STAT-016.

## Stop Conditions

This task is complete when:
- [ ] StateIds.php exists with named constants for all state IDs
- [ ] Each state has a dedicated class with args, action, transition, and zombie methods
- [ ] Every non-GAME state has a zombie handler that advances the game
- [ ] giveExtraTime is called on every turn transition
- [ ] All numeric state IDs are replaced with named constants
- [ ] checklists/pre-commit.json passes all items

## Escalation

Stop and ask the user when:

| Situation | Question to Ask |
|---|---|
| State purpose is unclear | "The legacy states.inc.php has a state that handles multiple concerns. Should I split it into separate State classes?" |
| State transition logic is complex | "The legacy state uses conditional transitions based on game state. Should I use the Engine tree for flow control?" |
| Zombie behavior is undefined | "The legacy code has no zombie handling for this state. What should happen when a player disconnects?" |

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
