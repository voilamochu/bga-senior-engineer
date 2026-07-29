# BGA Notification Patterns — Engineering Standard

**Document purpose:** Define the canonical approach for designing, implementing, and maintaining notifications across BGA game implementations. This is a framework-focused engineering standard derived from analysis of official reference projects and the BGA framework API.

**Applicability:** All new BGA game implementations. Existing projects should migrate toward these conventions during major refactors.

---

## Table of Contents

- [1. Purpose of Notifications](#1-purpose-of-notifications)
- [2. Server-to-Client Architecture](#2-server-to-client-architecture)
- [3. Public vs Private Notifications](#3-public-vs-private-notifications)
- [4. Notification Naming Conventions](#4-notification-naming-conventions)
- [5. Payload Design](#5-payload-design)
- [6. Translation and i18n](#6-translation-and-i18n)
- [7. Notification Sequencing](#7-notification-sequencing)
- [8. Updating Client State](#8-updating-client-state)
- [9. UI Refresh Patterns](#9-ui-refresh-patterns)
- [10. Reconnect Considerations](#10-reconnect-considerations)
- [11. Spectator Considerations](#11-spectator-considerations)
- [12. Undo Interactions](#12-undo-interactions)
- [13. Performance Considerations](#13-performance-considerations)
- [14. Common Mistakes](#14-common-mistakes)
- [15. Best Practices](#15-best-practices)
- [16. Reference Project Comparison](#16-reference-project-comparison)
- [17. Recommended Canonical Approach](#17-recommended-canonical-approach)

---

## 1. Purpose of Notifications

Notifications are the sole mechanism for propagating server-side game state changes to connected clients. They serve four distinct functions:

1. **State synchronization** — inform all players of game state mutations (cards drawn, resources gained, pieces moved)
2. **Game log** — produce a human-readable record of every action for the game log archive
3. **User interface animation** — trigger client-side visual reactions (slide resources, flip cards, update counters)
4. **Undo recovery** — enable the client to reverse visible changes when a player undoes an action (see [Section 12](#12-undo-interactions))

Notifications are fire-and-forget from the server's perspective. The server does not wait for the client to acknowledge a notification before proceeding. However, the framework guarantees that notifications arrive at each client in the exact order they were sent, and the client processes them sequentially.

---

## 2. Server-to-Client Architecture

### 2.1 The Notify Pipeline

```
PHP (Game Logic)
  │
  ├─ $this->notifyAllPlayers(type, log, args)       → all clients
  ├─ $this->notifyPlayer($playerId, type, log, args) → one client
  │
  ▼
BGA Framework (enqueues + stores in gamelog table)
  │
  ▼
Client (JavaScript notification queue)
  │
  ├─ setupPromiseNotifications()  ← auto-registers notif_* methods
  │
  ▼
notif_MyType(notif)   ← called sequentially for each notification
```

### 2.2 Server-Side Methods

The framework provides two send methods in the `Table` base class:

```php
// Broadcast to every player at the table (including spectators)
$this->notifyAllPlayers(string $notificationType, string $notificationLog, array $notificationArgs): void

// Send to a single player (used for private/hidden information)
$this->notifyPlayer(int $playerId, string $notificationType, string $notificationLog, array $notificationArgs): void
```

`$notificationArgs` also accepts a special `'_private'` key. When present, its value is a map of `playerId => data`; each player only receives their own entry in `_private`, while spectators receive nothing from `_private`. This is the framework's built-in mechanism for selective data delivery (see [Section 3](#3-public-vs-private-notifications)).

Both `notifyAllPlayers` and `notifyPlayer` are considered deprecated in the newer BGA framework in favor of `$this->bga->notify->all()` and `$this->bga->notify->player()`. Reference projects still use the old API. Migration to the new API is recommended for new projects.

### 2.3 Client-Side Registration

The client automatically discovers notification handlers by convention. Any method on the game class whose name starts with `notif_` is registered when you call `this.bga.notifications.setupPromiseNotifications()` in the `setupNotifications` method.

```js
// In your gamename.js
setupNotifications() {
    this.bga.notifications.setupPromiseNotifications();
    // or with custom prefix:
    this.bga.notifications.setupPromiseNotifications({ prefix: 'handle_' });
}

// Handlers are auto-discovered:
notif_gainResources(notif) {
    const args = notif.args;
    // args contains all the data sent from PHP
    this.updateResourceDisplay(args.player_id, args.resources);
}
```

### 2.4 The Notification Object

The client receives a notification object with the following fields:

| Field | Type | Description |
|---|---|---|
| `type` | string | The notification type name from PHP |
| `log` | string | The log string (possibly empty) |
| `args` | object | The arguments array from PHP |
| `move_id` | int | The move ID this notification belongs to |
| `uid` | string | Unique identifier |
| `table_id` | int | Table ID |

---

## 3. Public vs Private Notifications

### 3.1 Distinction

Public notifications are seen by all players and spectators. Private notifications are seen only by a specific player and contain hidden information (e.g., cards drawn into hand, secret scoring).

### 3.2 Two Approaches

#### Approach A: Separate Notification Types (ArkNova pattern)

Send one public notification for the log message and a separate private notification for the hidden data:

```php
// Public: everyone sees "Paul draws 2 cards"
$this->notifyAllPlayers('drawCards',
    clienttranslate('${player_name} draws ${n} card(s) from the deck'),
    ['player' => $player, 'n' => count($cards)]
);

// Private: only Paul sees which cards
$this->notifyPlayer($player->getId(), 'pDrawCards',
    clienttranslate('You draw ${card_names} from the deck'),
    ['player' => $player, 'cards' => $cards->toArray()]
);
```

**Trade-off:** Two notifications per action; clients must subscribe to both `notif_drawCards` and `notif_pDrawCards`. Clear separation of concerns.

#### Approach B: Single Notification with `_private` (Framework pattern)

Use the `_private` key in notification args:

```php
$this->notifyAllPlayers('drawCards',
    clienttranslate('${player_name} draws ${n} card(s) from the deck'),
    [
        'player' => $player,
        'n' => count($cards),
        '_private' => [
            $player->getId() => ['cards' => $cards->toArray()],
        ],
    ]
);
```

**Trade-off:** Single notification, simpler to subscribe. Spectators and other players do not receive the `_private` data. This is the newer, recommended approach.

### 3.3 Recommendation

Use **Approach B** (`_private` key) for new projects and when targeting the modern framework. Use **Approach A** only when you need different handler behavior on the client between the public and private parts (e.g., the public handler logs a message, the private handler updates card UI).

### 3.4 What Not to Send

Never send the following data in any notification:

- Database primary keys that reveal player patterns
- Future game state information (upcoming draws, shuffled deck order)
- Other players' hands or private selections
- The full deck composition

---

## 4. Notification Naming Conventions

### 4.1 Name Format

Notification names must be concise, descriptive, and follow a consistent naming convention.

| Convention | Example | Used By |
|---|---|---|
| `camelCase` | `gainResources`, `playSponsor` | Agricola, ArkNova, Arnak |
| `UPPER_SNAKE` with `NTF_` prefix | `NTF_UPDATE_CARDS`, `NTF_PLAYER_GAIN_SOIL` | Earth |

### 4.2 Recommendation

Use **`camelCase`** without prefix for notification names. The `NTF_` prefix convention in Earth is a consequence of its command-pattern architecture (notification names are class-level constants) and is unnecessary in a direct notification approach.

### 4.3 Naming Guidelines

| Pattern | Example | When |
|---|---|---|
| `verbNoun` | `gainResources` | General action notification |
| `placeNoun` | `placeFarmer` | Placing game elements |
| `updateNoun` | `updateScores` | Incremental state update |
| `refreshNoun` | `refreshUI` | Full state refresh |
| `pVerbNoun` | `pDrawCards` | Private variant of a notification (see §3.2 Approach A) |
| `clearNoun` | `clearTurn` | Undo or state reset |

### 4.4 Private Notification Suffix

When using separate notification types (Approach A), prefix the private variant with `p`:

```php
// Public
$this->notifyAllPlayers('drawCards', ...);
// Private
$this->notifyPlayer($playerId, 'pDrawCards', ...);
```

This convention is used consistently in ArkNova and should be adopted when Approach A is necessary.

---

## 5. Payload Design

### 5.1 Minimum Required Fields

Every notification payload should include:

```php
[
    'player' => $player,                  // Player object or id; auto-resolves to player_name/player_id
    // OR just add player_name/player_id directly:
    'player_name' => $player->getName(),
    'player_id' => $player->getId(),
]
```

If the notification describes a card action:

```php
[
    'card' => $card,                      // Card object; auto-resolves to card_name
    'i18n' => ['card_name'],              // Marks card_name for translation
]
```

### 5.2 Auto-Resolution Pattern (Agricola/ArkNova)

Maintain a centralized `updateArgs()` method that automatically expands common fields:

```php
protected static function updateArgs(&$data)
{
    // Player objects -> player_name + player_id
    if (isset($data['player'])) {
        $data['player_name'] = $data['player']->getName();
        $data['player_id'] = $data['player']->getId();
        unset($data['player']);
    }

    // Card objects -> card_name (with i18n)
    if (isset($data['card'])) {
        $data['i18n'][] = 'card_name';
        $data['card_name'] = $data['card']->getName();
    }

    // Resource arrays -> resources_desc (human-readable)
    if (isset($data['resources']) && !isset($data['resources_desc'])) {
        $data['resources_desc'] = Utils::resourcesToStr($data['resources']);
    }
}
```

This pattern appears in both Agricola and ArkNova. It eliminates boilerplate from every notification call.

### 5.3 Recommended Payload Structure

```php
$this->notifyAllPlayers('gainResources', clienttranslate('${player_name} gains ${resources_desc}'), [
    'player' => $player,             // auto-resolved by updateArgs()
    'resources' => $resources,       // auto-converted to resources_desc
    'source' => $source,             // optional: where the gain comes from
    'i18n' => ['source'],            // mark translatable fields
]);
```

### 5.4 What to Include vs. Exclude

**Include:**
- The affected player's id and name
- The new state after mutation (not just the delta) when the client needs absolute values
- Unique identifiers for game elements (card_id, meeple_id) so the client can target DOM elements
- A `total` or balance field when updating resources, so the client can update counters without maintaining state

**Exclude:**
- Full DB rows when a summary suffices
- Data the client already knows (use delta updates instead)
- Non-serializable PHP objects that the client cannot use

---

## 6. Translation and i18n

### 6.1 Marking Strings for Translation

Use `clienttranslate()` for notification log strings and `totranslate()` for strings stored in the database (card names, material definitions).

```php
clienttranslate('${player_name} gains ${resources_desc}')   // Correct
totranslate('Clay')                                          // For material strings
```

### 6.2 Dynamic Values in Translation Strings

Use `${variable_name}` placeholders in the log string. The framework substitutes values from the args array automatically:

```php
$this->notifyAllPlayers('gainResources',
    clienttranslate('${player_name} gains ${resources_desc} (${source})'),
    [
        'player' => $player,
        'resources' => $resources,
        'source' => $sourceText,       // This will replace ${source}
        'i18n' => ['source'],          // Marks 'source' as translatable
    ]
);
```

### 6.3 The `i18n` Array

The `i18n` key in notification args tells the framework which args contain translatable text. Without it, the framework treats all values as non-translatable data.

```php
// Without i18n: ${source} will NOT be translated
// With i18n: ${source} WILL be translated to the player's language
'i18n' => ['source', 'card_name', 'resource_name']
```

### 6.4 Nesting Translatable Strings

For complex notifications where a translatable string itself contains variables, use the nested format:

```php
'card_name' => [
    'log' => '${cardType} ${cardNumber}',
    'args' => [
        'cardType' => $card->getTypeName(),
        'cardNumber' => $card->getNumber(),
        'i18n' => ['cardType'],
    ],
]
```

This pattern (from ArkNova) allows recursive translation resolution.

### 6.5 Preserving Values Across Replay

Use the `preserve` array for values that must survive translation but should not be re-translated during archive replay:

```php
'preserve' => ['card_id']
```

This prevents the framework from stripping values it assumes are translation-only.

### 6.6 Human-Readable Resource Names

For archive replay compatibility, append human-readable names to icon tokens. The live client strips them during rendering:

```php
foreach (['resources_desc', 'resources2_desc'] as $descKey) {
    if (isset($data[$descKey])) {
        $data[$descKey] = self::appendResourceNames($data[$descKey]);
    }
}
```

This pattern from Agricola ensures archived game logs read "1 reed, 1 stone" instead of "1,1".

---

## 7. Notification Sequencing

### 7.1 Sequential Processing

The framework delivers notifications to each client in order and processes them sequentially. Each `notif_` handler must complete (resolve its promise) before the next notification is processed.

```js
// These run sequentially, not in parallel
notif_firstAction(notif) { ... }
notif_secondAction(notif) { ... }
```

### 7.2 Synchronous vs. Asynchronous Handlers

If a `notif_` handler returns a Promise, the notification queue waits for it to resolve. If no Promise is returned, the queue proceeds immediately after the minimum duration.

```js
// Handler that waits for animation
async notif_playCard(notif) {
    await this.animateCard(notif.args.card_id);
    this.updateTableau(notif.args.card_id);
}
```

### 7.3 Minimum Duration

The framework enforces a minimum duration per notification to prevent UI flicker. The default is 500ms for notifications with text and 1ms for notifications without text.

```js
this.bga.notifications.setupPromiseNotifications({
    minDuration: 300,            // With text
    minDurationNoText: 1,        // Without text
});
```

### 7.4 Simple Pause

Use the built-in `simplePause` notification to add a delay between sequences:

```php
$this->bga->notify->all('simplePause', '', ['time' => 500]); // 500ms
```

### 7.5 Controlling Duration from Server

You can pass a `duration` in args to override the minimum duration for a specific notification:

```php
$this->notifyAllPlayers('myNotification', '', [
    'duration' => 2000,  // Force 2-second display
    // ... other args
]);
```

### 7.6 State Change Notifications

By default, entering/leaving a game state generates a notification. Use the `_no_notify` flag to suppress this for transit states that are immediately passed through:

```php
ST_MY_STATE => [
    'name' => 'myState',
    'type' => 'game',
    'action' => 'stMyAction',
    'args' => 'argsMyState',
    '_no_notify' => true,     // Suppress state-change notification
],
```

This is documented in the BGA state machine guide and should be used for any state that resolves without player interaction.

---

## 8. Updating Client State

### 8.1 Direct State Update

The simplest pattern: the notification payload contains the new state, and the handler updates the DOM directly:

```php
// Server
$this->notifyAllPlayers('updateScore', '', [
    'player_id' => $player->getId(),
    'new_score' => $newScore,
]);
```

```js
// Client
notif_updateScore(notif) {
    const playerId = notif.args.player_id;
    const score = notif.args.new_score;
    this.getPlayerBoard(playerId).updateScore(score);
}
```

### 8.2 Accumulated State Update

For complex state (e.g., a player's entire board), send a comprehensive payload that the client uses to rebuild:

```php
$this->notifyAllPlayers('refreshPlayerBoard', '', [
    'player_id' => $player->getId(),
    'meeples' => $meeples->toArray(),
    'cards' => $cards->ui(),
    'scores' => $scores,
]);
```

### 8.3 Delta-Only Update (ArkNova pattern)

Send only what changed, using a cached-value comparison system:

```php
// Maintain a static cache of previous values
public static function updateIfNeeded(&$args, $notifName, $notifType)
{
    foreach (self::$listeners as $listener) {
        $name = $listener['name'];
        $method = $listener['method'];
        foreach (Players::getAll() as $pId => $player) {
            $val = $player->$method();
            if ($val !== (self::$cachedValues[$name][$pId] ?? null)) {
                $args['infos'][$name][$pId] = $val;
                self::$cachedValues[$name][$pId] = $val;
            }
        }
    }
}
```

This pattern (from ArkNova) attaches an `infos` object to every notification containing only the player state fields that actually changed. The client applies these deltas to update counters, icons, and panels.

### 8.4 Client Cache and DOM Reconciliation

Maintain a client-side cache of game elements and reconcile on notification:

```js
notif_updateCards(notif) {
    const cards = notif.args.cards;
    for (const card of cards) {
        let element = this.cardMgr.getElementById(card.cardId);
        if (element === null) {
            element = this.cardMgr.createElement(card);
        }
        this.cardMgr.moveToLocation(element, card.locationId);
    }
}
```

This pattern from Earth uses a `CardMgr` that owns the card cache. The notification handler only moves cards that changed location and creates those that don't exist yet.

### 8.5 Counter Updates

The framework provides the `Counter` component for live number displays. Use the `updatePlayerCounter` notification pattern:

```php
// Increment a counter
$this->notifyAllPlayers('updatePlayerCounter', '', [
    'player_id' => $player->getId(),
    'counter' => 'hand',
    'value' => count($hand),
]);
```

```js
notif_updatePlayerCounter(notif) {
    const args = notif.args;
    this.counters[args.player_id][args.counter].toValue(args.value);
}
```

---

## 9. UI Refresh Patterns

### 9.1 The `refreshUI` / `refreshHand` Pattern

This is the canonical pattern for full state synchronization, used identically in Agricola and ArkNova. It is invoked after undo operations and during reconnection.

**Server side:**

```php
public static function refreshUI($datas)
{
    // Strip non-essential data for efficiency
    $fDatas = [
        'players' => $datas['players'],
        'cards' => $datas['cards'],
        'meeples' => $datas['meeples'],
        // ... only essential state
    ];

    // Strip private data from public payload
    foreach ($fDatas['players'] as &$player) {
        $player['hand'] = []; // Clear hidden hands
    }

    self::notifyAll('refreshUI', '', ['datas' => $fDatas]);
}

public static function refreshHand($player, $hand)
{
    // Send only public card data
    foreach ($hand as &$card) {
        $card = self::filterCardDatas($card);
    }
    self::notify($player, 'refreshHand', '', [
        'player' => $player,
        'hand' => $hand,
    ]);
}
```

**Client side:**

```js
notif_refreshUI(notif) {
    const datas = notif.args.datas;
    this.updatePlayers(datas.players);
    this.updateCards(datas.cards);
    this.updateMeeples(datas.meeples);
}

notif_refreshHand(notif) {
    const hand = notif.args.hand;
    this.handMgr.rebuild(hand);
}
```

### 9.2 The `refreshUI` Flow

```
Undo or Reconnect occurs
  │
  ▼
Server calls refreshUI(allDatas)     → broadcasts public state to everyone
Server calls refreshHand(player, hand) → sends private hand to each player individually
  │
  ▼
Client rebuilds entire UI from the fresh state
```

### 9.3 When to Refresh

The `refreshUI` / `refreshHand` pattern must be called:

1. After any undo operation that rewinds game state
2. During reconnection (the client already calls `getAllDatas`; notifications confirm the state)
3. After zombie turn resolution
4. After any server-side state rollback

### 9.4 Filtering Card Data for Refresh

When refreshing card state, strip fields the client can reconstruct from cached static data:

```php
protected static function filterCardDatas($card)
{
    return [
        'id' => $card['id'],
        'location' => $card['location'],
        'pId' => $card['pId'],
        'state' => $card['state'],
        // Only send dynamic fields; omit name, type, description
    ];
}
```

This pattern assumes the client has a cache of static card data (loaded during setup). It significantly reduces refresh payload size.

### 9.5 Card Cache Priming

When cards are loaded from a seed (not from static material), prime the client cache before sending refresh notifications:

```php
public static function populateCardCache($cards)
{
    self::notifyAll('populateCardCache', '', ['cards' => $cards]);
}
```

This ensures the client has all card definitions before `refreshUI` uses `filterCardDatas`. Only required when cards are dynamically generated.

---

## 10. Reconnect Considerations

### 10.1 The Reconnection Flow

When a player refreshes the page (F5) or reconnects after a disconnect:

```
1. Page reloads, constructor runs
2. setup() is called with getAllDatas() result
3. All notif_ handlers are registered
4. All pending notifications (since the player's last move) are replayed
```

### 10.2 The Role of `getAllDatas`

The server's `getAllDatas` method must return a complete snapshot of the game state from the calling player's perspective:

```php
public function getAllDatas($currentPlayerId = null)
{
    $data = [
        'players' => $this->getCollectionFromDb("SELECT ..."),
        'cards' => $this->cards->getAll(),
        'meeples' => $this->meeples->getAll(),
    ];

    // Filter private data per player
    if ($currentPlayerId !== null) {
        $data['hand'] = $this->cards->getPlayerHand($currentPlayerId);
    }

    return $data;
}
```

### 10.3 Handling Replay of Notifications

When notifications are replayed during reconnection, the client processes them sequentially. This means:

- The client's notification handlers must be **idempotent** — processing the same notification twice must not corrupt state
- Handlers must check whether an element already exists before creating it
- Handlers that call `getAllDatas` setup during `setup()` establish baseline state; notification replay brings the state forward

### 10.4 Performance Concerns During Replay

Replaying hundreds of notifications during reconnection can be slow. The `refreshUI` pattern (see Section 9) addresses this by sending the entire current state in a single notification, which the client uses as a base snapshot, skipping intermediate notifications.

**Implement `refreshUI` as part of the reconnection path** so that reconnecting players receive one state payload instead of replaying every historical notification.

---

## 11. Spectator Considerations

### 11.1 What Spectators See

Spectators receive all public notifications but never receive:

- Private notifications sent via `notifyPlayer`
- The `_private` portion of a notification's args
- Hand contents or other hidden information

### 11.2 Detecting Spectators

```js
if (this.bga.players.isCurrentPlayerSpectator()) {
    // Hide interactive elements
}
```

### 11.3 No Special Notification Handling Required

The framework automatically filters private data for spectators. No special spectator logic is needed in notification handlers — simply do not send private data in public notification args.

### 11.4 Show/Hide Patterns

For elements that are always visible to players but should be hidden from spectators (e.g., hand panels), check spectator status in setup:

```js
setup() {
    if (!this.bga.players.isCurrentPlayerSpectator()) {
        this.setupHandPanel();
    }
}
```

Notification handlers can use the same check to skip private animations:

```js
notif_pDrawCards(notif) {
    if (this.bga.players.isCurrentPlayerSpectator()) return;
    // ... update hand display
}
```

---

## 12. Undo Interactions

### 12.1 The Undo Notification Challenge

When a player undoes an action, previously sent notifications must be reversed. The client must revert all visual changes that those notifications triggered.

### 12.2 Approach A: Gamelog Cancellation (Agricola/ArkNova)

The undo system logs every DB mutation to a `log` table. On undo:

1. Reverse all DB mutations
2. Cancel the corresponding gamelog packets (set `cancel = 1` on the `gamelog` table)
3. Send a `clearTurn` notification to the client
4. Send `refreshUI` + `refreshHand` to restore current state

```php
// Server side on undo
public static function clearTurn($player, $notifIds)
{
    self::notifyAll('clearTurn', clienttranslate('${player_name} restarts their turn'), [
        'player' => $player,
        'notifIds' => $notifIds,
    ]);
}

// Followed by full state refresh
self::refreshUI($datas);
foreach (Players::getAll() as $player) {
    self::refreshHand($player, $player->getHand()->ui());
}
```

```js
// Client side
notif_clearTurn(notif) {
    this.removeUndoButtons();
}
notif_refreshUI(notif) {
    this.rebuildUI(notif.args.datas);
}
```

This approach is **simple and correct** but expensive — it re-sends the entire state.

### 12.3 Approach B: Command Pattern (Earth)

Every player action is a `BaseActionCommand` with `do()` and `undo()` methods. The command queue is stored in an `action_command` DB table. On undo:

1. Pop commands from the queue in reverse order
2. Call `undo()` on each, which emits undo notifications
3. Undo notifications silently update the client state without re-triggering animations

```php
// Server
public function undoAll()
{
    ActionCommandMgr::undoAll($playerId, true);
}
```

```js
// Client — undo notifications are silent
notif_NTF_UNDO_BEGIN(notif) {
    this.beginUndoMode();
}
notif_NTF_UNDO_PRIVATE_STATE(notif) {
    this.restoreStateId(notif.args.stateId);
}
```

This approach is **fine-grained and efficient** but requires every action to implement a reverse operation.

### 12.4 Recommendation

Use **Approach A** (gamelog cancellation + refreshUI) for turn-based games where undo is an all-or-nothing restart of the turn. Use **Approach B** (command pattern) for simultaneous-turn games or games where per-action undo granularity is required.

### 12.5 Undo Buttons on the Client

When the server indicates an undoable step, display undo controls:

```php
// Server sends undo step indicator
$this->notifyPlayer($player, 'newUndoableStep', clienttranslate('Undo here'), [
    'stepId' => $stepId,
    'preserve' => ['stepId'],
]);
```

```js
notif_newUndoableStep(notif) {
    this.addUndoButton(notif.args.stepId);
}
```

The `preserve` array ensures the stepId survives translation during archive replay.

---

## 13. Performance Considerations

### 13.1 Payload Size

Each notification is serialized and stored in the `gamelog` table. Large payloads increase:

- Network transfer time
- Database storage for archived games
- Archive replay time

**Guidelines:**
- Prefer delta updates over full state snaps in frequent notifications
- Strip non-essential fields from card objects before sending
- Use `filterCardDatas` for refresh notifications (only send dynamic fields)
- Batch related changes into a single notification where possible

### 13.2 Notification Count

Each notification has overhead (transaction, serialization, network round-trip). Minimize notification count:

```php
// Bad: one notification per resource
foreach ($resources as $resource) {
    $this->notifyAllPlayers('gainResource', '', ['player' => $player, 'resource' => $resource]);
}

// Good: one notification for all resources
$this->notifyAllPlayers('gainResources', clienttranslate('${player_name} gains ${resources_desc}'), [
    'player' => $player,
    'resources' => $resources,
]);
```

### 13.3 Delta-Only Updates (ArkNova pattern)

Attach changed player state as an `infos` object to existing notifications rather than sending separate state notification:

```php
// In the Notifications class, before sending any notification:
self::updateIfNeeded($data, $name, 'public');

// This adds 'infos' to $data containing only changed fields:
// ['infos' => ['score' => [1234 => 45], 'icons' => [1234 => 3]]]
```

The client iterates `args.infos` and applies deltas:

```js
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

### 13.4 Fast Mode (Earth pattern)

Allow players to opt out of animations. When enabled, send visual-only notifications with minimal or zero duration:

```js
useFastNotification(playerId) {
    if (!this.getLocalPreference('fast_notif')) return false;
    return (this.player_id != playerId); // Only animate own actions
}
```

Notification handlers check this flag and skip animation promises:

```js
notif_playerGainSoil(notif) {
    if (this.useFastNotification(notif.args.playerId)) {
        this.playerBoardMgr.setSoilCount(notif.args.playerId, notif.args.totalSoilCount);
        return;
    }
    // ... animate
}

onBeforeNotification(notifId, args) {
    if (this.useFastNotification(args.args.playerId)) {
        this.setBXFastMode(true); // Skip all animations
    }
}
```

### 13.5 Ignoring Notifications

Clients can ignore specific notifications to skip visual processing. Use the ignore function in `setupPromiseNotifications`:

```js
this.bga.notifications.setupPromiseNotifications({
    ignoreNotifications: [
        ['notifType', (notif) => notif.args.player_id === this.player_id]
    ]
});
```

This is appropriate for skipping animations for the acting player's own actions. It does **not** secure hidden information — the data is still received by the client.

---

## 14. Common Mistakes

### 14.1 Missing `i18n` Array

```php
// WRONG: resource_name will not be translated
$this->notifyAllPlayers('message', clienttranslate('${player_name} gains ${resource_name}'), [
    'resource_name' => $names[$resource],
]);

// CORRECT: mark resource_name for translation
'i18n' => ['resource_name'],
```

### 14.2 Sending PHP Objects Instead of Arrays

```php
// WRONG: PHP object cannot be serialized
$this->notifyAllPlayers('message', '', ['card' => $cardObject]);

// CORRECT: convert to array first
$this->notifyAllPlayers('message', '', ['card' => $cardObject->toArray()]);
```

The framework converts arrays to JSON at the serialization boundary. PHP objects that implement `JsonSerializable` or that have a `toArray()` method must be explicitly converted.

### 14.3 Sending Private Data in Public Args

```php
// WRONG: all players see the hand cards
$this->notifyAllPlayers('drawCards', '', [
    'cards' => $hand,
    'player_id' => $player->getId(),
]);

// CORRECT: use _private for hidden data
$this->notifyAllPlayers('drawCards', '', [
    'n' => count($hand),
    'player_id' => $player->getId(),
    '_private' => [$player->getId() => ['cards' => $hand]],
]);
```

### 14.4 Non-Idempotent Handlers

```js
// WRONG: crashes if element already exists
notif_playCard(notif) {
    const element = document.createElement('div');
    this.gameArea.appendChild(element);
}

// CORRECT: check existence first
notif_playCard(notif) {
    let element = document.getElementById('card-' + notif.args.card_id);
    if (element === null) {
        element = document.createElement('div');
        element.id = 'card-' + notif.args.card_id;
        this.gameArea.appendChild(element);
    }
}
```

Handlers must be safe to replay during reconnection.

### 14.5 Ignoring Private Notifications for Spectators

```js
// WRONG: spectator will still receive and try to process this
notif_pDrawCards(notif) {
    this.updateHand(notif.args.cards);
}

// CORRECT: skip private handlers when spectating
notif_pDrawCards(notif) {
    if (this.bga.players.isCurrentPlayerSpectator()) return;
    this.updateHand(notif.args.cards);
}
```

### 14.6 Blocking the Notification Queue with Long Animations

```js
// WRONG: 5-second animation blocks all subsequent notifications
notif_slowAnimation(notif) {
    return this.animateForDuration(5000);
}

// CORRECT: break long animations into steps with intermediate notifications
// Server sends multiple notifications, each with a shorter animation
```

### 14.7 Sending Notifications After State Transitions

```php
// WRONG: state transition cancels pending notifications
$this->gamestate->nextState('nextTurn');
$this->notifyAllPlayers('lateNotif', '', []); // This may not be delivered

// CORRECT: send notifications before transitioning
$this->notifyAllPlayers('turnSummary', '', []);
$this->gamestate->nextState('nextTurn');
```

---

## 15. Best Practices

### 15.1 Centralized Notification Class

Create a dedicated `Notifications` class (or trait) that encapsulates all notification logic. Never scatter `notifyAllPlayers` / `notifyPlayer` calls throughout game logic.

```php
// Good: centralized
Notifications::gainResources($player, $resources, $source);

// Bad: scattered
$this->notifyAllPlayers('gainResources', clienttranslate('...'), [...]);
```

The centralized class should:

- Contain all notification type name constants
- Wrap `notifyAllPlayers` / `notifyPlayer` with automatic arg resolution
- Handle all i18n and translation concerns
- Manage the notification delta cache if used

### 15.2 Explicit Notification Methods

Define one static method per notification type:

```php
class Notifications
{
    public static function gainResources($player, $resources, $source = null)
    {
        $msg = $source
            ? clienttranslate('${player_name} gains ${resources_desc} (${source})')
            : clienttranslate('${player_name} gains ${resources_desc}');

        self::notifyAll('gainResources', $msg, [
            'player' => $player,
            'resources' => $resources,
            'source' => $source,
        ]);
    }
}
```

This provides:

- Type safety (the method signature documents expected arguments)
- Consistent null handling for optional fields
- A single place to update the log message
- IDE discoverability

### 15.3 Auto-Resolution of Common Fields

Implement a static `updateArgs` method that automatically resolves player objects, card objects, and resource arrays:

```php
protected static function updateArgs(&$data)
{
    if (isset($data['player'])) {
        $data['player_name'] = $data['player']->getName();
        $data['player_id'] = $data['player']->getId();
        unset($data['player']);
    }
    if (isset($data['card'])) {
        $data['i18n'][] = 'card_name';
        $data['card_name'] = $data['card']->getName();
    }
    if (isset($data['resources']) && !isset($data['resources_desc'])) {
        $data['resources_desc'] = Utils::resourcesToStr($data['resources']);
    }
}
```

### 15.4 Log Message for Every Public Notification

Every public notification should have a non-empty log string (second argument to `notifyAllPlayers`). The only exceptions are:

- State refresh notifications (`refreshUI`, `refreshHand`)
- Animation-only notifications that have no game-log meaning

Empty log strings in player-visible notifications produce silent entries in the game log archive, which harms replay readability.

### 15.5 Use `clienttranslate` at Call Site

Always use `clienttranslate()` at the notification call site, not at the function definition:

```php
// Correct
public static function gainResources($player, $resources, $source = null)
{
    $msg = $source
        ? clienttranslate('${player_name} gains ${resources_desc} (${source})')
        : clienttranslate('${player_name} gains ${resources_desc}');
    // ...
}
```

This ensures the string is captured during translation extraction.

### 15.6 Separate Visual-only from Game-state Notifications

Send visual-only notifications (animations, particle effects) as separate notifications with empty log strings. This keeps them out of the game log archive while still triggering client-side visuals:

```php
// Game-state notification (logged)
$this->notifyAllPlayers('gainResources', clienttranslate('${player_name} gains ${resources_desc}'), [...]);

// Visual-only notification (not logged)
$this->notifyAllPlayers('particleEffect', '', ['x' => 100, 'y' => 200]);
```

### 15.7 Notification Handler Naming

Follow a consistent naming pattern for notification handlers on the client:

```js
// Pattern: notif_<notification type>
notif_gainResources(notif) { ... }
notif_placeFarmer(notif) { ... }
```

If using JS state classes, delegate notification handling to the relevant state class:

```js
notif_gainResources(notif) {
    this.bga.states.getCurrentMainStateClass().onGainResources(notif.args);
}
```

### 15.8 Testing Notifications

Write test cases that verify:

- Every public notification has a non-empty log string with `clienttranslate`
- Private data is never in `_private` for players who should not see it
- All dynamic placeholders (`${...}`) have corresponding keys in args
- The `i18n` array correctly lists all translatable arg keys
- Notification handlers are idempotent (safe to replay)

---

## 16. Reference Project Comparison

### 16.1 Architecture Comparison

| Aspect | Agricola | Ark Nova | Arnak | Earth |
|---|---|---|---|---|
| Centralized class | Yes — `Notifications.php` (1141 lines) | Yes — `Notifications.php` (1672+ lines) | No — inline calls | Partial — `NotificationTrait.js` on client; command pattern on server |
| Auto arg resolution | Yes — `updateArgs()` | Yes — `updateArgs()` | No — manual expansion | No — handled by command notifier |
| Delta updates | No | Yes — `$listeners` cache | No | No |
| Private info | Separate `notifyPlayer` calls | Dual pattern (public + private) | Separate `notifyPlayer` calls | Command notifier types |
| Undo support | `clearTurn` + `refreshUI` | `clearTurn` + `refreshUI` | Basic undo action | Command undo (do/undo) |
| Notification prefix | camelCase | camelCase | camelCase | `NTF_` upper snake |
| Refresh pattern | `refreshUI` + `refreshHand` | `refreshUI` + `refreshHand` | None dedicated | Command commit rebroadcasts |
| Translation | `updateArgs` auto-i18n | `updateArgs` auto-i18n | Inline `i18n` arrays | `processNotifArgs` in notifier |
| Sequencing | Sequential via queue | Sequential via queue | Sequential via queue | Sequential + promise chaining |
| Spectator filter | Manual `filterCardDatas` | Manual `filterCardDatas` | No dedicated handling | `_private` key via notifier |

### 16.2 Strengths per Project

**Agricola:**
- Best auto-resolution system for common fields
- Cleanest `refreshUI` / `refreshHand` pattern for undo
- Most complete handling of notification i18n for archive logs

**ArkNova:**
- Most efficient notification system via delta cache
- Most thorough private info handling (dual notification pattern)
- Richest notification method count (~300) covering every game action

**Arnak:**
- Simplest approach — easy to understand
- Good for small games where centralized class overhead is not justified
- Tooltip injection into notifications is well handled

**Earth:**
- Most robust undo integration via command pattern
- Best handling of simultaneous-turn notifications
- Fast-mode player preference for animation skipping
- Most modular client-side notification dispatch

### 16.3 Trade-Offs

| Decision | Trade-Off |
|---|---|
| Centralized class vs. inline calls | Centralized adds structure but requires more files; inline is simpler but harder to maintain |
| Delta updates vs. full state | Deltas reduce payload but add complexity; full state is simpler but larger |
| Gamelog cancel vs. command undo | Cancel is simpler but sends full state refresh; commands are granular but require do/undo for every action |
| Separate public/private vs. `_private` key | Separate is clearer but doubles notifications; `_private` is cleaner but requires framework support |
| camelCase vs. upper snake | camelCase is standard in references; upper snake avoids collisions but looks non-standard |

---

## 17. Recommended Canonical Approach

### 17.1 Architecture

For new projects, adopt the following architecture:

```
modules/php/Core/
  Notifications.php          # Centralized notification class
  Notifications/             # (optional) Split if >500 lines
    GainNotifications.php
    CardNotifications.php
    ...

modules/js/
  NotificationTrait.js       # Client-side notification handlers
```

### 17.2 Server-Side Template

```php
<?php
namespace YOURGAME\Core;

class Notifications
{
    protected static function notifyAll($name, $msg, $data)
    {
        self::updateArgs($data);
        Game::get()->notifyAllPlayers($name, $msg, $data);
    }

    protected static function notify($player, $name, $msg, $data)
    {
        $pId = is_int($player) ? $player : $player->getId();
        self::updateArgs($data);
        Game::get()->notifyPlayer($pId, $name, $msg, $data);
    }

    protected static function updateArgs(&$data)
    {
        if (isset($data['player'])) {
            $data['player_name'] = $data['player']->getName();
            $data['player_id'] = $data['player']->getId();
            unset($data['player']);
        }
        if (isset($data['card'])) {
            $data['i18n'][] = 'card_name';
            $data['card_name'] = $data['card']->getName();
        }
        if (isset($data['resources']) && !isset($data['resources_desc'])) {
            $data['resources_desc'] = self::resourcesToStr($data['resources']);
        }
    }

    // One static method per notification type
    public static function gainResources($player, $resources, $source = null)
    {
        $msg = $source
            ? clienttranslate('${player_name} gains ${resources_desc} (${source})')
            : clienttranslate('${player_name} gains ${resources_desc}');

        self::notifyAll('gainResources', $msg, [
            'player' => $player,
            'resources' => $resources,
            'source' => $source,
            'i18n' => ['source'],
        ]);
    }

    // Refresh pattern for undo/reconnect
    public static function refreshUI($datas)
    {
        $fDatas = [
            'players' => $datas['players'],
            'cards' => $datas['cards'],
            'meeples' => $datas['meeples'],
        ];
        foreach ($fDatas['players'] as &$player) {
            $player['hand'] = [];
        }
        self::notifyAll('refreshUI', '', ['datas' => $fDatas]);
    }

    public static function refreshHand($player, $hand)
    {
        self::notify($player, 'refreshHand', '', [
            'player' => $player,
            'hand' => $hand,
        ]);
    }

    // Undo notification
    public static function clearTurn($player)
    {
        self::notifyAll('clearTurn', clienttranslate('${player_name} restarts their turn'), [
            'player' => $player,
        ]);
    }

    protected static function resourcesToStr($resources)
    {
        // Convert resource array to human-readable string with icons
    }

    protected static function filterCardDatas($card)
    {
        return [
            'id' => $card['id'],
            'location' => $card['location'],
            'pId' => $card['pId'],
            'state' => $card['state'],
        ];
    }
}
```

### 17.3 Client-Side Template

```js
// In yourgamename.js or modules/js/NotificationTrait.js

setupNotifications() {
    this.bga.notifications.setupPromiseNotifications();
}

notif_gainResources(notif) {
    const args = notif.args;
    this.updateResourceDisplay(args.player_id, args.resources);
    if (args.infos) {
        this.applyInfos(args.infos);
    }
}

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

### 17.4 Notification Delivery Checklist

Before implementing a notification, confirm:

- [ ] Does the notification type name follow `camelCase` naming?
- [ ] Is there a corresponding static method in the `Notifications` class?
- [ ] Is the log string wrapped in `clienttranslate()`?
- [ ] Does the log string use `${variable}` placeholders for dynamic values?
- [ ] Does every placeholder have a corresponding key in the args array?
- [ ] Are all translatable arg keys listed in the `i18n` array?
- [ ] Is the `player` field present and auto-resolved?
- [ ] Is there a `notif_` handler on the client?
- [ ] Is the handler idempotent (safe for replay)?
- [ ] If this is a refresh notification, is `filterCardDatas` applied?
- [ ] If this is a private notification, is it sent via `notifyPlayer` or `_private`?

### 17.5 Production Readiness

A notification system is production-ready when:

- Every public notification produces a meaningful log entry
- Reconnection replays leave the client in the correct state (tested by F5 refresh)
- Undo notifications restore the visual state without duplication
- Spectators never see hidden information
- The `gamelog` table is not excessively large (check archive storage)
- Animations can be skipped via a player preference option
- All strings are extracted for translation
