# BGA Client UI Architecture — Engineering Standard

**Document purpose:** Define the canonical architecture for large-scale JavaScript clients in Board Game Arena. Explain how professional BGA projects separate rendering, state, widgets, managers, dialogs, animations, and user interaction.

**Applicability:** All new BGA game implementations. Existing projects should use this document when refactoring client-side code, adding complex UI interactions, or scaling beyond a single Game.js file.

**Cross-references:**
- [client-synchronization-architecture.md](./client-synchronization-architecture.md) — notification flow, reconnect, client consistency
- [game-flow-architecture.md](./game-flow-architecture.md) — execution pipeline, thin coordinator principle, client module layout
- [state-machine-architecture.md](./state-machine-architecture.md) — state args, onEnteringState, onUpdateActionButtons
- [domain-architecture.md](./domain-architecture.md) — manager pattern, layering, dependency rules
- [notification-patterns.md](./notification-patterns.md) — notification handlers, public/private patterns, UI refresh
- [reference-project-analysis.md](../foundation/reference-project-analysis.md) — client architecture ratings, Manager patterns
- [bga-developer-handbook.md](../foundation/bga-developer-handbook.md) — bga API, BgaCards, BgaAnimations, Stock

---

## Table of Contents

- [1. UI Architecture Philosophy](#1-ui-architecture-philosophy)
- [2. Overall Client Architecture](#2-overall-client-architecture)
- [3. Responsibilities](#3-responsibilities)
- [4. Game.js](#4-gamejs)
- [5. Manager Pattern](#5-manager-pattern)
- [6. Widget Architecture](#6-widget-architecture)
- [7. Rendering](#7-rendering)
- [8. Action Buttons](#8-action-buttons)
- [9. Dialog Architecture](#9-dialog-architecture)
- [10. Selection Systems](#10-selection-systems)
- [11. State vs UI](#11-state-vs-ui)
- [12. Animation Integration](#12-animation-integration)
- [13. Performance](#13-performance)
- [14. Scaling](#14-scaling)
- [15. Anti-Patterns](#15-anti-patterns)
- [16. Templates](#16-templates)
- [17. Checklists](#17-checklists)

---

## 1. UI Architecture Philosophy

### 1.1 Five Principles

**Principle 1 — Thin Game.js.** Game.js is the coordinator, not the application. It wires together managers, registers notification handlers, and delegates to subsystems. It contains no rendering logic, no DOM manipulation, and no game state beyond references to managers.

```js
// CORRECT — Game.js delegates
setup(gamedatas) {
    this.cardMgr = new CardManager(this);
    this.boardMgr = new BoardManager(this);
    this.playerPanelMgr = new PlayerPanelManager(this);

    this.cardMgr.setup(gamedatas.cards);
    this.boardMgr.setup(gamedatas.board);
    this.playerPanelMgr.setup(gamedatas.players);
}

// WRONG — Game.js does everything
setup(gamedatas) {
    this.cards = gamedatas.cards;
    for (const card of gamedatas.cards) {
        const el = document.createElement('div');
        // ... 50 lines of DOM creation in Game.js
    }
}
```

See [game-flow-architecture.md §3.3](./game-flow-architecture.md#33-the-thin-coordinator-principle).

**Principle 2 — Separation of Concerns.** Every visual subsystem has a dedicated manager. Card rendering belongs in `CardManager`. Board rendering belongs in `BoardManager`. Player panels belong in `PlayerPanelManager`. A manager owns a single section of the DOM and is the only class that modifies it.

**Principle 3 — Server Authority.** The client never contains game rules, never decides outcomes, and never mutates authoritative state. All game state arrives via `getAllDatas()` or notifications. The client is a projection — it renders what the server tells it to render.

**Principle 4 — Declarative Rendering.** Managers should declare what the UI looks like based on state, not imperatively sequence DOM changes. `setup()` creates the initial DOM. Notification handlers update it incrementally.

**Principle 5 — Component Ownership.** Every DOM element has exactly one owner manager. If a card is in the hand, the `HandManager` owns it. If it moves to the board, ownership transfers to `BoardManager`. No two managers should ever modify the same element.

```
HandManager owns:    #hand container, each card element in hand
BoardManager owns:   #board container, each card element on board
PlayerPanelMgr owns: #player-panel-*, score counters, resource icons
DialogManager owns:  #dialog-overlay, dialog content
```

---

## 2. Overall Client Architecture

### 2.1 Layered Architecture

```
┌──────────────────────────────────────────────────────────────┐
│  Game.js                                                      │
│  Thin coordinator — setup, notifications, state handlers     │
├──────────────────────────────────────────────────────────────┤
│  Managers                                                     │
│  Own DOM sections — CardMgr, BoardMgr, PlayerPanelMgr,       │
│  DialogMgr, SelectionMgr, ScoreMgr                           │
├──────────────────────────────────────────────────────────────┤
│  Widgets / Components                                         │
│  Reusable UI elements — Stock, Counter, PlayerTable, Log     │
├──────────────────────────────────────────────────────────────┤
│  DOM                                                          │
│  HTML elements — the rendered game interface                 │
└──────────────────────────────────────────────────────────────┘
```

### 2.2 Architecture Diagram

```
                    ┌──────────────────┐
                    │  BGA Framework   │
                    │  (bga.actions,   │
                    │   bga.notif.,    │
                    │   bga.players)   │
                    └────────┬─────────┘
                             │
                    ┌────────▼─────────┐
                    │    Game.js       │
                    │                  │
                    │  setup()         │
                    │  setupNotif()    │
                    │  onEnteringState │
                    │  onUpdateActBtns │
                    └───┬───┬───┬──────┘
                        │   │   │
              ┌─────────┘   │   └──────────┐
              ▼             ▼              ▼
      ┌────────────┐ ┌────────────┐ ┌────────────┐
      │ CardMgr    │ │ BoardMgr   │ │ PlayerPanel│
      │            │ │            │ │ Mgr        │
      │ - hand     │ │ - tiles    │ │            │
      │ - tableau  │ │ - tokens   │ │ - scores   │
      │ - discard  │ │ - grid     │ │ - names    │
      └──────┬─────┘ └──────┬─────┘ │ - resources│
             │              │       └──────┬─────┘
             ▼              ▼              ▼
      ┌──────────────────────────────────────────┐
      │              Widgets / DOM                │
      │  BgaCards Stocks, Counters, HTML divs    │
      └──────────────────────────────────────────┘
```

### 2.3 Notification → Manager → DOM Flow

```
Notification arrives
       │
       ▼
Game.js (notif_X handler)
       │
       ├── delegates to appropriate Manager
       │
       ▼
Manager (updates state cache, modifies DOM)
       │
       ├── CardMgr.moveCard() → BgaCards stock.moveCard()
       ├── PlayerPanelMgr.updateScore() → Counter.toValue()
       └── BoardMgr.placeTile() → createElement + appendChild
       │
       ▼
DOM updated
```

### 2.4 State Transition → UI Update Flow

```
Framework advances state machine
       │
       ▼
Server sends state-change notification
       │
       ▼
onEnteringState(stateName, args)
       │
       ├── iterate all Managers → manager.onStateChange(state, args)
       │
       ▼
onUpdateActionButtons(stateName, args)
       │
       ├── clear existing buttons
       └── add buttons for current state's possible actions
```

---

## 3. Responsibilities

### 3.1 Component Responsibility Matrix

| Component | Owns | Creates DOM? | Handles Notifications? | Has State Cache? | Knows About Game.js? |
|---|---|---|---|---|---|
| **Game.js** | Coordinator, wiring | No (delegates) | Registers handlers | No (delegates) | N/A |
| **Manager** | DOM section | Yes (via setup) | Yes (via Game.js) | Yes (UI state) | Yes (reference) |
| **Widget** | Reusable element | Yes | No | No (stateless) | No |
| **Counter** | Animated number | Yes | No | Yes (current value) | No |
| **Player Board** | Single player display | Yes | No (via Manager) | Yes (per-player) | No |
| **Dialog** | Temporary overlay | Yes | No | No (ephemeral) | Yes (for callbacks) |
| **Animation Helper** | Animation timeline | No (mutates DOM) | No | No | No |
| **Selection Manager** | Selection state | No (highlights) | No | Yes (selected items) | No |
| **Utility** | Pure functions | No | No | No | No |

### 3.2 What Game.js Must Do

- Create and wire managers in `setup()`
- Register notification handlers in `setupNotifications()`
- Handle `onEnteringState`, `onLeavingState`, `onUpdateActionButtons`
- Provide a `bga.actions.performAction()` bridge for user interactions
- Hold references to all managers (dependency injection)

### 3.3 What Game.js Must NOT Do

- Create or manipulate DOM elements directly
- Contain rendering logic
- Maintain game state caches (delegate to managers)
- Call `bga.actions.performAction()` outside user-initiated events
- Contain business logic or game rules

### 3.4 What a Manager Must Do

- Own a specific DOM section (identified by a container element ID)
- Render initial state in `setup(gamedatas)`
- Update DOM in response to delegated notifications
- Maintain a client-side state cache for its domain
- Handle user interactions (clicks, drags) and call `Game.js` actions
- Provide cleanup in `onLeavingState` or `reset()`

### 3.5 What a Manager Must NOT Do

- Modify DOM outside its owned section
- Call other managers' methods directly (communicate through Game.js)
- Access `bga.actions` directly (receive references via constructor)
- Maintain authoritative game state (only UI cache)

---

## 4. Game.js

### 4.1 The Canonical Game.js Structure

```js
class MyGame {
    constructor() {
        // Managers created here or in setup()
        this.cardMgr = null;
        this.boardMgr = null;
        this.playerPanelMgr = null;
        this.dialogMgr = null;
        this.selectionMgr = null;
    }

    // ── FRAMEWORK LIFECYCLE ──

    setup(gamedatas) {
        // 1. Initialize managers with gamedatas
        this.cardMgr = new CardManager(this, 'player-hand');
        this.cardMgr.setup(gamedatas.cards);

        this.boardMgr = new BoardManager(this, 'game-board');
        this.boardMgr.setup(gamedatas.board);

        this.playerPanelMgr = new PlayerPanelManager(this, 'player-boards');
        this.playerPanelMgr.setup(gamedatas.players);

        this.selectionMgr = new SelectionManager(this);

        // 2. Register notification handlers
        this.setupNotifications();
    }

    setupNotifications() {
        this.bga.notifications.setupPromiseNotifications();
    }

    // ── STATE HANDLERS ──

    onEnteringState(stateName, args) {
        this.cardMgr.onStateChange(stateName, args);
        this.boardMgr.onStateChange(stateName, args);
        this.playerPanelMgr.onStateChange(stateName, args);
    }

    onLeavingState(stateName) {
        this.selectionMgr.clear();
        this.dialogMgr?.close();
    }

    onUpdateActionButtons(stateName, args) {
        // Clear existing buttons
        this.bga.gameui.removeActionButtons();

        // Add buttons for current state's possible actions
        if (args.passPossible) {
            this.bga.gameui.addActionButton(
                'btn_pass',
                _('Pass'),
                () => this.bga.actions.performAction('actPass')
            );
        }
    }

    // ── NOTIFICATION HANDLERS ──

    notif_cardPlayed(notif) {
        this.cardMgr.onCardPlayed(notif.args);
    }

    notif_gainResources(notif) {
        this.playerPanelMgr.onGainResources(notif.args);
    }

    notif_placeTile(notif) {
        this.boardMgr.onPlaceTile(notif.args);
    }

    notif_refreshUI(notif) {
        this.cardMgr.reset(notif.args.datas.cards);
        this.boardMgr.reset(notif.args.datas.board);
        this.playerPanelMgr.reset(notif.args.datas.players);
    }

    notif_refreshHand(notif) {
        if (this.bga.players.isCurrentPlayerSpectator()) return;
        this.cardMgr.refreshHand(notif.args.hand);
    }
}
```

### 4.2 setup()

`setup()` runs once when the page loads and during reconnect. It receives the complete snapshot from `getAllDatas()`. Its responsibilities:

1. **Create managers** — instantiate each manager with a reference to Game.js and a container element ID
2. **Initialize each manager** — pass the relevant subsection of gamedatas to each manager's `setup()` method
3. **Register notification handlers** — call `this.setupNotifications()`

```js
setup(gamedatas) {
    this.scoreMgr = new ScoreManager(this, 'score-container');
    this.scoreMgr.setup(gamedatas.players);

    this.cardMgr = new CardManager(this, 'card-container');
    this.cardMgr.setup(gamedatas.cards, gamedatas.cardDefinitions);

    this.setupNotifications();
}
```

### 4.3 setupNotifications()

Registers notification handlers with the framework:

```js
setupNotifications() {
    this.bga.notifications.setupPromiseNotifications({
        minDuration: 300,       // Minimum time per notification with text
        minDurationNoText: 1,   // Minimum time per notification without text
    });
}
```

Handlers are auto-discovered by naming convention: methods named `notif_X` are automatically registered for notification type `X`. See [notification-patterns.md §2.3](./notification-patterns.md#23-client-side-registration).

### 4.4 onEnteringState()

Called when the state machine enters a new state. Used to update the UI for the new state:

```js
onEnteringState(stateName, args) {
    this.cardMgr.onStateChange(stateName, args);
    this.boardMgr.onStateChange(stateName, args);
    this.playerPanelMgr.onStateChange(stateName, args);
}
```

Each manager updates its internal state and re-renders if needed. The `args` object contains the state's public and private data (see [state-machine-architecture.md §10](./state-machine-architecture.md#10-state-arguments)).

### 4.5 onLeavingState()

Called when leaving a state. Used to clean up state-specific UI:

```js
onLeavingState(stateName) {
    this.selectionMgr.clear();
    this.dialogMgr.close();
    this.cardMgr.clearHighlights();
}
```

### 4.6 onUpdateActionButtons()

Called to determine which action buttons to show. The canonical implementation:

```js
onUpdateActionButtons(stateName, args) {
    this.bga.gameui.removeActionButtons();

    if (!args.playableCards?.length) {
        this.bga.gameui.addActionButton(
            'btn_pass',
            _('Pass'),
            () => this.bga.actions.performAction('actPass')
        );
        return;
    }

    this.bga.gameui.addActionButton(
        'btn_play_card',
        _('Play card'),
        () => {
            const selected = this.selectionMgr.getSelected();
            if (selected) {
                this.bga.actions.performAction('actPlayCard', { cardId: selected.id });
            }
        }
    );
}
```

### 4.7 State Handlers

For complex games, state-specific behavior can be extracted to state handler files:

```
modules/js/
├── Game.js
├── Managers/
└── States/
    ├── PlayerTurn.js      ← handles onEnteringState for playerTurn
    └── ResolveChoice.js   ← handles onEnteringState for resolveChoice
```

```js
// States/PlayerTurn.js
class PlayerTurnHandler {
    constructor(game) {
        this.game = game;
    }

    onEnteringState(args) {
        this.game.cardMgr.setSelectable(args.playableCards);
        this.game.boardMgr.showValidPlacements(args.validPositions);
    }

    onLeavingState() {
        this.game.cardMgr.clearSelectable();
        this.game.boardMgr.clearHighlights();
    }
}
```

This pattern is optional — use it when `onEnteringState` logic exceeds 20 lines per state.

### 4.8 Notification Registration

Notification handler methods are auto-discovered. For each notification type `X`, define a method `notif_X(notif)` on Game.js:

```js
notif_cardPlayed(notif) {
    // Delegate immediately
    this.cardMgr.onCardPlayed(notif.args);
}

notif_gainResources(notif) {
    this.playerPanelMgr.onGainResources(notif.args);
}
```

**Rule:** Notification handlers in Game.js should be one-liners that delegate to a manager. If a handler contains more than 3 lines, extract the logic into the appropriate manager.

### 4.9 Dependency Injection

Managers receive a reference to Game.js and a container ID:

```js
class CardManager {
    constructor(game, containerId) {
        this.game = game;
        this.container = document.getElementById(containerId);
    }
}
```

Managers access the framework through Game.js:

```js
// Manager: send a player action
this.game.bga.actions.performAction('actPlayCard', { cardId });

// Manager: get current player info
this.game.bga.players.getCurrentPlayerId();
```

This keeps managers testable — the `game` object can be mocked.

---

## 5. Manager Pattern

### 5.1 Definition

A Manager is a JavaScript class that owns a section of the DOM, manages a client-side state cache for that section, and handles notifications that affect it.

### 5.2 Manager Inventory by Reference Project

| Project | Managers | Assessment |
|---|---|---|
| **Arnak** | Minimal (no dedicated client managers) | Monolithic Game.js |
| **Agricola** | Client managers exist but not formalized | Medium separation |
| **ArkNova** | Client managers exist but not formalized | Medium separation |
| **Earth** | CardMgr, TableauMgr, DeckMgr, PlayerBoardMgr, PlayerPanelMgr, GainMgr, PaymentMgr, ScoreMgr, LeafTokenMgr, FaunaBoardMgr, GaiaBoardMgr, CardDetailMgr, ObjectiveDetailMgr, HandMgr | Full separation |

Earth's Manager pattern is the canonical reference — it has one manager per UI subsystem. See [reference-project-analysis.md §4.2](./reference-project-analysis.md#42-subsystem-ratings).

### 5.3 Canonical Manager Structure

```js
class CardManager {
    constructor(game, containerId) {
        this.game = game;
        this.container = document.getElementById(containerId);
        this.cards = {};      // Client-side state cache
        this.stocks = {};     // BgaCards stocks or widget references
    }

    // ── SETUP ──

    setup(cardsData, definitions) {
        this.createStocks();
        this.cards = cardsData;
        this.renderAll();
    }

    createStocks() {
        this.handStock = new BgaCards.HandStock(
            this.game.cardsManager,
            document.getElementById('player-hand')
        );
    }

    // ── RENDERING ──

    renderAll() {
        const cards = Object.values(this.cards);
        this.handStock.addCards(
            cards.filter(c => c.location === 'hand')
        );
    }

    // ── NOTIFICATION HANDLERS ──

    onCardPlayed(args) {
        const card = this.cards[args.card_id];
        card.location = 'play';
        this.moveCardElement(args.card_id, 'play');
    }

    onCardDrawn(args) {
        this.cards[args.card_id] = args.card;
        this.handStock.addCard(args.card);
    }

    // ── STATE-SPECIFIC BEHAVIOR ──

    setSelectable(cardIds) {
        for (const id of cardIds) {
            const el = this.getElementById(id);
            el?.classList.add('selectable');
        }
    }

    clearSelectable() {
        this.container.querySelectorAll('.selectable')
            .forEach(el => el.classList.remove('selectable'));
    }

    // ── RESET ──

    reset(cardsData) {
        this.cards = cardsData;
        this.clearStocks();
        this.renderAll();
    }

    // ── HELPERS ──

    getElementById(cardId) {
        return document.getElementById(`card-${cardId}`);
    }

    moveCardElement(cardId, location) {
        const el = this.getElementById(cardId);
        const target = document.getElementById(`${location}-area`);
        if (el && target) {
            target.appendChild(el);
        }
    }
}
```

### 5.4 Manager Lifecycle

```
Game created
  │
  ▼
setup(gamedatas) ──→ Manager.setup(data)
  │                     ├── createStocks()
  │                     ├── init cache
  │                     └── renderAll()
  │
  ▼
Each notification ──→ Manager.onX(args)
  │                     ├── update cache
  │                     └── update DOM
  │
  ▼
onLeavingState ────→ Manager.clearHighlights()
  │
  ▼
reset() ────────────→ Manager.reset(data)  (on refreshUI)
  │                     ├── clear stocks
  │                     ├── rebuild cache
  │                     └── renderAll()
```

### 5.5 Ownership Rules

A Manager owns:
- A container DOM element (its root)
- All child elements within that container
- The client-side state cache for its domain

A Manager does NOT own:
- Other managers' containers
- Global framework state
- Elements that belong to a different domain

### 5.6 Communication Rules

Managers do not call each other. When a manager needs something from another domain, it goes through Game.js:

```js
// IN A MANAGER — CORRECT
class CardManager {
    onCardPlayed(args) {
        // Update own state
        this.moveCardElement(args.card_id, 'play');

        // Notify Game.js to update another manager
        this.game.onCardPlayedEffect(args.card_id);
    }
}

// IN Game.js — mediates
onCardPlayedEffect(cardId) {
    const card = this.cardMgr.getCard(cardId);
    if (card.hasScoreEffect()) {
        this.playerPanelMgr.updateScore(card.getScoreEffect());
    }
}
```

Direct manager-to-manager calls are forbidden — see [domain-architecture.md §4.7](./domain-architecture.md#47-cross-manager-interaction) for the same rule on the server side.

### 5.7 When to Create a New Manager

Create a new manager when:
- A section of the DOM has a clear visual boundary (a panel, a board, a hand)
- A section has 5+ notification handlers that update it
- A section has its own lifecycle (setup, reset, clear)
- A section maintains significant client-side state

Do NOT create a manager when:
- The logic is a single utility function (keep it in a helper)
- The logic is a one-off dialog (use DialogManager instead of a dedicated class)

---

## 6. Widget Architecture

### 6.1 Definition

A Widget is a reusable UI component that encapsulates rendering and behavior for a specific visual element. Unlike Managers, Widgets do not own a DOM section — they are used by Managers to render individual elements.

### 6.2 Widget Types

| Widget | Framework | Purpose |
|---|---|---|
| **BgaCards Stock** | `BgaCards.LineStock`, `HandStock`, `Deck`, etc. | Card display and animation |
| **Counter** | BGA Counter | Animated numeric display |
| **ebg.stock** | Legacy (Dojo) | Element grid display |
| **PlayerTable** | Custom | Player information row |
| **Log** | Framework | Game log display |

### 6.3 BgaCards Widgets

The modern approach for card games. Each stock type serves a specific layout:

```js
// Import
const BgaAnimations = await importEsmLib('bga-animations', '1.x');
const BgaCards = await importEsmLib('bga-cards', '1.x');

// Animation manager (shared)
this.animationManager = new BgaAnimations.Manager({
    animationsActive: () => this.game.bga.gameui.bgaAnimationsActive(),
});

// Card manager (shared)
this.cardsManager = new BgaCards.Manager({
    animationManager: this.animationManager,
    type: 'mygame-card',
    getId: (card) => card.id,
    setupFrontDiv: (card, div) => {
        div.dataset.type = card.type;
        div.dataset.typeArg = card.type_arg;
    },
});

// Stocks
this.handStock = new BgaCards.HandStock(this.cardsManager, handContainer);
this.tableauStock = new BgaCards.LineStock(this.cardsManager, tableauContainer);
this.deckStock = new BgaCards.Deck(this.cardsManager, deckContainer);
```

**Stock operations:**

```js
// Add cards
await this.handStock.addCards(cards);

// Remove cards
await this.handStock.removeCards(cardIds);

// Move between stocks (animated)
await this.handStock.removeCards([card]);
await this.tableauStock.addCards([card]);
// Or use the Manager's moveCard method:
this.cardsManager.moveCard(card, this.handStock, this.tableauStock);
```

See [bga-ai-implementation-reference.md §11](./bga-ai-implementation-reference.md#11-ui-components).

### 6.4 Counter Widget

Counters provide animated numeric display:

```js
// Create
this.scoreCounter = new ebg.counter();
this.scoreCounter.create('player-score-123');

// Update
this.scoreCounter.toValue(42);      // Animated
this.scoreCounter.setValue(42);     // Immediate
```

### 6.5 Legacy Stock (ebg.stock)

For games using the Dojo-based framework:

```js
const [stock] = await importDojoLibs(['ebg/stock']);
this.deck = new ebg.stock();
this.deck.create(this.game.bga.gameui, $('deck-container'), 60, 90);
this.deck.setSelectionMode(1);  // Single selection

// Add items
this.deck.addCardType(1, 'card', 'mygame_cards');
this.deck.addToStockWithId(1, 42);  // type=1, id=42
```

### 6.6 Custom Widgets

For game-specific reusable components:

```js
class PlayerPanelWidget {
    constructor(containerId, playerId, playerData) {
        this.container = document.getElementById(containerId);
        this.playerId = playerId;
        this.resources = playerData.resources;
        this.render();
    }

    render() {
        this.container.innerHTML = `
            <div class="player-panel" id="panel-${this.playerId}">
                <div class="player-name">${this.name}</div>
                <div class="player-resources">
                    ${this.renderResources()}
                </div>
                <div class="player-score">
                    <span class="counter" id="score-${this.playerId}">0</span>
                </div>
            </div>
        `;
    }

    updateResources(resources) {
        this.resources = resources;
        this.container.querySelector('.player-resources')
            .innerHTML = this.renderResources();
    }
}
```

---

## 7. Rendering

### 7.1 Initial Rendering

Initial rendering happens in `setup()`. Each manager creates its DOM structure from the `gamedatas` snapshot:

```js
class BoardManager {
    setup(boardData) {
        this.tiles = boardData.tiles;
        this.container.innerHTML = '';  // Clear
        this.renderGrid();
        this.renderTiles();
    }

    renderGrid() {
        for (let y = 0; y < this.gridSize; y++) {
            for (let x = 0; x < this.gridSize; x++) {
                const cell = document.createElement('div');
                cell.className = 'board-cell';
                cell.dataset.x = x;
                cell.dataset.y = y;
                cell.addEventListener('click', () => this.onCellClick(x, y));
                this.container.appendChild(cell);
            }
        }
    }

    renderTiles() {
        for (const tile of Object.values(this.tiles)) {
            const cell = this.getCell(tile.x, tile.y);
            if (cell) {
                cell.classList.add('occupied', `tile-${tile.type}`);
            }
        }
    }
}
```

### 7.2 Incremental Rendering

Incremental rendering happens in response to notifications. The manager updates only the affected elements:

```js
class BoardManager {
    onPlaceTile(args) {
        // Update cache
        this.tiles[args.tile_id] = { x: args.x, y: args.y, type: args.type };

        // Update DOM — minimal change
        const cell = this.getCell(args.x, args.y);
        cell.classList.add('occupied', `tile-${args.type}`);
    }
}
```

### 7.3 DOM Ownership

Every element belongs to exactly one manager. Elements are identified by predictable IDs:

```
#hand-container       → CardManager
#board-container      → BoardManager
#player-board-{pid}   → PlayerPanelManager
#dialog-overlay       → DialogManager
```

Managers create elements with IDs that match the server's entity IDs:

```js
const el = document.createElement('div');
el.id = `card-${card.id}`;
el.dataset.location = card.location;
```

This enables notification handlers to find elements by ID without DOM traversal:

```js
getElementById(cardId) {
    return document.getElementById(`card-${cardId}`);
}
```

### 7.4 Idempotent Rendering

Rendering must be idempotent — running the same update twice must not duplicate elements or corrupt state:

```js
// IDEMPOTENT — checks existence before creating
addCardToHand(card) {
    let el = document.getElementById(`card-${card.id}`);
    if (el) return;  // Already exists

    el = document.createElement('div');
    el.id = `card-${card.id}`;
    this.handStock.addCard(card);
}
```

### 7.5 Diff-Style Updates

For complex views, compute the difference between current state and new state, then apply only the changes:

```js
updatePlayerList(players) {
    const existing = this.container.querySelectorAll('.player-entry');
    const existingIds = new Set([...existing].map(el => el.dataset.playerId));
    const newIds = new Set(Object.keys(players));

    // Remove stale entries
    for (const el of existing) {
        if (!newIds.has(el.dataset.playerId)) {
            el.remove();
        }
    }

    // Add new entries
    for (const [id, data] of Object.entries(players)) {
        if (!existingIds.has(id)) {
            this.createPlayerEntry(id, data);
        }
    }

    // Update existing entries
    for (const [id, data] of Object.entries(players)) {
        this.updatePlayerEntry(id, data);
    }
}
```

---

## 8. Action Buttons

### 8.1 Lifecycle

```
State entered
  │
  ▼
onUpdateActionButtons(stateName, args)
  │
  ├── removeActionButtons() ← clear all existing
  ├── addActionButton()     ← add current state's buttons
  └── addActionButton()     ← add more buttons
  │
  ▼
Player clicks button
  │
  ▼
Action performed → server processes → notifications arrive
  │
  ▼
State may change → onUpdateActionButtons called again
```

### 8.2 Implementation

```js
onUpdateActionButtons(stateName, args) {
    this.bga.gameui.removeActionButtons();

    const isActive = this.bga.players.isCurrentPlayerActive();
    if (!isActive) return;

    switch (stateName) {
        case 'playerTurn':
            this.addPlayCardButton(args);
            this.addPassButton();
            break;
        case 'resolveChoice':
            this.addChoiceButtons(args);
            break;
        case 'discardPhase':
            this.addDiscardButton(args);
            break;
    }
}
```

### 8.3 State Ownership

Action buttons are owned by Game.js via `onUpdateActionButtons`. They are not owned by any manager because they are transient — they change with every state.

### 8.4 Cleanup

The framework's `removeActionButtons()` clears all buttons. This is called automatically at the start of each state. Do not store references to button elements — they are managed by the framework.

### 8.5 Button Grouping

Group related buttons visually:

```js
this.bga.gameui.addActionButton('btn_play', _('Play'), handler, 'primary');
this.bga.gameui.addActionButton('btn_pass', _('Pass'), handler, 'secondary');
this.bga.gameui.addActionButton('btn_undo', _('Undo'), handler, 'gray');
```

The fourth parameter (`'primary'`, `'secondary'`, `'gray'`) controls button styling. See [bga-developer-handbook.md §8](./bga-developer-handbook.md#8-ui-enhancements-and-interactivity).

### 8.6 Icons

Buttons can include icons:

```js
this.bga.gameui.addActionButton('btn_build', _('Build'), handler, 'primary', null, false, 'hammer');
```

The icon parameter references a CSS class that displays the icon.

### 8.7 Confirmation Dialogs

For destructive or irreversible actions, show a confirmation dialog:

```js
onUpdateActionButtons(stateName, args) {
    this.bga.gameui.addActionButton(
        'btn_end_game',
        _('End Game'),
        () => this.showEndGameConfirmation(),
        'gray'
    );
}

showEndGameConfirmation() {
    this.game.dialogMgr.confirm(
        _('Are you sure you want to end the game?'),
        () => this.game.bga.actions.performAction('actEndGame')
    );
}
```

---

## 9. Dialog Architecture

### 9.1 DialogManager

All dialogs go through a single DialogManager. This prevents multiple dialogs from conflicting:

```js
class DialogManager {
    constructor(game) {
        this.game = game;
        this.activeDialog = null;
        this.overlay = null;
    }

    show(title, content, buttons, options = {}) {
        this.close();  // Close any existing dialog

        this.overlay = document.createElement('div');
        this.overlay.className = 'dialog-overlay';
        this.overlay.innerHTML = `
            <div class="dialog-box">
                <h3>${title}</h3>
                <div class="dialog-content">${content}</div>
                <div class="dialog-buttons"></div>
            </div>
        `;

        const btnContainer = this.overlay.querySelector('.dialog-buttons');
        for (const btn of buttons) {
            const el = document.createElement('button');
            el.textContent = btn.label;
            el.onclick = () => {
                btn.action();
                if (!options.persistent) this.close();
            };
            btnContainer.appendChild(el);
        }

        document.body.appendChild(this.overlay);
        this.activeDialog = this.overlay;
    }

    confirm(message, onConfirm, onCancel) {
        this.show(_('Confirm'), message, [
            { label: _('Yes'), action: onConfirm },
            { label: _('No'), action: onCancel || (() => {}) },
        ]);
    }

    close() {
        if (this.activeDialog) {
            this.activeDialog.remove();
            this.activeDialog = null;
        }
    }
}
```

### 9.2 Modal Ownership

The DialogManager owns the overlay. No other manager should create modals or overlay elements directly.

### 9.3 Temporary UI

Dialogs are temporary — they are created when needed and destroyed when closed. They do not persist across state transitions:

```js
onLeavingState(stateName) {
    this.dialogMgr.close();
}
```

### 9.4 Validation

Dialogs that require player input (e.g., resource selection) must validate before closing:

```js
showResourceSelection(options) {
    let selected = [];

    this.dialogMgr.show(_('Select Resources'), renderOptions(options), [
        {
            label: _('Confirm'),
            action: () => {
                if (selected.length === 0) {
                    this.showError(_('You must select at least one resource'));
                    return;  // Don't close
                }
                this.game.bga.actions.performAction('actSelectResources', {
                    resources: selected,
                });
            },
        },
    ], { persistent: true });  // Don't auto-close
}
```

### 9.5 Callbacks

Dialogs use callbacks to communicate choices back to Game.js. The dialog itself does not know about game actions:

```js
class ResourceDialog {
    constructor(game, options, onConfirm) {
        this.game = game;
        this.options = options;
        this.onConfirm = onConfirm;
    }

    show() {
        this.game.dialogMgr.show(
            _('Select Resources'),
            this.render(),
            [{ label: _('OK'), action: () => this.onConfirm(this.getSelected()) }],
            { persistent: true }
        );
    }
}
```

### 9.6 Cleanup

Dialogs must clean up event listeners and DOM when closed:

```js
close() {
    if (this.activeDialog) {
        this.activeDialog.remove();
        this.activeDialog = null;
        document.removeEventListener('keydown', this._keyHandler);
    }
}
```

---

## 10. Selection Systems

### 10.1 SelectionManager

A dedicated manager for selection state:

```js
class SelectionManager {
    constructor(game) {
        this.game = game;
        this.selected = null;       // Currently selected item
        this.selectables = [];       // Items that can be selected
        this.multiSelected = [];     // Multi-selection state
    }

    // Set which items are selectable
    setSelectable(items) {
        this.selectables = items;
        this.highlightSelectables();
    }

    // Select an item
    select(item) {
        if (!this.selectables.includes(item)) return;
        this.deselect();
        this.selected = item;
        this.highlightSelected(item);
    }

    // Deselect
    deselect() {
        if (this.selected) {
            this.unhighlight(this.selected);
            this.selected = null;
        }
    }

    // Clear all selection state
    clear() {
        this.deselect();
        this.selectables = [];
        this.multiSelected = [];
        this.clearHighlights();
    }
}
```

### 10.2 Selection State

Selection state is managed by SelectionManager, not by DOM classes:

```js
// CORRECT: query selection manager for state
onCellClick(x, y) {
    if (this.game.selectionMgr.isSelected({ x, y })) {
        // Already selected — deselect
        this.game.selectionMgr.deselect();
    } else {
        // Select
        this.game.selectionMgr.select({ x, y, type: 'cell' });
    }
}

// WRONG: read selection from DOM class
onCellClick(el) {
    if (el.classList.contains('selected')) {  // ← fragile, DOM may be stale
        // ...
    }
}
```

### 10.3 Highlighting

Selection highlighting is a visual effect, applied by SelectionManager on top of the DOM:

```js
highlightSelectables() {
    for (const item of this.selectables) {
        const el = this.getElementById(item);
        el?.classList.add('selectable');
    }
}

highlightSelected(item) {
    const el = this.getElementById(item);
    el?.classList.add('selected');
}

unhighlight(item) {
    const el = this.getElementById(item);
    el?.classList.remove('selected');
}

clearHighlights() {
    this.game.bga.gamearea.querySelectorAll('.selectable, .selected')
        .forEach(el => el.classList.remove('selectable', 'selected'));
}
```

### 10.4 Cancellation

Selection cancellation can happen via:
- Clicking elsewhere (deselect)
- Leaving the state (`onLeavingState` → `selectionMgr.clear()`)
- Performing an action (action handler calls `selectionMgr.clear()`)
- Right-click or Escape key

```js
onLeavingState(stateName) {
    this.selectionMgr.clear();
}

// Manager action handler
onPlayCard() {
    const selected = this.game.selectionMgr.getSelected();
    if (selected) {
        this.game.bga.actions.performAction('actPlayCard', { cardId: selected.id });
        this.game.selectionMgr.clear();
    }
}
```

### 10.5 Multi-Selection

For games that require selecting multiple items:

```js
class SelectionManager {
    toggleMultiSelect(item) {
        const index = this.multiSelected.indexOf(item);
        if (index >= 0) {
            this.multiSelected.splice(index, 1);
            this.unhighlight(item);
        } else {
            this.multiSelected.push(item);
            this.highlightSelected(item);
        }
    }

    getMultiSelected() {
        return [...this.multiSelected];
    }

    isFullySelected() {
        return this.multiSelected.length === this.selectables.length;
    }
}
```

### 10.6 Hover

Hover effects are CSS-driven, not managed by JavaScript:

```css
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    cursor: pointer;
}

.card.selectable:hover {
    border-color: #4CAF50;
    box-shadow: 0 0 8px rgba(76, 175, 80, 0.5);
}
```

---

## 11. State vs UI

### 11.1 State Categories

| Category | Source | Persistence | Example |
|---|---|---|---|
| **Persistent state** | Server (`getAllDatas`, notifications) | Survives page refresh | Card locations, scores |
| **Temporary UI state** | Client only | Lost on refresh | Selection, open dialogs |
| **Animation state** | Client only | Lost when animation completes | Mid-flight card |
| **Derived state** | Computed from persistent state | Recomputable | Hand count, filtered card list |

### 11.2 Persistent State

Persistent state mirrors server state. It is stored in manager caches:

```js
this.cards = {
    42: { id: 42, type: 'farm', location: 'hand', locationArg: 123 },
    43: { id: 43, type: 'pasture', location: 'discard', locationArg: 0 },
};
```

This cache is:
- Populated from `getAllDatas()` in `setup()`
- Updated by notification handlers
- Replaced entirely by `refreshUI`
- Never mutated optimistically (see [client-synchronization-architecture.md §9.4](./client-synchronization-architecture.md#94-optimistic-ui))

### 11.3 Temporary UI State

Temporary state is UI-only and has no server counterpart:

```js
this.isDragging = false;
this.activeDialog = null;
this.selectedCardId = null;
this.scrollPosition = 0;
```

This state is:
- Created and destroyed within a single user interaction
- Not persisted across page refreshes
- Cleared in `onLeavingState()`

### 11.4 Animation State

Animation state tracks in-progress visual transitions:

```js
class CardManager {
    animateCardTo(cardId, targetStock) {
        const card = this.cards[cardId];
        card._animating = true;  // ← animation state

        return this.cardsManager.moveCard(card, this.currentStock, targetStock)
            .then(() => {
                card._animating = false;  // ← animation state cleared
            });
    }
}
```

Animation state is ephemeral and must not affect game logic:

```js
// CORRECT: animation state is separate from game state
getCardPosition(cardId) {
    const card = this.cards[cardId];
    return card.location;  // ← game state, correct regardless of animation
}

// WRONG: derived from animation state
getCardPosition(cardId) {
    const el = document.getElementById(`card-${cardId}`);
    return el._animating ? 'mid-flight' : this.cards[cardId].location;  // ← fragile
}
```

### 11.5 Derived State

Derived state is computed from persistent state and never stored separately:

```js
class PlayerPanelManager {
    // DERIVED: computed from persistent state
    getPlayerScore(playerId) {
        return this.players[playerId].score;
    }

    getHandCount(playerId) {
        return Object.values(this.cards)
            .filter(c => c.location === 'hand' && c.locationArg === playerId)
            .length;
    }

    // NOT derived: stored and updated separately
    // (this would be wrong — it could drift from the cache)
}
```

---

## 12. Animation Integration

### 12.1 Relationship with Notifications

Animations are triggered by notification handlers. The handler updates game state immediately, then returns a Promise that resolves when the animation completes:

```js
async notif_cardPlayed(notif) {
    const args = notif.args;

    // 1. Update state cache immediately
    this.cardMgr.updateCardLocation(args.card_id, 'play');

    // 2. Animate the visual transition
    await this.cardMgr.animateToLocation(args.card_id, 'play');

    // 3. After animation: state is already correct
}
```

### 12.2 Queue Interaction

The notification queue waits for each handler's returned Promise. This means animations block subsequent notifications:

```
Time  →
Queue: notif_A ──→ [animate A → await] ──→ notif_B ──→ [animate B → await] ──→ notif_C
```

If notifications must appear simultaneous, use `Promise.all` within a single notification:

```js
notif_drawCards(notif) {
    const promises = notif.args.cards.map(card =>
        this.cardMgr.animateDeal(card)
    );
    return Promise.all(promises);  // All cards animate in parallel
}
```

### 12.3 Animation Ownership

Animations are owned by the manager that owns the DOM element. The CardManager animates cards. The BoardManager animates tiles. Each manager uses its own animation helpers or the shared BgaAnimations Manager.

```js
class CardManager {
    constructor(game, containerId) {
        // BgaAnimations Manager (shared or per-manager)
        this.animationManager = new BgaAnimations.Manager({
            animationsActive: () => this.game.bga.gameui.bgaAnimationsActive(),
        });

        this.cardsManager = new BgaCards.Manager({
            animationManager: this.animationManager,
            // ...
        });
    }
}
```

### 12.4 Interruptibility

Animations can be interrupted by new state (e.g., reconnect, undo). Managers should handle this:

```js
reset(cardsData) {
    // Immediately stop all animations
    this.animationManager?.clear();

    // Rebuild from fresh data
    this.cards = cardsData;
    this.renderAll();
}
```

### 12.5 Fast Mode

Players should be able to skip animations via a preference:

```js
class CardManager {
    shouldAnimate() {
        return this.game.bga.gameui.bgaAnimationsActive()
            && !this.isFastMode();
    }

    isFastMode() {
        return this.game.getLocalPreference('fast_notif') === 1;
    }

    async animateToLocation(cardId, targetLocation) {
        if (!this.shouldAnimate()) {
            // Skip animation, just move DOM
            this.moveElementTo(cardId, targetLocation);
            return;
        }
        // Animate
        await this.cardsManager.moveCard(card, this.currentStock, targetStock);
    }
}
```

See [notification-patterns.md §13.4](./notification-patterns.md#134-fast-mode-earth-pattern).

---

## 13. Performance

### 13.1 DOM Batching

Batch DOM reads and writes to avoid layout thrashing:

```js
// BAD: interleaved reads and writes
for (const card of cards) {
    const w = el.offsetWidth;           // Read (forces layout)
    el.style.width = (w + 10) + 'px';   // Write
    const h = el.offsetHeight;          // Read (forces layout again)
    el.style.height = (h + 10) + 'px';  // Write
}

// GOOD: batch reads, then batch writes
const widths = cards.map(c => document.getElementById(`card-${c.id}`).offsetWidth);
cards.forEach((c, i) => {
    const el = document.getElementById(`card-${c.id}`);
    el.style.width = (widths[i] + 10) + 'px';
    el.style.height = (widths[i] + 10) + 'px';
});
```

### 13.2 Minimizing Reflows

Use CSS classes instead of inline style changes:

```js
// BAD: inline style changes cause reflows
el.style.display = 'block';
el.style.backgroundColor = 'red';
el.style.transform = 'translateX(10px)';

// GOOD: class-based changes
el.classList.add('visible', 'highlighted', 'moved');
```

### 13.3 Lazy Rendering

For large collections, render only what is visible:

```js
class LargeBoardManager {
    setup(boardData) {
        this.allTiles = boardData.tiles;
        // Only render tiles in the current viewport
        this.renderVisibleTiles();
    }

    renderVisibleTiles() {
        const visible = this.getVisibleTileIds();
        for (const id of visible) {
            if (!document.getElementById(`tile-${id}`)) {
                this.createTileElement(this.allTiles[id]);
            }
        }
    }
}
```

### 13.4 Virtual Ownership

For very large boards, use a virtual rendering approach — only elements in the viewport are in the DOM:

```js
class BoardManager {
    onScroll() {
        const visible = this.getVisibleRange();
        this.virtualTiles.render(visible);
    }
}
```

This is rarely needed for BGA games (boards typically fit in the viewport). Use only when the board exceeds ~2000 elements.

### 13.5 Large Boards

For grids larger than 10x10:

- Use a single container with CSS Grid (`display: grid`)
- Avoid individual event listeners on each cell — use event delegation
- Batch cell updates into DocumentFragment for initial render

```js
renderGrid() {
    const fragment = document.createDocumentFragment();
    for (let y = 0; y < this.gridSize; y++) {
        for (let x = 0; x < this.gridSize; x++) {
            const cell = document.createElement('div');
            cell.className = 'board-cell';
            cell.dataset.x = x;
            cell.dataset.y = y;
            fragment.appendChild(cell);
        }
    }
    this.container.appendChild(fragment);  // Single reflow

    // Event delegation
    this.container.addEventListener('click', (e) => {
        const cell = e.target.closest('.board-cell');
        if (cell) this.onCellClick(cell.dataset);
    });
}
```

---

## 14. Scaling

### 14.1 Small Game (5-15 states, < 50 files)

```
modules/js/
├── Game.js            ← thin, < 200 lines
└── Game.css           ← single stylesheet
```

**Characteristics:**
- Game.js is the only file
- Notification handlers are inline methods on Game.js
- No separate manager classes
- Simple DOM manipulation via `document.getElementById`
- No BgaCards or animation system

**Suitable for:** Fill-and-pass games, simple card games, abstract games

### 14.2 Medium Game (15-40 states, 50-150 files)

```
modules/js/
├── Game.js
├── Managers/
│   ├── CardMgr.js
│   ├── BoardMgr.js
│   └── PlayerPanelMgr.js
├── States/
│   └── PlayerTurn.js       ← optional, for complex state logic
└── styles/
    ├── cards.css
    └── board.css
```

**Characteristics:**
- 2-4 managers with clear DOM ownership
- Notification handlers in Game.js delegate to managers
- Per-domain CSS files
- BgaCards for card rendering if needed
- Basic SelectionManager

**Suitable for:** Arnak-level complexity

### 14.3 Large Game (40-80 states, 150-400 files)

```
modules/js/
├── Game.js
├── Managers/
│   ├── CardMgr.js
│   ├── BoardMgr.js
│   ├── PlayerPanelMgr.js
│   ├── ScoreMgr.js
│   ├── DialogMgr.js
│   ├── SelectionMgr.js
│   └── AnimationMgr.js
├── States/
│   ├── PlayerTurn.js
│   ├── ResolveChoice.js
│   └── ScoringPhase.js
├── Widgets/
│   ├── Counter.js
│   ├── PlayerPanel.js
│   └── ResourceIcon.js
├── Core/
│   ├── Notifications.js
│   └── Helpers.js
└── styles/
    ├── cards.scss
    ├── board.scss
    ├── panels.scss
    └── dialogs.scss
```

**Characteristics:**
- 5-8 managers with narrow responsibilities
- State handler files for complex states
- Reusable widget components
- BgaCards + BgaAnimations for card/animation management
- DialogManager for all modals
- SCSS partials for styling
- Notification delegation layer

**Suitable for:** Agricola/ArkNova complexity

### 14.4 Very Large Game (80+ states, 400+ files)

```
modules/js/
├── Game.js
├── Domain/                           ← mirrors server domain structure
│   ├── Cards/
│   │   ├── CardMgr.js
│   │   ├── HandMgr.js
│   │   ├── TableauMgr.js
│   │   └── DeckMgr.js
│   ├── Board/
│   │   ├── BoardMgr.js
│   │   └── TileMgr.js
│   ├── Players/
│   │   ├── PlayerPanelMgr.js
│   │   └── ScoreMgr.js
│   └── Actions/
│       ├── ActionMgr.js
│       └── PaymentMgr.js
├── States/
│   ├── PlayerTurn.js
│   ├── ResolveChoice.js
│   ├── DiscardPhase.js
│   └── ActivationPhase.js
├── Widgets/
│   ├── Counter.js
│   ├── PlayerPanel.js
│   ├── ResourceIcon.js
│   ├── CardDetail.js
│   └── ObjectiveDisplay.js
├── Core/
│   ├── Notifications.js
│   ├── DialogMgr.js
│   ├── SelectionMgr.js
│   ├── AnimationMgr.js
│   └── Helpers.js
└── styles/
    ├── cards.scss
    ├── board.scss
    ├── panels.scss
    ├── dialogs.scss
    └── animations.scss
```

**Characteristics:**
- Domain-oriented package layout (Domain/{Name}/)
- 10+ managers with single responsibilities
- Full BgaCards + BgaAnimations integration
- State handlers per state file
- Rich dialog system for selections, payments, confirmations
- Multi-selection support
- Fast-mode animation skipping
- SCSS with mixins and variables

**Suitable for:** Earth-level complexity, simultaneous turns, rich card interactions

### 14.5 Reference Project Client Architecture Comparison

| Aspect | Arnak | Agricola | ArkNova | Earth |
|---|---|---|---|---|
| **Client Managers** | Minimal | Informal | Informal | Full (14 managers) |
| **Game.js size** | Large (monolithic) | Medium | Medium | Small (thin) |
| **Rendering** | Direct DOM | Direct DOM | Direct DOM | BgaCards + DOM |
| **Animations** | Manual | Manual | Manual | BgaAnimations |
| **Dialog system** | Inline | Inline | Inline | DialogManager |
| **State handlers** | In Game.js | In Game.js | In Game.js | In dedicated files |
| **CSS architecture** | Per-domain CSS | SCSS modules | SCSS modules | SCSS partials |
| **Client architecture rating** | ★★★ | ★★★ | ★★★ | ★★★★★ |

From [reference-project-analysis.md](./reference-project-analysis.md#5-summary-matrix).

---

## 15. Anti-Patterns

### 15.1 Massive Game.js

**Symptom:** Game.js exceeds 800 lines, contains rendering logic, DOM manipulation, and game state management.

```js
// BAD: 1500-line Game.js doing everything
setup(gamedatas) {
    // 50 lines of card creation
    // 50 lines of board rendering
    // 50 lines of player panel setup
    // ... scattered with no structure
}
```

**Solution:** Extract managers. Each visual domain gets its own file. See §5.

**Reference:** Earth's Game.js is thin because all rendering is delegated to 14 managers.

### 15.2 DOM Queries Everywhere

**Symptom:** Code scattered throughout Game.js calling `document.getElementById`, `querySelector`, and modifying elements directly.

```js
// BAD: DOM queries scattered across methods
updateScore(pid, score) {
    document.getElementById(`score-${pid}`).textContent = score;
}
highlightCard(cardId) {
    document.getElementById(`card-${cardId}`).classList.add('highlighted');
}
```

**Solution:** All DOM queries belong in the owning manager. The manager provides methods like `getElementById`, `updateScore`, and `highlightCard`.

### 15.3 Global Variables

**Symptom:** Game state stored in global variables or on the `window` object.

```js
// BAD: global state
window.selectedCard = null;
window.currentPlayer = 123;
```

**Solution:** State belongs in managers. Use `this.game.selectionMgr.selected` and manager caches.

### 15.4 Mixed Responsibilities

**Symptom:** A manager that handles cards AND scores AND dialogs.

```js
// BAD: CardManager knows about everything
class CardManager {
    updateScore(pid, score) { ... }
    showDialog(title, content) { ... }
    highlightCard(cardId) { ... }
}
```

**Solution:** One responsibility per manager. Score updates → `PlayerPanelManager`. Dialogs → `DialogManager`. Cards → `CardManager`.

### 15.5 Duplicate Rendering

**Symptom:** The same DOM element is created or updated in multiple places.

```js
// BAD: two places create the same card element
// In notif_cardPlayed:
el = document.createElement('div');
// In notif_drawCards:
el = document.createElement('div');  // May duplicate
```

**Solution:** Each element is created by exactly one manager, exactly once. Use idempotent creation (check existence before creating).

### 15.6 Notification Logic in Widgets

**Symptom:** Widgets subscribe to notifications directly.

```js
// BAD: widget handles notifications
class PlayerPanel {
    constructor() {
        this.game.bga.notifications.subscribe('updateScore', (args) => {
            this.updateScore(args.score);
        });
    }
}
```

**Solution:** Notifications flow through Game.js → Manager → Widget. Widgets are passive — they receive data from managers.

### 15.7 Business Logic in JavaScript

**Symptom:** Game rules implemented on the client.

```js
// BAD: client-side game logic
notif_cardPlayed(notif) {
    if (this.currentPlayer === notif.args.playerId) {
        this.deductFromHand(notif.args.cardId);
        this.incrementScore(1);
        if (this.score >= 10) {
            this.declareWinner(this.currentPlayer);  // ← game logic on client!
        }
    }
}
```

**Solution:** The client renders, the server decides. Send the complete effect in the notification. See [client-synchronization-architecture.md §12.6](./client-synchronization-architecture.md#126-server-side-state-in-client).

---

## 16. Templates

### 16.1 Canonical Game.js

```js
class MyGame {
    constructor() {
        this.cardMgr = null;
        this.boardMgr = null;
        this.playerPanelMgr = null;
        this.dialogMgr = null;
        this.selectionMgr = null;
    }

    // ── SETUP ──

    setup(gamedatas) {
        this.cardMgr = new CardManager(this, 'card-container');
        this.cardMgr.setup(gamedatas.cards);

        this.boardMgr = new BoardManager(this, 'board-container');
        this.boardMgr.setup(gamedatas.board);

        this.playerPanelMgr = new PlayerPanelManager(this, 'panels-container');
        this.playerPanelMgr.setup(gamedatas.players);

        this.dialogMgr = new DialogManager(this);
        this.selectionMgr = new SelectionManager(this);

        this.setupNotifications();
    }

    setupNotifications() {
        this.bga.notifications.setupPromiseNotifications();
    }

    // ── STATE HANDLERS ──

    onEnteringState(stateName, args) {
        this.cardMgr.onStateChange(stateName, args);
        this.boardMgr.onStateChange(stateName, args);
        this.playerPanelMgr.onStateChange(stateName, args);
        this.selectionMgr.clear();
    }

    onLeavingState(stateName) {
        this.dialogMgr.close();
        this.selectionMgr.clear();
    }

    onUpdateActionButtons(stateName, args) {
        this.bga.gameui.removeActionButtons();
        if (!this.bga.players.isCurrentPlayerActive()) return;

        if (args.canPass) {
            this.bga.gameui.addActionButton(
                'btn_pass', _('Pass'),
                () => this.bga.actions.performAction('actPass')
            );
        }
    }

    // ── NOTIFICATION HANDLERS ──

    notif_cardPlayed(notif) { this.cardMgr.onCardPlayed(notif.args); }

    notif_gainResources(notif) { this.playerPanelMgr.onGainResources(notif.args); }

    notif_placeTile(notif) { this.boardMgr.onPlaceTile(notif.args); }

    notif_refreshUI(notif) {
        this.cardMgr.reset(notif.args.datas.cards);
        this.boardMgr.reset(notif.args.datas.board);
        this.playerPanelMgr.reset(notif.args.datas.players);
    }

    notif_refreshHand(notif) {
        if (this.bga.players.isCurrentPlayerSpectator()) return;
        this.cardMgr.refreshHand(notif.args.hand);
    }
}
```

### 16.2 Canonical Manager

```js
class CardManager {
    constructor(game, containerId) {
        this.game = game;
        this.container = document.getElementById(containerId);
        this.cards = {};         // State cache
        this.stocks = {};        // BgaCards stocks
    }

    // ── SETUP ──

    setup(cardsData) {
        this.cards = cardsData;
        this.createStocks();
        this.renderAll();
    }

    createStocks() {
        this.handStock = new BgaCards.HandStock(
            this.game.cardsManager,
            document.getElementById('player-hand')
        );
        this.tableauStock = new BgaCards.LineStock(
            this.game.cardsManager,
            document.getElementById('tableau')
        );
    }

    renderAll() {
        const cards = Object.values(this.cards);
        this.handStock.addCards(cards.filter(c => c.location === 'hand'));
        this.tableauStock.addCards(cards.filter(c => c.location === 'play'));
    }

    // ── NOTIFICATION HANDLERS ──

    onCardPlayed(args) {
        this.cards[args.card_id].location = 'play';
        return this.game.cardsManager.moveCard(
            this.cards[args.card_id],
            this.handStock,
            this.tableauStock
        );
    }

    onStateChange(stateName, args) {
        if (args.playableCards) {
            this.setSelectable(args.playableCards);
        } else {
            this.clearSelectable();
        }
    }

    // ── SELECTION ──

    setSelectable(cardIds) {
        for (const id of cardIds) {
            const el = this.getElementById(id);
            el?.classList.add('selectable');
        }
    }

    clearSelectable() {
        this.container.querySelectorAll('.selectable')
            .forEach(el => el.classList.remove('selectable'));
    }

    // ── REFRESH ──

    refreshHand(hand) {
        this.handStock.removeAll();
        this.handStock.addCards(hand);
    }

    reset(cardsData) {
        this.cards = cardsData;
        this.handStock.removeAll();
        this.tableauStock.removeAll();
        this.renderAll();
    }

    // ── HELPERS ──

    getElementById(cardId) {
        return document.getElementById(`card-${cardId}`);
    }
}
```

### 16.3 Canonical Widget

```js
class ResourceIcon {
    constructor(container, type, count) {
        this.container = container;
        this.type = type;
        this.el = null;
        this.render(count);
    }

    render(count) {
        this.el = document.createElement('div');
        this.el.className = `resource-icon resource-${this.type}`;
        this.el.innerHTML = `
            <span class="resource-icon-image"></span>
            <span class="resource-icon-count">${count}</span>
        `;
        this.container.appendChild(this.el);
    }

    update(count) {
        if (this.el) {
            this.el.querySelector('.resource-icon-count').textContent = count;
        }
    }
}
```

### 16.4 Canonical Dialog

```js
class DialogManager {
    constructor(game) {
        this.game = game;
        this.activeDialog = null;
    }

    show(title, contentHtml, buttons, options = {}) {
        this.close();

        const overlay = document.createElement('div');
        overlay.className = 'dialog-overlay';
        overlay.innerHTML = `
            <div class="dialog-box">
                <h3 class="dialog-title">${title}</h3>
                <div class="dialog-content">${contentHtml}</div>
                <div class="dialog-buttons"></div>
            </div>
        `;

        const btnContainer = overlay.querySelector('.dialog-buttons');
        for (const btn of buttons) {
            const el = document.createElement('button');
            el.textContent = btn.label;
            el.className = btn.className || '';
            el.onclick = () => {
                btn.action();
                if (!options.persistent) this.close();
            };
            btnContainer.appendChild(el);
        }

        if (options.closeOnOverlay !== false) {
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) this.close();
            });
        }

        document.body.appendChild(overlay);
        this.activeDialog = overlay;
    }

    confirm(message, onConfirm, onCancel) {
        this.show(
            _('Confirm'),
            `<p>${message}</p>`,
            [
                { label: _('Yes'), action: onConfirm, className: 'btn-primary' },
                { label: _('No'), action: onCancel || (() => {}), className: 'btn-secondary' },
            ]
        );
    }

    close() {
        if (this.activeDialog) {
            this.activeDialog.remove();
            this.activeDialog = null;
        }
    }
}
```

### 16.5 Canonical Player Panel

```js
class PlayerPanelManager {
    constructor(game, containerId) {
        this.game = game;
        this.container = document.getElementById(containerId);
        this.panels = {};
    }

    setup(playersData) {
        this.container.innerHTML = '';
        for (const [id, data] of Object.entries(playersData)) {
            this.panels[id] = this.createPanel(id, data);
        }
    }

    createPanel(playerId, data) {
        const panel = document.createElement('div');
        panel.id = `player-board-${playerId}`;
        panel.className = 'player-board';
        panel.innerHTML = `
            <div class="player-name">${data.name}</div>
            <div class="player-score">
                <span class="counter" id="score-${playerId}">0</span>
            </div>
            <div class="player-resources" id="resources-${playerId}"></div>
        `;
        this.container.appendChild(panel);

        // Create counter
        const counter = new ebg.counter();
        counter.create(`score-${playerId}`);
        counter.setValue(data.score);

        return { panel, counter, resources: data.resources };
    }

    onGainResources(args) {
        const panel = this.panels[args.player_id];
        if (!panel) return;

        panel.resources = args.resources;
        const container = panel.panel.querySelector('.player-resources');
        container.innerHTML = this.renderResources(args.resources);
    }

    updateScore(playerId, score) {
        const panel = this.panels[playerId];
        if (panel) {
            panel.counter.toValue(score);
        }
    }

    reset(playersData) {
        this.setup(playersData);
    }

    renderResources(resources) {
        return Object.entries(resources)
            .filter(([, v]) => v > 0)
            .map(([type, count]) =>
                `<span class="resource resource-${type}">${count}</span>`
            )
            .join('');
    }
}
```

---

## 17. Checklists

### 17.1 Architecture Review

- [ ] Game.js is thin (< 300 lines for medium games, < 500 for large games)
- [ ] Each visual domain has a dedicated manager
- [ ] Managers are instantiated in `setup()` with container element IDs
- [ ] Notification handlers in Game.js are one-liners that delegate to managers
- [ ] No DOM manipulation in Game.js (all in managers)
- [ ] No `document.getElementById` calls outside the owning manager
- [ ] No game logic or business rules on the client
- [ ] No direct manager-to-manager calls (communication through Game.js)
- [ ] Selection state is managed by SelectionManager, not by DOM classes
- [ ] DialogManager is the only class that creates modals
- [ ] Client-side state cache is populated from `getAllDatas()` and updated by notifications
- [ ] No `window` globals or leaked state

### 17.2 Performance Review

- [ ] Notification handlers are idempotent
- [ ] DOM reads and writes are batched
- [ ] CSS classes are used instead of inline style changes
- [ ] Event delegation is used for large grids
- [ ] `DocumentFragment` is used for batch element creation
- [ ] Animations are interruptible (can be cancelled on reset)
- [ ] Fast mode (skip animations) is implemented
- [ ] No unnecessary DOM queries in animation loops
- [ ] Large boards (> 10x10) use event delegation, not per-cell listeners
- [ ] `requestAnimationFrame` is used for smooth animations

### 17.3 Maintainability Review

- [ ] Manager responsibilities are documented in class comments
- [ ] Each manager is in its own file
- [ ] Manager names are consistent (`XxxManager`)
- [ ] CSS is in domain-specific files (not one monolithic stylesheet)
- [ ] Notification handlers are grouped by domain
- [ ] State handlers are separated from rendering logic
- [ ] The folder structure matches the project's scale (§14)
- [ ] BgaCards is used for card games (not manual DOM for cards)
- [ ] No deprecated Dojo patterns in new code
- [ ] All UI strings use `_()` for translation
- [ ] i18n for dynamic content is handled server-side and sent in notifications

---

## References

- [client-synchronization-architecture.md](./client-synchronization-architecture.md) — notification handlers (§4), client consistency (§9), templates (§13)
- [game-flow-architecture.md](./game-flow-architecture.md) — thin coordinator (§3.3), client module layout (§3.1), action buttons
- [state-machine-architecture.md](./state-machine-architecture.md) — onEnteringState (§4), onUpdateActionButtons, state args (§10)
- [domain-architecture.md](./domain-architecture.md) — manager pattern (§4), separation of concerns, dependency rules (§14)
- [notification-patterns.md](./notification-patterns.md) — notification handlers (§2.3), UI refresh (§9), fast mode (§13.4), idempotency (§10.3)
- [reference-project-analysis.md](../foundation/reference-project-analysis.md) — Earth client architecture (§4.2), client ratings (§5), Earth Manager pattern
- [bga-developer-handbook.md](../foundation/bga-developer-handbook.md) — bga API (§6), BgaCards (§7), BgaAnimations (§11), Stock (§7), Counters
