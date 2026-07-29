# BGA Project Architecture — Engineering Standard

**Document purpose:** Define the canonical architecture for organizing large-scale Board Game Arena projects. Describe how an experienced BGA engineer structures an entire repository so it remains understandable, maintainable, and scalable for many years. This is the capstone standard — it synthesizes all other standards into a unified project structure.

**Applicability:** All new BGA game implementations. Existing projects should use this document as a reference when planning major refactors, adding expansions, or restructuring the codebase.

**Cross-references:**
- [domain-architecture.md](./domain-architecture.md) — layered architecture, manager pattern, dependency rules, folder structure
- [persistence-architecture.md](./persistence-architecture.md) — database architecture, table ownership, migration strategy
- [client-ui-architecture.md](./client-ui-architecture.md) — manager pattern, widget architecture, scaling tiers
- [client-synchronization-architecture.md](./client-synchronization-architecture.md) — notification flow, reconnect, getAllDatas
- [animation-architecture.md](./animation-architecture.md) — animation ownership, BgaAnimations integration
- [action-architecture.md](./action-architecture.md) — thin action principle, validation layers, delegation
- [state-machine-architecture.md](./state-machine-architecture.md) — state taxonomy, state ids, state file organization
- [game-flow-architecture.md](./game-flow-architecture.md) — execution pipeline, thin coordinator, module layout
- [testing-debugging-architecture.md](./testing-debugging-architecture.md) — testing pyramid, CI validation, debugging infrastructure
- [notification-patterns.md](./notification-patterns.md) — centralized notification class, payload design
- [reference-project-analysis.md](../foundation/reference-project-analysis.md) — project lineage, subsystem ratings, scaling patterns
- [bga-developer-handbook.md](../foundation/bga-developer-handbook.md) — framework project structure, required files

---

## Table of Contents

