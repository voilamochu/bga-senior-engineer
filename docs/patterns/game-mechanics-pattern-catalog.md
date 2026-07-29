# BGA Game Mechanics Pattern Catalog

**Document purpose:** Provide definitive engineering blueprints for implementing common board game mechanics on Board Game Arena. This is an implementation handbook, not a game design document. Each pattern prescribes how to translate a physical mechanic into server architecture, state machines, persistence, notifications, client code, and tests.

**Cross-references:**
- [domain-architecture.md](../standards/domain-architecture.md) — layered architecture, managers, models
- [persistence-architecture.md](../standards/persistence-architecture.md) — tables, transactions, concurrency
- [state-machine-architecture.md](../standards/state-machine-architecture.md) — state types, transitions, args
- [action-architecture.md](../standards/action-architecture.md) — validation, delegation, error handling
- [client-ui-architecture.md](../standards/client-ui-architecture.md) — managers, widgets, rendering
- [client-synchronization-architecture.md](../standards/client-synchronization-architecture.md) — notifications, reconnect
- [animation-architecture.md](../standards/animation-architecture.md) — animation integration, fast mode
- [testing-debugging-architecture.md](../standards/testing-debugging-architecture.md) — test patterns, checklists
- [notification-patterns.md](../standards/notification-patterns.md) — payload design, public/private
- [project-architecture.md](../standards/project-architecture.md) — repository structure, naming
- [reference-project-analysis.md](../foundation/reference-project-analysis.md) — project-specific mechanic ratings
- [game-flow-architecture.md](../standards/game-flow-architecture.md) — execution pipeline, transaction model

---

## Table of Contents

