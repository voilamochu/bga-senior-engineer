# BGA Reference Project Analysis — Canonical Inventory

**Document purpose:** Analyze every official BGA reference project from a software engineering perspective. Identify strengths, weaknesses, subsystem quality, and architectural patterns so that future implementations can make informed design choices.

**Scope:** Four reference projects — Agricola, Ark Nova, Lost Ruins of Arnak, Earth. Each is assessed independently (no cross-project comparisons). Ratings are 1-5 stars per subsystem.

---

## Table of Contents

- [Executive Summary](#executive-summary)
- [1. Agricola](#1-agricola)
  - [1.1 Game Type](#11-game-type)
  - [1.2 Subsystem Ratings](#12-subsystem-ratings)
  - [1.3 Why Learn From This](#13-why-learn-from-this)
  - [1.4 Outdated / Legacy Patterns](#14-outdated--legacy-patterns)
- [2. Ark Nova](#2-ark-nova)
  - [2.1 Game Type](#21-game-type)
  - [2.2 Subsystem Ratings](#22-subsystem-ratings)
  - [2.3 Why Learn From This](#23-why-learn-from-this)
  - [2.4 Outdated / Legacy Patterns](#24-outdated--legacy-patterns)
- [3. Lost Ruins of Arnak](#3-lost-ruins-of-arnak)
  - [3.1 Game Type](#31-game-type)
  - [3.2 Subsystem Ratings](#32-subsystem-ratings)
  - [3.3 Why Learn From This](#33-why-learn-from-this)
  - [3.4 Outdated / Legacy Patterns](#34-outdated--legacy-patterns)
- [4. Earth](#4-earth)
  - [4.1 Game Type](#41-game-type)
  - [4.2 Subsystem Ratings](#42-subsystem-ratings)
  - [4.3 Why Learn From This](#43-why-learn-from-this)
  - [4.4 Outdated / Legacy Patterns](#44-outdated--legacy-patterns)
- [5. Summary Matrix](#5-summary-matrix)
- [6. Key Takeaways — Which Project to Consult for What](#6-key-takeaways--which-project-to-consult-for-what)

---

## Executive Summary

These four projects span the full complexity range of BGA implementations, from mid-weight (Arnak) to heavyweight (ArkNova). They represent two distinct architectural lineages:

1. **Pecatte/Toper lineage** (Agricola, Ark Nova) — built around a custom decision-tree Engine (`SeqNode`/`OrNode`/`XorNode`/`ParallelNode`/`LeafNode`) that decouples game flow from state machine transitions. Both share the same Core/Helpers/Managers/Models/States directory layout.

2. **Independent implementations** (Arnak by Adam Spanel, Earth by Guillaume Benny) — each invented their own architecture. Arnak uses a straightforward manual-state-machine approach. Earth uses a command-pattern queue with private state machines for simultaneous turns.

The key architectural distinction is **flow management**: the Engine projects invert control (the tree drives state transitions), while the manual projects drive state transitions directly in game logic. Neither is universally better — the Engine suits complex multi-step card games, while Earth's command pattern is mandatory for simultaneous play.

---

## 1. Agricola

**Implementation by:** Timothée Pecatte, Vincent Toper

### 1.1 Game Type

Worker placement, farming, resource management, engine building, drafting, spatial (farmyard grid layout). Turn-based. Large card deck (500+ unique cards across 5 expansions + base).

### 1.2 Subsystem Ratings

| Subsystem | Stars | Notes |
|---|---|---|
| Deck management | ★★★★★ | Custom `cards` DB table with JSON `extra_datas`. Five card decks + major improvements. BGA's `Deck` class is **not** used — full custom implementation via the `Pieces` helper. |
| Gamestate architecture | ★★★★★ | 40+ states. Clean separation of `game` / `activeplayer` / `multipleactiveplayer` types. Uses the custom decision-tree Engine (SEQ/OR/XOR/PARALLEL/LEAF nodes) which replaces most manual state transitions. The Engine is the crown jewel of this project. |
| Notifications | ★★★★★ | `Notifications.php` — 100+ notification types with centralized `updateArgs()` for i18n, resource names, player names, card names. `refreshUI` / `refreshHand` pattern for undo recovery. `clearTurn` notification for UI rollback on restart. |
| Undo support | ★★★★★ | Full multi-level undo via `Log.php`. Logs every create/update/delete to a `log` table. On undo, reverts DB mutations and cancels gamelog packets via the `cancel` column. Supports per-step undo and full-turn restart. Snapshot-based gamelog cancellation with packet IDs. |
| Engine / Flow | ★★★★★ | Custom `Engine.php` with tree-based decision nodes (SeqNode, OrNode, XorNode, ParallelNode, LeafNode). Card reactions hook into flow via `before<Action>()` and `computeReplace<Action>()` listener methods. Auto-resolves single-choice nodes. Supports cross-player partial-turn confirmation. |
| Card implementations | ★★★★★ | Each occupation and minor improvement is a separate PHP class in subdirectories `A/`, `B/`, `C/`, `D/`, `E/` (168 + 71 + 180+ each). Cards register listeners and reactions that auto-modify the Engine flow tree. |
| Database patterns | ★★★★☆ | Five custom tables (`meeples`, `global_variables`, `cards`, `user_preferences`, `log`). Uses `global_variables` with JSON column for typed globals. Custom `QueryBuilder` for SQL abstraction. `Pieces` for polymorphic meeple storage. |
| Player panels | ★★★★☆ | Custom player boards with farmyard grid stored as x/y coordinates in the DB. Drop zones for animal placement. |
| Drafting | ★★★★★ | Multiple draft modes: Living Hand, 7-of-10, simultaneous async draft, occupation-first draft, minor-first draft. Snake-opening round-1 variant for alternative first-round flow. |
| Statistics | ★★★☆☆ | Standard BGA stats via `stats.inc.php`. Card-level stat tracking via `card_stats` on individual card models. |
| End game | ★★★★☆ | Campaign mode spanning 8 games with persistent occupations across sessions. Full solo mode. Pre-end-game scoring pass. |
| Zombie mode | ★★★★☆ | `clearZombieNodes()` in Engine — removes unresolved tree nodes for disconnected players. |
| Reconnect handling | ★★★★★ | `refreshUI` + `refreshHand` pattern sets the standard across all references. On undo or reconnect, the server rebroadcasts full state, then player-specific hands. |
| Debugging | ★★★★★ | `DebugTrait.php` with Studio debug actions. Seed loading system for reproducible game setup. `bug-triage.md` and `bugspage1.json`. Dedicated "check combos" debug state. |
| Multi-step actions | ★★★★★ | Entirely Engine-driven. Atomic action classes (PlaceFarmer, Sow, Fencing, Renovation, Construct, etc.) are independent leaf nodes composed into sequences by the Engine tree. |
| Solo mode | ★★★★★ | Full campaign system with persistence, dedicated solo action card mode, opponent-like mechanics. |
| Logging | ★★★★★ | Rich notification log with resource name resolution. `appendResourceNames()` ensures archived game logs are readable without game JS. |

### 1.3 Why Learn From This

- **The Engine pattern** is the most sophisticated flow management system in any BGA reference. It decouples game logic from state machine transitions entirely, allowing cards to inject, replace, or extend actions at runtime.
- **The undo system** is production-grade. It handles partial-turn confirmation across players, checkpoint/step granularity, gamelog packet cancellation, and notification rollback. This is the reference implementation for undo on BGA.
- **The card architecture** — one class per card with listener-based reactions — is the gold standard for card-driven games. The `before<Action>` / `computeReplace<Action>` hook pattern is elegant and extensible.
- **The notification refresh pattern** (`refreshUI` + `refreshHand`) is the canonical approach for reconnection and undo recovery. Every project should replicate this.

### 1.4 Outdated / Legacy Patterns

- **Dojo** framework on the client side (not Vue/TypeScript)
- Uses `gameinfos.inc.php` (old PHP format) instead of `gameinfos.json`
- Custom card DB instead of BGA's built-in `Deck` class — the custom approach is more powerful but adds complexity that simpler games should avoid
- No SCSS modules at root level — all SCSS lives under `modules/css/`
- No TypeScript anywhere
- Does not use BGA's `Stock` component for resource display
- Individual card class files per card (500+) is correct for Agricola's complexity but over-engineered for smaller card pools

---

## 2. Ark Nova

**Implementation by:** Timothée Pecatte, Vincent Toper

### 2.1 Game Type

Engine building, tableau building, card management, spatial (hex-grid zoo map), multi-track progression (appeal / reputation / conservation), action card strength system, break mechanism, simultaneous discard phase.

### 2.2 Subsystem Ratings

| Subsystem | Stars | Notes |
|---|---|---|
| Gamestate architecture | ★★★★★ | 83 states — the largest state machine in the reference set. Well-organized into labelled sections: Setup, Turn, Base Actions (cards/animals/build/sponsors/association), Card Effects, Break, Marine World, Map Pack 2. |
| Engine / Flow | ★★★★★ | Same Engine pattern as Agricola (Seq/Or/Xor/Parallel/Leaf nodes) but extended with `FlowConvertor.php` which converts arbitrary bonuses into Engine flow trees at runtime. This enables cards/sponsors to generate dynamic flows on the fly. |
| Notifications | ★★★★★ | ~300 notification methods. The key innovation is the **auto-delta system**: `$listeners` array with cached values that detects state changes (icons, income, score, map status, hand limit, project strength) and only sends diffs. `pDrawCards` / `pDiscardCards` dual-notification pattern for hidden information. |
| Hex map / Spatial UI | ★★★★★ | `ZooMap.php` (1275 lines) — full hex grid system with cube coordinates (x:0-8, y:1-11). Building placement validation with adjacency rules, terrain constraints, standard/kiosk/pavilion/avian/aquatic enclosure management. |
| Card diversity | ★★★★★ | 160 animal cards, 81 sponsor cards, 39 conservation project cards, 17 final scoring cards, 26 zoo map classes, 5 action card types. Each card is a distinct PHP class extending `ZooCard` with static data + dynamic state. Cards support event listeners via `isListeningTo()`. |
| Multi-action flow | ★★★★★ | Five action cards (Cards, Animals, Build, Sponsors, Association) each parameterized by a mutable strength value (1-5). Strength modifies power: more cards drawn, larger buildings, more animals, etc. Complex nested card effects (Hunter, Perception, Scavenging, Sunbathing, Posturing, Peacocking, etc.) each have dedicated states. |
| Break phase | ★★★★★ | Multi-active simultaneous discard phase with per-player private state. Full break lifecycle: advance → pre-cards → discard → refill → income → finish. Workers return, tokens clean up, display refills. |
| Player resource model | ★★★★★ | `Player.php` (1242 lines) — comprehensive model managing money, appeal, reputation, conservation, X-tokens, association workers, hand limit, income, score. Rich API with getters for computed values. |
| Undo support | ★★★★★ | Same Log-based system as Agricola. Snapshot-based gamelog cancellation with packet IDs. Conditionally disabled during certain cross-player effects. |
| Database patterns | ★★★★☆ | Uses custom `Pieces`, `DB_Model` (ActiveRecord-like), `CachedDB_Manager` (in-memory row cache), `Collection` (enhanced ArrayObject), `QueryBuilder`. No `dbmodel.sql` found in project — schema is managed externally or programmatically. |
| Private information | ★★★★★ | Dual pattern: public notification ("Paul draws 2 cards") + private notification with actual card data ("You draw Tiger, Amazon Tree Boa"). Used for draws, discards, pilfering, storing cards. |
| Expansion support | ★★★★★ | Marine World (MW) fully integrated with additional sponsor cards, university types, reef abilities, bonus tiles, and card filtering. Map Pack 2. All gated behind `MW` runtime flags. Runs alongside base cards without architectural changes. |
| Debugging | ★★★★☆ | `DebugTrait.php` with generic debug actions. Less structured than Agricola's seed-loading system, but adequate for studio development. |
| Scoring | ★★★★★ | Dynamic score calculation from appeal + conservation + final scoring cards. Continuous score updates pushed via the notification delta listener system. Final scoring card evaluation at end-game. |
| Spectator support | ★★★★☆ | `resetCache()` refreshes notification state. Standard BGA spectator handling through `refreshUI`. |
| Zombie mode | ★★★★☆ | Same Engine-based node-clearing pattern as Agricola. Handles zombie players in multi-active break phase. |

### 2.3 Why Learn From This

- **The action card strength system** is unique among all references. It shows how to parameterize every major action by a mutable power level that changes as the game progresses.
- **The notification delta system** (`$listeners` / `updateIfNeeded`) is the most efficient notification pattern in any reference — only changed values are sent, reducing payload size significantly.
- **The hex grid implementation** is the only spatial map system across all four projects. Indispensable for any game with a modular board, tile placement, or area majority.
- **The break phase** demonstrates a complete multi-active simultaneous sub-game within a turn-based game.
- **Expansion integration** is seamless — MW cards co-exist with base cards, new mechanics are added without touching core flow.

### 2.4 Outdated / Legacy Patterns

- **Dojo** framework on the client side
- No TypeScript anywhere
- Does not use BGA's `Stock` or `Deck` components
- Uses `gameinfos.inc.php` instead of JSON
- `Notifications.php` at 1672+ lines is very large — splitting by domain (Cards, Animals, Buildings, Effects) would improve maintainability
- `Player.php` at 1242 lines is a god class by modern standards
- No `dbmodel.sql` present — unclear where the canonical schema lives
- ASCII art section headers in `states.inc.php` are cosmetic and clutter the file
- Uses `version.php` with plain string-based version tracking (fragile)

---

## 3. Lost Ruins of Arnak

**Implementation by:** Adam Spanel

### 3.1 Game Type

Deck-building, worker placement, resource management, exploration, multi-track research, guardian combat, artifact/idol system, assistant management.

### 3.2 Subsystem Ratings

| Subsystem | Stars | Notes |
|---|---|---|
| Deck management | ★★★★☆ | Uses custom `card` table with `card_position` enum (hand, deck, play, discard, supply, earring, keep). Standard BGA `Deck` class NOT used. Custom deck ordering via `deck_order` column. |
| Gamestate architecture | ★★★★☆ | ~30 states. Clean and straightforward with numeric state IDs. Fewer states than Agricola/ArkNova because turns are simpler: one main action per turn + free action window (`AFTER_MAIN`). The `AFTER_MAIN` state pattern (free actions before passing) is clean. |
| Worker placement | ★★★★☆ | Sites on the board with slot-based placement and travel costs. Standard BGA worker placement pattern. `location` table for board site data with size/is_open/position. |
| Card effects | ★★★★☆ | `card_effects.php` (609 lines) with `CardEffects` class. Effects are separated by type (artifact effects, item effects, basic card effects). Simpler than Agricola's per-card class approach — more practical for smaller card pools. |
| Resource management | ★★★★☆ | Five resources (coins, compass, tablet, arrowhead, jewel) stored as extra columns on the `player` table. Transport tech (boots, ships, cars, planes) for travel costs. |
| Exploration mechanic | ★★★★☆ | Site discovery with face-down locations. Travel costs based on distance and transport tech level. `travel_costs` in material. |
| Research tracks | ★★★★★ | Three research tracks (compass/tablet/arrowhead) with unlockable bonuses. Temple rank progression with guardian combat. `research_bonus` table for per-position bonuses. Research token economy. |
| Idol / Artifact system | ★★★★☆ | Artifacts grant special effects: assistant refresh, card upgrade, card exile, deck refresh. `ART_EFFECT` state with branching transitions (`artDone`, `mayExile`, `mustDiscard`, etc.). |
| Assistant system | ★★★★☆ | 20 unique assistants with different abilities (gain resources, upgrade cards, etc.). `assistant` table with ready/gold/offer state. `in_offer` for the market display. |
| Guardian combat | ★★★★☆ | Guardians on excavation sites with power values that must be overcome. `guardian` table. `idol_bonus` on board positions for rewards. |
| Notifications | ★★★☆☆ | Standard BGA pattern: direct `notifyAllPlayers` / `notifyPlayer` calls throughout game logic. No centralized notification class. No delta system. |
| Player panels | ★★★★☆ | Custom player boards with research track display, assistant slots, artifact/item slots, temple rank. Material-driven layout from `material.inc.php`. |
| Scoring | ★★★★☆ | Temple rank + research score + card points. `player_score_aux` tiebreaker formula. Clean multi-phase end-game sequence. |
| Statistics | ★★★★☆ | 30+ stats defined in `stats.inc.php`. Good coverage for cards played, sites explored, guardians defeated, research advances. |
| Game options | ★★★★☆ | Bird Temple / Snake Temple variants. Player preferences for card selection. |
| Debugging | ★★★☆☆ | Minimal debug infrastructure. No seed loading, no debug trait, no dedicated debug actions. Relies on BGA studio defaults. |
| Undo support | ★★★☆☆ | Single-action undo via an `undo` action in the state machine. No multi-step undo system like Agricola's Log. `db_undo_support` is enabled for basic framework-level undo. |
| Zombie mode | ★★★☆☆ | Standard zombie handling via BGA framework defaults. No custom zombie architecture or flow cleanup. |
| End game | ★★★★☆ | Clean two-phase end: scoring state (id 98) → gameEnd (id 99). Pre-end-game logic handles last-round completion. |
| Tooltips | ★★★★★ | `tooltips.js` (474 lines) — dedicated tooltip class with comprehensive coverage. Best tooltip infrastructure among all references. |

### 3.3 Why Learn From This

- **Best reference for a mid-complexity game** — it shows how to structure a BGA project that's non-trivial (deck-building + worker placement + research + combat) but not overwhelming.
- **The `AFTER_MAIN` free-action pattern** is elegant — after the mandatory action, players get a free-form window to play cards, use assistants, or pass.
- **Material-driven design** — `material.inc.php` (307 lines) defines all game data (cards, assistants, research tracks, travel costs, sites, guardians) in one file. This is the cleanest material definition among references.
- **Tooltip infrastructure** is the most complete — dedicated JS class with per-item tooltip strings. Should be replicated in any game with complex card text.
- **Good example of a single-developer project** — more representative of typical BGA projects than the large-team projects.
- **CSS architecture** is well-organized into domain-specific files (`arn_cards.css`, `arn_cardboard.css`, `arn_icons.css`, `arn_meeple.css`, `arn_tooltip.css`).

### 3.4 Outdated / Legacy Patterns

- **Dojo** framework on the client side
- **Numeric state IDs** (e.g., `NEXT_ROUND => 64`, `98`, `99`) instead of named constants — makes the code harder to read and refactor
- Uses PHP `array()` syntax throughout (PHP 5 style) — should be `[]` for modern PHP
- No TypeScript, no SCSS modules (uses flat CSS files)
- No Engine/Flow abstraction — all state transitions are manual
- `card_effects.php` at 609 lines is a monolith; splitting per card type would be cleaner
- Game logic is mixed into the single `arnak.game.php` file (2487 lines) instead of being split into trait modules
- Uses old `stats.inc.php` format instead of `stats.json`
- Uses `gameoptions.inc.php` instead of JSON formats

---

## 4. Earth

**Implementation by:** Guillaume Benny

### 4.1 Game Type

Engine building, tableau building, card drafting, simultaneous turn execution, multi-action selection (Plant/Compost/Water/Grow), event cards, conversion system, activation system, fauna board.

### 4.2 Subsystem Ratings

| Subsystem | Stars | Notes |
|---|---|---|
| Simultaneous turns | ★★★★★ | Full implementation using BGA's `initialprivate` + private states. Each player gets their own private state machine running inside a shared multi-active state. The only reference demonstrating true simultaneous play. |
| Private state machine | ★★★★★ | `PrivateState.php` — a custom layer wrapping BGA's private state API. Each player has an independent state machine with its own transitions, args, and undo scope, running within a parent multi-active state. |
| Action command queue | ★★★★★ | `Action.php` (1458 lines) — full command pattern. Every player action is a `BaseActionCommand` with `do()` / `undo()` methods. `ActionCommandMgr` manages the queue lifecycle: apply (execute in private context), commit (execute publicly + persist), undo (reverse + clear). |
| Undo support | ★★★★★ | Per-action undo within private state. `undoLast()` reverses the most recent command; `undoAll()` clears the entire pending queue. The `reevaluate()` system handles cross-player invalidation — if the active player's action changes the game state, other players' pending commands are automatically re-evaluated and may be undone. |
| Notifications | ★★★★★ | Four distinct notifier types: `ActionCommandNotifierPrivate` (unconfirmed actions with `[Unconfirmed action]` prefix), `ActionCommandNotifierPublic` (committed actions broadcast to all), `ActionCommandNotifierUndo` (silent revert notifs), `ActionCommandNotifierNone` (internal application without notifs). |
| Client architecture | ★★★★★ | Modular Manager pattern: `CardMgr` (card rendering/interaction), `TableauMgr` (tableau grid layout), `DeckMgr` (deck/discard), `PlayerBoardMgr` (player board), `PlayerPanelMgr` (score panels), `GainMgr` (resource gain selection), `PaymentMgr` (resource payment), `ScoreMgr` (scoring breakdown), `LeafTokenMgr` (leaf tokens), `FaunaBoardMgr` (fauna objectives), `GaiaBoardMgr` (solo opponent), `CardDetailMgr` (card zoom), `ObjectiveDetailMgr` (objective display), `HandMgr` (hand management). |
| Database schema | ★★★★★ | `dbmodel.sql` (237 lines) — 8 well-commented tables. `card` with `sprout_count` / `growth_count` for game state on cards. `player_state` for all per-player mutable state. `game_state` for global mutable state. `action_command` for the undo queue. `card_tag` for card tagging system. `player_seen_leaf_token` for hidden information tracking. `player_score` for granular end-game scoring. `player_exchange` for sprout gifting. |
| Card definitions | ★★★★★ | `CardDefMgr` hierarchy with domain-specific managers: Earth cards, Climate cards, Island cards, Fauna cards, Ecosystem cards, Abundance cards, Gaia (solo) cards. Each card is defined by a `CardDef` object with abilities, tags, costs, and scoring. |
| Card abilities system | ★★★★★ | `Ability.php` — full ability/effect system. Cards have abilities that trigger on specific events (plant, compost, water, grow, activate). Abilities can grant gains, require payments, copy other cards, or modify game state. Gain selection with "choose up to N" and "gain list" patterns. |
| Activation system | ★★★★★ | After the main action phase, players activate their island cards, climate cards, and tableau cards in a chosen direction (player board → tableau or tableau → player board). Card copy abilities allow cloning another card's effect. Activation order is per-player and independent. |
| Scoring | ★★★★★ | `player_score` table stores per-card scoring breakdowns by type (card score, event score, compost, sprout, growth, terrain, ecosystem, fauna). `Score.php` computes comprehensive end-game totals with progress tracking for ecosystem cards. |
| Game flow | ★★★★★ | `GameNextPhase.php` is the central phase dispatcher. `LastRound.php` handles end-game detection. Clean phase transitions: setup → main action → plant/compost/water/grow → activation → end turn → (repeat or score). |
| Solo mode | ★★★★☆ | Gaia system with an AI opponent that gains resources each turn. Solo fauna objectives. Gaia color and turn tracking in `game_state`. |
| Conversion system | ★★★★☆ | Sprout-to-soil conversion with ratio scaling (3→2, 6→4, 9→6). Seed germination (convert seed to sprout). Dedicated conversion private states. |
| Event cards | ★★★★★ | Event cards can be played during any phase. The event flow interrupts the current action, processes the event (choose card → pay → gain → return), then resumes the interrupted action. `return_from_event_state_id` stores the return point. |
| Locking | ★★★★☆ | `Lock.php` — custom MySQL-based advisory lock system. Prevents race conditions during simultaneous play where the active player's action could invalidate other players' pending actions in the millisecond between AJAX calls. |
| Debugging | ★★★★☆ | `Debug.php` in both `BX/` and `EA/` modules. Provides studio debug actions and state inspection. |
| Zombie mode | ★★★★☆ | `zombieRemoveAll()` in `ActionCommandMgr` — removes all pending action commands for the disconnected player. Standard private state cleanup. |

### 4.3 Why Learn From This

- **The only reference with simultaneous turn execution** — Earth solves a problem none of the other projects address. The private state + command queue pattern is essential for any game where all players act at the same time.
- **Command pattern for undo** — the only reference where every action is individually undoable. The `ActionCommand` hierarchy with `do()` / `undo()` is the cleanest undo implementation in any reference.
- **Cross-player invalidation** — the `reevaluate()` system handles the hardest undo problem: when Player A's committed action invalidates Player B's pending actions. This is novel and not present in any other project.
- **Modular client-side architecture** — the Manager pattern is the most modern and maintainable of all references. Each UI subsystem has a dedicated manager class with clear responsibilities.
- **SCSS + CSS architecture** — the most sophisticated styling system among references, with well-structured SCSS partials.
- **Modern config files** — uses `stats.json`, `gameoptions.json`, `gamepreferences.json` instead of PHP array files.
- **Annotation-driven DB rows** — `@dbcol`, `@dbkey`, `@dbautoincrement` annotations on PHP class properties for automatic database mapping. This is the most advanced DB abstraction in any reference.
- **Clean separation of concerns** — game logic is cleanly split between `BX/` (framework-level utilities: DB, Action, Lock, PrivateState, MultiActiveState) and `EA/` (game-specific: Cards, Abilities, States, Actions, Scoring).

### 4.4 Outdated / Legacy Patterns

- Still uses **Dojo** framework on the client side (though the modular Manager pattern mitigates Dojo's limitations)
- No TypeScript (the JS is the most modular of all references, but still vanilla JS)
- Large files: `Action.php` (1458 lines) is a monolith that could be split into `ActionCommand.php`, `ActionCommandMgr.php`, `Notifier.php`
- The private state + command queue approach is powerful but complex — overkill for simpler turn-based games
- Custom locking system (`Lock.php`) adds infrastructure complexity that most games won't need
- No SCSS sourcemaps or development build pipeline visible
- The annotation-based DB mapping (`@dbcol` etc.) is clever but relies on runtime reflection which has performance implications

---

## 5. Summary Matrix

| Feature | Agricola | Ark Nova | Arnak | Earth |
|---|---|---|---|---|
| Flow Engine | ★★★★★ | ★★★★★ | ☆ | ☆ |
| Undo System | ★★★★★ | ★★★★★ | ★★★ | ★★★★★ |
| Simultaneous Turns | ☆ | ☆ | ☆ | ★★★★★ |
| Private State Machine | ☆ | ☆ | ☆ | ★★★★★ |
| Notifications | ★★★★★ | ★★★★★ | ★★★ | ★★★★★ |
| Card Architecture | ★★★★★ | ★★★★★ | ★★★★ | ★★★★★ |
| DB Schema | ★★★★ | ★★★★ | ★★★★ | ★★★★★ |
| Spatial UI | ★★★★ | ★★★★★ | ★★★★ | ★★★★ |
| Client Architecture | ★★★ | ★★★ | ★★★ | ★★★★★ |
| Expansion Support | ★★★★ | ★★★★★ | ★★★★ | ★★★★ |
| Documentation | ★★★★★ | ★★★★ | ★★★ | ★★★ |

---

## 6. Key Takeaways — Which Project to Consult for What

### If you are implementing a game with...

| Problem | Consult | Why |
|---|---|---|
| **Complex multi-step actions with card interactions** | **Agricola** | The Engine tree + card listener pattern is unmatched. Cards can inject, replace, or extend any action at runtime. |
| **Simultaneous / real-time turns** | **Earth** | The only reference that solves this. Private state machines + action command queue. |
| **A modular board or hex grid** | **ArkNova** | `ZooMap.php` is the only spatial map system in the references. |
| **Full undo / redo support** | **Agricola or Earth** | Agricola for turn-based undo with partial-turn confirmation; Earth for per-action undo with cross-player invalidation. |
| **An existing physical card pool (50-200 cards)** | **Arnak** | The material-driven approach with `material.inc.php` and a single `card_effects.php` is the right level of complexity for medium card pools. |
| **A massive card pool (500+ cards)** | **Agricola or ArkNova** | Per-card class files with listener hooks scale to any number of cards. |
| **Expansions planned from day one** | **ArkNova** | MW integration shows how to add map packs, new card types, and mechanics without architectural disruption. |
| **Drafting at game start** | **Agricola** | Multiple draft modes (Living Hand, 7-of-10, simultaneous, async, snake). The most complete drafting system. |
| **Dual-purpose cards (e.g., cards are also workers)** | **Arnak** | The hybrid deck-builder + worker placement pattern where cards serve dual roles. |
| **Multi-track victory point progression** | **ArkNova** | Appeal + Reputation + Conservation + Final Scoring cards with dynamic scoring deltas. |
| **A game with very few states (< 20)** | **Arnak** | 30 states is the most approachable reference for learning BGA state machine basics. |
| **A need for modern client architecture** | **Earth** | The Manager pattern + SCSS modules + JSON config files is the most modern approach. |
| **Hidden information (private hand, hidden scoring)** | **ArkNova or Earth** | Dual notification pattern (public message + private card data). Earth's private state machine handles hidden information naturally. |
| **Solo mode** | **Agricola** | Full campaign system with persistence across sessions, solo-specific action cards, and AI-like mechanics. |
| **Rich tooltips on cards** | **Arnak** | `tooltips.js` is the most complete and well-organized tooltip system. |
| **Understanding BGA fundamentals** | **Arnak** | Simplest architecture, straightforward state machine, no custom Engine, single developer. Best starting point. |
| **Notification efficiency / payload size** | **ArkNova** | The `$listeners` delta system is the most efficient — only changed values are sent. |
| **Reconnection reliability** | **Agricola** | `refreshUI` + `refreshHand` + gamelog packet cancellation is the gold standard. |
| **Debugging / testing** | **Agricola** | Seed loading, bug report infrastructure, debug states, `bug-triage.md`. Most mature debugging ecosystem. |
