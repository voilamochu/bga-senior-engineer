# BGA Senior Engineer — Benchmark Task Corpus

**Repository:** `bga-senior-engineer`
**Source Codebase:** `bga-mercurio` (read-only reference)
**Version:** 1.0
**Date:** 2026-07-30

---

## Purpose

This document defines a corpus of representative engineering tasks extracted from the Mercurio BGA implementation.

Each task is designed to evaluate an AI agent's ability to perform authentic BGA migration and implementation work — not toy examples, but real architectural decisions that arise during modern BGA game development.

Tasks are independent, scoped for a senior engineer, and suitable for objective evaluation.

---

## How to Use This Corpus

1. Select a task from the corpus.
2. Provide the agent with the task description AND the Mercurio codebase as context.
3. Evaluate the agent's output against the defined evaluation criteria.
4. Use the difficulty rating to calibrate expectations.

---

## Task Index

| ID | Category | Title | Difficulty | Effort |
|----|----------|-------|------------|--------|
| ARC-01 | Architecture | Extract Notification Layer from Monolithic Game.php | Hard | 4-6h |
| ARC-02 | Architecture | Extract Manager Classes from Game.php | Hard | 6-8h |
| ARC-03 | Architecture | Implement Generic Board Interaction Framework | Medium | 3-5h |
| MIG-01 | Migration | Migrate Legacy Action Handler to #[PossibleAction] | Medium | 3-5h |
| MIG-02 | Migration | Migrate Dojo Client Module to ES Module | Hard | 4-6h |
| MIG-03 | Migration | Migrate Legacy State Machine to State Classes | Medium | 3-5h |
| DBG-01 | Debugging | Fix Notification-After-State-Transition Ordering | Medium | 2-3h |
| DBG-02 | Debugging | Implement stReplay / restoreReplay for Replay Support | Medium | 3-5h |
| DBG-03 | Debugging | Fix Silent Exception Swallowing in Tech Modifier Dispatch | Medium | 2-3h |
| NOT-01 | Notification | Migrate Deprecated notifyAllPlayers to Modern BGA API | Medium | 2-4h |
| NOT-02 | Notification | Consolidate Duplicated Notification Blocks | Easy | 1-2h |
| SYNC-01 | Synchronization | Fix Reconnect State Inconsistency for Drawing Phase | Medium | 3-5h |
| SYNC-02 | Synchronization | Add Spectator State Projection | Hard | 4-6h |
| CLI-01 | Client | Implement Client-Side Undo UI Feedback | Medium | 2-3h |
| CLI-02 | Client | Extract Client Manager Modules from Monolithic Game.js | Hard | 5-7h |
| STM-01 | State Machine | Implement ResolvePirateRaid Client State | Medium | 2-4h |
| STM-02 | State Machine | Fix Undefined Client State Transitions | Easy | 1-2h |
| PER-01 | Persistence | Normalize State Blob into Structured Tables | Hard | 6-8h |
| PER-02 | Persistence | Implement Game Statistics System | Medium | 2-4h |
| CRV-01 | Code Review | Review Exception Handling Semantics | Easy | 1-2h |
| CRV-02 | Code Review | Review SQL Injection and Type Safety | Medium | 1-2h |
| TST-01 | Testing | Write Server-Side Unit Tests for Tech Modifier Pipeline | Medium | 3-5h |
| TST-02 | Testing | Write Client-Side Notification Handler Tests | Medium | 3-5h |

---

## Architecture Tasks

---

### ARC-01: Extract Notification Layer from Monolithic Game.php

**Category:** Architecture
**Difficulty:** Hard
**Estimated Effort:** 4-6 hours
**Affected Subsystems:** Game.php, Notifications, Server Architecture

**Background:**
`modules/php/Game.php` (9,659 lines) is a monolithic class that mixes game logic, notification construction, action validation, state mutation, and persistence. All 73 notification calls (`notifyAllPlayers` / `notifyPlayer`) are inlined within action handler methods, with payload construction duplicated across multiple call sites. There is no centralized notification class.

**Objective:**
Extract a dedicated notification layer from Game.php. Create a `Notifications` manager class that owns all notification construction and dispatch. Each notification type should be a single static method that accepts typed parameters and returns nothing (sends via dependency injection). The notification manager should be injected into Game.php so that action handlers call `$this->notifications->resourcePurchased($player, $resource, $destination)` instead of manually building and sending notification arrays.

**Evaluation Criteria:**
- All 73 notification calls are removed from Game.php and relocated to the Notifications class
- Each notification type has exactly one method in the Notifications class (no duplication)
- The Notifications class is injectable / accessible from Game.php without global state
- Notification payloads are constructed programmatically, not via copy-pasted arrays
- The Notifications class does NOT contain game logic — only presentation of state changes
- Existing functionality is preserved (all notification types still fire with identical payloads)

**Why This Task Is Representative:**
Notification extraction is the canonical first step in decomposing a monolithic BGA Game.php. Every production BGA game reaches a point where inline notification code must be extracted into a dedicated layer. The task requires architectural judgment about boundaries, dependency injection, and payload construction patterns — exactly the kind of structural refactoring a senior engineer performs regularly.

---

### ARC-02: Extract Manager Classes from Game.php

**Category:** Architecture
**Difficulty:** Hard
**Estimated Effort:** 6-8 hours
**Affected Subsystems:** Game.php, Architecture, Domain Logic

**Background:**
Mercurio's `Game.php` handles concerns from multiple domains: planet cards, technologies, governors, contracts, resources, events, and scoring. All state mutation methods (`applySettleAction`, `applyResearch`, `applyBuildProject`, etc.) live in the same class as action handlers, legal action generators, and framework callbacks. This violates the single-responsibility principle and makes the class impossible to test in isolation.

**Objective:**
Extract domain-specific Manager classes following the BGA Manager pattern. Create at minimum:
- `PlanetManager` — planet card lifecycle (draw, keep, play, settle, outsource)
- `TechManager` — technology research, activation, cooldown, modifiers
- `GovernorManager` — governor draft, install, scoring, pool refresh
- `ResourceManager` — resource buy/sell, market state, lab routing
- `ContractManager` — commission fulfillment, favor track

Each Manager should own the corresponding slice of the serialized state object and expose methods that Game.php calls. Game.php becomes a thin coordinator: validate → delegate to manager → save state → notify.

