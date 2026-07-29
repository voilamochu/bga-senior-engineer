# BGA State Machine Architecture — Engineering Standard

**Document purpose:** Define the canonical approach for designing, structuring, evolving and maintaining BGA game state machines. This standard is derived from the official BGA framework API and from architecture analysis of the four reference projects: Arnak, Agricola, Ark Nova, and Earth.

**Applicability:** All new BGA game implementations. Existing projects should use this document as a reference when refactoring state machines.

**Cross-references:**
- [game-flow-architecture.md](./game-flow-architecture.md) — execution pipeline, request lifecycle, transaction model
- [notification-patterns.md](./notification-patterns.md) — notification design, public/private patterns, sequencing
- [bga-developer-handbook.md](../foundation/bga-developer-handbook.md) — framework API reference, state class reference
- [bga-ai-implementation-reference.md](../foundation/bga-ai-implementation-reference.md) — AI/bot integration with state machine
- [reference-project-analysis.md](../foundation/reference-project-analysis.md) — project-specific architecture ratings and lineage

---

## Table of Contents

- [1. Purpose of the State Machine](#1-purpose-of-the-state-machine)
- [2. Relationship: Game Flow, States, Actions, Notifications, Client UI](#2-relationship-game-flow-states-actions-notifications-client-ui)
- [3. State Taxonomy](#3-state-taxonomy)
- [4. State Lifecycle](#4-state-lifecycle)
- [5. State Responsibilities](#5-state-responsibilities)
- [6. Designing Good States](#6-designing-good-states)
- [7. State Granularity](#7-state-granularity)
- [8. Transition Design](#8-transition-design)
- [9. Transition Naming Conventions](#9-transition-naming-conventions)
- [10. State Arguments](#10-state-arguments)
- [11. Automatic States](#11-automatic-states)
- [12. Private States](#12-private-states)
- [13. Multiple-Active-Player States](#13-multiple-active-player-states)
- [14. State-Machine Scaling](#14-state-machine-scaling)
- [15. Common State-Machine Anti-Patterns](#15-common-state-machine-anti-patterns)
- [16. Debugging State Machines](#16-debugging-state-machines)
- [17. Refactoring Large State Machines](#17-refactoring-large-state-machines)
- [18. Recommended Canonical Architecture](#18-recommended-canonical-architecture)

---

## 1. Purpose of the State Machine

The BGA state machine is the central nervous system of every game implementation. It serves four distinct purposes:

**1. Flow control.** It defines the sequence of phases, turns, and steps that constitute a game from start to finish. The state machine determines what happens when, and in what order.

**2. Permission management.** It determines which player(s) — if any — are authorised to act at any moment. An `ACTIVE_PLAYER` state permits exactly one player to act; a `MULTIPLE_ACTIVE_PLAYER` state permits a set of players; a `GAME` state permits no one (the server acts automatically).

**3. Scope definition.** Each state defines the set of legal actions that a player may take. The framework rejects any action not listed in the state's `possibleactions` or not decorated with `#[PossibleAction]`.

**4. Client synchronisation.** State transitions trigger notifications that inform every client of the current phase. The client uses this information to render appropriate UI controls, update status text, and enable/disable interaction.

```
┌────────────────────────────────────────────────────────────┐
│                    STATE MACHINE                            │
│                                                            │
│  ┌──────────┐   transition   ┌──────────┐   transition   ┌─┴──────────┐
│  │ State 1  │ ─────────────► │ State 2  │ ─────────────► │ State 99   │
│  │ (GAME)   │               │ (ACTIVE  │               │ (Game End) │
│  │          │               │  PLAYER) │               │            │
│  └──────────┘               └────┬─────┘               └────────────┘
│                                  │
│                    ┌─────────────┼─────────────┐
│                    ▼             ▼             ▼
│              ┌──────────┐ ┌──────────┐ ┌──────────┐
│              │ Player A │ │ Player B │ │ Player C │
│              │ acts     │ │ waits    │ │ waits    │
│              └──────────┘ └──────────┘ └──────────┘
│                                                            │
│  Controls: flow, permissions, scope, client sync           │
└────────────────────────────────────────────────────────────┘
```

---

## 2. Relationship: Game Flow, States, Actions, Notifications, Client UI

The five concepts form a strict hierarchy within each request-response cycle. Their relationship is defined by the execution pipeline documented in [game-flow-architecture.md §2](./game-flow-architecture.md#2-overall-execution-pipeline).

### 2.1 Conceptual Model

```
GAME FLOW (the overall sequence of phases in a game)
    │
    ├── composed of transitions between STATES
    │       │
    │       ├── each STATE defines which ACTIONS are legal
    │       │       │
    │       │       ├── each ACTION triggers domain logic
    │       │       │       │
    │       │       │       └── sends NOTIFICATIONS
    │       │       │
    │       │       └── returns a transition key → next STATE
    │       │
    │       └── each STATE sends args to CLIENT UI
    │               │
    │               └── client renders controls, text, state
    │
    └── cycles until GAME END (state 99)
```

### 2.2 Request-Response Cycle

```
CLIENT UI                    STATE MACHINE                GAME SERVER
    │                              │                          │
    │── performAction() ──────────►│                          │
    │                              │── route to state ──────►│
    │                              │                          │── VALIDATE
    │                              │                          │── EXECUTE
    │                              │                          │── NOTIFY
    │                              │◄── return transition ───│
    │                              │── advance state ───────►│
    │◄── notifications + args ─────│                          │
    │                              │                          │
    │── onEnteringState(state)     │                          │
    │── render UI for new state    │                          │
```

### 2.3 Responsibility Mapping

| Concept | Lives In | Purpose | Persistence |
|---|---|---|---|
| **Game Flow** | Developer's design document | High-level phase sequence | Documentation only |
| **States** | `modules/php/States/*.php` | Define transitions, args, possible actions | Declared in class constructor |
| **Actions** | `#[PossibleAction]` methods on state classes | Validate, execute, notify, return transition | None (request-scoped) |
| **Notifications** | `Core/Notifications.php` | Broadcast state changes to clients | Stored in `gamelog` table |
| **Client UI** | `Game.js` + Manager classes | Render state, handle input, process notifications | JavaScript heap only |

### 2.4 Key Principle: State Machine Is the Source of Truth

The state machine defines what is *possible*. The game flow defines what is *intended*. The state machine may have more states than the game flow suggests — auxiliary states for setup, error recovery, undo, and edge cases. The game flow is a subset of the state machine's reachable paths.

For the full execution pipeline (validate → execute → persist → notify → transition), see [game-flow-architecture.md §2.1](./game-flow-architecture.md#21-the-canonical-pipeline). For notification design and sequencing, see [notification-patterns.md](./notification-patterns.md).

---

## 3. State Taxonomy

The BGA framework defines four state types. Two additional categories — Manager and End Game — are architectural conventions layered on top.

### 3.1 Framework State Types

```
┌─────────────────────────────────────────────────────────────────────┐
│                      STATE TAXONOMY                                 │
│                                                                     │
│  ┌─────────────────┐   ┌─────────────────┐   ┌──────────────────┐  │
│  │  ACTIVE_PLAYER  │   │  GAME           │   │  MULTIPLE_ACTIVE │  │
│  │                 │   │                 │   │  _PLAYER         │  │
│  │  Exactly one    │   │  No player      │   │                  │  │
│  │  player acts    │   │  acts (auto)    │   │  Subset of       │  │
│  │                 │   │                 │   │  players act     │  │
│  └────────┬────────┘   └────────┬────────┘   └────────┬─────────┘  │
│           │                    │                      │            │
│           │                    │                      │            │
│           ▼                    ▼                      ▼            │
│  ┌─────────────────┐   ┌─────────────────┐   ┌──────────────────┐  │
│  │  PRIVATE         │   │  (none)         │   │  PRIVATE         │  │
│  │                  │   │                 │   │  (per-player)    │  │
│  │  Used inside     │   │                 │   │  Used inside     │  │
│  │  ACTIVE_PLAYER   │   │                 │   │  MULTIPLE_ACTIVE │  │
│  │  (rare)          │   │                 │   │  _PLAYER         │  │
│  └─────────────────┘   └─────────────────┘   └──────────────────┘  │
└─────────────────────────────────────────────────────────────────────┘
```

### 3.2 Type Reference Table

| Type | Constant | Player Active? | Typical Use | Zombie Required? |
|---|---|---|---|---|
| **Active Player** | `StateType::ACTIVE_PLAYER` | Yes — exactly one | Normal turns, choice resolution | Yes |
| **Game** | `StateType::GAME` | No | Setup, scoring, automatic transitions, game end | No |
| **Multiple Active Player** | `StateType::MULTIPLE_ACTIVE_PLAYER` | Yes — a subset | Simultaneous discards, simultaneous choices | Yes |
| **Private** | `StateType::PRIVATE` | Yes — one per instance | Per-player decisions inside multiactive | Yes |

### 3.3 Architectural Categories

Beyond the four framework types, two additional categories are architectural conventions:

**Manager states** are not states in the framework sense. They are the `Managers/` classes that encapsulate domain logic and are called by state actions. (See [game-flow-architecture.md §3](./game-flow-architecture.md#3-separation-of-responsibilities) for the Manager pattern.)

**End Game** is the terminal state (reserved ID 99). It is a `GAME`-type state that runs final scoring and produces the results screen. All reachable state paths must ultimately terminate at state 99.

### 3.4 State Type Decision Guide

```
Does the state need player input?
  ├── NO  → StateType::GAME
  │
  └── YES → Does exactly one player need to act?
  │           ├── YES → StateType::ACTIVE_PLAYER
  │           └── NO  → StateType::MULTIPLE_ACTIVE_PLAYER
  │
  Does each active player need a separate, independent flow?
  ├── YES → Use initialPrivate with PRIVATE states
  └── NO  → Single state, players act on shared args
```

---

## 4. State Lifecycle

Every state passes through a well-defined lifecycle within each visit.

### 4.1 Lifecycle Diagram

```
                      STATE ENTERED
                           │
                           ▼
              ┌─────────────────────────┐
              │  1. onEnteringState()   │ ← called before any args or actions
              │    - Initialize state   │
              │    - Check auto-resolve │
              └───────────┬─────────────┘
                          │
                          ▼
              ┌─────────────────────────┐
              │  2. getArgs()           │ ← framework calls on server, delivers to client
              │    - Compute state args │
              │    - Set _no_notify     │
              │    - Set _private       │
              └───────────┬─────────────┘
                          │
                          ▼
              ┌─────────────────────────┐
              │  3. Client renders UI   │ ← onUpdateActionButtons, onEnteringState (client)
              │    - Show action buttons│
              │    - Display state text │
              └───────────┬─────────────┘
                          │
                          ▼
              ┌─────────────────────────┐
              │  4. Player Action OR    │ ← act*() method
              │     Automatic Action    │ ← action method (GAME states)
              │    - Validate           │
              │    - Execute            │
              │    - Notify             │
              └───────────┬─────────────┘
                          │
                          ▼
              ┌─────────────────────────┐
              │  5. Transition          │ ← return transition string
              │    - Framework advances │
              │    - Enters next state  │
              └───────────┬─────────────┘
                          │
                          ▼
              ┌─────────────────────────┐
              │  6. onLeavingState()    │ ← cleanup (rarely used)
              │    - Save ephemeral     │
              │    - Release resources  │
              └─────────────────────────┘
```

### 4.2 Phase Details

#### Phase 1: `onEnteringState()`

Called by the framework immediately after entering the state, before any args are sent to the client. This is the place for:
- Initialising state-specific data structures
- Checking whether the state should auto-resolve (see §11)
- Setting up timers or time limits
- Ensuring game invariants hold before presenting choices

```php
public function onEnteringState(int $activePlayerId, array $args): void
{
    // Auto-resolve if no choices exist
    if ($args['_no_notify'] ?? false) {
        return $this->actPass($activePlayerId);
    }
}
```

#### Phase 2: `getArgs()`

Called by the framework to compute the data sent to the client for this state. Returns an associative array that becomes the `args` property on the client's gamestate object.

```php
public function getArgs(int $activePlayerId): array
{
    return [
        'playableCards' => $this->game->cards->getPlayableCards($activePlayerId),
        '_private' => [
            $activePlayerId => [
                'hand' => $this->game->cards->getHand($activePlayerId),
            ],
        ],
    ];
}
```

Magic parameters available: `$activePlayerId`, `$active_player_id`, `$activePlayerNo`, `$active_player_no` (for `ACTIVE_PLAYER`); `$playerId`, `$player_id`, `$playerNo`, `$player_no` (for `PRIVATE`).

#### Phase 3: Client Rendering

The client receives state args and reacts. Two client-side methods are relevant:

- `onEnteringState(stateName, args)` — called when entering any state. Use for general setup.
- `onUpdateActionButtons(stateName, args)` — called to determine which action buttons to show. Prefer this for UI controls.

The client should not assume any state from previous states — args are the complete contract for the current state.

#### Phase 4: Action Execution

Player actions (or automatic actions for `GAME` states) execute the state's purpose. The action method:
1. **Validates** the action against game rules
2. **Executes** domain logic via Managers
3. **Notifies** via the Notifications class
4. **Returns** a transition string

```php
#[PossibleAction]
public function actPlayCard(int $cardId, int $activePlayerId, array $args): string
{
    // Validate
    if (!$this->game->cards->playerHasCard($activePlayerId, $cardId)) {
        throw new \BgaUserException(clienttranslate('You do not have this card'));
    }
    // Execute
    $this->game->cards->playCard($cardId, $activePlayerId);
    // Notify
    $this->game->notifications->cardPlayed($activePlayerId, $cardId);
    // Transition
    return 'cardPlayed';
}
```

#### Phase 5: Transition

The returned string is looked up in the current state's `transitions` map. The framework advances to the target state. If the target state has an `action` method (for `GAME` states) or additional logic in `onEnteringState`, that runs before the request completes.

#### Phase 6: `onLeavingState()`

Called when the state is about to be exited. Rarely needed in practice. May be used to persist ephemeral data or release state-scoped resources.

### 4.3 State Lifecycle for Automatic (GAME) States

For `GAME`-type states, the lifecycle differs:

```
STATE ENTERED
    │
    ▼
onEnteringState() → optional setup
    │
    ▼
action() → automatic execution (no player input)
    │
    ├── validate internal preconditions
    ├── execute logic
    ├── notify
    └── return transition string → next state
    │
    ▼
onLeavingState()
```

The framework calls the `action` method immediately, without waiting for client input. If `_no_notify` is set, no client notification is generated for the state visit.

### 4.4 Private State Lifecycle

For `PRIVATE` states inside a `MULTIPLE_ACTIVE_PLAYER` parent, each player gets their own lifecycle instance:

```
PARENT MULTIACTIVE STATE ENTERED
    │
    ├── initializePrivateStateForAllActivePlayers()
    │       │
    │       ├── Player A: onEnteringState → getArgs → act* → transition
    │       ├── Player B: onEnteringState → getArgs → act* → transition
    │       └── Player C: onEnteringState → getArgs → act* → transition
    │
    ├── (players act independently in their private state)
    │
    └── all private states resolved → parent transition
```

---

## 5. State Responsibilities

Each state class has a clear set of responsibilities. These follow from the state lifecycle and the separation-of-concerns principles in [game-flow-architecture.md §3](./game-flow-architecture.md#3-separation-of-responsibilities).

### 5.1 Responsibility Table

| Responsibility | Method / Property | Description |
|---|---|---|
| **Declare identity** | `id`, `type` (constructor) | Unique numeric ID and framework type |
| **Declare transitions** | `transitions` (constructor) | Map of transition keys → target state IDs |
| **Describe to players** | `description`, `descriptionMyTurn` | Human-readable string shown in UI |
| **Provide state args** | `getArgs()` | Data sent to client for this state |
| **Validate actions** | `act*()` methods | Check preconditions before execution |
| **Execute actions** | `act*()` methods | Trigger domain logic via Managers |
| **Notify clients** | `act*()` methods → Notifications | Broadcast changes after execution |
| **Auto-resolve** | `onEnteringState()` | Skip state if no meaningful choice exists |
| **Update progression** | `updateGameProgression` (constructor) | Whether this state advances the progress bar |

### 5.2 What a State Must NOT Do

- **Contain domain logic.** States are coordinators, not domain experts. Delegate to Managers.
- **Access the database directly.** Use Manager classes.
- **Send notifications directly.** Use the centralized Notifications class.
- **Make decisions about game flow outside its transitions.** A state should not hard-code target state IDs; use named transitions.
- **Persist data.** State objects are request-scoped. All data lives in the database.

### 5.3 Thin State Pattern

A well-structured state class is thin. Most of its methods delegate to Managers:

```php
class PlayerTurn extends GameState
{
    // Constructor — declaration only
    public function __construct(protected Game $game) { ... }

    // Action — thin delegation
    #[PossibleAction]
    public function actPlayCard(int $cardId, int $activePlayerId, array $args): string
    {
        $this->game->checkAction('actPlayCard');
        $this->game->cards->playCard($cardId, $activePlayerId);
        $this->game->notifications->cardPlayed($activePlayerId, $cardId);
        return 'playCard';
    }

    // Args — data assembly only
    public function getArgs(int $activePlayerId): array
    {
        return [
            'hand' => $this->game->cards->getHand($activePlayerId),
        ];
    }
}
```

---

## 6. Designing Good States

### 6.1 Principles

**1. One concern per state.** Each state should represent one coherent game phase. If a state does two unrelated things, split it. The exception is choice states (e.g., `RESOLVE_CHOICE`) where the single concern is presenting a choice.

**2. States should be self-describing.** From the state's `description`, `transitions`, and `getArgs()`, a reader should understand what the state does. If you need comments to explain a state's purpose, the state is poorly named or has too many responsibilities.

**3. Minimise coupling between states.** A state should not need to know why the previous state transitioned to it. The `getArgs()` method should compute args from the current game state, not from transition metadata.

**4. Every state should have a path to state 99.** No dead-end states. Every reachable state must ultimately transition to the terminal game-end state.

**5. Prefer named constants over numeric IDs.** Define state ID constants to avoid magic numbers:

```php
class StateIds
{
    const SETUP = 1;
    const PLAYER_TURN = 2;
    const RESOLVE_CHOICE = 3;
    const GAME_END = 99;
}
```

### 6.2 State Design Checklist

Before implementing a state, confirm:

- [ ] Does this state represent exactly one game phase?
- [ ] Does its `type` match the player-action semantics (ACTIVE_PLAYER / GAME / etc.)?
- [ ] Are all possible transitions declared in the `transitions` map?
- [ ] Are all action methods decorated with `#[PossibleAction]`?
- [ ] Does `getArgs()` return everything the client needs and nothing it doesn't?
- [ ] Is private data properly isolated via `_private`?
- [ ] Does `onEnteringState()` handle the auto-resolve case?
- [ ] Has `updateGameProgression` been set appropriately?
- [ ] Is `zombie()` implemented (for non-GAME states)?
- [ ] Is `giveExtraTime()` called on turn transitions?

### 6.3 Common State Shapes

Most states fall into one of five shapes:

| Shape | Type | Description | Example |
|---|---|---|---|
| **Action** | `ACTIVE_PLAYER` | Player performs one action, then transitions | `PlayerTurn` |
| **Choice** | `ACTIVE_PLAYER` or `PRIVATE` | Player chooses from options | `ResolveChoice` |
| **Gate** | `GAME` | Automatic check and forward | `CheckEndGame` |
| **Process** | `GAME` | Automatic computation (scoring, setup) | `ScoreRound` |
| **Multi** | `MULTIPLE_ACTIVE_PLAYER` | Multiple players act simultaneously | `SimultaneousDiscard` |

---

## 7. State Granularity

One of the most common design questions is: *should this be a new state, a helper method, an action, an Engine node, or a command?* The answer depends on the architectural approach and the nature of the step.

### 7.1 Granularity Decision Matrix

```
                       Requires        Has multiple       Needs player
                      round-trip?      sub-steps?          choice?
                         │                │                   │
                         ▼                ▼                   ▼

NEW STATE ──────────►   YES               —                   —
HELPER METHOD ─────►    NO               NO                   NO
ACTION ─────────────►   YES              NO                   NO
ENGINE NODE ────────►   YES              YES (composed)      YES
COMMAND ────────────►   YES (private)    YES                  YES (deferred)
```

### 7.2 Definitions

| Unit | Definition | Round-trip? | Example |
|---|---|---|---|
| **State** | A framework-managed game phase with its own `id`, `type`, `transitions`, and `getArgs()`. | Yes | `AFTER_MAIN` in Arnak |
| **Helper method** | A private function on a Manager or State class that encapsulates reusable logic. | No | `computePossibleMoves()` |
| **Action** | A `#[PossibleAction]`-decorated method on a State class that a player invokes. | Yes | `actPlayCard()` |
| **Engine node** | A node in a decision tree (Seq/Or/Xor/Parallel/Leaf) that the Engine resolves. | Yes (per node) | `PlaceFarmer` node in Agricola |
| **Command** | A `BaseActionCommand` subclass with `do()`/`undo()` in Earth's command-queue architecture. | Yes (per command, within private phase) | `PlantCardActionCommand` |

### 7.3 When to Make Something a New State

Create a new state when BOTH conditions hold:
1. The step requires a client round-trip (the server needs input to proceed)
2. The step is conceptually a distinct phase of the game

Do NOT create a state when:
- The step can be computed server-side without player input → use a `GAME` state or inline logic
- The step is a sub-choice within an existing choice → add a `#[PossibleAction]` method to the existing state (unless the sub-choice has different UI or permission semantics)

### 7.4 State Count Guidelines by Game Complexity

| Complexity | Typical States | Reference Project |
|---|---|---|
| Simple (fill-and-pass) | 5-15 | — |
| Medium (engine builder, moderate card pool) | 15-40 | Arnak (~30) |
| Complex (heavy card interactions, many phases) | 40-60 | Agricola (~40), Earth (~60) |
| Very Complex (massive card pool, expansions) | 60-100+ | Ark Nova (~83) |

These are guidelines, not rules. A game with many auto-resolve states may have a high state count but low complexity. A game with few states but a complex Engine tree may have high complexity with low state count.

### 7.5 Engine Node vs. State Decision

When using the Engine pattern (Agricola/ArkNova), the distinction between Engine nodes and framework states is:

```
Engine nodes = logical steps in an action (what to do)
Framework states = permission boundaries (who can act)
```

An Engine node becomes a framework state only when it requires a change in active player or presents a different permission model. Otherwise, the Engine resolves multiple nodes within a single framework state.

### 7.6 Command vs. Action Decision (Earth Pattern)

When using the command queue pattern:

```
Command = an atomic, undoable player action within the private phase
Action = the public commitment of all pending commands
```

Every distinct player choice within the private phase becomes a command. The action of committing (ending the turn) is a single action that replays all commands through the public notifier.

---

## 8. Transition Design

Transitions define the edges of the state machine graph. They are declared in each state's constructor as a map of string keys to target state IDs.

### 8.1 Transition Mechanics

```php
parent::__construct($game,
    id: 2,
    type: StateType::ACTIVE_PLAYER,
    transitions: [
        'playCard' => 3,
        'pass' => 4,
        'useAbility' => 5,
    ],
    // ...
);
```

When an action returns `'playCard'`, the framework looks up `transitions['playCard']`, finds `3`, and advances to state 3.

### 8.2 Transition Types

| Type | Meaning | Example Key |
|---|---|---|
| **Player choice** | The player chose an option | `'playCard'`, `'pass'` |
| **Automatic** | The server auto-resolved | `'nextPlayer'`, `'nextRound'` |
| **Conditional** | The game logic determined the path | `'endGame'`, `'continue'` |
| **Error recovery** | The action was impossible | `'restart'`, `'abandon'` |

### 8.3 Transition Design Rules

**Rule 1 — Every exit path must be declared.** If an action can return a string, that string must be in the `transitions` map. Missing transitions cause framework errors.

**Rule 2 — Transitions are one-way.** There is no implicit back-transition. If the state machine must return to a previous state, declare a separate transition in the target state.

**Rule 3 — Transitions should be semantic, not numeric.** The transition key describes *why* we're moving, not *where* we're moving. The `transitions` map resolves the key to a target ID.

```php
// GOOD: semantic key
transitions: [
    'cardPlayed' => 3,
    'turnPassed' => 4,
]

// BAD: numeric or location-based key
transitions: [
    'goToState3' => 3,
    'goToState4' => 4,
]
```

**Rule 4 — Target state IDs should be constants, not literals.**

```php
// GOOD
transitions: ['nextTurn' => StateIds::NEXT_PLAYER]

// BAD
transitions: ['nextTurn' => 17]
```

### 8.4 Transition Diagram

```
                  ┌───────────────────┐
                  │    PLAYER_TURN    │
                  │  (state id: 2)   │
                  └───────┬───┬───────┘
                          │   │
                    ┌─────┘   └─────┐
                    │               │
               'playCard'       'pass'
                    │               │
                    ▼               ▼
          ┌─────────────────┐  ┌──────────────┐
          │  CARD_EFFECT    │  │  NEXT_PLAYER │
          │  (state id: 3)  │  │  (state id: 4)│
          └───────┬─────────┘  └──────┬───────┘
                  │                   │
             'effectDone'        'checkEnd'
                  │                   │
                  ▼                   ▼
          ┌─────────────────┐  ┌──────────────┐
          │  CHECK_END      │  │  GAME_END    │
          │  (state id: 5)  │  │  (state: 99) │
          └───────┬─────────┘  └──────────────┘
                  │
            ┌─────┴──────┐
            │            │
       'continue'    'endGame'
            │            │
            ▼            ▼
    ┌────────────┐  ┌──────────┐
    │NEXT_PLAYER │  │GAME_END  │
    │(state: 4)  │  │(state:99)│
    └────────────┘  └──────────┘
```

### 8.5 Transitions from GAME States

For `GAME`-type states, the transition string is returned from the `action()` method:

```php
class CheckEndGame extends GameState
{
    public function action(int $activePlayerId, array $args): string
    {
        if ($this->game->scoring->isGameEnded()) {
            return 'endGame';
        }
        return 'continue';
    }
}
```

---

## 9. Transition Naming Conventions

Consistent naming makes the state machine readable and maintainable.

### 9.1 Standard Transition Names

| Category | Prefix | Examples |
|---|---|---|
| **Player actions** | (verb describing action) | `playCard`, `pass`, `draw`, `discard`, `confirm`, `undo` |
| **Turn flow** | `next` / `previous` | `nextPlayer`, `previousPlayer`, `nextRound`, `nextPhase` |
| **Round flow** | `start` / `end` | `startRound`, `endRound`, `startGame`, `endGame` |
| **Automatic resolution** | (past tense) | `cardPlayed`, `actionComplete`, `effectResolved` |
| **Conditional branches** | `check` / `handle` | `checkEnd`, `checkScore`, `handleEffect` |
| **Error/edge cases** | `restart`, `abandon`, `skip`, `cancel` | `restartTurn`, `abandonAction`, `skipEffect` |

### 9.2 Naming Patterns

| Pattern | Example | When |
|---|---|---|
| `verbNoun` | `playCard`, `placeFarmer` | Player-initiated action |
| `nextNoun` | `nextPlayer`, `nextRound` | Advancing to next entity |
| `nounDone` | `effectDone`, `scoringDone` | Completion of a sub-phase |
| `checkNoun` | `checkEnd`, `checkScore` | Gate/decision state |
| `startNoun` / `endNoun` | `startRound`, `endGame` | Phase boundaries |
| `restartNoun` | `restartTurn` | Undo / retry |
| `abandonNoun` | `abandonAction` | Impossible action escape |

### 9.3 Anti-Patterns

```
// AVOID: numeric references
transitions: ['goTo3' => 3]

// AVOID: direction without semantics
transitions: ['next' => 4]

// AVOID: implementation details
transitions: ['dbUpdateComplete' => 5]

// PREFER: semantic action names
transitions: ['cardPlayed' => 3, 'turnPassed' => 4]
```

---

## 10. State Arguments

State arguments (`getArgs()`) are the data contract between the server and the client for a given state. They must contain everything the client needs to render the state and nothing the client should not see.

### 10.1 Argument Categories

| Category | Example | Visibility |
|---|---|---|
| **Public state** | `playableCards`, `boardState` | All players |
| **Private state** | `hand`, `privateChoices` | Via `_private` key |
| **Flags** | `_no_notify` | Framework-reserved (never sent to client) |
| **Deltas** | `_delta` | Changed values only (performance optimization) |

### 10.2 The `_private` Key

Private data is sent to specific players using the `_private` key:

```php
public function getArgs(int $activePlayerId): array
{
    return [
        'publicInfo' => $this->game->getPublicInfo(),
        '_private' => [
            $activePlayerId => [
                'hand' => $this->game->cards->getHand($activePlayerId),
                'possibleMoves' => $this->game->computePrivateMoves($activePlayerId),
            ],
        ],
    ];
}
```

The framework strips the `_private` entries for all players except the intended recipient. Spectators receive nothing from `_private`.

### 10.3 The `_no_notify` Flag

Returned in `getArgs()` to indicate the state should auto-resolve without notifying the client:

```php
public function getArgs(int $activePlayerId): array
{
    $playableCards = $this->game->cards->getPlayableCards($activePlayerId);
    return [
        'playableCards' => $playableCards,
        '_no_notify' => count($playableCards) === 0,
    ];
}
```

Used in conjunction with `onEnteringState` for automatic transitions. See §11 for details.

### 10.4 Argument Design Principles

**1. Complete but minimal.** Send everything the client needs, but nothing it can derive. If the client already has card data from setup, send only card IDs and locations — not full card definitions.

**2. Delta when possible, full state when not.** For performance, compute only what changed. Fall back to full state when the delta logic becomes too complex.

**3. Private state goes in `_private`.** Never include private data in public args. The framework provides the `_private` mechanism; use it.

**4. Compute, don't store.** Args are computed on demand from the database. Do not cache args between requests — the state may have changed.

---

## 11. Automatic States

Automatic states (`GAME`-type) execute server-side logic without player input. They are used for setup, scoring, validation gates, and any phase that requires computation but not interaction.

### 11.1 When to Use Automatic States

```
Player needs to make a decision?
  ├── YES → ACTIVE_PLAYER or MULTIPLE_ACTIVE_PLAYER
  │
  └── NO  → Does this involve significant computation or DB writes?
            ├── YES → GAME state (automatic) → Notify → Transition
            └── NO  → Inline logic in the calling state's action
```

Use a `GAME` state when:
- Scoring a round or the final game
- Setting up a new round (deal cards, reset tokens)
- Checking end-game conditions
- Processing mandatory effects that require no choices
- Resolving engine tree nodes automatically

Do NOT use a `GAME` state when:
- The logic is a single DB query or simple computation — inline it
- The logic can be folded into the preceding state's `onEnteringState`

### 11.2 Automatic State Pattern

```php
class ScoreRound extends GameState
{
    public function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: 10,
            type: StateType::GAME,
            description: '',   // No player-facing text
            transitions: [
                'nextRound' => 4,
                'endGame' => 99,
            ],
            updateGameProgression: true,
        );
    }

    public function action(int $activePlayerId, array $args): string
    {
        // Execute scoring logic
        foreach ($this->game->players->getAll() as $player) {
            $points = $this->game->scoring->computeRoundScore($player);
            $this->game->players->addScore($player->getId(), $points);
            $this->game->notifications->roundScore($player, $points);
        }

        // Decide next state
        if ($this->game->scoring->isGameEnded()) {
            return 'endGame';
        }
        return 'nextRound';
    }
}
```

### 11.3 Auto-Resolve with `_no_notify`

For `ACTIVE_PLAYER` or `PRIVATE` states that may resolve automatically, use the `_no_notify` pattern:

```
State entered
    │
    ▼
onEnteringState()
    │
    ├─── _no_notify is false → wait for player input (normal flow)
    │
    └─── _no_notify is true  → call action method immediately → auto-transition
```

```php
public function onEnteringState(int $activePlayerId, array $args): void
{
    if ($args['_no_notify'] ?? false) {
        $this->actPass($activePlayerId);
    }
}
```

The `_no_notify` flag suppresses the state-change notification to the client, preventing unnecessary UI updates during the brief moment the state exists.

### 11.4 Chaining Automatic States

Multiple `GAME` states can chain within a single request when each has `_no_notify: true`:

```
State A (GAME, _no_notify: true)
  → action() returns 'next'
  → State B (GAME, _no_notify: true)
    → action() returns 'next'
    → State C (ACTIVE_PLAYER)
      → onEnteringState() → wait for player
```

The framework processes the entire chain within one request. The client sees only the final state (C) as its next state notification.

---

## 12. Private States

Private states (`StateType::PRIVATE`) give each player an independent state machine running inside a parent `MULTIPLE_ACTIVE_PLAYER` state. They are the BGA framework's built-in mechanism for per-player decision flows during simultaneous phases.

### 12.1 Architecture

```
┌───────────────────────────────────────────────────────┐
│  MULTIPLE_ACTIVE_PLAYER STATE (parent)                 │
│                                                       │
│  ┌──────────────────┐   ┌──────────────────┐          │
│  │ Player A         │   │ Player B         │          │
│  │ Private State 1  │   │ Private State 1  │          │
│  │ ──→ action       │   │ ──→ action       │          │
│  │ ──→ Private St 2 │   │ ──→ Private St 2 │          │
│  │ ──→ done         │   │ ──→ done         │          │
│  └────────┬─────────┘   └────────┬─────────┘          │
│           │                      │                    │
│           └──────────┬───────────┘                    │
│                      ▼                                │
│           All players done → parent transition        │
└───────────────────────────────────────────────────────┘
```

### 12.2 Setting Up Private States

Private states are declared in the parent `MULTIPLE_ACTIVE_PLAYER` state's constructor:

```php
parent::__construct($game,
    id: 20,
    type: StateType::MULTIPLE_ACTIVE_PLAYER,
    initialPrivate: DiscardPhase::class,  // Initial private state class
    transitions: [
        'allDone' => 21,
    ],
    // ...
);
```

When the parent state is entered, the framework creates a private state instance for each active player.

### 12.3 Private State Transitions

Private states transition independently using `nextPrivateState()`:

```php
class DiscardPhase extends GameState
{
    public function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: 201,
            type: StateType::PRIVATE,
            transitions: [
                'discardDone' => 202,
                'skip' => 202,
            ],
        );
    }

    #[PossibleAction]
    public function actDiscard(int $cardId, int $playerId, array $args): string
    {
        $this->game->cards->discard($cardId, $playerId);
        $this->game->notifications->cardDiscarded($playerId, $cardId);
        return 'discardDone';
    }
}
```

### 12.4 When the Parent Resolves

The parent `MULTIPLE_ACTIVE_PLAYER` state resolves when ALL players' private state machines have reached a terminal private state. The parent then transitions according to its own `transitions` map.

To check whether all private states have resolved:

```php
if ($this->game->gamestate->isAllPrivateStatesDone()) {
    // All players finished → parent can transition
}
```

### 12.5 Private State Args

Private state `getArgs()` receives magic parameters for the specific player:

```php
public function getArgs(int $playerId, int $playerNo): array
{
    return [
        'hand' => $this->game->cards->getHand($playerId),
        'mustDiscard' => count($this->game->cards->getHand($playerId)) > 7,
    ];
}
```

### 12.6 Earth-Style Private State Machine

Earth extends the framework's private state mechanism with a custom `PrivateState` layer and command queue. This adds:
- **Per-player undo** within the private phase
- **Action command queue** (commands are applied privately, committed publicly)
- **Cross-player invalidation** (committed actions may invalidate pending private commands)

For the full Earth pattern, see [game-flow-architecture.md §14.2](./game-flow-architecture.md#142-solution-a-private-state--command-queue-earth) and the reference project analysis.

### 12.7 When to Use Private States

| Scenario | Use? | Alternative |
|---|---|---|
| Simultaneous discard phase | Yes — framework private states | Separate MULTIPLE_ACTIVE_PLAYER per round |
| Simultaneous multi-action turns | Yes — command queue + private states | Not viable without private states |
| One player makes a private choice during another's turn | No — use `_private` in args | Private states are for simultaneous play only |
| All players vote simultaneously | Yes — simple MULTIPLE_ACTIVE_PLAYER | Private states only if each player has multiple steps |

---

## 13. Multiple-Active-Player States

`MULTIPLE_ACTIVE_PLAYER` states permit a *set* of players to act simultaneously. They are the foundation for any phase where multiple players take actions without waiting for turns.

### 13.1 Configuration

```php
parent::__construct($game,
    id: 30,
    type: StateType::MULTIPLE_ACTIVE_PLAYER,
    description: clienttranslate('All players must discard down to 7 cards'),
    descriptionMyTurn: clienttranslate('${you} must discard down to 7 cards'),
    transitions: [
        'allDone' => 31,
    ],
    initialPrivate: DiscardPhase::class,
    // ...
);
```

### 13.2 Activating Players

Players are made active in a multiactive state using framework methods:

```php
// Activate all players
$this->gamestate->setAllPlayersMultiactive();

// Activate specific subset
$this->gamestate->setPlayersMultiactive([$playerId1, $playerId2]);

// Exclude specific players
$this->gamestate->setAllPlayersMultiactive();
$this->gamestate->setPlayerNonMultiactive($playerId);
```

### 13.3 Resolving a Multiactive State

A multiactive state resolves when all active players have completed their required actions:

```php
// Called when a player finishes
$this->gamestate->setPlayerMultiactive($playerId);  // Deactivate this player

// Check if all are done
if ($this->gamestate->isAllPrivateStatesDone()) {
    // All finished → transition parent
}
```

### 13.4 Without Private States

If the multiactive phase requires only a single action per player (no sub-steps), private states may be unnecessary:

```php
class SimultaneousVote extends GameState
{
    public function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: 40,
            type: StateType::MULTIPLE_ACTIVE_PLAYER,
            description: clienttranslate('All players vote on the proposal'),
            descriptionMyTurn: clienttranslate('${you} must vote on the proposal'),
            transitions: [
                'voteCast' => '_INVALID_',  // No transition — stays in state until all vote
                'allVoted' => 41,
            ],
        );
    }

    #[PossibleAction]
    public function actVote(string $vote, int $currentPlayerId, array $args): string
    {
        $this->game->votes->cast($currentPlayerId, $vote);
        $this->game->notifications->voteCast($currentPlayerId, $vote);

        // Deactivate this player
        $this->game->gamestate->setPlayerNonMultiactive($currentPlayerId);

        // Check if all done
        $active = $this->game->gamestate->getActivePlayerList();
        if (empty($active)) {
            return 'allVoted';
        }
        return '';  // Return empty to stay in current state
    }
}
```

### 13.5 Multiactive State Diagram

```
                      ┌──────────────────────────────────┐
                      │   MULTIACTIVE STATE               │
                      │   (state id: 40)                  │
                      └──┬───┬───┬───┬───┬───┬───┬───┬───┘
                         │   │   │   │   │   │   │   │
                      ┌──┘   │   │   │   │   │   │   └──┐
                      ▼      ▼   ▼   ▼   ▼   ▼   ▼      ▼
                   P1 vote  P2 vote  P3 vote  P4 vote
                      │      │   │   │   │   │   │      │
                      │      │   │   │   │   │   │      │
                      └──────┴───┴───┴───┴───┴───┴──────┘
                                      │
                              all players voted
                                      │
                                      ▼
                            ┌──────────────────┐
                            │  NEXT STATE      │
                            │  (state id: 41)  │
                            └──────────────────┘
```

### 13.6 Zombie Handling in Multiactive States

When a player disconnects during a multiactive phase, the `zombie()` method must handle their pending actions:

```php
public function zombie(int $playerId, array $args): string
{
    // Auto-discard excess cards for zombie player
    $hand = $this->cards->getHand($playerId);
    while (count($hand) > 7) {
        $card = array_pop($hand);
        $this->cards->discard($card['id'], $playerId);
    }
    // Deactivate the zombie player
    $this->gamestate->setPlayerNonMultiactive($playerId);

    // Check if all remaining active players are done
    $active = $this->gamestate->getActivePlayerList();
    if (empty($active)) {
        return 'allDone';
    }
    return '';
}
```

---

## 14. State-Machine Scaling

The four reference projects demonstrate four different approaches to state-machine scaling. Each approach trades simplicity against expressiveness.

### 14.1 Comparison Table

| Aspect | Arnak | Agricola | Ark Nova | Earth |
|---|---|---|---|---|
| **State count** | ~30 | ~40 | ~83 | ~60 |
| **Flow engine** | None (manual states) | Engine (Seq/Or/Xor/Parallel/Leaf) | Engine + FlowConvertor | Command queue |
| **Multi-step actions** | Dedicated states per step | Engine tree resolves stepwise | Engine tree + dynamic flow from cards | Command queue with do/undo |
| **State IDs** | Numeric (fragile) | Constants via Globals | Constants via Globals | Constants via class refs |
| **State file pattern** | `states.inc.php` (array) | `states.inc.php` (array) | `states.inc.php` (array) | State classes in `States/` |
| **Card injection** | Switch in card_effects.php | Per-card class + before/computeReplace hooks | Per-card class + FlowConvertor | Per-card Ability definitions |
| **Simultaneous turn** | No | No | Break phase only | Full (private state + command queue) |
| **Auto-resolution** | Inline checks | Engine auto-resolves single-child OrNodes | Engine auto-resolves | Command re-evaluate |
| **Zombie handling** | Framework defaults | Engine clearZombieNodes | Engine clearZombieNodes | ActionCommandMgr::zombieRemoveAll |

### 14.2 Arnak: Manual State Machine (Simple Scale)

**Approach:** Every game phase and every action sub-step is a dedicated state. Transitions are hard-coded in `states.inc.php`.

```
SELECT_ACTION → [PLAY_CARD | RESEARCH | DIG | BUY_ITEM | ...] → AFTER_MAIN → NEXT_PLAYER
```

**Strengths:** Simple, transparent, easy to debug, no abstraction layer.

**Limitations:** State count grows linearly with action types. Card effects require branching logic in a single `card_effects.php`. Adding a new action type requires adding a new state and wiring all transitions.

**When to use:** Games with <20 action types, limited card interactions, and no simultaneous play.

### 14.3 Agricola: Engine Tree (Complex Scale)

**Approach:** A custom Engine class replaces most manual state transitions with a decision tree. Nodes are resolved stepwise; the Engine decides the next framework state dynamically.

```
Engine::setup(tree) → Engine::proceed()
  → OrNode (player chooses) → ST_RESOLVE_CHOICE
  → LeafNode (atomic action) → ST_GAIN / ST_PAY / ST_FENCING
  → SeqNode (multi-step) → resolve children in order
  → tree done → ST_CONFIRM_TURN
```

**Strengths:** Scales to 500+ cards with complex interactions. Cards inject nodes into the tree via `before<Action>()` / `computeReplace<Action>()` hooks. State count stays manageable (~40) despite enormous game complexity.

**Limitations:** High implementation complexity. The Engine is a custom abstraction that must be maintained. Debugging requires understanding both the state machine and the Engine tree.

**When to use:** Games with complex card-driven actions, many card types, and deep action sequences.

### 14.4 Ark Nova: Engine + FlowConvertor (Very Complex Scale)

**Approach:** Same Engine as Agricola, extended with `FlowConvertor` that generates dynamic flow trees from arbitrary bonuses. Card/sponsor effects produce runtime flow trees.

```
Same as Agricola + FlowConvertor::convert(bonuses) → creates flow tree on the fly
```

**Strengths:** Highest expressiveness. Dynamic flow generation enables cards with arbitrary effects without new states. ~83 states but effectively infinite action combinations.

**Limitations:** Highest complexity. `FlowConvertor` adds another abstraction layer. Notifications class is very large (1672+ lines). Requires deep understanding of the Engine architecture.

**When to use:** Games with massive card pools, dynamic action strength systems, and planned expansions.

### 14.5 Earth: Command Queue (Simultaneous Scale)

**Approach:** A command queue decouples action execution from commitment. Each player's actions are applied to a private state and queued in the `action_command` table. Commitment replays commands through a public notifier.

```
PRIVATE PHASE:  queue commands → do() on private notifier → confirm
PUBLIC PHASE:   replay commands through public notifier → reevaluate() → commit
```

**Strengths:** The only approach that supports true simultaneous play. Per-action undo without DB rollback. Cross-player invalidation via `reevaluate()`.

**Limitations:** Highest infrastructure complexity. Requires private state machine, command queue DB table, and reevaluation logic. Overkill for turn-based games.

**When to use:** Only when the game has simultaneous turns (all players act at the same time).

### 14.6 Scaling Decision Guide

```
Simultaneous turns?
├── YES → Earth-style command queue (private states + action commands)
│
└── NO  → Card complexity?
          ├── LOW (< 50 cards, simple effects)
          │   └── Arnak-style manual states
          │
          ├── MEDIUM (50-200 cards, triggered effects)
          │   └── Manual states + data-driven card effects (Arnak card_effects.php)
          │
          └── HIGH (200+ cards, flow-modifying effects)
              └── Engine tree (Agricola/ArkNova style)
                  ├── Static flow → Agricola Engine
                  └── Dynamic flow → ArkNova Engine + FlowConvertor
```

---

## 15. Common State-Machine Anti-Patterns

### 15.1 The God State

**Pattern:** A single state handles multiple unrelated phases via conditional logic in `getArgs()` and `onEnteringState()`.

```
// ANTI-PATTERN
public function getArgs(int $activePlayerId): array
{
    if ($this->game->getPhase() === 'SELECTION') {
        // return selection args
    } elseif ($this->game->getPhase() === 'RESOLUTION') {
        // return resolution args
    } elseif (...) { ... }
}
```

**Problem:** Violates single responsibility. The state becomes a maintenance burden as phases are added.

**Solution:** Split into one state per phase. Use the state machine to encode phase transitions.

### 15.2 The Death Star State

**Pattern:** A state with dozens of possible actions, most of which are unused in any given visit.

```
// ANTI-PATTERN
possibleactions: ['actA', 'actB', 'actC', 'actD', 'actE', ..., 'actZ']
```

**Problem:** Unclear which actions are actually available. The `getArgs()` method must compute availability client-side.

**Solution:** Compute the set of possible actions dynamically in `getArgs()` and communicate it to the client. Better yet, split into multiple states with smaller action sets.

### 15.3 State ID Entropy

**Pattern:** Numeric state IDs scattered as magic numbers throughout the codebase.

```
// ANTI-PATTERN
if ($stateId === 7) { ... }
return $this->gamestate->nextState('goTo42');
```

**Problem:** Brittle. Renumbering a state breaks all references. Impossible to search for "all transitions to state X."

**Solution:** Define state ID constants:

```php
class StateIds
{
    const SETUP = 1;
    const PLAYER_TURN = 2;
    const RESOLVE_CHOICE = 3;
    const DISCARD_PHASE = 4;
    const GAME_END = 99;
}
```

### 15.4 The Ping-Pong Pattern

**Pattern:** Two states that repeatedly transition to each other:

```
State A → State B → State A → State B → ...
```

**Problem:** May indicate a state should be a sub-phase within a single state, or that both should be merged.

**Solution:** Merge into a single state with internal phase tracking, or use the Engine pattern to resolve the back-and-forth within a single framework state.

### 15.5 Notify-After-Transition

**Pattern:** Sending notifications after the state transition:

```php
// ANTI-PATTERN
$this->gamestate->nextState('nextTurn');
$this->notifyAllPlayers('lateNotif', '', [...]);  // May not be delivered
```

**Problem:** Notifications sent after a state transition are not guaranteed to be delivered.

**Solution:** Always send notifications before the transition. See [game-flow-architecture.md §16.1](./game-flow-architecture.md#161-sending-notifications-after-nextstate).

### 15.6 The State-That-Never-Ends

**Pattern:** A state with no path to another state (dead end).

```php
transitions: ['continue' => StateIds::SAME_STATE]  // Stays forever
```

**Problem:** The game loops indefinitely. Players cannot progress.

**Solution:** Every state must have at least one path to a different state (ultimately to state 99).

### 15.7 Missing Zombie Handler

**Pattern:** A non-GAME state without zombie handling.

**Problem:** If a player disconnects during this state, the game freezes.

**Solution:** Implement `zombie()` for every `ACTIVE_PLAYER`, `MULTIPLE_ACTIVE_PLAYER`, and `PRIVATE` state. For `GAME` states, zombie handling is not required (the state has no player input).

### 15.8 Manual Permission Checks

**Pattern:** Checking player identity manually instead of relying on the state machine.

```php
// ANTI-PATTERN
if ($this->getCurrentPlayerId() !== $expectedPlayer) {
    throw new \BgaUserException('Not your turn');
}
```

**Problem:** The framework already enforces active-player checks. Duplicating them adds noise and can mask state machine bugs.

**Solution:** Trust `checkAction()` and the state machine's active-player enforcement. Use state type to define permissions structurally.

### 15.9 Action Method in the Wrong State

**Pattern:** An `act*()` method that does not correspond to any transition in its state.

```php
// ANTI-PATTERN
public function actPass(int $activePlayerId, array $args): string
{
    return 'pass';  // 'pass' is not in this state's transitions
}
```

**Problem:** The transition string is invalid. The framework will error.

**Solution:** Every returned transition string must be declared in the state's `transitions` map.

### 15.10 The Kitchen-Sink `getArgs()`

**Pattern:** `getArgs()` returns every piece of game state, regardless of whether it's needed.

```php
// ANTI-PATTERN
public function getArgs(): array
{
    return [
        'players' => $this->game->getAllPlayers(),
        'cards' => $this->game->getAllCards(),
        'board' => $this->game->getFullBoardState(),
        'scores' => $this->game->getScores(),
        'history' => $this->game->getFullGameHistory(),
        'options' => $this->game->getAllOptions(),
        // ... everything
    ];
}
```

**Problem:** Unnecessary data increases payload size, slows down the client, and may leak information.

**Solution:** Return only what the client needs for this state. For data the client already has (static card definitions), send only IDs and locations.

---

## 16. Debugging State Machines

### 16.1 Common Debugging Techniques

**1. State transition logging.** Track every state entry and exit:

```php
// In Game.php or a debug trait
public function debugDumpState(): void
{
    $state = $this->gamestate->state();
    $this->dump('Current state', [
        'id' => $state['id'],
        'name' => $state['name'],
        'type' => $state['type'],
        'active_player' => $this->getActivePlayerId(),
        'possible_actions' => $state['possibleactions'] ?? [],
    ]);
}
```

**2. State graph visualization.** Render the state machine as a directed graph for visual inspection:

```
// Generate a DOT graph of all states and transitions for documentation
// Can be rendered with Graphviz
digraph StateMachine {
    node [shape=box];
    1 -> 2 [label="startGame"];
    2 -> 3 [label="playCard"];
    2 -> 4 [label="pass"];
    3 -> 4 [label="effectDone"];
    4 -> 5 [label="nextPlayer"];
    5 -> 2 [label="continue"];
    5 -> 99 [label="endGame"];
}
```

**3. Debug actions.** Register debug-only actions (behind a Studio check) to inspect state:

```php
// Only available in Studio environment
public function actDebugStateInfo(): void
{
    $this->checkAction('actDebugStateInfo');
    // Dump current state machine info to PHP error log
    error_log(json_encode($this->gamestate->state()));
}
```

**4. Seed loading (Agricola pattern).** Support deterministic game setup with seed values for reproducible debugging:

```
A seed value (e.g., "test-discard-phase") determines deck order,
card draws, and random outcomes. Same seed = identical game state.
```

### 16.2 Common Bugs and Their Symptoms

| Symptom | Likely Cause | Check |
|---|---|---|
| `checkAction is not possible...` | Action not in `#[PossibleAction]` or `possibleactions` array | Verify the attribute is present and the method name matches the action name |
| State loops infinitely | No path to state 99, or self-looping transition | Trace all paths from the state to 99 |
| Client shows stale UI | `getArgs()` not refreshing from DB, or client not re-rendering on state change | Verify args are computed from DB, not cached |
| Private data visible to all | `_private` key missing or misused | Check `getArgs()` for public exposure of private data |
| Zombie causes game hang | Missing `zombie()` implementation for a non-GAME state | Implement zombie for every state |
| State transition not working | Transition key not in `transitions` map, or misspelled | Compare returned string to map keys |
| Game progress bar stuck | `updateGameProgression` not set on states | Set to `true` on meaningful progress states |
| Notifications missing after undo | Notifications sent before state transition in undo flow | Ensure notify → transition ordering |

### 16.3 Debugging the Engine Pattern (Agricola/ArkNova)

When using the Engine, the resolution tree adds complexity. Two key debugging techniques:

**1. Dump the current tree.** Serialise the Engine tree state and inspect node positions:

```php
// Debug: dump the Engine tree
$tree = Globals::getEngine();
error_log('Engine tree: ' . json_encode($tree->toDebugArray()));
```

**2. Log node resolution.** Each node's resolution decision should be logged:

```php
// In Engine::proceed()
error_log(sprintf(
    'Engine: %s node %s → %s',
    get_class($node),
    $node->getId(),
    $decision
));
```

### 16.4 Debugging the Command Queue (Earth)

For the command-queue pattern, inspect the pending commands:

```php
// Debug: dump all pending commands for a player
$commands = ActionCommandMgr::getPendingCommands($playerId);
error_log('Pending commands: ' . json_encode($commands->toArray()));
```

---

## 17. Refactoring Large State Machines

As games grow (through expansions, feature additions, or bug fixes), state machines tend to accumulate cruft. Here is a systematic approach to refactoring.

### 17.1 Signs You Need to Refactor

- States with >10 outgoing transitions
- A state with >5 `act*` methods
- States that duplicate logic from other states
- State IDs that have grown non-sequential through insertions
- Transitions that skip intermediate states unpredictably
- A `zombie()` method with a long if-else chain
- `getArgs()` that returns vastly different data depending on internal flags

### 17.2 Refactoring Process

**Step 1 — Map the current state machine.** Render the full graph of all states and transitions. Identify:
- Dead states (no incoming transitions — unreachable)
- Dead-end states (no path to 99)
- Ping-pong pairs
- Star states (one state connects to many others — likely the God State)

**Step 2 — Identify concerns.** Group states by the game phase they represent. Phases should form contiguous, acyclic sub-graphs.

**Step 3 — Consolidate or split.** Apply two patterns:

*Split large states:* A state with many concerns becomes one state per concern.

*Merge trivial states:* A `GAME` state that does one DB update and immediately transitions can be inlined into the preceding state's action.

**Step 4 — Normalize state IDs.** Renumber states to form logical groups:

```
1-9: Setup
10-19: Core turn cycle
20-29: Sub-phases and effects
30-39: Multi-active phases
40-49: End-game and scoring
50-89: Expansion slots
90-99: Reserved (setup/teardown)
```

**Step 5 — Introduce constants.** Replace all numeric state ID references with named constants.

**Step 6 — Extract the Engine (if warranted).** If the game has grown from 30 to 60 states and card interactions are complex, consider extracting an Engine pattern. This is a major refactoring, not a minor one. See §9.3 of [game-flow-architecture.md](./game-flow-architecture.md#93-approach-b-engine-tree-agricolaarknova).

**Step 7 — Add debuggability.** Add debug actions, state dumps, and graph export to catch regressions.

### 17.3 State Grouping Strategy

Structure the state machine into numbered groups with reserved ranges:

```
┌──────────────────────────────────────────────────┐
│              STATE ID GROUPS                      │
│                                                  │
│  01-09  Setup / Initialization                   │
│         1  = gameSetup (reserved, DO NOT CHANGE)  │
│         2  = initialDeal                         │
│         3  = playerOrder                         │
│                                                  │
│  10-19  Main Turn Cycle                          │
│         10 = startTurn                           │
│         11 = mainAction                          │
│         12 = freeActions                         │
│         13 = endTurn                             │
│                                                  │
│  20-29  Sub-phases / Effects                     │
│         20 = resolveChoice                       │
│         21 = cardEffect                          │
│         22 = bonusGain                           │
│                                                  │
│  30-39  Multi-active Phases                      │
│         30 = simultaneousDiscard                 │
│         31 = simultaneousChoice                  │
│                                                  │
│  40-49  End-Game                                 │
│         40 = finalScoring                        │
│         41 = endGameBreakTie                     │
│                                                  │
│  50-89  Expansion Slots (reserved for future)    │
│                                                  │
│  90-98  Internal / Debug                         │
│         90 = checkCombos (debug only)            │
│                                                  │
│  99     Game End (reserved, DO NOT CHANGE)       │
└──────────────────────────────────────────────────┘
```

### 17.4 Migrating from Legacy to State Classes

If migrating from a `states.inc.php` array-based state machine to the modern State classes approach:

**Phase 1 — Create a `States/` directory.** One class per state from `states.inc.php`.

**Phase 2 — Extract state-level logic.** Move action methods from `Game.php` (or `action.php`) into the corresponding State class. Decorate with `#[PossibleAction]`.

**Phase 3 — Verify transitions.** Ensure every `states.inc.php` transition has an equivalent declaration in a State class.

**Phase 4 — Remove `states.inc.php`.** Once all states are migrated, delete the file. The framework automatically discovers State classes in the `States/` directory.

### 17.5 Adding Expansion States

When adding expansions, follow the reserved-ID approach:

```php
// In the expansion's state class
class ExpansionCardEffect extends GameState
{
    public function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: 50,  // First expansion slot
            type: StateType::ACTIVE_PLAYER,
            // ...
        );
    }
}
```

Expansion states should:
- Use IDs in the reserved expansion range (50-89)
- Never modify existing states' IDs or transitions
- Add new transitions to existing states (if the expansion adds new paths)
- Gate new states behind expansion-enabled checks

---

## 18. Recommended Canonical Architecture

### 18.1 Architecture Selection Summary

| Game Characteristic | Recommended Architecture | Reference |
|---|---|---|
| Turn-based, simple actions | Manual states | Arnak |
| Turn-based, complex card interactions | Engine tree | Agricola |
| Turn-based, dynamic card-generated flows | Engine + FlowConvertor | Ark Nova |
| Simultaneous turns | Command queue + private states | Earth |

### 18.2 Canonical State Machine Structure

For a new project with standard turn-based play and moderate complexity:

```
┌───────────────────────────────────────────────────────────────┐
│                   CANONICAL STATE MACHINE                      │
│                                                               │
│  1 ──→ Setup (GAME)                                           │
│   │     └──→ setupNewGame() → initialDeal → startGame         │
│   │                                                           │
│   ▼                                                           │
│  10 ──→ StartTurn (GAME)                                      │
│   │     └──→ setActivePlayer → updateGameProgression          │
│   │                                                           │
│   ▼                                                           │
│  11 ──→ PlayerTurn (ACTIVE_PLAYER)                            │
│   │     ├──→ actPlayCard()  → cardPlayed                     │
│   │     ├──→ actUseAbility() → abilityUsed                   │
│   │     └──→ actPass()      → turnPassed                      │
│   │                                                           │
│   ▼                                                           │
│  12 ──→ ResolveEffect (GAME or ACTIVE_PLAYER)                 │
│   │     └──→ effectResolved                                   │
│   │                                                           │
│   ▼                                                           │
│  13 ──→ EndTurn (GAME)                                        │
│   │     ├──→ nextPlayer (if game continues)                   │
│   │     └──→ endGame (if terminal)                            │
│   │                                                           │
│   ▼                                                           │
│  99 ──→ GameEnd (GAME)                                        │
│         └──→ finalScoring → game results                      │
└───────────────────────────────────────────────────────────────┘
```

### 18.3 Canonical State Class Template

```php
<?php
declare(strict_types=1);

namespace Bga\Games\YourGame\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\YourGame\Core\Notifications;

class PlayerTurn extends GameState
{
    public function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: StateIds::PLAYER_TURN,
            type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('${actplayer} must play a card or pass'),
            descriptionMyTurn: clienttranslate('${you} must play a card or pass'),
            transitions: [
                'cardPlayed' => StateIds::RESOLVE_EFFECT,
                'abilityUsed' => StateIds::RESOLVE_EFFECT,
                'turnPassed' => StateIds::END_TURN,
            ],
            updateGameProgression: true,
        );
    }

    public function onEnteringState(int $activePlayerId, array $args): void
    {
        $this->game->giveExtraTime($activePlayerId);

        if ($args['_no_notify'] ?? false) {
            $this->actPass($activePlayerId);
        }
    }

    public function getArgs(int $activePlayerId): array
    {
        $playableCards = $this->game->cards->getPlayableCards($activePlayerId);
        return [
            'playableCardIds' => array_keys($playableCards),
            '_private' => [
                $activePlayerId => [
                    'hand' => $this->game->cards->getHand($activePlayerId),
                ],
            ],
            '_no_notify' => empty($playableCards) && !$this->game->abilities->hasUsableAbilities($activePlayerId),
        ];
    }

    #[PossibleAction]
    public function actPlayCard(int $cardId, int $activePlayerId, array $args): string
    {
        $this->game->checkAction('actPlayCard');

        if (!$this->game->cards->canPlayCard($cardId, $activePlayerId)) {
            throw new \BgaUserException(clienttranslate('You cannot play this card'));
        }

        $this->game->cards->playCard($cardId, $activePlayerId);
        Notifications::cardPlayed($activePlayerId, $cardId);

        return 'cardPlayed';
    }

    #[PossibleAction]
    public function actPass(int $activePlayerId, array $args): string
    {
        $this->game->checkAction('actPass');
        Notifications::turnPassed($activePlayerId);
        return 'turnPassed';
    }
}
```

### 18.4 Directory Layout

```
modules/php/
├── Game.php                        # Thin coordinator
├── States/
│   ├── StateIds.php                # State ID constants
│   ├── Setup.php                   # State 1 (reserved)
│   ├── StartTurn.php               # GAME state: begin turn
│   ├── PlayerTurn.php              # ACTIVE_PLAYER: main action
│   ├── ResolveEffect.php           # ACTIVE_PLAYER or GAME: effect resolution
│   ├── EndTurn.php                 # GAME state: end turn logic
│   └── GameEnd.php                 # State 99 (reserved)
├── Core/
│   ├── Globals.php                 # Typed global variables
│   ├── Notifications.php           # Centralized notification factory
│   ├── Preferences.php             # User preference wrappers
│   └── Stats.php                   # Statistics definitions
├── Managers/
│   ├── Players.php                 # Player data and lifecycle
│   ├── Cards.php                   # Card deck, hand, operations
│   ├── Board.php                   # Board state management
│   └── Scoring.php                 # Score computation
├── Models/
│   ├── Player.php                  # Player data object
│   └── Card.php                    # Card data object
└── Helpers/
    ├── DB.php                      # Query abstraction
    └── Utils.php                   # Pure functions
```

### 18.5 Production Readiness Checklist

A state machine is production-ready when:

- [ ] Every state has a unique numeric ID within reserved ranges
- [ ] Every non-GAME state implements `zombie()`
- [ ] Every turn transition calls `giveExtraTime()`
- [ ] Every state has at least one path to state 99
- [ ] All transition keys are semantic (not numeric)
- [ ] All state IDs are referenced via constants, not literals
- [ ] `_no_notify` is used for auto-resolving states
- [ ] Private data uses `_private` key in `getArgs()`
- [ ] `updateGameProgression` is set appropriately on all states
- [ ] No dead states or unreachable transitions
- [ ] Debug actions exist for state inspection
- [ ] Zombie handler covers every non-GAME state
- [ ] Expansions can be added without modifying existing state IDs

---

## Appendix A: Quick Reference

### State Types

| Type | Player Activation | Zombie Required | Use Case |
|---|---|---|---|
| `GAME` | None | No | Auto-processing, scoring, transitions |
| `ACTIVE_PLAYER` | Exactly one | Yes | Normal turns, choices |
| `MULTIPLE_ACTIVE_PLAYER` | Subset of players | Yes | Simultaneous actions |
| `PRIVATE` | One per instance (inside MAP) | Yes | Per-player decision flows |

### Reserved State IDs

| ID | Purpose | Modifiable? |
|---|---|---|
| 1 | First game state | No |
| 99 | Last game state (game end) | No |

### Lifecycle Methods

| Method | Called When | Purpose |
|---|---|---|
| `onEnteringState()` | After state entered, before args | Initialization, auto-resolve check |
| `getArgs()` | After onEnteringState | Compute client-facing state args |
| `act*()` | On player action | Validate, execute, notify, return transition |
| `action()` | Immediately on GAME states | Automatic execution |
| `onLeavingState()` | Before state exit | Cleanup |

### Magic Parameters

| Method | Available Parameters |
|---|---|
| `getArgs()` | `$activePlayerId`, `$active_player_id`, `$activePlayerNo`, `$active_player_no`, `$playerId`, `$player_id`, `$playerNo`, `$player_no` |
| `act*()` | `$args`, `$activePlayerId`, `$active_player_id`, `$currentPlayerId`, `$current_player_id` |

### State File Naming Convention

```
States/
├── StateIds.php         # Constants file (always)
├── Setup.php           # ID 1 (reserved)
├── PlayerTurn.php      # ID N (named by phase)
├── ResolveChoice.php   # ID N (named by purpose)
└── GameEnd.php         # ID 99 (reserved)
```
