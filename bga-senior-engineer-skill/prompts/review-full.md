---
task: review-full
version: 1.0.0
last_updated: 2026-07-30
source: docs/ai-os/bga-senior-engineer-doctrine.md
required_rules:
  - rules/constitution.json
required_checklists:
  - checklists/pre-commit.json
  - checklists/pre-review.json
  - checklists/pre-release.json
required_examples:
  - examples/manager-example.json
  - examples/action-example.json
  - examples/model-example.json
  - examples/notification-example.json
  - examples/state-example.json
  - examples/client-manager-example.json
  - examples/undo-example.json
phases:
  - name: architecture
    rules:
      - rules/architecture.json
  - name: state_actions
    rules:
      - rules/state-machine.json
      - rules/actions.json
  - name: data_notifications
    rules:
      - rules/persistence.json
      - rules/notifications.json
  - name: client_sync
    rules:
      - rules/client.json
      - rules/synchronization.json
  - name: undo_animations
    rules:
      - rules/undo-replay.json
      - rules/animations.json
  - name: testing_migration
    rules:
      - rules/testing.json
      - rules/migration.json
    checklists:
      - checklists/pre-review.json
      - checklists/pre-release.json
max_tokens: 3310
---

# Full Pre-Release Audit

## Prerequisites

Before proceeding, confirm the following files are loaded:
- rules/constitution.json
- checklists/pre-commit.json

Do not continue until all files are confirmed loaded.

## Lazy-Load Rules

None.

## Phase 1: Architecture Review

### Phase Rules

Before starting this phase, load:
- rules/architecture.json

### Steps

#### Step 1: Verify Game.php

Confirm Game.php is under 300 lines with zero SQL queries and zero domain logic following ARCH-001 through ARCH-004. setupNewGame instantiates every Manager. getAllDatas delegates to each Manager. zombie delegates to the current state.

#### Step 2: Verify Manager Ownership

Confirm every Manager owns exactly one aggregate root following ARCH-005 and ARCH-006. No cross-Manager writes. No Manager-to-Manager calls following ARCH-014.

#### Step 3: Verify Component Boundaries

Confirm the communication path follows ARCH-016: Game.php delegates to Managers, Managers delegate to Models. No layer skipping. Engine nodes contain zero domain logic following ARCH-017.

### Phase Checklist

Before declaring this phase complete:
- [ ] Game.php under 300 lines with zero SQL and zero domain logic
- [ ] Each Manager owns one aggregate root with exclusive write ownership
- [ ] Component communication follows the defined protocol

## Phase 2: State Machine and Actions Review

### Phase Rules

Before starting this phase, load:
- rules/state-machine.json
- rules/actions.json

### Steps

#### Step 1: Verify State Classes

Every state is a dedicated PHP class following STAT-001 through STAT-003 with args, action, transition, and zombie methods. State IDs use named constants following STAT-004. Transition keys are semantic following STAT-005.

#### Step 2: Verify Zombie and Time

Every non-GAME state has a zombie handler following STAT-009 and STAT-010. giveExtraTime is called on every turn transition following STAT-011.

#### Step 3: Verify Action Handlers

Every action handler is under 15 lines following ACTN-001. Actions follow five ordered responsibilities following ACTN-002. Actions validate before mutation following ACTN-005. Actions delegate to Managers following ACTN-006.

### Phase Checklist

Before declaring this phase complete, run checklists/pre-commit.json. Verify all items pass.

## Phase 3: Persistence and Notifications Review

### Phase Rules

Before starting this phase, load:
- rules/persistence.json
- rules/notifications.json

### Steps

#### Step 1: Verify DB Schema

All game tables use ENGINE=InnoDB with FK constraints following PERS-003. State-changing queries use atomic conditional UPDATE patterns following PERS-004. No entity data in global_variables following PERS-008.

#### Step 2: Verify Data-Driven Configuration

Capacities, costs, and ratios are in the database or material.inc.php following PERS-009 and PERS-010. Scores are incremental on the player table following PERS-007 and PERS-011.

#### Step 3: Verify Notifications

All notifications go through a centralized Notifications class following NOTF-001. One static method per type following NOTF-003. updateArgs handles i18n following NOTF-004. Hidden information is protected following NOTF-009.

### Phase Checklist

Before declaring this phase complete, run checklists/pre-commit.json. Verify all items pass.

## Phase 4: Client and Synchronization Review

### Phase Rules

Before starting this phase, load:
- rules/client.json
- rules/synchronization.json