**Evaluation Criteria:**
- At least 4 domain-specific Manager classes are extracted
- Each Manager owns a well-defined slice of state, with clear getter/setter boundaries
- Game.php action handlers are reduced to validation + single delegation calls
- Manager classes do NOT call notification methods directly (they return state diffs or emit events)
- Managers are testable in isolation (no dependency on Game framework methods)
- The existing game behavior is preserved identically

**Why This Task Is Representative:**
Manager extraction is a recurring architectural pattern in BGA game development. Every game starts monolithic and must be decomposed as it grows. This task tests the ability to identify domain boundaries, design clean APIs, and refactor without changing behavior — a core senior engineer skill.

---

### ARC-03: Implement Generic Board Interaction Framework

**Category:** Architecture
**Difficulty:** Medium
**Estimated Effort:** 3-5 hours
**Affected Subsystems:** Game.js, Client Architecture, UI Framework

**Background:**
Mercurio's client has a partially-implemented Board Interaction Framework in `modules/js/Game.js` (lines 31-235). The `InteractionMode` system exists with a `_modeRegistry` singleton, but currently only `BUY_RESOURCE` is implemented. Actions like `BEAM`, `TAP`, and `SELL_RESOURCE` require board interaction (clicking planets to select source/destination pairs) but handle it directly in their respective state classes, bypassing the generic framework.

The framework's architecture supports `enterInteractionMode()`, `discoverLegalTargets()`, `applyTargetHighlighting()`, and `_clearInteractionMode()`. The legal targets methods for BEAM and TAP already exist on the server side (`getBeamActions`, `getTapActions`).

**Objective:**
Complete the Generic Board Interaction Framework by registering BEAM, TAP, and SELL_RESOURCE interaction modes. Each mode should:
1. Register a `discoverLegalTargets` function in `_modeRegistry`
2. Support multi-step selection (select source → filter legal destinations)
3. Cleanly exit on cancel, state transition, or completion

**Evaluation Criteria:**
- BEAM, TAP, and SELL_RESOURCE are registered as interaction modes
- Each mode correctly filters legal targets using server-provided legal action data
- The framework handles multi-step selection (phase transitions within one interaction)
- Cancellation (via cancel button or state change) cleans up all highlighting and event listeners
- No duplicate highlighting logic exists in individual state classes
- All existing interaction behavior is preserved

**Why This Task Is Representative:**
Building extensible UI frameworks is a recurring pattern in BGA development as games grow beyond simple button-click actions. This task requires understanding the existing architecture, extending it consistently, and ensuring clean state management — all essential senior engineer responsibilities.

---

## Migration Tasks

---

### MIG-01: Migrate Legacy Action Handler to #[PossibleAction]

**Category:** Migration
**Difficulty:** Medium
**Estimated Effort:** 3-5 hours
**Affected Subsystems:** mercurio.action.php, Game.php, States, Architecture

**Background:**
Mercurio uses a legacy action handler (`mercurio.action.php`) with 29 public action methods. Each method manually calls `setAjaxMode()`, `validateAction()`, `getArg()`, delegates to `$this->game->act*()`, and calls `ajaxResponse()`. The modern BGA scaffold replaces this entirely with `#[PossibleAction]` attributes on methods in state classes under `modules/php/States/`.

The legacy `action.php` file includes a TODO comment explicitly stating it should be migrated (see line 5-10 in `mercurio.action.php`). The `bga_scaffold/` directory contains the modern pattern in `modules/php/States/PlayerTurn.php`.

**Objective:**
Eliminate `mercurio.action.php` by migrating all 29 action methods to `#[PossibleAction]` attributes on the appropriate state classes. Create the `modules/php/States/` directory structure with individual state classes (PlayerTurn, ResolvePendingObligation, etc.). Each state class should declare `#[PossibleAction]` attributes that link client actions to handler methods in Game.php.

**Evaluation Criteria:**
- `mercurio.action.php` is empty or removed (actions now handled by the framework)
- State classes exist in `modules/php/States/` for all game states
- Each action has the correct `#[PossibleAction]` attribute on the correct state class
- Argument extraction uses typed method parameters (not `$this->getArg()`)
- Action authorization is enforced by state membership (an action is only available in its declaring state)
- All 29 existing actions continue to function identically

**Why This Task Is Representative:**
The legacy-to-modern action handler migration is the single most common migration task in BGA development. The BGA framework has shifted from `action.php` to attribute-based routing, and virtually every legacy game must undergo this migration. The task requires understanding how the framework dispatches actions, how authorization works, and how arguments are typed.

---

### MIG-02: Migrate Dojo Client Module to ES Module

**Category:** Migration
**Difficulty:** Hard
**Estimated Effort:** 4-6 hours
**Affected Subsystems:** mercurio.js, modules/js/Game.js, Client Architecture

**Background:**
The Mercurio client uses a legacy Dojo module pattern (`define(... declare('bgagame.mercurio', ...)`) in `modules/js/Game.js`. The root `mercurio.js` is a legacy bridge file that loads the Dojo module as a side effect. The modern BGA scaffold uses an ES module pattern (`export class Game`) with `import`/`export` syntax, no Dojo dependency, and no bridge file.

The Dojo pattern uses `declare('bgagame.mercurio', ebg.core.gamegui, { ... })` with `dojo/_base/declare` and `ebg/core/gamegui` dependencies. The module object is registered as a global and accessed via `bgagame.mercurio`. All 6,061 lines of client logic are in a single class.

**Objective:**
Convert `modules/js/Game.js` from Dojo `declare(...)` to ES module `export class Game extends GameGui { ... }`. Remove the legacy root `mercurio.js` bridge. Update any dependent files. The ES module should:
- Use `import` instead of `define()` dependencies
- Export the Game class as a named export
- Remove all Dojo-specific patterns (`dojo/_base/declare`, `declare()`, `ebg.core.gamegui`)
- Use modern JavaScript syntax throughout

**Evaluation Criteria:**
- `modules/js/Game.js` uses `export class Game` syntax (no Dojo)
- The root `mercurio.js` bridge is removed
- No Dojo dependencies remain (`dojo/_base/declare`, `dojo/dom`, etc.)
- The game loads and runs correctly in the modern BGA framework
- All state classes, notification handlers, and UI renderers work identically
- No regressions in client-side behavior

