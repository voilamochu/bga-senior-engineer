# BGA Game Flow Architecture — Engineering Standard

**Document purpose:** Define the canonical execution architecture of a BGA game from the moment a player clicks a button until control returns to another player. This is the foundational standard that all later engineering standards build upon.

**Applicability:** All new BGA game implementations. Existing projects should use this document as a reference when refactoring execution flow.

**Cross-references:**
- [notification-patterns.md](./notification-patterns.md) — notification payload design, public/private patterns, sequencing
- [reference-project-analysis.md](../foundation/reference-project-analysis.md) — project-specific ratings and architectural lineage
- [bga-developer-handbook.md](../foundation/bga-developer-handbook.md) — framework API reference

---

## Table of Contents

- [1. The Request-Response Model](#1-the-request-response-model)
- [2. Overall Execution Pipeline](#2-overall-execution-pipeline)
- [3. Separation of Responsibilities](#3-separation-of-responsibilities)
- [4. Command Lifecycle](#4-command-lifecycle)
- [5. Transaction Boundaries](#5-transaction-boundaries)
- [6. Server Authority](#6-server-authority)
- [7. State Ownership](#7-state-ownership)
- [8. Turn Ownership](#8-turn-ownership)
- [9. Multi-Step Action Execution](#9-multi-step-action-execution)
- [10. Error Handling](#10-error-handling)
- [11. Undo Interaction](#11-undo-interaction)
- [12. Reconnect Interaction](#12-reconnect-interaction)
- [13. Spectator Interaction](#13-spectator-interaction)
- [14. Simultaneous Turn Considerations](#14-simultaneous-turn-considerations)
- [15. Performance Implications](#15-performance-implications)
- [16. Common Anti-Patterns](#16-common-anti-patterns)
- [17. Reference Project Comparison](#17-reference-project-comparison)
- [18. Recommended Canonical Architecture](#18-recommended-canonical-architecture)

---

## 1. The Request-Response Model

Every BGA game interaction is a single HTTP request from the client to the server. There are no persistent connections, no WebSockets, and no server-push channels. The BGA platform wraps each request in a database transaction, executes the game logic, sends notifications, transitions the state machine, and commits. If any exception is thrown, everything rolls back.

This has profound implications:

- **Each request is stateless.** The PHP class instance is constructed fresh on every call. Nothing persists in memory between requests.
- **All state lives in the database.** Game state, player state, globals — everything must be read from the DB at the start of each request and written back before it ends.
- **Notifications are the only output.** The PHP method never returns a value to the caller. All data flows to the client through the notification pipeline.
- **The state transition happens last.** After all notifications are queued, the framework advances the state machine. Notifications sent after a state transition may not be delivered.
- **Exceptions are the only rollback mechanism.** Throwing an exception reverts all DB changes, cancels all queued notifications, and leaves the game state exactly as it was before the request.

### 1.1 Stateless Request Lifecycle

```
Client browser              BGA Platform                Game Server (PHP)
     │                            │                            │
     │── click → performAction ──►│                            │
     │                            │── construct Game.php ────►│
     │                            │                            │── read DB state
     │                            │                            │── execute action
     │                            │                            │── mutate DB
     │                            │                            │── send notifications
     │                            │                            │── return state transition
     │                            │◄───────────────────────────│
     │                            │── commit transaction ─────►│ (or rollback)
     │                            │── deliver notifications ──►│
     │◄── update UI ─────────────│                            │
     │                            │                            │
     │──── (next action) ───────►│                            │
     │                            │── construct Game.php ────►│ (fresh instance)
     │                            │                            │── read DB state (again)
     │                            │                            │── ...
```

### 1.2 Framework Versions

The reference projects span two framework generations:

| Aspect | Legacy (states.inc.php) | Modern (State classes) |
|---|---|---|
| State definition | `$machinestates` PHP array in `states.inc.php` | PHP classes in `modules/php/States/` |
| Action routing | `action.php` + `Game.php` methods | Autowired `act*` methods in State classes |
| Notifications | `$this->notifyAllPlayers()` | `$this->bga->notify->all()` |
| Action handler signature | `function actMyAction()` | `#[PossibleAction] function actMyAction(...$magicParameters)` |

The legacy approach is what Agricola, Ark Nova, and Arnak use. Earth uses a hybrid — it has a `states.inc.php` but drives flow through a custom engine. New projects should use the modern State classes approach.

---

## 2. Overall Execution Pipeline

### 2.1 The Canonical Pipeline

```
┌─────────────────────────────────────────────────────────────────┐
│  CLIENT BROWSER                                                  │
│  ┌──────────────┐    ┌──────────────────┐                        │
│  │ User clicks  │───►│ performAction()  │                        │
│  │ UI element   │    │ (AJAX to server) │                        │
│  └──────────────┘    └────────┬─────────┘                        │
└───────────────────────────────┼─────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│  BGA PLATFORM (Routing & Transaction Management)                 │
│  ┌────────────────┐    ┌───────────────┐    ┌─────────────────┐  │
│  │ Construct      │───►│ Begin DB      │───►│ Route to        │  │
│  │ Game.php       │    │ Transaction   │    │ State.action()  │  │
│  └────────────────┘    └───────────────┘    └────────┬────────┘  │
└───────────────────────────────────────────────────────┼──────────┘
                                                        │
                                                        ▼
┌─────────────────────────────────────────────────────────────────┐
│  GAME SERVER (PHP)                                               │
│                                                                  │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │  1. VALIDATE                                                 │ │
│  │     ┌──────────────┐    ┌──────────────┐                     │ │
│  │     │ checkAction() │───►│ Validate     │                     │ │
│  │     │ (framework)   │    │ game rules   │                     │ │
│  │     └──────────────┘    └──────┬───────┘                     │ │
│  │                                │                             │ │
│  │  2. EXECUTE                    │                             │ │
│  │                                ▼                             │ │
│  │     ┌─────────────────────────────────────┐                   │ │
│  │     │ Execute domain logic                │                   │ │
│  │     │ (Managers, Models, Domain services) │                   │ │
│  │     └─────────────────────────────────────┘                   │ │
│  │                                │                             │ │
│  │  3. PERSIST                    │                             │ │
│  │                                ▼                             │ │
│  │     ┌──────────────────────────────┐                         │ │
│  │     │ Write changes to database    │                         │ │
│  │     │ (within open transaction)    │                         │ │
│  │     └──────────────────────────────┘                         │ │
│  │                                │                             │ │
│  │  4. NOTIFY                     │                             │ │
│  │                                ▼                             │ │
│  │     ┌──────────────────────────────┐                         │ │
│  │     │ Send notifications to        │                         │ │
│  │     │ all/selected players         │                         │ │
│  │     └──────────────────────────────┘                         │ │
│  │                                │                             │ │
│  │  5. TRANSITION                 │                             │ │
│  │                                ▼                             │ │
│  │     ┌──────────────────────────────┐                         │ │
│  │     │ Return transition string     │                         │ │
│  │     │ → framework advances state   │                         │ │
│  │     └──────────────────────────────┘                         │ │
│  └─────────────────────────────────────────────────────────────┘ │
│                                                                  │
└───────────────────────────────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────┐
│  BGA PLATFORM                                                    │
│  ┌────────────────┐    ┌───────────────┐    ┌─────────────────┐  │
│  │ Commit DB      │    │ Store         │    │ Deliver         │  │
│  │ Transaction    │───►│ notifications │───►│ to clients      │  │
│  │ (or rollback)  │    │ in gamelog    │    │ (AJAX response) │  │
│  └────────────────┘    └───────────────┘    └────────┬────────┘  │
└───────────────────────────────────────────────────────┼──────────┘
                                                        │
                                                        ▼
┌─────────────────────────────────────────────────────────────────┐
│  CLIENT BROWSER                                                  │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │  Receive notification batch                                  │ │
│  │                                                              │ │
│  │  ┌─────────────────────────────────────────────────────────┐ │ │
│  │  │  Process sequentially:                                   │ │ │
│  │  │    notif_1(notif) → ... → notif_N(notif)                 │ │ │
│  │  │    Each handler updates DOM, animates, resolves promise  │ │ │
│  │  └─────────────────────────────────────────────────────────┘ │ │
│  │                                                              │ │
│  │  State change triggers onEnteringState / onUpdateActionBtns  │ │
│  └─────────────────────────────────────────────────────────────┘ │
└───────────────────────────────────────────────────────────────────┘
```

### 2.2 Step-by-Step

**Step 1 — User Interaction.** The player clicks a draggable element, a button, or a card. The click handler calls `this.bga.actions.performAction('actionName', { ...args })`. This sends an AJAX POST to the BGA server.

**Step 2 — Framework Routing.** The BGA platform constructs a fresh instance of the game's `Game.php` class. It begins a database transaction. It routes the request to the appropriate action method based on the current game state's `possibleactions` array or the State class's `#[PossibleAction]` attributes.

**Step 3 — Validation.** `checkAction('actionName')` verifies that the action is legal in the current state and that the requesting player is authorised to take it. The game logic then validates the action's parameters against the game rules. If validation fails, throw `\BgaUserException` for user-visible errors or `\BgaSystemException` for internal errors. The transaction rolls back, the client receives the error.

**Step 4 — Execution.** The domain logic runs. This may involve multiple subsystems: Managers update game state, Models compute results, the Engine (if used) resolves decision tree nodes. DB writes accumulate in the open transaction.

**Step 5 — Notification.** The game logic calls `$this->notifyAllPlayers(...)` or `$this->notifyPlayer(...)` for every visible consequence of the action. Notifications are queued by the platform but not yet delivered.

**Step 6 — State Transition.** The action method returns a transition string (e.g., `'nextPlayer'`). The framework looks up the transition in the current state's `transitions` map and advances the state machine to the target state. If the target state has an `action` method, that method is also executed within the same request.

**Step 7 — Commit.** The database transaction commits. All queued notifications are stored in the `gamelog` table and delivered to clients in the AJAX response.

**Step 8 — Client Processing.** The client receives the notification batch. Each notification is processed sequentially by its `notif_` handler. After all notifications are processed, if the state changed, `onEnteringState` is called on the new state.

---

## 3. Separation of Responsibilities

### 3.1 The Module Layout

A well-structured BGA project separates concerns across the following layers. This layout is derived from the Agricola/ArkNova lineage and adapted for modern framework conventions.

```
modules/
├── php/
│   ├── Game.php              Entry point. Extends Table. Thin coordinator.
│   ├── States/               One class per game state.
│   ├── Core/                 Framework-agnostic infrastructure.
│   │   ├── Engine.php        Flow engine (optional — for complex games).
│   │   ├── Globals.php       Typed global variable manager.
│   │   ├── Notifications.php Centralised notification factory.
│   │   ├── Preferences.php   User preference manager.
│   │   └── Stats.php         Statistics manager.
│   ├── Managers/             Domain logic for game subsystems.
│   │   ├── Players.php       Player lifecycle, turn order, elimination.
│   │   ├── Cards.php         Deck, hand, discard management.
│   │   ├── Board.php         Board state, positions, tiles.
│   │   ├── Scoring.php       Score calculation and tracking.
│   │   └── Actions.php       Atomic action registry.
│   ├── Models/               Data objects with behaviour.
│   │   ├── Player.php        Player state, resources, computed properties.
│   │   ├── Card.php          Card definition, state, UI representation.
│   │   └── Token.php         Generic game piece model.
│   └── Helpers/              Utility classes.
│       ├── DB.php            Database abstraction / query builder.
│       ├── Log.php           Undo logging.
│       ├── Collection.php    Enhanced array handling.
│       └── Utils.php         Pure functions.
│
└── js/
    ├── Game.js               Main client class. Thin coordinator.
    ├── Managers/             Client-side domain managers.
    │   ├── CardMgr.js        Card DOM creation, movement, animation.
    │   ├── BoardMgr.js       Board rendering, tile placement.
    │   ├── PlayerPanelMgr.js Player panel updates.
    │   └── ScoreMgr.js       Score display and animation.
    ├── States/               Client-side state handlers.
    │   └── PlayerTurn.js     One file per client-handled state.
    └── Core/
        └── Notifications.js  Notification handler registration + helpers.
```

### 3.2 Layer Responsibilities

| Layer | Responsibility | Can throw? | Can notify? | Can access DB? |
|---|---|---|---|---|
| **Game.php** | Route requests, coordinate high-level flow, implement framework-mandated methods (`setupNewGame`, `getAllDatas`, `zombie`). | Yes | Yes | Yes |
| **State classes** | Validate action parameters, execute state-specific logic, return transition string. | Yes | Yes | Yes |
| **Managers** | Encapsulate domain logic for a subsystem. Query and mutate its tables. | Yes | Yes (if no Notifications class) | Yes |
| **Models** | Represent a single entity. Compute derived values. Format for UI. | No (should not) | No | No (data passed in) |
| **Core/Engine** | Drive multi-step flow via a decision tree. Does NOT know about HTTP or the framework request cycle. | Yes | No (delegates to Notifications) | No (delegates to Globals) |
| **Core/Notifications** | Factory for notification methods. Handles i18n, arg resolution, delta detection. | No | Yes (wraps framework notify) | No |
| **Helpers** | Pure utility functions. Database abstraction. Undo logging. | N/A | N/A | Varies |
| **Client Managers** | Own a subsection of the DOM. Handle creation, movement, animation of elements. | N/A | N/A | N/A |

### 3.3 The "Thin Coordinator" Principle

Game.php and Game.js should be thin. They exist to coordinate, not to contain domain logic.

**Good** — Game.php delegates:
```php
// Game.php
public function actPlayCard(int $cardId, int $activePlayerId, array $args): string
{
    $this->checkAction('actPlayCard');
    $this->cards->playCard($cardId, $activePlayerId);
    $this->notifications->cardPlayed(Players::get($activePlayerId), $cardId);
    return 'cardPlayed';
}
```

**Bad** — Game.php does everything:
```php
// Game.php (anti-pattern)
public function actPlayCard(int $cardId, int $activePlayerId, array $args): string
{
    $this->checkAction('actPlayCard');
    $card = $this->getObjectFromDB("SELECT * FROM card WHERE card_id = $cardId");
    $player = $this->getObjectFromDB("SELECT * FROM player WHERE player_id = $activePlayerId");
    $this->DbQuery("UPDATE card SET card_location = 'play' WHERE card_id = $cardId");
    $this->DbQuery("UPDATE player SET player_score = player_score + 1 WHERE player_id = $activePlayerId");
    $this->notifyAllPlayers('cardPlayed', clienttranslate('${player_name} played a card'), [
        'player_id' => $activePlayerId,
        'player_name' => $player['player_name'],
        'card_id' => $cardId,
    ]);
    return 'cardPlayed';
}
```

---

## 4. Command Lifecycle

Every player action passes through five distinct phases. These phases happen within a single request within a single database transaction.

### 4.1 The Five Phases

```
                 VALIDATE ──→ EXECUTE ──→ PERSIST ──→ NOTIFY ──→ TRANSITION
                     │            │          │           │            │
                     │            │          │           │            │
                     ▼            ▼          ▼           ▼            ▼
              checkAction()   Domain     DB writes   Framework    Return
              Rule checks    logic      (in txn)    notify()     transition
                                                                    string
```

**Phase 1 — VALIDATE.** `checkAction('actionName')` is called automatically by the framework for State class actions (if the `#[PossibleAction]` attribute is used) or must be called manually in legacy action handlers. Game-specific validation follows: are the parameters valid? Does the player have the required resources? Is the target legal? Validation must be exhaustive. If validation fails, throw an exception. The transaction rolls back. No partial state is committed.

**Phase 2 — EXECUTE.** The domain logic runs. Managers and Models compute the effects of the action. This phase may call other methods, resolve engine nodes, or trigger card reactions. No DB mutations have been committed yet (they are buffered in the transaction), but reads see all changes made earlier in the same request.

**Phase 3 — PERSIST.** DB writes from the execution phase are accumulated in the open transaction. No explicit `COMMIT` is needed — the framework commits automatically if no exception is thrown.

**Phase 4 — NOTIFY.** Notification calls queue messages for delivery. The framework does NOT send them immediately. They are stored in an in-memory buffer and flushed to the `gamelog` table at commit time.

**Phase 5 — TRANSITION.** The action method returns a transition string. The framework advances the state machine. If the target state has an `action` method, it runs now — within the same request, before the transaction commits, before any notifications are delivered.

### 4.2 Return Value Contract

In the **modern framework** (State classes), actions return a string — the transition key. In the **legacy framework** (action methods on Game.php), actions return nothing and call `$this->gamestate->nextState('transition')`.

```php
// Modern (State class)
#[PossibleAction]
public function actPlayCard(int $cardId, int $activePlayerId, array $args): string
{
    // ... validate, execute, notify ...
    return 'cardPlayed'; // ← returns transition key
}

// Legacy (Game.php method)
public function actPlayCard(int $cardId, int $activePlayerId, array $args): string
{
    // ... validate, execute, notify ...
    $this->gamestate->nextState('cardPlayed'); // ← calls nextState directly
    return ''; // ← return value ignored
}
```

The Engine pattern (Agricola/ArkNova) complicates this: action methods do NOT return transitions. Instead, they call `Engine::resolve(...)` or `Engine::resolveAction(...)`, and the engine decides the next state after the tree is fully resolved. This is discussed in [Section 9](#9-multi-step-action-execution).

### 4.3 Single Responsibility per Action

Each action method should do exactly one thing. If a "play card" action triggers a draw, a gain, and a score update, that is still one action from the player's perspective — but the domain logic may call multiple managers. The notification should describe the complete effect, not a single step.

---

## 5. Transaction Boundaries

### 5.1 The Implicit Transaction

BGA wraps every request in an implicit database transaction. This is the single most important architectural constraint in the framework.

- The transaction begins before the action method is called.
- All DB writes within the action accumulate in the transaction buffer.
- If the action method returns normally (or calls `nextState` successfully), the framework commits the transaction.
- If any exception propagates out of the action method, the framework rolls back the transaction.
- Rollback reverts ALL DB changes and discards ALL queued notifications.

```php
public function actPlayCard(int $cardId, int $activePlayerId): string
{
    // Transaction is already active at this point

    $this->cards->moveCard($cardId, 'play', $activePlayerId);  // Buffered
    $this->players->addScore($activePlayerId, 1);               // Buffered

    // If the next line throws, BOTH the card move AND the score change are rolled back
    $this->checkPlayerHasNotWon($activePlayerId);

    $this->notifications->cardPlayed($activePlayerId, $cardId); // Queued in memory
    return 'cardPlayed';
    // Framework commits transaction, writes notifications to gamelog, delivers to clients
}
```

### 5.2 Implications

**Implication 1 — No partial commits.** If the logic fails at any point, the game state is as if the action never happened. This means validation MUST happen before mutation. Once mutation starts, any failure must be allowed to propagate as an exception.

**Implication 2 — State reads see uncommitted writes.** Within the same request, a later DB read will see the mutations made by an earlier write in the same request. This is standard MySQL transaction behaviour (default REPEATABLE READ isolation).

**Implication 3 — External side effects must be idempotent or deferred.** If your action sends an email, calls an external API, or writes to a file, it cannot be rolled back. Defer such operations. BGA does not support them, and they should be avoided entirely.

**Implication 4 — Long-running transactions are dangerous.** BGA imposes a PHP execution limit (typically 30 seconds). If your action takes too long, the connection is killed and the transaction is rolled back. Keep each action fast.

### 5.3 The "Commit Barrier" Pattern

When using the Engine pattern (Agricola/ArkNova), the Engine resolves nodes sequentially within a single request. Each node resolution is atomic at the application level but shares the same DB transaction. The Engine must therefore ensure that earlier node resolutions do not make later ones impossible — because the transaction cannot be partially committed.

The **Log/checkpoint** pattern addresses this for undo: checkpoints mark positions in the action log, but DB writes are still within the same transaction. The actual commit happens only when the Engine fully resolves and the action method returns.

Earth's command pattern avoids this entirely by queuing actions in a separate `action_command` table and committing them in a separate request. This is discussed in [Section 14](#14-simultaneous-turn-considerations).

---

## 6. Server Authority

### 6.1 The Golden Rule

**The server is the sole authority for all game state.** The client never mutates state. The client never decides game outcomes. The client never reveals hidden information except as instructed by the server.

### 6.2 What This Means in Practice

- **Validation happens on the server.** Client-side validation is for UX only. Always re-validate on the server.
- **Hidden information is computed on the server.** The client does not know the deck order, the contents of other players' hands, or future draws. The server sends only what each player is permitted to see.
- **Randomness is resolved on the server.** Shuffling, drawing, and random selection happen in PHP. The client receives the result.
- **Timing is controlled by the server.** The state machine determines when a player can act. The client cannot volunteer actions outside the current state's `possibleactions`.

### 6.3 Enforcement Mechanisms

The framework enforces server authority in three ways:

1. **`checkAction()`** — verifies that the action name is listed in the current state's `possibleactions`. If not, the exception `checkAction is not possible...` is thrown.
2. **Active player check** — for `ACTIVE_PLAYER` states, only the active player can submit actions. For `MULTIPLE_ACTIVE_PLAYER` states, only players in the active set can submit.
3. **Transaction isolation** — even if a client manages to send a malformed request, the transaction rolls back, and the game state is unchanged.

### 6.4 Why Client-Side Validation Is Not Enough

```js
// Client-side: fast UX feedback
if (this.player.coins < card.cost) {
    this.showError('Not enough coins');
    return;
}
this.bga.actions.performAction('actBuyCard', { cardId: card.id });
```

```php
// Server-side: the real validation (must match or be stricter)
#[PossibleAction]
public function actBuyCard(int $cardId, int $activePlayerId, array $args): string
{
    $player = $this->players->get($activePlayerId);
    $card = $this->cards->get($cardId);
    if ($player->getCoins() < $card->getCost()) {
        throw new \BgaUserException(clienttranslate('You do not have enough coins'));
    }
    // ... execute ...
}
```

The server may reject an action that passed client validation — for example, if another player's action in a simultaneous-turn phase depleted the card before this player's request arrived.

---

## 7. State Ownership

### 7.1 Who Owns What

Every piece of mutable game state must have a clear owner. State ownership determines who can read it, who can write it, and when.

| State | Owned By | Stored In | Access |
|---|---|---|---|
| Player resources | `Players` manager | `player` table columns | Owner reads/writes; others read public only |
| Player hand | Individual player | `card` table with `player_id` | Owner only |
| Board state | `Board` manager | Custom table | All players read |
| Deck composition | Game | `card` table with location | No one reads (opaque) |
| Score | `Scoring` manager | `player` table `player_score` | All players read |
| Global flags | `Globals` manager | `global_variables` table | All managers read |

### 7.2 Ownership Boundaries in Code

State ownership is enforced by convention, not by the framework. The reference projects use directory structure and namespace conventions:

```
Managers/Players.php    ← owns everything in the player table
Managers/Cards.php      ← owns everything in the card table
Managers/Board.php      ← owns everything in the board/location tables
```

A Manager should be the ONLY class that writes to its table. Other classes read through the Manager's public API:

```php
// CORRECT: reading through the owning manager
$player = $this->players->get($playerId);
$coins = $player->getCoins();

// WRONG: direct DB query from another class
$coins = $this->getUniqueValueFromDB(
    "SELECT coins FROM player WHERE player_id = $playerId"
);
```

### 7.3 The Engine State

When using the Engine pattern (Agricola/ArkNova), the Engine tree itself is state — it encodes the current position in a multi-step action flow. This state is serialised to a `global_variables` JSON column. Only the Engine reads or writes this state; Managers and Models are unaware of it.

---

## 8. Turn Ownership

### 8.1 Turn Models

BGA supports three turn models, configured in `gameinfos.jsonc` or `gameinfos.inc.php`:

| Model | `turnControl` | Description |
|---|---|---|
| Simple | `'simple'` | A plays, B plays, C plays, A plays... |
| Circuit | `'circuit'` | A plays and chooses who plays next |
| Complex | `'complex'` | Arbitrary active player management (default for non-trivial games) |

All reference projects use `'complex'` because they need fine-grained control over turn order.

### 8.2 Active Player vs. Current Player

The framework distinguishes two concepts:

```
active player = the player whose turn it is (per the state machine)
current player = the player who sent the current request (per the HTTP session)

These may differ. For example, in a MULTIPLE_ACTIVE_PLAYER state, every 
active player sends requests simultaneously. The active player remains 
the same across all requests; the current player changes per request.
```

| Method | Returns | When to Use |
|---|---|---|
| `$this->getActivePlayerId()` | The player id from the state machine | Validating whether the requesting player is authorised |
| `$this->getCurrentPlayerId()` | The player who sent the request (DO NOT USE in most cases) | ONLY when you specifically need to know who clicked the button (e.g., logging) |

In State class actions, magic parameters provide both:

```php
#[PossibleAction]
public function actPlayCard(
    int $cardId,
    int $activePlayerId,     // ← from state machine
    int $currentPlayerId     // ← who actually sent the request
): string {
    // For ACTIVE_PLAYER states, activePlayerId == currentPlayerId
    // For MULTIPLE_ACTIVE_PLAYER, they match only for the acting player
}
```

### 8.3 Turn Transitions

The framework provides helpers for common turn patterns:

```php
// Make the next player in natural order active
$this->activeNextPlayer();

// Make the previous player active
$this->activePrevPlayer();

// Make a specific player active
$this->gamestate->changeActivePlayer($playerId);
```

In the legacy approach, these calls must be followed by `$this->gamestate->nextState(...)`. In the modern approach, the state class action returns a transition string, and the framework handles activation changes declared in the transition.

---

## 9. Multi-Step Action Execution

### 9.1 The Problem

Many board game actions are not atomic. They involve:

1. A player chooses a card to play
2. The game asks: do you want to pay with resource A or resource B?
3. The player chooses
4. The card triggers a bonus: draw a card, choose to keep or discard
5. The player chooses again

Each of these steps requires a round-trip to the client. The state machine must pause, wait for input, then resume.

### 9.2 Approach A: Dedicated States (Arnak)

The simplest approach: one state per step.

```
SELECT_ACTION (player chooses main action)
  │
  ├──→ PLAY_CARD (player selects card)
  │       │
  │       └──→ PAY_COST (player chooses payment method)
  │               │
  │               └──→ AFTER_MAIN (player may take free actions)
  │                       │
  │                       └──→ NEXT_PLAYER
```

This is what Arnak does. The `SELECT_ACTION` state handles the main action choice. When the player chooses "play a card", it transitions to a state where the card is selected, then to a state where payment is chosen, then back to the free-action state `AFTER_MAIN`.

**Pros:** Simple, transparent, easy to debug. Every state has clear `possibleactions` and `transitions`.

**Cons:** The number of states grows linearly with the number of action types and sub-steps. Arnak handles this with ~30 states; a game with richer actions would need many more.

### 9.3 Approach B: Engine Tree (Agricola/ArkNova)

The Engine replaces explicit states with a decision tree. The tree is composed of nodes:

| Node Type | Behaviour |
|---|---|
| `SeqNode` | Execute children in sequence |
| `OrNode` | Execute one child (player chooses) |
| `XorNode` | Execute one child (mandatory choice) |
| `ParallelNode` | Execute all children (each player resolves independently) |
| `LeafNode` | Execute a single atomic action |

Instead of defining a state for each step, the game builds a tree at runtime. The Engine walks the tree, pausing at each decision point. Only a few generic states are needed in `states.inc.php`:

```
ST_RESOLVE_STACK    → change active player if needed
ST_RESOLVE_CHOICE   → player must choose between options
ST_GAIN             → atomic: gain resources (no player input)
ST_PAY              → atomic: pay resources (player chooses how)
ST_CONFIRM_TURN     → player confirms or restarts their turn
```

The Engine determines which state to jump to based on the tree's current node:

```php
// Engine::proceed() decides the next state dynamically:
if (node needs player choice) → jump to ST_RESOLVE_CHOICE
if (node is a Leaf action)    → jump to the action's specific state
                               (e.g., ST_GAIN, ST_PAY, ST_FENCING)
if (node is resolved)         → continue to next node
if (tree is fully resolved)   → jump to ST_CONFIRM_TURN
```

The tree is serialised to the DB after each node resolution:

```php
// Engine saves state after every step
Engine::resolveAction($args);
Globals::setEngine($tree->toArray());
```

**Pros:** Scales to arbitrarily complex action sequences. Cards can inject new nodes into the tree at runtime (via listeners). The number of states in `states.inc.php` stays small.

**Cons:** Complex to implement and debug. The indirection makes it harder to trace the execution path. Requires a custom Engine class.

### 9.4 Approach C: Command Queue (Earth)

Earth solves multi-step actions within a simultaneous-turn model using a command queue. Every player action creates a `BaseActionCommand` object:

```php
// A "plant a card" action is a command:
$cmd = new PlantCardActionCommand($playerId, $cardId);
$cmd->do($notifier);   // Apply to private state (client sees "pending" preview)
ActionCommandMgr::saveOne($cmd, $notifier);   // Save to action_command table
```

Commands are stored in a separate table and committed to the real game state in a later request:

```php
// When the player confirms their turn:
ActionCommandMgr::commit($playerId);
// This replays all queued commands through a public notifier,
// making them visible to all players.
```

**Pros:** Natural undo (each command has `do()` and `undo()`). Supports simultaneous play (each player queues commands independently). Clean separation between private preview and public commitment.

**Cons:** Highest complexity. Requires a command queue infrastructure, a private state machine per player, and a commit/reconcile phase. The `action_command` table must be maintained alongside real game state.

### 9.5 Recommendation

| Game type | Approach | Why |
|---|---|---|
| Simple turns (1 action → pass) | Dedicated states | Few steps, easy to map |
| Complex card-driven turns | Engine tree | Cards inject steps dynamically |
| Simultaneous turns | Command queue | Only approach that works |

For new projects that do NOT need simultaneous turns, start with **dedicated states**. Graduate to the **Engine tree** only when card interactions become too complex to model with explicit states. The command queue should be reserved exclusively for simultaneous-turn games.

---

## 10. Error Handling

### 10.1 Exception Hierarchy

| Exception | When to Use | User Visible? |
|---|---|---|
| `\BgaUserException` | The player did something wrong (invalid move, not enough resources, wrong game phase). The message should explain what they should do instead. | Yes — shown in a popup |
| `\BgaSystemException` | Something is broken (inconsistent state, missing data, failed assertion). The message is for the developer. | No — shown as "An internal error has occurred" |
| `\feException` | (Legacy) Use `\BgaSystemException` instead. | No |
| `\BgaVisibleSystemException` | A system error where the technical details should be shown. Rare. | Yes |

### 10.2 The Error Contract

```php
#[PossibleAction]
public function actPlayCard(int $cardId, int $activePlayerId, array $args): string
{
    // VALIDATE — may throw BgaUserException
    $this->game->checkAction('actPlayCard');                         // framework check
    $this->game->checkPlayerActive($activePlayerId);                 // (auto-done by framework for State classes)
    if (!$this->game->cards->playerHasCard($activePlayerId, $cardId)) {
        throw new \BgaUserException(clienttranslate('You do not have this card'));
    }
    if (!$this->game->cards->canPlayCard($cardId)) {
        throw new \BgaUserException(clienttranslate('This card cannot be played now'));
    }

    // EXECUTE — may throw BgaSystemException if invariants are violated
    try {
        $this->game->cards->playCard($cardId, $activePlayerId);
    } catch (\Exception $e) {
        throw new \BgaSystemException('Failed to play card: ' . $e->getMessage());
    }

    // NOTIFY
    $this->game->notifications->cardPlayed($activePlayerId, $cardId);

    // TRANSITION
    return 'cardPlayed';
}
```

### 10.3 Error Handling in the Engine Pattern

When using the Engine (Agricola/ArkNova), errors can occur at two levels:

1. **Inside a node's action** — the action method validates and throws `\BgaUserException`. The Engine does not catch it. The transaction rolls back.
2. **In the Engine's flow logic** — `Engine::proceed()` may find that no choices are available. The Engine transitions to `ST_IMPOSSIBLE_MANDATORY_ACTION`, giving the player a chance to restart their turn or exchange resources.

### 10.4 The `ST_IMPOSSIBLE_MANDATORY_ACTION` Escape Hatch

Both Agricola and ArkNova define a special state for when a mandatory action cannot be taken:

```php
ST_IMPOSSIBLE_MANDATORY_ACTION => [
    'name' => 'impossibleAction',
    'type' => 'activeplayer',
    'possibleactions' => [
        'actRestart',
        'actAbandonStuckAction',
    ],
],
```

This state is not defined in `states.inc.php` by default; it must be added explicitly. It is entered when:
- A mandatory action's preconditions are not met
- The player cannot pay the required cost
- A card effect prevents the action

The player can then restart their turn (undoing all steps) or, if the Engine supports it, abandon the stuck action.

---

## 11. Undo Interaction

This section summarises the undo architecture. For full details, see the undo patterns in [notification-patterns.md §12](./notification-patterns.md#12-undo-interactions).

### 11.1 Undo in the Request Pipeline

Undo is NOT a state transition. It is a separate action that:

1. Reads the undo log from the `log` or `action_command` table
2. Reverses each logged DB mutation
3. Cancels the corresponding gamelog packets
4. Sends a `clearTurn` notification to the client
5. Sends `refreshUI` + `refreshHand` to restore current state (or replays inverse notifications)

### 11.2 Where Undo Fits in the Pipeline

```
Normal action:
  VALIDATE → EXECUTE → PERSIST → NOTIFY → TRANSITION → COMMIT

Undo action:
  READ LOG → REVERSE DB → CANCEL GAMELOG → NOTIFY CLEAR → NOTIFY REFRESH → COMMIT
```

### 11.3 Interaction with Multi-Step Actions

**Engine pattern (Agricola/ArkNova):** The Engine tracks resolution progress. Undo can go back to the last checkpoint or to the start of the turn. Partial-turn confirmation marks a checkpoint beyond which undo cannot cross (unless all affected players agree).

**Command queue (Earth):** Undo pops commands from the queue in reverse order, calling `undo()` on each. Since commands have not been committed yet, there is no DB rollback — only the queue is modified.

**Dedicated states (Arnak):** Simple single-action undo. Each state handles its own undo logic.

For the engine and dedicated-states approaches, undo happens within the same request (the undo action reads the log and reverts). For the command queue, undo happens within the same turn (the queue is modified before commit).

---

## 12. Reconnect Interaction

This section summarises the reconnect architecture. For full details, see [notification-patterns.md §10](./notification-patterns.md#10-reconnect-considerations).

### 12.1 The Reconnection Pipeline

When a player refreshes the page or reconnects after a disconnect:

```
Client reloads page
  │
  ▼
Browser constructs Game.js instance
  │
  ▼
Client calls the framework's init endpoint
  │
  ▼
Server constructs Game.php (fresh instance)
  │
  ▼
Server calls getAllDatas(currentPlayerId)
  │  Returns complete game state snapshot
  │  (filtered to the calling player's perspective)
  ▼
Client calls setup(gamedatas)
  │  Rebuilds entire UI from snapshot
  ▼
Client registers notification handlers
  │
  ▼
Framework replays all gamelog packets from the last
confirmed state to the current state
  │  Each notification's notif_ handler runs,
  │  bringing the UI to the current state
  ▼
Client enters the current game state
  (onEnteringState called)
```

### 12.2 The Role of State Transitions in Replay

The replayed gamelog packets include state transition notifications. The client's notification queue processes them sequentially, meaning the client passes through every intermediate state the game has been through — even if the player was disconnected during those states. The final state after replay is the current game state.

### 12.3 The `_no_notify` Flag and Replay

States with `_no_notify = true` do not generate state-change notifications. During replay, the client skips these states entirely. This is correct because `_no_notify` states are transitional and have no visual representation.

### 12.4 The `refreshUI` Shortcut

As discussed in [notification-patterns.md §10.4](./notification-patterns.md#104-performance-concerns-during-replay), replaying hundreds of notifications during reconnection is inefficient. The `refreshUI` design pattern provides a snapshot:

```php
// After the replay, send a fresh-state snapshot
$this->notifications->refreshUI($this->getAllDatas($playerId));
$this->notifications->refreshHand($player, $player->getHand());
```

When the client receives `refreshUI`, it can skip intermediate notification handlers and rebuild directly from the snapshot. This is optional but strongly recommended for games with many state steps.

---

## 13. Spectator Interaction

This section summarises spectator handling. For full details, see [notification-patterns.md §11](./notification-patterns.md#11-spectator-considerations).

### 13.1 The Spectator Pipeline

```
Spectator loads the game page
  │
  ▼
Server calls getAllDatas(spectatorId)
  │  Returns ONLY public state
  │  (no hand data, no private selections)
  ▼
Spectator receives all public notifications
  │  Private notifications (notifyPlayer, _private) are filtered by the framework
  ▼
Spectator UI enters read-only mode
  │  No action buttons, no interactive elements
```

### 13.2 How the Pipeline Differs

| Aspect | Player | Spectator |
|---|---|---|
| `getAllDatas` | Includes private data for this player | Excludes all private data |
| Public notifications | Received | Received |
| Private notifications (`notifyPlayer`) | Received for this player | NOT received |
| `_private` payloads | Only own entries | NOT received |
| UI elements | Interactive (action buttons, draggables) | Read-only |
| State args (`_private` in state args) | Only own entries | NOT received |

### 13.3 Notification Pipeline with Spectators

```
Server sends:
  notifyAllPlayers('drawCards', ...)
    → Players receive: { n: 2, player_id: 123 }
    → Spectators receive: same
    → (no hidden data in public args)

  notifyPlayer(123, 'pDrawCards', ...)
    → Player 123 receives: { cards: [...details...] }
    → Spectators: NOT RECEIVED

  notifyAllPlayers('gainResources', ...)
    → Players receive: { resources: [...], _private: { 123: {...} } }
    → Spectators receive: { resources: [...] }  (_private removed by framework)
```

---

## 14. Simultaneous Turn Considerations

### 14.1 The Race Condition Problem

In simultaneous-turn games, multiple players send actions at roughly the same time. Each action is a separate request, processed in a separate transaction. The naive approach — reading state, validating, writing — fails because one player's committed action may invalidate another player's action that is still in flight.

```
Time  Player A                    Player B
 │    SELECT_ACTION state (both active)
 │      │                            │
 │      │── actPlantCard() ───────►  │
 │      │                            │
 │      │   Validate: OK             │
 │      │   Write: card moved        │
 │      │   Commit ─────────────────►│
 │      │                            │── actPlantCard() (same card?)
 │      │                            │
 │      │                            │   Validate: still OK (reads stale state)
 │      │                            │   Write: conflicts or duplicates
 │      │                            │   Commit ─→ ERROR or corrupt state
 ▼      ▼                            ▼
```

### 14.2 Solution A: Private State + Command Queue (Earth)

Earth solves this by decoupling action execution from commitment. Each player's actions are applied to a **private state** that only they can see. The private state is stored in a per-player state machine, and the actions are queued in an `action_command` table.

```
Phase 1 — Private (each player independently):
  Player A queues commands → applied to Player A's private state
  Player B queues commands → applied to Player B's private state
  (No conflict possible: each writes to their own state)

Phase 2 — Commitment (triggered by confirmation or phase end):
  Player A's commands are replayed through a public notifier
  Player B's commands are replayed through a public notifier
  Conflicts are detected during replay (the `reevaluate()` system)
```

The re-evaluation system checks each queued command against the current public state before replaying it:

```php
// When committing, each command is checked:
foreach ($commands as $command) {
    $reeval = $command->reevaluate($notifier, $currentPublicState);
    switch ($reeval) {
        case REEVALUATE_NO_CHANGE:
            $command->do($publicNotifier);  // Execute normally
            break;
        case REEVALUATE_UPDATE:
            $command->update($publicNotifier);  // Execute with adjusted parameters
            break;
        case REEVALUATE_DELETE:
            // Silently skip (action is no longer valid)
            break;
        case REEVALUATE_UNDO:
            // Notify the player that their action was undone
            break;
    }
}
```

### 14.3 Solution B: Framework MULTIPLE_ACTIVE_PLAYER + Private States

The BGA framework supports `MULTIPLE_ACTIVE_PLAYER` states where multiple players are active simultaneously. Within such a state, the framework provides `initializePrivateStateForAllActivePlayers()` and `nextPrivateState()` for per-player state machines.

This is the framework's built-in solution for simultaneous play. Earth's command queue builds on top of this but adds the undo/commit layer.

### 14.4 Recommendation

Use the framework's built-in `MULTIPLE_ACTIVE_PLAYER` + private states for simple simultaneous actions (e.g., all players discard a card). Use Earth's command queue pattern only when:
- Players need to perform multiple actions in their private phase before committing
- Individual actions must be undoable within the private phase
- Cross-player invalidation is a real concern

---

## 15. Performance Implications

### 15.1 Request Latency

Every player action results in an HTTP round-trip. The total perceived latency is:

```
Round-trip time = network_latency + server_processing + notification_processing
```

Network latency is beyond your control. Server processing depends on:
- Number of DB queries
- Complexity of game logic
- Size and number of notifications
- Engine tree resolution (if applicable)

Notification processing depends on:
- Number of notifications in the batch
- Size of each notification payload
- Client-side animation duration

### 15.2 DB Query Minimisation

Each request begins with a fresh PHP instance. All game state must be read from the DB at the start of the action. The reference projects handle this through:

- **Cached DB managers** (ArkNova's `CachedDB_Manager`) — in-memory cache within a single request
- **Globals objects** (Agricola/ArkNova/Earth) — typed global variables loaded once per request
- **Lazy loading** — models fetch data only when accessed

### 15.3 Notification Payload Size

Every notification is stored in the `gamelog` table for the lifetime of the game. Large payloads increase:
- Network transfer time for every client
- Database storage for archived games
- Archive replay time

See [notification-patterns.md §13](./notification-patterns.md#13-performance-considerations) for detailed guidance on payload minimisation.

### 15.4 Animation vs. State Updates

Animation-heavy notifications slow down the notification queue. The Earth project demonstrates a "fast mode" where animations are skipped for other players' actions. This is a player preference and should be honoured by notification handlers.

### 15.5 The `_no_notify` Flag

States that are automatically resolved without player input should use the `_no_notify` flag to suppress state-change notifications to the client. This avoids unnecessary client-side processing during state transitions.

---

## 16. Common Anti-Patterns

### 16.1 Sending Notifications After `nextState`

```php
// ANTI-PATTERN
$this->gamestate->nextState('nextTurn');
$this->notifyAllPlayers('lateUpdate', '', [...]);  // May not be delivered
```

Notifications sent after a state transition are not guaranteed to be delivered. Always send notifications before calling `nextState` or returning a transition string.

### 16.2 Mixing Validation and Mutation

```php
// ANTI-PATTERN
$this->players->spendCoins($playerId, $cost);   // Mutation
$this->players->transferCard($playerId, $cardId); // Mutation
if (!$this->players->canPay($playerId)) {         // Validation (too late!)
    throw new \BgaUserException('Cannot pay');
}
```

Once mutation starts, a validation failure rolls back all changes. This is wasteful and can cause subtle bugs if the rollback leaves caches inconsistent. Always validate fully before mutating.

### 16.3 Leaking Private Data in Public Args

```php
// ANTI-PATTERN — all players see each other's hands
$this->notifyAllPlayers('drawCards', '', [
    'player_id' => $playerId,
    'cards' => $this->cards->getHand($playerId),  // ← private!
]);
```

Use `_private` or `notifyPlayer` for hidden information.

### 16.4 Blocking the Notification Queue with Long Animations

```js
// ANTI-PATTERN — blocks all subsequent notifications for 5 seconds
notif_slowAnimation(notif) {
    return this.wait(5000);
}
```

Break long animations into steps, each with its own notification, or use the `simplePause` framework notification for short delays.

### 16.5 Business Logic in Game.php

```php
// ANTI-PATTERN — Game.php contains domain logic
public function actPlayCard(...)
{
    $card = $this->getObjectFromDB("SELECT * FROM card WHERE id = $cardId");
    $this->DbQuery("UPDATE player SET coins = coins - {$card['cost']} WHERE id = $playerId");
    $this->DbQuery("UPDATE card SET location = 'play' WHERE id = $cardId");
    // ... 50 more lines of inline SQL ...
}
```

Domain logic should live in Managers. Game.php should be a thin coordinator.

### 16.6 Reading State Outside a Transaction

```php
// ANTI-PATTERN — reads data that may have changed since the transaction started
public function actPlayCard(...)
{
    $playerCoins = $this->getUniqueValueFromDB(
        "SELECT coins FROM player WHERE player_id = $playerId FOR UPDATE"
    );
    // This SELECT ... FOR UPDATE is unnecessary in most cases
    // because the transaction already holds row-level locks implicitly
}
```

The implicit transaction already provides isolation. Explicit locking (`FOR UPDATE`, `LOCK TABLES`) is rarely needed and can cause deadlocks.

### 16.7 Writing Directly to `gamelog`

```php
// ANTI-PATTERN — never write to the gamelog table directly
$this->DbQuery("INSERT INTO gamelog ...");
```

The gamelog is managed by the framework. Always use `notifyAllPlayers` or `notifyPlayer`. Direct gamelog writes may conflict with the framework's internal management.

---

## 17. Reference Project Comparison

### 17.1 Architecture Comparison

| Aspect | Arnak | Agricola | Ark Nova | Earth |
|---|---|---|---|---|
| **Flow engine** | None (manual states) | Engine (Seq/Or/Xor/Parallel/Leaf) | Engine + FlowConvertor | Command queue |
| **Multi-step actions** | Dedicated states | Engine tree | Engine tree | Command queue |
| **Undo** | Single-action undo (`undo` state) | Log-based (checkpoint/step) | Log-based (same as Agricola) | Command undo (do/undo per command) |
| **Action routing** | `arnak.action.php` → Game.php methods | `agricola.action.php` → Game.php methods | `arknova.action.php` → Game.php methods | `earth.action.php` → Game.php methods |
| **State definition** | `states.inc.php` (array) | `states.inc.php` (array) | `states.inc.php` (array) | `states.inc.php` (array) |
| **DB abstraction** | Manual SQL in `sql_wrappers.php` | QueryBuilder + Pieces | QueryBuilder + DB_Model + CachedDB_Manager | Annotation-based ActionRow + DB layer |
| **Notifications** | Inline calls | Centralised class | Centralised class + delta cache | Command notifiers (4 types) |
| **Globals** | `getGameStateValue` / `setGameStateValue` | `Globals.php` (JSON column) | `Globals.php` (JSON column) | BGA `globals` + custom `game_state` table |
| **Simultaneous turns** | No | No | Break phase only | Full simultaneous |
| **Zombie handling** | Framework defaults | Engine `clearZombieNodes()` | Engine `clearZombieNodes()` | `ActionCommandMgr::zombieRemoveAll()` |

### 17.2 Flow Architecture Comparison

**Arnak** — Simplest flow. Each action is a state transition. The `AFTER_MAIN` state provides a free-action window. No abstraction layer between actions and states.

```
SELECT_ACTION → [PLAY_CARD | RESEARCH | DIG | ...] → AFTER_MAIN → NEXT_PLAYER
```

**Agricola** — Engine-driven flow. Actions are tree nodes, not states. The Engine resolves the tree automatically, inserting card reactions as needed. The state machine has ~40 states, but most are generic (ST_GAIN, ST_PAY, ST_RESOLVE_CHOICE).

```
Engine::setup(tree) → Engine::proceed()
  → resolves nodes one by one
  → each node maps to a generic state (ST_GAIN, ST_PAY, etc.)
  → card listeners inject new nodes (before<Action>, computeReplace<Action>)
  → when tree is done → ST_CONFIRM_TURN
```

**ArkNova** — Same Engine as Agricola, but extended with `FlowConvertor` that generates dynamic flow trees from card/sponsor bonuses. The state machine has ~83 states, reflecting the game's complexity.

```
Same as Agricola + FlowConvertor::convert(bonuses) → creates flow tree on the fly
```

**Earth** — Command queue flow. Actions are queued during private phase, committed during public phase. Each action command has `do()` and `undo()`. The `reevaluate()` system handles cross-player invalidation.

```
Private phase:
  player queues PlantCardCommand → do() on private notifier
  player queues GainSoilCommand → do() on private notifier
  player confirms → ActionCommandMgr::commit(playerId)

Public phase:
  each command replays through public notifier
  reevaluate() checks validity against current state
  conflicting commands are skipped or undone
```

### 17.3 State-to-Action Mapping

| Project | States | Notable States |
|---|---|---|
| Arnak | ~30 | `SELECT_ACTION`, `AFTER_MAIN`, `SITE_EFFECT`, `ART_EFFECT`, `RESEARCH_BONUS`, `BUY_ITEM`, `MUST_TRAVEL`, `MAY_EXILE` |
| Agricola | ~40 | `ST_CONFIRM_TURN`, `ST_RESOLVE_CHOICE`, `ST_GAIN`, `ST_PAY`, `ST_PLACE_FARMER`, `ST_FENCING`, `ST_SOW`, `ST_REORGANIZE`, `ST_SPECIAL_EFFECT` |
| ArkNova | ~83 | `ST_TURNACTION`, `ST_BUILD`, `ST_ANIMALS`, `ST_SPONSORS`, `ST_ASSOCIATION`, `ST_CARDS`, `ST_BREAK_MULTIACTIVE`, plus ~30 animal/sponsor effect states |
| Earth | ~60 | `STATE_MAIN_ACTION`, `STATE_ACTION_PLANT`, `STATE_ACTION_WATER`, `STATE_ACTION_GROW`, `STATE_ACTION_COMPOST`, plus per-action private states |

---

## 18. Recommended Canonical Architecture

### 18.1 Architecture Selection Decision Tree

```
Does your game have simultaneous turns?
  ├── YES → Use Earth-style command queue architecture
  │           (See §9.4, §14)
  │
  └── NO  → Does your game have complex card interactions
  │          where cards can modify the flow of an action?
  │           ├── YES → Use Engine-style tree architecture
  │           │           (See §9.3, reference Agricola/ArkNova)
  │           │
  │           └── NO  → Use dedicated states architecture
  │                       (See §9.2, reference Arnak)
  │
  Does your game have 100+ unique cards with triggered effects?
  ├── YES → Use individual card listener classes
  │           (See Agricola/ArkNova per-card class pattern)
  │
  └── NO  → Use data-driven card effects
              (See Arnak card_effects.php pattern)
```

### 18.2 Canonical Execution Flow

For a new BGA project with standard turn-based play and moderate card complexity, the recommended architecture is:

```
CLIENT                         SERVER
  │                              │
  │── performAction() ──────────►│
  │                              │
  │   ┌──────────────────────────┴──────────────┐
  │   │ Game.php (thin coordinator)              │
  │   │  ├── checkAction() (framework)           │
  │   │  ├── Validate via Manager API            │
  │   │  ├── Execute via Manager API             │
  │   │  ├── Notify via Notifications class      │
  │   │  └── Return transition string            │
  │   └──────────────────────────────────────────┘
  │                              │
  │   framework commits DB       │
  │   framework delivers notifs  │
  │                              │
  │◄── receive notifications ───│
  │                              │
  │   process sequentially:      │
  │     notif_* handlers         │
  │     onEnteringState          │
  │   (ready for next action)    │
```

### 18.3 Directory Layout

```
modules/
├── php/
│   ├── Game.php                  # Extends Table. Thin coordinator.
│   ├── States/                   # One class per game state (State classes pattern)
│   │   ├── PlayerTurn.php
│   │   ├── ResolveChoice.php
│   │   └── GameEnd.php
│   ├── Core/
│   │   ├── Globals.php           # Typed global variables
│   │   ├── Notifications.php     # Centralised notification factory
│   │   ├── Preferences.php       # User preference wrappers
│   │   └── Stats.php             # Statistic wrappers
│   ├── Managers/
│   │   ├── Players.php           # Player lifecycle and resources
│   │   ├── Cards.php             # Card operations
│   │   ├── Board.php             # Board state
│   │   └── Scoring.php           # Score calculation
│   ├── Models/
│   │   ├── Player.php            # Player data object
│   │   └── Card.php              # Card data object
│   └── Helpers/
│       ├── DB.php                # Query builder / DB abstraction
│       └── Utils.php             # Pure utility functions
│
└── js/
    ├── Game.js                   # Client entry point
    ├── Core/
    │   └── Notifications.js      # Notification handler setup
    └── Managers/
        ├── CardMgr.js            # Card DOM management
        ├── BoardMgr.js           # Board rendering
        └── PlayerPanelMgr.js     # Player panel updates
```

### 18.4 Action Template

```php
// modules/php/States/PlayerTurn.php
namespace Bga\Games\YourGame\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\YourGame\Core\Notifications;

class PlayerTurn extends GameState
{
    public function __construct(protected Game $game)
    {
        parent::__construct(
            id: 2,
            type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('${actplayer} must play a card or pass'),
            descriptionMyTurn: clienttranslate('${you} must play a card or pass'),
            transitions: [
                'playCard' => 3,
                'pass' => 4,
            ],
            updateGameProgression: true,
        );
    }

    #[PossibleAction]
    public function actPlayCard(int $cardId, int $activePlayerId, array $args): string
    {
        // 1. VALIDATE (framework checkAction is automatic for State classes)
        if (!$this->game->cards->playerHasCard($activePlayerId, $cardId)) {
            throw new \BgaUserException(clienttranslate('You do not have this card'));
        }

        // 2. EXECUTE
        $this->game->cards->playCard($cardId, $activePlayerId);
        $this->game->players->addScore($activePlayerId, 1);

        // 3. NOTIFY
        Notifications::cardPlayed(
            $this->game->players->get($activePlayerId),
            $this->game->cards->get($cardId)
        );

        // 4. TRANSITION
        return 'playCard';
    }

    public function getArgs(int $activePlayerId): array
    {
        return [
            'hand' => $this->game->cards->getHand($activePlayerId),
            '_private' => [
                $activePlayerId => [
                    'possibleCards' => $this->game->cards->getPlayableCards($activePlayerId),
                ],
            ],
        ];
    }
}
```

### 18.5 Manager Template

```php
// modules/php/Managers/Players.php
namespace Bga\Games\YourGame\Managers;

class Players extends \APP_DbObject
{
    public static function get(int $playerId): Player
    {
        // Fetch from DB or cache
    }

    public static function getAll(): array
    {
        // Return all players indexed by id
    }

    public static function addScore(int $playerId, int $points): void
    {
        self::DbQuery("UPDATE player SET player_score = player_score + $points WHERE player_id = $playerId");
    }
}
```

### 18.6 Notifications Template

```php
// modules/php/Core/Notifications.php
namespace Bga\Games\YourGame\Core;

class Notifications
{
    protected static function notifyAll(string $type, string $msg, array $args): void
    {
        self::updateArgs($args);
        Game::get()->notifyAllPlayers($type, $msg, $args);
    }

    protected static function notify($player, string $type, string $msg, array $args): void
    {
        $pId = is_int($player) ? $player : $player->getId();
        self::updateArgs($args);
        Game::get()->notifyPlayer($pId, $type, $msg, $args);
    }

    protected static function updateArgs(array &$args): void
    {
        if (isset($args['player'])) {
            $args['player_name'] = $args['player']->getName();
            $args['player_id'] = $args['player']->getId();
            unset($args['player']);
        }
        if (isset($args['card'])) {
            $args['i18n'][] = 'card_name';
            $args['card_name'] = $args['card']->getName();
        }
    }

    public static function cardPlayed(Player $player, Card $card): void
    {
        self::notifyAll(
            'cardPlayed',
            clienttranslate('${player_name} plays ${card_name}'),
            ['player' => $player, 'card' => $card]
        );
    }

    // ... one method per notification type
}
```

### 18.7 Client Action Template

```js
// modules/js/Game.js
class YourGameGame {
    // ...

    setupNotifications() {
        this.bga.notifications.setupPromiseNotifications();
    }

    notif_cardPlayed(notif) {
        const args = notif.args;
        this.cardMgr.moveCard(args.card_id, 'play');
        this.playerPanelMgr.updateScore(args.player_id, args.new_score);
    }

    // ...
}
```

---

## Appendix A: Key Principles Summary

| Principle | Description |
|---|---|
| **Server authority** | The server is the sole source of truth. The client never mutates state. |
| **Stateless requests** | Each request constructs a fresh PHP instance. Nothing persists in memory. |
| **Implicit transactions** | Every request runs in a DB transaction. Exceptions roll everything back. |
| **Notify before transition** | Send all notifications before returning a transition string. |
| **Thin coordinator** | Game.php and Game.js delegate to Managers and Models. |
| **Validate before mutating** | Complete all validation before writing to the DB. |
| **One Manager per table** | Each Manager owns one DB table or logical domain. |
| **Explicit state ownership** | Every piece of mutable state has a clear owner. |
| **Centralised notifications** | One Notifications class for all notification types. |
| **Idempotent handlers** | Client notification handlers must be safe to replay on reconnect. |
