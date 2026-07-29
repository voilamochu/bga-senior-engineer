# BGA Domain Architecture — Engineering Standard

**Document purpose:** Define how a BGA game is decomposed into cohesive domain components. Establish the canonical architecture for Manager boundaries, Model design, Entity lifecycle, aggregate roots, cross-domain communication, and scaling strategy. This is the top-level architecture standard; all other standards describe specific subsystems within this framework.

**Applicability:** All new BGA game implementations. Existing projects should use this document as a reference when refactoring toward cleaner domain boundaries.

**Cross-references:**
- [game-flow-architecture.md](./game-flow-architecture.md) — execution pipeline, request lifecycle, separation of responsibilities
- [state-machine-architecture.md](./state-machine-architecture.md) — state lifecycle, transitions, state types
- [action-architecture.md](./action-architecture.md) — action lifecycle, validation layers, manager delegation
- [notification-patterns.md](./notification-patterns.md) — notification payload design, sequencing, undo patterns
- [reference-project-analysis.md](../foundation/reference-project-analysis.md) — project-specific architecture ratings and lineage
- [bga-developer-handbook.md](../foundation/bga-developer-handbook.md) — framework API reference

---

## Table of Contents

- [1. Purpose of Domain Architecture](#1-purpose-of-domain-architecture)
- [2. Layered Architecture](#2-layered-architecture)
- [3. Responsibility Matrix](#3-responsibility-matrix)
- [4. Manager Architecture](#4-manager-architecture)
- [5. Model Architecture](#5-model-architecture)
- [6. Entity Lifecycle](#6-entity-lifecycle)
- [7. Aggregates](#7-aggregates)
- [8. Cross-Domain Communication](#8-cross-domain-communication)
- [9. Globals](#9-globals)
- [10. Engine Interaction](#10-engine-interaction)
- [11. Notifications as Domain Events](#11-notifications-as-domain-events)
- [12. Folder Structure](#12-folder-structure)
- [13. Scaling Strategy](#13-scaling-strategy)
- [14. Dependency Rules](#14-dependency-rules)
- [15. Testing Implications](#15-testing-implications)
- [16. Performance Implications](#16-performance-implications)
- [17. Common Anti-Patterns](#17-common-anti-patterns)
- [18. Refactoring Patterns](#18-refactoring-patterns)
- [19. Canonical Architecture](#19-canonical-architecture)
- [20. Templates](#20-templates)
- [21. Checklists](#21-checklists)

---

## 1. Purpose of Domain Architecture

Domain architecture defines the boundary lines of a BGA game implementation. It answers:

- Which subsystem owns which data?
- How do subsystems communicate?
- Where does business logic live?
- What happens when a subsystem grows too large?

Without explicit domain architecture, a BGA project degenerates into:

- **God Game.php** — thousands of lines of inline SQL and game logic
- **God Manager** — a single manager that knows about every table and every rule
- **Circular dependencies** — Manager A calls Manager B which calls Manager A
- **Anemic models** — database rows passed as raw arrays with no behaviour
- **Notification scatter** — `notifyAllPlayers` calls strewn across every class

The reference projects demonstrate that the most maintainable BGA implementations have clear domain boundaries. Agricola and Ark Nova share the same architectural lineage because their domain decomposition follows consistent rules. Earth's command-queue system succeeds because its domain boundaries (command, private state, public state) are clean. Arnak, while simpler, pays a maintainability tax for its monolithic `arnak.game.php`.

This standard establishes those boundary rules explicitly.

---

## 2. Layered Architecture

### 2.1 The Canonical Layer Stack

```
┌──────────────────────────────────────────────────────────────┐
│  FRAMEWORK                                                    │
│  BGA Platform (Table base class, gamestate, Deck, etc.)      │
├──────────────────────────────────────────────────────────────┤
│  GAME                                                         │
│  Game.php — thin coordinator, framework-mandated methods      │
├──────────────────────────────────────────────────────────────┤
│  STATE CLASSES                                                │
│  One class per state — validate, delegate, transition         │
├──────────────────────────────────────────────────────────────┤
│  ACTIONS                                                      │
│  Atomic player intents — #[PossibleAction]-decorated methods  │
├──────────────────────────────────────────────────────────────┤
│  MANAGERS                                                     │
│  Domain logic — one per subsystem, owns its tables            │
├──────────────────────────────────────────────────────────────┤
│  MODELS / ENTITIES                                            │
│  Data objects with behaviour — computed properties, rules     │
├──────────────────────────────────────────────────────────────┤
│  DATABASE                                                     │
│  MySQL tables — the persistent state of the game             │
└──────────────────────────────────────────────────────────────┘
```

### 2.2 Layer Responsibilities

| Layer | Owns | Knows About | Persists |
|---|---|---|---|
| **Framework** | Request lifecycle, transaction, routing, Deck | Nothing game-specific | gamelog, global_preferences |
| **Game** | Framework API surface (`setupNewGame`, `getAllDatas`, `zombie`) | All managers, notifications, globals | Nothing directly |
| **State classes** | Action routing, state args, transitions | Game (for delegation) | Nothing (request-scoped) |
| **Actions** | Validation, coordination | State class (owns it) | Nothing (delegates to managers) |
| **Managers** | Domain logic for one subsystem | Its own table(s), Game for cross-manager | Its own table(s) |
| **Models** | Computed properties, formatting | Nothing (data passed in) | Nothing (stateless) |
| **Database** | Persistent state | Nothing | Everything |

### 2.3 Cross-References to Other Standards

| Layer | Detailed In |
|---|---|
| Framework + Game | [game-flow-architecture.md §3](./game-flow-architecture.md#3-separation-of-responsibilities) |
| State classes | [state-machine-architecture.md §5](./state-machine-architecture.md#5-state-responsibilities) |
| Actions | [action-architecture.md §4](./action-architecture.md#4-action-responsibilities) |
| Managers | §4 of this document |
| Models | §5 of this document |
| Notifications | [notification-patterns.md §15](./notification-patterns.md#15-best-practices) |

---

## 3. Responsibility Matrix

### 3.1 The Complete Matrix

Every code component in a BGA project has a defined role. This matrix is the canonical reference for which component does what.

| Component | Has DB Access? | Can Notify? | Can Throw? | Has State? | Can Access Other Managers? | Knows About HTTP? |
|---|---|---|---|---|---|---|
| **Game** | Yes | Yes | Yes | No (request-scoped) | Yes (all) | No |
| **State** | Yes (via managers) | Yes (via notifications class) | Yes | No (request-scoped) | Yes (all, via game) | No |
| **Action** | No (delegates) | No (delegates) | Yes | No | No (delegates) | No |
| **Manager** | Yes (its own tables only) | Yes (if no notifications class) | Yes | No (all state in DB) | Yes (via game, limited) | No |
| **Model** | No | No | Should not | No (data constructed from DB read) | No | No |
| **Entity** | No | No | Should not | Yes (represents a DB row) | No | No |
| **Value Object** | No | No | No | Yes (immutable) | No | No |
| **Notification class** | No | Yes (wraps framework) | No | No | No | No |
| **Globals** | Yes (global_variables table) | No | No | Yes (persistent KV store) | No | No |
| **Engine** | No (delegates to Globals) | No (delegates to notifications) | Yes | Yes (serialised tree in globals) | Yes (limited, via reference) | No |
| **Helper** | Varies (DB helper yes, utils no) | No | No | No | No | No |
| **Utility** | No | No | No | No | No | No |

### 3.2 Component Definitions

**Game** — The `Game.php` class extending `Table`. Thin coordinator that implements `setupNewGame()`, `getAllDatas()`, `zombie()`, and `giveExtraTime()`. Holds references to all managers, the notifications class, and globals. See [game-flow-architecture.md §3.3](./game-flow-architecture.md#33-the-thin-coordinator-principle).

**State** — A class in `modules/php/States/` extending `GameState`. One per game state. Declares transitions, provides args, hosts `#[PossibleAction]` action methods. Delegates domain logic to managers. See [state-machine-architecture.md §5](./state-machine-architecture.md#5-state-responsibilities).

**Action** — A `#[PossibleAction]`-decorated method on a State class. Validates, delegates execution to managers, delegates notifications to the Notifications class, returns a transition string. Contains zero domain logic. See [action-architecture.md §5](./action-architecture.md#5-thin-action-principle).

**Manager** — A class in `modules/php/Managers/` encapsulating all domain logic for one subsystem. Owns one or more database tables. Provides public read/write API. Encapsulates invariants. May call other managers only through the Game mediator. See §4 of this document.

**Model** — A stateless data object (often a PHP class) that wraps a database row. Computes derived values, formats data for the UI, validates business rules. Models do not read from or write to the database — data is passed into them. See §5 of this document.

**Entity** — A model with identity. The same entity (e.g., a specific card with ID 42) is the same object across requests, identifiable by a primary key. Entities can be mutated (unlike value objects). The distinction between Entity and Model is fuzzy in PHP due to the stateless request model — treat "Entity" as a Model with a persistent identity.

**Value Object** — An immutable data container. Two value objects with the same properties are interchangeable. Examples: `Resources(['coin' => 3, 'stone' => 2])`, `Position(x: 3, y: 7)`, `Cost(cardId: 42, resources: [...])`. Value objects have no database identity. See §5.3.

**Notification class** — A centralized factory in `Core/Notifications.php`. Contains one static method per notification type. Handles i18n, arg resolution, and calling `notifyAllPlayers`/`notifyPlayer`. Never called outside the action → notification pipeline. See [notification-patterns.md §15.1](./notification-patterns.md#151-centralized-notification-class).

**Globals** — Typed global variable manager in `Core/Globals.php`. Wraps `$this->bga->globals` or direct `global_variables` table access with typed getters/setters. Used for game-phase flags, engine state, and cross-turn configuration. See §9 of this document.

**Engine** — Decision-tree flow engine used by Agricola and Ark Nova. Walks a tree of `SeqNode`/`OrNode`/`XorNode`/`ParallelNode`/`LeafNode` nodes. Inverts control — the Engine decides the next state, not the action method. The Engine tree is serialised to globals between requests. See [state-machine-architecture.md §9.3](./state-machine-architecture.md#93-approach-b-engine-tree-agricolaarknova) and §10 of this document.

**Helper** — Utility classes that provide reusable functionality. `Helpers/DB.php` provides SQL abstraction. `Helpers/Collection.php` provides enhanced array operations. Helpers may access the database (DB helper) or be pure functions (Utils). Helpers should be stateless and framework-agnostic where possible.

**Utility** — Pure functions with no side effects and no state. Examples: array shuffling, string formatting, resource-to-string conversion. Utilities never access the database or the framework API.

### 3.3 Component Ownership Diagram

```
┌────────────────────────────────────────────┐
│                Game.php                    │
│  (framework API surface, coordination)     │
├────────────────────────────────────────────┤
│                                            │
│  ┌──────────────┐  ┌──────────────────┐   │
│  │ StateClasses │  │  Core            │   │
│  │ (flow, args) │  │  ├── Globals     │   │
│  └──────┬───────┘  │  ├── Engine      │   │
│         │          │  ├── Notif.      │   │
│         ▼          │  ├── Stats       │   │
│  ┌──────────────┐  │  └── Prefs       │   │
│  │   Actions    │  └──────────────────┘   │
│  │ (validate,   │                         │
│  │  delegate,   │  ┌──────────────────┐   │
│  │  return)     │  │   Managers       │   │
│  └──────┬───────┘  │  ├── Players     │   │
│         │          │  ├── Cards       │   │
│         ▼          │  ├── Board       │   │
│  ┌──────────────┐  │  ├── Scoring     │   │
│  │  Models      │  │  └── ...         │   │
│  │ (computed    │  └──────────────────┘   │
│  │  properties) │                         │
│  └──────────────┘  ┌──────────────────┐   │
│                    │   Helpers        │   │
│                    │  ├── DB          │   │
│                    │  ├── Log         │   │
│                    │  └── Utils       │   │
│                    └──────────────────┘   │
└────────────────────────────────────────────┘
```

---

## 4. Manager Architecture

### 4.1 Definition

A **Manager** is the domain-logic class that owns one or more related database tables and encapsulates all operations on them. Managers are the primary unit of domain decomposition in a BGA project.

### 4.2 Ownership

**Rule — One Manager per aggregate root.**

Each Manager claims exclusive write access to its table(s). No other class performs direct `UPDATE`, `INSERT`, or `DELETE` on those tables.

```php
// CORRECT — Players manager owns the player table
$this->players->addScore($playerId, 5);

// WRONG — direct DB access from outside the owning manager
$this->DbQuery("UPDATE player SET player_score = player_score + 5 WHERE player_id = $playerId");
```

**When a Manager owns multiple tables**, those tables must form a cohesive aggregate (see §7). The `Board` manager might own `board_tiles`, `board_positions`, and `board_meeples` if they are always read and written together.

### 4.3 Lifecycle

```
MANAGER LIFECYCLE

Request arrives
  │
  ▼
Game.php constructed ──→ Manager instances created in constructor
  │                        (or lazy-loaded on first access)
  ▼
Action method runs    ──→ Managers read from DB, mutate, persist
  │
  ▼
Transaction commits   ──→ DB writes permanent
  │
  ▼
PHP instance destroyed ──→ Manager instances garbage collected
```

Managers are **request-scoped**. They are constructed fresh on every HTTP request. Nothing persists in memory between requests. All state lives in the database.

```php
// In Game.php constructor
public function __construct()
{
    parent::__construct();
    $this->players = new Players($this);
    $this->cards = new Cards($this);
    $this->board = new Board($this);
}
```

### 4.4 Boundaries

A Manager's boundary is defined by:

1. **Table ownership** — which tables it exclusively writes to
2. **Domain scope** — what concept it represents (cards, players, board, scoring)
3. **Cohesion** — whether all its methods operate on closely related data

**Boundary test:** If you can describe a Manager's responsibility in one sentence without using "and", it has the right boundary.

| Manager | One-Sentence Description | Pass? |
|---|---|---|
| `Players` | Manages player resources, turn order, and elimination | Pass |
| `Cards` | Manages the deck, hands, and card positions | Pass |
| `Board` | Manages tile placement and board state | Pass |
| `ScoreScoring` | Computes and tracks scores | Pass |
| `CardAndBoardAndPlayers` | Does everything | Fail |

### 4.5 Public API Design

Every Manager exposes a consistent public API pattern:

```php
class Cards extends Manager
{
    // === READ METHODS ===
    public function get(int $cardId): Card
    public function getHand(int $playerId): array
    public function getDeck(): array
    public function getAll(): array

    // === VALIDATION METHODS ===
    public function validateCanPlay(int $cardId, int $playerId): void
    public function validateExists(int $cardId): void

    // === MUTATION METHODS ===
    public function playCard(int $cardId, int $playerId): void
    public function drawCard(int $playerId): Card
    public function discard(int $cardId): void

    // === QUERY METHODS ===
    public function countHand(int $playerId): int
    public function getPlayableCards(int $playerId): array
}
```

**Naming conventions:**

| Prefix | Purpose | Example |
|---|---|---|
| `get` | Read data, return Model or array | `getHand($playerId)` |
| `validate` | Check preconditions, throw on failure | `validateCanPlay($cardId, $playerId)` |
| `can` | Boolean check, no throw | `canPlayCard($cardId, $playerId): bool` |
| (verb) | Execute mutation | `playCard($cardId, $playerId)` |
| `count` | Return count, not collection | `countHand($playerId)` |
| `list` | Return collection for UI | `listPlayableCards($playerId)` |

**What a Manager's public API must NOT expose:**
- Raw database queries
- Internal caching logic
- Framework internals (gamestate, bga->globals directly)
- Unvalidated write methods

### 4.6 Static vs Instance

**Prefer instance methods.** Managers should be instance objects held by Game.php.

```php
// CORRECT — instance
$this->cards->playCard($cardId, $playerId);

// AVOID — static
Cards::playCard($cardId, $playerId);
```

Static managers create testability problems and make dependency injection impossible. The reference projects use both patterns:

| Pattern | Used By | Assessment |
|---|---|---|
| Instance (via Game) | Arnak | Preferred for new projects |
| Static with `Game::get()` | Agricola, Ark Nova | Legacy pattern; works but limits testability |
| Instance (via BX container) | Earth | Modern; uses dependency injection |

**When static is acceptable:**
- In the Notifications class (called from everywhere, no state)
- In pure utility methods
- In the Engine pattern (called from action methods that don't have a Game reference)

### 4.7 Cross-Manager Interaction

Managers must not call each other directly. All cross-manager communication goes through the Game mediator or is orchestrated by the action method.

**Pattern A — Action orchestrates (preferred):**

```php
public function actPlayCard(int $cardId, int $activePlayerId, array $args): string
{
    $this->game->cards->playCard($cardId, $activePlayerId);
    $this->game->players->addResources($activePlayerId, $card->getBonus());
    $this->game->notifications->cardPlayed($activePlayerId, $cardId);
    return 'cardPlayed';
}
```

**Pattern B — Game mediator (for complex interactions):**

```php
class Game extends Table
{
    public function onCardPlayed(int $cardId, int $playerId): void
    {
        $card = $this->cards->get($cardId);
        if ($card->hasBonusResource()) {
            $this->players->addResources($playerId, $card->getBonus());
        }
        if ($card->hasBoardEffect()) {
            $this->board->applyEffect($card->getEffect(), $playerId);
        }
    }
}
```

**Pattern C — Event-based (future, not yet in reference projects):**

Event dispatching for cross-manager effects is not used in any current reference project. The Engine pattern in Agricola/ArkNova approximates this via card listener hooks (`before<Action>`, `computeReplace<Action>`), but these are specific to card effects, not general cross-manager messaging.

**Forbidden pattern — Circular manager calls:**

```php
// FORBIDDEN
class Cards extends Manager
{
    public function playCard($cardId, $playerId): void
    {
        $this->game->players->spendResources($playerId, $cost); // BAD
    }
}
```

The Cards manager should not call Players manager. The action orchestrates both.

---

## 5. Model Architecture

### 5.1 Data Objects vs Entities vs Value Objects

| Type | Identity | Mutable | DB Access | Example |
|---|---|---|---|---|
| **Data Object** | None | Yes | No | Raw `$card = ['id' => 42, 'type' => 'farm']` |
| **Entity** | Has ID | Yes | No | `$card = new Card(42, 'farm'); $card->setLocation('play');` |
| **Value Object** | By value | No | No | `$resources = new Resources(['coin' => 3]);` |

### 5.2 Entity Models

An Entity wraps a database row and provides computed properties, formatting, and validation logic. Entities are **constructed from database data** and are **never persisted directly** — the Manager handles persistence.

```php
class Card
{
    public function __construct(
        private readonly int $id,
        private readonly string $type,
        private readonly string $typeArg,
        private string $location,
        private int $locationArg,
        private array $extraDatas = [],
    ) {}

    // Computed properties
    public function getCost(): Resources
    {
        return match ($this->type) {
            'farm' => new Resources(['coin' => 2]),
            'pasture' => new Resources(['wood' => 1]),
            default => new Resources([]),
        };
    }

    public function getDisplayName(): string
    {
        return $this->type . ' #' . $this->typeArg;
    }

    // UI formatting
    public function toUi(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'location' => $this->location,
        ];
    }

    // Validation
    public function isPlayable(): bool
    {
        return $this->location === 'hand';
    }
}
```

**Rules for Entities:**
- Never access the database
- Never call `notifyAllPlayers` or any framework method
- Never contain gameplay logic that spans multiple entities (that belongs in the Manager)
- Do compute derived values, format for UI, and validate single-entity invariants

### 5.3 Immutable Value Objects

Value objects model concepts that have no identity. Two value objects with the same properties are equivalent.

```php
class Resources
{
    public function __construct(
        private readonly array $resources  // ['coin' => 3, 'wood' => 2]
    ) {}

    // Operations return NEW instances (immutable)
    public function add(Resources $other): Resources
    {
        $result = $this->resources;
        foreach ($other->resources as $type => $amount) {
            $result[$type] = ($result[$type] ?? 0) + $amount;
        }
        return new Resources($result);
    }

    public function subtract(Resources $other): Resources
    {
        $result = $this->resources;
        foreach ($other->resources as $type => $amount) {
            $result[$type] = ($result[$type] ?? 0) - $amount;
        }
        return new Resources($result);
    }

    public function hasAtLeast(Resources $other): bool
    {
        foreach ($other->resources as $type => $amount) {
            if (($this->resources[$type] ?? 0) < $amount) return false;
        }
        return true;
    }

    // Conversion to string for notifications
    public function toStr(): string
    {
        // ... resource-to-icon mapping
    }

    // Factory
    public static function fromArray(array $data): Resources
    {
        return new Resources($data);
    }
}
```

**Value Object rules:**
- All properties are `readonly` (PHP 8.1+) or `private` with no setters
- Mutation methods return a new instance
- Equality is by value, not identity
- No database access
- No framework dependencies
- Should be `final`

### 5.4 Using Models in Managers

Managers construct Models from database reads:

```php
class Cards extends Manager
{
    public function get(int $cardId): Card
    {
        $row = $this->getObjectFromDB(
            "SELECT * FROM card WHERE card_id = $cardId"
        );
        return new Card(
            id: (int)$row['card_id'],
            type: $row['card_type'],
            typeArg: $row['card_type_arg'],
            location: $row['card_location'],
            locationArg: (int)$row['card_location_arg'],
            extraDatas: json_decode($row['extra_datas'] ?? '{}', true),
        );
    }

    public function getHand(int $playerId): array
    {
        $rows = $this->getCollectionFromDB(
            "SELECT * FROM card WHERE card_location = 'hand' AND card_location_arg = $playerId"
        );
        return array_map(fn($row) => $this->rowToCard($row), $rows);
    }
}
```

---

## 6. Entity Lifecycle

Every entity in a BGA project passes through five lifecycle phases. Because the PHP runtime is request-scoped, the lifecycle is tightly coupled to database operations.

### 6.1 Lifecycle Diagram

```
                   ┌──────────┐
                   │ CREATION │  ← setupNewGame() or gameplay action
                   └────┬─────┘
                        │
                        ▼
                   ┌──────────┐
                   │ LOADING  │  ← read from DB at start of each request
                   └────┬─────┘
                        │
                        ▼
                   ┌──────────┐
                   │ MUTATION │  ← domain logic changes entity state
                   └────┬─────┘
                        │
                        ▼
                   ┌──────────┐
                   │ PERSIST  │  ← write to DB within the open transaction
                   └────┬─────┘
                        │
                        ▼
                   ┌──────────┐
                   │ DELETION │  ← rare: card consumed, token removed
                   └──────────┘
```

### 6.2 Creation

Entities are created during `setupNewGame()` or during gameplay (drawing a card, placing a token).

```php
// In Manager
public function createCard(string $type, string $typeArg, string $location, int $locationArg = 0): Card
{
    $id = $this->getUniqueValueFromDB(
        "INSERT INTO card (card_type, card_type_arg, card_location, card_location_arg)
         VALUES ('$type', '$typeArg', '$location', $locationArg)
         RETURNING card_id"
    );
    return $this->get($id);
}
```

**Rules for creation:**
- Only the owning Manager creates entities
- Creation must initialise all required fields
- Creation happens inside the implicit transaction
- Creation returns the entity (not just the ID)

### 6.3 Loading

Entities are loaded at the start of each request. Because PHP is stateless, every request must re-read the database.

```php
public function get(int $cardId): Card
{
    $row = $this->getObjectFromDB(...);
    if (!$row) {
        throw new \BgaSystemException("Card $cardId not found");
    }
    return $this->rowToCard($row);
}
```

**Loading patterns:**

| Pattern | Description | When to Use |
|---|---|---|
| **Eager** | Load all entities at start of request | Small collections (players, visible board) |
| **Lazy** | Load on first access | Card details only when needed |
| **Batch** | Load all in one query | Multiple entities of same type |

**Lazy-loading via `CachedDB_Manager` (ArkNova pattern):**

```php
class CachedDB_Manager
{
    private static array $cache = [];

    public static function get(string $table, int $id): array
    {
        if (!isset(self::$cache[$table][$id])) {
            // Load from DB and cache for this request
            self::$cache[$table][$id] = Game::get()->getObjectFromDB(
                "SELECT * FROM $table WHERE id = $id"
            );
        }
        return self::$cache[$table][$id];
    }
}
```

This is safe because the PHP instance is request-scoped — the cache lives only for the duration of one request.

### 6.4 Mutation

Entities are mutated by their owning Manager. Mutation is a three-step sequence:

```php
public function playCard(int $cardId, int $playerId): Card
{
    // 1. READ current state
    $card = $this->get($cardId);

    // 2. VALIDATE invariants (in entity)
    if (!$card->isPlayable()) {
        throw new \BgaUserException(clienttranslate('This card cannot be played'));
    }

    // 3. PERSIST change (via Manager, not entity)
    $this->DbQuery(
        "UPDATE card SET card_location = 'play', card_location_arg = $playerId
         WHERE card_id = $cardId"
    );

    // Return updated entity
    return $this->get($cardId);
}
```

**Rule — Validate before persist.** All validation must complete before the first DB write. If mutation fails after a write, the transaction rolls back, which is correct but wasteful. See [action-architecture.md §6.5](./action-architecture.md#65-validation-order-principle).

### 6.5 Persistence

Persistence is automatic — BGA wraps every request in a transaction. The Manager issues `INSERT`, `UPDATE`, or `DELETE` queries, and the framework commits at the end of the request if no exception was thrown.

```php
// Inside the implicit transaction — no explicit commit needed
$this->DbQuery(
    "UPDATE player SET player_score = player_score + $points
     WHERE player_id = $playerId"
);
```

**Implications for entities:**
- Entities do not track "dirty" state (the DB is the source of truth)
- Entities are not ORM-mapped — the Manager translates between rows and objects
- There is no unit-of-work pattern; each mutation is a direct SQL query

### 6.6 Deletion

Deletion is rare in BGA games. Most entities are moved between locations (deck → hand → play → discard) rather than deleted. When deletion is necessary:

```php
public function removeCard(int $cardId): void
{
    $card = $this->get($cardId);  // Verify exists
    $this->DbQuery("DELETE FROM card WHERE card_id = $cardId");
}
```

**When to delete vs. move:**
- Delete when the entity is permanently removed from the game (consumed token, spent resource)
- Move when the entity continues to exist in a different state (card in hand → card in play)

### 6.7 Lifecycle in the Engine Pattern

In Agricola and Ark Nova, entity lifecycle is managed through the Engine's action classes:

```php
class Gain extends Action
{
    public function actGain(array $resources, int $playerId, bool $notify = true): void
    {
        // Engine action classes use static Managers
        $player = Players::get($playerId);
        $player->gainResources($resources);
        // The Engine calls Globals::setEngine() to persist tree state
        Engine::resolveAction(['playerId' => $playerId, 'resources' => $resources]);
    }
}
```

Entity creation can happen inside Engine leaf nodes, but the lifecycle pattern (create → load → mutate → persist) remains the same.

---

## 7. Aggregates

### 7.1 Definition

An **aggregate** is a cluster of domain objects that are treated as a single unit. Each aggregate has an **aggregate root** — the single entity that external classes interact with.

In BGA terms: **Every Manager owns exactly one aggregate root.**

### 7.2 Identifying Aggregate Roots

An aggregate root is the natural entry point for a domain concept:

| Aggregate Root | Manager | Owned Tables |
|---|---|---|
| Player | `Players` | `player` |
| Card | `Cards` | `card` |
| Board | `Board` | `board_tiles`, `board_meeples` |
| Action command | `ActionCommandMgr` | `action_command` |

### 7.3 When Multiple Tables Belong to One Manager

Multiple tables should belong to one manager when they form a single aggregate:

**Scenario A — Composition.** The board has tiles and meeples. Tiles cannot exist without the board; meeples cannot exist without tiles. The `Board` manager owns all three tables because they are always read and written as a unit:

```php
class Board extends Manager
{
    // Owns: board_tiles, board_meeples, board_positions
    // Reads and writes all three together

    public function placeTile(int $x, int $y, string $type): void
    {
        // Insert into board_tiles
        // Update board_positions
        // All within one aggregate boundary
    }
}
```

**Scenario B — Strong lifecycle coupling.** A card's `sprout_count` and `growth_count` (Earth) are part of the card aggregate. They are stored as columns on the `card` table (not a separate table) because they have no independent lifecycle.

**Decision tree for table ownership:**

```
Do the tables share a lifecycle?
  ├── NO → Separate managers
  │
  └── YES → Can one exist without the other?
              ├── YES → Separate managers (reference by ID)
              │
              └── NO → Same manager (aggregate)
```

### 7.4 When Tables Should NOT Belong to One Manager

**Scenario A — Independent lifecycles.** Cards and players have independent lifecycles. A card is created (from the deck), moves through the game, and is discarded. A player exists for the entire game. Even though cards reference players (via `card_location_arg = player_id`), they are separate aggregates.

```php
// CORRECT — separate managers for separate aggregates
$this->cards->playCard($cardId, $playerId);
$this->players->addResources($playerId, $card->getCost());
```

**Scenario B — Different change frequencies.** Scoring data (recalculated every turn) and player preferences (set once) should be in different managers, even if both reference the player.

**Scenario C — Cross-cutting concerns.** Logging, undo tracking, and statistics read from many tables but should not own them. They are separate concerns managed by dedicated classes (`Log.php`, `Stats.php`).

### 7.5 Aggregate Boundaries by Reference Project

| Project | Aggregate Roots | Manager Count |
|---|---|---|
| **Arnak** | Player, Card, Board, Site, Assistant, Guardian | ~6 |
| **Agricola** | Player, Card (5 decks), Meeples, Board, Log | ~8 |
| **ArkNova** | Player, ZooCard, ZooMap, ActionCard, ConservationProject | ~8 |
| **Earth** | Player, Card, ActionCommand, GameState, PlayerState | ~7 |

The reference projects converge on **6-8 aggregate roots** regardless of game complexity. Ark Nova (~83 states, massive card pool) has the same number of managers as Arnak (~30 states, medium card pool). The aggregate roots are stable; what scales is the internal complexity of each manager.

---

## 8. Cross-Domain Communication

### 8.1 The Four Patterns

| Pattern | Coupling | Testability | When to Use |
|---|---|---|---|
| **Direct orchestration** | Tight | High | Simple actions with one or two manager calls |
| **Game mediator** | Medium | Medium | Complex interactions involving 3+ managers |
| **Engine orchestration** | Loose | Low | Card-driven flows where steps are dynamic |
| **Events (not yet used)** | Loose | High | Future pattern for decoupled cross-domain effects |

### 8.2 Direct Orchestration (Preferred)

The action method calls managers in sequence. This is the simplest and most testable pattern.

```php
#[PossibleAction]
public function actBuyCard(int $cardId, int $activePlayerId, array $args): string
{
    $this->game->players->validateCanAfford($activePlayerId, $cost);
    $this->game->cards->validateCanBuy($cardId);

    $this->game->players->spendResources($activePlayerId, $cost);
    $this->game->cards->moveToHand($cardId, $activePlayerId);

    $this->game->notifications->cardBought($activePlayerId, $cardId, $cost);
    return 'cardBought';
}
```

**Trade-offs:**
- + Simple, explicit, easy to trace
- + All dependencies visible in the action method
- - Action method must know about all managers involved
- - Duplication if the same orchestration sequence is needed from multiple actions

### 8.3 Game Mediator

The Game class provides methods that coordinate multiple managers. Used when the same cross-manager logic is needed from multiple places.

```php
// In Game.php
public function purchaseCard(int $playerId, int $cardId, Resources $cost): void
{
    $this->players->validateCanAfford($playerId, $cost);
    $this->cards->validateCanBuy($cardId);

    if ($this->cards->hasDiscount($cardId, $playerId)) {
        $cost = $cost->subtract($this->cards->getDiscount($cardId, $playerId));
    }

    $this->players->spendResources($playerId, $cost);
    $this->cards->moveToHand($cardId, $playerId);
    $this->board->applyCardEffect($cardId, $playerId);
}
```

**Trade-offs:**
- + Reusable across multiple action methods and states
- + Centralizes cross-manager business logic
- - Game.php grows (mitigated by traits or service classes)
- - Creates implicit dependency: every caller of `purchaseCard` depends on Players + Cards + Board

### 8.4 Engine Orchestration

The Engine pattern inverts control. The action method does not decide the sequence — the Engine's decision tree does.

```php
// The Engine builds a tree:
$tree = new SeqNode([
    new PayCost($cost),
    new GainResources($reward),
    new DiscardCard($cardId),
]);

// Engine::proceed() walks the tree sequentially
// Each leaf node (action class) calls Managers directly:
class GainResources extends Action
{
    public function act($args): void
    {
        Players::get($args['playerId'])->gainResources($args['resources']);
        Notifications::gainResources($args['player'], $args['resources']);
        Engine::resolveAction($args);  // proceed to next node
    }
}
```

**Trade-offs:**
- + Dynamic composition — cards can inject, replace, or extend steps
- + State machine stays small (few generic states)
- - Complex to implement and debug
- - Action classes use static managers (testability cost)
- - Execution path is implicit, not visible in a single method

### 8.5 Pattern Decision Guide

```
How many managers need to collaborate?
  ├── 1-2 → Direct orchestration
  │
  └── 3+ → Is this sequence reused in multiple places?
            ├── NO  → Direct orchestration (keep in action)
            │
            └── YES → Game mediator (extract to Game or service)
                       OR Engine orchestration (if steps are dynamic)

Are the steps fixed or dynamic?
  ├── Fixed → Direct orchestration or Game mediator
  │
  └── Dynamic (card-dependent) → Engine orchestration
```

### 8.6 Forbidden Patterns

**A — Manager-to-Manager direct calls:**

```php
// FORBIDDEN
class Cards extends Manager
{
    public function playCard($cardId, $playerId): void
    {
        $this->game->players->spendResources($playerId, $this->get($cardId)->getCost());
        // Cards should NOT call Players directly
    }
}
```

**B — Cross-manager constructor injection:**

```php
// FORBIDDEN
class Cards extends Manager
{
    public function __construct(Game $game, private Players $players)
    {
        // Managers should only depend on Game, not on other managers
    }
}
```

**C — Managers calling each other's private methods:**

```php
// FORBIDDEN
$this->game->players->directDbUpdate(...);
// Call the public API method, not internal helpers
```

---

## 9. Globals

### 9.1 Definition

Globals are typed key-value pairs stored in the `global_variables` table (or via `$this->bga->globals`). They provide lightweight storage for game state that does not belong to any specific entity.

### 9.2 When Globals Are Appropriate

| Use Case | Example | Appropriate? |
|---|---|---|
| Game-phase flags | `game_phase = 'round_2'` | Yes |
| Current round number | `current_round = 3` | Yes |
| Engine tree state | `engine_tree = {...serialised...}` | Yes |
| First player | `first_player = 42` | Yes |
| Player hand contents | Stored in card table | No — use the card table |
| Player resources | Stored in player table | No — use the player table |
| Card state | Stored in card table | No — use the card table |

**Rule — Use globals only for state that has no natural entity home.**

### 9.3 The Globals Class Pattern

```php
class Globals
{
    public static function getCurrentRound(): int
    {
        return self::get('currentRound', 1);
    }

    public static function setCurrentRound(int $round): void
    {
        self::set('currentRound', $round);
    }

    public static function isGamePhase(string $phase): bool
    {
        return self::get('gamePhase') === $phase;
    }

    public static function setGamePhase(string $phase): void
    {
        self::set('gamePhase', $phase);
    }

    // Using the modern framework API
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

### 9.4 When Globals Become an Anti-Pattern

**Symptom 1 — Global soup.** A growing collection of unrelated keys with no structure. Instead of 15 disparate globals, group related values:

```php
// BAD — scattered globals
Globals::set('roundNumber', 3);
Globals::set('phase', 'action');
Globals::set('currentPlayerIndex', 2);

// GOOD — structured globals with clear prefixes or a phase object
Globals::setGamePhase('action');
Globals::setCurrentRound(3);
```

**Symptom 2 — Entity data in globals.** If a value describes a specific entity (player, card, board), store it in the entity's table, not in globals:

```php
// BAD — player-specific data in globals
Globals::set('player_has_drawn_' . $playerId, true);

// GOOD — player state on the player table
$this->players->setHasDrawn($playerId, true);
```

**Symptom 3 — JSON blob globals.** Storing large JSON blobs in a single global key. This works (the Engine tree is a JSON blob) but should be limited to a single, well-defined structure.

```php
// ACCEPTABLE — single, well-defined blob
Globals::setEngine($tree->toArray());

// BAD — multiple large blobs
Globals::set('cache_a', $largeArray);
Globals::set('cache_b', $anotherLargeArray);
```

**Symptom 4 — Globals as cache.** Using globals to cache computed values that could be recomputed from entity state. This creates a consistency problem:

```php
// BAD — cached value that can drift from entity state
Globals::set('totalScore', $this->scoring->computeTotal());
// ... later, entity score changes but cached value is stale

// GOOD — compute from source of truth
$this->scoring->computeTotal();
```

### 9.5 Globals in the Reference Projects

| Project | Globals Pattern | Assessment |
|---|---|---|
| Arnak | Direct `$this->bga->globals->set/get` | Simple, no typed wrapper |
| Agricola | `Globals.php` class with typed methods | Good — typed getters/setters |
| ArkNova | `Globals.php` class + JSON for engine state | Good — separates concerns |
| Earth | Custom `GameState` table | Distinct — uses DB table instead of globals |

**Recommendation:** Use a typed `Globals` class (Agricola/ArkNova pattern). Do not use the `global_variables` table directly outside this class. For very large game state (Earth's game_state table), consider a dedicated DB table instead.

---

## 10. Engine Interaction

### 10.1 Where the Engine Fits

The Engine is an optional layer between State classes and Managers. It replaces explicit state transitions with a decision tree.

```
Without Engine:
  State → Action → Manager → State transition (explicit)

With Engine:
  State → Action → Engine → Manager(s) → Engine → State transition (dynamic)
```

### 10.2 Engine's Domain Role

The Engine is **not a domain component**. It is a **flow coordinator**. It does not contain game rules — it determines the sequence of operations. The domain logic lives in Managers and Action classes.

```php
// The Engine does NOT contain domain logic:
class SeqNode
{
    public function proceed(): void
    {
        foreach ($this->children as $child) {
            if (!$child->isResolved()) {
                $child->execute();
                return;
            }
        }
    }
}

// Domain logic lives in the leaf action classes:
class PayCost extends Action
{
    public function execute(): void
    {
        Players::get($this->playerId)->spendResources($this->cost);
    }
}
```

### 10.3 Engine's Data Model

The Engine tree is serialised to globals after each step:

```
┌──────────────┐         ┌─────────────────────┐
│  Engine tree │ ──────► │  Globals::setEngine │ ──► global_variables
│  (in memory) │ ◄────── │  Globals::getEngine │ ◄── (JSON column)
└──────────────┘         └─────────────────────┘
```

This means:
- The tree survives across multiple HTTP requests (multi-step actions)
- The tree is request-scoped when loaded (deserialised each request)
- Only the Engine reads or writes this JSON blob

### 10.4 Engine and Manager Interaction

The Engine does not call Managers directly. Leaf node action classes (instantiated by the Engine) call static Managers:

```php
// Leaf action class — called by Engine
class PlaceFarmer extends Action
{
    public function actPlaceFarmer(int $spaceId, int $playerId): void
    {
        // Static call to Manager
        Players::get($playerId)->placeWorker($spaceId);
        Notifications::placeWorker(Players::get($playerId), $spaceId);

        // Tell Engine to proceed
        Engine::resolveAction(['playerId' => $playerId, 'spaceId' => $spaceId]);
    }
}
```

**Architectural implication:** Managers are called statically (Agricola/ArkNova pattern) rather than through the instance Game reference. This is a trade-off — it simplifies the action class API but makes managers harder to mock.

### 10.5 Engine and State Machine Interaction

The Engine determines which framework state to jump to based on the current tree state:

```php
// Engine::proceed() returns a route instruction
public static function proceed(): string
{
    $node = self::getNextUnresolved();

    if ($node === null) {
        // Tree fully resolved → confirm turn
        return 'confirmTurn';
    }

    if ($node instanceof OrNode) {
        // Player must choose → choice state
        return 'resolveChoice';
    }

    if ($node instanceof LeafNode) {
        // Atomic action → action state
        return $node->getStateName();
    }

    // Sequenced node → resolve next child (no state change)
    return self::proceed();
}
```

The called state then executes the specific action class.

### 10.6 When to Use the Engine

| Criterion | Engine | Manual States |
|---|---|---|
| Player action has 1-3 fixed steps | No | Yes |
| Cards can inject steps dynamically | Yes | No |
| Action tree is known at compile time | No | Yes |
| Debugging traceability | Harder | Easier |
| Number of states in states.inc.php | ~5 generic | 1 per distinct step |
| Testability of flow logic | Lower | Higher |

See [state-machine-architecture.md §9.5](./state-machine-architecture.md#95-recommendation) for the full decision guide.

---

## 11. Notifications as Domain Events

### 11.1 The Key Distinction

Notifications are often described as "domain events" in BGA discussions. This is architecturally incorrect. A notification is a **presentation event** — its purpose is to update the client UI, not to signal domain logic.

| Aspect | Domain Event | BGA Notification |
|---|---|---|
| Purpose | Trigger downstream domain logic | Update client presentation |
| Consumer | Other domain components | Client JavaScript handlers |
| Content | Business-meaningful data | UI-ready serialised data |
| Persistence | Event store (optional) | gamelog table |
| Timing | Before or after transaction | Within transaction, queued |
| Idempotency | Must be idempotent | Client handles replay |

### 11.2 Why This Distinction Matters

If notifications are treated as domain events, domain logic creeps into notification handlers:

```php
// ANTI-PATTERN — domain logic in notification
public function onCardPlayed(): void
{
    $this->cards->removeFromHand($cardId);
    $this->updateScore($playerId);
}
```

The client must not contain domain logic. Notifications are pure presentation. See [game-flow-architecture.md §6](./game-flow-architecture.md#6-server-authority).

### 11.3 What Replaces Domain Events in BGA

BGA has no event bus. Cross-domain communication uses the patterns in §8 (direct orchestration, game mediator, engine).

**Notification callbacks are client-side only.** The server's notification class sends data. The client's `notif_` method receives it and updates the DOM. Nothing in between contains domain logic.

```
Server domain logic (Managers)
  │
  ▼
Notifications class (presentation factory)
  │
  ▼
Framework (queues, transports)
  │
  ▼
Client notif_ handler (DOM update)
```

See [notification-patterns.md §1](./notification-patterns.md#1-purpose-of-notifications) for the four purposes of notifications.

### 11.4 The One Exception: Delta Updates

ArkNova's notification delta system (`$listeners` / `updateIfNeeded`) attaches changed player state to every notification. This is not domain logic in the notification — it is an optimisation that piggybacks computed state deltas on the notification pipeline. The computation (`$player->getScore()`) still happens in the domain layer.

```php
// In Domain — delta computation happens before notification
$listeners = [
    ['name' => 'score', 'method' => 'getScore'],
    ['name' => 'icons', 'method' => 'getIconCount'],
];

public static function updateIfNeeded(&$args, $notifName, $notifType): void
{
    foreach (self::$listeners as $listener) {
        // Call domain method, attach delta to notification args
        $val = $player->{$listener['method']}();
        // ... compare with cached value, attach if changed
    }
}
```

This is still a presentation optimisation, not domain logic in the notification channel.

---

## 12. Folder Structure

### 12.1 Small Games (5-15 states, < 50 files)

```
modules/
├── php/
│   ├── Game.php
│   ├── States/
│   │   ├── PlayerTurn.php
│   │   └── GameEnd.php
│   ├── Managers/
│   │   ├── Players.php
│   │   ├── Cards.php
│   │   └── Board.php
│   ├── Core/
│   │   ├── Globals.php
│   │   └── Notifications.php
│   └── Helpers/
│       └── Utils.php
└── js/
    ├── Game.js
    └── Game.css
```

**Characteristics:**
- Inline Notifications (no separate methods per type)
- State classes are thin, managers are compact
- One CSS file for all styling
- No Engine, no command queue
- Suitable for: fill-and-pass games, simple card games, abstract games

### 12.2 Medium Games (15-40 states, 50-150 files)

```
modules/
├── php/
│   ├── Game.php
│   ├── States/
│   │   ├── PlayerTurn.php
│   │   ├── ResolveChoice.php
│   │   ├── ScoreRound.php
│   │   ├── GameEnd.php
│   │   └── StateIds.php            ← named constants for state IDs
│   ├── Managers/
│   │   ├── Players.php
│   │   ├── Cards.php
│   │   ├── Board.php
│   │   ├── Scoring.php
│   │   └── Actions.php             ← atomic action registry (Engine pattern)
│   ├── Models/
│   │   ├── Player.php
│   │   ├── Card.php
│   │   └── Resources.php           ← value object
│   ├── Core/
│   │   ├── Globals.php
│   │   ├── Notifications.php
│   │   └── Stats.php
│   └── Helpers/
│       ├── DB.php
│       ├── Collection.php
│       └── Utils.php
└── js/
    ├── Game.js
    ├── Managers/
    │   ├── CardMgr.js
    │   ├── BoardMgr.js
    │   └── PlayerPanelMgr.js
    ├── States/
    │   └── PlayerTurn.js
    └── styles/
        ├── cards.css
        └── board.css
```

**Characteristics:**
- Separate Models/ directory for entities and value objects
- Client-side Manager pattern (one per UI subsystem)
- Notifications class has one method per notification type
- Named constants for state IDs (see [state-machine-architecture.md §6.1](./state-machine-architecture.md#61-principles))
- Suitable for: Arnak-level complexity, medium card pools

### 12.3 Large Games (40-80 states, 150-400 files)

```
modules/
├── php/
│   ├── Game.php
│   ├── States/
│   │   ├── PlayerTurn.php
│   │   ├── ResolveChoice.php
│   │   ├── ScoreRound.php
│   │   ├── GameEnd.php
│   │   ├── Setup.php
│   │   ├── BreakPhase/                 ← sub-domain state group
│   │   │   ├── PreBreak.php
│   │   │   ├── DiscardPhase.php
│   │   │   └── IncomePhase.php
│   │   └── StateIds.php
│   ├── Managers/
│   │   ├── Players.php
│   │   ├── Cards.php
│   │   ├── Board.php
│   │   ├── Scoring.php
│   │   ├── Actions.php
│   │   └── Engine/                     ← Engine pattern
│   │       ├── Engine.php
│   │       ├── Nodes/
│   │       │   ├── SeqNode.php
│   │       │   ├── OrNode.php
│   │       │   ├── XorNode.php
│   │       │   ├── ParallelNode.php
│   │       │   └── LeafNode.php
│   │       └── Actions/
│   │           ├── Gain.php
│   │           ├── Pay.php
│   │           ├── Place.php
│   │           └── CardAction.php
│   ├── Models/
│   │   ├── Player.php
│   │   ├── Card.php
│   │   ├── Resources.php
│   │   ├── Cost.php
│   │   └── Effect.php
│   ├── Core/
│   │   ├── Globals.php
│   │   ├── Notifications.php
│   │   ├── Notifications/              ← split by domain
│   │   │   ├── CardNotifications.php
│   │   │   ├── PlayerNotifications.php
│   │   │   └── BoardNotifications.php
│   │   ├── Stats.php
│   │   ├── Preferences.php
│   │   └── Log.php                     ← undo logging
│   └── Helpers/
│       ├── DB.php
│       ├── Collection.php
│       └── Utils.php
└── js/
    ├── Game.js
    ├── Managers/
    │   ├── CardMgr.js
    │   ├── BoardMgr.js
    │   ├── PlayerPanelMgr.js
    │   ├── ScoreMgr.js
    │   └── EffectMgr.js
    ├── States/
    │   ├── PlayerTurn.js
    │   └── BreakPhase.js
    └── styles/
        ├── cards.scss
        ├── board.scss
        └── panels.scss
```

**Characteristics:**
- Engine pattern or command queue for complex flows
- Notifications split by domain if > 500 lines
- States organised into sub-directories for distinct phases
- Log class for undo tracking
- Client managers for every UI subsystem
- SCSS partials for styling
- Suitable for: Agricola/ArkNova complexity, 500+ card pools

### 12.4 Very Large Games (80+ states, 400+ files)

```
modules/
├── php/
│   ├── Game.php
│   ├── States/
│   │   ├── (same as Large + per-expansion state groups)
│   │   ├── ExpansionOne/
│   │   └── ExpansionTwo/
│   ├── Domain/                           ← domain-oriented package
│   │   ├── Players/
│   │   │   ├── PlayerManager.php
│   │   │   ├── PlayerModel.php
│   │   │   └── PlayerValueObjects.php
│   │   ├── Cards/
│   │   │   ├── CardManager.php
│   │   │   ├── CardModel.php
│   │   │   ├── CardValueObjects.php
│   │   │   └── Decks/
│   │   │       ├── BaseDeck.php
│   │   │       └── ExpansionDeck.php
│   │   ├── Board/
│   │   │   ├── BoardManager.php
│   │   │   ├── BoardModel.php
│   │   │   └── Tiles/
│   │   └── Scoring/
│   │       ├── ScoringManager.php
│   │       ├── ScoringModel.php
│   │       └── Calculators/
│   ├── Engine/
│   │   ├── Engine.php
│   │   ├── Nodes/
│   │   └── Actions/
│   ├── Core/
│   │   ├── Globals.php
│   │   ├── Notifications/
│   │   ├── Stats.php
│   │   ├── Log.php
│   │   └── CachedDB.php
│   └── Helpers/
└── js/
    ├── Game.js
    ├── Domain/                            ← mirrors server domain structure
    │   ├── Cards/
    │   ├── Board/
    │   ├── Players/
    │   └── Scoring/
    ├── States/
    └── styles/
```

**Characteristics:**
- Domain-oriented package layout (Domain/{Name}/ instead of Managers/ + Models/)
- Expansion content isolated in subdirectories
- Engine as a top-level directory (when complex enough)
- Client mirrors server domain structure
- Suitable for: Games with expansions integrated at launch, 1000+ cards

### 12.5 Folder Evolution Strategy

Start with the **Medium** layout. Graduate to **Large** when:
- Notifications.php exceeds 500 lines → split into subdirectory
- States/ directory exceeds 15 files → group by phase
- A Manager exceeds 400 lines → extract Model classes and helpers
- Client JS file exceeds 800 lines → introduce client Managers

Graduate to **Very Large** when:
- Total PHP files exceed 200
- Multiple developers work on the same codebase
- Expansions are integrated into the main code

---

## 13. Scaling Strategy

### 13.1 How Arnak Scales

**Approach:** Manual state machine, flat manager structure, medium complexity.

**Strategy:** Arnak treats each game phase as an explicit state. Complexity is managed by keeping the scope bounded — one main action per turn, then a free-action window.

```
Arnak's scaling boundary: ~30 states, ~6 managers, ~2500 lines in Game.php
```

**What breaks when Arnak gets bigger:**
- Game.php becomes a monolith (already 2487 lines — the pain point is visible)
- No Engine means card-driven flow injection requires new states
- Notification scatter (no centralized class) means cross-cutting changes touch many files
- Numeric state IDs make the state machine harder to refactor

**Lesson:** Arnak's architecture works for its complexity level. A larger game with card-driven flow would need either an Engine or a more structured decomposition.

### 13.2 How Agricola Scales

**Approach:** Engine pattern with decision tree, per-card classes, robust undo.

**Strategy:** Agricola separates flow control (Engine) from domain logic (Managers) from card implementation (per-card classes). This allows it to scale to 500+ unique cards without architectural changes.

```
Agricola's scaling boundary: ~40 states, ~8 managers, 500+ card classes, Engine tree
```

**Key scaling enablers:**
- The Engine absorbs flow complexity that would otherwise require hundreds of states
- Per-card classes with listener hooks (`before<Action>`, `computeReplace<Action>`) keep card logic isolated
- The undo Log table handles multi-step rollback without polluting domain logic
- Static Managers with `Game::get()` avoid constructor chains

**Trade-off accepted:** Complexity shifted from the state machine to the Engine. Debugging requires understanding both the state machine AND the Engine tree.

### 13.3 How Ark Nova Scales

**Approach:** Engine pattern + dynamic flow conversion, notification deltas, hex grid.

**Strategy:** Ark Nova extends Agricola's Engine with `FlowConvertor.php` — a system that converts arbitrary bonus structures into Engine flow trees at runtime. This enables cards and sponsors to generate dynamic action sequences.

```
ArkNova's scaling boundary: ~83 states, ~8 managers, 300+ card classes, ~300 notification methods
```

**Key scaling enablers:**
- `FlowConvertor.php` — dynamic tree generation from card bonuses (cards create new flows without new states)
- Notification delta system — only changed values are sent, keeping payload small despite rich state
- `CachedDB_Manager` — in-request caching reduces repeated DB reads
- `Pieces` helper — polymorphic entity storage for different token types

**Key scaling cost:**
- 1672-line Notifications.php (split by domain would improve maintainability)
- 1242-line Player.php (god class by modern standards)
- The delta cache adds complexity that smaller games should not adopt

### 13.4 How Earth Scales

**Approach:** Command queue pattern with private state machines, simultaneous turns.

**Strategy:** Earth solves a fundamentally different scaling problem — simultaneous play. Each player gets an independent private state machine. Actions are queued as commands and committed in a batch.

```
Earth's scaling boundary: ~60 states, ~7 managers, command queue, private state machine
```

**Key scaling enablers:**
- Command pattern (`do()` / `undo()`) isolates each player action
- `ActionCommandMgr` manages the queue lifecycle independently of game state
- `PrivateState` layer wraps BGA's private state API, providing per-player flow
- `reevaluate()` handles cross-player invalidation when committed actions affect pending commands

**Key scaling cost:**
- Highest complexity of all four projects
- Custom locking system (`Lock.php`) needed for race condition prevention
- Command infrastructure is overkill for non-simultaneous games

### 13.5 Why Each Made Different Choices

| Project | Core Challenge | Scaling Choice | Why |
|---|---|---|---|
| **Arnak** | Medium complexity, single developer | Manual states, flat structure | Simplest approach that works; developer could hold entire game in head |
| **Agricola** | Card-driven flow, 500+ cards | Engine + per-card classes | Cards need to inject flow dynamically; manual states would explode |
| **ArkNova** | Dynamic bonuses, rich state | Engine + FlowConvertor + Deltas | Same core as Agricola + need for runtime tree generation from card bonuses |
| **Earth** | Simultaneous play, per-action undo | Command queue + private states | Only approach that supports independent player action + commit model |

**The architectural determinant is not game complexity. It is the nature of player interaction:**

- **Sequential turn, fixed steps** → Manual states (Arnak)
- **Sequential turn, dynamic steps** → Engine (Agricola, ArkNova)
- **Simultaneous turn** → Command queue (Earth)

---

## 14. Dependency Rules

### 14.1 Layer Dependency Diagram

```
Framework (Table, Deck, gamestate)
    ↑
  Game.php
    ↑
State Classes ──→ Notifications (via Game)
    ↑
  Actions (live IN State classes)
    ↑
Managers ──────→ Globals, Helpers (DB, Collection)
    ↑
  Models / Entities / Value Objects
    ↑
  Helpers / Utilities
```

### 14.2 Allowed Dependencies

| Component | May Depend On | Must NOT Depend On |
|---|---|---|
| **Game** | Framework, all managers, notifications, globals, helpers | Nothing (top of the chain) |
| **State** | Game, managers, notifications, state IDs | Other states, Models directly |
| **Action** | Game, managers, notifications | Other actions, Models directly (pass through managers) |
| **Manager** | Game (for mediator), its own models, helpers, DB, Globals | Other managers, notifications (prefer delegation) |
| **Model** | Value objects, utilities | Framework, game, managers, DB |
| **Value Object** | Utilities, other value objects | Framework, game, managers, DB, models |
| **Notification** | Framework notify API, helpers (for arg resolution) | Managers, models, game logic |
| **Globals** | Framework globals API, game (for getInstance) | Managers, models |
| **Engine** | Globals, managers (static calls), action classes, notifications | State machine, game |
| **Helper** | Framework (for DB helper), nothing (for utils) | Managers, models, notifications |
| **Utility** | Nothing | Everything |

### 14.3 Forbidden Dependencies

```
✗ Manager → Manager (direct)
✗ Model → Framework
✗ Model → Manager
✗ Model → Database
✗ State → State (other state classes)
✗ Notification → Manager
✗ Notification → Model
✗ Engine → State machine
✗ Action → Raw SQL
✗ Action → notifyAllPlayers (direct)
```

### 14.4 Enforcing Dependency Rules

**Via directory structure.** The `Managers/` directory should only import from `Core/`, `Helpers/`, and its own domain. The `Models/` directory should import from nowhere outside `Helpers/`.

**Via code review.** Every pull request should be checked for:
- A Manager calling another Manager's write method directly
- A Model importing from the framework namespace
- Raw `notifyAllPlayers` calls outside the Notifications class

**Via testability.** If a class is hard to test because of its dependencies, it likely violates the dependency rules.

---

## 15. Testing Implications

### 15.1 Layer Testability

| Layer | Testability | Strategy |
|---|---|---|
| **Value Objects** | Excellent | Pure PHP unit tests, no framework needed |
| **Models** | Excellent | Construct with data, test computed properties |
| **Helpers** | Good | Pure PHP tests; DB helper needs mock |
| **Managers** | Good | Integration tests with in-memory DB or mock Game |
| **Notifications** | Good | Test args are correct, i18n arrays are complete |
| **Actions** | Moderate | Integration test through the state-action pipeline |
| **State classes** | Moderate | Same as actions — test through the framework |
| **Game** | Hard | Requires framework bootstrap |
| **Engine** | Hard | Requires full Engine tree setup |

### 15.2 What to Test

| Component | What to Test | Example |
|---|---|---|
| **Value Object** | Equality, immutability, arithmetic | `Resources([coin => 3])->add(Resources([coin => 2])) === Resources([coin => 5])` |
| **Model** | Computed property correctness | `Card(type: 'farm')->getCost() === Resources(['coin' => 2])` |
| **Manager** | Invariant enforcement, mutation correctness | `$manager->playCard(42, 1)` moves card to 'play' location |
| **Action** | Validation rejects invalid input, happy path succeeds | `actPlayCard(invalidId)` throws `BgaUserException` |
| **Notification** | Args contain all required keys, i18n array is complete | `$method()` sends expected keys |
| **Globals** | Round-trip set/get, typed accessors | `Globals::setCurrentRound(3)` → `Globals::getCurrentRound() === 3` |

### 15.3 Testing Manager Invariants

```php
final class CardsManagerTest extends TestCase
{
    public function testPlayCardMovesToPlayLocation(): void
    {
        $game = $this->createMock(Game::class);
        $manager = new Cards($game);
        // Setup: create card in hand
        $cardId = $manager->createCard('farm', '1', 'hand', 1);
        // Execute
        $manager->playCard($cardId, 1);
        // Verify
        $card = $manager->get($cardId);
        $this->assertEquals('play', $card->getLocation());
    }

    public function testPlayCardThrowsIfNotInHand(): void
    {
        $this->expectException(\BgaUserException::class);
        $game = $this->createMock(Game::class);
        $manager = new Cards($game);
        $manager->playCard(999, 1);  // Non-existent card
    }
}
```

### 15.4 Testing Value Objects

Value objects are the most testable component — pure PHP with no framework dependencies:

```php
final class ResourcesTest extends TestCase
{
    public function testAdd(): void
    {
        $a = new Resources(['coin' => 3, 'wood' => 1]);
        $b = new Resources(['coin' => 2, 'stone' => 1]);
        $result = $a->add($b);
        $this->assertEquals(new Resources(['coin' => 5, 'wood' => 1, 'stone' => 1]), $result);
    }

    public function testImmutability(): void
    {
        $a = new Resources(['coin' => 3]);
        $a->add(new Resources(['coin' => 2]));
        $this->assertEquals(new Resources(['coin' => 3]), $a);
    }

    public function testHasAtLeast(): void
    {
        $a = new Resources(['coin' => 5, 'wood' => 2]);
        $this->assertTrue($a->hasAtLeast(new Resources(['coin' => 3])));
        $this->assertFalse($a->hasAtLeast(new Resources(['coin' => 6])));
    }
}
```

### 15.5 Testing and the Engine Pattern

Engine-based projects (Agricola, ArkNova) use static Managers, making unit testing harder. The recommended approach is integration testing through the Engine:

```php
// Test a complete Engine action sequence
public function testGainResourcesFlow(): void
{
    // Setup: create tree with Gain node
    $tree = new SeqNode([
        new Gain(Resources::fromArray(['coin' => 3])),
    ]);
    Engine::buildFromTree($tree, 1);

    // Execute: resolve
    Engine::proceed();

    // Verify: player has the resources
    $player = Players::get(1);
    $this->assertEquals(3, $player->getCoins());
}
```

---

## 16. Performance Implications

### 16.1 The BGA Performance Model

Every BGA request is subject to:
- **PHP execution limit** (~30 seconds, typically much less)
- **Single DB transaction** (no partial commits)
- **Stateless runtime** (no in-memory caching between requests)
- **Notification serialisation** (all notifications are JSON-serialised and stored in gamelog)

### 16.2 Domain Architecture Performance Concerns

| Concern | Impact | Mitigation |
|---|---|---|
| **Manager granularity** | Too many managers = many DB calls | Batch reads, eager loading |
| **Model construction** | Constructing models for every row is slower than raw arrays | Use models for complex logic, raw arrays for simple reads |
| **Static managers** | `Game::get()` in loops is slow | Cache Game reference, batch operations |
| **Value objects** | Creating objects for every resource operation adds overhead | Pool or reuse value objects for identical configurations |
| **Cross-manager calls** | Each call adds indirection | Orchestrate in action method, not via multiple manager calls |
| **Engine serialisation** | Large Engine trees serialised to globals cost DB writes | Keep tree compact, prune resolved nodes |

### 16.3 The Eager Loading Pattern

```php
class Cards extends Manager
{
    private ?array $allCardsCache = null;

    public function getAll(): array
    {
        if ($this->allCardsCache === null) {
            $rows = $this->getCollectionFromDB("SELECT * FROM card");
            $this->allCardsCache = array_map(fn($r) => $this->rowToCard($r), $rows);
        }
        return $this->allCardsCache;
    }
}
```

Cache lives only within the current request. This is safe and is used by `CachedDB_Manager` in ArkNova.

### 16.4 The Mediator Performance Trade-off

The Game mediator pattern (a method on Game.php that calls multiple managers) adds one extra method call per operation. This cost is negligible. The alternative — action methods making all calls directly — is faster by an immeasurable margin. **Do not optimise for method call overhead.** Optimise for:
- Number of DB queries (batch into fewer queries)
- Notification payload size (send deltas, not full state)
- Engine tree depth (prune resolved nodes)

### 16.5 When Architecture Hurts Performance

**Too many value objects in hot loops:**

```php
// SLOW — creates objects every iteration
foreach ($this->getAllCards() as $card) {
    $cost = new Resources($card->getCost());
    if ($player->getResources()->hasAtLeast($cost)) { ... }
}

// FAST — compute once, use raw values
foreach ($this->getAllCards() as $card) {
    if ($player->getCoins() >= $card->getCoinCost()) { ... }
}
```

**Too much model overhead for simple reads:**

```php
// Use raw arrays for simple read-only queries
// Use Models only when computed properties or validation logic is needed
```

---

## 17. Common Anti-Patterns

### 17.1 God Manager

**Symptom:** A single manager that handles cards, players, board, and scoring.

**Detection:** The manager class exceeds 500 lines, imports from every other subsystem, and has methods with unrelated prefixes (`getCard`, `getPlayer`, `getBoard`).

**Solution:** Split by aggregate root. Extract `Cards`, `Players`, `Board`, `Scoring` into separate managers. See §18 for refactoring patterns.

**Reference examples:**
- ArkNova's `Player.php` at 1242 lines is approaching this anti-pattern
- Earth's `Action.php` at 1458 lines is approaching this anti-pattern

### 17.2 Anemic Model

**Symptom:** Models are plain data containers with no behaviour. All logic lives in Managers or Game.php.

**Detection:** Model classes have only public properties, getters/setters, and `toArray()` — no computed properties, no validation methods, no business behaviour.

```php
// ANTI-PATTERN — anemic model
class Card
{
    public function __construct(
        public int $id,
        public string $type,
        public string $typeArg,
        public string $location,
    ) {}
}

// CORRECT — model with behaviour
class Card
{
    // ... constructor with readonly properties ...

    public function getCost(): Resources { ... }
    public function isPlayable(): bool { ... }
    public function getDisplayName(): string { ... }
}
```

**Solution:** Move computed properties and single-entity validation logic from the Manager into the Model. Keep multi-entity logic (operations involving multiple cards) in the Manager.

### 17.3 Circular Managers

**Symptom:** Manager A calls Manager B which calls Manager A (through the Game mediator).

**Detection:** Trace a single action's call chain. If you see `Cards::playCard` → `Game::onCardPlayed` → `Players::spendResources` → `Game::onResourceSpent` → `Cards::checkCardTriggers`, you have a cycle.

**Solution:**
1. Move orchestration to the action method (breaking the cycle)
2. Or use the Engine pattern where leaf nodes are independent

### 17.4 Shared Mutable Globals

**Symptom:** Multiple managers read and write the same global key, causing race conditions within a request.

**Detection:** Search for `Globals::set` and trace the callers. If two managers set the same key, it's shared mutable state.

**Solution:** Each global key should be owned by exactly one manager. If two managers need the same data, one should be the owner and the other should read through the owning manager's API.

### 17.5 Utility Dumping Ground

**Symptom:** A single `Utils.php` file containing unrelated functions — string formatting, array operations, resource conversion, notification helpers.

**Detection:** `Utils.php` exceeds 300 lines and has methods on unrelated topics.

**Solution:** Split into focused utility classes:
- `StringUtils.php` — string formatting
- `ArrayUtils.php` — array operations  
- `ResourceUtils.php` — resource-to-string conversion, icons
- Prefer static methods on the relevant Model or Value Object over utility functions

### 17.6 Manager Inheritance Abuse

**Symptom:** A base Manager class with common DB methods, then concrete managers inheriting from it. The base class grows to accommodate every subclass's needs.

```php
// ANTI-PATTERN — inheritance abuse
abstract class BaseManager
{
    protected function dbGetAll(string $table): array { ... }
    protected function dbInsert(string $table, array $data): int { ... }
    // ... 20 more methods covering every possible DB operation
}

class Cards extends BaseManager { ... }
class Players extends BaseManager { ... }
```

**Solution:** Prefer composition over inheritance. Use a `DB` helper class that all managers compose, rather than a base class. Or use a trait for shared DB access methods.

### 17.7 Static Singleton Abuse

**Symptom:** Everything is accessed via static `::get()` or `::instance()` methods. Managers, models, helpers — all statics.

**Detection:** Instance methods are rare. Most code looks like `Manager::doSomething()` or `Model::computeSomething()`.

**Solution:** Use instance managers injected through Game.php's constructor. Limit static access to:
- Notifications class (called from everywhere)
- Pure utility functions
- Well-documented exceptions (Engine pattern's action classes)

### 17.8 State Machine as Manager

**Symptom:** State classes contain domain logic that should be in managers.

```php
// ANTI-PATTERN — domain logic in state class
public function actPlayCard(int $cardId, int $activePlayerId, array $args): string
{
    $this->game->DbQuery("UPDATE card SET ...");
    $this->game->DbQuery("UPDATE player SET ...");
    // ...
}
```

**Solution:** See [state-machine-architecture.md §5.2](./state-machine-architecture.md#52-what-a-state-must-not-do) and [action-architecture.md §5](./action-architecture.md#5-thin-action-principle).

---

## 18. Refactoring Patterns

### 18.1 Splitting an Oversized Manager

**Problem:** A single `Manager.php` handles cards, players, and board — exceeding 800 lines.

**Steps:**

1. **Identify aggregate roots.** Group methods by the table they access. `getHand()` → Cards, `addScore()` → Players, `placeTile()` → Board.

2. **Extract methods to new classes.** Create `Players.php`, `Board.php`, and shrink the original manager down to Cards:

```php
// BEFORE: GodManager.php has 50 methods across 3 domains
// AFTER: Cards.php has 18 methods (all card-related), Players.php has 15 methods, Board.php has 17 methods
```

3. **Move table ownership.** Update `dbmodel.sql` comments to document which manager owns which table. Move any SQL that crosses the new boundary.

4. **Update Game.php constructor.** Add new manager instances:

```php
// BEFORE
$this->godManager = new GodManager($this);

// AFTER
$this->players = new Players($this);
$this->cards = new Cards($this);
$this->board = new Board($this);
```

5. **Update callers.** Replace `$this->game->godManager->getHand(...)` with `$this->game->cards->getHand(...)`. Same for every call site.

6. **Test.** Run existing tests. They should pass with only import changes.

### 18.2 Introducing a New Domain

**Problem:** A new game mechanic (e.g., "Events" or "Missions") does not fit existing managers.

**Steps:**

1. **Define the aggregate root.** What is the central entity? (Event, Mission) What data does it own?

2. **Create the DB table.** Add to `dbmodel.sql`:

```sql
CREATE TABLE IF NOT EXISTS `event` (
    `event_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `event_type` varchar(32) NOT NULL,
    `event_state` varchar(16) NOT NULL DEFAULT 'pending',
    `event_player_id` int(10) unsigned DEFAULT NULL,
    PRIMARY KEY (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
```

3. **Create the Manager.** Implement `get()`, `create()`, mutation methods, and validation.

4. **Create the Model.** Wrap rows in a typed entity with computed properties.

5. **Register in Game.php.** Add `$this->events = new Events($this);` to the constructor.

6. **Add notification methods.** Add `EventsNotifications` to `Core/Notifications/` or extend the existing notifications class.

7. **Update cross-references.** Update Game mediator methods if existing managers need to interact with the new domain.

### 18.3 Migrating from Raw Arrays to Models

**Problem:** Codebase passes raw database rows everywhere, using `$card['card_type']` instead of `$card->getType()`.

**Steps:**

1. **Create the Model class.** Define readonly properties and computed methods.

2. **Add a factory method to the Manager.** `rowToCard(array $row): Card`.

3. **Update read methods.** Change `get()` and `getAll()` to return Model instances.

4. **Update callers incrementally.** Each file that accesses cards by array index gets updated to use Model methods. Do this one file at a time.

5. **No changes to write methods needed.** Managers still accept primitive parameters for write operations.

### 18.4 Migrating from Inline Notifications to Centralized Class

**Problem:** `notifyAllPlayers` calls are scattered throughout managers and actions.

**Steps:**

1. **Create the Notifications class.** Start with the template in §20.5.

2. **Identify notification types.** Search for `notifyAllPlayers` and `notifyPlayer` calls. Each unique first argument is a notification type.

3. **Create one method per type.** Group related types in the same class or split by domain.

4. **Replace inline calls.** Update each call site to use the new method.

5. **Verify.** Run the game. Notifications should be identical.

### 18.5 Migrating from Static to Instance Managers

**Problem:** Managers use `Game::get()` for static access, making testing difficult.

**Steps:**

1. **Add an instance reference** to Game.php (if not present).

2. **Change static methods to instance.** Keep the static method as a thin wrapper that delegates to the instance:

```php
// BEFORE
class Cards
{
    public static function get(int $cardId): Card
    {
        $row = Game::get()->getObjectFromDB(...);
        return new Card(...);
    }
}

// AFTER (transitional)
class Cards
{
    // Instance method (new callers use this)
    public function get(int $cardId): Card { ... }

    // Static wrapper (old callers still work)
    public static function sget(int $cardId): Card
    {
        return Game::get()->cards->get($cardId);
    }
}
```

3. **Migrate callers incrementally.** Replace `Cards::get(...)` with `$this->game->cards->get(...)`.

4. **Remove static wrappers** once all callers are migrated.

---

## 19. Canonical Architecture

### 19.1 Dependency Diagram

```
┌─────────────────────────────────────────────────────────────┐
│  FRAMEWORK LAYER                                            │
│  Table, gamestate, Deck, bga->globals, bga->notify          │
│  (BGA Platform — no game-specific code)                    │
├─────────────────────────────────────────────────────────────┤
│                       ▲ calls                               │
│                       │                                     │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  GAME LAYER (Game.php)                               │   │
│  │  setupNewGame, getAllDatas, zombie, giveExtraTime    │   │
│  │  Thin coordinator — delegates to everything below    │   │
│  └──────────────────────────────────────────────────────┘   │
│                       │                                     │
│         ┌─────────────┼─────────────┬──────────────┐        │
│         ▼             ▼             ▼              ▼        │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────────┐   │
│  │  STATE   │ │ MANAGERS │ │  CORE    │ │  HELPERS     │   │
│  │ CLASSES  │ │ (domain) │ │ Globals  │ │ DB, Collection│   │
│  │ (flow)   │ │ Cards    │ │ Engine   │ │ Utils         │   │
│  │          │ │ Players  │ │ Notif.   │ └──────────────┘   │
│  │ act*()   │ │ Board    │ │ Stats    │                    │
│  │ getArgs()│ │ Scoring  │ │ Log      │                    │
│  └──────────┘ └──────────┘ └──────────┘                    │
│       │             │                                       │
│       ▼             ▼                                       │
│  ┌──────────┐ ┌──────────┐                                  │
│  │ ACTIONS  │ │  MODELS  │                                  │
│  │ (live in │ │ Entities │                                  │
│  │  states) │ │ Value Obj│                                  │
│  └──────────┘ └──────────┘                                  │
│       │             │                                       │
│       ▼             ▼                                       │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  DATABASE LAYER                                      │   │
│  │  MySQL tables: player, card, board, global_variables │   │
│  └──────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

### 19.2 Package Diagram

```
modules/php/
│
├── Game.php                    [1 file]
│
├── States/                     [N files, one per state]
│   ├── PlayerTurn.php
│   ├── ResolveChoice.php
│   ├── ScoreRound.php
│   ├── CheckEndGame.php
│   ├── GameEnd.php
│   └── StateIds.php            [named constants]
│
├── Managers/                   [N files, one per aggregate root]
│   ├── Players.php
│   ├── Cards.php
│   ├── Board.php
│   ├── Scoring.php
│   └── Actions.php             [atomic action registry]
│
├── Models/                     [N files, one per entity type]
│   ├── Player.php
│   ├── Card.php
│   ├── Resources.php           [value object]
│   ├── Cost.php                [value object]
│   └── Effect.php              [value object]
│
├── Core/                       [infrastructure, ~5 files]
│   ├── Globals.php
│   ├── Engine.php              [optional — for complex games]
│   ├── Notifications.php
│   ├── Stats.php
│   ├── Log.php                 [undo logging]
│   └── Preferences.php
│
└── Helpers/                    [~3 files]
    ├── DB.php
    ├── Collection.php
    └── Utils.php
```

### 19.3 Communication Diagram

```
┌──────┐     performAction()     ┌───────────┐
│Client│ ──────────────────────► │State Class│
│  JS  │                         │ act*()    │
└──────┘                         └─────┬─────┘
      ▲                                │
      │                                │ delegates to
      │                                ▼
      │                        ┌───────────────┐
      │                        │   Manager(s)  │
      │                        │  Cards, etc.  │
      │                        └───────┬───────┘
      │                                │
      │                                │ reads/writes
      │                                ▼
      │                        ┌───────────────┐
      │                        │   Database    │
      │                        └───────┬───────┘
      │                                │
      │                                │ sends
      │                                ▼
      │                        ┌───────────────┐
      │◄── notifications ─────│ Notifications │
      │                        │   class      │
      │                        └───────────────┘
      │
      │◄── state transition ──│  Framework    │
```

### 19.4 Domain Ownership Diagram

```
┌────────────────────────────────────────────────────────────────┐
│                    DOMAIN OWNERSHIP MAP                         │
│                                                                │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐         │
│  │  PLAYER      │  │  CARD        │  │  BOARD       │         │
│  │  Domain      │  │  Domain      │  │  Domain      │         │
│  │              │  │              │  │              │         │
│  │  manager:    │  │  manager:    │  │  manager:    │         │
│  │  Players     │  │  Cards       │  │  Board       │         │
│  │              │  │              │  │              │         │
│  │  tables:     │  │  tables:     │  │  tables:     │         │
│  │  player      │  │  card        │  │  board_tile  │         │
│  │              │  │  card_effect │  │  board_token │         │
│  │  model:      │  │              │  │              │         │
│  │  Player      │  │  model:      │  │  model:      │         │
│  │              │  │  Card        │  │  BoardTile   │         │
│  │  owns:       │  │              │  │              │         │
│  │  - resources │  │  owns:       │  │  owns:       │         │
│  │  - score     │  │  - deck      │  │  - tile grid │         │
│  │  - turn      │  │  - hand      │  │  - token     │         │
│  │    order     │  │  - discard   │  │    placement │         │
│  │  - elim.     │  │  - location  │  │  - position  │         │
│  └──────────────┘  └──────────────┘  │    validation│         │
│                                       └──────────────┘         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐         │
│  │  SCORING     │  │  ENGINE      │  │  GAME        │         │
│  │  Domain      │  │  Domain      │  │  State       │         │
│  │              │  │              │  │              │         │
│  │  manager:    │  │  manager:    │  │  owned by:   │         │
│  │  Scoring     │  │  Engine      │  │  Globals     │         │
│  │              │  │              │  │              │         │
│  │  tables:     │  │  tables:     │  │  keys:       │         │
│  │  player      │  │  global_     │  │  - phase     │         │
│  │  (score      │  │  variables   │  │  - round     │         │
│  │   column)    │  │  (JSON blob) │  │  - turn      │         │
│  │              │  │              │  │  - flags     │         │
│  │  model:      │  │  model:      │  │              │         │
│  │  Score       │  │  Tree        │  │  model: none │         │
│  │              │  │  (runtime)   │  │              │         │
│  │  computes:   │  │              │  │              │         │
│  │  - round     │  │  resolves:   │  │              │         │
│  │    score     │  │  - SeqNode   │  │              │         │
│  │  - final     │  │  - OrNode    │  │              │         │
│  │    score     │  │  - XorNode   │  │              │         │
│  │  - tiebreak  │  │  - Parallel  │  │              │         │
│  └──────────────┘  └──────────────┘  └──────────────┘         │
└────────────────────────────────────────────────────────────────┘
```

---

## 20. Templates

### 20.1 Canonical Manager

```php
<?php

declare(strict_types=1);

namespace MyGame\Managers;

use MyGame\Models\Card;
use MyGame\Core\Globals;
use MyGame\Helpers\DB;

class Cards extends Manager
{
    public function __construct(
        private Game $game,
    ) {}

    // --- READ METHODS ---

    public function get(int $cardId): Card
    {
        $row = $this->game->getObjectFromDB(
            "SELECT * FROM card WHERE card_id = $cardId"
        );
        if (!$row) {
            throw new \BgaSystemException("Card $cardId not found");
        }
        return $this->rowToCard($row);
    }

    public function getHand(int $playerId): array
    {
        $rows = $this->game->getCollectionFromDB(
            "SELECT * FROM card WHERE card_location = 'hand' AND card_location_arg = $playerId"
        );
        return array_map(fn($r) => $this->rowToCard($r), $rows);
    }

    public function getAll(): array
    {
        $rows = $this->game->getCollectionFromDB("SELECT * FROM card");
        return array_map(fn($r) => $this->rowToCard($r), $rows);
    }

    // --- VALIDATION METHODS ---

    public function validateCanPlay(int $cardId, int $playerId): void
    {
        $card = $this->get($cardId);
        if ($card->getLocation() !== 'hand') {
            throw new \BgaUserException(clienttranslate('This card is not in your hand'));
        }
        if ((int)$card->getLocationArg() !== $playerId) {
            throw new \BgaUserException(clienttranslate('You do not own this card'));
        }
    }

    // --- MUTATION METHODS ---

    public function playCard(int $cardId, int $playerId): Card
    {
        $this->validateCanPlay($cardId, $playerId);
        $this->game->DbQuery(
            "UPDATE card SET card_location = 'play', card_location_arg = $playerId
             WHERE card_id = $cardId"
        );
        return $this->get($cardId);
    }

    public function drawCard(int $playerId): Card
    {
        $cardId = $this->game->getUniqueValueFromDB(
            "SELECT card_id FROM card
             WHERE card_location = 'deck'
             ORDER BY card_location_arg ASC
             LIMIT 1"
        );
        if (!$cardId) {
            throw new \BgaSystemException('Deck is empty');
        }
        $this->game->DbQuery(
            "UPDATE card SET card_location = 'hand', card_location_arg = $playerId
             WHERE card_id = $cardId"
        );
        return $this->get((int)$cardId);
    }

    // --- INTERNAL ---

    private function rowToCard(array $row): Card
    {
        return new Card(
            id: (int)$row['card_id'],
            type: $row['card_type'],
            typeArg: $row['card_type_arg'],
            location: $row['card_location'],
            locationArg: (int)$row['card_location_arg'],
            extraDatas: json_decode($row['extra_datas'] ?? '{}', true),
        );
    }
}
```

### 20.2 Canonical Model (Entity)

```php
<?php

declare(strict_types=1);

namespace MyGame\Models;

class Card
{
    public function __construct(
        private readonly int $id,
        private readonly string $type,
        private readonly string $typeArg,
        private string $location,
        private int $locationArg,
        private array $extraDatas = [],
    ) {}

    // --- Identity ---

    public function getId(): int
    {
        return $this->id;
    }

    // --- Computed Properties ---

    public function getCost(): Resources
    {
        // Lookup cost from game material based on type/typeArg
        return match ($this->type) {
            'farm' => Resources::fromArray(['coin' => 2, 'wood' => 1]),
            'pasture' => Resources::fromArray(['wood' => 2]),
            default => Resources::fromArray([]),
        };
    }

    public function isPlayable(): bool
    {
        return $this->location === 'hand';
    }

    // --- UI Formatting ---

    public function toUi(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'typeArg' => $this->typeArg,
            'location' => $this->location,
            'locationArg' => $this->locationArg,
        ];
    }

    // --- Location ---

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getLocationArg(): int
    {
        return $this->locationArg;
    }

    // --- Extra Data ---

    public function getExtra(string $key, mixed $default = null): mixed
    {
        return $this->extraDatas[$key] ?? $default;
    }

    public function getExtraDatas(): array
    {
        return $this->extraDatas;
    }
}
```

### 20.3 Canonical Value Object

```php
<?php

declare(strict_types=1);

namespace MyGame\Models;

final class Resources
{
    public function __construct(
        private readonly array $resources,
    )
    {
        // Validate no negative values
        foreach ($resources as $type => $amount) {
            if ($amount < 0) {
                throw new \InvalidArgumentException("Resource $type cannot be negative");
            }
        }
    }

    // --- Arithmetic ---

    public function add(Resources $other): Resources
    {
        $result = $this->resources;
        foreach ($other->resources as $type => $amount) {
            $result[$type] = ($result[$type] ?? 0) + $amount;
        }
        return new Resources($result);
    }

    public function subtract(Resources $other): Resources
    {
        $result = $this->resources;
        foreach ($other->resources as $type => $amount) {
            $result[$type] = ($result[$type] ?? 0) - $amount;
        }
        return new Resources($result);
    }

    // --- Queries ---

    public function hasAtLeast(Resources $other): bool
    {
        foreach ($other->resources as $type => $amount) {
            if (($this->resources[$type] ?? 0) < $amount) {
                return false;
            }
        }
        return true;
    }

    public function get(string $type): int
    {
        return $this->resources[$type] ?? 0;
    }

    public function isEmpty(): bool
    {
        return empty(array_filter($this->resources, fn($v) => $v > 0));
    }

    // --- Conversion ---

    public function toArray(): array
    {
        return $this->resources;
    }

    public function toStr(): string
    {
        $parts = [];
        foreach ($this->resources as $type => $amount) {
            if ($amount > 0) {
                $parts[] = "$amount $type";
            }
        }
        return implode(', ', $parts);
    }

    // --- Factory ---

    public static function fromArray(array $data): Resources
    {
        return new Resources($data);
    }

    public static function empty(): Resources
    {
        return new Resources([]);
    }
}
```

### 20.4 Canonical Domain Service

A Domain Service contains business logic that does not naturally fit in a single Manager or Model. Use sparingly — most logic belongs in Managers.

```php
<?php

declare(strict_types=1);

namespace MyGame\Services;

use MyGame\Managers\Players;
use MyGame\Managers\Cards;
use MyGame\Managers\Board;
use MyGame\Models\Resources;

class PurchaseService
{
    public function __construct(
        private Players $players,
        private Cards $cards,
        private Board $board,
    ) {}

    public function purchaseCard(int $playerId, int $cardId): void
    {
        $card = $this->cards->get($cardId);
        $cost = $card->getCost();

        // Apply discounts from board state
        $cost = $this->applyDiscounts($playerId, $cardId, $cost);

        // Validate
        $player = $this->players->get($playerId);
        if (!$player->getResources()->hasAtLeast($cost)) {
            throw new \BgaUserException(clienttranslate('Not enough resources'));
        }

        // Execute
        $this->players->spendResources($playerId, $cost);
        $this->cards->playCard($cardId, $playerId);
        $this->board->applyCardEffect($cardId, $playerId);
    }

    private function applyDiscounts(int $playerId, int $cardId, Resources $cost): Resources
    {
        if ($this->board->hasDiscountForType($cardId)) {
            $discount = $this->board->getDiscount($cardId);
            $cost = $cost->subtract($discount);
        }
        return $cost;
    }
}
```

**When to use a Domain Service:**
- The logic spans 3+ managers
- The logic is a specific business operation (Purchase, Trade, Upgrade)
- The logic would otherwise live in Game.php as a fat mediator method

**When NOT to use a Domain Service:**
- The logic fits in a single Manager (keep it there)
- The logic is a Manager's private implementation detail (don't extract)

### 20.5 Canonical Notification Class

```php
<?php

declare(strict_types=1);

namespace MyGame\Core;

use MyGame\Models\Resources;

class Notifications
{
    private static function notifyAll(string $name, string $msg, array $data): void
    {
        self::updateArgs($data);
        Game::get()->notifyAllPlayers($name, $msg, $data);
    }

    private static function notify(string $name, string $msg, array $data, int $playerId): void
    {
        self::updateArgs($data);
        Game::get()->notifyPlayer($playerId, $name, $msg, $data);
    }

    private static function updateArgs(array &$data): void
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
            $data['resources_desc'] = $data['resources']->toStr();
        }
    }

    // --- Notification Methods ---

    public static function cardPlayed($player, int $cardId, string $cardName): void
    {
        self::notifyAll('cardPlayed', clienttranslate('${player_name} plays ${card_name}'), [
            'player' => $player,
            'card_name' => $cardName,
            'card_id' => $cardId,
            'i18n' => ['card_name'],
        ]);
    }

    public static function gainResources($player, Resources $resources, ?string $source = null): void
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

    public static function refreshUI(array $datas): void
    {
        $fDatas = [
            'players' => $datas['players'],
            'cards' => $datas['cards'],
        ];
        foreach ($fDatas['players'] as &$player) {
            $player['hand'] = [];
        }
        self::notifyAll('refreshUI', '', ['datas' => $fDatas]);
    }

    public static function refreshHand($player, array $hand): void
    {
        self::notify('refreshHand', '', [
            'player' => $player,
            'hand' => $hand,
        ], $player->getId());
    }

    public static function clearTurn($player): void
    {
        self::notifyAll('clearTurn', clienttranslate('${player_name} restarts their turn'), [
            'player' => $player,
        ]);
    }
}
```

---

## 21. Checklists

### 21.1 Production Readiness Checklist

- [ ] Every DB table has a clear owning Manager (documented in comments)
- [ ] Every Manager owns exactly one aggregate root (no God Managers)
- [ ] Models have behaviour (computed properties, validation methods) — no anemic models
- [ ] Value objects are immutable with readonly properties
- [ ] Managers never call other managers directly (orchestration is in actions)
- [ ] Action methods are thin (< 15 lines, no SQL, no inline notifyAllPlayers)
- [ ] All notifications go through a centralized Notifications class
- [ ] Globals are typed (wrapped in a Globals class), not raw set/get calls
- [ ] Entity data is on entity tables, not in globals
- [ ] State classes contain no domain logic (delegated to Managers)
- [ ] Cross-manager communication uses the Game mediator or action orchestration, not direct calls
- [ ] Circular dependencies are absent (verified by tracing call chain)
- [ ] Engine tree (if used) is serialised and restored correctly between requests
- [ ] Engine nodes contain no domain logic (delegated to Managers/Models)
- [ ] Notification class has `updateArgs()` or equivalent for auto-resolve
- [ ] `i18n` arrays are complete for all translatable notification args
- [ ] Layers respect the dependency rules (§14)

### 21.2 Architecture Review Checklist

- [ ] Can each Manager's responsibility be described in one sentence without "and"?
- [ ] Does every table belong to exactly one Manager?
- [ ] If two tables belong to the same Manager, are they part of the same aggregate?
- [ ] If two tables belong to different Managers, is their interaction orchestrated, not direct?
- [ ] Are there fewer than 10 Managers? (More than 10 suggests over-fragmentation)
- [ ] Do Models exist for entities with non-trivial computed properties?
- [ ] Are Value Objects used for resource bundles, costs, positions, and other compound concepts?
- [ ] Is the Engine (if used) the only component that determines flow between multi-step actions?
- [ ] Are globals used only for state that has no entity table?
- [ ] Is the folder structure appropriate for the game's size (§12)?

### 21.3 Code Review Questions

**Manager questions:**
- Does this Manager write to tables it does not own?
- Does this Manager call another Manager directly?
- Does this Manager contain raw `notifyAllPlayers` calls?
- Are read methods returning Models (not raw arrays) when behaviour is needed?
- Are mutation methods idempotent-safe? (Running twice produces correct state)

**Model questions:**
- Does this Model access the database?
- Does this Model call the framework?
- Does this Model have computed properties that duplicate Manager logic?

**State/Action questions:**
- Is this action method under 15 lines?
- Does it contain domain logic that belongs in a Manager?
- Does it call `notifyAllPlayers` directly?
- Is validation complete before any mutation?

**Cross-domain questions:**
- Is cross-domain logic orchestrated by the action, not by Manager-to-Manager calls?
- Are Game mediator methods necessary, or could the action orchestrate directly?
- Is the Engine (if used) the appropriate level of abstraction, or would manual states suffice?

**Globals questions:**
- Does this global belong on an entity table instead?
- Is this global a cache of computed values (risk of staleness)?
- Is this global part of a growing collection of unrelated keys (global soup)?

---

## References

- [game-flow-architecture.md](./game-flow-architecture.md) — execution pipeline, transaction model, thin coordinator principle
- [state-machine-architecture.md](./state-machine-architecture.md) — state lifecycle, Engine pattern, state types
- [action-architecture.md](./action-architecture.md) — action lifecycle, validation layers, manager delegation
- [notification-patterns.md](./notification-patterns.md) — centralized notification class, payload design, i18n
- [reference-project-analysis.md](../foundation/reference-project-analysis.md) — project-specific architecture ratings and scaling patterns
- [bga-developer-handbook.md](../foundation/bga-developer-handbook.md) — framework API reference