**Why This Task Is Representative:**
The Dojo-to-ESM migration is a framework-mandated modernization that every BGA game built before 2025 must undergo. It tests deep understanding of the BGA client framework, module loading, and the ability to make large-scale mechanical changes without breaking functionality.

---

### MIG-03: Migrate Legacy State Machine to State Classes

**Category:** Migration
**Difficulty:** Medium
**Estimated Effort:** 3-5 hours
**Affected Subsystems:** states.inc.php, Game.php, material.inc.php, Architecture

**Background:**
Mercurio defines its state machine in the legacy `states.inc.php` file (161 lines) with 8 game states. The modern BGA scaffold uses individual PHP classes in `modules/php/States/` with `#[PossibleAction]` attributes. The `states.inc.php` file references client states (IDs 100-106) that are not fully defined (KNOWN_ISSUES BV-002). The `material.inc.php` file defines duplicate PHP constants for state IDs.

The legacy file format is an associative array `$machinestates` keyed by state ID with `name`, `description`, `type`, `action`, `transitions`, and `possibleactions` sub-keys.

**Objective:**
Replace `states.inc.php` with individual state classes in `modules/php/States/`. Each state class should extend the BGA framework's state base class and define:
- State name and type via class metadata or attributes
- Action handler methods decorated with `#[PossibleAction]`
- Transition definitions to next states
- The `arg*` method returning state-specific arguments

**Evaluation Criteria:**
- `states.inc.php` is replaced by state classes in `modules/php/States/`
- Each game state (gameSetup, playerTurn, resolvePendingObligation, endTurn, finalScoring, gameEnd) has a corresponding class
- Client overlay states (clientChooseSector, clientChooseKeep, etc.) are properly defined
- All transitions between states are preserved
- `material.inc.php` constants are replaced by class-based or enum-based references
- State-specific `arg*` methods are moved into the corresponding state class

**Why This Task Is Representative:**
State machine migration from `states.inc.php` to State classes is a framework-mandated migration path. It tests understanding of the BGA state lifecycle, transition semantics, and how state classes integrate with `#[PossibleAction]`. Every legacy BGA game must complete this migration.

---

## Debugging Tasks

---

### DBG-01: Fix Notification-After-State-Transition Ordering

**Category:** Debugging
**Difficulty:** Medium
**Estimated Effort:** 2-3 hours
**Affected Subsystems:** Game.php, Notifications, Synchronization, Reconnect

**Background:**
At least 4 action handlers in `Game.php` call `saveState()` followed by `gamestate->nextState()` BEFORE sending notifications:
- `actBuyResource` (lines 2573-2611)
- `actResearchTech` / `applyResearch` (lines 4315-4346)
- `applyBeam` (lines 4609-4682)
- `actUseProject` (lines 7705-7722)

When a player reconnects after the state transition but before the notification is processed, they never receive the notification payload. On reconnect, the client fetches `getAllDatas()` which returns the current state (post-transition), but the notification payload (containing `playerMoney`, `market`, `actionsTaken`, etc.) was never applied client-side. The result is a desynchronized client — the board state may be correct from `getAllDatas`, but transitional information (animations, intermediate state) is lost.

**Objective:**
Fix the notification ordering in all affected action handlers so that notifications are sent BEFORE `nextState()` is called. Ensure that the state is saved before notifications (so that reconnecting players during notification processing see correct state), but the state transition occurs only after all notifications are dispatched. Verify that no other action handlers in Game.php have the same ordering issue.

**Evaluation Criteria:**
- All notification-sending code executes before `gamestate->nextState()` in every action handler
- State is saved before notifications (so reconnecting players receive correct state from `getAllDatas`)
- No regression in game behavior for non-reconnect scenarios
- A systematic audit of all notification sites is performed (not just the 4 known instances)
- The fix includes a rationale comment explaining the ordering requirement

**Why This Task Is Representative:**
Notification ordering bugs are among the most common and insidious issues in BGA development. They only manifest during reconnection, making them hard to reproduce and diagnose. This task tests systematic debugging skills, understanding of the BGA notification lifecycle, and the ability to reason about asynchronous distributed state.

---

### DBG-02: Implement stReplay / restoreReplay for Replay Support

**Category:** Debugging
**Difficulty:** Medium
**Estimated Effort:** 3-5 hours
**Affected Subsystems:** Game.php, Replay, State Machine, Persistence

**Background:**
Mercurio has no replay support. The BGA framework requires specific methods for replay mode to function:
- `stReplay` — a state handler that restores the game state at a given move
- `restoreGame()` or similar — reverses state to a particular point in the action log

The codebase uses `bga_rand()` for seeded shuffles and explicitly documents "replay consistency" considerations (Game.php lines 935, 1290), but no replay mechanism exists. The `mercurio_action_log` table records actions with `log_id`, `game_id`, `move_id`, `player_id`, `action`, and `timestamp`, providing the raw data for replay. However, because the entire game state is stored as a single serialized blob, there is no move-level snapshot from which to reconstruct intermediate states.

**Objective:**
Implement replay support by:
1. Evaluating whether the serialized state blob approach can support replay, or whether a move-log-based replay mechanism is needed
2. Implementing the `restoreReplay()` / `stReplay()` methods in Game.php
3. Ensuring replay works correctly for all game actions (explore, settle, research, beam, tap, etc.)
4. Testing replay from different points in the action log

**Evaluation Criteria:**
- Replay mode is functional (videos can be generated from completed games)
- The `stReplay` method correctly restores state for any replay step
- Replay works for games of varying lengths and action combinations
- No regression in normal gameplay
- The replay mechanism handles edge cases (mid-action replay, undo during replay, etc.)

**Why This Task Is Representative:**
Replay support is a core BGA feature that every game must implement. It is often deferred until late in development, then rushed. Implementing replay for a game with a monolithic state blob requires architectural thinking about how to decompose state into replayable steps. This tasks tests understanding of the BGA replay lifecycle and state reconstruction patterns.

---

### DBG-03: Fix Silent Exception Swallowing in Tech Modifier Dispatch

**Category:** Debugging
**Difficulty:** Medium
**Estimated Effort:** 2-3 hours
**Affected Subsystems:** Game.php, Technology System, Error Handling

**Background:**
The technology modifier system in Game.php uses two dispatch methods (`applyTechModifier` at line 5535 and `applyEventModifier` at line 5578) that silently swallow ALL exceptions from modifier handlers:

```php
try {
    $currentValue = $this->$handler($tech, $player, $context, $currentValue);
} catch (\Throwable $e) {
    error_log("Mercurio tech modifier warning: handler {$handler} threw " . $e->getMessage());
}
```

If a modifier handler throws, the exception is logged but not re-thrown. The state may be partially corrupted (the handler modified `$player` or `$context` before throwing), and the modifier composition chain continues as if nothing happened. The return value `$currentValue` is unchanged from before the handler ran, silently producing incorrect game logic.

At least 3 modifier handlers are confirmed no-ops (`_applyProjectOnBuildModifier`, `_applyProjectActivateSingleModifier`, `_applyEndgameVpModifier`), suggesting the dispatch system has known issues that were worked around rather than fixed.

**Objective:**
Fix the exception handling in the modifier dispatch pipeline. Each modifier handler should either:
- Complete successfully and return a modified value, or
- Throw an exception that propagates (not silently swallowed)

Additionally, investigate and fix or remove the 3 no-op handlers. Either implement their intended behavior or remove them from the modifier registry.

**Evaluation Criteria:**
- `applyTechModifier` no longer silently swallows exceptions (exceptions propagate)
- `applyEventModifier` no longer silently swallows exceptions
- All modifier handlers are either implemented correctly or removed from the registry
- The 3 no-op handlers are either implemented or removed
- An error in a modifier handler causes a visible failure (not silent data corruption)
- The fix includes a test or verification that modifier composition works end-to-end

**Why This Task Is Representative:**
Silent exception swallowing is a recurring anti-pattern in game development. It creates data corruption bugs that are extremely difficult to diagnose because no error surfaces at the point of failure. This task tests the ability to trace through complex dispatch chains, identify error-handling deficiencies, and fix them without breaking existing functionality.

---

## Notification Tasks

---

### NOT-01: Migrate Deprecated notifyAllPlayers to Modern BGA API

**Category:** Notification
**Difficulty:** Medium
**Estimated Effort:** 2-4 hours
**Affected Subsystems:** Game.php, Notifications, Framework Migration

**Background:**
Game.php uses the deprecated `$this->notifyAllPlayers()` / `$this->notifyPlayer()` API in 73 locations. The modern BGA framework provides `$this->bga->notify->all()` and `$this->bga->notify->player()` as replacements. While the deprecated API still works in the current framework version, it is explicitly non-compliant with the project's engineering standards (Standard 5.1-5.3) and will break on future framework upgrades.

The modern API has a different signature:
- Old: `$this->notifyAllPlayers($type, $message, $payload)`
- New: `$this->bga->notify->all($payload, $type)` or `$this->bga->notify->all($payload, $type, $message)`

**Objective:**
Replace all 73 deprecated notification calls with the modern `$this->bga->notify->all()` / `$this->bga->notify->player()` API. Ensure payload structure and message strings are preserved. Do NOT extract notifications into a separate class (that is ARC-01); this task is purely an API migration.

**Evaluation Criteria:**
- Zero calls to deprecated `$this->notifyAllPlayers()` / `$this->notifyPlayer()` remain in Game.php
- All 38 notification types use the modern API
- Payload structure is identical before and after migration
- Message strings (for client-side display) are preserved
- All notification types continue to function correctly
- No behavioral changes beyond the API call

**Why This Task Is Representative:**
API migration is a constant reality in framework-dependent development. BGA's notification API changed between framework versions, and every legacy game must eventually migrate. This task tests systematic replacement skills and attention to detail across many call sites.

---

### NOT-02: Consolidate Duplicated Notification Blocks

**Category:** Notification
**Difficulty:** Easy
**Estimated Effort:** 1-2 hours
**Affected Subsystems:** Game.php, Notifications, Maintainability

**Background:**
Several notification patterns are duplicated across multiple locations in Game.php:

1. `labOutputActivated` notification is sent from 4 separate locations: `stPlayerTurn` (line 161), `actBuyResource` (line 2622), `applyBeam` (line 4708), and `applyTap` (line 5006). Each block constructs an identical payload with different variable names.

2. Market milestone switch statements (positions 2, 3, 4) are duplicated identically in `actBuyResource` (lines 2640-2661) and `actSellResource` (lines 2792-2813).

3. Synergy milestone switch statements are duplicated in `applyBeam` (lines 4699-4730) and `applyTap` (lines 4991-5022).

4. Three near-identical `cardKept` notification blocks exist in `actChooseKeep` (lines 2075-2088, 2132-2143, 2145-2156).

**Objective:**
Extract all duplicated notification blocks into private helper methods. Each unique notification type should have exactly ONE helper method that constructs and sends the notification. Action handlers call the helper method instead of inlining the notification construction.

**Evaluation Criteria:**
- `labOutputActivated` is sent from exactly one helper method (called from all 4 locations)
- Market milestone notification is sent from exactly one helper method (called from buy and sell)
- Synergy milestone notification is sent from exactly one helper method (called from beam and tap)
- `cardKept` is sent from exactly one helper method (called from all 3 paths in actChooseKeep)
- All notification payloads are identical to the current behavior
- No duplicated notification construction code remains

**Why This Task Is Representative:**
Notification duplication is a common code smell in BGA games because notification construction is often added incrementally. Consolidation improves maintainability and reduces the risk of inconsistent payloads. This task tests the ability to identify and extract duplicated patterns — a fundamental refactoring skill.

---

## Synchronization Tasks

---

### SYNC-01: Fix Reconnect State Inconsistency for Drawing Phase

**Category:** Synchronization
**Difficulty:** Medium
**Estimated Effort:** 3-5 hours
**Affected Subsystems:** Game.php, Game.js, Synchronization, Reconnect

**Background:**
During the Explore action, a player draws cards and enters the `clientChooseKeep` state to select which cards to keep. The `drawingState` (containing drawn cards) is stored in the game state and sent to the drawing player via `getAllDatas()` only when `drawingState->role === viewerRole` (line 1421-1422).

If a player reconnects while in the `clientChooseKeep` state, `getAllDatas()` correctly returns the `drawingState` for that player. However, the legal action data returned by `argChooseKeep()` and the notification handler for `cardKept` rely on the client having received the initial `drawingState` during setup. If notifications were sent in the wrong order (see DBG-01), the client may be in an inconsistent state on reconnect — the `drawingState` is correct from `getAllDatas`, but the UI state class may not properly initialize from it.