- [Pattern Structure](#pattern-structure)
- [1. Worker Placement](#1-worker-placement)
- [2. Action Selection](#2-action-selection)
- [3. Simultaneous Turns](#3-simultaneous-turns)
- [4. Turn Order Manipulation](#4-turn-order-manipulation)
- [5. Card Drafting](#5-card-drafting)
- [6. Deck Building](#6-deck-building)
- [7. Tableau Building](#7-tableau-building)
- [8. Hand Management](#8-hand-management)
- [9. Resource Conversion](#9-resource-conversion)
- [10. Engine Building](#10-engine-building)
- [11. Market Systems](#11-market-systems)
- [12. Auctions](#12-auctions)
- [13. Hidden Information](#13-hidden-information)
- [14. Variable Player Powers](#14-variable-player-powers)
- [15. Tile Placement](#15-tile-placement)
- [16. Route Building](#16-route-building)
- [17. Area Control](#17-area-control)
- [18. Majority Scoring](#18-majority-scoring)
- [19. Set Collection](#19-set-collection)
- [20. Contracts and Objectives](#20-contracts-and-objectives)
- [21. Technology Trees](#21-technology-trees)
- [22. Multi-Step Actions](#22-multi-step-actions)
- [23. Triggered Effects](#23-triggered-effects)
- [24. Event Systems](#24-event-systems)
- [25. End Game Detection](#25-end-game-detection)
- [26. Scoring Systems](#26-scoring-systems)
- [27. Undo-Safe Mechanics](#27-undo-safe-mechanics)
- [28. Replay-Safe Mechanics](#28-replay-safe-mechanics)

---

## Pattern Structure

Each pattern follows this template:

```
┌─────────────────────────────────────────────────────────────┐
│  MECHANIC NAME                                               │
│                                                              │
│  1. Purpose — why this mechanic exists                       │
│  2. Architecture — managers, models, engine                  │
│  3. State Machine — state types, transitions                 │
│  4. Persistence — tables, columns, globals                   │
│  5. Notifications — what to send, to whom                    │
│  6. Client — managers, widgets, interaction                  │
│  7. Animation — what moves, when, how fast                   │
│  8. Testing — unit, integration, replay                      │
│  9. Scalability — how it grows with expansions               │
│ 10. Anti-Patterns — what to avoid                            │
│ 11. Reference Implementations                                │
│ 12. Implementation Checklist                                 │
│ 13. Testing Checklist                                        │
└─────────────────────────────────────────────────────────────┘
```

---

## 1. Worker Placement

**Purpose:** Players place limited workers on shared action spaces to claim actions or resources. Spaces may be blocked when occupied.

### Architecture

**Managers:** `Board` (owns placement spaces), `Workers` (owns worker pool per player), `Players` (owns worker count)

```
Workers Manager:         get(), place(), remove(), recall(), countActive()
Board Manager:           getSpace(), isOccupied(), occupy(), release(), getAvailableSpaces()
Players Manager:         getWorkerCount(), setWorkerCount()
```

**Model:** `WorkerSpace` (capacity, current workers, type), `Worker` (owner, location, state)

**Engine:** Not needed — each placement is a single action.

### State Machine

```
SELECT_SPACE (ACTIVE_PLAYER) → 'placed' → RESOLVE_EFFECT (GAME) → 'done' → NEXT
```

States: `SELECT_SPACE` (player chooses), `RESOLVE_EFFECT` (auto-resolve space effect), `RECALL_WORKERS` (optional phase end)

### Persistence

```sql
CREATE TABLE space (
    space_id INT AUTO_INCREMENT PRIMARY KEY,
    space_type VARCHAR(16) NOT NULL,
    capacity INT NOT NULL DEFAULT 1,
    player_id INT DEFAULT NULL,       -- NULL = available
    round_placed INT DEFAULT NULL
);

CREATE TABLE worker (
    worker_id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    space_id INT DEFAULT NULL,        -- NULL = in pool
    state VARCHAR(16) DEFAULT 'pool'
);
```

**Globals:** `currentRound`, `nextSpaceId`

### Notifications

```php
Notifications::workerPlaced($player, $spaceId);        // Public
Notifications::spaceEffect($player, $spaceType, $args); // Public
Notifications::workersRecalled($player, $count);        // Public
```

### Client

**Manager:** `BoardManager` (renders spaces, shows occupancy), `WorkerManager` (renders worker tokens)

**Interaction:** Click space → `performAction('actPlaceWorker', { spaceId })`

### Animation

- Worker token slides from player pool to board space (300ms)
- Effect resolution: resource fly to player panel (200ms)
- Recall: all worker tokens slide back to player pool (400ms staggered)

### Testing

```php
public function testCannotPlaceOnFullSpace(): void { ... }
public function testCannotPlaceMoreThanWorkerLimit(): void { ... }
public function testRecallReturnsAllWorkers(): void { ... }
```

### Anti-Patterns

- **Space as state machine** — don't create a state per space; use data-driven spaces
- **Hardcoded capacity** — store capacity in DB, not in PHP constants
- **Missing recall** — always provide a recall mechanism for end-of-round cleanup

### Reference

**Arnak (★★★★☆):** `location` table for board site data with `size/is_open/position`. Worker placement with travel costs. Standard pattern — one main action per turn.

### Checklist

- [ ] `space` table with `capacity` column
- [ ] `worker` table with `state` enum
- [ ] Atomic placement: `UPDATE space SET player_id = X WHERE space_id = Y AND player_id IS NULL`
- [ ] Worker limit enforced per player
- [ ] Recall mechanism at end of round/phase
- [ ] Travel costs (if applicable) validated before placement

---

## 2. Action Selection

**Purpose:** Players choose from a set of available actions, often parameterized by a mutable value (strength, position, cards).

### Architecture

**Managers:** `Actions` (defines available actions), `ActionState` (tracks selection state)

```
Actions Manager:    getAvailable(), executeAction($actionId, $params), isDoable()
```

**Model:** `Action` (id, type, strength, params, isAvailable)

**Engine:** Recommended when actions have dynamic parameters (ArkNova action card strength system).

### State Machine

```
SELECT_ACTION (ACTIVE_PLAYER) → 'chosen' → EXECUTE_ACTION (ACTIVE_PLAYER/GAME) → 'done'
```

### Persistence

```sql
CREATE TABLE action_state (
    action_id INT NOT NULL AUTO_INCREMENT,
    action_type VARCHAR(32) NOT NULL,
    strength INT NOT NULL DEFAULT 1,
    player_id INT DEFAULT NULL,
    round_used INT DEFAULT NULL
);
```

### Notifications

```php
Notifications::actionSelected($player, $actionType, $strength);
Notifications::actionExecuted($player, $actionType, $result);
```

### Reference

**ArkNova (★★★★★):** Five action cards parameterized by mutable strength (1-5). Strength modifies power: more cards drawn, larger buildings, etc. The `FlowConvertor.php` converts bonuses into Engine flow trees at runtime.

### Anti-Patterns

- **Action as state** — don't use states for each action variant; use a generic state + action type data
- **Hardcoded strength** — store strength values in DB, not in PHP constants

---

## 3. Simultaneous Turns

**Purpose:** Multiple players act at the same time without waiting for turn order.

### Architecture

**Managers:** `PrivateState` (per-player state machine), `ActionCommand` (command queue)

```
ActionCommandMgr:   apply($command), commit($playerId), undoLast($playerId), reevaluate()
PrivateState:       initialize($playerId), getState($playerId), transition($playerId, $action)
```

**Model:** `BaseActionCommand` (do(), undo(), commit(), reevaluate())

**Engine:** Not used. Command queue replaces Engine.

### State Machine

Use `MULTIPLE_ACTIVE_PLAYER` + `PRIVATE` states (Earth pattern):

```
SIMULTANEOUS_PHASE (MULTIPLE_ACTIVE_PLAYER)
  └── PRIVATE_STATE per player → actX → PRIVATE_STATE → actY → done
        → all done → parent transition
```

### Persistence

```sql
CREATE TABLE action_command (
    command_id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    command_type VARCHAR(32) NOT NULL,
    command_args JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    state VARCHAR(16) DEFAULT 'pending'  -- pending/committed/cancelled
);
```

### Concurrency

Use atomic conditional UPDATE or advisory locks (Earth's `Lock.php`):

```php
// Atomic claim
$affected = $this->DbQuery("UPDATE resource SET player_id = $pid
    WHERE resource_id = $rid AND player_id IS NULL");
if ($affected === 0) throw new \BgaUserException('Already taken');
```

### Notifications

Four notifier types (Earth pattern):

```php
Notifications::private($player, 'pendingAction', $args);   // Private preview
Notifications::public('committedAction', $args);             // Public commit
Notifications::private($player, 'undoAction', $args);       // Silent undo
Notifications::internal('stateChange', $args);               // No client update
```

### Client

**Managers:** `PendingActionMgr` (shows unconfirmed actions with indicator), `CommitMgr` (handles commit/undo UI)

### Testing

```php
public function testSimultaneousNoConflict(): void { ... }
public function testSimultaneousConflictResolved(): void { ... }
public function testReevaluateAfterCrossPlayerEffect(): void { ... }
```

### Reference

**Earth (★★★★★):** The only reference demonstrating true simultaneous play. Private state + command queue. `ActionCommandMgr` manages the queue lifecycle. `reevaluate()` handles cross-player invalidation.

### Checklist

- [ ] `MULTIPLE_ACTIVE_PLAYER` + `PRIVATE` state configuration
- [ ] `action_command` table with JSON args
- [ ] `BaseActionCommand` with `do()` / `undo()` / `commit()`
- [ ] Cross-player invalidation (`reevaluate()`)
- [ ] Locking for atomic claims (advisory lock or conditional UPDATE)
- [ ] Private notification for pending actions
- [ ] Commit phase replays through public notifier

---

## 4. Turn Order Manipulation

**Purpose:** Players can change who goes next through card effects, passing mechanics, or initiative tracks.

### Architecture

**Manager:** `TurnOrder` (owns turn sequence, initiative)

```
TurnOrder:   getCurrentPlayer(), getNextPlayer(), setOrder($playerIds),
             addPass($playerId), isTurnComplete(), advance()
```

### State Machine

```
PLAYER_TURN → 'pass' → CHECK_TURN_ORDER (GAME) → 'nextPlayer' → PLAYER_TURN
                                            → 'repeat' → PLAYER_TURN (same player)
```

### Persistence

```sql
CREATE TABLE turn_order (
    turn_id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    turn_position INT NOT NULL,
    has_passed TINYINT DEFAULT 0
);
```

**Globals:** `currentTurnIndex`, `currentPhase`

### Notifications

```php
Notifications::turnOrderChanged($newOrder);
Notifications::playerPassed($player);
Notifications::initiativeChanged($player, $newInitiative);
```

### Reference

**Agricola (★★★★★):** Turn order changes via occupation cards. The Engine manages turn progression through `SeqNode` sequences.

### Anti-Patterns

- **Index-based order** — don't use hardcoded array indices; use DB-backed turn order
- **Forgetting pass completion** — ensure all players passing triggers advancement

---

## 5. Card Drafting

**Purpose:** Players select cards from a limited pool, often passing remaining cards to the next player.

### Architecture

**Managers:** `Draft` (manages draft state per round), `Cards` (handles card pools)

```
Draft:   initialize($players), getHand($playerId), pickCard($playerId, $cardId),
         passRemaining(), isRoundComplete(), getNextRoundCards()
```

### State Machine

```
DRAFT_ROUND (MULTIPLE_ACTIVE_PLAYER)
  └── DRAFT_PICK (PRIVATE per player) → 'picked' → wait for all
    → all done → next round or done
```

### Persistence

```sql
CREATE TABLE draft_pool (
    pool_id INT AUTO_INCREMENT PRIMARY KEY,
    round INT NOT NULL,
    player_id INT NOT NULL,
    card_id INT NOT NULL,
    picked TINYINT DEFAULT 0
);
```

### Notifications

```php
Notifications::draftHand($player, $cardIds);   // Private — current hand
Notifications::draftPicked($player, $cardId);   // Public — card taken
```

### Client

**Interaction:** Click card → confirm pick. Show remaining cards face-up, own hand face-down.

### Reference

**Agricola (★★★★★):** Multiple draft modes: Living Hand, 7-of-10, simultaneous async, occupation-first, minor-first. Snake-opening variant. The most complete drafting system.

### Anti-Patterns

- **Revealing unpicked cards** — never expose remaining cards' identities to other players
- **Missing async support** — allow players to draft at different times

---

## 6. Deck Building

**Purpose:** Players start with a basic deck and acquire cards that get shuffled into their discard/draw pile.

### Architecture

**Managers:** `Deck` (owns all card locations), `Cards` (card definitions)

```
Deck:    draw($playerId, $count), shuffle($playerId), addToDiscard($playerId, $cardId),
         getDeck($playerId), getDiscard($playerId), getHand($playerId)
```

**Model:** `Card` (type, location, owner, state)

### State Machine

```
PLAYER_TURN (ACTIVE_PLAYER)
  → 'buyCard' → BUY_RESOLVE (GAME) → 'bought' → PLAYER_TURN
```

### Persistence

```sql
CREATE TABLE card (
    card_id INT AUTO_INCREMENT PRIMARY KEY,
    card_type VARCHAR(32) NOT NULL,
    card_location VARCHAR(16) NOT NULL,    -- deck/hand/play/discard/removed
    card_location_arg INT NOT NULL,         -- player_id or position
    extra_datas JSON DEFAULT NULL
);
```

Location-based approach: cards move through `deck → hand → play → discard → deck` (shuffle).

### Notifications

```php
Notifications::cardBought($player, $card);
Notifications::cardDrawn($player, $cards);     // Private — only player sees drawn cards
Notifications::deckShuffled($player);
```

### Client

**Manager:** `DeckManager` (renders deck/discard piles), `HandManager` (renders hand)

### Reference

**Arnak (★★★★☆):** Custom `card` table with `card_position` enum (hand, deck, play, discard, supply, earring, keep). Custom deck ordering. Material-driven card definitions.

### Anti-Patterns

- **Framework Deck for complex decks** — use custom tables when cards have per-card state
- **Deck order leaking** — never reveal deck order; use one notification for draw count, private for identities

---

## 7. Tableau Building

**Purpose:** Players build a personal array of cards that provide ongoing benefits.

### Architecture

**Managers:** `Tableau` (owns player tableaus)

```
Tableau:   get($playerId), playCard($playerId, $cardId, $position),
           activate($playerId, $cardId), getActiveCards($playerId)
```

**Model:** `TableauCard` (card, position, state: active/inactive/exhausted)

### State Machine

```
PLAY_CARD (ACTIVE_PLAYER) → 'played' → RESOLVE_PLAY (GAME) → 'resolved' → PLAYER_TURN
```

### Persistence

```sql
CREATE TABLE tableau_card (
    tableau_id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    card_id INT NOT NULL,
    position INT NOT NULL,
    state VARCHAR(16) DEFAULT 'active',    -- active/inactive/exhausted
    counters JSON DEFAULT NULL              -- game-specific counters
);
```

### Client

**Manager:** `TableauManager` (positions cards on player board, shows state)

### Animation

- Card slides from hand to tableau position (300ms)
- Activation: card glows, then returns to normal (500ms)
- Exhaust: card rotates or dims (200ms)

### Reference

**ArkNova (★★★★★):** 160 animal cards, 81 sponsor cards placed in zoo tableau. Cards support event listeners via `isListeningTo()`. Activation effects modify game state.

**Earth (★★★★★):** Tableau grid layout with `TableauMgr`. Cards have `sprout_count`, `growth_count`. Activation passes through tableau in player-chosen direction.

---

## 8. Hand Management

**Purpose:** Players draw, hold, and play cards from a personal hand. Hand size may be limited.

### Architecture

**Manager:** `Hand` (owns hand state per player), `Cards` (card location tracking)

```
Hand:    draw($playerId, $count), discard($playerId, $cardId), play($playerId, $cardId),
         getHand($playerId), count($playerId), getHandLimit($playerId)
```

### State Machine

```
PLAYER_TURN → 'playCard' → PLAY_CARD → 'done' → PLAYER_TURN
DISCARD_DOWN (ACTIVE_PLAYER) → 'discarded' → NEXT
```

### Persistence

Cards store `card_location = 'hand'` and `card_location_arg = $playerId`. Hand limit stored on `player` table.

### Notifications

```php
Notifications::cardDrawn($player, $cards);     // Private
Notifications::cardPlayed($player, $cardId);   // Public
Notifications::cardDiscarded($player, $cardId); // Public
```

### Client

**Manager:** `HandManager` — renders cards in a fan layout (BgaCards HandStock)

### Animation

- Draw: card slides from deck to hand (300ms)
- Play: card slides from hand to tableau (350ms)
- Discard: card slides from hand to discard pile (250ms)

### Testing

```php
public function testHandLimitEnforced(): void { ... }
public function testCannotPlayCardNotInHand(): void { ... }
public function testDrawFromEmptyDeckShufflesDiscard(): void { ... }
```

---

## 9. Resource Conversion

**Purpose:** Players trade resources at fixed or variable rates (e.g., 3 wood → 1 coin). May be one-way or two-way.

### Architecture

**Manager:** `Conversion` (owns conversion rules)

```
Conversion:   getAvailable($playerId), convert($playerId, $from, $to, $ratio),
              getRatio($from, $to), optimize($playerId, $desired)
```

**Model:** `ConversionRule` (from type, to type, ratio, isActive, isRepeatable)

### State Machine

```
CONVERT_PHASE (ACTIVE_PLAYER) → 'converted' → CONVERT_PHASE (repeat)
                              → 'done' → NEXT
```

### Persistence

```sql
CREATE TABLE conversion_rule (
    rule_id INT AUTO_INCREMENT PRIMARY KEY,
    from_type VARCHAR(16) NOT NULL,
    to_type VARCHAR(16) NOT NULL,
    ratio_from INT NOT NULL DEFAULT 1,
    ratio_to INT NOT NULL DEFAULT 1,
    repeatable TINYINT DEFAULT 1,
    max_per_turn INT DEFAULT NULL
);
```

### Notifications

```php
Notifications::conversion($player, $from, $to, $amount);
```

### Client

**Interaction:** Show dialog with drag-and-drop or button-based conversion. Display available ratios.

### Reference

**Earth (★★★★☆):** Sprout-to-soil conversion with ratio scaling (3→2, 6→4, 9→6). Seed germination (convert seed to sprout). Dedicated conversion private states.

### Anti-Patterns

- **Hardcoded ratios** — store ratios in DB, not in PHP constants
- **Missing optimization** — provide "convert max" button for repeated conversions

---

## 10. Engine Building

**Purpose:** Players acquire synergistic components that produce compounding effects over time.

### Architecture

**Manager:** `Engine` (owns effect resolution), but more commonly handled through triggered effects (see §23). The Engine pattern (Agricola/ArkNova) IS the implementation — see [state-machine-architecture.md §9.3](./state-machine-architecture.md#93-approach-b-engine-tree-agricolaarknova).

```
Engine:     buildTree($playerId), resolve(), getNextNode(), isResolved()
CardListener: beforeAction($action), afterAction($action), replaceAction($action)
```

### Domain Model

`SeqNode` / `OrNode` / `XorNode` / `ParallelNode` / `LeafNode` decision tree. Cards register listeners that modify the tree.

### State Machine

Engine-based games use few generic states:

```
ST_RESOLVE_STACK → ST_RESOLVE_CHOICE → ST_GAIN → ST_PAY → ST_CONFIRM_TURN
```

### Persistence

Engine tree serialised to globals as JSON blob:

```php
Globals::setEngine($tree->toArray());
$tree = Tree::fromArray(Globals::getEngine());
```

### Reference

**Agricola (★★★★★):** Occupations and minor improvements are separate PHP classes that register listeners. `before<Action>()` and `computeReplace<Action>()` hooks modify the Engine flow tree.

**ArkNova (★★★★★):** Extends with `FlowConvertor.php` — converts arbitrary bonuses into Engine flow trees at runtime.

### Anti-Patterns

- **Engine as state machine** — Engine defines flow; state machine defines permissions
- **Business logic in Engine nodes** — Engine coordinates; Managers execute

---

## 11. Market Systems

**Purpose:** A shared pool of resources or cards that players can purchase, often with variable pricing or limited supply.

### Architecture

**Manager:** `Market` (owns supply, pricing, availability)

```
Market:  getAvailable(), getPrice($itemId), purchase($playerId, $itemId),
         refresh($count), isEmpty(), isDirty()
```

### State Machine

```
MARKET_PHASE (ACTIVE_PLAYER) → 'bought' → MARKET_PHASE (repeat)
                             → 'done' → NEXT
```

### Persistence

```sql
CREATE TABLE market_item (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_type VARCHAR(32) NOT NULL,
    stock INT NOT NULL DEFAULT 1,
    base_price INT NOT NULL,
    current_price INT NOT NULL,
    refresh_round INT DEFAULT NULL
);
```

### Notifications

```php
Notifications::marketPurchased($player, $itemId, $price);
Notifications::marketRefreshed($items);
```

### Concurrency

Use atomic conditional UPDATE for simultaneous market access:

```php
$affected = $this->DbQuery("UPDATE market_item SET stock = stock - 1
    WHERE item_id = $id AND stock > 0");
```

### Anti-Patterns

- **Read-then-write for stock** — always use `SET stock = stock - 1 WHERE stock > 0`
- **Price update after sell** — validate player can afford BEFORE writing

---

## 12. Auctions

**Purpose:** Players bid on items. Highest bidder wins and pays their bid.

### Architecture

**Manager:** `Auction` (owns round state, bids)

```
Auction:    start($itemId), bid($playerId, $amount), pass($playerId),
            getHighBid(), getWinner(), isComplete()
```

### State Machine

```
AUCTION_ROUND (MULTIPLE_ACTIVE_PLAYER)
  └── AUCTION_BID (PRIVATE) → 'bid' → 'pass' → resolve when all passed

AUCTION_RESOLVE (GAME) → 'sold' → NEXT
```

### Persistence

```sql
CREATE TABLE auction_bid (
    bid_id INT AUTO_INCREMENT PRIMARY KEY,
    auction_id INT NOT NULL,
    player_id INT NOT NULL,
    amount INT NOT NULL,
    passed TINYINT DEFAULT 0
);
```

### Notifications

```php
Notifications::bidPlaced($player, $amount);     // Public (open auctions)
Notifications::bidPlacedPrivate($player);        // Private (closed auctions)
Notifications::auctionWon($player, $item, $amount);
```

### Client

**Interaction:** Bid amount slider/input + confirm. Display current high bid.

### Anti-Patterns

- **Forcing bids** — always provide a pass option
- **Bid increments** — allow any valid amount, not just fixed increments

---

## 13. Hidden Information

**Purpose:** Information that is known to one player but hidden from others (hand contents, secret objectives, face-down cards).

### Architecture

**Managers:** `Hand` (private — filter by player), `HiddenState` (per-player secrets)

Access control is enforced at three layers:

**Server layer:** `getAllDatas()` returns private data only for the calling player

**Notification layer:** `notifyPlayer()` + `_private` key filters by recipient

**Database layer:** Tables with `player_id` column implicitly scope queries

### Persistence

```sql
CREATE TABLE hidden_objective (
    objective_id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,         -- Scope: only this player knows
    objective_type VARCHAR(32) NOT NULL,
    completed TINYINT DEFAULT 0
);
```

### Notifications

Dual notification pattern (ArkNova):

```php
// Public: everyone sees the count
$this->notifyAllPlayers('drawCards', clienttranslate('${player_name} draws ${n} cards'), [
    'player_id' => $player->getId(),
    'n' => count($cards),
]);

// Private: only drawing player sees card identities
$this->notifyPlayer($player->getId(), 'pDrawCards', '', [
    'cards' => $cards,
]);
```

Or single notification with `_private`:

```php
$this->notifyAllPlayers('drawCards', clienttranslate('${player_name} draws cards'), [
    'player_id' => $player->getId(),
    '_private' => [$player->getId() => ['cards' => $cards]],
]);
```

### Client

```js
notif_pDrawCards(notif) {
    if (this.bga.players.isCurrentPlayerSpectator()) return;
    this.handMgr.addCards(notif.args.cards);
}
```

### Reference

**ArkNova (★★★★★):** Dual pattern: public notification ("Paul draws 2 cards") + private notification with actual card data ("You draw Tiger, Amazon Tree Boa").

---

## 14. Variable Player Powers

**Purpose:** Each player has unique abilities, starting conditions, or asymmetric objectives.

### Architecture

**Manager:** `PlayerPower` (owns power definitions and state)

```
PlayerPower:    get($playerId), activate($playerId, $powerId),
                getAvailablePowers($playerId), isActive($playerId, $powerType)
```

**Model:** `PlayerPower` (playerId, powerType, state, params)

### State Machine

Powers modify existing states rather than creating new ones:

```php
// In PLAYER_TURN state
public function actPlayCard(int $cardId, int $activePlayerId): string
{
    // Base check
    $this->game->cards->validatePlay($cardId, $activePlayerId);

    // Power override check
    $this->game->powers->onBeforePlayCard($activePlayerId, $cardId);

    // Execute
    $this->game->cards->playCard($cardId, $activePlayerId);
    return 'cardPlayed';
}
```

### Persistence

```sql
CREATE TABLE player_power (
    power_id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    power_type VARCHAR(32) NOT NULL,
    state VARCHAR(16) DEFAULT 'active',
    uses_remaining INT DEFAULT NULL,
    params JSON DEFAULT NULL
);
```

### Notifications

```php
Notifications::powerActivated($player, $powerType, $params);
```

### Reference

**Earth (★★★★★):** Each card has abilities that trigger on events. `Ability.php` — full ability/effect system. Cards can gain resources, require payments, copy other cards, or modify game state.

**Arnak (★★★★☆):** 20 unique assistants with different abilities. `assistant` table with ready/gold/offer state.

### Anti-Patterns

- **Power-specific states** — don't create states per power; use hooks/managers
- **Hardcoded power behaviour** — use strategy pattern or data-driven effects

---

## 15. Tile Placement

**Purpose:** Players place tiles on a shared or personal board following placement rules.

### Architecture

**Managers:** `Board` (owns grid), `Tiles` (owns tile definitions and placements)

```
Board:  getCell($x, $y), isValidPlacement($x, $y, $tileType), place($x, $y, $tileType, $playerId),
        getAdjacent($x, $y), getNeighbors($x, $y), getPlacementScore($playerId)
```

**Model:** `Tile` (type, owner, position, state), `BoardCell` (position, occupant)

### State Machine

```
PLACE_TILE (ACTIVE_PLAYER) → 'placed' → RESOLVE_TILE (GAME) → 'done' → PLAYER_TURN
```

### Persistence

```sql
CREATE TABLE board_cell (
    board_id INT NOT NULL,
    x INT NOT NULL,
    y INT NOT NULL,
    tile_id INT DEFAULT NULL,
    owner_id INT DEFAULT NULL,
    PRIMARY KEY (board_id, x, y)
);
```

### Client

**Manager:** `BoardManager` — renders grid, highlights valid placements, animates tile placement

**Interaction:** Click cell to place, drag tile to cell, or select tile then click cell.

### Animation

- Tile slides from hand to board position (300ms)
- Placement effect (glow, scale) on snap (200ms)

### Reference

**ArkNova (★★★★★):** Full hex grid system with cube coordinates (x:0-8, y:1-11). `ZooMap.php` (1275 lines). Building placement validation with adjacency rules, terrain constraints, enclosure management.

**Agricola (★★★★☆):** Farmyard grid layout with x/y coordinates. Drop zones for animal placement.

---

## 16. Route Building

**Purpose:** Players build paths, roads, or connections between locations on a board.

### Architecture

**Manager:** `Route` (owns connections)

```
Route:  connect($playerId, $fromId, $toId), isConnected($playerId, $fromId, $toId),
        getNetwork($playerId), getLongestRoute($playerId), isValidConnection($fromId, $toId)
```

### Persistence

```sql
CREATE TABLE route_segment (
    segment_id INT AUTO_INCREMENT PRIMARY KEY,
    from_location INT NOT NULL,
    to_location INT NOT NULL,
    owner_id INT DEFAULT NULL,
    route_type VARCHAR(16) NOT NULL
);
CREATE TABLE location (
    location_id INT AUTO_INCREMENT PRIMARY KEY,
    x INT NOT NULL,
    y INT NOT NULL,
    location_type VARCHAR(32) NOT NULL
);
```

### State Machine

```
BUILD_ROUTE (ACTIVE_PLAYER) → 'built' → BUILD_ROUTE (repeat)
                            → 'done' → NEXT
```

### Notifications

```php
Notifications::routeBuilt($player, $fromId, $toId);
```

### Testing

```php
public function testCannotBuildOverlappingRoute(): void { ... }
public function testLongestRouteCalculation(): void { ... }
public function testRouteMustConnectToExisting(): void { ... }
```

---

## 17. Area Control

**Purpose:** Players compete for influence over regions on a board. Majority or presence determines control.

### Architecture

**Manager:** `Area` (owns regions and influence)

```
Area:   getRegion($regionId), placeInfluence($playerId, $regionId),
        getInfluence($regionId), getController($regionId),
        getRegionsByPlayer($playerId), isAdjacent($regionA, $regionB)
```

### Persistence

```sql
CREATE TABLE region_influence (
    region_id INT NOT NULL,
    player_id INT NOT NULL,
    influence INT NOT NULL DEFAULT 0,
    PRIMARY KEY (region_id, player_id)
);
```

### Scoring

Compute area majority at scoring time:

```php
public function scoreAreas(): array
{
    $scores = [];
    foreach ($this->getAllRegions() as $region) {
        $controller = $this->getController($region['id']);
        if ($controller) {
            $scores[$controller] += $region['points'];
        }
    }
    return $scores;
}
```

### Notifications

```php
Notifications::influencePlaced($player, $regionId, $amount);
Notifications::areaControlChanged($regionId, $newController);
```

---

## 18. Majority Scoring

**Purpose:** At scoring intervals, the player(s) with the most of something (points, pieces, cards) earn rewards.

### Architecture

**Manager:** `Majority` (owns scoring rules and computation)

```
Majority:   compute($category), getLeader($category), getTiers($category),
            score($category), getAllCategories()
```

### State Machine

```
SCORE_MAJORITY (GAME) → 'scored' → NEXT
```

### Persistence

```sql
CREATE TABLE majority_category (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_type VARCHAR(32) NOT NULL,
    tier_1_points INT NOT NULL DEFAULT 5,
    tier_2_points INT NOT NULL DEFAULT 3,
    tier_3_points INT NOT NULL DEFAULT 1
);
```

### Notifications

```php
Notifications::majorityScored($category, $rankings);
```

### Testing

```php
public function testTieBreaksCorrectly(): void { ... }
public function testNoPointsWhenNoParticipation(): void { ... }
public function testMultipleTiersAwardedCorrectly(): void { ... }
```

---

## 19. Set Collection

**Purpose:** Players collect groups of items that score together. Sets may be fixed or variable.

### Architecture

**Manager:** `Collection` (owns set definitions and completion tracking)

```
Collection: getSets($playerId), isComplete($playerId, $setId),
            completeSet($playerId, $setId), getScore($playerId, $setId),
            getAvailable($playerId)
```

### Persistence

```sql
CREATE TABLE collected_set (
    set_id INT NOT NULL,
    player_id INT NOT NULL,
    completed TINYINT DEFAULT 0,
    completed_round INT DEFAULT NULL,
    PRIMARY KEY (set_id, player_id)
);

CREATE TABLE set_definition (
    set_id INT AUTO_INCREMENT PRIMARY KEY,
    set_type VARCHAR(32) NOT NULL,
    required_count INT NOT NULL,
    points INT NOT NULL,
    bonus_type VARCHAR(32) DEFAULT NULL
);
```

### Notifications

```php
Notifications::setCompleted($player, $setId, $points);
Notifications::setProgress($player, $setId, $progress);
```

---

## 20. Contracts and Objectives

**Purpose:** Players fulfil specific conditions to earn points or rewards. Contracts may be public or private.

### Architecture

**Manager:** `Contracts` (owns contract definitions and completion)

```
Contracts:  getActive($playerId), complete($playerId, $contractId),
            getReward($contractId), isFulfillable($playerId, $contractId),
            reveal($contractId), getPublicContracts()
```

### Persistence

```sql
CREATE TABLE contract (
    contract_id INT AUTO_INCREMENT PRIMARY KEY,
    contract_type VARCHAR(32) NOT NULL,
    visibility VARCHAR(16) DEFAULT 'private',  -- public/private
    player_id INT DEFAULT NULL,                  -- NULL = unclaimed
    completed TINYINT DEFAULT 0,
    completed_round INT DEFAULT NULL
);
```

### State Machine

```
CHECK_CONTRACTS (GAME) → check each contract against game state
  → 'completed' → reward player → continue
  → 'none' → NEXT
```

### Notifications

```php
Notifications::contractCompleted($player, $contractId, $reward);
Notifications::contractRevealed($player, $contractId);
```

### Reference

**ArkNova (★★★★★):** 39 conservation project cards with specific fulfilment conditions. Dynamic scoring tracked via notification delta system.

**Earth (★★★★★):** Event cards, fauna objectives, ecosystem cards with completion tracking. `player_score` table stores per-card scoring breakdowns.

---

## 21. Technology Trees

**Purpose:** Players advance along tracks or trees, unlocking bonuses and capabilities at each level.

### Architecture

**Manager:** `TechTree` (owns tracks and progress)

```
TechTree:   getTrack($trackId), advance($playerId, $trackId, $steps),
            getLevel($playerId, $trackId), getBonus($trackId, $level),
            canAdvance($playerId, $trackId), getMaxLevel($trackId)
```

### Persistence

```sql
CREATE TABLE tech_track (
    track_id INT NOT NULL,
    player_id INT NOT NULL,
    level INT NOT NULL DEFAULT 0,
    PRIMARY KEY (track_id, player_id)
);

CREATE TABLE tech_bonus (
    bonus_id INT AUTO_INCREMENT PRIMARY KEY,
    track_id INT NOT NULL,
    level INT NOT NULL,
    bonus_type VARCHAR(32) NOT NULL,
    bonus_args JSON NOT NULL
);
```

### State Machine

```
ADVANCE_TECH (ACTIVE_PLAYER) → 'advanced' → RESOLVE_BONUS (GAME) → 'done' → PLAYER_TURN
```

### Notifications

```php
Notifications::techAdvanced($player, $trackId, $newLevel, $bonus);
```

### Reference

**Arnak (★★★★★):** Three research tracks (compass/tablet/arrowhead) with unlockable bonuses. Temple rank progression with guardian combat. `research_bonus` table for per-position bonuses.

---

## 22. Multi-Step Actions

**Purpose:** A single player action that requires multiple sequential decisions (choose card → choose target → pay cost → resolve effect).

### Architecture

Three approaches — see [game-flow-architecture.md §9](./game-flow-architecture.md#9-multi-step-action-execution):

| Approach | When to Use | Reference |
|---|---|---|
| **Dedicated States** | Simple, fixed steps | Arnak |
| **Engine Tree** | Dynamic steps, card-driven | Agricola, ArkNova |
| **Command Queue** | Simultaneous, undoable steps | Earth |

### Dedicated States (Arnak)

```
SELECT_CARD → PAY_COST → CHOOSE_TARGET → RESOLVE → PLAYER_TURN
```

Each step is a separate state. Simple but explodes with complex actions.

### Engine Tree (Agricola/ArkNova)

```
SeqNode: [PayCost, GainResources, DiscardCard, ChooseOptional]
```

Engine walks the tree, pausing at OrNode for player choices. See [domain-architecture.md §10](./domain-architecture.md#10-engine-interaction).

### Command Queue (Earth)

```php
$cmd = new ComplexActionCommand($playerId, $params);
$cmd->do($notifier);     // Apply to private state
ActionCommandMgr::saveOne($cmd, $notifier);
```

### Testing

```php
public function testMultiStepCompletesAllSteps(): void { ... }
public function testMultiStepCanUndoToBeginning(): void { ... }
public function testStepValidationRejectsInvalidChoice(): void { ... }
```

---

## 23. Triggered Effects

**Purpose:** Effects that fire when specific conditions are met (card played, resource gained, round ends). May chain (effect triggers another effect).

### Architecture

**Manager:** `TriggerManager` (owns effect registration and dispatch)

```
TriggerManager:  register($event, $handler, $priority), dispatch($event, $context),
                 unsubscribe($handler), hasTriggers($event)
```

### Implementation Pattern

```php
class Cards extends Manager
{
    public function playCard(int $cardId, int $playerId): Card
    {
        $card = $this->get($cardId);
        $this->DbQuery("UPDATE card SET ... WHERE card_id = $cardId");

        // Dispatch event — triggered effects fire here
        $this->game->dispatchEvent('cardPlayed', [
            'card' => $card,
            'player_id' => $playerId,
        ]);

        return $this->get($cardId);
    }
}
```

### Preventing Infinite Chains

```php
class TriggerManager
{
    private int $chainDepth = 0;
    private const MAX_DEPTH = 10;

    public function dispatch(string $event, array $context): void
    {
        $this->chainDepth++;
        if ($this->chainDepth > self::MAX_DEPTH) {
            $this->chainDepth = 0;
            throw new \BgaSystemException("Trigger chain exceeded max depth");
        }
        // ... process triggers ...
        $this->chainDepth--;
    }
}
```

### Reference

**Agricola (★★★★★):** Card reactions via `before<Action>()` and `computeReplace<Action>()` listener methods. Cards inject nodes into the Engine flow tree.

**Earth (★★★★★):** `Ability.php` — abilities trigger on plant, compost, water, grow, activate events. Games can gain resources, require payments, copy cards.

### Testing

```php
public function testTriggerFiresOnEvent(): void { ... }
public function testTriggerChainTerminates(): void { ... }
public function testMaxChainDepthPreventsInfiniteLoop(): void { ... }
```

---

## 24. Event Systems

**Purpose:** Game-wide events that affect all players — round events, random events, milestone events.

### Architecture

**Manager:** `Events` (owns event deck, triggers, and resolution)

```
Events: draw(), resolve($eventId), getActive(), discard($eventId),
        getCurrentEvent(), schedule($eventType, $trigger)
```

### State Machine

```
EVENT_PHASE (GAME) → draw event → resolve event → NEXT
```

### Persistence

```sql
CREATE TABLE game_event (
    event_id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(32) NOT NULL,
    event_state VARCHAR(16) DEFAULT 'pending',   -- pending/active/resolved
    round_drawn INT NOT NULL,
    round_resolved INT DEFAULT NULL
);
```

### Notifications

```php
Notifications::eventRevealed($event);
Notifications::eventResolved($event, $effects);
```

### Client

**Manager:** `EventManager` — shows active event, animates event resolution

### Reference

**Earth (★★★★★):** Event cards can be played during any phase. Event flow interrupts the current action, processes the event, then resumes. `return_from_event_state_id` stores the return point.

**Agricola (★★★★★):** Harvest events, seasonal events trigger at fixed intervals. Engine processes them through the decision tree.

---

## 25. End Game Detection

**Purpose:** Determine when the game ends — triggered by conditions, round limits, or player actions.

### Architecture

**Manager:** `EndGame` (owns end conditions)

```
EndGame: checkAll(), isTriggered(), getTriggerReason(),
         getRankings(), trigger($reason)
```

### State Machine

```
CHECK_END (GAME) → 'endGame' → SCORE_FINAL (GAME) → 'done' → GAME_END (99)
                 → 'continue' → NEXT_PLAYER
```

### Implementation

```php
class CheckEndGame extends GameState
{
    public function action(int $activePlayerId, array $args): string
    {
        if ($this->game->endGame->isTriggered()) {
            return 'endGame';
        }
        return 'continue';
    }
}
```

### Persistence

```sql
CREATE TRIGGER condition on player table or globals:
- max_rounds stored in Globals::get('maxRounds')
- check after every action in CHECK_END state
```

### Notifications

```php
Notifications::endGameTriggered($reason);
Notifications::finalScore($rankings);
```

### Reference

**Arnak (★★★★☆):** Two-phase end: scoring state (id 98) → gameEnd (id 99). Pre-end-game logic handles last-round completion.

**Earth (★★★★★):** `LastRound.php` handles end-game detection. Clean phase transitions.

### Testing

```php
public function testEndDetectedAtCorrectRound(): void { ... }
public function testEndDetectedOnConditionMet(): void { ... }
public function testGameEndsAtState99(): void { ... }
```

---

## 26. Scoring Systems

**Purpose:** Compute and track player scores throughout the game and at end-game.

### Architecture

**Manager:** `Scoring` (owns score computation)

```
Scoring:   getScore($playerId), addScore($playerId, $points, $category),
           computeEndGame(), getBreakdown($playerId), getRankings(),
           getTiebreaker($playerId)
```

### Persistence

```sql
-- Primary score on player table
ALTER TABLE player ADD COLUMN player_score INT DEFAULT 0;
ALTER TABLE player ADD COLUMN player_score_aux INT DEFAULT 0;  -- Tiebreaker

-- Detailed breakdown (optional, Earth pattern)
CREATE TABLE player_score_detail (
    detail_id INT AUTO_INCREMENT PRIMARY KEY,
    player_id INT NOT NULL,
    category VARCHAR(32) NOT NULL,    -- cards/events/bonuses/majorities
    points INT NOT NULL,
    source_id INT DEFAULT NULL         -- FK to scoring source
);
```

### Notifications

```php
Notifications::scoreUpdated($player, $newScore, $category, $points);
Notifications::finalScore($rankings);
```

ArkNova delta pattern: attach `infos` with score changes to every notification:

```php
self::updateIfNeeded($data, 'score', 'public');  // Attaches to current notification
```

### Client

**Manager:** `ScoreManager` — renders score panel, animates counter changes

**Animation:** Counter animates from old value to new value via `ebg.counter.toValue()`.

### Reference

**ArkNova (★★★★★):** Dynamic score calculation from appeal + conservation + final scoring cards. Continuous score updates via notification delta system.

**Earth (★★★★★):** `player_score` table stores per-card scoring breakdowns. `Score.php` computes comprehensive end-game totals.

### Anti-Patterns

- **Score stored in globals** — store on player table, not in globals
- **Missing tiebreaker** — always define a tiebreaker column (`player_score_aux`)
- **Recomputing from scratch** — use incremental updates for live scoring

---

## 27. Undo-Safe Mechanics

**Purpose:** Ensure every action can be reversed without corrupting game state.

### Architecture

Two strategies — see [notification-patterns.md §12](./notification-patterns.md#12-undo-interactions):

**Approach A — Gamelog Cancellation (Agricola/ArkNova):**

```
Log table records every DB mutation.
On undo: reverse mutations → cancel gamelog packets → refreshUI
```

**Approach B — Command Pattern (Earth):**

```
Every action is a BaseActionCommand with do()/undo().
On undo: pop command → call undo() → emit undo notifications
```

### Persistence

```sql
-- Agricola/ArkNova pattern
CREATE TABLE log (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    log_player_id INT NOT NULL,
    log_action VARCHAR(32) NOT NULL,
    log_args JSON NOT NULL,
    log_round INT NOT NULL,
    log_turn INT NOT NULL,
    log_sequence INT NOT NULL,
    log_cancel TINYINT DEFAULT 0
);

-- Earth pattern — see action_command table (Simultaneous Turns §3)
```

### Requirements for Undo Safety

Every mutation must:
1. Log enough information to reverse it (old values, new values)
2. Be reversible by a deterministic inverse operation
3. Be part of a single atomic transaction (implicit rollback handles errors)

```php
public function spendResource(int $playerId, string $type, int $amount): void
{
    $oldValue = $this->getResource($playerId, $type);
    $newValue = $oldValue - $amount;

    $this->DbQuery("UPDATE player SET $type = $newValue WHERE player_id = $playerId");

    Log::add('resourceChange', [
        'player_id' => $playerId,
        'type' => $type,
        'old' => $oldValue,
        'new' => $newValue,
    ]);
}
```

### What Cannot Be Undone

- Shuffles (deck order after shuffle)
- Random draws (cards already seen)
- External side effects (none in BGA)
- Cross-player actions where other players made decisions based on the new state

### Reference

**Agricola (★★★★★):** Full multi-level undo via `Log.php`. Checkpoint/step granularity. Gamelog packet cancellation.

**Earth (★★★★★):** Per-action undo via command pattern. `reevaluate()` for cross-player invalidation.

### Checklist

- [ ] All mutations log old values
- [ ] Undo reverses mutations in reverse order
- [ ] Gamelog packets cancelled on undo
- [ ] `refreshUI` + `refreshHand` sent after undo
- [ ] Checkpoints prevent undo across committed actions
- [ ] Cross-player effects have special undo handling

---

## 28. Replay-Safe Mechanics

**Purpose:** Ensure the game can be reconstructed from the gamelog at any point — for reconnect, archive replay, and debugging.

### Architecture

Replay safety is an architectural property, not a feature:

**`getAllDatas()`** returns a complete snapshot from any player's perspective at any state.

**Notifications** are the incremental delta between states. Replaying them from any snapshot produces the correct new state.

**Handler idempotency** ensures replaying the same notification twice does not corrupt state.

### Key Rules

1. **Every notification is idempotent** — running `notif_X` twice produces the same result as running it once

```js
notif_cardPlayed(notif) {
    let el = document.getElementById(`card-${notif.args.card_id}`);
    if (!el) {
        el = document.createElement('div');
        el.id = `card-${notif.args.card_id}`;
    }
    // Position is always set correctly regardless of prior state
    this.positionCard(el, notif.args.target_location);
}
```

2. **Notifications contain absolute values, not just deltas** — final score, not "+5 points"

3. **Randomness is seeded and stored** — the same seed produces the same game

```php
class SeedManager
{
    public static function initialize(): void
    {
        $seed = Globals::get('gameSeed');
        if ($seed === null) {
            $seed = random_int(0, PHP_INT_MAX);
            Globals::set('gameSeed', $seed);
        }
        srand($seed);
    }
}
```

4. **`refreshUI` shortcut** allows skipping notification replay on reconnect

### Testing Replay Safety

```php
public function testReplayProducesSameState(): void
{
    $initialState = $this->game->getAllDatas(1);
    $notifications = $this->captureNotifications();

    // Execute actions
    $this->executeAction('actPlayCard', ['cardId' => 42]);

    // Reset to initial state
    $this->restoreState($initialState);

    // Replay notifications
    foreach ($notifications as $n) {
        $this->client->processNotification($n);
    }

    // Final state must match
    $this->assertEquals($this->game->getAllDatas(1), $initialState);
}
```

### Reference

**Agricola (★★★★★):** `refreshUI` + `refreshHand` + gamelog packet cancellation. Gold standard for reconnection reliability.

### Checklist

- [ ] All notification handlers are idempotent
- [ ] Element creation checks for existing elements
- [ ] Notifications contain absolute values (not just deltas)
- [ ] Randomness is seeded and deterministic
- [ ] `refreshUI` is implemented for reconnection
- [ ] Replay from any state produces correct final state
- [ ] No domain logic runs during notification replay

---

## Common Patterns Across Mechanics

### Concurrency Guard Pattern

For any shared resource claimed by players:

```php
$affected = $this->DbQuery(
    "UPDATE resource SET player_id = $playerId
     WHERE resource_id = $resourceId AND player_id IS NULL"
);
if ($affected === 0) {
    throw new \BgaUserException(clienttranslate('That resource is no longer available'));
}
```

### Game Mediator Pattern

For cross-manager interaction during complex effects:

```php
// In Game.php
public function onCardPlayed(int $cardId, int $playerId): void
{
    $card = $this->cards->get($cardId);
    $this->players->addResources($playerId, $card->getBonus());
    $this->board->applyEffect($card->getEffect(), $playerId);
    $this->dispatchEvent('cardPlayed', ['card' => $card, 'player_id' => $playerId]);
}
```

### Engine Hook Pattern (Agricola)

For card-driven modifications to action flow:

```php
// Card registers listener
$listener = new BeforeActionHook('actPlayCard', function ($args) {
    // Modify args, replace action, add bonus steps
});
Engine::registerListener($listener);
```

### Material-Driven Definition Pattern (Arnak)

```php
// material.inc.php
$this->cards = [
    'farm' => ['name' => clienttranslate('Farm'), 'cost' => ['coin' => 2]],
    'pasture' => ['name' => clienttranslate('Pasture'), 'cost' => ['wood' => 1]],
];

// Manager reads from material
public function getCardCost(string $type): Resources
{
    $material = Game::get()->getMaterial();
    return Resources::fromArray($material['cards'][$type]['cost']);
}
```

---

## References

- [domain-architecture.md](../standards/domain-architecture.md) — layered architecture (§2), manager pattern (§4), engine interaction (§10)
- [persistence-architecture.md](../standards/persistence-architecture.md) — table design (§5), transactions (§9), concurrency (§10)
- [state-machine-architecture.md](../standards/state-machine-architecture.md) — state types (§3), private states (§12), multi-active (§13)
- [action-architecture.md](../standards/action-architecture.md) — validation layers (§6), error handling (§12), thin action (§5)
- [client-ui-architecture.md](../standards/client-ui-architecture.md) — manager pattern (§5), widget architecture (§6), dialogs (§9)
- [client-synchronization-architecture.md](../standards/client-synchronization-architecture.md) — notifications (§4), reconnect (§6), spectators (§7)
- [animation-architecture.md](../standards/animation-architecture.md) — animation lifecycle (§2), fast mode (§11), BgaCards (§9)
- [testing-debugging-architecture.md](../standards/testing-debugging-architecture.md) — testing pyramid (§2), replay (§9), checklists (§15)
- [notification-patterns.md](../standards/notification-patterns.md) — payload design (§5), public/private (§3), undo (§12), fast mode (§13.4)
- [project-architecture.md](../standards/project-architecture.md) — naming conventions (§7), expansions (§8), anti-patterns (§13)
- [game-flow-architecture.md](../standards/game-flow-architecture.md) — multi-step actions (§9), transaction boundaries (§5), undo (§11)
- [reference-project-analysis.md](../foundation/reference-project-analysis.md) — project ratings, mechanic-specific strengths