- [1. Architecture Philosophy](#1-architecture-philosophy)
- [2. Repository Structure](#2-repository-structure)
- [3. Server Organization](#3-server-organization)
- [4. Client Organization](#4-client-organization)
- [5. Dependency Rules](#5-dependency-rules)
- [6. Module Boundaries](#6-module-boundaries)
- [7. Naming Conventions](#7-naming-conventions)
- [8. Expansion Architecture](#8-expansion-architecture)
- [9. Documentation Organization](#9-documentation-organization)
- [10. AI-Friendly Architecture](#10-ai-friendly-architecture)
- [11. Build and Tooling](#11-build-and-tooling)
- [12. Migration Strategy](#12-migration-strategy)
- [13. Anti-Patterns](#13-anti-patterns)
- [14. Templates](#14-templates)
- [15. Checklists](#15-checklists)

---

## 1. Architecture Philosophy

### 1.1 Six Principles

**Principle 1 — Separation of Concerns.** Every file, class, and directory has exactly one responsibility. A Manager does not contain notification logic. A Model does not access the database. Game.js does not render DOM elements. When a file's purpose cannot be described in a single sentence, it needs to be split.

**Principle 2 — Layered Architecture.** Every component belongs to a layer. Layers have strict dependency direction: Framework ← Game ← States ← Actions ← Managers ← Models ← Database. A component may only depend on components in its own layer or layers below it.

**Principle 3 — Stable Dependencies.** Dependencies point in the direction of stability. Lower layers (Models, Value Objects) are more stable than higher layers (Game, States). Stable layers should not depend on unstable layers.

```
Most stable:      Value Objects → Models → Helpers
                  ↓
                  Managers → Core (Globals, Engine)
                  ↓
                  States → Actions
                  ↓
Least stable:     Game.php (coordinator, framework API surface)
```

**Principle 4 — Cohesion.** Related code lives together. A Manager and its Model are in the same domain package. States for a game phase are grouped in a subdirectory. Client managers that render cards are in the same domain folder as the card model.

**Principle 5 — Modularity.** Every module has a defined public API and hidden internal implementation. A Manager exposes `get()`, `create()`, and `playCard()` methods. Its internal SQL queries and validation helpers are private. Another module should not depend on an internal helper of a different module.

**Principle 6 — Scalability.** The architecture must accommodate growth from 20 files to 500+ files without structural changes. The folder layout from day one should anticipate the eventual scale. A small game's structure should be a subset of a large game's structure — not a different structure that requires rewriting.

### 1.2 Architecture Lineage

The canonical architecture derives from two lineages:

**Pecatte/Toper lineage (Agricola, Ark Nova)** — built around Engine pattern, Centralized Notifications, Thin Coordinator principle, and layered Managers/Models/Helpers/Core separation.

**Earth lineage** — built around Command Queue pattern, Domain-oriented packages, Client Manager pattern, and annotation-driven persistence.

This standard synthesizes both lineages, preferring the Pecatte/Toper layered structure for most projects while adopting Earth's domain-oriented packaging for very large projects.

See [reference-project-analysis.md §6](./reference-project-analysis.md#6-key-takeaways--which-project-to-consult-for-what).

---

## 2. Repository Structure

### 2.1 Complete Repository Tree

```
mygame/
├── .opencode/                    ← AI agent configuration
├── dbmodel.sql                   ← Database schema
├── gameinfos.jsonc               ← Game metadata (players, colors)
├── gameoptions.jsonc             ← Game configuration options
├── gamepreferences.jsonc         ← Player preference definitions
├── stats.jsonc                   ← Statistics definitions
├── material.inc.php              ← Game material definitions
├── version.php                   ← Version tracking
│
├── modules/
│   ├── php/
│   │   ├── Game.php              ← Thin coordinator
│   │   ├── States/               ← One class per state
│   │   ├── Managers/             ← Domain logic (one per aggregate)
│   │   ├── Models/               ← Entities and value objects
│   │   ├── Core/                 ← Framework-level infrastructure
│   │   ├── Services/             ← Domain services (cross-manager logic)
│   │   ├── Helpers/              ← Utilities and DB abstraction
│   │   └── Traits/               ← Shared behaviour (DebugTrait, etc.)
│   │
│   ├── js/
│   │   ├── Game.js               ← Thin client coordinator
│   │   ├── Managers/             ← Client managers (DOM ownership)
│   │   ├── States/               ← Client state handlers
│   │   ├── Widgets/              ← Reusable UI components
│   │   ├── Core/                 ← Client infrastructure
│   │   └── styles/               ← CSS/SCSS files
│   │
│   └── css/                      ← Legacy Dojo CSS (if applicable)
│
├── img/                          ← Game images (cards, tokens, boards)
├── misc/                         ← Studio-only storage (1MB limit)
│
├── docs/
│   ├── 00-documentation-architecture.md
│   ├── foundation/               ← Reference documents
│   ├── standards/                ← Engineering standards
│   ├── architecture/             ← System architecture documents
│   ├── patterns/                 ← Reusable patterns
│   ├── playbooks/                ← Step-by-step procedures
│   └── checklists/               ← Verifiable item lists
│
├── tests/                        ← PHPUnit tests
│   ├── Models/
│   ├── Managers/
│   ├── Actions/
│   ├── States/
│   ├── Notifications/
│   └── FullGame/
│
├── package.json                  ← JS dependencies, lint scripts
├── eslint.config.js              ← ESLint configuration
├── tsconfig.json                 ← TypeScript configuration (if used)
└── .github/
    └── workflows/
        └── ci.yml                ← CI pipeline
```

### 2.2 Repository Structure Diagram

```
├── dbmodel.sql              ← Single source of truth for database schema
├── gameinfos.jsonc           ← Framework metadata (required)
├── gameoptions.jsonc         ← Game options (required)
├── material.inc.php          ← Card/token definitions
├── version.php               ← Version tracking for upgrades
│
├── modules/                  ← ALL game code
│   ├── php/                  ← Server-side code
│   ├── js/                   ← Client-side code
│   └── css/                  ← Legacy CSS (avoid, prefer modules/js/styles/)
│
├── img/                      ← Image assets only
├── misc/                     ← Studio scratch space (1MB limit)
│
├── tests/                    ← Mirrors modules/php/ structure
│
├── docs/                     ← Comprehensive documentation
│
├── package.json              ← Node tooling (linting, formatting)
├── .github/workflows/        ← CI configuration
```

### 2.3 What Goes Where

| Path | Purpose | Owned By | Review Cadence |
|---|---|---|---|
| `dbmodel.sql` | All CREATE TABLE statements | Backend | Every schema change |
| `modules/php/Game.php` | Framework-mandated methods, wiring | Backend | Every PR |
| `modules/php/States/` | State class files | Backend | Every new state |
| `modules/php/Managers/` | Server domain logic | Backend | Every PR |
| `modules/php/Models/` | Entities and value objects | Backend | Every PR |
| `modules/php/Core/` | Globals, Notifications, Engine | Backend | Infrequent |
| `modules/php/Helpers/` | DB, Collection, Utils | Backend | Infrequent |
| `modules/js/Game.js` | Client coordinator | Frontend | Every PR |
| `modules/js/Managers/` | Client domain managers | Frontend | Every PR |
| `modules/js/styles/` | CSS/SCSS files | Frontend | Every PR |
| `tests/` | PHPUnit tests | All | Every PR |
| `docs/` | Engineering standards | All | Per standard |

---

## 3. Server Organization

### 3.1 Module Layout

```
modules/php/
│
├── Game.php                    [1 file, thin coordinator]
│
├── States/                     [N files, one per state]
│   ├── PlayerTurn.php
│   ├── ResolveChoice.php
│   ├── ScoreRound.php
│   ├── CheckEndGame.php
│   ├── GameEnd.php
│   └── StateIds.php            [Named state ID constants]
│
├── Managers/                   [N files, one per aggregate root]
│   ├── Players.php
│   ├── Cards.php
│   ├── Board.php
│   ├── Scoring.php
│   └── Actions.php             [Atomic action registry, Engine pattern]
│
├── Models/                     [N files, entities + value objects]
│   ├── Player.php
│   ├── Card.php
│   ├── Resources.php           [Value object]
│   ├── Cost.php                [Value object]
│   └── Effect.php              [Value object]
│
├── Core/                       [5-8 files, infrastructure]
│   ├── Globals.php
│   ├── Engine.php              [Optional — for Engine pattern]
│   ├── Engine/                 [Split if Engine > 500 lines]
│   │   ├── Nodes/
│   │   └── Actions/
│   ├── Notifications.php
│   ├── Notifications/          [Split if > 500 lines]
│   ├── Stats.php
│   ├── Log.php                 [Undo logging]
│   └── Preferences.php
│
├── Services/                   [0-5 files, cross-manager logic]
│   └── PurchaseService.php
│
├── Helpers/                    [3-5 files, utilities]
│   ├── DB.php
│   ├── Collection.php
│   └── Utils.php
│
└── Traits/                     [Shared behaviour mixins]
    └── DebugTrait.php
```

### 3.2 Component Responsibilities

| Directory | Responsibility | Detailed In |
|---|---|---|
| `Game.php` | Framework API surface, thin coordinator, wiring | [game-flow-architecture.md §3.3](./game-flow-architecture.md#33-the-thin-coordinator-principle) |
| `States/` | State machine lifecycle, args, action methods | [state-machine-architecture.md §5](./state-machine-architecture.md#5-state-responsibilities) |
| `Managers/` | Domain logic, table ownership, invariants | [domain-architecture.md §4](./domain-architecture.md#4-manager-architecture) |
| `Models/` | Entities, value objects, computed properties | [domain-architecture.md §5](./domain-architecture.md#5-model-architecture) |
| `Core/` | Framework-agnostic infrastructure | [domain-architecture.md §3](./domain-architecture.md#3-responsibility-matrix) |
| `Services/` | Cross-manager domain services | [domain-architecture.md §20.4](./domain-architecture.md#204-canonical-domain-service) |
| `Helpers/` | Pure utilities, DB abstraction | [domain-architecture.md §3.2](./domain-architecture.md#32-component-definitions) |
| `Traits/` | Debug actions, shared behaviour | [testing-debugging-architecture.md §8.3](./testing-debugging-architecture.md#83-debug-flags) |
| `Engine/` | Decision tree flow engine (optional) | [domain-architecture.md §10](./domain-architecture.md#10-engine-interaction) |

### 3.3 File Size Guidelines

| Component | Max Lines | Action When Exceeded |
|---|---|---|
| `Game.php` | 400 | Extract mediator methods to Game traits |
| State class | 150 | Split states, extract to sub-states |
| Manager | 500 | Split by aggregate sub-domain |
| Model | 200 | Extract value objects, helper classes |
| `Notifications.php` | 500 | Split into `Notifications/` subdirectory |
| `Globals.php` | 100 | Split into domain-specific globals files |
| Helper | 300 | Split into focused helper classes |
| Trait | 200 | Extract to dedicated class |

---

## 4. Client Organization

### 4.1 Module Layout

```
modules/js/
│
├── Game.js                     [1 file, thin coordinator]
│
├── Managers/                   [N files, one per DOM section]
│   ├── CardMgr.js              [Card DOM, hand, tableau]
│   ├── BoardMgr.js             [Board DOM, tiles, tokens]
│   ├── PlayerPanelMgr.js       [Player info, scores, resources]
│   ├── ScoreMgr.js             [Score display and updates]
│   ├── DialogMgr.js            [All modal dialogs]
│   ├── SelectionMgr.js         [Selection state and highlighting]
│   └── AnimationMgr.js         [Shared animation service]
│
├── Domain/                     [Very large: domain packages]
│   ├── Cards/
│   │   ├── CardMgr.js
│   │   ├── HandMgr.js
│   │   └── TableauMgr.js
│   ├── Board/
│   │   ├── BoardMgr.js
│   │   └── TileMgr.js
│   └── Players/
│       ├── PlayerPanelMgr.js
│       └── ScoreMgr.js
│
├── States/                     [State-specific handlers]
│   ├── PlayerTurn.js
│   ├── ResolveChoice.js
│   └── DiscardPhase.js
│
├── Widgets/                    [Reusable UI components]
│   ├── Counter.js
│   ├── PlayerPanel.js
│   └── ResourceIcon.js
│
├── Core/                       [Client infrastructure]
│   ├── Notifications.js        [Notification handler registration]
│   └── Helpers.js              [Client utilities]
│
└── styles/                     [CSS/SCSS files]
    ├── cards.scss
    ├── board.scss
    ├── panels.scss
    └── dialogs.scss
```

### 4.2 Component Responsibilities

| Directory | Responsibility | Detailed In |
|---|---|---|
| `Game.js` | Thin coordinator, setup, notification wiring | [client-ui-architecture.md §4](./client-ui-architecture.md#4-gamejs) |
| `Managers/` | DOM ownership, state cache, rendering | [client-ui-architecture.md §5](./client-ui-architecture.md#5-manager-pattern) |
| `Domain/` | Domain-oriented packages (very large games) | [client-ui-architecture.md §14.4](./client-ui-architecture.md#144-very-large-game-80-states-400-files) |
| `States/` | State-specific UI behaviour | [client-ui-architecture.md §4.7](./client-ui-architecture.md#47-state-handlers) |
| `Widgets/` | Reusable UI elements | [client-ui-architecture.md §6](./client-ui-architecture.md#6-widget-architecture) |
| `Core/` | Client infrastructure | [client-ui-architecture.md §3](./client-ui-architecture.md#3-responsibilities) |
| `styles/` | Domain-specific CSS/SCSS | [client-ui-architecture.md §14](./client-ui-architecture.md#14-scaling) |

### 4.3 Client-Server Mirroring

For very large projects, the client domain structure mirrors the server domain structure:

```
modules/php/Domain/Cards/         ←→  modules/js/Domain/Cards/
modules/php/Domain/Board/         ←→  modules/js/Domain/Board/
modules/php/Domain/Players/       ←→  modules/js/Domain/Players/
```

This mirroring makes it obvious which client manager corresponds to which server manager. A developer looking at `CardManager.php` knows the corresponding client code is in `js/Domain/Cards/CardMgr.js`.

---

## 5. Dependency Rules

### 5.1 Global Dependency Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│  FRAMEWORK (Table, gamestate, bga API)                          │
│  Can be depended on by: everything                              │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  GAME (Game.php)                                          │   │
│  │  Can depend on: Framework, all Managers, Core, Helpers    │   │
│  │  Can be depended on by: States, Actions                   │   │
│  └──────────────────────────────────────────────────────────┘   │
│                          │                                      │
│                          ▼                                      │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  STATES (States/*.php)                                    │   │
│  │  Can depend on: Game, Notifications, Managers, Core       │   │
│  │  Can be depended on by: Nothing (callers are framework)   │   │
│  └──────────────────────────────────────────────────────────┘   │
│                          │                                      │
│                          ▼                                      │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  ACTIONS (methods on States)                              │   │
│  │  Can depend on: Game, Managers, Notifications             │   │
│  │  Can be depended on by: Nothing (inline in states)        │   │
│  └──────────────────────────────────────────────────────────┘   │
│                          │                                      │
│                          ▼                                      │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  MANAGERS                                                │   │
│  │  Can depend on: Game (mediator), Models, Helpers, Core   │   │
│  │  Can be depended on by: States, Actions, Services        │   │
│  └──────────────────────────────────────────────────────────┘   │
│                          │                                      │
│                          ▼                                      │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  MODELS / VALUE OBJECTS                                   │   │
│  │  Can depend on: Helpers (Utils), other value objects     │   │
│  │  Can be depended on by: everything above                  │   │
│  └──────────────────────────────────────────────────────────┘   │
│                          │                                      │
│                          ▼                                      │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │  HELPERS / UTILITIES                                      │   │
│  │  Can depend on: Nothing (pure) or Framework (DB helper)  │   │
│  │  Can be depended on by: everything above                  │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 5.2 Allowed Dependencies

| Component | May Depend On | Must NOT Depend On |
|---|---|---|
| **Game.php** | Framework, all managers, core, helpers | Nothing (top) |
| **State class** | Game, managers, notifications, core | Other states, models directly |
| **Action method** | Game, managers, notifications | Other actions, raw SQL |
| **Manager** | Game (mediator), models, core, helpers | Other managers, notifications |
| **Model** | Value objects, helpers | Game, managers, DB, notifications |
| **Value Object** | Other value objects, helpers | Game, managers, DB, anything |
| **Notification** | Framework notify API, helpers | Managers, models, game logic |
| **Globals** | Framework globals API | Managers, models |
| **Engine** | Globals, managers, action classes | State machine, notification routing |
| **Helper** | Framework (DB helper), nothing (utils) | Managers, models, notifications |
| **Service** | Managers, models, core | Game, states, actions |

### 5.3 Forbidden Dependencies

```
✗ Manager → Manager (direct)
✗ Manager → Notification (should go through action)
✗ Model → Framework
✗ Model → Manager
✗ Model → Database
✗ State → State (other state classes)
✗ Notification → Manager
✗ Notification → Model (directly — data passed through args)
✗ Engine → State machine
✗ Action → Raw SQL
✗ Action → notifyAllPlayers (direct — must use Notifications class)
```

### 5.4 Circular Dependency Prevention

Circular dependencies are detected by:

```php
// Symptom: Class A imports Class B, which imports Class A
// Detection: trace any cross-file import chain; if it returns to the start, it is circular.

// Resolution: extract the shared dependency or use the Game mediator pattern.
```

See [domain-architecture.md §17.3](./domain-architecture.md#173-circular-managers) for circular dependency anti-patterns and resolution.

### 5.5 Visibility Rules

| Visibility | Convention | Example |
|---|---|---|
| **Public API** | `public` methods on Manager/Model/Service | `$this->cards->playCard()` |
| **Internal** | `protected` or `private` on Manager/Model | `$this->rowToCard()` |
| **Package-private** | No PHP mechanism — convention via comments | `@internal` in docblock |
| **Framework-only** | Methods that must be called only by the framework | `setupNewGame()`, `getAllDatas()`, `zombie()` |

**Rule:** If a method does not need to be called from outside its class, it should be `private`. If it needs to be called from subclasses (traits, inheritance), it should be `protected`. If it is used only within the same domain package, document it as `@internal`.

---

## 6. Module Boundaries

### 6.1 What Defines a Module

A module is a directory with a cohesive purpose and a well-defined public API:

| Module | Public API | Internal Implementation |
|---|---|---|
| `Managers/Cards.php` | `get()`, `getAll()`, `create()`, `playCard()`, `drawCard()`, `discard()` | `rowToCard()`, SQL queries, validation helpers |
| `Managers/Players.php` | `get()`, `getAll()`, `addScore()`, `spendResources()`, `getCurrentTurn()` | `rowToPlayer()`, resource calculations, turn logic |
| `Core/Notifications.php` | `cardPlayed()`, `gainResources()`, `scoreUpdated()`, `refreshUI()` | `notifyAll()`, `notify()`, `updateArgs()`, `filterCardDatas()` |
| `Models/Card.php` | `getCost()`, `isPlayable()`, `getDisplayName()`, `toUi()` | Internal property access, formatting helpers |

### 6.2 Ownership

Every module has a clear owner (a team or individual):

| Module | Owner | Review Required |
|---|---|---|
| `Managers/Cards.php` | Backend (card subsystem) | Yes |
| `Managers/Players.php` | Backend (player subsystem) | Yes |
| `Managers/Board.php` | Backend (board subsystem) | Yes |
| `Core/Notifications.php` | Backend (notification design) | Yes |
| `Models/*.php` | Backend (data design) | Yes |
| `js/Managers/CardMgr.js` | Frontend (card UI) | Yes |
| `js/styles/` | Frontend (visual design) | Yes |

### 6.3 Extension Points

Modules should have explicit extension points for expansions:

```php
// In Cards Manager — extension point for card effects
public function onCardPlayed(int $cardId, int $playerId): void
{
    // Base game effect
    $this->applyBaseEffect($cardId, $playerId);

    // Extension hook — called by expansion cards
    $this->game->dispatchEvent('cardPlayed', $cardId, $playerId);
}
```

Extension points should be declared, not accidental. See §8 for expansion architecture.

---

## 7. Naming Conventions

### 7.1 Convention Table

| Element | Convention | Examples |
|---|---|---|
| **PHP files** | `PascalCase.php` | `PlayerTurn.php`, `CardManager.php` |
| **PHP classes** | `PascalCase` | `class PlayerTurn`, `class Cards` |
| **PHP interfaces** | `PascalCase`, no prefix | `interface Renderable` |
| **PHP traits** | `PascalCase` with `Trait` suffix | `class DebugTrait` |
| **PHP functions/methods** | `camelCase` | `getHand()`, `playCard()` |
| **PHP constants** | `UPPER_SNAKE` | `LOCATION_HAND`, `MAX_PLAYERS` |
| **PHP enum cases** | `UPPER_SNAKE` | `case PLAYER_TURN = 2` |
| **PHP variables** | `camelCase` | `$activePlayerId`, `$cardId` |
| **JS files** | `PascalCase.js` | `CardMgr.js`, `PlayerTurn.js` |
| **JS classes** | `PascalCase` | `class CardManager` |
| **JS methods** | `camelCase` | `animatePlayCard()` |
| **JS variables** | `camelCase` | `activePlayerId` |
| **CSS/SCSS files** | `kebab-case.css` | `card-styles.css`, `player-panel.scss` |
| **CSS classes** | `kebab-case` | `.player-panel`, `.card-highlighted` |
| **CSS IDs** | `kebab-case` | `#player-hand`, `#game-board` |
| **Image files** | `kebab-case.png` | `card-farm.png`, `token-wood.png` |
| **DB tables** | `snake_case` | `card`, `player`, `board_tiles` |
| **DB columns** | `snake_case` | `card_location`, `player_score` |
| **DB indexes** | `table_column` | `card_location`, `player_score_idx` |
| **State IDs** | `UPPER_SNAKE` constants | `StateIds::PLAYER_TURN = 2` |
| **State files** | `PascalCase.php` | `PlayerTurn.php` |
| **Transitions** | `camelCase` | `'cardPlayed'`, `'nextPlayer'` |
| **Notification types** | `camelCase` | `'cardPlayed'`, `'gainResources'` |
| **Notification handlers** | `notif_X` | `notif_cardPlayed()` |
| **Actions** | `actPascalCase` | `actPlayCard()`, `actPass()` |
| **Game options** | Numeric, keyed by integer | `100`, `101` in `gameoptions.jsonc` |

### 7.2 Naming Anti-Patterns

```
AVOID:                          PREFER:
──────────────────────────────────────────────────
States/State2.php               States/PlayerTurn.php
Managers/GodManager.php         Managers/Cards.php + Managers/Players.php
Models/Data.php                 Models/Player.php
Helpers/Utility.php             Helpers/Utils.php (split if > 300 lines)
Core/Misc.php                   Core/Globals.php (specific purpose)
notif_update()                  notif_updateScore()
actDoStuff()                    actPlayCard()
transition 'goTo3'              transition 'cardPlayed'
css .cardStyle                  css .card-style
```

### 7.3 State Naming

State IDs are named constants, not magic numbers:

```php
class StateIds
{
    const SETUP = 1;
    const PLAYER_TURN = 2;
    const RESOLVE_CHOICE = 3;
    const SCORE_ROUND = 4;
    const CHECK_END = 5;
    const GAME_END = 99;
}
```

State file names match state descriptions: `PlayerTurn.php`, `ResolveChoice.php`.

See [state-machine-architecture.md §6.1](./state-machine-architecture.md#61-principles).

### 7.4 Manager Naming

Managers are named after the aggregate root they own, pluralized:

| Manager | Aggregate Root | Table Owned |
|---|---|---|
| `Players` | Player | `player` |
| `Cards` | Card | `card` |
| `Board` | Board | `board_tiles`, `board_positions` |
| `Scoring` | Score | `player` (score column) |

### 7.5 Notification Naming

Notification types follow `verbNoun` camelCase:

| Notification | Triggered By | Log Message |
|---|---|---|
| `cardPlayed` | Player plays a card | `${player_name} plays ${card_name}` |
| `gainResources` | Player gains resources | `${player_name} gains ${resources_desc}` |
| `scoreUpdated` | Score changes | `${player_name} scores ${points}` |

See [notification-patterns.md §4](./notification-patterns.md#4-notification-naming-conventions).

---

## 8. Expansion Architecture

### 8.1 Feature Modules

Expansions are structured as additive modules, not modifications to existing code:

```
modules/php/
├── States/
│   ├── (base states)
│   └── ExpansionOne/
│       ├── NewAction.php
│       └── NewPhase.php
├── Managers/
│   ├── (base managers)
│   └── ExpansionOne/
│       ├── ExpansionCards.php
│       └── ExpansionBoard.php
├── Models/
│   ├── (base models)
│   └── ExpansionOne/
│       └── ExpansionCard.php
├── Core/
│   └── Notifications/
│       └── ExpansionNotifications.php
└── ExpansionOne.php           ← Entry point for expansion content
```

### 8.2 Variant Support

Game variants use configuration-driven behaviour:

```php
class Game extends Table
{
    private function isVariantActive(string $variant): bool
    {
        return (int)$this->getGameStateValue($variant) === 1;
    }

    public function setupNewGame(array $players): void
    {
        if ($this->isVariantActive('expert_mode')) {
            $this->board->configureExpertMode();
        }
    }
}
```

```json
// gameoptions.jsonc
{
    "100": {
        "name": "Expert Mode",
        "values": {
            "1": { "name": "Disabled", "tmdisplay": "Standard" },
            "2": { "name": "Enabled", "tmdisplay": "Expert" }
        },
        "default": 1
    }
}
```

### 8.3 Optional Content

Optional card pools and modules are loaded at setup time:

```php
public function setupNewGame(array $players): void
{
    $cardPool = $this->getCardPool();
    $expansionIds = $this->getActiveExpansions();

    foreach ($cardPool as $card) {
        if ($this->shouldIncludeCard($card, $expansionIds)) {
            $this->cards->createCard($card['type'], $card['typeArg'], 'deck');
        }
    }
}
```

### 8.4 Configuration-Driven Behaviour

Use configuration flags to gate expansion behaviour:

```php
class Card
{
    public function getEffect(): Effect
    {
        if ($this->game->isExpansionActive('marine_world')) {
            return $this->getMarineWorldEffect();
        }
        return $this->getBaseEffect();
    }
}
```

### 8.5 Expansion Architecture Diagram

```
┌──────────────────────────────────────────────────────────────┐
│  BASE GAME                                                    │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐     │
│  │ Game.php │  │ States/  │  │ Mgrs/    │  │ Models/  │     │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘     │
└──────────────────────────────────────────────────────────────┘
                           │
             ┌─────────────┼─────────────┐
             │             │             │
             ▼             ▼             ▼
┌──────────────────┐ ┌──────────────────┐ ┌──────────────────┐
│ EXPANSION ONE    │ │ EXPANSION TWO    │ │ EXPANSION THREE  │
│                  │ │                  │ │                  │
│ New states       │ │ New cards        │ │ New board        │
│ New managers     │ │ New mechanics    │ │ New phase        │
│ New models       │ │ New UI managers  │ │ New win-conditions│
│                  │ │                  │ │                  │
│ config: 'exp1'   │ │ config: 'exp2'   │ │ config: 'exp3'   │
└──────────────────┘ └──────────────────┘ └──────────────────┘
```

### 8.6 Future-Proofing

Design for expansion from day one:

```php
// In gameoptions.jsonc
// Reserve option IDs for future use: 100-199 base, 200+ expansions

// In dbmodel.sql
// Add columns as NULLABLE with defaults to support existing rows
ALTER TABLE card ADD COLUMN `expansion_id` INT DEFAULT NULL;

// In managers
// Use extension points rather than if/else chains
class Cards extends Manager
{
    public function onCardPlayed(int $cardId, int $playerId): void
    {
        $this->applyBaseEffect($cardId, $playerId);
        $this->game->dispatchEvent('cardPlayed', $cardId, $playerId); // hook
    }
}
```

---

## 9. Documentation Organization

### 9.1 Documentation Tree

```
docs/
├── 00-documentation-architecture.md    ← Master index and structure
│
├── foundation/                          ← Reference documents
│   ├── bga-developer-handbook.md       ← Official framework reference
│   ├── bga-ai-implementation-reference.md
│   └── reference-project-analysis.md   ← Project ratings and lineage
│
├── standards/                           ← Engineering standards
│   ├── game-flow-architecture.md
│   ├── state-machine-architecture.md
│   ├── action-architecture.md
│   ├── notification-patterns.md
│   ├── domain-architecture.md
│   ├── persistence-architecture.md
│   ├── client-synchronization-architecture.md
│   ├── client-ui-architecture.md
│   ├── animation-architecture.md
│   ├── testing-debugging-architecture.md
│   └── project-architecture.md         ← This document
│
├── architecture/                        ← System architecture
│   ├── project-structure-overview.md
│   ├── engine-architecture.md
│   └── client-data-model.md
│
├── patterns/                            ← Reusable patterns
│   ├── card-implementation-patterns.md
│   ├── simultaneous-turn-patterns.md
│   ├── multi-step-action-patterns.md
│   ├── draft-patterns.md
│   ├── spatial-board-patterns.md
│   ├── resource-management-patterns.md
│   ├── scoring-patterns.md
│   └── expansion-integration-patterns.md
│
├── playbooks/                           ← Step-by-step procedures
│   ├── new-project-from-template.md
│   ├── studio-deployment.md
│   ├── production-release.md
│   └── migrating-to-modern-framework.md
│
└── checklists/                          ← Verifiable item lists
    ├── pre-release-checklist.md
    ├── notification-audit-checklist.md
    └── state-machine-audit-checklist.md
```

### 9.2 Document Type Definitions

| Type | Icon | Length | Purpose |
|---|---|---|---|
| **Reference** | REF | 200-800 lines | Authoritative description of an existing system |
| **Engineering Standard** | STD | 300-1500 lines | Prescriptive rules and conventions |
| **Pattern Catalog** | PAT | 50-200 lines per pattern | Reusable solutions |
| **Playbook** | PLY | 100-300 lines | Step-by-step procedure |
| **Checklist** | CHK | 50-150 lines | Verifiable items |

### 9.3 Decision Records

Architectural decisions are recorded as lightweight ADRs (Architecture Decision Records):

```markdown
# ADR-001: Use Engine Pattern for Multi-Step Actions

**Date:** 2024-01-15
**Status:** Accepted
**Context:** Card effects can inject arbitrary steps into player actions.
**Decision:** Use the Engine pattern (SeqNode/OrNode/XorNode) instead of dedicated states.
**Consequences:** + Handles complex card interactions. - Debugging requires understanding Engine tree.
```

Store ADRs in `docs/architecture/adr/`.

---

## 10. AI-Friendly Architecture

### 10.1 Principles

Modern AI-assisted development benefits from specific architectural properties. These are not mandatory but improve AI tooling effectiveness.

**Small Modules.** Each file has a single, well-defined purpose. An AI tool can read one file and understand its complete behaviour without chasing references across 10 files.

**Deterministic Ownership.** Every piece of data has exactly one owning module. An AI tool can determine "who owns this?" by looking at the directory structure.

**Predictable Naming.** Names follow conventions strictly. `Cards.php` manages cards. `CardMgr.js` renders cards. `cardPlayed` is the notification for playing a card. An AI tool can predict file locations from names.

**Minimal Context Switching.** A single change should require modifying as few files as possible. Adding a card effect should not require touching Game.php, the state machine, and three managers.

**Machine-Readable Structure.** Directory names, file names, and class names follow consistent patterns that tools can parse and navigate.

### 10.2 AI-Friendly Patterns

```php
// AI-FRIENDLY: single responsibility, clear ownership
class Cards extends Manager
{
    public function playCard(int $cardId, int $playerId): Card { ... }
    public function getHand(int $playerId): array { ... }
    // All card-related logic in one place
}

// AI-HOSTILE: scattered responsibilities
class Game extends Table
{
    public function playCard(int $cardId, int $playerId): void
    {
        // Card logic mixed with player logic
    }
}
```

| Property | AI-Friendly | AI-Hostile |
|---|---|---|
| File size | < 300 lines | > 1000 lines |
| Function length | < 20 lines | > 100 lines |
| Dependencies | Explicit constructor injection | Hidden `Game::get()` calls |
| Naming | Descriptive, consistent | Abbreviated, inconsistent |
| Structure | Flat within directory | Deep nesting |
| Comments | Minimal (code is self-documenting) | Excessive or missing |

---

## 11. Build and Tooling

### 11.1 Node Tooling

```json
// package.json
{
    "scripts": {
        "lint:php": "php -l modules/php/ && vendor/bin/phpstan analyse modules/php/ --level 5",
        "lint:js": "eslint modules/js/",
        "lint:css": "stylelint modules/js/styles/",
        "lint": "npm run lint:php && npm run lint:js && npm run lint:css",
        "test": "vendor/bin/phpunit",
        "test:coverage": "vendor/bin/phpunit --coverage-html coverage/",
        "format": "prettier --write modules/js/",
        "ci": "npm run lint && npm run test"
    },
    "devDependencies": {
        "eslint": "^9.0.0",
        "prettier": "^3.0.0",
        "stylelint": "^16.0.0"
    }
}
```

### 11.2 PHP Tooling

```bash
# Static analysis
vendor/bin/phpstan analyse modules/php/ --level 5

# Unit tests
vendor/bin/phpunit

# Specific test suites
vendor/bin/phpunit tests/Managers/
vendor/bin/phpunit tests/Notifications/

# Linting
php -l modules/php/Game.php
```

### 11.3 CI Pipeline

```yaml
# .github/workflows/ci.yml
name: CI
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - run: composer install
      - run: npm install
      - run: npm run lint
      - run: npm test
```

### 11.4 Scripts

Common development scripts in `scripts/`:

```
scripts/
├── setup-dev.sh              ← Initialize development environment
├── deploy-studio.sh          ← Deploy to BGA Studio
├── run-simulation.sh         ← Run automated game simulation
├── generate-seeds.sh         ← Generate regression test seeds
└── validate-translations.sh  ← Check i18n completeness
```

---

## 12. Migration Strategy

### 12.1 Refactoring Patterns

When a project outgrows its current structure, apply incremental refactoring:

| Current State | Target State | Strategy |
|---|---|---|
| Single Game.php (2000+ lines) | Game.php + Managers | Extract by aggregate root |
| Monolithic JS file | Client Managers | Extract by DOM section |
| Flat States/ directory | Grouped by phase | Create subdirectories, update state IDs |
| Raw DB arrays everywhere | Models | Add Model classes, update callers incrementally |
| Inline notifications | Centralized Notifications class | Create Notifications class, migrate one type at a time |
| Static managers | Instance managers | Add instance reference, migrate callers |

### 12.2 Incremental Modernization

Migrate from legacy to modern patterns one step at a time:

```php
// Step 1: Add a thin Notifications wrapper alongside existing inline calls
class Notifications
{
    public static function cardPlayed($player, $cardId): void
    {
        // New centralized path
    }
}

// Step 2: Migrate call sites one by one
// Before: $this->notifyAllPlayers('cardPlayed', ...)
// After:  Notifications::cardPlayed($player, $cardId)

// Step 3: Remove legacy inline calls once all are migrated
```

### 12.3 Deprecation

Mark deprecated code with clear annotations:

```php
/** @deprecated Use CardsManager::playCard() instead */
public function actPlayCardLegacy(int $cardId, int $playerId): void
{
    // Old implementation — kept for backward compatibility
}
```

### 12.4 Compatibility

Maintain backward compatibility during migration:

```php
class Cards extends Manager
{
    // New instance method
    public function get(int $cardId): Card { ... }

    // Compatibility wrapper for old callers
    /** @deprecated Use Cards::get() instead */
    public static function sget(int $cardId): Card
    {
        return Game::get()->cards->get($cardId);
    }
}
```

---

## 13. Anti-Patterns

### 13.1 God Objects

**Symptom:** A single class (Game.php, a Manager, a Model) that does everything — renders, queries, validates, notifies.

```
Game.php (3000 lines)
├── setupNewGame()
├── getAllDatas()
├── zombie()
├── playCard()           ← should be in Cards Manager
├── addScore()           ← should be in Scoring Manager
├── renderBoard()        ← should be in client BoardMgr
├── sendNotifications()  ← should be in Notifications class
└── ...
```

**Solution:** Extract by responsibility. Each domain gets its own Manager. Each concern gets its own file.

See [domain-architecture.md §17.1](./domain-architecture.md#171-god-manager).

### 13.2 Circular Dependencies

**Symptom:** Manager A calls Manager B which calls Manager A (through Game mediator).

```
Cards::playCard() → Game::onCardPlayed() → Players::spendResources()
    ↑                                                │
    └────────────────────────────────────────────────┘
    (Game::onResourceSpent → Cards::checkTriggers)
```

**Solution:** Action methods orchestrate the sequence linearly. Managers do not call each other.

See [domain-architecture.md §17.3](./domain-architecture.md#173-circular-managers).

### 13.3 Mixed Responsibilities

**Symptom:** A file or class handles multiple unrelated concerns.

```
Card.php
├── getCost()              ← entity logic
├── renderHTML()           ← rendering (belongs in client Manager)
├── validate()             ← validation (belongs in Manager)
├── notifyCardPlayed()     ← notification (belongs in Notifications class)
└── saveToDatabase()       ← persistence (belongs in Manager)
```

**Solution:** Each class has one responsibility. Models compute. Managers coordinate. Notifications send. Client managers render.

### 13.4 Utility Dumping Ground

**Symptom:** A single `Utils.php` containing unrelated functions — string formatting, array operations, resource conversion, notification helpers.

**Solution:** Create focused utility classes or put methods on the relevant Model/Value Object.

See [domain-architecture.md §17.5](./domain-architecture.md#175-utility-dumping-ground).

### 13.5 Hidden Ownership

**Symptom:** It is unclear which Manager owns a table. Multiple managers write to the same table.

**Solution:** Document table ownership in `dbmodel.sql` comments. Enforce at code review.

See [persistence-architecture.md §4.1](./persistence-architecture.md#41-table-ownership).

### 13.6 Folder Entropy

**Symptom:** Files are placed in the wrong directory because the correct directory is "too far" or "too much work to create."

```
modules/php/
├── Game.php
├── Managers/
│   ├── Players.php
│   ├── SpecialRules.php     ← should be under a new domain
│   └── CardManager.php      ← should be Cards.php (naming)
├── States/
│   └── PlayerTurn.php
├── Helpers/
│   ├── Utils.php
│   └── card_helper.php      ← should be in Models/Card.php
└── Core/
    └── temp_debug.php       ← should be in Traits/DebugTrait.php
```

**Solution:** The folder structure should be treated as architecture, not storage. Every file's location is a design decision. Review folder structure during code review.

---

## 14. Templates

### 14.1 Canonical Repository Tree

```
<gamename>/
├── dbmodel.sql
├── gameinfos.jsonc
├── gameoptions.jsonc
├── gamepreferences.jsonc
├── stats.jsonc
├── material.inc.php
├── version.php
├── modules/
│   ├── php/
│   │   ├── Game.php
│   │   ├── States/
│   │   ├── Managers/
│   │   ├── Models/
│   │   ├── Core/
│   │   ├── Services/
│   │   ├── Helpers/
│   │   └── Traits/
│   ├── js/
│   │   ├── Game.js
│   │   ├── Managers/
│   │   ├── States/
│   │   ├── Widgets/
│   │   ├── Core/
│   │   └── styles/
│   └── css/                        ← Legacy (if needed)
├── img/
├── misc/
├── tests/
│   ├── Models/
│   ├── Managers/
│   ├── Actions/
│   └── Notifications/
├── docs/
│   ├── 00-documentation-architecture.md
│   ├── foundation/
│   ├── standards/
│   ├── patterns/
│   ├── playbooks/
│   └── checklists/
├── scripts/
├── package.json
└── .github/
    └── workflows/
        └── ci.yml
```

### 14.2 Canonical Server Module

```php
<?php
declare(strict_types=1);

namespace MyGame\Managers;

use MyGame\Models\Card;
use MyGame\Core\Globals;

class Cards extends Manager
{
    // ── READ ──

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

    // ── WRITE ──

    public function playCard(int $cardId, int $playerId): Card
    {
        $this->game->DbQuery(
            "UPDATE card SET card_location = 'play', card_location_arg = $playerId
             WHERE card_id = $cardId"
        );
        return $this->get($cardId);
    }

    // ── INTERNAL ──

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

### 14.3 Canonical Client Module

```js
class CardManager {
    constructor(game, containerId) {
        this.game = game;
        this.container = document.getElementById(containerId);
        this.cards = {};
    }

    setup(cardsData) {
        this.cards = cardsData;
        this.renderAll();
    }

    onCardPlayed(args) {
        this.cards[args.card_id].location = 'play';
        this.moveCardElement(args.card_id, 'play');
    }

    onStateChange(stateName, args) {
        if (args.playableCards) this.setSelectable(args.playableCards);
    }

    reset(cardsData) {
        this.cards = cardsData;
        this.container.innerHTML = '';
        this.renderAll();
    }

    renderAll() {
        for (const card of Object.values(this.cards)) {
            this.createCardElement(card);
        }
    }

    createCardElement(card) {
        let el = document.getElementById(`card-${card.id}`);
        if (el) return el;
        el = document.createElement('div');
        el.id = `card-${card.id}`;
        el.className = `card card-${card.type}`;
        el.dataset.cardId = card.id;
        this.container.appendChild(el);
        return el;
    }

    moveCardElement(cardId, location) {
        const el = this.getElementById(cardId);
        const target = document.getElementById(`${location}-area`);
        if (el && target) target.appendChild(el);
    }

    getElementById(cardId) {
        return document.getElementById(`card-${cardId}`);
    }
}
```

### 14.4 Canonical Feature Module

Adding a new domain (e.g., "Events") to the project:

```php
// 1. dbmodel.sql
-- TABLE: event
-- OWNER: Managers/Events
-- AGGREGATE: Event
CREATE TABLE IF NOT EXISTS `event` (
    `event_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `event_type` varchar(32) NOT NULL,
    `event_state` varchar(16) NOT NULL DEFAULT 'pending',
    `event_player_id` int(10) unsigned DEFAULT NULL,
    PRIMARY KEY (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
```

```php
// 2. Models/Event.php
class Event
{
    public function __construct(
        private readonly int $id,
        private readonly string $type,
        private string $state,
        private ?int $playerId,
    ) {}

    public function isPending(): bool
    {
        return $this->state === 'pending';
    }

    public function resolve(): void
    {
        $this->state = 'resolved';
    }
}
```

```php
// 3. Managers/Events.php
class Events extends Manager
{
    public function get(int $id): Event { ... }
    public function create(string $type, int $playerId): Event { ... }
    public function resolve(int $id): void { ... }
}
```

```php
// 4. Game.php
public function __construct()
{
    parent::__construct();
    $this->events = new Events($this);
}
```

```php
// 5. Notifications (add to existing or new file)
Notifications::eventTriggered($player, $event);
```

```js
// 6. Client Manager
class EventManager {
    constructor(game, containerId) { ... }
    onEventTriggered(args) { ... }
}
```

### 14.5 Canonical Documentation Layout

```markdown
# Title — Document Type

**Document purpose:** One-sentence description of what this document covers.

**Applicability:** Who should read this and when.

**Cross-references:**
- [related-document.md](./related-document.md) — specific section reference

---

## Table of Contents

- [Section 1](#section-1)
- [Section 2](#section-2)

---

## 1. Section One

### 1.1 Subsection

Content with cross-references to authoritative sources.

```php
// Code examples inline
```

---

## References

- [source-document.md](./source-document.md) — §specific sections
```

---

## 15. Checklists

### 15.1 Architecture Review

- [ ] Every file has a single, well-defined responsibility
- [ ] The folder structure matches the project's current scale (small/medium/large/very large)
- [ ] No file exceeds its maximum line count (§3.3)
- [ ] Dependencies follow the allowed/forbidden rules (§5)
- [ ] No circular dependencies exist (verified by tracing import chains)
- [ ] All naming conventions are followed (§7)
- [ ] Table ownership is documented in `dbmodel.sql`
- [ ] Each Manager owns exactly one aggregate root
- [ ] Client managers mirror server domain structure (for large projects)
- [ ] Expansion architecture is designed from day one (§8)
- [ ] Tests are organized to mirror the server structure
- [ ] Documentation exists for all architectural decisions

### 15.2 Folder Review

- [ ] `modules/php/` contains only server-side PHP code
- [ ] `modules/js/` contains only client-side JavaScript code
- [ ] `modules/css/` is empty (prefer `modules/js/styles/`)
- [ ] States are organized into subdirectories when > 15 files
- [ ] Managers are one per aggregate root (never a single "GodManager")
- [ ] Models are separated from Managers (no model logic in Manager files)
- [ ] Helpers are focused (not a dumping ground)
- [ ] Traits contain only shared behaviour (DebugTrait, etc.)
- [ ] Test directory mirrors the PHP module structure
- [ ] No files in the root directory except framework-required files
- [ ] `img/` contains only game images (no code)
- [ ] `misc/` is empty or contains only Studio scratch files

### 15.3 Dependency Review

- [ ] Models never import from Managers or Core
- [ ] Value objects never import from the framework
- [ ] Managers never call other managers directly
- [ ] Notifications class never imports from Managers or Models
- [ ] Action methods contain no raw SQL
- [ ] Action methods contain no direct `notifyAllPlayers` calls
- [ ] State classes contain no domain logic (delegated to Managers)
- [ ] Game.php is thin (< 400 lines)
- [ ] No circular dependencies between Managers
- [ ] Engine (if used) does not call the state machine directly
- [ ] Client managers do not call each other directly

### 15.4 Naming Review

- [ ] PHP files: `PascalCase.php`
- [ ] JS files: `PascalCase.js`
- [ ] CSS/SCSS files: `kebab-case.css`
- [ ] Database tables: `snake_case`
- [ ] State IDs: named constants (not magic numbers)
- [ ] Transitions: `camelCase` semantic names
- [ ] Notification types: `camelCase`
- [ ] Notification handlers: `notif_X` on client
- [ ] Action methods: `actPascalCase`
- [ ] Manager names: pluralized aggregate root
- [ ] CSS classes: `kebab-case`
- [ ] All names are descriptive (not abbreviated)

### 15.5 Expansion Readiness

- [ ] Game options reserve IDs for future expansions (100+)
- [ ] Managers have extension points (`dispatchEvent` hooks)
- [ ] Schema columns are nullable with defaults for future additions
- [ ] Card pools are configurable (loaded at setup time)
- [ ] No hard-coded card lists in game logic
- [ ] Feature flags (`isExpansionActive()`) gate expansion behaviour
- [ ] New domains can be added without modifying existing managers
- [ ] Client UI can accommodate new panels/sections
- [ ] Notification system can be extended with new types
- [ ] State machine can accept new states without renumbering
- [ ] Test infrastructure supports expansion-specific test suites

---

## References

- [domain-architecture.md](./domain-architecture.md) — layered architecture (§2), folder structure (§12), dependency rules (§14), testing (§15), refactoring patterns (§18)
- [persistence-architecture.md](./persistence-architecture.md) — table ownership (§4), migration strategy (§15), naming conventions (§5)
- [client-ui-architecture.md](./client-ui-architecture.md) — manager pattern (§5), scaling tiers (§14), folder organization (§14)
- [client-synchronization-architecture.md](./client-synchronization-architecture.md) — notification lifecycle (§5), getAllDatas (§3)
- [animation-architecture.md](./animation-architecture.md) — animation ownership (§3), animation types (§4)
- [action-architecture.md](./action-architecture.md) — thin action principle (§5), validation layers (§6), delegation (§11)
- [state-machine-architecture.md](./state-machine-architecture.md) — state taxonomy (§3), state IDs (§6.1), transition naming (§9)
- [game-flow-architecture.md](./game-flow-architecture.md) — module layout (§3.1), thin coordinator (§3.3), execution pipeline (§2)
- [testing-debugging-architecture.md](./testing-debugging-architecture.md) — testing pyramid (§2), CI validation (§12), debugging infrastructure (§8)
- [notification-patterns.md](./notification-patterns.md) — centralized notification class (§15.1), naming conventions (§4), payload design (§5)
- [reference-project-analysis.md](../foundation/reference-project-analysis.md) — project lineage, subsystem ratings, scaling patterns, Earth client architecture
- [bga-developer-handbook.md](../foundation/bga-developer-handbook.md) — framework project structure, required files, migration guide
- [00-documentation-architecture.md](../00-documentation-architecture.md) — documentation folder layout, numbering scheme, document types
