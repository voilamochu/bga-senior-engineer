# BGA Client Synchronization Architecture — Engineering Standard

**Document purpose:** Define the complete synchronization model between the authoritative PHP server and the JavaScript client. Explain not only WHAT the framework does, but WHY reference projects structure synchronization the way they do.

**Applicability:** All new BGA game implementations. Existing projects should use this document when debugging synchronization bugs, optimizing notification payloads, or implementing reconnection logic.

**Cross-references:**
- [game-flow-architecture.md](./game-flow-architecture.md) — execution pipeline, transaction model, reconnect, spectator
- [notification-patterns.md](./notification-patterns.md) — notification lifecycle, payload design, public/private, undo, reconnect
- [state-machine-architecture.md](./state-machine-architecture.md) — state args, getArgs(), _private, _no_notify
- [domain-architecture.md](./domain-architecture.md) — notifications as presentation events, layering, dependency rules
- [persistence-architecture.md](./persistence-architecture.md) — DB as source of truth, entity lifecycle, caching
- [action-architecture.md](./action-architecture.md) — validation before mutate, action lifecycle
- [reference-project-analysis.md](../foundation/reference-project-analysis.md) — project-specific sync ratings and patterns
- [bga-developer-handbook.md](../foundation/bga-developer-handbook.md) — client-side API, notification registration

---

## Table of Contents