Additionally, spectator reconnection during a drawing phase has no mechanism to show even the count of drawn cards.

**Objective:**
Implement reconnect safety for the drawing phase. Ensure that:
1. On reconnect during `clientChooseKeep`, the client correctly initializes the keep-selection UI from `gamedatas` (not from stale notification cache)
2. The `clientChooseKeep` state class correctly handles the case where `args` from `onEnteringState` is provided by the initial transition vs. reconstructed from `getAllDatas`
3. Drawing state is properly cleaned up when the phase ends (preventing stale state on re-reconnect)
4. The reload-safe pattern is documented (or updated) for the drawing phase

**Evaluation Criteria:**
- Reconnecting during `clientChooseKeep` correctly shows the draw-selection UI
- Reconnecting during `clientChooseKeep` shows the correct set of drawn cards
- Completing the keep selection after reconnect works correctly
- Spectators reconnecting during drawing show appropriate state (at minimum, a pending-draw indicator)
- The fix is verified against the reload-safe state pattern documented in `ops/04-patterns/reload-safe-state.md`

**Why This Task Is Representative:**
Reconnect bugs during multi-step player flows are among the hardest to diagnose in BGA development. They require understanding both server-side state reconstruction and client-side UI initialization. The drawing phase is a particularly tricky case because it involves transient state (drawn cards that haven't been committed yet). This task tests the ability to trace end-to-end state flow across a reconnection boundary.

---

### SYNC-02: Add Spectator State Projection

**Category:** Synchronization
**Difficulty:** Hard
**Estimated Effort:** 4-6 hours
**Affected Subsystems:** Game.php, Game.js, Spectator Mode, Synchronization

**Background:**
Spectator mode in Mercurio is currently broken. Investigation notes (in `.ai/bga-runtime-notes.md`) confirm that spectator requests fail with "Game not found" before `getAllDatas()` is ever entered. The `getAllDatas()` signature has been updated to accept nullable `?int $currentPlayerId = null`, but the framework-level failure persists. The issue has been deferred and documented in `docs/KNOWN_ISSUES.md`.

Beyond the framework-level breakage, Mercurio has no spectator-specific state projection. When `getAllDatas()` receives `null` as `$currentPlayerId`, the method currently treats it the same as any other request. Spectators see all player boards (open-information game), but no spectator-specific UI enhancements exist.

**Objective:**
Fix spectator mode by:
1. Diagnosing why spectator requests fail at the framework level (before `getAllDatas()`)
2. Implementing spectator-specific state projection in `getAllDatas()` (e.g., adding a `isSpectator: true` flag, showing appropriate UI state)
3. Ensuring the client handles `viewerRole = null` gracefully
4. Testing spectator flow: join as spectator, refresh, observe multiple turns

**Evaluation Criteria:**
- Spectators can successfully load and view a game in progress
- Spectator UI updates correctly as the game progresses
- State projection for spectators handles the drawing phase (no private card data leaked)
- Refreshing as a spectator correctly restores the spectator view
- The fix addresses both the framework-level and application-level issues

**Why This Task Is Representative:**
Spectator mode is a fundamental BGA feature that interacts with every part of the stack: framework routing, state serialization, notification dispatch, and client rendering. Fixing it requires deep system-level debugging across PHP and JS layers. This task represents the kind of cross-cutting debugging that senior engineers are expected to handle independently.

---

## Client Tasks

---

### CLI-01: Implement Client-Side Undo UI Feedback

**Category:** Client
**Difficulty:** Medium
**Estimated Effort:** 2-3 hours
**Affected Subsystems:** Game.js, Client UI, Undo System

**Background:**
Mercurio supports two undo operations: `actUndoSettle()` and `actUndoResearch()`. The server-side implementation validates undo legality and performs the state rollback. However, the client provides no visual feedback when undo is available or in progress:

- The undo action buttons appear in the action bar (via `getUndoSettleActions` / `getUndoResearchActions`) but are not visually distinct from other actions
- There is no animation or transition when undo is applied (cards snap to new positions)
- No confirmation dialog for undo (which is standard BGA practice for destructive undo operations)
- After undo completes, the client state is correct but the transition is abrupt

**Objective:**
Implement client-side undo UI feedback:
1. Add a confirmation dialog (BGA standard confirmation) before executing undo
2. Add visual highlighting to the element being undone (the settled card or researched tech)
3. Implement a smooth transition animation during undo (card slides back to hand, tech returns to market)
4. Ensure the undo button is visually distinct from primary actions (e.g., gray background, undo icon)
5. Update the `settleUndone` / `researchUndone` notification handlers to trigger animations

**Evaluation Criteria:**
- Undo action buttons are visually distinct from primary actions
- A confirmation dialog is shown before undo executes
- The affected card/tech animates to its original position during undo
- The animation completes before the board state is fully refreshed
- No regression in undo functionality
- The implementation follows BGA animation best practices (no external animation library)

**Why This Task Is Representative:**
Client-side undo feedback is a user experience requirement that touches both UI rendering and notification handling. It tests the ability to add visual polish to an existing feature without breaking the underlying server-authoritative undo logic. This is representative of the kind of UX enhancement work that comprises a significant portion of BGA development effort.

---

### CLI-02: Extract Client Manager Modules from Monolithic Game.js

**Category:** Client
**Difficulty:** Hard
**Estimated Effort:** 5-7 hours
**Affected Subsystems:** Game.js, Client Architecture, Module Structure

**Background:**
`modules/js/Game.js` is a 6,061-line monolithic Dojo module containing all client-side logic: zone renderers, state classes, notification handlers, interaction framework, setup, and debug utilities. The modern BGA pattern uses separate ES module files for each concern, typically under `modules/js/` with a directory structure like:

```
modules/js/
  Game.js              (main entry point, thin coordinator)
  PlanetManager.js     (planet card rendering + state)
  TechManager.js       (technology rendering + activation)
  GovernorManager.js   (governor rendering + draft)
  ResourceManager.js   (resource market + buy/sell rendering)
  ContractManager.js   (commission rendering + fulfillment)
  NotificationManager.js (notification handler registration)
  InteractionFramework.js (board interaction modes)
```

**Objective:**
Extract client-side manager modules from the monolithic Game.js. Each manager should:
- Own a specific zone of the game board (planets, techs, governors, resources, contracts)
- Export render functions and state update methods
- Be importable by Game.js and other managers
- Be independently testable (no undocumented dependencies on Game.js internals)
- Follow the modern ES module pattern

**Evaluation Criteria:**
- At least 4 manager modules are extracted from Game.js
- Game.js imports managers and delegates zone-specific work to them
- Each manager is a clean ES module (no Dojo `define`, no implicit globals)
- Each manager has a well-defined API surface (documented exports)
- Managers are independently importable and have no circular dependencies
- Existing behavior is preserved identically
- Game.js is reduced from 6,061 lines to approximately 2,500 lines (thinner coordinator)

**Why This Task Is Representative:**
Client-side modularization is a common architectural improvement as BGA games grow. A monolithic Game.js becomes difficult to maintain, test, and extend. Extracting manager modules is the client-side equivalent of ARC-02 (server-side Manager extraction). This task tests module design, dependency management, and the ability to decompose a large file into a clean module hierarchy.

---

## State Machine Tasks

---

### STM-01: Implement ResolvePirateRaid Client State

**Category:** State Machine
**Difficulty:** Medium
**Estimated Effort:** 2-4 hours
**Affected Subsystems:** Game.php, Game.js, State Machine, Pirate Raid Mechanic

**Background:**
Mercurio has a pirate raid mechanic where during end-of-production events, pirates may raid planets, forcing the owner to disable an output. The state machine defines a `clientResolvePirateRaid` state (ID 103) in `states.inc.php`, and the server-side flows are implemented:
- `stResolvePendingObligation()` routes to `clientResolvePirateRaid` when `obligationKind === 'pirateRaid'`
- `argResolvePirateRaid()` is called but currently returns incomplete args
- `actResolvePirateRaid()` handles the player's choice
- `getPirateRaidActions()` returns legal raid resolutions

However, the client-side state class `ResolvePirateRaidState` exists in Game.js (line 1684) but is incomplete. The `onEnteringState` method does not properly set up the raid resolution UI, and `onUpdateActionButtons` does not generate the correct action buttons. The state is registered in the constructor (line 1976) but the class implementation is a stub.

**Objective:**
Complete the `ResolvePirateRaidState` client implementation:
1. `onEnteringState`: Highlight affected outputs, show raid flash animation, display raid details
2. `onLeavingState`: Clean up highlighting and animation classes
3. `onUpdateActionButtons`: Show the affected planet, list affected outputs, provide resolution options
4. Wire up the existing CSS animation (`mc-pirate-raid-flash`) to the affected output elements
5. Ensure the state handles the case where the player refreshes during resolution (reload safety)

**Evaluation Criteria:**
- The pirate raid resolution UI is fully functional
- Affected outputs are highlighted when entering the state
- The raid flash animation plays on affected elements
- Players can select and disable an output to resolve the raid
- The state correctly cleans up on leave or cancel
- Reloading during pirate raid resolution correctly restores the state
- The `argResolvePirateRaid()` server method returns complete args for the client

**Why This Task Is Representative:**
Client state implementation is a recurring task in BGA development. The pirate raid state is representative because it involves highlighting game elements, handling player choices, and coordinating with server-side state. Many states are initially stubbed and must be completed later — this is how BGA development often progresses.

---

### STM-02: Fix Undefined Client State Transitions

**Category:** State Machine
**Difficulty:** Easy
**Estimated Effort:** 1-2 hours
**Affected Subsystems:** states.inc.php, State Machine, KNOWN_ISSUES

**Background:**
KNOWN_ISSUES BV-002 documents that `states.inc.php` defines transitions to client states (IDs 100-106) from the `resolvePendingObligation` state (ID 15), but not all of these state IDs have entries in the `$machinestates` array. Specifically:
- `clientChooseSector` (100) — defined
- `clientChooseKeep` (101) — defined
- `clientChooseClaimSwap` (102) — defined
- `clientResolvePirateRaid` (103) — defined
- `clientResolveInteraction` (104) — defined
- `clientResolveGovernorDraft` (105) — defined
- `clientResolvePlanetInputs` (106) — **NOT defined** in `$machinestates`

Additionally, the transition conditions in `stResolvePendingObligation()` (lines 180-235) reference obligation kinds that may not all map to implemented client states. The `planetInputs` obligation kind is handled in code but `clientResolvePlanetInputs` is missing from the state machine definition.

**Objective:**
Fix the state machine definition by:
1. Adding the missing `clientResolvePlanetInputs` state (106) to `$machinestates` with correct properties
2. Verifying all obligation kinds in `stResolvePendingObligation()` have corresponding state definitions
3. Ensuring all state transitions are correctly wired
4. Removing any dead transitions to states that no longer exist

**Evaluation Criteria:**
- All 7 client states (100-106) are defined in the state machine
- Each state has correct type, action, and transitions
- The `planetInputs` obligation kind correctly routes to `clientResolvePlanetInputs`
- No dead or orphaned state transitions remain
- The KNOWN_ISSUES entry BV-002 is resolved

**Why This Task Is Representative:**
State machine inconsistencies are common during active development. As new mechanics are added, the state machine grows organically and can fall out of sync. This task tests the ability to audit a state machine definition against the code that uses it, identify gaps, and fix them systematically.

---

## Persistence Tasks

---

### PER-01: Normalize State Blob into Structured Tables

**Category:** Persistence
**Difficulty:** Hard
**Estimated Effort:** 6-8 hours
**Affected Subsystems:** dbmodel.sql, Game.php, Persistence Architecture, Migration

**Background:**
Mercurio uses a monolithic serialized state blob stored in the `mercurio_state` table. The `loadState()` / `saveState()` pattern (lines 9643-9658) serializes and deserializes the entire game state on every action:

```php
private function loadState() {
    return json_decode(json_encode($this->globals->get('serializedState', [])));
}
```

This approach has several problems:
- Every action serializes and deserializes the entire game state (O(n) per operation)
- JSON round-trip corrupts types (`stdClass` vs array, integer vs string)
- No SQL queries can be performed on game state (it's all in a JSON blob)
- Partial updates require loading, modifying, and saving the entire blob
- No concurrency safety for concurrent modifications
- `loadState()` double-encodes (encode then decode) for unclear reasons

**Objective:**
Design and implement a normalized database schema for Mercurio's game state. Replace the monolithic blob with structured tables for:
- Players (hand, resources, techs, lab state, track positions)
- Common sectors (planet slots, governor slots)
- Decks and discard piles
- Resource market and tech market
- Governor pool and bag
- Turn state and obligations

Implement CRUD operations for each table and migrate the serialized state logic to use SQL queries.

**Evaluation Criteria:**
- The `mercurio_state` blob table is replaced by normalized tables in `dbmodel.sql`
- All game operations work without loading the full state blob
- `loadState()` / `saveState()` are removed (replaced by targeted queries)
- The normalization supports at least third normal form (no JSON blobs in relational tables)
- The migration handles existing games (if any) or is verified with test data
- No regression in game behavior

**Why This Task Is Representative:**
Normalization of monolithic state is one of the most challenging architectural migrations in BGA development. It requires deep understanding of the game's data model, careful schema design, and extensive refactoring of all state access patterns. This tasks represents a real architectural decision that senior engineers must make when a game outgrows its initial simple persistence strategy.

---

### PER-02: Implement Game Statistics System

**Category:** Persistence
**Difficulty:** Medium
**Estimated Effort:** 2-4 hours
**Affected Subsystems:** Game.php, stats.jsonc, Persistence, Framework Configuration

**Background:**
Mercurio currently initializes only a single player stat (`vp`) in `setupNewGame` (line 102). The `stats.jsonc` file in the scaffold is entirely commented out — no active stat definitions exist. The BGA framework requires stats to be defined in `stats.jsonc` (or `stats.json`) and initialized in `setupNewGame` for them to function correctly.

A complete BGA game should track standard gameplay statistics:
- Turns played (per player)
- Actions performed (explore, settle, research, etc.)
- Resources bought/sold
- Technologies researched and activated
- Governors drafted and installed
- Contracts fulfilled
- End-game scoring breakdown

**Objective:**
Design and implement a complete statistics system for Mercurio:
1. Define table and player stats in `stats.jsonc`
2. Initialize all stats in `setupNewGame()`
3. Update stats in each action handler (explore counter, settle counter, etc.)
4. Ensure stats are correctly stored and retrieved by the BGA framework
5. Verify stats display on the game result screen

**Evaluation Criteria:**
- `stats.jsonc` contains table and player stat definitions for at least 10 meaningful metrics
- All defined stats are initialized in `setupNewGame()`
- Stats are updated at the correct points in each action handler
- Stats correctly track per-player and per-game aggregates
- Stats display correctly on the BGA game results page
- No regressions from the stat-update code additions

**Why This Task Is Representative:**
Game statistics are a standard BGA requirement that is often deferred. Implementing them requires understanding the BGA stats API, the game's action flow, and the framework's data model. This task tests the ability to add cross-cutting instrumentation without disrupting existing logic.

---

## Code Review Tasks

---

### CRV-01: Review Exception Handling Semantics

**Category:** Code Review
**Difficulty:** Easy
**Estimated Effort:** 1-2 hours
**Affected Subsystems:** Game.php, Error Handling, BGA Best Practices

**Background:**
The project's engineering standards (Standard 22.1-22.5) mandate specific exception types for specific situations:
- `UserException` for player-facing validation errors (e.g., "You cannot do that")
- `SystemException` for internal logic errors (e.g., "Invalid state in modifier dispatch")
- `VisibleSystemException` only for debug-visible errors (intended for development)

However, Game.php uses `VisibleSystemException` in at least 20 locations where `UserException` is semantically correct (e.g., lines 1998, 2016, 2221, 3094, 3104). The distinction matters: `VisibleSystemException` is visible to the player AND creates a system log entry, while `UserException` is player-visible only with no system log noise. Using `VisibleSystemException` for routine validation errors clutters production logs with expected error conditions.

**Objective:**
Perform a systematic code review of all exception throw sites in Game.php. For each one, determine whether the correct exception type is used. Fix any misclassified exceptions. The review should categorize each throw as:
- Correct (`UserException` for player validation, `SystemException` for internal errors)
- Misclassified (wrong exception type used)
- Ambiguous (based on code analysis, not just grep patterns)

**Evaluation Criteria:**
- Every `throw` statement in Game.php is reviewed and categorized
- All misclassified exceptions are corrected to the correct type
- The review is documented with a summary of findings (not just fixed in code)
- Ambiguous cases are noted with recommendations
- Zero uses of `VisibleSystemException` for validation errors that are semantically `UserException`

**Why This Task Is Representative:**
Exception handling semantics are a common point of confusion in BGA development. The framework provides distinct exception types with distinct behaviors, and using the wrong type has concrete consequences (log noise, broken game flow). This tasks tests attention to detail and understanding of the BGA error handling contract.

---

### CRV-02: Review SQL Injection and Type Safety

**Category:** Code Review
**Difficulty:** Medium
**Estimated Effort:** 1-2 hours
**Affected Subsystems:** Game.php, Persistence, Security, Type Safety

**Background:**
Game.php contains direct SQL queries with interpolated variables:

```php
// Line 283 (stFinalScoring):
$this->DbQuery("UPDATE player SET player_score = {$score['totalVP']}, player_score_aux = {$score['moneyRemainder']} WHERE player_id = $playerId");

// Line 9655 (saveState):
$this->DbQuery("UPDATE player SET player_score_aux = $money WHERE player_id = $playerId");
```

While some interpolated values are cast to `(int)`, others are not. Additionally, the codebase lacks PHP type hints on action handler parameters and many private methods, relying on manual `(int)` casts that can silently convert non-numeric values to 0.

**Objective:**
Perform a security-focused code review of all database interactions and parameter handling in Game.php:
1. Audit all `DbQuery` calls for SQL injection risk (variable interpolation, lack of parameterization)
2. Audit all action handler parameters for type safety
3. Audit all `(int)` casts that may mask bugs
4. Fix all identified vulnerabilities and type-safety issues

**Evaluation Criteria:**
- All `DbQuery` calls use parameterized queries or explicit `intval()` / `sprintf('%d')` formatting
- No raw `$variable` interpolation exists in SQL strings
- All action handler parameters have proper type hints (not just `(int)` casts in the body)
- The review identifies and fixes all SQL injection vulnerabilities
- The review is documented with before/after examples for each fix

**Why This Task Is Representative:**
Security reviews are a standard engineering responsibility. SQL injection in BGA games is rare but consequential. Type safety issues are common in PHP codebases that evolved without strict typing. This task tests the ability to systematically audit code for security and correctness concerns — a core senior engineer skill.

---

## Testing Tasks

---

### TST-01: Write Server-Side Unit Tests for Tech Modifier Pipeline

**Category:** Testing
**Difficulty:** Medium
**Estimated Effort:** 3-5 hours
**Affected Subsystems:** Game.php, Technology System, Testing Infrastructure

**Background:**
Mercurio has no automated tests. The technology modifier pipeline is a good candidate for server-side unit tests because:
- It has a well-defined interface: input modifiers (handlers modify `$currentValue`)
- It has known edge cases: 40 technologies with 13 passive, 11 active, 11 project, 5 endgame
- It has known no-op handlers (3 that need resolution)
- It has known silent exception swallowing (see DBG-03)
- Modifier composition is tested only via a standalone script (`scripts/test-modifier-composition.php`)

However, Game.php is tightly coupled to the BGA framework, making direct unit testing difficult. The tech modifier system must be extracted or made testable in isolation first.

**Objective:**
Design and implement server-side unit tests for the technology modifier pipeline:
1. Extract the modifier system into a testable form (standalone class or interface with dependency injection for framework dependencies)
2. Write tests for each modifier type:
   - Cost discount modifiers
   - Hand limit modifiers
   - Explore draw modifiers
   - Resource substitution modifiers
   - Auto-satisfaction modifiers
   - Each of the 5 activate_instant techs
3. Test edge cases: multiple modifiers stacking, modifier with no effect, empty tech list
4. Test that exceptions from modifier handlers are NOT silently swallowed

**Evaluation Criteria:**
- Test framework is set up (PHPUnit or similar)
- Tests cover all modifier types (minimum 10 test cases)
- Tests run independently of the BGA framework (no database, no game instance)
- Modifier stacking (multiple modifiers affecting the same value) is tested
- The test for silent exception swallowing verifies that exceptions propagate
- The 3 no-op handlers have test coverage documenting their current behavior
- Tests can be run from the command line with a single command

**Why This Task Is Representative:**
Testing is a critical skill that is often absent from BGA codebases. The tech modifier pipeline is a perfect candidate for unit testing because it has clear inputs, outputs, and edge cases. Setting up test infrastructure for a framework-coupled codebase requires architectural judgment about where to draw abstraction boundaries.

---

### TST-02: Write Client-Side Notification Handler Tests

**Category:** Testing
**Difficulty:** Medium
**Estimated Effort:** 3-5 hours
**Affected Subsystems:** Game.js, Client Architecture, Notification System

**Background:**
Mercurio has 38 notification handlers in Game.js, each following a three-phase pattern:
1. Mutate `this.gamedatas` with notification args
2. Update player-specific state
3. Dispatch targeted renderers

The notification handlers are tightly coupled to the DOM (they call renderers that manipulate DOM elements) and to `this.gamedatas` (which is shared mutable state). This makes them difficult to test in isolation. However, testing notification handlers is critical for ensuring that the client stays in sync with the server.

**Objective:**
Design and implement client-side tests for notification handlers:
1. Create a test harness that provides a mock `gamedatas` object, mock renderers, and mock notification args
2. Write tests for at least 5 notification handlers:
   - `cardKept` — tests hand update, discard buffer update
   - `resourcePurchased` — tests money deduction, market update
   - `techResearched` — tests tech list update, slot availability
   - `beamCompleted` — tests planet state changes, resource transfers
   - `governorInstalled` — tests governor slot update, pool changes
3. Test edge cases: notification for a different player, notification during non-viewer turn, notification with empty payload
4. Verify that `gamedatas` mutation matches expected server state

**Evaluation Criteria:**
- Test harness is set up (Playwright, Jest, or plain JS test runner)
- Tests cover at least 5 notification handlers with multiple scenarios each
- Tests verify that `this.gamedatas` is mutated correctly (not just that renderers are called)
- Tests verify that renderers are called with the correct data
- Tests run independently of the BGA framework (mocked framework dependencies)
- Tests can be run from the command line with a single command

**Why This Task Is Representative:**
Client-side notification testing is a recurring need in BGA development. Notification handlers are the glue between server state and client rendering, and bugs in them cause desynchronization issues that are hard to diagnose in production. This task tests the ability to design test infrastructure for a framework-dependent client module.

---

## Appendix: Task Selection Guide

### By Skill Level

| Skill Level | Recommended Tasks |
|-------------|-------------------|
| Junior Engineer (filter) | NOT-02, STM-02, CRV-01 |
| Mid-Level Engineer | MIG-01, MIG-03, DBG-01, DBG-03, NOT-01, STM-01, PER-02 |
| Senior Engineer | ARC-01, ARC-02, ARC-03, MIG-02, DBG-02, SYNC-01, SYNC-02, CLI-02, PER-01, TST-01, TST-02 |
| Staff Engineer | CLI-01, CRV-02, SYNC-01 + SYNC-02 combined |

### By Coverage Area

| Coverage Area | Tasks |
|---------------|-------|
| Server Architecture | ARC-01, ARC-02 |
| Client Architecture | ARC-03, CLI-01, CLI-02 |
| Framework Migration | MIG-01, MIG-02, MIG-03, NOT-01 |
| Debugging | DBG-01, DBG-02, DBG-03 |
| Synchronization | SYNC-01, SYNC-02 |
| State Machine | STM-01, STM-02 |
| Persistence | PER-01, PER-02 |
| Code Quality | CRV-01, CRV-02, NOT-02 |
| Testing | TST-01, TST-02 |

### Recommended Evaluation Sessions

**Quick Session (2-3 hours):** Pick 2-3 Easy/Medium tasks from different categories.

**Standard Session (4-6 hours):** Pick 3-4 Medium tasks spanning server, client, and debugging.

**Full Session (8+ hours):** Pick 1-2 Hard tasks with their supporting Medium tasks.

**Migration Focus:** MIG-01 + NOT-01 + MIG-03 (migrate actions, notifications, and state machine).

**Architecture Focus:** ARC-01 + ARC-02 + CLI-02 (extract server and client modules).

**Debugging Focus:** DBG-01 + DBG-02 + DBG-03 (fix notification ordering, replay, and error handling).