### Steps

#### Step 1: Verify Client Architecture

Client code is organized into Manager classes mirroring the server structure following CLNT-001. Notification handlers use notif_<camelCase> naming following CLNT-005. All handlers are registered in setupPromiseNotifications following CLNT-006.

#### Step 2: Verify Client Action Wiring

All server actions use bga.actions.performAction following CLNT-008. No DOM mutation before server confirmation following CLNT-009.

#### Step 3: Verify Reconnect

getAllDatas delegates to every Manager following SYNC-001. refreshUI and refreshHand are the canonical reconnect path following SYNC-002. Spectators never receive private notifications following SYNC-005.

### Phase Checklist

Before declaring this phase complete, run checklists/pre-commit.json. Verify all items pass.

## Phase 5: Undo and Animation Review

### Phase Rules

Before starting this phase, load:
- rules/undo-replay.json
- rules/animations.json

### Steps

#### Step 1: Verify Undo Logging

Every mutation method records old values before writing following UNDO-001. Undo reverses in LIFO order within checkpoint boundaries following UNDO-002. Checkpoints at commit boundaries following UNDO-003.

#### Step 2: Verify Replay Safety

Notification payloads carry absolute values following UNDO-007. Replay handlers render without domain logic following UNDO-008. refreshUI shortcut is available for long replays following UNDO-009.

#### Step 3: Verify Animations

BgaAnimations.Manager is instantiated once following ANIM-001. animationsActive is wired to player preference following ANIM-002. Animations are disabled during reconnect following ANIM-007. Animation durations follow framework constraints following ANIM-009.

### Phase Checklist

Before declaring this phase complete, run checklists/pre-commit.json. Verify all items pass.

## Phase 6: Testing and Migration Review

### Phase Rules

Before starting this phase, load:
- rules/testing.json
- rules/migration.json

### Steps

#### Step 1: Verify Test Coverage

Manager public methods have unit tests following TEST-002. Scoring functions have tests with known input and output following TEST-003. Unique card abilities have dedicated tests following TEST-004.

#### Step 2: Verify Undo and Replay Tests

Mutation paths that support undo have undo tests following TEST-005. A replay test executes N moves and replays from start following TEST-006.

#### Step 3: Verify Migration Readiness

If the project has legacy code, verify the extraction order follows MIGR-014. Verify migration maintains parity following MIGR-015.

### Phase Checklist

Before declaring this phase complete:
1. Run checklists/pre-review.json. Verify all items pass.
2. Run checklists/pre-release.json. Verify all items pass.

## Edge Cases

- Game uses Engine tree? Verify Engine nodes contain zero domain logic. See STAT-012.
- Game has simultaneous turns? Verify MULTIPLE_ACTIVE_PLAYER with PRIVATE state type. See STAT-016.
- Game has custom card abilities? Each unique ability needs a dedicated test. See TEST-004.
- Game has legacy code from a previous BGA framework version? Follow migration extraction order. See MIGR-014.
- Game uses complex animations? Verify animations are disabled during reconnect and replay. See ANIM-007 and ANIM-010.

## Stop Conditions

This task is complete when:
- [ ] Game.php is under 300 lines with zero domain logic
- [ ] All Managers own one aggregate root with exclusive write ownership
- [ ] All states have zombie handlers and giveExtraTime is wired
- [ ] All action handlers are under 15 lines with five ordered responsibilities
- [ ] Database uses InnoDB with atomic UPDATEs and no entity data in globals
- [ ] Notifications are centralized with i18n and hidden information protected
- [ ] Client handlers are idempotent and registered in setupPromiseNotifications
- [ ] Reconnect path uses getAllDatas delegation with refreshUI and refreshHand
- [ ] Undo logging records old values with LIFO reversal
- [ ] Animations are disabled during reconnect and replay
- [ ] Tests cover Managers, scoring, unique abilities, undo, and replay
- [ ] checklists/pre-release.json passes all items

## Escalation

Stop and ask the user when:

| Situation | Question to Ask |
|---|---|
| Critical architectural issue found | "The codebase has a fundamental architectural issue: <description>. Should I fix it now or create a follow-up task?" |
| Pre-release checklist item cannot be verified | "I cannot verify <checklist item> because <reason>. Can you confirm this is handled correctly?" |
| Multiple issues found in the same domain | "I found <N> issues in <domain>. Should I fix all of them, or prioritize by severity?" |

## Self-Validation

Before declaring the task complete:

1. Run checklists/pre-release.json
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
