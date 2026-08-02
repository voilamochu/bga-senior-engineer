---
task: new-feature
version: 1.0.0
last_updated: 2026-07-30
source: docs/ai-os/bga-senior-engineer-doctrine.md
required_rules:
  - references/rules/constitution.json
required_checklists:
  - assets/checklists/pre-commit.json
required_examples:
  - assets/examples/manager-example.json
  - assets/examples/action-example.json
  - assets/examples/model-example.json
  - assets/examples/notification-example.json
  - assets/examples/client-manager-example.json
  - assets/examples/state-example.json
  - assets/examples/undo-example.json
phases:
  - name: design
    rules:
      - references/rules/architecture.json
      - references/rules/state-machine.json
  - name: implementation
    rules:
      - references/rules/persistence.json
      - references/rules/actions.json
    examples:
      - assets/examples/manager-example.json
      - assets/examples/action-example.json
      - assets/examples/model-example.json
  - name: integration
    rules:
      - references/rules/notifications.json
      - references/rules/client.json
    examples:
      - assets/examples/notification-example.json
      - assets/examples/client-manager-example.json
      - assets/examples/state-example.json
    checklists:
      - assets/checklists/pre-commit.json
  - name: undo
    rules:
      - references/rules/undo-replay.json
    examples:
      - assets/examples/undo-example.json
    checklists:
      - assets/checklists/pre-commit.json
max_tokens: 3140
---

# Add a New Game Feature

## Prerequisites

Before proceeding, confirm the following files are loaded:
- references/rules/constitution.json
- assets/checklists/pre-commit.json

Do not continue until all files are confirmed loaded.

## Lazy-Load Rules

None.

## Phase 1: Architecture and State Design

### Phase Rules

Before starting this phase, load:
- references/rules/architecture.json
- references/rules/state-machine.json

### Steps

#### Step 1: Define Domain Boundary

Identify the aggregate root for the new feature. Follow the decision hierarchy in ARCH-022: can this be a data row? A computed property on an existing Model? A single Manager method? A new Manager? Determine the owning Manager following ARCH-005 (one aggregate per Manager).

If the feature creates a new aggregate (a set of DB tables with shared invariants), plan a new Manager following ARCH-008.

#### Step 2: Design DB Schema

Design the database schema before writing any code. Follow PERS-001 (schema before implementation). Use plural snake_case table names (PERS-002). Declare ENGINE=InnoDB with foreign key constraints on identity columns (PERS-003). Design atomic conditional UPDATE patterns for concurrent operations (PERS-004).

#### Step 3: Design State Flow

Determine whether the feature requires new states. Follow STAT-001 through STAT-003 for state class design. Define semantic transition keys following STAT-005. Plan args payloads for each state following STAT-006 — return only data the active player is permitted to see.

Identify zombie handling for every non-GAME state (STAT-009, STAT-010). Plan giveExtraTime calls on each turn transition (STAT-011).

### Phase Checklist

Before declaring this phase complete:
- [ ] Domain boundary is clear: one Manager, one aggregate root
- [ ] Schema design supports all required operations
- [ ] State flow covers all transitions with zombie handlers planned

## Phase 2: Persistence and Actions Implementation

### Phase Rules

Before starting this phase, load:
- references/rules/persistence.json
- references/rules/actions.json

Load assets/examples/manager-example.json to see the canonical Manager pattern.
Load assets/examples/action-example.json to see the canonical action handler.
Load assets/examples/model-example.json to see the canonical Model pattern.

### Steps

#### Step 1: Implement Manager

Create the Manager class following ARCH-005 through ARCH-010. The Manager constructor receives Game reference, DB connection, and typed interfaces for dependent Managers (ARCH-010). Implement getAllDatas following ARCH-003.

Implement mutation methods that record old values to the undo log before writing new state (UNDO-001). Implement validation methods (Manager::validate*) that check preconditions before mutation.

#### Step 2: Implement Models

Create Model classes for the Manager's data following ARCH-011 through ARCH-013. Models wrap a single DB row, compute derived values, and format data for UI presentation. Models must have zero framework dependencies — no Game reference, no DB connection, no globals.

For compound domain concepts (resources, positions, costs), create immutable value objects following ARCH-013.

#### Step 3: Implement Actions

Create action handlers following ACTN-001 through ACTN-008. Each action must be under 15 lines of executable logic (ACTN-001). Follow the five ordered responsibilities: validate, execute, persist, notify, transition (ACTN-002).

Actions validate against all five layers (ACTN-003) before any mutation (ACTN-005). Actions delegate all domain work to the owning Manager (ACTN-006). Zero SQL in actions (ACTN-010). Zero domain logic in actions (ACTN-011). Actions return a valid semantic transition string (ACTN-008).

### Phase Checklist

Before declaring this phase complete, run assets/checklists/pre-commit.json. Verify all items pass.

## Phase 3: Notifications and Client Wiring

### Phase Rules

Before starting this phase, load:
- references/rules/notifications.json
- references/rules/client.json

Load assets/examples/notification-example.json to see the canonical notification pattern.
Load assets/examples/client-manager-example.json to see the canonical client Manager.
Load assets/examples/state-example.json to see the canonical state class.

### Steps

