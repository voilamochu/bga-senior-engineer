# BGA Persistence Architecture — Engineering Standard

**Document purpose:** Define the complete persistence model for Board Game Arena projects. Cover how game state is stored, read, written, rolled back, migrated, and archived. Establish canonical patterns for table design, entity lifecycle, query strategy, concurrency, and transaction management.

**Applicability:** All new BGA game implementations. Existing projects should use this document as a reference when refactoring database access, adding tables, or diagnosing persistence-related issues.

**Cross-references:**
- [domain-architecture.md](./domain-architecture.md) — manager ownership, entity lifecycle, aggregates, dependency rules
- [game-flow-architecture.md](./game-flow-architecture.md) — execution pipeline, transaction model
- [action-architecture.md](./action-architecture.md) — validation-before-mutate, action lifecycle
- [state-machine-architecture.md](./state-machine-architecture.md) — state IDs, state args, state persistence
- [notification-patterns.md](./notification-patterns.md) — gamelog table, notification persistence, undo patterns
- [reference-project-analysis.md](../foundation/reference-project-analysis.md) — database patterns per project
- [bga-developer-handbook.md](../foundation/bga-developer-handbook.md) — framework API, Deck, globals

---

## Table of Contents

- [1. Purpose of Persistence](#1-purpose-of-persistence)
- [2. Persistence Philosophy](#2-persistence-philosophy)
- [3. Persistence Layers](#3-persistence-layers)
- [4. Database Architecture](#4-database-architecture)
- [5. Table Design](#5-table-design)
- [6. Entity Persistence Lifecycle](#6-entity-persistence-lifecycle)
- [7. Globals Persistence](#7-globals-persistence)
- [8. Deck Persistence](#8-deck-persistence)
- [9. Transactions](#9-transactions)
- [10. Concurrency](#10-concurrency)
- [11. Undo Implications](#11-undo-implications)
- [12. Performance](#12-performance)
- [13. Scaling](#13-scaling)
- [14. Anti-Patterns](#14-anti-patterns)
- [15. Migration Strategy](#15-migration-strategy)
- [16. Templates](#16-templates)
- [17. Checklists](#17-checklists)

---

## 1. Purpose of Persistence

Persistence in a BGA project serves five distinct purposes:

1. **Game state** — every card position, resource count, board configuration, and scoring datum must survive between HTTP requests
2. **Turn integrity** — the database must reflect exactly one consistent state per request after commit, and zero invalid partial states if a request fails
3. **Audit trail** — every action is recorded in the gamelog, enabling replay, undoing, and post-game analysis
4. **Reconnection** — a player who refreshes the page must see the exact current game state reconstructed from the database
5. **Archive** — completed games are stored permanently in the gamelog and stats tables, enabling the framework's archive replay feature

These purposes impose strict constraints on how persistence is designed. The database is not just a storage layer — it is the sole source of truth for game state, the rollback boundary for error recovery, and the synchronization mechanism between the server and all connected clients.

---

## 2. Persistence Philosophy

### 2.1 Server Authority

The server is the sole authority for all game state. Every persistence operation originates from server-side PHP code. The client never writes to the database. See [game-flow-architecture.md §6](./game-flow-architecture.md#6-server-authority).

**Enforcement mechanisms:**
- `checkAction()` prevents unauthorised actions
- The implicit transaction rolls back any malformed request
- The framework's routing ensures only declared actions reach game logic

### 2.2 Request Statelessness

Every HTTP request constructs a fresh PHP instance:

```php
// Request 1: PHP reads DB state, executes, writes, PHP destroyed
// Request 2: PHP reads DB state (as left by request 1), executes, writes, PHP destroyed
```

Nothing persists in memory between requests. No static cache, no singleton state, no in-memory session data. This means:

**All state must be in the database at the start of every request.** If you need data, you must read it from the DB. If you change data, you must write it to the DB before the request ends.

The only exception is request-scoped caching within a single request. See §12.5.

### 2.3 Database as Source of Truth

Every piece of mutable game state has exactly one home in the database. That home is the authoritative copy:

| State | Source of Truth | Never Duplicated In |
|---|---|---|
| Card position | `card` table row | globals, notification args |
| Player resources | `player` table column | globals, game state table |
| Round number | globals key | player table, card table |
| Score | `player_score` column | globals, notification cache |

**Rule:** If two places can disagree about the same value, one of them is wrong. The DB is always right.

The notification delta cache in ArkNova (`$cachedValues`) is an optimisation, not a source of truth — if the cache disagrees with the DB, the DB wins and the cache is rebuilt.

### 2.4 Implicit Transactions

BGA wraps every request in an implicit MySQL transaction. The transaction begins before the action method is called and commits (or rolls back) when the method returns.

```
REQUEST ARRIVES
  │
  ▼
BEGIN TRANSACTION       ← automatic by framework
  │
  ▼
Action method runs      ← all DB reads/writes within this transaction
  │
  ├── throws exception  → ROLLBACK (automatic)
  └── returns normally  → COMMIT (automatic)
```

See [game-flow-architecture.md §5.1](./game-flow-architecture.md#51-the-implicit-transaction) for the full treatment.

### 2.5 Replay Safety

All game mutations must be replay-safe. The gamelog stores every notification, and during reconnection, the framework replays them on the client. On the server side, `getAllDatas()` must return a complete snapshot that, combined with gamelog replay, produces the correct client state.

Replay safety for persistence means:
- **Idempotent writes** — running the same INSERT/UPDATE twice should not corrupt state (use conditional updates, not blind overwrites)
- **Deterministic reads** — reading from the DB at the same point in the state machine always returns the same data
- **No hidden state** — every piece of information needed to reconstruct the game is in the DB

---

## 3. Persistence Layers

### 3.1 Layer Stack

```
┌──────────────────────────────────────────────────────────┐
│  FRAMEWORK LAYER                                         │
│  - Table base class: getObjectFromDB, getCollectionFromDB │
│  - DbQuery, DbCommand                                   │
│  - gamestate table (state machine position)             │
│  - global_preferences table (BGA-managed)               │
│  - gamelog table (notification archive)                  │
├──────────────────────────────────────────────────────────┤
│  MANAGER LAYER                                           │
│  - One Manager per aggregate root                        │
│  - Owns one or more tables exclusively                   │
│  - Encapsulates all read/write operations                │
│  - Returns Models, not raw arrays                        │
├──────────────────────────────────────────────────────────┤
│  GLOBALS LAYER                                           │
│  - Typed key-value wrapper                               │
│  - Backed by global_variables table or bga->globals      │
│  - Used for cross-turn state, engine tree, flags         │
├──────────────────────────────────────────────────────────┤
│  DECK LAYER                                              │
│  - Framework Deck module or custom table                 │
│  - Card/piece location management                        │
│  - Draw, shuffle, move, discard operations               │
├──────────────────────────────────────────────────────────┤
│  DB HELPERS                                              │
│  - QueryBuilder (SQL abstraction)                        │
│  - CachedDB_Manager (in-request row cache)               │
│  - Pieces (polymorphic entity storage)                   │
│  - Collection (enhanced array operations)                │
├──────────────────────────────────────────────────────────┤
│  DATABASE                                                │
│  - MySQL tables (InnoDB)                                 │
│  - Player table (framework-managed + custom columns)     │
│  - Custom game tables                                    │
│  - global_variables table                                │
│  - gamelog table (framework-managed)                     │
└──────────────────────────────────────────────────────────┘
```

### 3.2 Layer Responsibility Matrix

| Layer | Reads | Writes | Owns Tables? | Caches? |
|---|---|---|---|---|
| **Framework** | gamestate, global_preferences | gamestate, global_preferences | gamestate, gamelog | No |
| **Manager** | Its tables (via framework API or DB helpers) | Its tables exclusively | Yes — its aggregate's tables | Optional (request-scoped) |
| **Globals** | global_variables | global_variables | global_variables (key namespace) | No |
| **Deck** | Its card/piece table | Its card/piece table | Its table (if custom) or framework-managed | No |
| **DB Helpers** | Any table (via Manager) | Any table (via Manager) | No | Yes (CachedDB_Manager) |
| **Database** | All game data | All game data | Everything | No |

### 3.3 Framework Persistence API

The `Table` base class provides the primary persistence API:

```php
// Read methods
$this->getObjectFromDB(string $sql): array|null
$this->getCollectionFromDB(string $sql): array
$this->getNonEmptyCollectionFromDB(string $sql): array
$this->getUniqueValueFromDB(string $sql): mixed
$this->getObjectListFromDB(string $sql): array

// Write methods
$this->DbQuery(string $sql): int|null
$this->DbCommand(string $sql, array $params): int|null  // modern, prepared

// Modern framework
$this->bga->globals->get(string $key): mixed
$this->bga->globals->set(string $key, mixed $value): void
$this->bga->globals->delete(string $key): void
```

All SQL in BGA is raw MySQL strings interpolated with PHP variables. There is no ORM, no query builder in the base framework (though Agricola and Ark Nova add custom `QueryBuilder` classes).

---

## 4. Database Architecture

### 4.1 Table Ownership

Every custom table must be owned by exactly one Manager. The owning Manager is the **only** class that writes to that table. Other classes read through the Manager's public API.

```php
// CORRECT — Cards manager owns card table
$this->game->cards->playCard($cardId, $playerId);

// WRONG — direct write from another class
$this->game->DbQuery("UPDATE card SET card_location = 'play' WHERE card_id = $cardId");
```

**Table ownership mapping** is documented in comments in `dbmodel.sql`:

```sql
-- TABLE: card
-- OWNER: Managers/Cards.php
-- PURPOSE: All card instances in the game (deck, hand, play, discard)
-- AGGREGATE: Card
```

See [domain-architecture.md §7.3](./domain-architecture.md#73-when-multiple-tables-belong-to-one-manager) for the aggregate ownership decision tree.

### 4.2 Aggregate Ownership

Multiple tables belong to one Manager when they form a single aggregate — they share a lifecycle and cannot exist independently:

| Manager | Owned Tables | Rationale |
|---|---|---|
| `Players` | `player` | Player aggregate |
| `Cards` | `card` | Card aggregate (each card is independent) |
| `Board` | `board_tiles`, `board_meeples` | Board aggregate (tiles and meeples share lifecycle) |
| `Scoring` | `player` (score column) | Scoring is a column on player, not a separate table |
| `ActionCommands` | `action_command` | Command queue aggregate (Earth pattern) |

### 4.3 Foreign Keys

BGA games should define foreign key relationships in the schema for data integrity:

```sql
CREATE TABLE IF NOT EXISTS `card` (
    `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `card_location` varchar(16) NOT NULL,
    `card_location_arg` int(11) NOT NULL,
    `player_id` int(10) unsigned DEFAULT NULL,
    PRIMARY KEY (`card_id`),
    FOREIGN KEY (`player_id`) REFERENCES `player`(`player_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
```

**Rules for foreign keys:**
- Define them in `dbmodel.sql` — they document relationships and prevent orphaned data
- Use `ON DELETE CASCADE` for owned entities (cards belonging to a deleted player)
- Use `ON DELETE SET NULL` for optional references (a card may not belong to any player)
- Do NOT rely on foreign keys for application-level validation — that is the Manager's responsibility

### 4.4 Soft vs Hard Ownership

**Hard ownership** — the owning table's primary key is the entity's identity:

```sql
-- Owned: card is identified by its own primary key
CREATE TABLE card (card_id INT AUTO_INCREMENT PRIMARY KEY, ...);
```

**Soft ownership** — the entity's identity is derived from the owning aggregate:

```sql
-- Soft-owned: board positions only exist within a board
CREATE TABLE board_position (
    board_id INT NOT NULL,
    position_x INT NOT NULL,
    position_y INT NOT NULL,
    tile_type VARCHAR(16),
    PRIMARY KEY (board_id, position_x, position_y)
);
```

| Ownership | Identity | Deletion | Example |
|---|---|---|---|
| Hard | Self (own PK) | Cascade removes child | Card entity |
| Soft | Parent PK + position | Parent owns lifecycle | Board position |

Soft ownership is appropriate when the child has no independent existence. Hard ownership is appropriate when the entity can be referenced by other tables.

---

## 5. Table Design

### 5.1 Naming Conventions

| Element | Convention | Example |
|---|---|---|
| Table name | lowercase_snake | `board_tiles`, `player_state` |
| Primary key | `table_name_id` | `card_id`, `player_id` |
| Foreign key | `referenced_table_id` | `player_id`, `card_id` |
| Boolean flag | `flag_name` (TINYINT) | `has_drawn` |
| Timestamp | `created_at`, `updated_at` | Framework does not enforce |
| JSON column | `extra_datas` (Agricola convention) | `extra_datas` |
| Enum string | `status`, `type`, `state` | `card_location` |

### 5.2 Primary Keys

**Always use surrogate auto-increment primary keys** for custom tables:

```sql
CREATE TABLE card (
    card_id int(10) unsigned NOT NULL AUTO_INCREMENT,
    card_type varchar(16) NOT NULL,
    card_type_arg int(11) NOT NULL,
    PRIMARY KEY (card_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
```

| Key Type | Use | Example |
|---|---|---|
| **Surrogate (auto-increment)** | Default — all custom tables | `card_id` |
| **Natural** | Only for framework-mandated tables | `player_id` (from framework) |
| **Composite** | Junction tables, soft-ownership | `(board_id, position_x, position_y)` |

**Avoid natural keys.** Game card types like `('farm', 1)` may change with expansions. Surrogate keys (`card_id = 42`) are immutable, smaller, and faster for joins.

The framework requires `player_id` to be the natural key from the `player` table (it is provided by BGA during `setupNewGame`). All references to players should use the framework's `player_id`.

### 5.3 Enum Values

BGA does not support MySQL `ENUM` type well in schema declarations. Instead, use `VARCHAR` with application-level validation:

```sql
-- CORRECT
`card_location` varchar(16) NOT NULL DEFAULT 'deck'
-- Validated in Cards Manager: in_array($location, ['deck', 'hand', 'play', 'discard'])

-- AVOID
`card_location` enum('deck', 'hand', 'play', 'discard') NOT NULL DEFAULT 'deck'
```

**Application-level validation enum pattern:**

```php
class Card
{
    public const LOCATION_DECK = 'deck';
    public const LOCATION_HAND = 'hand';
    public const LOCATION_PLAY = 'play';
    public const LOCATION_DISCARD = 'discard';

    public static function isValidLocation(string $location): bool
    {
        return in_array($location, [
            self::LOCATION_DECK,
            self::LOCATION_HAND,
            self::LOCATION_PLAY,
            self::LOCATION_DISCARD,
        ]);
    }
}
```

### 5.4 JSON Columns

JSON columns are appropriate for:
- Card extra data that varies by card type (Agricola's `extra_datas`)
- Small, infrequently-queried configuration blobs
- Polymorphic attributes that would require dozens of nullable columns

```sql
CREATE TABLE card (
    card_id int(10) unsigned NOT NULL AUTO_INCREMENT,
    card_type varchar(16) NOT NULL,
    `extra_datas` json DEFAULT NULL,
    PRIMARY KEY (card_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
```

**When to use JSON:**
- The data is read together, never queried individually
- The schema varies per row (different card types have different extra fields)
- The data is small (< 1KB per row)

**When NOT to use JSON:**
- The value is queried in WHERE clauses frequently (use a real column)
- The value needs to be indexed (use a real column)
- The data is large or grows over time (use a related table)
- The data belongs in its own table (e.g., card effects should be a separate table if they have their own lifecycle)

See §14.3 for JSON abuse patterns.

### 5.5 Version Columns

For the undo log (see [notification-patterns.md §12](./notification-patterns.md#12-undo-interactions)), add a version or sequence column:

```sql
CREATE TABLE log (
    `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `log_player_id` int(10) unsigned NOT NULL,
    `log_action` varchar(32) NOT NULL,
    `log_args` json NOT NULL,
    `log_round` int(11) NOT NULL,
    `log_turn` int(11) NOT NULL,
    `log_sequence` int(11) NOT NULL,    -- ← version column for ordering
    `log_cancel` tinyint(1) NOT NULL DEFAULT 0,  -- ← cancellation flag
    PRIMARY KEY (`log_id`),
    KEY `log_round_turn` (`log_round`, `log_turn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
```

Version columns are not needed on entity tables in BGA because:
- The implicit transaction prevents concurrent writers
- There is no optimistic locking concern within a single request

### 5.6 Timestamps

Add timestamps only when needed for debugging or audit:

```sql
CREATE TABLE action_command (
    `command_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `command_type` varchar(32) NOT NULL,
    `command_args` json NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`command_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
```

BGA does not require `created_at` / `updated_at` on every table. Use them only when the temporal information has game-logic significance (Earth's command queue uses timestamps for ordering).

---

## 6. Entity Persistence Lifecycle

### 6.1 Lifecycle Diagram

```
                    ┌──────────┐
                    │  CREATE  │  ← INSERT (setupNewGame or gameplay)
                    └────┬─────┘
                         │
                    ┌────▼─────┐
                    │   READ   │  ← SELECT (start of each request)
                    └────┬─────┘
                         │
                    ┌────▼─────┐
                    │  UPDATE  │  ← mutation within transaction
                    └────┬─────┘
                         │
            ┌────────────┼────────────┐
            │            │            │
       ┌────▼───┐  ┌─────▼────┐  ┌───▼────┐
       │ DELETE │  │  MOVE    │  │ ARCHIVE│
       │ (rare) │  │ (common) │  │ (rare) │
       └────────┘  └──────────┘  └────────┘
```

### 6.2 CREATE

Entities are created in `setupNewGame()` or during gameplay:

```php
public function createCard(string $type, string $typeArg, string $location, int $locationArg = 0): Card
{
    $this->game->DbQuery(
        "INSERT INTO card (card_type, card_type_arg, card_location, card_location_arg)
         VALUES ('$type', '$typeArg', '$location', $locationArg)"
    );
    $id = (int)$this->game->bga->db->getLastInsertId();
    return $this->get($id);
}
```

**CREATE rules:**
- Only the owning Manager creates entities
- All required columns must be set during creation
- Use `getLastInsertId()` or `RETURNING` (MySQL 8.4+) to get the new ID
- Creation happens inside the implicit transaction

### 6.3 READ

Entity reading can be eager or lazy, but always within the request:

```php
// Eager — load all at start of request
public function getAll(): array
{
    $rows = $this->game->getCollectionFromDB("SELECT * FROM card");
    return array_map(fn($r) => $this->rowToCard($r), $rows);
}

// Lazy — load on demand (with in-request cache)
private static array $cardCache = [];

public function get(int $cardId): Card
{
    if (!isset(self::$cardCache[$cardId])) {
        $row = $this->game->getObjectFromDB(
            "SELECT * FROM card WHERE card_id = $cardId"
        );
        if (!$row) {
            throw new \BgaSystemException("Card $cardId not found");
        }
        self::$cardCache[$cardId] = $this->rowToCard($row);
    }
    return self::$cardCache[$cardId];
}
```

The in-request cache is safe because the PHP instance is destroyed after the request. However, it must be cleared if a Manager mutates a cached entity within the same request:

```php
public function playCard(int $cardId, int $playerId): Card
{
    $this->game->DbQuery(
        "UPDATE card SET card_location = 'play', card_location_arg = $playerId
         WHERE card_id = $cardId"
    );
    unset(self::$cardCache[$cardId]);  // Invalidate cache
    return $this->get($cardId);
}
```

### 6.4 UPDATE

Updates modify existing rows. The owning Manager is the only class that issues UPDATE statements for its table:

```php
public function addScore(int $playerId, int $points): void
{
    $this->game->DbQuery(
        "UPDATE player SET player_score = player_score + $points
         WHERE player_id = $playerId"
    );
}
```

**UPDATE rules:**
- Validate before mutate — all checks must pass before the first UPDATE
- Use relative updates (`SET col = col + $delta`) where possible to avoid read-then-write races
- Always include a WHERE clause that matches exactly one row (or document why not)

### 6.5 DELETE

Deletion is rare in BGA games. Most entities are moved between locations rather than deleted:

```php
public function removeCard(int $cardId): void
{
    $this->game->DbQuery(
        "DELETE FROM card WHERE card_id = $cardId"
    );
}
```

**When to DELETE vs. UPDATE location:**

| Scenario | Operation | Reason |
|---|---|---|
| Card consumed by game effect | DELETE | Entity no longer exists |
| Card discarded from hand | UPDATE location | Entity moves to discard pile |
| Token spent | DELETE | Token consumed permanently |
| Token moved | UPDATE location | Token continues to exist |

### 6.6 MOVE

Moving is the most common mutation in card/board games. It is an UPDATE of location, not a DELETE + CREATE:

```php
public function moveCard(int $cardId, string $toLocation, int $toArg = 0): Card
{
    $this->game->DbQuery(
        "UPDATE card SET card_location = '$toLocation', card_location_arg = $toArg
         WHERE card_id = $cardId"
    );
    return $this->get($cardId);
}
```

**Move performance:** A single UPDATE is far cheaper than DELETE + INSERT. Always move rather than recreate.

### 6.7 ARCHIVE

Archive is typically framework-managed. The `gamelog` table stores all notifications for archive replay. Custom tables do not need archive columns under normal circumstances.

For games with campaign or solo persistence (Agricola's campaign mode), create an explicit archive table:

```sql
CREATE TABLE IF NOT EXISTS `campaign` (
    `campaign_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `player_id` int(10) unsigned NOT NULL,
    `campaign_data` json NOT NULL,
    `campaign_progress` int(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`campaign_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
```

---

## 7. Globals Persistence

### 7.1 Storage Mechanism

Globals are stored in one of two places:

| Storage | API | Best For |
|---|---|---|
| `global_variables` table | `$this->bga->globals->get/set/delete` | Small values, game configuration, single values |
| Custom table | Direct SQL | Large blobs, complex state, engine tree |

The `global_variables` table is a framework-managed key-value store with two columns: `name` and `value`. Values are stored as strings.

### 7.2 Typed Globals

Always wrap globals in a typed class rather than using raw `get/set` calls:

```php
class Globals
{
    private const ROUND = 'currentRound';
    private const PHASE = 'gamePhase';
    private const FIRST_PLAYER = 'firstPlayerId';

    public static function getCurrentRound(): int
    {
        return (int)self::get(self::ROUND, 1);
    }

    public static function setCurrentRound(int $round): void
    {
        self::set(self::ROUND, $round);
    }

    public static function getGamePhase(): string
    {
        return self::get(self::PHASE, 'setup');
    }

    public static function setGamePhase(string $phase): void
    {
        if (!in_array($phase, ['setup', 'action', 'break', 'end'], true)) {
            throw new \BgaSystemException("Invalid game phase: $phase");
        }
        self::set(self::PHASE, $phase);
    }

    private static function get(string $key, mixed $default = null): mixed
    {
        return Game::get()->bga->globals->get($key) ?? $default;
    }

    private static function set(string $key, mixed $value): void
    {
        Game::get()->bga->globals->set($key, $value);
    }
}
```

**Typed globals provide:**
- Type coercion (return types in PHPDoc)
- Validation (setter throws on invalid values)
- Discoverability (IDE shows available keys)
- Single place to change storage strategy

### 7.3 Serialized Globals

For complex state like the Engine decision tree, store a serialized JSON blob using a dedicated key:

```php
class Globals
{
    private const ENGINE_TREE = 'engineTree';

    public static function setEngineTree(array $tree): void
    {
        self::set(self::ENGINE_TREE, json_encode($tree));
    }

    public static function getEngineTree(): ?array
    {
        $raw = self::get(self::ENGINE_TREE);
        return $raw ? json_decode($raw, true) : null;
    }

    public static function clearEngineTree(): void
    {
        self::set(self::ENGINE_TREE, null);
    }
}
```

See [domain-architecture.md §10.3](./domain-architecture.md#103-engines-data-model) for how the Engine uses serialised globals.

### 7.4 Configuration

Game options and player preferences are stored by the framework:

```php
// Game options — stored in global_preferences (framework-managed)
$this->getGameStateValue('option_name');
$this->setGameStateValue('option_name', $value);

// Player preferences — stored per-player (framework-managed)
$this->getPreferenceValue($playerId, 'preference_name');
$this->setPreferenceValue($playerId, 'preference_name', $value);
```

These are managed by the framework's config system and should not be duplicated in custom tables.

### 7.5 Temporary State

Temporary state (request-scoped, not persisted) should never touch the database:

```php
// CORRECT — request-scoped array
private array $cache = [];

// WRONG — persisting request-scoped data
Globals::set('temp', $value);  // This lives in the DB across requests
```

---

## 8. Deck Persistence

### 8.1 The Framework Deck Module

The recommended approach for card games is the framework's `Deck` module:

```php
// In Game.php setupNewGame()
$this->cards = $this->deckFactory->createDeck('card');

// Create cards
$cards = [];
foreach ($cardTypes as $type => $count) {
    for ($i = 0; $i < $count; $i++) {
        $cards[] = ['type' => $type, 'type_arg' => $i + 1];
    }
}
$this->cards->createCards($cards, 'deck');

// Shuffle
$this->cards->shuffle('deck');

// Draw
$hand = $this->cards->pickCards(5, 'deck', $playerId);

// Move
$this->cards->moveCard($cardId, 'discard');
```

The `Deck` module manages its own table (`card` by default) with a standard schema. It handles:
- Location tracking (`card_location`, `card_location_arg`)
- Ordering (`card_location_arg` doubles as sort order)
- Shuffling
- Picking specific cards or random cards
- Counting cards by location

**See:** [bga-developer-handbook.md §7](./bga-developer-handbook.md#deck-component-server-side)

### 8.2 When to Use the Deck Module

| Criterion | Use Deck Module | Use Custom Table |
|---|---|---|
| Standard card game | Yes | No |
| Cards need extra state per card | No — use `extra_datas` JSON | Yes — add columns |
| Cards move through standard locations (deck/hand/play/discard) | Yes | Yes (same pattern) |
| Cards have complex per-card state (counters, orientation) | No — Deck module has no per-card state | Yes |
| Game uses tiles, meeples, or tokens (not cards) | No — Deck is card-specific | Yes |

### 8.3 Custom Deck Implementation

Games with per-card state beyond location and type should use a custom table instead of the Deck module:

```sql
-- Agricola's custom card table
CREATE TABLE IF NOT EXISTS `card` (
    `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `card_type` varchar(16) NOT NULL,
    `card_type_arg` int(11) NOT NULL,
    `card_location` varchar(16) NOT NULL,
    `card_location_arg` int(11) NOT NULL,
    `card_pId` int(10) unsigned DEFAULT NULL,
    `card_state` int(11) NOT NULL DEFAULT 0,
    `extra_datas` json DEFAULT NULL,
    PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
```

Custom deck implementation provides:
- Arbitrary per-card columns (`card_state`, `card_pId`)
- JSON extra data for polymorphic card attributes
- No framework-imposed schema constraints

**Trade-off:** You must implement shuffle, pick, and move operations yourself:

```php
public function shuffleDeck(): void
{
    $cards = $this->game->getCollectionFromDB(
        "SELECT card_id FROM card WHERE card_location = 'deck' ORDER BY RAND()"
    );
    $order = 0;
    foreach ($cards as $cardId => $row) {
        $this->game->DbQuery(
            "UPDATE card SET card_location_arg = $order WHERE card_id = $cardId"
        );
        $order++;
    }
}

public function pickCard(int $playerId): Card
{
    $cardId = $this->game->getUniqueValueFromDB(
        "SELECT card_id FROM card
         WHERE card_location = 'deck'
         ORDER BY card_location_arg ASC
         LIMIT 1"
    );
    if (!$cardId) throw new \BgaSystemException('Deck is empty');
    return $this->moveCard((int)$cardId, 'hand', $playerId);
}
```

### 8.4 Hybrid Approach

Use the Deck module for standard operations and a separate table for extra card state:

```php
// Deck module manages location
$this->deck->moveCard($cardId, 'play', $playerId);

// Custom table manages extra state
$this->game->DbQuery(
    "UPDATE card_extra SET sprout_count = sprout_count + 1 WHERE card_id = $cardId"
);
```

This avoids duplicating the Deck module's location management while supporting per-card state. Used by Earth (cards have `sprout_count`, `growth_count` on the `card` table alongside Deck-like location columns).

### 8.5 Reference Project Comparison

| Project | Deck Approach | Why |
|---|---|---|
| **Arnak** | Custom `card` table | Cards need `card_position` enum and per-card ordering |
| **Agricola** | Custom `cards` table via `Pieces` helper | Cards need `extra_datas` JSON for polymorphic attributes |
| **ArkNova** | Custom table via `Pieces` | Cards need per-card state for 300+ unique card classes |
| **Earth** | Custom `card` table with sprout/growth columns | Cards need game-state counters on each card |

None of the four reference projects use the framework Deck module directly. All use custom tables because none are "standard card games" — they all need per-card state beyond location.

**Recommendation:** Use the Deck module for simple card games where cards have no per-card state. Use custom tables when cards need extra columns, JSON, or derived properties.

---

## 9. Transactions

### 9.1 The Implicit Transaction Model

Every BGA request runs inside a single MySQL transaction. This is the single most important persistence constraint.

```
Request starts  ──→  BEGIN TRANSACTION
                        │
                    Game logic (reads see uncommitted writes)
                        │
                    ┌───┼───┐
                    │   │   │
                    ▼   ▼   ▼
                Multiple DB writes (buffered)
                        │
                    Action method returns
                        │
                    ┌───┴───┐
                    │       │
                    ▼       ▼
                 COMMIT   (or ROLLBACK on exception)
```

See [game-flow-architecture.md §5](./game-flow-architecture.md#5-transaction-boundaries) for the full treatment.

### 9.2 Rollback Behaviour

When an exception propagates out of the action method:

1. The framework catches the exception
2. MySQL ROLLBACK is issued
3. All DB changes within the request are reverted
4. All queued notifications are discarded
5. The error is returned to the client

```php
public function actPlayCard(int $cardId, int $activePlayerId): string
{
    // These writes are buffered:
    $this->game->DbQuery("UPDATE card SET ...");       #1
    $this->game->DbQuery("UPDATE player SET ...");     #2

    // If this throws, BOTH #1 and #2 are rolled back:
    throw new \BgaUserException(clienttranslate('Cannot play card'));

    // This never executes:
    return 'cardPlayed';
}
```

**Consequences:**
- Validate before mutate — wasteful rollbacks hurt performance
- No external side effects (emails, API calls) — they cannot be rolled back
- Reads see uncommitted writes within the same request (MySQL REPEATABLE READ)

### 9.3 Nested Operations

Within the action, Managers may be called multiple times. All mutations accumulate in the same transaction:

```php
public function actPlayCard(int $cardId, int $activePlayerId): string
{
    // All within the SAME transaction:
    $card = $this->game->cards->get($cardId);           // Read
    $this->game->players->spendResources(...);           // Write #1
    $this->game->cards->playCard($cardId, $playerId);    // Write #2
    $this->game->board->applyCardEffect($cardId);         // Write #3

    return 'cardPlayed';
    // COMMIT — all three writes or none
}
```

**Nested transaction note:** MySQL's `SAVEPOINT` mechanism is available but rarely needed. The implicit transaction covers the entire action. If you find yourself needing nested transaction semantics, you likely have an architectural problem (too much logic in one action).

### 9.4 Idempotency

Writes should be idempotent or nearly so. Running the same action twice should not corrupt state:

```php
// CORRECT — idempotent relative update
$this->game->DbQuery(
    "UPDATE player SET player_score = player_score + 5 WHERE player_id = $playerId"
);
// Running twice adds 10, which is correct if the action was legitimately submitted twice

// PROBLEMATIC — absolute update
$newScore = $player->getScore() + 5;
$this->game->DbQuery(
    "UPDATE player SET player_score = $newScore WHERE player_id = $playerId"
);
// If state changed between read and write, this overwrites the correct value
```

Relative updates are inherently more robust than read-modify-write cycles.

### 9.5 Engine Transactions

When using the Engine pattern (Agricola/ArkNova), the entire tree resolution happens within one request — one transaction:

```
Single request:
  BEGIN TRANSACTION
    Engine node 1 → writes #1
    Engine node 2 → writes #2
    Engine node 3 → writes #3
    (all buffered)
  COMMIT
```

This means earlier nodes cannot be rolled back independently of later nodes. The `Log` table records checkpoints for undo, but the DB transaction does not commit until the full action completes.

See [game-flow-architecture.md §5.3](./game-flow-architecture.md#53-the-commit-barrier-pattern).

### 9.6 Command Queue Transactions

Earth's command queue spans multiple requests, each with its own transaction:

```
Request 1: Player queues command
  BEGIN TRANSACTION
    Command applied to private state
    Command saved to action_command table
  COMMIT

Request 2: Player commits turn
  BEGIN TRANSACTION
    All queued commands replayed
    Real game state mutated
  COMMIT
```

Each request is independently atomic. The command table bridges the two transactions.

See [game-flow-architecture.md §14.2](./game-flow-architecture.md#142-solution-a-private-state--command-queue-earth).

---

## 10. Concurrency

### 10.1 The Concurrency Model

BGA concurrency is simple for turn-based games:
- One active player acts per request
- No other player modifies state concurrently
- The implicit transaction serialises all writes

Concurrency problems arise in two scenarios:
1. **Simultaneous turns** — multiple players act in the same `MULTIPLE_ACTIVE_PLAYER` state
2. **Race conditions** — one player's action reads stale state because another player's action committed moments earlier

### 10.2 Simultaneous Actions

In `MULTIPLE_ACTIVE_PLAYER` states, each player's action is a separate HTTP request, processed in a separate MySQL transaction:

```
Time  Player A                Player B
 │    REQUEST 1               REQUEST 2
 │    BEGIN TX                BEGIN TX
 │      Read card X: available  Read card X: available (both see same state)
 │      Write: take card X      Write: take card X
 │    COMMIT ◄────────────────► Both succeed — card X is double-taken!
```

This is the classic lost-update problem.

### 10.3 Solutions to Simultaneous Conflicts

**Solution A — Atomic check-and-set (preferred):**

Use a conditional UPDATE that fails if state changed:

```php
public function claimCard(int $cardId, int $playerId): Card
{
    // Atomic: update only if card is still available
    $affected = $this->game->DbQuery(
        "UPDATE card
         SET card_location = 'hand', card_location_arg = $playerId
         WHERE card_id = $cardId
           AND card_location = 'offer'"
    );
    if ($affected === 0) {
        // Card was already taken by another request — this UPDATE matched 0 rows
        throw new \BgaUserException(clienttranslate('This card is no longer available'));
    }
    return $this->get($cardId);
}
```

This is an optimistic concurrency strategy: assume no conflict, but detect and reject if one occurred.

**Solution B — Earth's advisory lock:**

```php
// Lock.php — Earth uses MySQL GET_LOCK() for mutual exclusion
public static function acquireLock(string $key): void
{
    $sql = "SELECT GET_LOCK('{$key}', 5)";
    // Timeout after 5 seconds
}

// Before any simultaneous action:
Lock::acquireLock("card_claim_{$cardId}");
// ... perform action ...
Lock::releaseLock("card_claim_{$cardId}");
```

This is a pessimistic strategy: lock the resource before acting.

**Solution C — Private state isolation (Earth pattern):**

In Earth's command queue, each player's actions apply to a private state, not the shared game state. Conflicts are detected during the commit phase via `reevaluate()`.

See [action-architecture.md §9.4](./action-architecture.md#94-transaction-boundaries-in-the-command-queue-pattern-earth).

### 10.4 Optimistic vs Pessimistic Strategies

| Strategy | Mechanism | When to Use |
|---|---|---|
| **Optimistic** (atomic check-and-set) | Conditional UPDATE, check affected rows | Common resources (cards, tokens, offers) |
| **Optimistic** (read-then-check) | Read state, validate, write (re-validate if simultaneous) | Player-specific resources (no conflict) |
| **Pessimistic** (advisory lock) | MySQL `GET_LOCK()` | Cross-player critical sections (rare) |
| **Pessimistic** (private state isolation) | Command queue with separate state | Complex simultaneous turns (Earth) |

**Recommendation:** Start with optimistic concurrency (atomic conditional UPDATE). Graduate to Earth's command queue only for games with simultaneous turns and complex undo requirements.

### 10.5 The Atomic UPDATE Pattern

The most common concurrency-safe write pattern:

```php
// DECLARATIVE: condition is in the WHERE clause
$this->game->DbQuery(
    "UPDATE card
     SET card_location = 'hand', card_location_arg = $playerId
     WHERE card_id = $cardId
       AND card_location = 'offer'"
);

// Check affected rows
if ($this->game->bga->db->affectedRows() === 0) {
    throw new \BgaUserException(clienttranslate('That card is no longer available'));
}
```

This works because MySQL's row-level locking ensures only one transaction wins the UPDATE. The losing transaction sees 0 affected rows.

---

## 11. Undo Implications

### 11.1 The Undo Log

For undo support, every DB mutation must be reversible. Both approaches (gamelog cancellation and command queue) require a log of changes.

**Log table schema (Agricola/ArkNova pattern):**

```sql
CREATE TABLE IF NOT EXISTS `log` (
    `log_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `log_player_id` int(10) unsigned NOT NULL,
    `log_round` int(11) NOT NULL,
    `log_turn` int(11) NOT NULL,
    `log_action` varchar(32) NOT NULL,
    `log_args` json NOT NULL,
    `log_sequence` int(11) NOT NULL,
    `log_cancel` tinyint(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`log_id`),
    KEY `player_round_turn` (`log_player_id`, `log_round`, `log_turn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
```

Each log entry records:
- What action was taken
- Which player took it
- Where in the game sequence (round, turn, sequence)
- The arguments needed to reverse it
- A cancellation flag for undo

### 11.2 Reversibility

Every mutation must be reversible. The Manager provides both a forward method and a reverse (or the action is designed so the log records enough information to reverse it):

```php
public function addResource(int $playerId, string $resource, int $amount): void
{
    $this->game->DbQuery(
        "UPDATE player SET {$resource} = {$resource} + $amount WHERE player_id = $playerId"
    );
}

// The reverse is inferred: subtract the same amount
public function removeResource(int $playerId, string $resource, int $amount): void
{
    $this->game->DbQuery(
        "UPDATE player SET {$resource} = {$resource} - $amount WHERE player_id = $playerId"
    );
}
```

**The undo action replays the log in reverse:**

```php
public function undoLastAction(int $playerId, int $round, int $turn): void
{
    $logEntries = $this->game->getCollectionFromDB(
        "SELECT * FROM log
         WHERE log_player_id = $playerId
           AND log_round = $round
           AND log_turn = $turn
           AND log_cancel = 0
         ORDER BY log_sequence DESC"
    );
    foreach ($logEntries as $entry) {
        $this->reverseAction($entry);
        // Mark as cancelled
        $this->game->DbQuery(
            "UPDATE log SET log_cancel = 1 WHERE log_id = {$entry['log_id']}"
        );
    }
}
```

See [notification-patterns.md §12](./notification-patterns.md#12-undo-interactions) for the full undo notification pattern.

### 11.3 Gamelog Implications

The `gamelog` table stores notifications. When an action is undone, the corresponding gamelog packets must be cancelled:

```php
public function cancelGamelogPackets(array $notifIds): void
{
    $idList = implode(',', $notifIds);
    $this->game->DbQuery(
        "UPDATE gamelog SET cancel = 1 WHERE gamelog_id IN ($idList)"
    );
}
```

The framework reads the `cancel` column when replaying notifications. Cancelled packets are skipped.

### 11.4 Engine Replay

For the Engine pattern (Agricola/ArkNova), undo must also revert the Engine tree to a previous state. The Engine tree is serialised to globals:

```php
public function undoToCheckpoint(int $checkpointId): void
{
    // 1. Reverse DB mutations from log
    $this->reverseLogEntriesAfter($checkpointId);

    // 2. Restore Engine tree from checkpoint snapshot
    $snapshot = $this->getCheckpointSnapshot($checkpointId);
    Globals::setEngineTree($snapshot['engine_tree']);

    // 3. Cancel gamelog packets
    $this->cancelGamelogPackets($snapshot['notif_ids']);

    // 4. Refresh client
    $this->notifications->clearTurn($player);
    $this->notifications->refreshUI($this->getAllDatas($playerId));
}
```

The Log table's `log_args` JSON column stores the engine tree snapshot at each checkpoint.

---

## 12. Performance

### 12.1 Indexes

Every query pattern should have a supporting index:

```sql
-- Query by location and player
SELECT * FROM card WHERE card_location = 'hand' AND card_location_arg = $playerId
-- Index:
KEY `card_location_player` (`card_location`, `card_location_arg`)

-- Query by player for scoring
SELECT * FROM player ORDER BY player_score DESC
-- Already indexed: player_score (framework adds this if used in order)

-- Query log by player, round, turn
SELECT * FROM log WHERE log_player_id = $pid AND log_round = $r AND log_turn = $t
-- Index:
KEY `log_player_round_turn` (`log_player_id`, `log_round`, `log_turn`)
```

**Default indexes to add:**

```sql
-- Every custom table should have:
PRIMARY KEY (`table_id`)
-- Every location-based query should have:
KEY `location` (`card_location`, `card_location_arg`)
-- Every player FK should have:
KEY `player_id` (`player_id`)
```

### 12.2 Batch Reads

Prefer a single query over N individual queries:

```php
// BAD — N+1 queries
foreach ($cardIds as $cardId) {
    $card = $this->get($cardId);  // One query per card
}

// GOOD — single batch query
$cards = $this->getMultiple($cardIds);

public function getMultiple(array $cardIds): array
{
    $idList = implode(',', array_map('intval', $cardIds));
    $rows = $this->game->getCollectionFromDB(
        "SELECT * FROM card WHERE card_id IN ($idList)"
    );
    return array_map(fn($r) => $this->rowToCard($r), $rows);
}
```

### 12.3 Batch Writes

Prefer a single multi-row UPDATE over multiple individual UPDATES:

```php
// BAD — N queries
foreach ($cardIds as $index => $cardId) {
    $this->game->DbQuery(
        "UPDATE card SET card_location_arg = $index WHERE card_id = $cardId"
    );
}

// GOOD — single batch (use CASE)
$cases = [];
foreach ($cardIds as $index => $cardId) {
    $cases[] = "WHEN $cardId THEN $index";
}
$caseStr = implode(' ', $cases);
$this->game->DbQuery(
    "UPDATE card SET card_location_arg = CASE card_id $caseStr END
     WHERE card_id IN (" . implode(',', $cardIds) . ")"
);
```

### 12.4 N+1 Query Avoidance

The N+1 problem occurs when a loop triggers a query per iteration:

```php
// N+1: one query to get players, then N queries for each player's cards
$players = $this->game->players->getAll();
foreach ($players as $player) {
    $hand = $this->game->cards->getHand($player->getId());  // One query per player
}

// FIX: batch all hands in one query
$allHands = $this->game->cards->getAllHands();  // Single query with GROUP BY
```

**Detection:** If an action method causes 10+ queries and the game has 4-6 players, look for N+1 patterns.

### 12.5 Request-Scoped Caching

Caching within a single request is safe and recommended:

```php
class Cards extends Manager
{
    private ?array $allCards = null;

    public function getAll(): array
    {
        if ($this->allCards === null) {
            $rows = $this->game->getCollectionFromDB("SELECT * FROM card");
            $this->allCards = array_map(fn($r) => $this->rowToCard($r), $rows);
        }
        return $this->allCards;
    }
}
```

This pattern is used by `CachedDB_Manager` in ArkNova. It avoids re-reading the same data within a single request. The cache is destroyed when the PHP instance is garbage collected.

**Important:** The cache must be invalidated when the Manager mutates the data within the same request:

```php
public function playCard(int $cardId, int $playerId): Card
{
    $this->game->DbQuery(...);
    $this->allCards = null;  // Invalidate
    return $this->get($cardId);
}
```

### 12.6 Framework Query Methods Performance

| Method | Returns | Performance | Use When |
|---|---|---|---|
| `getObjectFromDB` | Single row as array | Fast (LIMIT 1) | Fetching by ID |
| `getCollectionFromDB` | All rows keyed by first column | Fast | Batch reads |
| `getNonEmptyCollectionFromDB` | Same, throws if empty | Fast | Required data |
| `getUniqueValueFromDB` | Single scalar value | Fastest | COUNT, SUM, MAX |
| `getObjectListFromDB` | Numeric array of rows | Fast | Ordered results |
| `DbQuery` | Affected row count | Fast | INSERT, UPDATE, DELETE |
| `DbCommand` | Affected row count (prepared) | Slightly slower | Modern API with params |

---

## 13. Scaling

### 13.1 Small Game (5-15 states, < 50 files)

**Persistence profile:**
- 2-4 custom tables
- 10-20 globals
- No undo log
- No Engine persistence
- Direct framework query methods

**Schema:** 2-4 tables in `dbmodel.sql`, all with simple structure and no JSON columns.

**Globals:** Raw `$this->bga->globals->get/set` calls or a minimal `Globals` class.

**Deck:** Framework Deck module (no custom table needed unless cards have per-card state).

### 13.2 Medium Game (15-40 states, 50-150 files)

**Persistence profile:**
- 4-8 custom tables
- 20-40 globals
- Optional undo log
- Optional Engine persistence
- Custom `Globals` class

**Schema:** Tables may have JSON columns for extra data. Foreign keys documented. Version columns on log table if undo is supported.

**Globals:** Typed `Globals` class with getters/setters.

**Deck:** Custom table if cards need per-card state; framework Deck module otherwise.

**Caching:** Request-scoped cache in Managers.

### 13.3 Large Game (40-80 states, 150-400 files)

**Persistence profile:**
- 6-10 custom tables
- 40-80 globals
- Full undo log with checkpoint/step granularity
- Engine tree persistence (serialised JSON in globals)
- Typed Globals class with serialised blob support

**Schema:** Tables with JSON columns for polymorphic data. Composite keys for soft-owned entities. Multiple indexes on query patterns.

**Globals:** Dedicated globals class + Engine tree serialisation.

**Deck:** Always custom table (cards have per-card state, extra_datas JSON).

**Caching:** `CachedDB_Manager` pattern for in-request row caching.

### 13.4 Very Large Game (80+ states, 400+ files)

**Persistence profile:**
- 8-15+ custom tables
- 80+ globals with domain-specific sub-classes
- Full undo log with cross-player invalidation
- Engine tree or command queue persistence
- Multiple JSON columns with structured schemas

**Schema:** Tables organised by domain with clear ownership annotations. Campaign/persistence tables for solo mode. Separate score detail tables for end-game breakdown.

**Globals:** Split by domain into sub-classes (`GamePhaseGlobals`, `EngineGlobals`, `PlayerGlobals`).

**Deck:** Always custom with domain-specific columns (sprout counts, growth counters, etc.).

**Caching:** Multi-level: request-scoped row cache, lazy collections, eager loading strategies.

**Performance:** Batch operations preferred over individual queries. Index analysis for every query pattern. Notification payload optimisation for archive storage.

### 13.5 Reference Project Persistence Comparison

| Aspect | Arnak (Medium) | Agricola (Large) | ArkNova (Large) | Earth (Very Large) |
|---|---|---|---|---|
| Custom tables | 6 | 5 | ~8 | 8 |
| JSON columns | No | `extra_datas` | `extra_datas` | No (annotation-based mapping) |
| Undo log | No dedicated | `log` table | `log` table | `action_command` table |
| Engine persistence | N/A | JSON in globals | JSON in globals | Command queue in action_command |
| Deck | Custom | Custom (`Pieces`) | Custom (`Pieces`) | Custom (with sprout/growth) |
| Caching | None | None visible | `CachedDB_Manager` | Annotation-driven DB rows |
| Globals style | Raw | Typed class | Typed class | Custom DB table (game_state) |

---

## 14. Anti-Patterns

### 14.1 Database as Cache

**Symptom:** Storing computed values in the database that can be derived from other columns.

```sql
-- BAD: redundant column
CREATE TABLE player (
    player_id INT PRIMARY KEY,
    player_score INT NOT NULL,
    player_score_display INT NOT NULL,  -- Same as player_score but formatted
    ...
);
```

**Solution:** Compute derived values at read time. Use the `Model` layer for formatting:

```php
class Player
{
    public function getScoreDisplay(): string
    {
        return number_format($this->score);
    }
}
```

The only exception is score columns stored on the `player` table — these are the authoritative source, not a cache.

### 14.2 Globals Abuse

**Symptom:** Entity data stored in globals instead of entity tables.

```php
// BAD — player state in globals
Globals::set('player_coins_' . $playerId, $coins);
Globals::set('player_hand_' . $playerId, json_encode($hand));

// GOOD — player state on the player table
$this->players->setCoins($playerId, $coins);
$this->cards->getHand($playerId);
```

**Detection:** If a global key contains a player ID, card ID, or any domain entity identifier, it likely belongs on an entity table.

**See:** [domain-architecture.md §9.4](./domain-architecture.md#94-when-globals-become-an-anti-pattern) for four specific symptoms.

### 14.3 JSON Abuse

**Symptom:** Entire game state stored in a single JSON column.

```sql
-- BAD: everything in one JSON blob
CREATE TABLE game_state (
    game_id INT PRIMARY KEY,
    state JSON NOT NULL  -- { players: [...], cards: [...], board: {...} }
);
```

This defeats the purpose of a relational database — you cannot query individual fields, add indexes, or maintain referential integrity.

**When JSON IS appropriate:**
- Card extra data that varies per card type
- Engine tree state (single blob, only one query pattern)
- Player preferences (small, infrequently queried)

**When JSON IS NOT appropriate:**
- Data that needs to be indexed or filtered
- Data that has its own lifecycle
- Data that should have foreign key constraints
- Large blobs that are read more frequently than the primary columns

### 14.4 Cross-Manager Writes

**Symptom:** A Manager writes to a table it does not own.

```php
// BAD — CardsManager writes to player table
class Cards extends Manager
{
    public function playCard(int $cardId, int $playerId): void
    {
        $this->game->DbQuery("UPDATE card SET ...");
        $this->game->DbQuery("UPDATE player SET player_score = ...");  // Cross-manager write!
    }
}
```

**Solution:** Only the owning Manager writes to its table. Cross-manager operations are orchestrated by the action method.

See [domain-architecture.md §4.7](./domain-architecture.md#47-cross-manager-interaction).

### 14.5 Duplicate State

**Symptom:** The same logical state exists in two places, risking divergence.

```sql
-- BAD: hand size in two places
CREATE TABLE player (
    player_id INT PRIMARY KEY,
    hand_size INT NOT NULL DEFAULT 0,  ← derived from card table
    ...
);

CREATE TABLE card (
    card_id INT PRIMARY KEY,
    card_location VARCHAR(16),
    card_location_arg INT,
    ...
);
-- Now hand_size can fall out of sync with COUNT(*) FROM card WHERE location='hand'
```

**Solution:** Derive counts from the source of truth:

```php
public function getHandSize(int $playerId): int
{
    return (int)$this->game->getUniqueValueFromDB(
        "SELECT COUNT(*) FROM card
         WHERE card_location = 'hand' AND card_location_arg = $playerId"
    );
}
```

If performance requires a cached count, use a single column on the entity table and update it atomically alongside every mutation that affects it.

### 14.6 Denormalisation Mistakes

**Symptom:** Denormalised data chosen for convenience rather than performance.

```sql
-- BAD: denormalised player name on card table
CREATE TABLE card (
    card_id INT PRIMARY KEY,
    card_location VARCHAR(16),
    player_name VARCHAR(32)  ← denormalised from player
);
```

**When denormalisation is acceptable:**
- The denormalised value is read far more often than it changes
- The value is always updated atomically alongside the source
- The performance gain is measured, not assumed

**When denormalisation is harmful:**
- The value can be derived with a simple JOIN
- The value changes independently of the entity
- The denormalisation adds update anomalies

Player names should always be read from the framework's `player` table, never copied to entity tables.

---

## 15. Migration Strategy

### 15.1 Schema Evolution

BGA provides `upgradeTableDb(int $fromVersion)` for post-release schema changes:

```php
// In Game.php
public function upgradeTableDb($fromVersion): void
{
    if ($fromVersion <= 1) {
        $this->applyDbUpgrade(
            "ALTER TABLE card ADD COLUMN `card_state` INT NOT NULL DEFAULT 0"
        );
    }
    if ($fromVersion <= 2) {
        $this->applyDbUpgrade(
            "ALTER TABLE card ADD INDEX `card_location_state` (`card_location`, `card_state`)"
        );
    }
}
```

**Migration rules:**
- Always add columns, never remove them (backward compatibility for ongoing games)
- Always provide a default value for new columns (so existing rows have a valid value)
- Add indexes after data is populated (indexing large tables can be slow)
- Test migrations on a copy of production data

### 15.2 Adding Columns

New columns should be:
- `NOT NULL DEFAULT <value>` for scalar types
- `NULL` for optional data
- Added after existing columns or at the end of the table

```sql
ALTER TABLE card
    ADD COLUMN `card_state` int(11) NOT NULL DEFAULT 0
    AFTER `card_location_arg`;
```

### 15.3 Backward Compatibility

Running games must not be broken by schema changes:

- **New columns** must have defaults so existing rows are valid
- **New tables** can be created freely (they don't affect existing queries)
- **Removing columns** is forbidden (existing game code may reference them)
- **Renaming columns** must be done with an alias (add new column, populate from old, keep old for backward compatibility)

```php
// Migration: rename player_score → player_total_score
$fromVersion <= 3 => {
    // Add new column
    $this->applyDbUpgrade(
        "ALTER TABLE player ADD COLUMN `player_total_score` INT NOT NULL DEFAULT 0"
    );
    // Copy data
    $this->applyDbUpgrade(
        "UPDATE player SET player_total_score = player_score"
    );
    // Keep old column for backward compatibility — remove only in next major release
};
```

### 15.4 Schema Documentation

Every table in `dbmodel.sql` should have a header comment:

```sql
-- TABLE: card
-- OWNER: Managers/Cards
-- AGGREGATE: Card
-- CREATED: 2024-01-01
-- PURPOSE: All card instances. Cards move through locations (deck/hand/play/discard).
--          card_state tracks orientation (0=face_down, 1=face_up).
--          extra_datas stores card-type-specific attributes as JSON.
--
-- COLUMNS:
--   card_id            PK, auto-increment
--   card_type          Type identifier (matches material.inc.php)
--   card_type_arg      Numeric argument (card number or variant)
--   card_location      Current location (deck/hand/play/discard/offer)
--   card_location_arg  Location-specific argument (player_id for hand, sort order for deck)
--   card_state         Bit field: 1=face_up, 2=locked, 4=exhausted
--   extra_datas        JSON: card-type-specific attributes

CREATE TABLE IF NOT EXISTS `card` (
    `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `card_type` varchar(16) NOT NULL,
    `card_type_arg` int(11) NOT NULL,
    `card_location` varchar(16) NOT NULL,
    `card_location_arg` int(11) NOT NULL,
    `card_state` int(11) NOT NULL DEFAULT 0,
    `extra_datas` json DEFAULT NULL,
    PRIMARY KEY (`card_id`),
    KEY `location` (`card_location`, `card_location_arg`),
    KEY `type` (`card_type`, `card_type_arg`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
```

---

## 16. Templates

### 16.1 Canonical Table

```sql
-- TABLE: <table_name>
-- OWNER: Managers/<ManagerName>
-- AGGREGATE: <AggregateRootName>
-- PURPOSE: <Single sentence describing what this table stores>

CREATE TABLE IF NOT EXISTS `<table_name>` (
    `<table_name>_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `<table_name>_type` varchar(16) NOT NULL,
    `<table_name>_location` varchar(16) NOT NULL,
    `<table_name>_location_arg` int(11) NOT NULL DEFAULT 0,
    `<table_name>_state` int(11) NOT NULL DEFAULT 0,
    `player_id` int(10) unsigned DEFAULT NULL,
    `extra_datas` json DEFAULT NULL,
    PRIMARY KEY (`<table_name>_id`),
    KEY `location` (`<table_name>_location`, `<table_name>_location_arg`),
    KEY `player` (`player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
```

### 16.2 Canonical Manager Persistence Methods

```php
class Cards extends Manager
{
    // === READ ===

    public function get(int $id): Card
    {
        $row = $this->game->getObjectFromDB(
            "SELECT * FROM card WHERE card_id = $id"
        );
        if (!$row) {
            throw new \BgaSystemException("Card $id not found");
        }
        return $this->rowToCard($row);
    }

    public function getAll(): array
    {
        $rows = $this->game->getCollectionFromDB("SELECT * FROM card");
        return array_map(fn($r) => $this->rowToCard($r), $rows);
    }

    public function getByLocation(string $location, int $arg = 0): array
    {
        $rows = $this->game->getCollectionFromDB(
            "SELECT * FROM card
             WHERE card_location = '$location'
               AND card_location_arg = $arg
             ORDER BY card_location_arg"
        );
        return array_map(fn($r) => $this->rowToCard($r), $rows);
    }

    // === CREATE ===

    public function create(string $type, string $typeArg, string $location, int $locArg = 0): Card
    {
        $this->game->DbQuery(
            "INSERT INTO card (card_type, card_type_arg, card_location, card_location_arg)
             VALUES ('$type', '$typeArg', '$location', $locArg)"
        );
        return $this->get((int)$this->game->bga->db->getLastInsertId());
    }

    // === UPDATE (location) ===

    public function move(int $id, string $location, int $locArg = 0): Card
    {
        $this->game->DbQuery(
            "UPDATE card
             SET card_location = '$location', card_location_arg = $locArg
             WHERE card_id = $id"
        );
        return $this->get($id);
    }

    // === ATOMIC UPDATE (concurrency-safe) ===

    public function claim(int $id, int $playerId): Card
    {
        $affected = $this->game->DbQuery(
            "UPDATE card
             SET card_location = 'hand', card_location_arg = $playerId
             WHERE card_id = $id
               AND card_location = 'offer'"
        );
        if ($affected === 0) {
            throw new \BgaUserException(clienttranslate('That card is no longer available'));
        }
        return $this->get($id);
    }

    // === BATCH UPDATE ===

    public function moveMultiple(array $cardIds, string $location, int $locArg = 0): void
    {
        $idList = implode(',', array_map('intval', $cardIds));
        $this->game->DbQuery(
            "UPDATE card
             SET card_location = '$location', card_location_arg = $locArg
             WHERE card_id IN ($idList)"
        );
    }

    // === DELETE ===

    public function remove(int $id): void
    {
        $this->game->DbQuery("DELETE FROM card WHERE card_id = $id");
    }

    // === COUNT ===

    public function countByLocation(string $location, int $arg = 0): int
    {
        return (int)$this->game->getUniqueValueFromDB(
            "SELECT COUNT(*) FROM card
             WHERE card_location = '$location' AND card_location_arg = $arg"
        );
    }

    // === INTERNAL ===

    private function rowToCard(array $row): Card
    {
        return new Card(
            id: (int)$row['card_id'],
            type: $row['card_type'],
            typeArg: $row['card_type_arg'],
            location: $row['card_location'],
            locationArg: (int)$row['card_location_arg'],
            state: (int)$row['card_state'],
            extraDatas: json_decode($row['extra_datas'] ?? '{}', true),
        );
    }
}
```

### 16.3 Canonical Globals

```php
<?php

declare(strict_types=1);

namespace MyGame\Core;

class Globals
{
    // Key constants — single source of truth for key names
    private const KEY_ROUND = 'currentRound';
    private const KEY_PHASE = 'gamePhase';
    private const KEY_FIRST_PLAYER = 'firstPlayerId';
    private const KEY_ENGINE_TREE = 'engineTree';

    // === Typed accessors ===

    public static function getCurrentRound(): int
    {
        return (int)self::get(self::KEY_ROUND, 1);
    }

    public static function setCurrentRound(int $round): void
    {
        self::set(self::KEY_ROUND, $round);
    }

    public static function getGamePhase(): string
    {
        return self::get(self::KEY_PHASE, 'setup');
    }

    public static function setGamePhase(string $phase): void
    {
        $valid = ['setup', 'action', 'break', 'end'];
        if (!in_array($phase, $valid, true)) {
            throw new \BgaSystemException("Invalid phase: $phase");
        }
        self::set(self::KEY_PHASE, $phase);
    }

    public static function getFirstPlayerId(): int
    {
        return (int)self::get(self::KEY_FIRST_PLAYER, 0);
    }

    public static function setFirstPlayerId(int $playerId): void
    {
        self::set(self::KEY_FIRST_PLAYER, $playerId);
    }

    // === Serialised blob accessors ===

    public static function getEngineTree(): ?array
    {
        $raw = self::get(self::KEY_ENGINE_TREE);
        return $raw ? json_decode($raw, true) : null;
    }

    public static function setEngineTree(array $tree): void
    {
        self::set(self::KEY_ENGINE_TREE, json_encode($tree));
    }

    public static function clearEngineTree(): void
    {
        self::set(self::KEY_ENGINE_TREE, null);
    }

    // === Internal ===

    private static function get(string $key, mixed $default = null): mixed
    {
        return Game::get()->bga->globals->get($key) ?? $default;
    }

    private static function set(string $key, mixed $value): void
    {
        Game::get()->bga->globals->set($key, $value);
    }
}
```

### 16.4 Canonical Deck Wrapper

A wrapper that provides the Deck module's API while adding custom persistence:

```php
<?php

declare(strict_types=1);

namespace MyGame\Managers;

use MyGame\Models\Card;

class DeckManager
{
    public function __construct(
        private Game $game,
        private string $tableName = 'card',
    ) {}

    // === CREATE ===

    public function createCards(array $definitions, string $location, int $locArg = 0): void
    {
        $values = [];
        foreach ($definitions as $card) {
            $type = $this->game->escapeString($card['type']);
            $typeArg = (int)($card['type_arg'] ?? 0);
            $values[] = "('$type', $typeArg, '$location', $locArg)";
        }
        $this->game->DbQuery(
            "INSERT INTO {$this->tableName} (card_type, card_type_arg, card_location, card_location_arg)
             VALUES " . implode(', ', $values)
        );
    }

    // === SHUFFLE ===

    public function shuffle(string $location): void
    {
        $cards = $this->game->getCollectionFromDB(
            "SELECT card_id FROM {$this->tableName}
             WHERE card_location = '$location'
             ORDER BY RAND()"
        );
        $order = 0;
        foreach ($cards as $cardId => $row) {
            $this->game->DbQuery(
                "UPDATE {$this->tableName}
                 SET card_location_arg = $order
                 WHERE card_id = $cardId"
            );
            $order++;
        }
    }

    // === PICK ===

    public function pickCard(string $fromLocation, int $toArg, string $toLocation = 'hand'): Card
    {
        $cardId = $this->game->getUniqueValueFromDB(
            "SELECT card_id FROM {$this->tableName}
             WHERE card_location = '$fromLocation'
             ORDER BY card_location_arg ASC
             LIMIT 1"
        );
        if (!$cardId) {
            throw new \BgaSystemException("No cards in '$fromLocation'");
        }
        $this->moveCard((int)$cardId, $toLocation, $toArg);
        return $this->getCard((int)$cardId);
    }

    public function pickCards(int $count, string $fromLocation, int $toArg, string $toLocation = 'hand'): array
    {
        $cardIds = $this->game->getCollectionFromDB(
            "SELECT card_id FROM {$this->tableName}
             WHERE card_location = '$fromLocation'
             ORDER BY card_location_arg ASC
             LIMIT $count",
            true  // flat list
        );
        $cardIds = array_keys($cardIds);
        $this->moveCards($cardIds, $toLocation, $toArg);
        return array_map(fn($id) => $this->getCard($id), $cardIds);
    }

    // === MOVE ===

    public function moveCard(int $cardId, string $location, int $locArg = 0): void
    {
        $this->game->DbQuery(
            "UPDATE {$this->tableName}
             SET card_location = '$location', card_location_arg = $locArg
             WHERE card_id = $cardId"
        );
    }

    public function moveCards(array $cardIds, string $location, int $locArg = 0): void
    {
        $idList = implode(',', array_map('intval', $cardIds));
        $this->game->DbQuery(
            "UPDATE {$this->tableName}
             SET card_location = '$location', card_location_arg = $locArg
             WHERE card_id IN ($idList)"
        );
    }

    public function moveAll(string $fromLocation, string $toLocation, int $toArg = 0): void
    {
        $this->game->DbQuery(
            "UPDATE {$this->tableName}
             SET card_location = '$toLocation', card_location_arg = $toArg
             WHERE card_location = '$fromLocation'"
        );
    }

    // === QUERY ===

    public function countCards(string $location, int $arg = 0): int
    {
        $where = "card_location = '$location'";
        if ($arg !== 0) {
            $where .= " AND card_location_arg = $arg";
        }
        return (int)$this->game->getUniqueValueFromDB(
            "SELECT COUNT(*) FROM {$this->tableName} WHERE $where"
        );
    }

    // For reading cards as Models, delegate to the Cards Manager
    private function getCard(int $cardId): Card
    {
        return $this->game->cards->get($cardId);
    }
}
```

---

## 17. Checklists

### 17.1 Production Readiness Checklist

- [ ] Every custom table is owned by exactly one Manager (documented in comments)
- [ ] Every table has a primary key (auto-increment for entity tables)
- [ ] Every table has indexes for its query patterns (at minimum: location, player_id)
- [ ] All entity data is on entity tables — nothing important lives only in globals
- [ ] Globals are typed with a `Globals` class, not raw `set/get` calls
- [ ] The Deck module or custom deck implements shuffle, pick, and move correctly
- [ ] All writes within an action validate before mutate
- [ ] Simultaneous actions use atomic conditional UPDATE (not read-then-write)
- [ ] Undo log table (if used) has a cancellation flag (`log_cancel`)
- [ ] Migration path is defined (`upgradeTableDb`) for future schema changes
- [ ] No cross-manager writes (Manager A does not write to Manager B's table)
- [ ] JSON columns are used only for genuinely polymorphic data
- [ ] Request-scoped caches are invalidated on mutation
- [ ] No duplicate state (same value in two places)
- [ ] Foreign keys are defined for referential integrity

### 17.2 Schema Review Checklist

- [ ] Table name follows `snake_case` convention
- [ ] Primary key is `<table_name>_id` for entity tables
- [ ] All columns have explicit `NOT NULL` or `DEFAULT` (no implicit NULL)
- [ ] JSON columns are documented with expected structure
- [ ] Indexes exist for every WHERE clause pattern
- [ ] No ENUM types (use VARCHAR + application validation)
- [ ] No redundant columns (values derivable from other columns)
- [ ] Version/timestamp columns are present if needed for undo
- [ ] `upgradeTableDb()` handles all past schema versions
- [ ] Comments document owner, aggregate, and purpose for each table

### 17.3 Performance Review Checklist

- [ ] No N+1 query patterns (verify with 4+ player count)
- [ ] Batch reads used instead of individual `get()` calls in loops
- [ ] Batch writes (CASE/IN) used instead of individual UPDATES in loops
- [ ] Indexes are added for the specific ORDER BY patterns used
- [ ] JOINs are indexed on both sides
- [ ] Request-scoped cache is used for data read multiple times
- [ ] Request-scoped cache is invalidated on mutation
- [ ] NOT EXISTS or COUNT(*) queries are indexed
- [ ] Engine tree (if used) is pruned of resolved nodes before serialisation
- [ ] Notification payloads avoid full row dumps (use `filterCardDatas`)
- [ ] Archive storage is considered for gamelog size
- [ ] No `SELECT *` patterns that read more columns than needed (use explicit column lists for large rows)

---

## References

- [domain-architecture.md](./domain-architecture.md) — manager ownership (§4), entity lifecycle (§6), aggregates (§7), dependency rules (§14), testing implications (§15)
- [game-flow-architecture.md](./game-flow-architecture.md) — transaction boundaries (§5), execution pipeline (§2), the implicit transaction model (§5.1)
- [action-architecture.md](./action-architecture.md) — validation-before-mutate (§6.5), transaction behaviour (§9), manager delegation (§11)
- [state-machine-architecture.md](./state-machine-architecture.md) — state IDs, state persistence in globals
- [notification-patterns.md](./notification-patterns.md) — gamelog table, undo interactions (§12), reconnect (§10)
- [reference-project-analysis.md](../foundation/reference-project-analysis.md) — database patterns per project (⭐ ratings), table schemas, undo systems
- [bga-developer-handbook.md](../foundation/bga-developer-handbook.md) — database model (§3), globals (§5), Deck component (§7), upgradeTableDb (§5)
