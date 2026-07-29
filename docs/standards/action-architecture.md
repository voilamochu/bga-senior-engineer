# BGA Action Architecture — Engineering Standard

**Document purpose:** Define the canonical approach for designing, implementing, validating, executing, testing, and maintaining every server action in a BGA game. This standard is derived from the framework API and from architecture analysis of the four reference projects: Arnak, Agricola, Ark Nova, and Earth.

**Applicability:** All new BGA game implementations. Existing projects should use this document as a reference when refactoring action handling.

**Cross-references:**
- [game-flow-architecture.md](./game-flow-architecture.md) — execution pipeline, transaction model, separation of responsibilities
- [state-machine-architecture.md](./state-machine-architecture.md) — state lifecycle, transitions, state types, action routing
- [notification-patterns.md](./notification-patterns.md) — notification payload design, sequencing, undo patterns
- [bga-developer-handbook.md](../foundation/bga-developer-handbook.md) — framework API reference, state class reference
- [reference-project-analysis.md](../foundation/reference-project-analysis.md) — project-specific architecture ratings and action patterns

---

## Table of Contents

- [1. Purpose of Actions](#1-purpose-of-actions)
- [2. Relationship: AJAX, Game.php, States, Managers, Models, Notifications, State Machine](#2-relationship-ajax-gamephp-states-managers-models-notifications-state-machine)
- [3. Action Lifecycle](#3-action-lifecycle)
- [4. Action Responsibilities](#4-action-responsibilities)
- [5. Thin Action Principle](#5-thin-action-principle)
- [6. Validation Architecture](#6-validation-architecture)
- [7. Action Categories](#7-action-categories)
- [8. Parameter Handling](#8-parameter-handling)
- [9. Transaction Behaviour](#9-transaction-behaviour)
- [10. Notifications Inside Actions](#10-notifications-inside-actions)
- [11. Manager Delegation](#11-manager-delegation)
- [12. Error Handling](#12-error-handling)
- [13. Undo Implications](#13-undo-implications)
- [14. Simultaneous Action Implications](#14-simultaneous-action-implications)
- [15. Performance Considerations](#15-performance-considerations)
- [16. Security Considerations](#16-security-considerations)
- [17. Anti-Patterns](#17-anti-patterns)
- [18. Canonical Architecture](#18-canonical-architecture)
- [19. Templates](#19-templates)
- [20. Checklists](#20-checklists)

---

## 1. Purpose of Actions

An action is a single atomic unit of player intent expressed as a server-bound request. Every action has exactly five responsibilities in order:

1. **Validate** — confirm the request is legal in the current game state
2. **Execute** — apply the domain logic
3. **Persist** — write state changes to the database
4. **Notify** — broadcast the results to clients
5. **Transition** — signal the state machine where to go next

Actions are the only mechanism by which game state changes. There is no background processing, no cron jobs, no server-push. Every mutation flows through an action.

Actions serve three additional architectural functions:

- **Audit boundary** — every state change is attributable to a specific action by a specific player at a specific time
- **Undo boundary** — the undo system reverts at the action granularity (or coarser, never finer)
- **Security boundary** — the action is the point where the framework enforces authentication (is this player allowed to act?) and authorisation (is this action legal in this state?)

---

## 2. Relationship: AJAX, Game.php, States, Managers, Models, Notifications, State Machine

### 2.1 Conceptual Map

```
CLIENT                    SERVER
──────                    ──────

AJAX call ──────────────────────────┐
  (performAction)                   │
                                    ▼
                            ┌───────────────┐
                            │  action.php   │  ← Legacy entry point
                            │   OR          │
                            │  State class  │  ← Modern entry point
                            │  (autowired)  │
                            └───────┬───────┘
                                    │
                            ┌───────▼───────┐
                            │   Game.php    │  ← Thin coordinator
                            │  checkAction  │
                            └───────┬───────┘
                                    │
                    ┌───────────────┼───────────────┐
                    ▼               ▼               ▼
            ┌───────────┐   ┌───────────┐   ┌───────────┐
            │ Managers   │   │ Models    │   │ State     │
            │ (domain    │   │ (data     │   │ Machine   │
            │  logic)    │   │  objects) │   │ (trans.)  │
            └─────┬─────┘   └───────────┘   └─────┬─────┘
                  │                               │
                  ▼                               ▼
            ┌───────────┐                 ┌───────────────┐
            │ Database  │                 │ Notifications │
            │ (persist) │                 │ (broadcast)   │
            └───────────┘                 └───────────────┘
```

### 2.2 Responsibility Mapping

| Layer | Role in Action | Can Throw? | Can Notify? | Can Access DB? | Knows About HTTP? |
|---|---|---|---|---|---|
| **AJAX / `performAction`** | Transport layer. Serialises args, sends POST, receives response. | N/A | N/A | N/A | Yes |
| **`action.php`** | Legacy routing. Delegates to Game.php method. | No | No | No | Yes |
| **State class** | Modern action host. Validates params, delegates execution, returns transition. | Yes | Yes | Yes | No |
| **Game.php** | Thin coordinator. Framework-mandated methods, high-level flow. | Yes | Yes | Yes | No |
| **Managers** | Domain logic. One per subsystem. Mutates its tables. | Yes | Yes | Yes | No |
| **Models** | Data objects with behaviour. Computed properties, UI formatting. | No | No | No | No |
| **Notifications** | Centralised notification factory. | No | Yes (wraps framework) | No | No |
| **State Machine** | Advances on transition string returned by action. | No | No | No | No |

### 2.3 Call Chain

```
Client JS                           Server PHP
─────────                           ──────────

this.bga.actions.performAction(
  'actPlayCard', { cardId: 42 }
)
  │
  ▼
HTTP POST ──────────────────────────► action.php (legacy)
                                     OR
                                     State class autowiring (modern)
                                       │
                                       ▼
                                     checkAction('actPlayCard')
                                       │
                                       ▼
                                     State::actPlayCard(...)
                                       │
                                       ├── Manager::validate()
                                       ├── Manager::execute()
                                       ├── Notifications::cardPlayed()
                                       └── return 'cardPlayed'
                                       │
                                       ▼
                                     Framework advances state machine
                                       │
                                       ▼
                                     Commit transaction
                                       │
                                       ▼
◄───── HTTP response with notifications
```

### 2.4 Key Principle: Actions Are State-Scoped

Every action belongs to exactly one game state. The state declares which actions are legal via `possibleactions` (legacy) or `#[PossibleAction]` (modern). The framework enforces this at the routing layer — if an action is not declared for the current state, the request is rejected before any game logic runs.

For the full state machine relationship, see [state-machine-architecture.md §2](./state-machine-architecture.md#2-relationship-game-flow-states-actions-notifications-client-ui).

---

## 3. Action Lifecycle

### 3.1 Full Lifecycle Diagram

```
BROWSER
  │
  │  User clicks UI element
  │  JS calls this.bga.actions.performAction('actX', { args })
  │
  ▼
AJAX
  │
  │  POST to BGA platform
  │  Args serialised as JSON
  │
  ▼
FRAMEWORK ROUTING
  │
  │  Construct fresh Game.php instance
  │  Begin DB transaction
  │  Read current game state from DB
  │  Route to state's action handler
  │
  ▼
ACTION ROUTING
  │
  │  Legacy:  action.php → Game::actX()
  │  Modern:  State class autowiring
  │
  │  Framework calls checkAction('actX')
  │    → verifies action is in current state's possibleactions
  │    → verifies player is authorised (active/multiactive)
  │
  ▼
VALIDATION
  │
  │  ┌──────────────────────────────────────────────┐
  │  │  1. Framework validation  ── checkAction()   │
  │  │  2. State validation      ── is this state   │
  │  │                              valid to act in?│
  │  │  3. Game-rule validation   ── are params     │
  │  │                              semantically    │
  │  │                              legal?          │
  │  │  4. Domain validation     ── are             │
  │  │                              preconditions   │
  │  │                              met?            │
  │  │  5. Persistence validation ── do referenced  │
  │  │                              entities exist? │
  │  └──────────────────────────────────────────────┘
  │
  │  If ANY validation fails → throw BgaUserException
  │  → transaction rolls back → client sees error
  │
  ▼
EXECUTION
  │
  │  ┌──────────────────────────────────────────────┐
  │  │  Delegate to Manager(s)                      │
  │  │  Managers call Models for computed values    │
  │  │  Engine resolves decision tree (if used)     │
  │  │  No DB writes committed yet (buffered)       │
  │  └──────────────────────────────────────────────┘
  │
  ▼
PERSISTENCE
  │
  │  ┌──────────────────────────────────────────────┐
  │  │  DB writes accumulate in open transaction    │
  │  │  All reads see uncommitted writes            │
  │  │  (MySQL REPEATABLE READ isolation)           │
  │  └──────────────────────────────────────────────┘
  │
  ▼
NOTIFICATION
  │
  │  ┌──────────────────────────────────────────────┐
  │  │  Notifications::method()                     │
  │  │    → notifyAllPlayers / notifyPlayer         │
  │  │    → Queued in memory by framework           │
  │  │    → NOT yet delivered to clients            │
  │  └──────────────────────────────────────────────┘
  │
  ▼
TRANSITION
  │
  │  ┌──────────────────────────────────────────────┐
  │  │  Return transition string                    │
  │  │  Framework looks up transitions[returnValue] │
  │  │  Advances state machine to target state      │
  │  │  If target has action() method, runs it now  │
  │  └──────────────────────────────────────────────┘
  │
  ▼
COMMIT
  │
  │  ┌──────────────────────────────────────────────┐
  │  │  Framework commits DB transaction            │
  │  │  Notifications written to gamelog table      │
  │  │  Notifications delivered in HTTP response    │
  │  └──────────────────────────────────────────────┘
  │
  ▼
CLIENT UPDATE
  │
  │  ┌──────────────────────────────────────────────┐
  │  │  Client receives notification batch          │
  │  │  Processes sequentially:                     │
  │  │    notif_1 → notif_2 → ... → notif_N         │
  │  │  Each handler updates DOM / animates         │
  │  │  onEnteringState called if state changed     │
  │  └──────────────────────────────────────────────┘
```

### 3.2 Phase Detail: Validation

Validation is a gate. It runs before any mutation. The five validation layers are checked in order:

```
Framework   ──  checkAction('actX')
State       ──  Is the game in a valid state for this action?
Game-rule   ──  Does the action's parameter make semantic sense?
Domain      ──  Can this player afford it? Are preconditions met?
Persistence ──  Do the referenced entities exist in the DB?
```

Each layer is stricter than the last. Once any layer throws, the transaction rolls back. See [§6 Validation Architecture](#6-validation-architecture) for the full treatment.

### 3.3 Phase Detail: Transition

The action method returns a string. That string is looked up in the current state's `transitions` map:

```php
// State class constructor
transitions: [
    'cardPlayed' => 3,
    'turnPassed' => 4,
]

// Action returns the key
return 'cardPlayed';
// → Framework advances to state ID 3
```

If the target state is a `GAME`-type state with an `action()` method, that method runs immediately — within the same request, before the transaction commits.

If the target state has `_no_notify: true`, no state-change notification is sent to the client.

For the full treatment of transitions, see [state-machine-architecture.md §8](./state-machine-architecture.md#8-transition-design).

### 3.4 The Return Value Contract

| Framework Generation | Return Type | State Advancement |
|---|---|---|
| **Legacy** (`states.inc.php`) | `void` | Call `$this->gamestate->nextState('transition')` explicitly |
| **Modern** (State classes) | `string` | Return transition key; framework advances automatically |
| **Engine pattern** (Agricola/ArkNova) | `void` | Action calls `Engine::resolveAction()`; Engine calls `Globals::setEngine()` then Engine calls proceed which routes to next state |

---

## 4. Action Responsibilities

### 4.1 What Every Action Must Do

| Responsibility | Required? | Implementation |
|---|---|---|
| Validate framework permissions | Mandatory | `checkAction()` or `#[PossibleAction]` attribute |
| Validate parameters | Mandatory | Check types, ranges, and semantic validity |
| Validate game rules | Mandatory | Check preconditions, resources, legal targets |
| Execute domain logic | Mandatory | Delegate to Managers |
| Send notifications | Mandatory | Notify all affected players |
| Return transition | Mandatory | Return string key or call `nextState()` |
| Handle errors | Mandatory | Throw `\BgaUserException` for user errors, `\BgaSystemException` for internal errors |

### 4.2 What Every Action Must NOT Do

- **Contain raw database queries** — delegate to Managers
- **Contain business logic** — delegate to Managers
- **Contain notification construction** — delegate to Notifications class
- **Make HTTP calls or external side effects** — these cannot be rolled back
- **Cache data between requests** — the class instance is request-scoped
- **Assume client input is valid** — always re-validate on the server

### 4.3 Action Shape (Modern Framework)

```php
#[PossibleAction]
public function actPlayCard(
    int $cardId,
    int $activePlayerId,
    array $args
): string {
    // 1. Framework validation — automatic via #[PossibleAction]

    // 2. State validation — verify this state permits the action
    if (!$this->game->tableCards->isFaceUp($cardId)) {
        throw new \BgaUserException(clienttranslate('This card is not available'));
    }

    // 3. Game-rule validation
    $player = $this->game->players->get($activePlayerId);
    $card = $this->game->cards->get($cardId);
    if ($player->getCoins() < $card->getCost()) {
        throw new \BgaUserException(clienttranslate('Not enough coins'));
    }

    // 4. Domain validation
    if (!$this->game->cards->canPlayCard($cardId, $activePlayerId)) {
        throw new \BgaUserException(clienttranslate('This card cannot be played now'));
    }

    // 5. Execute — delegate to Managers
    $this->game->cards->playCard($cardId, $activePlayerId);

    // 6. Notify
    $this->game->notifications->cardPlayed($activePlayerId, $cardId);

    // 7. Transition
    return 'cardPlayed';
}
```

### 4.4 Action Shape (Legacy Framework)

```php
// In Game.php
public function actPlayCard(int $cardId, int $activePlayerId): void
{
    $this->checkAction('actPlayCard');

    // ... validate, execute, notify ...

    $this->gamestate->nextState('cardPlayed');
}
```

---

## 5. Thin Action Principle

### 5.1 Definition

An action method should be thin. It validates, delegates, notifies, and transitions. It does NOT contain domain logic, raw SQL, or notification construction.

### 5.2 The Delegation Hierarchy

```
Action method (thin)
  ├── checkAction()              ← framework
  ├── Player::validateAction()   ← Model (pure logic, no DB)
  ├── Cards::playCard()          ← Manager (domain logic + DB)
  ├── Notifications::cardPlayed() ← Notifications class
  └── return 'cardPlayed'
```

### 5.3 Canonical Example

```php
// THIN — correct
#[PossibleAction]
public function actDiscardCard(int $cardId, int $activePlayerId, array $args): string
{
    $this->game->players->canDiscard($activePlayerId, $cardId);
    $this->game->cards->discard($cardId, $activePlayerId);
    $this->game->notifications->cardDiscarded($activePlayerId, $cardId);
    return 'cardDiscarded';
}
```

### 5.4 Anti-Example

```php
// FAT — incorrect
#[PossibleAction]
public function actDiscardCard(int $cardId, int $activePlayerId, array $args): string
{
    // Don't do this — raw SQL in action method
    $card = $this->game->getObjectFromDB("SELECT * FROM card WHERE card_id = $cardId");
    $player = $this->game->getObjectFromDB("SELECT * FROM player WHERE player_id = $activePlayerId");

    if ($player['hand_size'] <= 1) {
        throw new \BgaUserException('Cannot discard last card');
    }

    $this->game->DbQuery("UPDATE card SET card_location = 'discard' WHERE card_id = $cardId");
    $this->game->DbQuery("UPDATE player SET hand_size = hand_size - 1 WHERE player_id = $activePlayerId");

    $this->game->notifyAllPlayers('cardDiscarded', clienttranslate('${player_name} discards a card'), [
        'player_id' => $activePlayerId,
        'card_id' => $cardId,
    ]);

    return 'cardDiscarded';
}
```

### 5.5 Measuring Thinness

An action method is "thin enough" when:
- It fits in 5-15 lines of code
- It contains no `if/else` branching for domain logic
- It contains no SQL strings
- Every substantive operation is a single delegation call
- A reader can understand the complete effect of the action in under 10 seconds

---

## 6. Validation Architecture

### 6.1 The Five Validation Layers

Validation is not a single step. It is five distinct layers, each with a different concern, timing, and failure mode.

```
REQUEST ENTERS
      │
      ▼
┌─────────────────────────────────────────────────────────────┐
│  LAYER 1: FRAMEWORK VALIDATION                              │
│                                                             │
│  checkAction('actX')  or  #[PossibleAction] attribute        │
│  ─────────────────────────────────────────────               │
│  Checks:   Is this action declared for the current state?    │
│            Is the requesting player authorised?               │
│            (active player check)                              │
│                                                             │
│  Throw:    BgaSystemException (framework-managed)             │
│  Recovers: Transaction rollback, client sees                │
│            "checkAction is not possible..."                   │
└─────────────────────────────────────────────────────────────┘
      │
      ▼
┌─────────────────────────────────────────────────────────────┐
│  LAYER 2: STATE VALIDATION                                  │
│                                                             │
│  Verify the action is semantically valid in this state.     │
│  ─────────────────────────────────────────────               │
│  Checks:   Is the game in a phase where this action         │
│            makes sense?                                      │
│            Have all required sub-steps been completed?       │
│                                                             │
│  Throw:    BgaUserException                                  │
│  Example:  "You cannot play a card during the discard phase" │
└─────────────────────────────────────────────────────────────┘
      │
      ▼
┌─────────────────────────────────────────────────────────────┐
│  LAYER 3: GAME-RULE VALIDATION                              │
│                                                             │
│  Validate parameters against game rules.                    │
│  ─────────────────────────────────────────────               │
│  Checks:   Do the parameters make semantic sense?            │
│            Is the target legal?                              │
│            Are the values within valid ranges?               │
│                                                             │
│  Throw:    BgaUserException                                  │
│  Example:  "Card index 42 does not exist"                    │
│            "Position (7,3) is outside the board"             │
└─────────────────────────────────────────────────────────────┘
      │
      ▼
┌─────────────────────────────────────────────────────────────┐
│  LAYER 4: DOMAIN VALIDATION                                 │
│                                                             │
│  Validate preconditions against current game state.         │
│  ─────────────────────────────────────────────               │
│  Checks:   Does the player have the required resources?      │
│            Are the preconditions for this action met?        │
│            Would this action violate game invariants?        │
│                                                             │
│  Throw:    BgaUserException                                  │
│  Example:  "Not enough coins"                                │
│            "You may only play one card per turn"             │
└─────────────────────────────────────────────────────────────┘
      │
      ▼
┌─────────────────────────────────────────────────────────────┐
│  LAYER 5: PERSISTENCE VALIDATION                            │
│                                                             │
│  Verify referenced entities exist and are in expected state. │
│  ─────────────────────────────────────────────               │
│  Checks:   Does the referenced card ID exist?                │
│            Is the card in the expected location?              │
│            Is the player actually who they claim to be?      │
│                                                             │
│  Throw:    BgaUserException (bad ID) or BgaSystemException   │
│            (inconsistent DB state)                           │
│  Example:  "Card not found in your hand"                     │
│            "Player state corrupted: hand_size mismatch"      │
└─────────────────────────────────────────────────────────────┘
      │
      ▼
EXECUTION BEGINS
```

### 6.2 Validation Flow Diagram

```
                    ┌─────────────────────┐
                    │   Client sends      │
                    │   performAction()   │
                    └──────────┬──────────┘
                               │
                    ┌──────────▼──────────┐
                    │  Framework routing  │
                    │  + checkAction()    │──→ L1 fail → rollback
                    └──────────┬──────────┘
                               │
                    ┌──────────▼──────────┐
                    │  State validation   │──→ L2 fail → BgaUserException
                    └──────────┬──────────┘
                               │
                    ┌──────────▼──────────┐
                    │  Game-rule          │
                    │  validation         │──→ L3 fail → BgaUserException
                    └──────────┬──────────┘
                               │
                    ┌──────────▼──────────┐
                    │  Domain             │
                    │  validation         │──→ L4 fail → BgaUserException
                    └──────────┬──────────┘
                               │
                    ┌──────────▼──────────┐
                    │  Persistence        │
                    │  validation         │──→ L5 fail → BgaUser/SystemException
                    └──────────┬──────────┘
                               │
                    ┌──────────▼──────────┐
                    │  EXECUTION          │
                    │  (no more throws)   │
                    └─────────────────────┘
```

### 6.3 Validation in the Engine Pattern (Agricola/ArkNova)

When using the Engine pattern, validation is distributed across two levels:

**Level A — Engine node `isDoable()`:** Before presenting a choice to the player, the Engine walks the decision tree and calls `isDoable()` on each unresolved node. Nodes that return `false` are excluded from the choice set. This is a soft check — it filters options without throwing.

```php
// In LeafNode or concrete Action class
public function isDoable(Player $player): bool
{
    return $player->getCoins() >= $this->getCost();
}
```

**Level B — Action `act*()` method:** When the player makes a choice, the action method re-validates. This is the hard check — it throws if preconditions are no longer met. This is necessary because the game state may have changed between the time options were presented and the time the player chose.

```php
public function actBuild(int $buildingId, int $activePlayerId, array $args): string
{
    // Re-validate — state may have changed since isDoable() was called
    if (!Actions::isDoable($this->actionId, Engine::getNextUnresolved(), Players::get($activePlayerId))) {
        throw new \BgaUserException(clienttranslate('This action is no longer available'));
    }
    // ... execute
}
```

### 6.4 Validation in the Command Queue Pattern (Earth)

In Earth's BX framework, validation is embedded in the command itself:

```php
class PlantCardActionCommand extends BaseActionCommand
{
    public function do(ActionCommandNotifier $notifier): void
    {
        // Validation happens inside do()
        if ($this->player->getActions() < 1) {
            throw new \BgaUserException('No actions remaining');
        }
        // ... apply to private state
    }
}
```

Commands are validated when applied (`ActionCommandMgr::apply()`) and re-validated via `reevaluate()` when the game state changes.

### 6.5 Validation Order Principle

**Validate before mutate. Never validate after write.**

Once any DB write has occurred, a validation failure forces a full transaction rollback. This is correct behaviour, but it wastes work. Validate everything that can be validated before making the first mutation.

```php
// CORRECT
public function actPlayCard(int $cardId, int $activePlayerId): string
{
    // ALL validation first
    $player = $this->game->players->get($activePlayerId);
    $card = $this->game->cards->get($cardId);
    $player->validateCanPlay($card);
    $this->game->cards->validatePlayable($cardId);

    // THEN mutation
    $this->game->cards->playCard($cardId, $activePlayerId);
    $this->game->players->spendResources($player, $card->getCost());

    // THEN notification
    $this->game->notifications->cardPlayed($activePlayerId, $cardId);
    return 'cardPlayed';
}
```

### 6.6 Validation Responsibility by Layer

| What to Validate | Where | Exception | Example |
|---|---|---|---|
| Action name, player authorisation | Framework (automatic) | System | `checkAction('actPlayCard')` |
| Parameter types, ranges | Action method | User | `$cardId > 0` |
| Parameter semantics | Action method → Manager | User | `cardId` exists in hand |
| Game rules, preconditions | Action method → Manager | User | Not enough coins |
| Player eligibility | Model | User | Player::canPlayCard() |
| DB entity existence | Manager | User | Card exists in expected location |
| Invariant enforcement | Manager | System | Hand size invariant violated |
| Engine choices | Engine node `isDoable()` | N/A (filtered, not thrown) | Skip unavailable choices |

---

## 7. Action Categories

### 7.1 Category Overview

| Category | Source | Player Active? | Notification Scope | Zombie Needed? |
|---|---|---|---|---|
| **Player actions** | Player click | Yes | Public + private | Yes |
| **Automatic actions** | State machine `action()` method | No | Public | No |
| **Setup actions** | Game initialisation | No | None | No |
| **Debug actions** | Developer (Studio only) | Varies | None (skipped in prod) | No |
| **Admin actions** | BGA admin panel | No | None | No |
| **Zombie actions** | Framework (`zombie()` method) | No (player disconnected) | Public | N/A |

### 7.2 Player Actions

The most common category. Initiated by a client-side user interaction.

```php
// Client
this.bga.actions.performAction('actPlayCard', { cardId: 42 });

// Server
#[PossibleAction]
public function actPlayCard(int $cardId, int $activePlayerId, array $args): string
{
    // validate → execute → notify → transition
}
```

**Characteristics:**
- Always gated by `checkAction()` or `#[PossibleAction]`
- Always requires the requesting player to be in the active set
- Always validates before mutating
- Always sends notifications

### 7.3 Automatic Actions

Executed by the framework when entering a `GAME`-type state that has an `action()` method.

```php
class ScoreRound extends GameState
{
    public function __construct(protected Game $game)
    {
        parent::__construct($game,
            id: 10,
            type: StateType::GAME,
            description: '',
            transitions: ['nextRound' => 4, 'endGame' => 99],
        );
    }

    public function action(int $activePlayerId, array $args): string
    {
        foreach ($this->game->players->getAll() as $player) {
            $points = $this->game->scoring->computeRoundScore($player);
            $this->game->players->addScore($player->getId(), $points);
            $this->game->notifications->roundScore($player, $points);
        }
        return $this->game->scoring->isGameEnded() ? 'endGame' : 'nextRound';
    }
}
```

**Characteristics:**
- No player input required
- Called automatically by framework on state entry
- Should handle `_no_notify` for states that pass through without client visible change
- Should NOT throw `BgaUserException` (there is no user to inform)
- Must return a valid transition key

### 7.4 Setup Actions

Called during `setupNewGame()` to initialise game state. Not routed through normal action machinery.

```php
public function setupNewGame(array $players): void
{
    // Create player records
    // Initialise globals
    // Deal cards
    // Set initial game state
}
```

**Characteristics:**
- Runs outside the normal request-response cycle
- No notifications needed (client receives full state via `getAllDatas`)
- No `checkAction()` call
- Must initialise all DB state for a new game

### 7.5 Debug Actions

Actions available only in the Studio development environment.

```php
// In a DebugTrait
public function actDebugAddResource(int $resourceType, int $amount, int $playerId): void
{
    if (!$this->isDevelopmentEnvironment()) {
        throw new \BgaSystemException('Debug actions are not available in production');
    }
    $this->players->addResource($playerId, $resourceType, $amount);
}
```

**Characteristics:**
- Guarded by environment check
- May bypass normal game rules
- Should still use proper Manager delegation
- Must be disabled in production

### 7.6 Admin Actions

Actions available only to BGA administrators. Rarely needed in game code.

### 7.7 Zombie Actions

Actions taken by the framework when a player disconnects. Defined in the `zombie()` method.

```php
public function zombie(int $playerId, array $args): string
{
    // For the current state, auto-perform the most reasonable action
    $stateName = $this->gamestate->state()['name'];
    switch ($stateName) {
        case 'playerTurn':
            $this->cards->autoPlayCard($playerId);
            return 'cardPlayed';
        case 'discardPhase':
            $this->cards->autoDiscardExcess($playerId);
            $hand = $this->cards->getHand($playerId);
            if (count($hand) <= 7) {
                $this->gamestate->setPlayerNonMultiactive($playerId);
            }
            return '';
        default:
            return '';
    }
}
```

**Characteristics:**
- Must be implemented for every non-`GAME` state
- Must leave the game in a consistent state
- Should choose the least harmful default action
- May return a transition string or empty string
- Should notify remaining players of the auto-action

---

## 8. Parameter Handling

### 8.1 Parameter Sources

Action parameters arrive from three sources:

| Source | Example | Trust Level |
|---|---|---|
| Client JS args | `performAction('actX', { cardId: 42 })` | Low — must validate |
| Framework magic params | `$activePlayerId`, `$currentPlayerId` | High — framework-provided |
| State args | `$args` (from `getArgs()`) | Medium — game-code computed |

### 8.2 Client-Provided Parameters

Every parameter from the client must be validated on the server.

```php
#[PossibleAction]
public function actPlayCard(
    int $cardId,            // Client-provided — validate
    int $activePlayerId,    // Framework-provided — trust
    array $args              // State args — trust
): string {
    // Validate client-provided parameter
    $this->game->cards->validateCardExists($cardId);
    $this->game->cards->validateInHand($cardId, $activePlayerId);
    // ...
}
```

### 8.3 Validation Rules for Parameters

| Parameter Type | Validation | Example |
|---|---|---|
| IDs (`int`) | Existence check, ownership check | `$this->cards->exists($cardId)` |
| Enums/choices (`string`) | Whitelist check | `in_array($choice, $validChoices)` |
| Quantities (`int`) | Range check, availability check | `$amount > 0 && $amount <= $max` |
| Positions (`int,int`) | Bounds check, occupancy check | `onBoard($x, $y) && !occupied($x, $y)` |
| Selections (`int[]`) | Each element validated individually | `array_all_exist($ids)` |

### 8.4 Magic Parameters

The framework auto-fills certain parameters. These should be used instead of manually calling `getActivePlayerId()` or `getCurrentPlayerId()`.

| Magic Parameter | Available In | Value |
|---|---|---|
| `$activePlayerId` / `$active_player_id` | `act*()`, `getArgs()` | The active player from the state machine |
| `$currentPlayerId` / `$current_player_id` | `act*()` only | The player who sent the HTTP request |
| `$activePlayerNo` / `$active_player_no` | `getArgs()` | Active player number |
| `$playerId` / `$player_id` | `getArgs()` (PRIVATE states only) | The private state's player |
| `$playerNo` / `$player_no` | `getArgs()` (PRIVATE states only) | The private state's player number |
| `$args` | `act*()` | State args from `getArgs()` |

### 8.5 Parameter Coercion

Framework magic parameters are typed. Client-provided parameters arrive as JSON via AJAX and are coerced by PHP. Always type-hint client-provided parameters and let PHP's type system handle coercion:

```php
// PHP will coerce string "42" to int 42
// PHP will throw TypeError if coercion is impossible
public function actPlayCard(
    int $cardId,   // ← typed
    // ...
): string {
```

---

## 9. Transaction Behaviour

### 9.1 The Implicit Transaction

BGA wraps every action in an implicit database transaction.

```
Request arrives
  │
  ▼
BEGIN TRANSACTION        ← automatic
  │
  ▼
Action method runs
  │  ├── reads see uncommitted data
  │  └── writes accumulate in buffer
  │
  ▼
if (exception thrown):
  └── ROLLBACK           ← automatic
      └── all DB changes reverted
      └── all notifications discarded
else:
  └── COMMIT             ← automatic
      └── notifications written to gamelog
      └── notifications delivered to clients
```

### 9.2 Implications for Action Implementation

**Rule 1 — Validate before write.** Once mutation starts, any failure forces a full rollback. All validation must complete before the first DB write.

**Rule 2 — No partial commits.** If any part of the action fails, the entire action is rolled back. Design actions to be atomic.

**Rule 3 — Reads see uncommitted writes.** Within a single request, a later DB read sees mutations from an earlier write. This is MySQL `REPEATABLE READ` isolation. Design validation to account for this: validate initial state BEFORE mutation, not after.

**Rule 4 — No external side effects.** The action cannot make HTTP calls, send emails, write files, or trigger external systems. These cannot be rolled back. See [game-flow-architecture.md §5.2](./game-flow-architecture.md#52-implications).

### 9.3 Transaction Boundaries in the Engine Pattern

When using the Engine pattern (Agricola/ArkNova), the entire tree resolution happens within a single DB transaction. Multiple Engine steps may execute in one request, but the commit happens only when the action method returns.

```php
// Single request, single transaction
// Engine may resolve: GAIN → CHOOSE → GAIN → PAY
// All four steps accumulate in the same transaction buffer
// Commit happens when the outermost action method returns
```

This means:
- Engine checkpoints (`Engine::checkpoint()`) are application-level, not DB-level
- Undo across Engine steps reads from the `log` table (same transaction)
- The transaction is not committed until the player confirms their turn

### 9.4 Transaction Boundaries in the Command Queue Pattern (Earth)

Earth's command queue pattern defers the commit across requests:

```
Request 1: Player queues command
  → Command applied to private state
  → Transaction commits (private state persisted)
  → Command saved to action_command table

Request 2: Player commits turn
  → All queued commands replayed through public notifier
  → Real game state mutated
  → Transaction commits (public state persisted)
```

This means the same atomicity guarantees apply per request, but the full action spans multiple requests.

---

## 10. Notifications Inside Actions

### 10.1 Ordering Rules

Notifications must follow a strict ordering:

```
1. EXECUTE  (domain logic runs)
2. NOTIFY   (results broadcast)
3. TRANSITION (state machine advances)
```

The transition must always be last. Notifications sent after `nextState()` or after returning a transition string may not be delivered. See [notification-patterns.md §14.7](./notification-patterns.md#147-sending-notifications-after-state-transitions).

### 10.2 What to Notify

Every action must notify visible consequences. The notification should describe the complete effect, not individual sub-steps.

```php
// CORRECT: single notification describing complete effect
$this->game->notifications->cardPlayed($activePlayerId, $cardId);

// INCORRECT: multiple notifications for sub-steps
$this->game->notifications->cardMoved($activePlayerId, $cardId);
$this->game->notifications->resourcesSpent($activePlayerId, $cost);
$this->game->notifications->resourcesGained($activePlayerId, $gain);
```

Exception: When sub-steps require distinct client-side animations, use multiple notifications but emit them before the transition.

### 10.3 Delegation to Notifications Class

Actions must never call `notifyAllPlayers` or `notifyPlayer` directly. Always delegate to the centralised Notifications class.

```php
// CORRECT
$this->game->notifications->cardPlayed($activePlayerId, $cardId);

// INCORRECT
$this->game->notifyAllPlayers('cardPlayed', clienttranslate('...'), [...]);
```

See [notification-patterns.md §15.1](./notification-patterns.md#151-centralized-notification-class).

### 10.4 Engine Pattern Notifications

In the Engine pattern, notifications are sent FROM the action's concrete class, not from the Engine or the state handler:

```php
class Gain extends Action
{
    public function actGain($resources, $playerId): void
    {
        // Execute
        $this->game->players->addResources($playerId, $resources);
        // Notify — in the concrete action, not in the Engine
        $this->game->notifications->gainResources(
            Players::get($playerId),
            $resources
        );
        // Resolve and proceed
        $this->resolveAction($args, $checkpoint = true);
    }
}
```

---

## 11. Manager Delegation

### 11.1 The Delegation Contract

Actions delegate to Managers. Managers encapsulate all domain logic for their subsystem. The contract:

```
Action (thin coordinator)
  │
  ├── calls Manager::validate*()   ← preconditions
  ├── calls Manager::execute*()    ← mutation
  └── calls Notifications::*()     ← broadcast

Manager
  ├── owns its DB table(s)
  ├── provides public read methods
  ├── provides public write methods
  └── may call other Managers (rare, via Game)
```

### 11.2 Manager Delegation Pattern

```php
// Action method — thin
#[PossibleAction]
public function actBuildStructure(int $structureId, int $activePlayerId, array $args): string
{
    $player = $this->game->players->get($activePlayerId);
    $structure = $this->game->structures->get($structureId);

    // Validation via Manager
    $this->game->structures->validateBuildable($structureId, $activePlayerId);

    // Validation via Model
    $player->validateResources($structure->getCost());

    // Execution via Manager
    $this->game->structures->build($structureId, $activePlayerId);
    $this->game->players->spendResources($activePlayerId, $structure->getCost());

    // Notify
    $this->game->notifications->structureBuilt($activePlayerId, $structureId);

    return 'structureBuilt';
}
```

### 11.3 Manager-to-Manager Communication

Managers should not call other Managers directly. Instead, the action method orchestrates. However, when Managers must interact (e.g., a card effect modifies resources), the preferred pattern is:

```php
// Option A: Action orchestrates (preferred)
$this->game->cards->playCard($cardId, $activePlayerId);
$this->game->players->addResources($activePlayerId, $bonusResources);

// Option B: Manager returns result, action passes to next Manager
$cost = $this->game->cards->getPlayCost($cardId, $activePlayerId);
$this->game->players->spendResources($activePlayerId, $cost);

// Option C: Manager calls Game as mediator (rare, for complex interactions)
// Cards manager asks Game to coordinate cross-manager effects
$this->game->onCardPlayed($cardId, $activePlayerId);
```

### 11.4 Engine Pattern Delegation

In the Engine pattern, the delegation chain is:

```
Client → State → Engine → Actions::takeAction() → Concrete Action class → Manager(s)
```

The concrete Action class (`Actions/Gain.php`, `Actions/Pay.php`) is itself a thin delegate:

```php
class Gain extends \AGR\Models\Action
{
    public function actGain(array $resources, int $playerId, bool $notify = true): void
    {
        $player = Players::get($playerId);
        $player->gainResources($resources);     // Manager
        if ($notify) {
            Notifications::gainResources($player, $resources);
        }
        Engine::resolveAction([
            'playerId' => $playerId,
            'resources' => $resources,
        ]);
    }
}
```

### 11.5 Command Queue Pattern Delegation

In the command queue pattern, delegation flows through the command:

```
Client → State → ActionCommandMgr::apply()
  → Command::do()           ← applies to private state
  → ActionCommandMgr::save() ← persists command to DB

Client → State → ActionCommandMgr::commit()
  → Command::commit()       ← replays through public notifier
```

---

## 12. Error Handling

### 12.1 Error Categories and Their Exceptions

```
ERROR CATEGORIES
│
├── IMPOSSIBLE MOVE
│   → The player attempted something that is not a legal move
│   → BgaUserException
│   → Example: "You cannot place a farmer on an occupied field"
│   → Responsibility: Action method / Manager validation
│
├── INVALID REQUEST
│   → The request parameters are malformed or out of range
│   → BgaUserException
│   → Example: "Card ID 999 does not exist"
│   → Responsibility: Action method parameter validation
│
├── ILLEGAL STATE
│   → The action is not permitted in the current game state
│   → BgaUserException (or framework-handled)
│   → Example: "You cannot act in this phase"
│   → Responsibility: Framework checkAction() + state validation
│
└── INTERNAL FAILURE
    → The game logic encountered an unexpected condition
    → BgaSystemException
    → Example: "Invariant violation: hand_size < 0"
    → Responsibility: Manager / Model invariant checks
```

### 12.2 Exception Decision Tree

```
Action received
  │
  ├── Is the action in the current state's possibleactions?
  │     NO  → Framework throws (ILLEGAL STATE)
  │
  ├── Are all parameters valid IDs, ranges, types?
  │     NO  → Throw BgaUserException (INVALID REQUEST)
  │
  ├── Is the action semantically legal in this game phase?
  │     NO  → Throw BgaUserException (ILLEGAL STATE)
  │
  ├── Are all preconditions met?
  │     NO  → Throw BgaUserException (IMPOSSIBLE MOVE)
  │
  ├── Does the action violate a game invariant during execution?
  │     YES → Throw BgaSystemException (INTERNAL FAILURE)
  │
  └── Action completes successfully
```

### 12.3 Exception Reference

| Exception | When to Use | User Visible? | Message Style |
|---|---|---|---|
| `\BgaUserException` | The player made a mistake | Yes — shown as popup | "You cannot X because Y" |
| `\BgaSystemException` | The code has a bug or state is corrupted | No — "An internal error has occurred" | Technical description for developer |
| `\BgaVisibleSystemException` | Rare: system error that should be visible | Yes | Technical detail |
| `\feException` | Legacy — use BgaSystemException instead | No | Legacy |

### 12.4 Error Message Best Practices

**User-visible errors (`BgaUserException`):**
- Explain what the player should do instead
- Use `clienttranslate()` for i18n
- Be specific: "You have only 2 coins" NOT "Insufficient funds"
- Never expose internal state: "Card ID 42 is not in your hand" is fine; "SELECT query on card table failed" is not

```php
// GOOD
throw new \BgaUserException(clienttranslate('You cannot play this card because you already have 3 active cards'));

// BAD — technical details exposed
throw new \BgaUserException('Failed to insert into card table');

// BAD — unhelpful
throw new \BgaUserException(clienttranslate('Invalid move'));
```

**System errors (`BgaSystemException`):**
- Describe the invariant that was violated
- Include relevant state for debugging
- Never call `clienttranslate()` (these strings are for developers)

```php
// GOOD
throw new \BgaSystemException("Hand size invariant violation: expected {$expected}, got {$actual} for player {$playerId}");

// BAD — silent failure
return;  // Never silently ignore invariants
```

### 12.5 The `ST_IMPOSSIBLE_MANDATORY_ACTION` Pattern

When a mandatory action cannot be completed (e.g., a forced card draw from an empty deck), use this state instead of throwing. See [game-flow-architecture.md §10.4](./game-flow-architecture.md#104-the-st_impossible_mandatory_action-escape-hatch).

---

## 13. Undo Implications

### 13.1 Undo and the Action Boundary

Undo operates at the action granularity. The undo system reverts the effects of one or more actions.

```
Normal action:
  VALIDATE → EXECUTE → PERSIST → NOTIFY → TRANSITION

Undo action:
  READ LOG → REVERSE DB → CANCEL GAMELOG → NOTIFY → TRANSITION
```

### 13.2 How Actions Support Undo

Actions must be designed with undo in mind, even if undo is not immediately implemented.

**Principle 1 — Log every mutation.** Every `INSERT`, `UPDATE`, and `DELETE` must be logged to the `log` table with enough information to reverse it.

```php
// In a Manager method
public function addResource(int $playerId, string $type, int $amount): void
{
    $oldValue = $this->getResource($playerId, $type);
    $newValue = $oldValue + $amount;
    self::DbQuery("UPDATE player SET {$type} = {$newValue} WHERE player_id = {$playerId}");

    // Log for undo
    Log::addEntry('resource_change', [
        'player_id' => $playerId,
        'type' => $type,
        'old' => $oldValue,
        'new' => $newValue,
    ]);
}
```

**Principle 2 — Make actions reversible.** The log entry must contain the original values to enable rollback.

**Principle 3 — Tag irreversible actions.** Some actions (e.g., deck shuffles, random draws) are inherently irreversible. Mark them so the undo system can refuse to undo past them.

```php
// ArkNova pattern
public function isIrreversible(Player $player): bool
{
    return $this->involvesRandomness();
}
```

### 13.3 Undo Patterns by Architecture

| Architecture | Undo Mechanism | Granularity | DB Impact |
|---|---|---|---|
| **Engine pattern** (Agricola/ArkNova) | `Log` table + Engine checkpoints + gamelog cancellation | Per-step or full-turn | Reverts DB + cancels gamelog |
| **Command queue** (Earth) | `ActionCommandMgr::undoAll()` | Per-command | Removes from queue (not yet committed) |
| **Dedicated states** (Arnak) | Single-action undo via state action | Per-action | Framework-level `db_undo_support` |

For full details on undo patterns, see [game-flow-architecture.md §11](./game-flow-architecture.md#11-undo-interaction) and [notification-patterns.md §12](./notification-patterns.md#12-undo-interactions).

---

## 14. Simultaneous Action Implications

### 14.1 The Problem

In simultaneous-turn phases (`MULTIPLE_ACTIVE_PLAYER`), multiple players send actions concurrently. Each action is a separate HTTP request in a separate DB transaction. Without safeguards, one player's committed action can invalidate another player's in-flight action.

```
Player A sends actPlantCard(cardId=5) ──► Transaction begins
                                            Read: card 5 is in market
                                            Validate: OK
                                            Write: move card 5 to hand
                                            Commit: SUCCESS

Player B sends actPlantCard(cardId=5) ──► Transaction begins (AFTER A committed)
                                            Read: card 5 is gone (sees A's commit)
                                            Validate: FAIL — card not in market
                                            Rollback: clean
```

But if A and B read the old state simultaneously:

```
Player A: Read card 5 → in market
Player B: Read card 5 → in market  (stale — hasn't seen A yet)
Player A: Write + Commit → card 5 gone
Player B: Validate → still thinks card 5 exists
Player B: Write → DUPLICATE or CORRUPT
```

### 14.2 Solutions

**Solution A: Framework locking (recommended for most games).** The framework serialises requests per table. Standard BGA behaviour ensures that two actions for the same table never run concurrently. This is sufficient for most games.

**Solution B: Pessimistic locking (Earth pattern).** Earth implements MySQL advisory locks via `Lock.php`:

```php
\BX\Lock\Locker::lock();
// Critical section — only one request at a time
\BX\Action\ActionCommandMgr::apply($playerId);
\BX\Lock\Locker::unlock();
```

**Solution C: Validate-after-commit pattern.** When a resource is contended, perform the critical check AFTER the write, and roll back if the check fails:

```php
public function actClaimTile(int $tileId, int $activePlayerId, array $args): string
{
    // Try to claim (atomic update with condition)
    $claimed = $this->game->board->claimTileIfAvailable($tileId, $activePlayerId);
    if (!$claimed) {
        throw new \BgaUserException(clienttranslate('This tile has already been claimed by another player'));
    }
    // ... notify and transition
}
```

The `claimTileIfAvailable` method uses an atomic `UPDATE ... WHERE claimed = 0` query that only succeeds if the tile is still unclaimed.

### 14.3 Implications for Action Design

- **Check for freshness at the point of write.** Reading state at the start of the action does not guarantee it is still valid when you write.
- **Use conditional updates** (`UPDATE ... WHERE condition`) for contended resources.
- **Document contended resources** clearly in the Manager interface.
- **For command queue pattern**, implement `reevaluate()` to handle cross-player invalidation.

---

## 15. Performance Considerations

### 15.1 Action Execution Time

BGA imposes a PHP execution limit (typically 30 seconds). Each action should complete well within this limit.

| Action Type | Target Time | Max Acceptable |
|---|---|---|
| Simple action (play card, pass) | <100ms | <1s |
| Complex action (Engine resolution, scoring) | <500ms | <5s |
| Setup action | <2s | <10s |

### 15.2 DB Query Minimisation

Each action should minimise database queries.

```php
// BAD: N+1 queries in a loop
foreach ($cards as $card) {
    $this->game->DbQuery("UPDATE card SET location = 'discard' WHERE card_id = {$card['id']}");
}

// GOOD: single batch query
$ids = array_map(fn($c) => $c['id'], $cards);
$this->game->DbQuery("UPDATE card SET location = 'discard' WHERE card_id IN (" . implode(',', $ids) . ")");
```

### 15.3 Notification Payload Size

Keep notification payloads small. See [notification-patterns.md §13.1](./notification-patterns.md#131-payload-size).

| Notification Type | Target Size | Max Acceptable |
|---|---|---|
| Regular action notification | <1KB | <5KB |
| State refresh (`refreshUI`) | <10KB | <50KB |
| Delta update (changed fields only) | <500B | <2KB |

### 15.4 Expensive Operations to Avoid in Actions

- **Full table scans** — ensure query conditions use indexed columns
- **Serialising large objects** — send IDs, not full objects, when the client has cached data
- **Computing full state from scratch** — compute deltas when possible
- **Deep recursion** — PHP recursion limit defaults to 256; avoid for Engine trees with many nodes
- **Repeated reads of the same data** — cache within the request scope

---

## 16. Security Considerations

### 16.1 Server Authority

The server is the sole authority for all game state. This principle governs all security considerations.

See [game-flow-architecture.md §6](./game-flow-architecture.md#6-server-authority).

### 16.2 Action-Specific Security Rules

**Rule 1 — Never trust client-provided values.** Every parameter from the client must be validated:
- IDs must reference existing entities
- The requesting player must own or have access to referenced entities
- Quantities must be within valid ranges

**Rule 2 — Never process actions outside declared possible actions.** The framework enforces this via `checkAction()`, but action methods must not bypass it.

**Rule 3 — Never reveal hidden information in error messages.** Error messages should not reveal:
- Contents of other players' hands
- Deck order or composition
- Future game state

```php
// BAD — reveals card doesn't exist in player's hand, but also reveals card name
throw new \BgaUserException(clienttranslate('You do not have The Great Dragon in your hand'));

// GOOD — generic
throw new \BgaUserException(clienttranslate('You cannot play this card'));
```

**Rule 4 — Never accept player IDs from the client.** Use the framework-provided `$activePlayerId` or `$currentPlayerId`.

```php
// BAD — client provides player ID
public function actPlayCard(int $cardId, int $playerId, array $args): string

// GOOD — framework provides player ID
public function actPlayCard(int $cardId, int $activePlayerId, array $args): string
```

**Rule 5 — Guard debug/admin actions.** Debug actions must check the environment:

```php
if (!$this->game->isDevelopmentEnvironment()) {
    throw new \BgaSystemException('Not available');
}
```

---

## 17. Anti-Patterns

### 17.1 Fat Actions

**Problem:** Action methods contain domain logic, SQL queries, and notification construction.

```php
// ANTI-PATTERN
public function actPlayCard(int $cardId, int $activePlayerId): string
{
    $card = $this->game->getObjectFromDB("SELECT * FROM card WHERE card_id = $cardId");
    // ... 30 more lines of inline logic
}
```

**Solution:** Delegate to Managers.

### 17.2 Notification-First

**Problem:** Notifications are sent before the action is fully validated.

```php
// ANTI-PATTERN
public function actPlayCard(int $cardId, int $activePlayerId): string
{
    $this->game->notifyAllPlayers('cardPlayed', ...);
    // ... validation that might throw AFTER notification
    if (!$this->game->cards->canPlayCard($cardId)) {
        throw new \BgaUserException('Cannot play this card');  // Rollback — notification lost?
    }
}
```

**Solution:** Validate completely before notifying.

### 17.3 Validation-After-Write

**Problem:** State is mutated before validation is complete.

```php
// ANTI-PATTERN
public function actPlayCard(int $cardId, int $activePlayerId): string
{
    $this->game->cards->moveCard($cardId, 'play', $activePlayerId);  // Write first
    if (!$this->game->cards->canPlayCard($cardId)) {                  // Validate after
        // Too late — card is already moved
    }
}
```

**Solution:** Validate everything before the first write.

### 17.4 Database-in-Action

**Problem:** Action methods contain raw SQL queries.

```php
// ANTI-PATTERN
public function actPlayCard(int $cardId, int $activePlayerId): string
{
    $this->game->DbQuery("UPDATE card SET card_location = 'play' WHERE card_id = $cardId");
}
```

**Solution:** Wrap all DB access in Manager classes.

### 17.5 Business Logic in Game.php

**Problem:** Game.php accumulates domain logic that belongs in Managers.

```php
// ANTI-PATTERN — Game.php
public function calculateScore(int $playerId): int
{
    $cards = $this->getObjectListFromDB("SELECT ...");
    $score = 0;
    foreach ($cards as $card) { /* complex scoring logic */ }
    return $score;
}
```

**Solution:** Move to `Managers/Scoring.php` or appropriate Manager.

### 17.6 Client-Trusting Actions

**Problem:** Server assumes client-provided values are valid without re-validation.

```php
// ANTI-PATTERN
public function actPlayCard(int $cardId, int $activePlayerId): string
{
    // Assumes client passed a valid card ID
    $this->game->cards->playCard($cardId, $activePlayerId);
}
```

**Solution:** Validate every client-provided parameter.

### 17.7 Duplicate Validation

**Problem:** The same validation is repeated at multiple layers unnecessarily.

```php
// ANTI-PATTERN: validating card existence in EVERY action that accepts a cardId
$card = $this->game->cards->get($cardId);
if (!$card) {
    throw new \BgaUserException('Card does not exist');
}
```

**Solution:** Centralise validation in Manager methods. The Manager's `get()` method throws if the entity does not exist. Don't re-check in the action.

### 17.8 Silent Failure

**Problem:** The action catches exceptions and continues, leaving the game in an inconsistent state.

```php
// ANTI-PATTERN
public function actPlayCard(int $cardId, int $activePlayerId): string
{
    try {
        $this->game->cards->playCard($cardId, $activePlayerId);
    } catch (\Exception $e) {
        // Silently ignore — game state is now inconsistent
    }
}
```

**Solution:** Let exceptions propagate. The framework handles rollback.

### 17.9 Action Method Returning Wrong Type

**Problem:** Action method returns a value that is not a valid transition key.

```php
// ANTI-PATTERN — returns nothing or wrong type
public function actPlayCard(int $cardId, int $activePlayerId): string
{
    // ... 
    return;  // void return — framework error
}
```

**Solution:** Always return a valid transition string from the state's `transitions` map. For legacy framework, always call `nextState()`.

### 17.10 Actions That Span Multiple States Without Engine

**Problem:** A single logical action spans multiple framework states, with each state expecting a separate `act*()` call, but the action methods are not properly coordinated.

**Solution:** Use either dedicated states (each action is self-contained) or the Engine pattern (tree manages flow). Do not manually coordinate multi-state actions in ad hoc code.

### 17.11 Earth-Specific Anti-Patterns

| Anti-Pattern | Solution |
|---|---|
| Calling `ActionCommandMgr::commit()` without validating pending commands | Call `reevaluate()` before `commit()` to detect invalidated commands |
| Forgetting to call `Lock::lock()` in contended actions | Always acquire lock for any action that reads/mutates shared state |
| Sending public notifications from private command notifier | Use `ActionCommandNotifierPublic` for committed actions, not `ActionCommandNotifierPrivate` |

---

## 18. Canonical Architecture

### 18.1 Sequence Diagram: Player Action

```
┌──────┐    ┌──────────┐    ┌──────────┐    ┌────────┐    ┌──────────┐    ┌──────────────┐    ┌──────────────┐
│Client │    │ Framework│    │ Game.php │    │ State  │    │ Managers │    │ Notifications│    │    State     │
│  JS   │    │          │    │          │    │ Class  │    │          │    │              │    │   Machine    │
└──┬───┘    └────┬─────┘    └────┬─────┘    └───┬────┘    └────┬─────┘    └──────┬───────┘    └──────┬───────┘
   │             │               │              │              │                 │                   │
   │performAction│               │              │              │                 │                   │
   │('actPlay',  │               │              │              │                 │                   │
   │ {id: 42})   │               │              │              │                 │                   │
   │────────────►│               │              │              │                 │                   │
   │             │               │              │              │                 │                   │
   │             │begin txn      │              │              │                 │                   │
   │             │construct Game │              │              │                 │                   │
   │             │──────────────►│              │              │                 │                   │
   │             │               │              │              │                 │                   │
   │             │route to state │              │              │                 │                   │
   │             │───────────────┼─────────────►│              │                 │                   │
   │             │               │              │              │                 │                   │
   │             │               │checkAction   │              │                 │                   │
   │             │               │◄─────────────┤              │                 │                   │
   │             │               │              │              │                 │                   │
   │             │               │              │validate()    │                 │                   │
   │             │               │              │─────────────►│                 │                   │
   │             │               │              │              │                 │                   │
   │             │               │              │execute()     │                 │                   │
   │             │               │              │─────────────►│                 │                   │
   │             │               │              │              │──► DB writes   │                   │
   │             │               │              │              │  (buffered in  │                   │
   │             │               │              │              │   txn)         │                   │
   │             │               │              │              │                 │                   │
   │             │               │              │notify()      │                 │                   │
   │             │               │              │───────────────────────────────►│                   │
   │             │               │              │              │                 │                   │
   │             │               │              │return 'done' │                 │                   │
   │             │               │              │──────────────┼─────────────────┼──────────────────►│
   │             │               │              │              │                 │                   │
   │             │               │advance state │              │                 │                   │
   │             │◄──────────────┼──────────────┤              │                 │                   │
   │             │               │              │              │                 │                   │
   │             │commit txn     │              │              │                 │                   │
   │◄────────────│notifications  │              │              │                 │                   │
   │             │               │              │              │                 │                   │
```

### 18.2 Request Lifecycle Diagram

```
┌─────────────────────────────────────────────────────────────────────────────────────┐
│                           REQUEST LIFECYCLE                                           │
│                                                                                       │
│   ┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐   ┌──────────┐           │
│   │TRANSPORT │   │ ROUTING  │   │VALIDATION│   │EXECUTION │   │ RESPONSE │           │
│   │(HTTP)    │   │(State)   │   │(5-layer) │   │(Manager) │   │(Notifs)  │           │
│   └──────────┘   └──────────┘   └──────────┘   └──────────┘   └──────────┘           │
│         │              │              │              │              │                 │
│         │ POST /       │              │              │              │                 │
│         │ action.php   │              │              │              │                 │
│         │─────────────►│              │              │              │                 │
│         │              │ #[Possible  │              │              │                 │
│         │              │ Action]     │              │              │                 │
│         │              │ autowire    │              │              │                 │
│         │              │────────────►│              │              │                 │
│         │              │              │ Framework   │              │                 │
│         │              │              │ checkAction │              │                 │
│         │              │              │────────────►│              │                 │
│         │              │              │              │ Manager     │                 │
│         │              │              │              │ delegation  │                 │
│         │              │              │              │────────────►│                 │
│         │              │              │              │              │ Notification   │
│         │              │              │              │              │ batch          │
│         │              │              │              │              │────────────►   │
│         │              │              │              │              │                │
│         │◄─────────────┼──────────────┼──────────────┼──────────────┤                │
│         │ JSON with    │              │              │              │                │
│         │ notifications│              │              │              │                │
└─────────────────────────────────────────────────────────────────────────────────────┘
```

### 18.3 Validation Flow Diagram

```
                         ┌───────────────────┐
                         │  Client Action    │
                         │  performAction()  │
                         └─────────┬─────────┘
                                   │
                         ┌─────────▼─────────┐
                         │  FRAMEWORK LAYER  │
                         │  checkAction()    │──→ throw BgaSystemException
                         │  active player?   │    "checkAction not possible"
                         └─────────┬─────────┘
                                   │
                         ┌─────────▼─────────┐
                         │  STATE LAYER       │
                         │  Is this action   │──→ throw BgaUserException
                         │  valid in this    │    "You cannot do that now"
                         │  game phase?      │
                         └─────────┬─────────┘
                                   │
                         ┌─────────▼─────────┐
                         │  GAME-RULE LAYER   │
                         │  params make      │──→ throw BgaUserException
                         │  semantic sense?   │    "Card not found"
                         │  targets legal?    │
                         └─────────┬─────────┘
                                   │
                         ┌─────────▼─────────┐
                         │  DOMAIN LAYER      │
                         │  preconditions     │──→ throw BgaUserException
                         │  met?              │    "Not enough coins"
                         │  player eligible?  │
                         └─────────┬─────────┘
                                   │
                         ┌─────────▼─────────┐
                         │  PERSISTENCE       │
                         │  entities exist?   │──→ throw BgaUserException
                         │  DB consistent?    │    or BgaSystemException
                         └─────────┬─────────┘
                                   │
                         ┌─────────▼─────────┐
                         │  EXECUTION         │
                         │  (all gates passed)│
                         └───────────────────┘
```

### 18.4 Reference Project Comparison

| Aspect | Arnak | Agricola | Ark Nova | Earth |
|---|---|---|---|---|
| **Action routing** | `action.php`→Game.php | `action.php`→Game.php | `action.php`→Game.php | `action.php`→Game.php |
| **Action abstraction** | Direct methods on Game.php | Engine + Actions registry | Engine + Actions registry | Command queue (BX framework) |
| **Action registry** | None (ad-hoc methods) | `Managers/Actions.php` | `Managers/Actions.php` | `ActionCommandMgr` |
| **Action base class** | None | `Models/Action.php` | `Models/Action.php` | `BaseActionCommand` |
| **Validation pattern** | Inline in action methods | `isDoable()` + action method | `isDoable()` + action method | `do()` + `reevaluate()` |
| **Action file pattern** | Methods in Game.php | `Actions/*.php` | `Actions/*.php` | `Actions/*.php` |
| **Action granularity** | Single step per action | Atomic (Gain, Pay, etc.) | Atomic (Gain, Pay, Build, etc.) | Per-player-command |
| **Undo per action** | Basic framework undo | Log-based (any granularity) | Log-based (any granularity) | Command undo (`do()`/`undo()`) |
| **Action notification** | Inline `notifyAllPlayers` | Centralised Notifications | Centralised Notifications | Command notifier pattern |
| **Number of action files** | 0 (inline in Game.php) | ~20 action classes | ~40 action classes + effects | ~15 command classes |
| **Auto-resolution** | Manual `_no_notify` | Engine tree auto-resolve | Engine tree auto-resolve | Command commit sequence |
| **Cross-player invalidation** | N/A | Engine `confirmPartialTurn` | Engine `confirmPartialTurn` | `reevaluate()` system |

### 18.5 Strengths per Project

**Arnak:**
- Simplest action model — no abstraction overhead
- Best for games with <20 distinct action types
- Easy to trace and debug

**Agricola:**
- Best action abstraction for complex card-driven games
- `Actions` registry with `isDoable()`/`takeAction()` is the canonical thin-action pattern
- The `Action` base class provides consistent validation hooks

**Ark Nova:**
- Richest action ecosystem with support for bonuses, effects, and dynamic flow
- `insertBonusesFlow()` converts arbitrary bonuses into action trees at runtime
- `FlowConvertor` pattern for dynamic action generation from card effects

**Earth:**
- Only solution for simultaneous-turn action management
- Command queue with `do()`/`undo()`/`reevaluate()` is the only pattern that handles cross-player action invalidation
- Four-tier notifier system handles private preview, public commit, silent undo, and internal application

---

## 19. Templates

### 19.1 Canonical Action Class Template

```php
<?php

declare(strict_types=1);

namespace Bga\Games\YourGame\Actions;

use Bga\GameFramework\States\PossibleAction;
use Bga\Games\YourGame\Core\Notifications;
use Bga\Games\YourGame\Managers\Players;
use Bga\Games\YourGame\Managers\Cards;
use Bga\Games\YourGame\Models\Player;

/**
 * Atomic action: Play a card from hand
 *
 * Validates card ownership, affordability, and playability.
 * Moves card from hand to tableau, deducts cost, notifies.
 */
class PlayCard
{
    /**
     * Check whether this action is doable for the given player.
     * Used by the Engine to filter available choices.
     */
    public static function isDoable(Player $player): bool
    {
        $playableCards = Cards::getPlayableCards($player->getId());
        return !empty($playableCards);
    }

    /**
     * Execute the action (called from State class).
     */
    public static function act(int $cardId, int $activePlayerId): void
    {
        $player = Players::get($activePlayerId);
        $card = Cards::get($cardId);

        // Domain validation
        if ($player->getCoins() < $card->getCost()) {
            throw new \BgaUserException(
                \clienttranslate('You do not have enough coins to play this card')
            );
        }

        // Execute via Managers
        Cards::playCard($cardId, $activePlayerId);
        Players::spendCoins($activePlayerId, $card->getCost());

        // Notify
        Notifications::cardPlayed($player, $card);
    }
}
```

### 19.2 State Class Action Method Template

```php
<?php

declare(strict_types=1);

namespace Bga\Games\YourGame\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\YourGame\Core\Notifications;
use Bga\Games\YourGame\Managers\Players;
use Bga\Games\YourGame\Managers\Cards;

class PlayerTurn extends GameState
{
    public function __construct(protected \Bga\Games\YourGame\Game $game)
    {
        parent::__construct($game,
            id: 2,
            type: StateType::ACTIVE_PLAYER,
            description: \clienttranslate('${actplayer} must play a card or pass'),
            descriptionMyTurn: \clienttranslate('${you} must play a card or pass'),
            transitions: [
                'cardPlayed' => 3,
                'turnPassed' => 4,
            ],
            updateGameProgression: true,
        );
    }

    #[PossibleAction]
    public function actPlayCard(int $cardId, int $activePlayerId, array $args): string
    {
        // State validation
        $this->checkAction('actPlayCard');

        // Game-rule + domain validation via Manager
        Cards::validatePlayable($cardId, $activePlayerId);

        // Execute via domain action class
        Actions\PlayCard::act($cardId, $activePlayerId);

        // Transition
        return 'cardPlayed';
    }

    #[PossibleAction]
    public function actPass(int $activePlayerId, array $args): string
    {
        $this->checkAction('actPass');
        Notifications::turnPassed(Players::get($activePlayerId));
        return 'turnPassed';
    }

    public function getArgs(int $activePlayerId): array
    {
        return [
            'playableCards' => Cards::getPlayableCardIds($activePlayerId),
            '_private' => [
                $activePlayerId => [
                    'hand' => Cards::getHand($activePlayerId),
                ],
            ],
        ];
    }
}
```

### 19.3 Validation Template

```php
/**
 * Five-layer validation template.
 * Copy this structure for every action.
 */
public function actSomeAction(int $param, int $activePlayerId, array $args): string
{
    // ── LAYER 1: Framework validation ──
    // Automatic via #[PossibleAction] attribute
    // or manual: $this->checkAction('actSomeAction');

    // ── LAYER 2: State validation ──
    // Verify the game phase permits this action
    if (!$this->isActionAllowedInCurrentPhase()) {
        throw new \BgaUserException(
            \clienttranslate('This action is not allowed in the current phase')
        );
    }

    // ── LAYER 3: Game-rule validation ──
    // Verify parameters are semantically valid
    $entity = Manager::get($param);
    if ($entity === null) {
        throw new \BgaUserException(
            \clienttranslate('Invalid parameter')
        );
    }

    // ── LAYER 4: Domain validation ──
    // Verify preconditions
    $player = Players::get($activePlayerId);
    if (!$player->canPerformAction()) {
        throw new \BgaUserException(
            \clienttranslate('You cannot perform this action right now')
        );
    }

    // ── LAYER 5: Persistence validation ──
    // Verify referenced entities are in expected state
    Manager::validateEntityState($param, $activePlayerId);

    // ── EXECUTION ──
    Manager::execute($param, $activePlayerId);
    Notifications::actionPerformed($player, $entity);

    return 'transitionKey';
}
```

### 19.4 Exception Template

```php
/**
 * Standardised exception usage.
 */

// ── User-visible errors (player mistake) ──
// IMPOSSIBLE MOVE
throw new \BgaUserException(
    \clienttranslate('You cannot place a worker on an occupied space')
);

// INVALID REQUEST
throw new \BgaUserException(
    \clienttranslate('Card ID ${cardId} is not in your hand')
);

// ILLEGAL STATE
throw new \BgaUserException(
    \clienttranslate('You cannot act during ${phaseName}')
);

// ── System errors (code bug / invariant violation) ──
// INTERNAL FAILURE
throw new \BgaSystemException(
    "Expected hand_size <= {$max} for player {$playerId}, got {$actual}"
);
```

### 19.5 Manager Delegation Template

```php
<?php

declare(strict_types=1);

namespace Bga\Games\YourGame\Managers;

use Bga\Games\YourGame\Core\Game;
use Bga\Games\YourGame\Models\YourEntity;
use Bga\Games\YourGame\Helpers\DB;

/**
 * Manager for a game subsystem.
 * Owns its DB table(s). Provides read + write API.
 */
class YourManager
{
    /**
     * Get an entity by ID. Throws if not found.
     */
    public static function get(int $id): YourEntity
    {
        $data = DB::getObject('SELECT * FROM your_table WHERE id = :id', ['id' => $id]);
        if ($data === null) {
            throw new \BgaSystemException("Entity {$id} not found");
        }
        return new YourEntity($data);
    }

    /**
     * Validate preconditions without mutating state.
     * Called by actions before execution.
     */
    public static function validateAction(int $entityId, int $playerId): void
    {
        $entity = self::get($entityId);
        if (!$entity->isOwnedBy($playerId)) {
            throw new \BgaUserException(
                \clienttranslate('You do not own this entity')
            );
        }
        if (!$entity->isActionable()) {
            throw new \BgaUserException(
                \clienttranslate('This entity cannot be used right now')
            );
        }
    }

    /**
     * Execute the action. Mutates state.
     * Called by actions after validation.
     */
    public static function execute(int $entityId, int $playerId): void
    {
        $oldLocation = self::getLocation($entityId);
        $newLocation = 'played';

        DB::update('your_table', ['location' => $newLocation], ['id' => $entityId]);
        DB::insert('log', [
            'player_id' => $playerId,
            'entity_id' => $entityId,
            'old_location' => $oldLocation,
            'new_location' => $newLocation,
        ]);
    }

    // ── Read methods ──

    public static function getAllForPlayer(int $playerId): array
    {
        return DB::getObjectList(
            'SELECT * FROM your_table WHERE player_id = :pid',
            ['pid' => $playerId]
        );
    }

    // ── Write methods ──

    public static function updateLocation(int $entityId, string $location): void
    {
        DB::update('your_table', ['location' => $location], ['id' => $entityId]);
    }
}
```

### 19.6 Engine Pattern Action Template (Agricola/ArkNova style)

```php
<?php

declare(strict_types=1);

namespace Bga\Games\YourGame\Actions;

use Bga\Games\YourGame\Core\Engine;
use Bga\Games\YourGame\Core\Notifications;
use Bga\Games\YourGame\Managers\Players;
use Bga\Games\YourGame\Managers\Resources;
use Bga\Games\YourGame\Models\Action;
use Bga\Games\YourGame\Models\Player;

class GainResources extends Action
{
    public function getState(): int
    {
        return StateIds::ST_GAIN;
    }

    public function isDoable(Player $player): bool
    {
        return true;  // Gaining is always doable
    }

    public function actGain(array $resources, int $playerId, bool $notify = true): void
    {
        $player = Players::get($playerId);
        $player->gainResources($resources);

        if ($notify) {
            Notifications::gainResources($player, $resources);
        }

        Engine::resolveAction([
            'playerId' => $playerId,
            'resources' => $resources,
        ]);
        Engine::proceed();
    }
}
```

### 19.7 Command Queue Action Template (Earth style)

```php
<?php

declare(strict_types=1);

namespace Bga\Games\YourGame\Actions;

use BX\Action\ActionCommandMgr;
use BX\Action\BaseActionCommand;
use BX\Action\ActionCommandNotifier;

class PlayCardCommand extends BaseActionCommand
{
    public function __construct(
        int $playerId,
        private int $cardId,
    ) {
        parent::__construct($playerId);
    }

    public function do(ActionCommandNotifier $notifier): void
    {
        $player = Players::get($this->playerId);
        $card = Cards::get($this->cardId);

        // Validate
        if (!$player->hasCard($this->cardId)) {
            throw new \BgaUserException('Card not in hand');
        }

        // Apply to private state
        Cards::moveCard($this->cardId, 'tableau', $this->playerId);
        Players::spendCoins($this->playerId, $card->getCost());

        // Notify privately
        $notifier->notifyPlayer('privateCardPlayed', [
            'cardId' => $this->cardId,
            'playerId' => $this->playerId,
        ]);
    }

    public function undo(ActionCommandNotifier $notifier): void
    {
        // Reverse the do() operation
        Cards::moveCard($this->cardId, 'hand', $this->playerId);
        $card = Cards::get($this->cardId);
        Players::addCoins($this->playerId, $card->getCost());

        $notifier->notifyPlayer('privateCardUndone', [
            'cardId' => $this->cardId,
            'playerId' => $this->playerId,
        ]);
    }

    public function reevaluate(array &$seenObjects): bool
    {
        // Check if the card is still available
        $card = Cards::get($this->cardId);
        if ($card->getLocation() !== 'hand' || $card->getOwnerId() !== $this->playerId) {
            return false;  // Command is no longer valid
        }
        return true;  // Command is still valid
    }
}
```

---

## 20. Checklists

### 20.1 Production Readiness Checklist

Before considering an action implementation production-ready:

- [ ] All five validation layers are present (framework, state, game-rule, domain, persistence)
- [ ] Validation completes before any DB mutation
- [ ] Notifications are sent after execution, before transition
- [ ] Every client-provided parameter is validated
- [ ] All exceptions are `\BgaUserException` (user error) or `\BgaSystemException` (internal error)
- [ ] The action does NOT contain raw SQL
- [ ] The action does NOT contain inline notification calls
- [ ] The action method returns a valid transition key (modern) or calls `nextState()` (legacy)
- [ ] The transition key exists in the current state's `transitions` map
- [ ] Zombie mode is implemented for non-`GAME` states
- [ ] `giveExtraTime()` is called on turn transitions
- [ ] Hidden information is not leaked in public notifications
- [ ] Error messages use `clienttranslate()` for i18n
- [ ] The action completes within acceptable time limits (<1s for simple, <5s for complex)
- [ ] Database queries are minimised (no N+1 patterns)
- [ ] Simultaneous-turn safety is addressed (locks or conditional updates)

### 20.2 Architecture Review Checklist

When reviewing action architecture:

- [ ] **Thin Action principle** — action method is <15 lines, delegates everything
- [ ] **Separation of concerns** — Game.php, State classes, Managers, Models have clear roles
- [ ] **Validation layering** — all five layers present, no validation-after-write
- [ ] **Notification ordering** — validate → execute → notify → transition
- [ ] **Error categorisation** — exceptions correctly distinguish user vs system errors
- [ ] **Manager ownership** — each Manager owns its tables, no cross-Manager writes
- [ ] **Transaction safety** — no external side effects, no partial commits
- [ ] **Undo readiness** — mutations are logged, action is reversible (or marked irreversible)
- [ ] **Parameter trust** — client-provided values are validated, framework magic params are trusted
- [ ] **Performance** — batch queries, minimise payload, no N+1 loops

### 20.3 Review Questions

Use these questions when reviewing any action implementation:

**Validation:**
1. What happens if the client sends a negative `$cardId`?
2. What happens if the client sends a `$cardId` that belongs to another player?
3. What happens if the player cannot afford the action?
4. What happens if the action is called from the wrong game state?
5. Is there any path where validation happens after mutation?

**Execution:**
6. Is all domain logic delegated to Managers?
7. Are there any raw SQL strings in the action method?
8. Could this action be made thinner?

**Notifications:**
9. Does every mutation have a corresponding notification?
10. Are private notifications sent only to the authorised player?
11. Is the notification before or after the transition?

**Errors:**
12. Is every user-visible error wrapped in `clienttranslate()`?
13. Is every system error a `\BgaSystemException`?
14. Are error messages informative without revealing hidden state?

**State machine:**
15. Does every action return a valid transition key?
16. Is the transition key in the current state's transitions map?
17. Does the action method have a `zombie()` implementation for the containing state?

**Concurrency:**
18. Could this action race with another player's action in a simultaneous phase?
19. If yes, is there a locking or conditional-update mechanism?

**Undo:**
20. Could this action be undone? Are the mutations logged?
21. If the action is irreversible, is it marked as such?