#### Step 1: Define Notifications

Create static notification methods in the centralized Notifications class following NOTF-001 through NOTF-004. One static method per notification type (NOTF-003). Use updateArgs() for automatic i18n and player name resolution (NOTF-004).

Define the refreshUI notification carrying complete public state (NOTF-005). Define refreshHand for per-player hidden state (NOTF-006). Plan the public/private split for draws and hidden information following NOTF-008. Ensure no hidden information leaks in public notification payloads (NOTF-009).

#### Step 2: Wire Client Handlers

Create client-side Manager classes mirroring the server Manager structure following CLNT-001 and CLNT-002. For card games, use BgaCards for all card rendering (CLNT-003).

Register notification handlers in setupPromiseNotifications with the notif_<camelCase> naming convention (CLNT-005, CLNT-006). Every handler must be idempotent (CLNT-007). Use bga.actions.performAction for all server actions (CLNT-008). Never mutate DOM before the server confirms the action (CLNT-009).

#### Step 3: Wire State Args

Consume state args from the server through the client Manager that owns the domain (CLNT-014). The client Manager resolves args into the data structures the UI components expect.

### Phase Checklist

Before declaring this phase complete, run assets/checklists/pre-commit.json. Verify all items pass.

## Phase 4: Undo Integrity Wiring

### Phase Rules

Before starting this phase, load:
- references/rules/undo-replay.json

Load assets/examples/undo-example.json to see the canonical undo pattern.

### Steps

#### Step 1: Wire Undo Logging

Verify every mutation method records old values before writing new state (UNDO-001). Verify undo reverses mutations in strict LIFO order within checkpoint boundaries (UNDO-002). Verify checkpoints are created at each logical commit boundary (UNDO-003).

#### Step 2: Wire Undo Notifications

After an undo completes, send refreshUI for complete public state (UNDO-010). Send refreshHand for each affected player's hidden state (UNDO-010). Send clearTurn to remove stale pending-state indicators (UNDO-011). The order must be: clearTurn first, then refreshUI, then refreshHand (SYNC-006 through SYNC-008).

#### Step 3: Verify Replay Safety

Verify every notification payload carries absolute values, not deltas (UNDO-007). Verify replay notification handlers render payload data without executing domain logic (UNDO-008). For long replay sequences, refreshUI may be used as a shortcut to rebuild public state without replaying every notification (UNDO-009).

### Phase Checklist

Before declaring this phase complete, run assets/checklists/pre-commit.json. Verify all items pass.

## Edge Cases

- New feature creates a new aggregate with no existing Manager? Create a new Manager following ARCH-008. Define the DB schema, Model classes, and Manager API in one pass.
- Existing Manager needs extension but risks exceeding 800 lines? Consider splitting into sub-Managers for separate concerns. Follow ARCH-018 for module size limits.
- New feature requires cross-Manager coordination? Handle coordination in the action, not by making Managers call each other directly. See ARCH-014.
- Simple feature that does not need a new state? Follow the decision hierarchy in ARCH-022. Use existing states with data-driven choices instead of creating a new state class.
- Feature introduces simultaneous player actions? Use MULTIPLE_ACTIVE_PLAYER with PRIVATE state type (STAT-016). Plan command queue for do/undo/reevaluate (UNDO-005).
- Feature affects scoring? Scores are maintained incrementally (PERS-011). Store the primary score in player_score and auxiliary scores in player_score_aux (PERS-007).
- Game rules for the new feature are ambiguous? Escalate to the user before implementing.

## Stop Conditions

This task is complete when:
- [ ] Domain boundary is defined with one Manager per aggregate root
- [ ] DB schema is designed with InnoDB, FK constraints, and atomic UPDATE patterns
- [ ] Manager is implemented with validation methods, mutation methods, and old-value logging
- [ ] Model classes wrap DB rows with computed properties and zero framework dependencies
- [ ] Action handlers are under 15 lines with five ordered responsibilities
- [ ] Notifications are centralized with one static method per type and updateArgs i18n
- [ ] Client handlers are idempotent, registered in setupPromiseNotifications, and use bga.actions
- [ ] Undo logging is wired with LIFO reversal, checkpoints, and correct notification order
- [ ] Replay safety is verified: absolute notification payloads, no domain logic in handlers
- [ ] assets/checklists/pre-commit.json passes all items

## Escalation

Stop and ask the user when:

| Situation | Question to Ask |
|---|---|
| Design affects existing features | "The new feature requires changing the schema of an existing table owned by <Manager>. Should I extend the table or create a new related table?" |
| Game rules ambiguous | "The specification says <X> but the common implementation pattern implies <Y>. Which interpretation should I follow?" |
| Cross-Manager dependency required | "The new Manager needs read access to data owned by <existing Manager>. Should it read via a public getter method, or should the action coordinate both Managers?" |
| Performance trade-off in schema design | "The correct schema design requires a join across two tables for every query. Would you like me to denormalize for performance, or keep the normalized design?" |
| Feature requires new framework API usage | "This feature uses a BGA framework API I have not seen in the reference projects. Should I proceed with the documented API or check for known limitations first?" |

## Self-Validation

Before declaring the task complete:

1. Run assets/checklists/pre-commit.json
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