- [1. Synchronization Philosophy](#1-synchronization-philosophy)
- [2. Synchronization Lifecycle](#2-synchronization-lifecycle)
- [3. getAllDatas()](#3-getalldatas)
- [4. Notifications](#4-notifications)
- [5. Notification Lifecycle](#5-notification-lifecycle)
- [6. Reconnect](#6-reconnect)
- [7. Spectators](#7-spectators)
- [8. Incremental Synchronization](#8-incremental-synchronization)
- [9. Client Consistency](#9-client-consistency)
- [10. Synchronization Patterns](#10-synchronization-patterns)
- [11. Performance](#11-performance)
- [12. Anti-Patterns](#12-anti-patterns)
- [13. Templates](#13-templates)
- [14. Checklists](#14-checklists)

---

## 1. Synchronization Philosophy

### 1.1 The Five Principles

**Principle 1 — Server Authority.** The server is the sole source of truth for all game state. The client never mutates state, never decides outcomes, and never reveals hidden information except as instructed by the server. See [game-flow-architecture.md §6](./game-flow-architecture.md#6-server-authority).

This principle dictates synchronization architecture: every state change must originate on the server, flow through a notification, and be applied by the client. There is no client-initiated state mutation.

**Principle 2 — Client as Projection.** The client is a projection of server state, not an independent model. The client holds a cache of the last-known server state and updates it via notifications. If the client is ever out of sync, the correct response is to re-fetch from the server (reconnect), not to guess or derive.

```
SERVER:   DB (source of truth) → Notification → gamelog (archive)
                         │
                         ▼
CLIENT:   JS cache (projection) → DOM (rendered UI)
```

**Principle 3 — Stateless Requests.** Every HTTP request is independent. The server is constructed fresh each time. The client also reconstructs on page load — there is no persistent connection. Synchronization must survive complete client and server re-initialization.

**Principle 4 — Event-Driven Synchronization.** The client does not poll for changes. Every state update is pushed as a notification in the AJAX response of the action that triggered it. The client processes these notifications sequentially and updates the UI.

**Principle 5 — Reconnect Guarantees.** Any client can refresh the page at any time and reconstruct the exact current state. This is guaranteed by:
1. `getAllDatas()` — returns a complete snapshot of current state
2. Gamelog replay — replays all notifications since the last confirmed state
3. `refreshUI` pattern — optional snapshot shortcut to avoid replaying hundreds of notifications

### 1.2 The Synchronization Contract

```
The server guarantees:
  - All game state is in the DB at the start of every request
  - Every mutation generates at least one notification
  - Notifications are delivered in order
  - The gamelog contains a complete history of all notifications
  - getAllDatas() returns a correct snapshot from any player's perspective

The client guarantees:
  - Notification handlers are idempotent
  - The UI is rebuilt from getAllDatas() on load
  - Notification replay produces the same state as the current server state
  - No domain logic runs on the client
```

---

## 2. Synchronization Lifecycle

### 2.1 The Complete Lifecycle

```
┌──────────────────────────────────────────────────────────────────────────┐
│  BROWSER                                                                  │
│  ┌──────────────┐                                                         │
│  │ User clicks  │                                                         │
│  │ UI element   │                                                         │
│  └──────┬───────┘                                                         │
│         │                                                                  │
│         ▼                                                                  │
│  ┌──────────────────────────────────────────┐                              │
│  │ performAction('actX', {args})            │                              │
│  │ JS serialises args → AJAX POST           │                              │
│  └─────────────────────┬────────────────────┘                              │
└────────────────────────┼──────────────────────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  BGA PLATFORM                                                             │
│  ┌──────────────────────────────────────────────────────────────────────┐ │
│  │  BEGIN TRANSACTION                                                   │ │
│  │  Route to State class action method                                  │ │
│  └──────────────────────────────────────────────────────────────────────┘ │
│                         │                                                 │
└─────────────────────────┼─────────────────────────────────────────────────┘
                          │
                          ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  SERVER (PHP)                                                             │
│                                                                           │
│  ┌──────────────────────────────────────────────────────────────────────┐ │
│  │  1. VALIDATE   checkAction() + game rules + preconditions            │ │
│  │     ── on failure → throw → ROLLBACK → error response               │ │
│  │                                                                      │ │
│  │  2. EXECUTE    Domain logic (Managers, Models)                        │ │
│  │     ── reads DB, computes results                                    │ │
│  │                                                                      │ │
│  │  3. PERSIST    DB writes accumulate in transaction                    │ │
│  │                                                                      │ │
│  │  4. NOTIFY     Notifications::methods() → queued in memory            │ │
│  │     ── notifyAllPlayers / notifyPlayer / _private payloads           │ │
│  │                                                                      │ │
│  │  5. TRANSITION Return transition string → framework advances state   │ │
│  └──────────────────────────────────────────────────────────────────────┘ │
│                         │                                                 │
└─────────────────────────┼─────────────────────────────────────────────────┘
                          │
                          ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  BGA PLATFORM                                                             │
│  ┌──────────────────────────────────────────────────────────────────────┐ │
│  │  COMMIT TRANSACTION                                                  │ │
│  │  Notifications written to gamelog table                               │ │
│  │  Notifications serialised → HTTP response                             │ │
│  └──────────────────────────────────────────────────────────────────────┘ │
│                         │                                                 │
└─────────────────────────┼─────────────────────────────────────────────────┘
                          │
                          ▼
┌──────────────────────────────────────────────────────────────────────────┐
│  CLIENT (JavaScript)                                                      │
│                                                                           │
│  ┌──────────────────────────────────────────────────────────────────────┐ │
│  │  RECEPTION                                                          │ │
│  │  HTTP response received                                              │ │
│  │  Notification batch deserialised from JSON                           │ │
│  │                                                                      │ │
│  │  QUEUE PROCESSING                                                    │ │
│  │  For each notification in order:                                      │ │
│  │    ┌────────────────────────────────────────────────────────────┐    │ │
│  │    │  notif_X(notif)  ← handler auto-discovered by name         │    │ │
│  │    │    ├── Read args                                           │    │ │
│  │    │    ├── Update client state cache                           │    │ │
│  │    │    ├── Create/move/remove DOM elements                    │    │ │
│  │    │    └── Return Promise (if async animation) → await        │    │ │
│  │    └────────────────────────────────────────────────────────────┘    │ │
│  │    Wait for promise → next notification                              │ │
│  │                                                                      │ │
│  │  POST-PROCESSING                                                     │ │
│  │  If state changed:                                                    │ │
│  │    onEnteringState(stateName, args)  ← update UI for new state       │ │
│  │    onUpdateActionButtons(stateName, args) ← show/hide buttons       │ │
│  │                                                                      │ │
│  │  STABLE UI                                                           │ │
│  │  Client waits for next user interaction                              │ │
│  └──────────────────────────────────────────────────────────────────────┘ │
└──────────────────────────────────────────────────────────────────────────┘
```

### 2.2 Step Details

**Step 1 — Browser Action.** The user interacts with the UI. JavaScript calls `this.bga.actions.performAction('actX', args)`. The framework serialises the action name and arguments into an AJAX POST.

**Step 2 — AJAX Transport.** The POST travels to the BGA platform. The framework constructs a fresh Game.php instance and begins a DB transaction. If the player is not authorised or the action is not in the current state's possible actions, the request is rejected before any game logic runs.

**Step 3 — Server Validation.** `checkAction()` confirms the action is legal in the current state. Game-specific validation checks parameters, preconditions, and invariants. Any failure throws an exception → transaction rolls back → error response returns to client → client shows error popup.

**Step 4 — Server Execution.** Managers execute domain logic. DB writes accumulate in the open transaction. Models compute derived values.

**Step 5 — Server Persistence.** DB writes are buffered in the transaction. No commit yet.

**Step 6 — Server Notify.** The action calls centralized notification methods. Each call to `notifyAllPlayers` / `notifyPlayer` is queued in memory by the framework. Notifications are NOT sent yet.

**Step 7 — Server Transition.** The action returns a transition string. The framework advances the state machine. If the target state has an `action()` method (GAME state), it runs now.

**Step 8 — Commit.** The framework commits the transaction. All queued notifications are written to the `gamelog` table. The notification batch is serialised into the HTTP response.

**Step 9 — Client Reception.** The client receives the HTTP response containing the notification batch. Each notification has `type`, `args`, `move_id`, and metadata.

**Step 10 — Queue Processing.** The client processes notifications sequentially. Each `notif_` handler (auto-discovered by naming convention) runs. If the handler returns a Promise (for animations), the queue waits for it to resolve before processing the next notification.

**Step 11 — Post-Processing.** After all notifications, if the state machine advanced, `onEnteringState` is called with the new state name and args. `onUpdateActionButtons` updates available actions.

**Step 12 — Stable UI.** The client is now synchronised with the server. It waits for the next user interaction.

---

## 3. getAllDatas()

### 3.1 Purpose

`getAllDatas()` is the foundational synchronization method. It returns a complete snapshot of the game state from the calling player's perspective. It is called:
- When a player loads the page (initial setup)
- When a player refreshes (F5)
- When a spectator joins
- During reconnect (as the baseline for notification replay)

```php
public function getAllDatas(int $currentPlayerId = null): array
{
    $data = [
        'players' => $this->players->getAllForUi(),
        'cards' => $this->cards->getAll(),
        'board' => $this->board->getState(),
        'globals' => [
            'round' => Globals::getCurrentRound(),
            'phase' => Globals::getGamePhase(),
        ],
    ];

    // Private data — only for the requesting player
    if ($currentPlayerId !== null) {
        $data['hand'] = $this->cards->getHand($currentPlayerId);
        $data['privateChoices'] = $this->players->getPrivateChoices($currentPlayerId);
    }

    return $data;
}
```

### 3.2 Ownership

`getAllDatas()` lives in `Game.php`. It is the only method that reads from every Manager to assemble a cross-domain snapshot. Each Manager provides a dedicated `getAllForUi()` or `getState()` method:

```php
class Players extends Manager
{
    public function getAllForUi(): array
    {
        $players = $this->getAll();
        $result = [];
        foreach ($players as $player) {
            $result[$player->getId()] = [
                'id' => $player->getId(),
                'name' => $player->getName(),
                'color' => $player->getColor(),
                'score' => $player->getScore(),
                'resource_icons' => $player->getResourceIcons(),
                // Public info only — no hand data here
            ];
        }
        return $result;
    }
}
```

**Rule:** Each Manager provides a UI-facing read method. `getAllDatas` composes them. No Manager should be calling other Managers inside `getAllForUi()`.

### 3.3 Snapshot Design

A good `getAllDatas()` snapshot is:

**Complete** — it contains everything the client needs to render the full UI:
```php
public function getAllDatas(int $currentPlayerId = null): array
{
    return [
        'players' => $this->players->getAllForUi(),
        'cards' => $this->cards->getAll(),
        'board' => $this->board->getState(),
        'availableActions' => $this->getAvailableActions(),
        'globals' => $this->globals->getAll(),
    ];
}
```

**Minimal** — it does not include data the client can derive:
```php
// GOOD: send card IDs and locations, not full card definitions
// (card definitions are loaded from static material)
$data['cards'] = array_map(fn($c) => [
    'id' => $c->getId(),
    'location' => $c->getLocation(),
    'locationArg' => $c->getLocationArg(),
], $cards);
```

**Filtered** — it respects the calling player's visibility:
```php
if ($currentPlayerId !== null) {
    $data['hand'] = $this->cards->getHand($currentPlayerId);
}
// Spectators and other players do NOT get hand data
```

### 3.4 Private Data

Private data in `getAllDatas()` must be clearly separated from public data:

```php
// CORRECT: public data first, private data appended conditionally
public function getAllDatas(int $currentPlayerId = null): array
{
    // Public — sent to everyone
    $data = [
        'players' => $this->getPublicPlayers(),
        'board' => $this->board->getState(),
    ];

    // Private — sent only to the requesting player
    if ($currentPlayerId !== null) {
        $data['hand'] = $this->cards->getHand($currentPlayerId);
    }

    return $data;
}
```

**Never** include `_private` in `getAllDatas()` — the `_private` mechanism is for notification args and state args only. For `getAllDatas()`, conditionally include private data based on `$currentPlayerId`.

### 3.5 Spectator Handling

When `$currentPlayerId` is a spectator (or null for anonymous spectators), `getAllDatas()` must return only public data:

```php
public function getAllDatas(int $currentPlayerId = null): array
{
    // Always include public state
    $data = [
        'players' => $this->players->getPublicState(),
        'cards' => $this->cards->getPublicState(),
        'board' => $this->board->getState(),
    ];

    // Only include private data for non-spectator players
    if ($currentPlayerId !== null && !$this->isSpectator($currentPlayerId)) {
        $data['hand'] = $this->cards->getHand($currentPlayerId);
    }

    return $data;
}
```

The framework does NOT filter `$currentPlayerId` for spectators — you must handle it in `getAllDatas()`. See [notification-patterns.md §11](./notification-patterns.md#11-spectator-considerations).

### 3.6 Performance

`getAllDatas()` runs on every page load and every reconnect. It must be fast:

```php
// GOOD: single queries per domain, batched
$data['cards'] = $this->cards->getAll();           // One query
$data['players'] = $this->players->getAllForUi();  // One query
$data['board'] = $this->board->getState();         // One query

// BAD: N+1 queries inside a loop
foreach ($players as $player) {
    $data['hand_' . $player->getId()] = $this->cards->getHand($player->getId());
}
// → N queries where 1 would suffice
```

See [persistence-architecture.md §12.2](./persistence-architecture.md#122-batch-reads) for batch query patterns.

### 3.7 Filtering

`getAllDatas()` must filter data based on the caller's perspective:

| Caller | What They See |
|---|---|
| Active player | Public state + their own hand + their private choices |
| Other player | Public state + their own hand only |
| Spectator | Public state only (no hands) |

### 3.8 Incremental Evolution

`getAllDatas()` typically grows over the development lifecycle:

```
Phase 1 (MVP):       players, cards
Phase 2 (features):  +board, +globals, +availableActions
Phase 3 (polish):    +notification cache priming, +filtered card data
Phase 4 (launch):    +spectator filtering, +private player state
```

Start with the minimum needed to render the UI. Add fields only when the client requires them.

---

## 4. Notifications

### 4.1 Purpose in Synchronization

Notifications are the incremental synchronization mechanism. Every state change that occurs on the server must be communicated to the client via a notification. See [notification-patterns.md §1](./notification-patterns.md#1-purpose-of-notifications) for the four purposes.

### 4.2 Ordering

Notifications are ordered by the server and delivered to each client in the exact order they were sent. The client processes them sequentially.

```php
// Server sends in this order:
$this->notifyAllPlayers('resourceSpent', ...);    // 1
$this->notifyAllPlayers('cardPlayed', ...);       // 2
$this->notifyAllPlayers('scoreUpdated', ...);     // 3

// Client processes: resourceSpent → cardPlayed → scoreUpdated
// Never: cardPlayed → resourceSpent → scoreUpdated
```

**Why ordering matters:** Each notification may depend on the previous one. If `cardPlayed` moves a card and `scoreUpdated` depends on the new score, they must be sent in that order.

### 4.3 Atomicity

All notifications in a single request are delivered together or not at all. If the transaction rolls back, no notifications are delivered.

```
Request succeeds:  notif_A, notif_B, notif_C  →  all three delivered
Request fails:     (nothing — transaction rolled back)
```

This atomicity is guaranteed by the implicit transaction model. See [game-flow-architecture.md §5.1](./game-flow-architecture.md#51-the-implicit-transaction).

### 4.4 Grouping

Related state changes should be grouped into a single notification when possible:

```php
// BAD: three separate notifications for one logical action
$this->notifyAllPlayers('spentResources', '', ['player_id' => $pid, ...]);
$this->notifyAllPlayers('gainedResources', '', ['player_id' => $pid, ...]);
$this->notifyAllPlayers('updatedScore', '', ['player_id' => $pid, ...]);

// GOOD: one notification describing the complete effect
$this->notifications->tradeExecuted($player, $spent, $gained, $newScore);
```

However, when multiple distinct animations are needed on the client, separate notifications are appropriate:

```php
// Acceptable separation for distinct visual effects:
$this->notifications->cardMoved($cardId, 'from' => 'hand', 'to' => 'play');
$this->notifications->resourcesChanged($playerId, $delta);
```

### 4.5 Payload Philosophy

Notification payloads should follow three rules:

**Rule 1 — Include enough for the client to update.** The client should not need to compute game state from notification arguments:

```php
// GOOD: includes the final counter value
$this->notifyAllPlayers('updateScore', '', [
    'player_id' => $playerId,
    'score' => $newScore,          // ← final value
]);

// BAD: requires client to track state
$this->notifyAllPlayers('updateScore', '', [
    'player_id' => $playerId,
    'delta' => 5,                  // ← requires client to maintain accumulator
]);
```

**Rule 2 — Include identifiers for DOM targeting.** The client should be able to find the affected element:

```php
// GOOD: includes the element ID
'card_id' => $cardId
```

**Rule 3 — Include the minimum data required.** Do not send full DB rows — send only the fields the client needs:

```php
// GOOD: filtered card data
'card' => [
    'id' => $card->getId(),
    'location' => $card->getLocation(),
]

// BAD: full DB row
'card' => $cardRow  // may contain internal fields
```

### 4.6 Delta vs Snapshot

Two strategies for updating client state:

| Strategy | Payload | Use Case |
|---|---|---|
| **Delta** | What changed | Frequent updates, small changes |
| **Snapshot** | Complete current state | After undo, reconnect, significant state changes |

**Delta example:**

```php
$this->notifications->scoreChanged($playerId, +5);
// Client: this.playerBoards[playerId].score += 5;
```

**Snapshot example:**

```php
$this->notifications->refreshUI($snapshot);
// Client: this.rebuildUI(notif.args.datas);
```

ArkNova's notification delta system (`$listeners` / `updateIfNeeded`) attaches only changed fields to every notification. This is the most efficient approach for rich state — see [notification-patterns.md §13.3](./notification-patterns.md#133-delta-only-updates-arknova-pattern).

**Trade-off:** Deltas reduce payload size but require the client to maintain accumulative state. Snapshots are larger but simplify the client (just rebuild).

### 4.7 Public vs Private

| Method | Visibility | Example |
|---|---|---|
| `notifyAllPlayers` | All players + spectators | `cardPlayed`, `scoreUpdated` |
| `notifyPlayer` | Single player only | `pDrawCards` (private hand) |
| `_private` key | Per-player within a single notification | `gainResources` with hidden hand |

**Recommendation:** Use `_private` for new projects. Use separate `notifyPlayer` calls only when the private notification needs a different handler on the client.

See [notification-patterns.md §3](./notification-patterns.md#3-public-vs-private-notifications) for the full comparison.

### 4.8 Transient vs Persistent Notifications

| Type | Stored in Gamelog? | Survives Replay? | Example |
|---|---|---|---|
| **Persistent** | Yes | Yes | `cardPlayed`, `gainResources` |
| **Transient** | No (empty log string) | No | `simplePause`, `particleEffect` |

Transient notifications are used for visual effects that should not appear in the game log:

```php
// Persistent: appears in game log
$this->notifyAllPlayers('gainResources',
    clienttranslate('${player_name} gains ${resources_desc}'),
    [...]
);

// Transient: visual-only, not in game log
$this->notifyAllPlayers('simplePause', '', ['time' => 500]);
```

Transient notifications are skipped during reconnect replay. This is correct — the visual effect is no longer relevant.

---

## 5. Notification Lifecycle

### 5.1 Lifecycle Diagram

```
                    ┌──────────────────────┐
                    │  SERVER CREATION      │
                    │  Notifications::method │
                    │  calls framework API  │
                    └──────────┬───────────┘
                               │
                               ▼
                    ┌──────────────────────┐
                    │  FRAMEWORK QUEUE      │
                    │  In-memory buffer     │
                    │  (not yet sent)       │
                    └──────────┬───────────┘
                               │
                    ┌──────────▼───────────┐
                    │  TRANSACTION COMMIT   │
                    │  Framework commits DB │
                    │  Writes to gamelog    │
                    └──────────┬───────────┘
                               │
                    ┌──────────▼───────────┐
                    │  TRANSPORT            │
                    │  Serialised to JSON   │
                    │  Sent in HTTP response│
                    └──────────┬───────────┘
                               │
                    ┌──────────▼───────────┐
                    │  CLIENT RECEPTION    │
                    │  Deserialised from   │
                    │  AJAX response       │
                    └──────────┬───────────┘
                               │
                    ┌──────────▼───────────┐
                    │  NOTIFICATION QUEUE  │
                    │  Sequential dispatch │
                    │  to notif_ handlers  │
                    └──────────┬───────────┘
                               │
                    ┌──────────▼───────────┐
                    │  HANDLER EXECUTION   │
                    │  Update state cache  │
                    │  Update DOM          │
                    │  Return Promise      │
                    └──────────┬───────────┘
                               │
                    ┌──────────▼───────────┐
                    │  ANIMATION            │
                    │  (if handler async)   │
                    │  Promise resolves     │
                    └──────────┬───────────┘
                               │
                    ┌──────────▼───────────┐
                    │  FINAL UI STATE      │
                    │  Client synchronised │
                    │  Ready for input     │
                    └──────────────────────┘
```

### 5.2 Stage Details

**Stage 1 — Server Creation.** The domain logic calls a method on the centralized Notifications class. The method constructs the payload, calls `notifyAllPlayers` or `notifyPlayer`, and the framework enqueues the notification:

```php
// In Notifications class
public static function cardPlayed($player, $cardId, $cardName): void
{
    self::notifyAll('cardPlayed', clienttranslate('${player_name} plays ${card_name}'), [
        'player' => $player,
        'card_id' => $cardId,
        'card_name' => $cardName,
        'i18n' => ['card_name'],
    ]);
}
```

**Stage 2 — Framework Queue.** The notification is stored in the framework's in-memory buffer. No DB writes yet. The buffer accumulates all notifications for the current request.

**Stage 3 — Transaction Commit.** If the action method returns normally, the framework commits the DB transaction. All queued notifications are written to the `gamelog` table.

**Stage 4 — Transport.** The notification batch is serialised to JSON and included in the HTTP response. Each notification becomes an object with `type`, `args`, `move_id`, and `uid` fields.

**Stage 5 — Client Reception.** The client deserialises the response. The notification batch is available as an array on the AJAX response object.

**Stage 6 — Notification Queue.** The framework dispatches notifications sequentially. Each `notif_` handler is called in order. The queue waits for each handler's returned Promise to resolve.

```js
// Client: sequential dispatch
for (const notif of notificationBatch) {
    const handler = this['notif_' + notif.type];
    if (handler) {
        await handler.call(this, notif);
    }
}
```

**Stage 7 — Handler Execution.** The handler updates the client's state cache and DOM:

```js
notif_cardPlayed(notif) {
    const args = notif.args;
    // Update client cache
    this.cards[args.card_id].location = 'play';
    // Update DOM
    const cardEl = this.cardMgr.getElementById(args.card_id);
    this.cardMgr.moveToLocation(cardEl, 'play');
}
```

**Stage 8 — Animation.** If the handler performs an animated transition (e.g., moving a card across the board), it returns a Promise. The queue waits for the animation to complete.

```js
async notif_cardPlayed(notif) {
    await this.cardMgr.animateToLocation(args.card_id, 'play');
    // DOM is now updated
}
```

**Stage 9 — Final UI State.** All notifications processed. The client is synchronised. `onEnteringState` fires if the state changed.

### 5.3 Notification Queue Model

```
Time  →  notif_A ──→ [handler runs] ──→ notif_B ──→ [handler runs] ──→ notif_C ──→ ...
              │              │               │              │               │
              ▼              ▼               ▼              ▼               ▼
           queued        animating        queued        animating        running
```

Each notification blocks the queue until its handler completes. This ensures deterministic ordering.

### 5.4 Private Notification Flow

```
SENDER (PHP)                          RECEIVERS
────────────                          ──────────

notifyAllPlayers('cardPlayed',...)
  │                                    Player A: receives notif_cardPlayed
  ├──→ all players                     Player B: receives notif_cardPlayed
  │                                    Spectator: receives notif_cardPlayed
  │
notifyPlayer(123, 'pDrawCards',...)
  │                                    Player 123: receives notif_pDrawCards
  └──→ player 123 only                 Player 456: NOT RECEIVED
                                       Spectator: NOT RECEIVED

notifyAllPlayers('drawCards',...)
  │          with _private: {
  │            123: { cards: [...] },
  │          }
  ├──→ all players                     Player 123: receives { player_id, n, cards: [...] }
  │                                    Player 456: receives { player_id, n }
  │                                    Spectator: receives { player_id, n }
```

---

## 6. Reconnect

### 6.1 How Reconnect Works

When a player refreshes the page or reconnects after a disconnect:

```
1. Page loads → Game.js constructor runs
2. Framework calls setup(gamedatas) ← from getAllDatas()
3. Client registers notification handlers
4. Framework replays gamelog from last confirmed state to current state
5. Client processes replayed notifications sequentially
6. Client enters current game state (onEnteringState)
7. (Optional) refreshUI snapshot shortcut
```

### 6.2 How getAllDatas Rebuilds State

`getAllDatas()` returns the complete current state from the calling player's perspective. The client's `setup()` method receives this data and rebuilds the entire UI:

```js
setup(gamedatas) {
    // Rebuild from complete snapshot
    this.players = gamedatas.players;
    this.cards = gamedatas.cards;
    this.board = gamedatas.board;
    this.hand = gamedatas.hand;  // If available (private)

    // Render
    this.renderPlayerBoards();
    this.renderBoard();
    this.renderHand();
}
```

### 6.3 Why Notifications Are Not Enough

Notifications alone cannot restore state because:
1. The gamelog may be truncated (BGA archives old games)
2. The client may have been disconnected for a long time (hundreds of notifications)
3. The client may have lost its cache entirely (page refresh)

`getAllDatas()` provides the baseline. Notifications replay brings the baseline forward to the current state.

```
getAllDatas() snapshot:  state at time T
                          │
                          ▼
Replayed notifications:   T → T+1 → T+2 → ... → current time
                          │
                          ▼
Client state:            matches current server state
```

### 6.4 Reconstruction Strategy

```php
// Server-side reconstruction support
public function getAllDatas(int $currentPlayerId = null): array
{
    // Core state — always needed
    $data = [
        'players' => $this->players->getAllForUi(),
        'cards' => $this->cards->getAll(),
        'board' => $this->board->getState(),
    ];

    // Private state per player
    if ($currentPlayerId !== null) {
        $data['hand'] = $this->cards->getHand($currentPlayerId);
        $data['privateChoices'] = $this->players->getPrivateChoices($currentPlayerId);
    }

    // Cache priming data — card definitions if dynamically generated
    if ($this->cards->areCardsDynamic()) {
        $data['cardDefinitions'] = $this->cards->getDefinitions();
    }

    return $data;
}
```

### 6.5 Cache Rebuilding

The client must rebuild all caches from `getAllDatas()`:

```js
setup(gamedatas) {
    // Rebuild card cache
    this.cardMgr.setCards(gamedatas.cards);

    // Rebuild player cache
    this.playerMgr.setPlayers(gamedatas.players);

    // Rebuild board state
    this.boardMgr.setState(gamedatas.board);

    // Prime card definition cache
    if (gamedatas.cardDefinitions) {
        this.cardDefinitionCache = gamedatas.cardDefinitions;
    }

    // Rebuild private state (if available)
    if (gamedatas.hand) {
        this.handMgr.setHand(gamedatas.hand);
    }
}
```

**Rules for cache rebuilding:**
- Every cache must be fully rebuildable from `getAllDatas()`
- No cache should depend on notification history
- The rebuild must produce the same state as notification replay would

### 6.6 The refreshUI Shortcut

For games with many intermediate states, replaying all gamelog notifications during reconnect is slow. The `refreshUI` pattern skips replay:

```php
// During reconnect, after getAllDatas() setup:
// Send a snapshot that the client uses as the current state
$this->notifications->refreshUI($this->getAllDatas($playerId));
$this->notifications->refreshHand($player, $player->getHand());
```

```js
// Client: when refreshUI is received during replay, skip all prior notifications
notif_refreshUI(notif) {
    this.rebuildUI(notif.args.datas);
    // Signal framework: skip remaining notification replay
}
```

See [notification-patterns.md §9](./notification-patterns.md#9-ui-refresh-patterns) for the full implementation.

### 6.7 Reconnect Sequence Diagram

```
Client                           Server
──────                           ──────

Page load (F5)
  │
  ├── construct Game.js
  │
  ├── getAllDatas() ──────────►  Server reads DB
  │                                │
  │◄── complete snapshot ──────  Returns: players, cards, board, hand
  │
  ├── setup(gamedatas)
  │     ├── render board
  │     ├── render players
  │     └── render hand
  │
  ├── register notif_ handlers
  │
  ├── replay gamelog ──────────►  Framework reads gamelog
  │                                │
  │◄── notifications ──────────  Returns: notifications since last state
  │     (processed sequentially)
  │
  ├── onEnteringState(state)
  │
  └── READY
```

---

## 7. Spectators

### 7.1 Null Player

Spectators have no player ID in the traditional sense. The framework identifies them as spectators:

```js
if (this.bga.players.isCurrentPlayerSpectator()) {
    // Spectator-specific handling
}
```

On the server side, `getAllDatas()` receives the spectator's internal ID or null. The method must detect this and exclude private data:

```php
public function getAllDatas(int $currentPlayerId = null): array
{
    $isSpectator = $currentPlayerId === null
        || $this->isSpectator($currentPlayerId);

    $data = [
        'players' => $this->players->getPublicState(),
        'cards' => $this->cards->getPublicState(),
        'board' => $this->board->getState(),
        'isSpectator' => true,  // ← allow client to switch to read-only mode
    ];

    if (!$isSpectator) {
        $data['hand'] = $this->cards->getHand($currentPlayerId);
    }

    return $data;
}
```

### 7.2 Private Information

Private information is filtered at two levels:

**Server level** — `getAllDatas()` does not include private data for spectators.

**Framework level** — `notifyPlayer()` calls are never delivered to spectators. The `_private` key in notification args is stripped for spectators:

```php
// All players see: { player_id: 123, n: 2 }
// Player 123 also sees: _private: { 123: { cards: [...] } }
// Spectators see: { player_id: 123, n: 2 }  (_private stripped)
```

### 7.3 Visibility Rules

| Data | Player | Spectator |
|---|---|---|
| Board state | Visible | Visible |
| Player scores | Visible | Visible |
| Public card positions | Visible | Visible |
| Private hand | Own only | Hidden |
| Private choices | Own only | Hidden |
| Undo buttons | Visible (if eligible) | Hidden |
| Action buttons | Visible (if active) | Hidden |

### 7.4 Shared State

Spectators share the public state projection. They see all `notifyAllPlayers` notifications. The client code must guard private handlers:

```js
notif_pDrawCards(notif) {
    if (this.bga.players.isCurrentPlayerSpectator()) return;
    this.updateHand(notif.args.cards);
}
```

### 7.5 Late Joins

A spectator joining late follows the same reconnect path as a player:
1. `getAllDatas()` returns public state
2. Gamelog replay brings them to the current state
3. All public notifications from game start are replayed

The framework handles this automatically. The only additional responsibility is ensuring `getAllDatas()` correctly filters for spectators.

### 7.6 Spectator Sequence Diagram

```
Spectator                        Server
────────                         ──────

Loads game page
  │
  ├── getAllDatas() ──────────►  Server identifies spectator
  │                                │
  │◄── public snapshot ───────  Returns: players (no hands), cards
  │                                (public locations only), board
  │
  ├── setup(gamedatas)
  │     ├── render board
  │     ├── render players
  │     └── NO hand panel (spectator check)
  │
  ├── replay gamelog
  │     (public notifications only)
  │
  ├── onEnteringState(state)
  │
  └── READ-ONLY MODE
```

---

## 8. Incremental Synchronization

### 8.1 When to Send Deltas

| Scenario | Strategy | Rationale |
|---|---|---|
| Score change | Delta | Single value, frequently updated |
| Card movement | Delta | Client has card in cache, just moves DOM element |
| Resource change | Delta | Counter update, simple animation |
| After undo | Snapshot | State may have rewound significantly |
| After reconnect | Snapshot | Client needs full state baseline |
| Complex card effect | Delta + snapshot | Delta for primary change, snapshot for derived fields |

### 8.2 When to Resend Snapshots

Send a full snapshot when:
- The client may have lost track of intermediate state (undo, rollback)
- Multiple unrelated changes occurred and individual deltas would be more expensive than one snapshot
- The deltas would require complex client-side composition

```php
// After undo: send snapshot
$this->notifications->refreshUI($this->getAllDatas($playerId));

// Normal action: send delta
$this->notifications->cardPlayed($player, $cardId);
```

### 8.3 Trade-offs

| Aspect | Delta | Snapshot |
|---|---|---|
| Payload size | Small | Large |
| Client complexity | Higher (must accumulate) | Lower (just rebuild) |
| Server complexity | Lower (just send changes) | Higher (must assemble full state) |
| Replay safety | Must be idempotent | Always safe |
| Undo handling | Must reverse each delta | Just resend snapshot |

### 8.4 Reference Project Comparison

| Project | Sync Strategy | Why |
|---|---|---|
| **Arnak** | Full snapshot per action (no delta system) | Simpler client; fewer state fields |
| **Agricola** | Delta (per-action notifications) + snapshot on undo | Complex state; undo requires full reset |
| **ArkNova** | Delta with `$listeners` cache (smart deltas) | Richest state; payload minimisation critical |
| **Earth** | Command-based (private deltas → public commit) | Simultaneous turns; private preview before public |

**Arnak** sends full state in notifications because the state is relatively simple. Each action's notification contains enough data for the client to update directly.

**Agricola** sends per-action notifications with detailed payloads. Undo triggers a `refreshUI` snapshot that completely resets the client state.

**ArkNova** invented the delta system because with ~300 notification types and 83 states, full payloads would be enormous. The `$listeners` cache tracks which fields changed and only sends those.

**Earth** has two levels: private notifications (player sees their own pending changes) and public notifications (committed changes visible to all). The command pattern naturally produces deltas — each command's `do()` emits exactly the notifications for its effect.

---

## 9. Client Consistency

### 9.1 Avoiding Stale State

The client must never hold state that can become stale. Every piece of client-side state should be updated by exactly one notification type:

```js
// Client state cache
this.state = {
    cards: {},      // Updated by: notif_cardPlayed, notif_cardMoved, notif_drawCards
    players: {},    // Updated by: notif_gainResources, notif_updateScore
    board: {},      // Updated by: notif_placeTile, notif_removeTile
};
```

**Stale state detection:** If the client holds a value that is not refreshed by any notification, it is stale. The correct fix is either:
- Add a notification that updates it, or
- Derive it from other state

### 9.2 Derived State

State that can be derived from other state should not be stored separately:

```js
// BAD: derived state stored independently
this.handSize = 5;
this.handCards = [...];

// GOOD: derived from source
get handSize() {
    return Object.values(this.state.cards)
        .filter(c => c.location === 'hand' && c.locationArg === this.playerId)
        .length;
}
```

### 9.3 Animation State

Animation state (a card mid-flight, a counter animating) is separate from game state. The game state is always the target of the animation:

```js
async notif_cardPlayed(notif) {
    // Game state updates immediately
    const card = this.state.cards[notif.args.card_id];
    card.location = 'play';

    // Animation runs on top of game state
    const el = this.cardMgr.getElementById(card.id);
    await this.animationManager.animate(el, { from: 'hand', to: 'play' });

    // After animation, game state is already correct
}
```

**Rule:** Game state must be correct before the animation starts. The animation is a visual transition between two correct states.

### 9.4 Optimistic UI

**DO NOT use optimistic UI in BGA games.** The principle of server authority means the client must never assume an action will succeed before the server confirms it.

```js
// BAD: optimistic update
this.bga.actions.performAction('actPlayCard', { cardId: 42 });
this.removeCardFromHand(42);  // Assumes success — WRONG

// CORRECT: wait for server confirmation
this.bga.actions.performAction('actPlayCard', { cardId: 42 });
// notif_cardPlayed will update the client on success
// On error, the server throws and the client shows the error
```

The only exception is Earth's private command queue, where pending actions are applied to a private state and shown with a "[Pending]" indicator. This is not optimistic — it is a deliberate private preview of state that has not yet been committed.

### 9.5 Rollback

When a server action fails (exception thrown), the transaction rolls back on the server. The client must reverse any local changes made in anticipation:

```js
// The framework handles this:
this.bga.actions.performAction('actPlayCard', { cardId: 42 })
    .catch((error) => {
        // Transaction rolled back on server
        // Client: show error, reset UI to pre-action state
        this.showError(error.message);
        // The client state is still correct because no notification was delivered
    });
```

Since the client should never optimistically update, rollback only requires:
- Showing the error message
- Re-enabling UI elements that were disabled during the request

---

## 10. Synchronization Patterns

### 10.1 Pattern Overview

| Pattern | Server | Client | Best For |
|---|---|---|---|
| **Direct Refresh** | Sends complete state per notification | Rebuilds affected UI section | Simple games, small state |
| **Notification-Driven** | Sends targeted deltas | Applies incremental updates | Medium complexity |
| **Hybrid** | Deltas for normal actions, snapshots for undo | Applies deltas, rebuilds on snapshot | Most games |
| **Delta Cache (ArkNova)** | Attaches only changed fields via listener cache | Applies deltas from `infos` object | Rich state, many field types |
| **Command Queue (Earth)** | Two-phase: private (pending) + public (commit) | Shows pending state, then committed state | Simultaneous turns |

### 10.2 Direct Refresh (Arnak-Style)

```php
// Server: send everything the client needs
$this->notifyAllPlayers('refreshPlayer', clienttranslate('...'), [
    'player_id' => $playerId,
    'resources' => $player->getResources(),
    'hand_count' => count($hand),
    'score' => $player->getScore(),
]);
```

```js
// Client: rebuild the player panel
notif_refreshPlayer(notif) {
    const pid = notif.args.player_id;
    this.playerBoards[pid].setResources(notif.args.resources);
    this.playerBoards[pid].setScore(notif.args.score);
    this.counters['hand_' + pid].toValue(notif.args.hand_count);
}
```

**Pros:** Simple, no accumulation logic, no derived state.
**Cons:** Larger payloads, may update fields that haven't changed.

### 10.3 Notification-Driven (Agricola-Style)

```php
// Server: one notification per logical change
$this->notifications->resourcesSpent($player, $cost);
$this->notifications->cardPlayed($player, $cardId);
$this->notifications->scoreUpdated($player, $newScore);
```

```js
// Client: specific handler per notification type
notif_resourcesSpent(notif) {
    this.playerBoards[notif.args.player_id].setResources(notif.args.resources);
}

notif_cardPlayed(notif) {
    this.cardMgr.moveToLocation(notif.args.card_id, 'play');
}

notif_scoreUpdated(notif) {
    this.playerBoards[notif.args.player_id].setScore(notif.args.score);
}
```

**Pros:** Clean separation of concerns, easy to trace.
**Cons:** More notification types, more handlers.

### 10.4 Hybrid (Recommended)

Deltas for normal actions, snapshots for undo and reconnect:

```php
// Normal action: targeted delta
$this->notifications->cardPlayed($player, $cardId);

// Undo: full snapshot
$this->notifications->clearTurn($player);
$this->notifications->refreshUI($this->getAllDatas($playerId));
$this->notifications->refreshHand($player, $player->getHand());
```

```js
// Normal handler
notif_cardPlayed(notif) {
    this.cardMgr.moveToLocation(notif.args.card_id, 'play');
}

// Snapshot handler — rebuilds everything
notif_refreshUI(notif) {
    this.rebuildUI(notif.args.datas);
}
```

### 10.5 Earth Pattern

Earth adds a private phase before public commitment:

```php
// Private phase (player sees pending state)
$cmd = new PlantCardActionCommand($playerId, $cardId);
$cmd->do($privateNotifier);
ActionCommandMgr::saveOne($cmd, $privateNotifier);

// Public phase (all players see committed state)
ActionCommandMgr::commit($playerId);
```

```js
// Private handler — shows pending indicator
notif_NTF_APPLY_PRIVATE_STATE(notif) {
    this.showPendingAction(notif.args);
}

// Public handler — committed state
notif_NTF_COMMIT_ACTION(notif) {
    this.removePendingIndicator();
    this.applyCommittedState(notif.args);
}
```

### 10.6 ArkNova Delta Cache Pattern

ArkNova attaches changed player fields to every notification as an `infos` block:

```php
// Every notification method calls this before sending:
self::updateIfNeeded($data, $name, 'public');

// This adds: $data['infos'] = ['score' => [1234 => 45], 'icons' => [1234 => 3]]
```

```js
// Every notification handler processes infos:
notif_anyNotification(notif) {
    if (notif.args.infos) {
        for (const [field, values] of Object.entries(notif.args.infos)) {
            for (const [playerId, value] of Object.entries(values)) {
                this.updateField(playerId, field, value);
            }
        }
    }
    // ... handle the notification's primary action
}
```

See [notification-patterns.md §13.3](./notification-patterns.md#133-delta-only-updates-arknova-pattern).

---

## 11. Performance

### 11.1 Payload Minimization

Every notification is serialised to JSON and stored in the `gamelog` table. Large payloads degrade:
- Network transfer time
- Database storage for archived games
- Archive replay time

**Guidelines:**
- Send card IDs, not full card objects (client has card definitions)
- Send resource deltas, not full resource arrays
- Use `filterCardDatas()` to strip non-essential fields
- Batch related changes into a single notification

### 11.2 Filtering

```php
// Filter card data before sending in notifications
protected static function filterCardDatas($card): array
{
    return [
        'id' => $card['id'],
        'location' => $card['location'],
        'locationArg' => $card['location_arg'],
        // Omit: name, type, description, cost — client has these cached
    ];
}
```

### 11.3 Batch Notifications

```php
// BAD: one notification per resource type
foreach ($resources as $type => $amount) {
    $this->notifyAllPlayers('gainResource', '', ['player' => $player, 'type' => $type, 'amount' => $amount]);
}

// GOOD: one notification for all resources
$this->notifications->gainResources($player, $resources);
```

### 11.4 Animation Batching

Multiple small animations should be batched to avoid blocking the notification queue:

```js
// BAD: sequential animations block the queue
async notif_gainResources(notif) {
    for (const resource of notif.args.resources) {
        await this.animateResourceGain(resource);
    }
}

// GOOD: parallel animations within a single notification
notif_gainResources(notif) {
    const promises = notif.args.resources.map(r => this.animateResourceGain(r));
    return Promise.all(promises);
}
```

### 11.5 Network Efficiency

| Concern | Best Practice |
|---|---|
| Payload size | Send filtered card data, not full rows |
| Notification count | Batch related changes into one notification |
| Animation duration | Allow fast mode (skip animations) |
| Reconnect payload | Implement `refreshUI` to skip replay |
| Delta efficiency | Use ArkNova's `$listeners` pattern for rich state |

---

## 12. Anti-Patterns

### 12.1 Duplicating State

**Symptom:** The same logical state exists in the client cache AND in the DOM data attributes.

```js
// BAD: state in two places
this.handCount = 5;
$('#hand-count').data('count', 5);
// Update must touch both — they will inevitably desync
```

**Solution:** Single source of truth on the client. Everything else derives from it:

```js
// GOOD: single cache
this.state = {
    players: {},
    cards: {},
};

// Derived for DOM
get handCount() {
    return this.getHand().length;
}
```

### 12.2 Trusting Client State

**Symptom:** The server sends deltas and the client accumulates them, but the client's accumulated state is never verified against the server.

```js
// BAD: client trusts its own accumulated score
this.playerScore += delta;
// If a notification was missed or a reconnect didn't replay correctly,
// this value will be permanently wrong.
```

**Solution:** Always send absolute values in notifications, not just deltas:

```php
// GOOD: send absolute values
$this->notifyAllPlayers('updateScore', '', [
    'player_id' => $playerId,
    'score' => $newScore,    // ← absolute, not delta
]);
```

### 12.3 Large Payloads

**Symptom:** Notifications send full database rows.

```php
// BAD: send entire card row
$this->notifyAllPlayers('cardPlayed', '', [
    'card' => $cardRow,  // Contains: id, type, type_arg, location, location_arg, state, extra_datas
]);
```

**Solution:** Filter to only what the client needs:

```php
// GOOD: send only needed fields
$this->notifyAllPlayers('cardPlayed', '', [
    'card_id' => $cardId,
    'location' => 'play',
]);
```

### 12.4 Hidden Dependencies

**Symptom:** A notification handler depends on state set by a previous notification handler, but the dependency is not explicit.

```js
// BAD: handler depends on prior state
notif_cardPlayed(notif) {
    const cardType = this.cardTypes[notif.args.card_id];  // Assumes cardTypes is populated
    this.renderCard(cardType);
}
```

If `cardTypes` was not populated (e.g., setup not yet complete), this crashes.

**Solution:** Ensure `getAllDatas()` provides all prerequisites. Use `setup()` to prime caches that notification handlers depend on.

### 12.5 Missing Reconnect Support

**Symptom:** Reconnecting players see a broken UI because notification handlers are not idempotent.

```js
// BAD: creates element without checking existence
notif_playCard(notif) {
    const el = document.createElement('div');
    this.gameArea.appendChild(el);
}

// GOOD: checks existence first (idempotent)
notif_playCard(notif) {
    let el = document.getElementById('card-' + notif.args.card_id);
    if (!el) {
        el = document.createElement('div');
        el.id = 'card-' + notif.args.card_id;
        this.gameArea.appendChild(el);
    }
}
```

See [notification-patterns.md §10.3](./notification-patterns.md#103-handling-replay-of-notifications).

### 12.6 Server-Side State in Client

**Symptom:** Game logic is duplicated on the client.

```js
// BAD: client computes game logic
notif_cardPlayed(notif) {
    if (this.currentPlayer === notif.args.player_id) {
        this.deductFromHand(notif.args.card_id);
        this.addToPlay(notif.args.card_id);
        this.incrementScore(1);
    }
}
```

**Solution:** Send the complete effect in the notification. The client only renders:

```js
// GOOD: client only updates UI
notif_cardPlayed(notif) {
    this.moveCardElement(notif.args.card_id, 'play');
}
```

### 12.7 Notification Scatter

**Symptom:** `notifyAllPlayers` calls are scattered throughout the codebase.

```php
// BAD: notification logic mixed with domain logic
public function playCard($cardId, $playerId): void
{
    $this->DbQuery("UPDATE card SET ...");
    $this->notifyAllPlayers('cardPlayed', '', [...]);  // ← scattered
}
```

**Solution:** Centralized Notifications class. See [domain-architecture.md §20.5](./domain-architecture.md#205-canonical-notification-class) and [notification-patterns.md §15.1](./notification-patterns.md#151-centralized-notification-class).

---

## 13. Templates

### 13.1 Canonical getAllDatas()

```php
// In Game.php
public function getAllDatas(int $currentPlayerId = null): array
{
    $result = [];

    // === PUBLIC STATE (all callers) ===
    $result['players'] = $this->players->getAllForUi();
    $result['cards'] = $this->cards->getAllPublic();
    $result['board'] = $this->board->getPublicState();
    $result['globals'] = [
        'round' => Globals::getCurrentRound(),
        'phase' => Globals::getGamePhase(),
    ];

    // === PRIVATE STATE (only for actual players) ===
    if ($currentPlayerId !== null) {
        $result['hand'] = $this->cards->getHand($currentPlayerId);
        $result['privateChoices'] = $this->players->getPrivateState($currentPlayerId);
    }

    // === CACHE PRIMING (if needed) ===
    if ($this->cards->usesDynamicDefinitions()) {
        $result['cardDefinitions'] = $this->cards->getDefinitions();
    }

    return $result;
}
```

### 13.2 Canonical Notification (Server)

```php
// In Core/Notifications.php
public static function cardPlayed($player, int $cardId): void
{
    $card = Game::get()->cards->get($cardId);
    self::notifyAll('cardPlayed', clienttranslate('${player_name} plays a card'), [
        'player' => $player,
        'card_id' => $cardId,
        'card_name' => $card->getDisplayName(),
        'i18n' => ['card_name'],
    ]);
}

// Internal helpers
protected static function notifyAll(string $type, string $log, array $args): void
{
    self::updateArgs($args);
    Game::get()->notifyAllPlayers($type, $log, $args);
}

protected static function updateArgs(array &$args): void
{
    if (isset($args['player'])) {
        $args['player_name'] = $args['player']->getName();
        $args['player_id'] = $args['player']->getId();
        unset($args['player']);
    }
}
```

### 13.3 Canonical Notification Handler (Client)

```js
// In Game.js

setupNotifications() {
    this.bga.notifications.setupPromiseNotifications();
}

notif_cardPlayed(notif) {
    const args = notif.args;

    // Update client state cache
    if (this.state.cards[args.card_id]) {
        this.state.cards[args.card_id].location = 'play';
    }

    // Update DOM (idempotent)
    let el = document.getElementById('card-' + args.card_id);
    if (!el) {
        el = this.cardMgr.createElement(args.card_id);
    }
    this.cardMgr.moveToLocation(el, 'play');

    // Return promise for animation (if any)
    return this.cardMgr.animateToLocation(args.card_id, 'play');
}

// Private notification handler
notif_pDrawCards(notif) {
    if (this.bga.players.isCurrentPlayerSpectator()) return;

    const args = notif.args;
    this.handMgr.addCards(args.cards);
}

// Snapshot handler
notif_refreshUI(notif) {
    this.rebuildUI(notif.args.datas);
}

notif_refreshHand(notif) {
    if (this.bga.players.isCurrentPlayerSpectator()) return;
    this.handMgr.rebuild(notif.args.hand);
}

notif_clearTurn(notif) {
    this.removeUndoButtons();
}
```

### 13.4 Canonical Reconnect Implementation

```php
// Server side — in the Notifications class or a dedicated method
public static function handleReconnect(int $playerId): void
{
    // Send current state snapshot
    $datas = Game::get()->getAllDatas($playerId);
    self::refreshUI($datas);
    self::refreshHand(
        Players::get($playerId),
        Game::get()->cards->getHand($playerId)
    );
}

public static function refreshUI(array $datas): void
{
    $filtered = [
        'players' => $datas['players'],
        'cards' => $datas['cards'],
        'board' => $datas['board'],
        'globals' => $datas['globals'] ?? [],
    ];
    foreach ($filtered['players'] as &$player) {
        $player['hand'] = [];  // Strip private data from public
    }
    self::notifyAll('refreshUI', '', ['datas' => $filtered]);
}

public static function refreshHand($player, array $hand): void
{
    $filtered = array_map(fn($c) => self::filterCardDatas($c), $hand);
    self::notifyPlayer('refreshHand', '', [
        'player' => $player,
        'hand' => $filtered,
    ], $player->getId());
}
```

```js
// Client side
notif_refreshUI(notif) {
    const datas = notif.args.datas;
    this.state.players = datas.players;
    this.state.cards = datas.cards;
    this.board.setState(datas.board);
    this.renderAll();
}

notif_refreshHand(notif) {
    if (this.bga.players.isCurrentPlayerSpectator()) return;
    this.state.hand = notif.args.hand;
    this.handMgr.rebuild(notif.args.hand);
}

notif_clearTurn(notif) {
    this.removeUndoButtons();
}
```

---

## 14. Checklists

### 14.1 Synchronization Review

- [ ] Every state mutation generates at least one notification
- [ ] Notifications are sent through a centralized Notifications class
- [ ] Each notification payload contains enough for the client to update (absolute values, not just deltas)
- [ ] Notification handlers are registered via `setupPromiseNotifications()`
- [ ] Handlers are idempotent (safe for replay during reconnect)
- [ ] Handlers check spectator status before processing private data
- [ ] Notification type names follow `camelCase` convention
- [ ] Transient notifications (no game-log significance) have empty log strings
- [ ] Persistent notifications have meaningful `clienttranslate()` log strings
- [ ] The `i18n` array lists all translatable arg keys
- [ ] No `notifyAllPlayers` calls outside the Notifications class

### 14.2 Reconnect Review

- [ ] `getAllDatas()` returns a complete snapshot from the player's perspective
- [ ] `getAllDatas()` excludes private data for spectators
- [ ] Client `setup()` rebuilds all caches from `getAllDatas()`
- [ ] Notification handlers are idempotent (replay does not duplicate state)
- [ ] `refreshUI` / `refreshHand` patterns are implemented for undo and reconnect
- [ ] The `refreshUI` pattern filters private data from public payloads
- [ ] Card data is filtered (`filterCardDatas`) in refresh notifications
- [ ] Handler element creation checks for existing elements before creating
- [ ] The client can fully rebuild from `getAllDatas()` + notification replay
- [ ] Reconnect works for all player states (active, waiting, spectator)

### 14.3 Notification Review

- [ ] Notification type name is descriptive (`verbNoun`)
- [ ] Log string uses `clienttranslate()` with `${variable}` placeholders
- [ ] Every placeholder has a corresponding key in args
- [ ] Translatable args are listed in `i18n` array
- [ ] Player data is passed as a `player` object (auto-resolved by `updateArgs()`)
- [ ] Private data uses `_private` key (preferred) or separate `notifyPlayer` call
- [ ] Card data uses `filterCardDatas()` for refresh notifications
- [ ] Handler returns a Promise for async animations
- [ ] Handler checks element existence before creation
- [ ] No sensitive data in public args
- [ ] Notification does not contain domain logic (presentation only)

### 14.4 Performance Review

- [ ] Notification payloads are minimal (no full DB rows)
- [ ] Related changes are grouped into a single notification
- [ ] Transient notifications have empty log strings (not stored in gamelog)
- [ ] Animation duration can be skipped via player preference (fast mode)
- [ ] `refreshUI` is implemented to avoid replaying hundreds of notifications on reconnect
- [ ] `getAllDatas()` uses batch queries (no N+1)
- [ ] Card definitions are cached on the client (not sent in every notification)
- [ ] Deltas are preferred over full snapshots for frequent, small changes
- [ ] ArkNova-style `$listeners` delta cache is considered for rich state
- [ ] No `SELECT *` patterns in notification data assembly

---

## References

- [game-flow-architecture.md](./game-flow-architecture.md) — execution pipeline (§2), server authority (§6), reconnect (§12), spectator (§13)
- [notification-patterns.md](./notification-patterns.md) — notification lifecycle (§2), public/private (§3), payload design (§5), reconnect (§10), spectator (§11), delta patterns (§13), performance (§13)
- [state-machine-architecture.md](./state-machine-architecture.md) — state args (§10), _private (§10.2), _no_notify (§10.3)
- [domain-architecture.md](./domain-architecture.md) — notifications as presentation events (§11), dependency rules (§14), notification template (§20.5)
- [persistence-architecture.md](./persistence-architecture.md) — entity lifecycle (§6), batch queries (§12.2), request caching (§12.5)
- [action-architecture.md](./action-architecture.md) — action lifecycle (§3), validation before mutate (§6.5)
- [reference-project-analysis.md](../foundation/reference-project-analysis.md) — sync patterns per project (⭐ ratings), notification comparisons
- [bga-developer-handbook.md](../foundation/bga-developer-handbook.md) — client API, notification registration, setup flow
