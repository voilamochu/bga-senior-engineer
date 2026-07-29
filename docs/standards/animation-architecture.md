# BGA Animation Architecture — Engineering Standard

**Document purpose:** Define the complete architecture for animations in Board Game Arena implementations. Explain how professional BGA projects coordinate animations with notifications, state transitions, rendering, and user interaction.

**Applicability:** All new BGA game implementations. Existing projects should use this document when adding or refactoring animation logic, integrating BgaAnimations, or debugging animation-related synchronization issues.

**Cross-references:**
- [client-ui-architecture.md](./client-ui-architecture.md) — manager pattern, rendering, state vs UI, performance
- [client-synchronization-architecture.md](./client-synchronization-architecture.md) — notification lifecycle, queue processing, consistency
- [notification-patterns.md](./notification-patterns.md) — notification sequencing, minimum duration, UI refresh
- [game-flow-architecture.md](./game-flow-architecture.md) — execution pipeline, transaction model
- [state-machine-architecture.md](./state-machine-architecture.md) — state transitions, args, _no_notify
- [bga-developer-handbook.md](../foundation/bga-developer-handbook.md) — BgaAnimations API, BgaCards, Counters
- [bga-ai-implementation-reference.md](../foundation/bga-ai-implementation-reference.md) — BgaAnimations setup, component library
- [reference-project-analysis.md](../foundation/reference-project-analysis.md) — animation patterns per project, client architecture

---

## Table of Contents

- [1. Animation Philosophy](#1-animation-philosophy)
- [2. Animation Lifecycle](#2-animation-lifecycle)
- [3. Animation Ownership](#3-animation-ownership)
- [4. Animation Types](#4-animation-types)
- [5. Queue Architecture](#5-queue-architecture)
- [6. Promise Architecture](#6-promise-architecture)
- [7. Animation Managers](#7-animation-managers)
- [8. BgaAnimations](#8-bgaaniamations)
- [9. BgaCards Integration](#9-bgacards-integration)
- [10. Performance](#10-performance)
- [11. Fast Mode](#11-fast-mode)
- [12. Reconnect](#12-reconnect)
- [13. Spectators](#13-spectators)
- [14. Anti-Patterns](#14-anti-patterns)
- [15. Templates](#15-templates)
- [16. Checklists](#16-checklists)

---

## 1. Animation Philosophy

### 1.1 Four Principles

**Principle 1 — Animations Are Presentation Only.** Animations are visual transitions between two correct states. They never modify authoritative game state, never affect game logic, and never carry gameplay meaning. A player who disables all animations must see the exact same game state as a player who watches every animation.

```js
// CORRECT: animation is a visual transition on top of correct state
async animateCardTo(cardId, targetLocation) {
    const card = this.cards[cardId];
    card.location = targetLocation;      // ← state updated BEFORE animation
    await this.moveElementWithAnimation(cardId, targetLocation);
    // ← after animation: state is already correct
}

// WRONG: animation drives state
async animateCardTo(cardId, targetLocation) {
    await this.moveElementWithAnimation(cardId, targetLocation);
    this.cards[cardId].location = targetLocation;  // ← state updated AFTER
}
```

**Principle 2 — Never Authoritative.** Animations cannot be the source of truth for any game state. The source of truth is always the server database. On the client, the source of truth is the state cache populated from `getAllDatas()` and notification payloads.

See [client-ui-architecture.md §11.4](./client-ui-architecture.md#114-animation-state).

**Principle 3 — Server-First Model.** Every animation is a consequence of a server action. The sequence is always:

```
Server action → Notification → Client state cache → Animation → Final UI
```

There is no client-initiated animation that represents speculative game state. See [client-synchronization-architecture.md §9.4](./client-synchronization-architecture.md#94-optimistic-ui).

**Principle 4 — Event-Driven Animation.** Animations are triggered by notification handlers. The notification queue processes handlers sequentially, and each handler can return a Promise that represents the animation's duration. This ties the animation timeline directly to the server's event stream.

### 1.2 Why This Philosophy Matters

```
Correct flow:
  Notification arrives → state cache updated → animation plays → done
  The animation shows a transition between the OLD visual state and the NEW.
  At all times, the state cache is correct.

Incorrect flow:
  Animation plays → state cache updated after → done
  During the animation, the state cache is WRONG.
  If a reconnect happens mid-animation, state is lost.
```

The server-first model guarantees that the client state cache is always correct, regardless of animation state.

---

## 2. Animation Lifecycle

### 2.1 Complete Lifecycle

```
NOTIFICATION ARRIVES
       │
       ▼
┌─────────────────────────────────────────┐
│ 1. NOTIFICATION RECEIVED                 │
│    HTTP response deserialised            │
│    Notification added to framework queue │
└─────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│ 2. STATE CACHE UPDATE                    │
│    notif_X handler runs                  │
│    Updates manager state cache           │
│    (game state is now correct)           │
└─────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│ 3. ANIMATION SCHEDULING                 │
│    Handler decides WHETHER to animate   │
│    Checks fast mode preference          │
│    If skip: jump to step 6              │
│    If animate: create animation plan    │
└─────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│ 4. DOM UPDATE (pre-animation)           │
│    Element positioned at START state    │
│    (visible, at origin location)        │
└─────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│ 5. ANIMATION EXECUTION                  │
│    CSS transition / BgaAnimations runs   │
│    Element moves from START to END      │
│    Returns Promise when finished        │
└─────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│ 6. PROMISE RESOLUTION                   │
│    Handler's Promise resolves           │
│    Animation state cleaned up           │
│    Final DOM state is correct           │
└─────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│ 7. NEXT NOTIFICATION                    │
│    Framework dispatches next notif      │
│    (or fires onEnteringState if done)   │
└─────────────────────────────────────────┘
```

### 2.2 Notification Handler Flow

```
notif_cardPlayed(notif)
       │
       ▼
  ┌─────────────────────────┐
  │ Update state cache      │
  │ cards[id].loc = 'play'  │  ← ALWAYS first
  └──────────┬──────────────┘
             │
             ▼
  ┌─────────────────────────┐
  │ Should animate?         │
  │                         │
  │ ├── Fast mode?    → skip
  │ ├── Spectator?    → skip
  │ └── Normal play   → continue
  └──────────┬──────────────┘
             │
      animate│        │skip
             ▼        ▼
  ┌──────────────┐  ┌──────────────┐
  │ Animate DOM  │  │ Update DOM   │
  │ (Promise)    │  │ immediately  │
  └──────┬───────┘  └──────┬───────┘
         │                 │
         ▼                 ▼
  ┌─────────────────────────┐
  │ Promise resolves        │
  │ Queue advances          │
  └─────────────────────────┘
```

### 2.3 Card Movement Lifecycle

```
EXAMPLE: Card moves from hand to tableau

Time  │  STATE CACHE                     DOM
      │
notif │  card.location = 'play'          card is visible in hand
      │
      ▼
      │  card.location = 'play'          card element removed from hand stock
      │                                   card appended to tableau stock
      │                                   (hidden, at origin position)
      │
      ▼
      │  card.location = 'play'          BgaAnimations moves card
      │                                   from hand position to tableau
      │                                   (CSS transition plays)
      │
      ▼
done  │  card.location = 'play'          card visible in tableau stock
      │                                   no _animating flag
```

The state cache says `location = 'play'` throughout the entire animation. The DOM transitions visually, but the cache is always correct.

---

## 3. Animation Ownership

### 3.1 Ownership Hierarchy

```
Game.js  (coordinates, does NOT animate)
   │
   ├── CardManager      ── animates cards (via BgaCards / BgaAnimations)
   ├── BoardManager     ── animates tiles, tokens
   ├── PlayerPanelMgr   ── animates counters (ebg.counter)
   ├── ScoreMgr         ── animates score changes
   ├── AnimationManager ── shared animation service (optional)
   └── DialogManager    ── animates dialog open/close (CSS transitions)
```

### 3.2 Ownership Rules

**Rule 1 — Each DOM element is animated by its owning manager.**

```js
// CardManager owns card elements → CardManager animates them
class CardManager {
    async animateToLocation(cardId, targetStock) {
        const card = this.cards[cardId];
        return this.cardsManager.moveCard(card, this.currentStock, targetStock);
    }
}

// BoardManager owns tile elements → BoardManager animates them
class BoardManager {
    async animatePlaceTile(x, y, tileType) {
        const el = this.getCell(x, y);
        el.classList.add('tile-', tileType);
        el.classList.add('tile-placed');
        return this.waitForTransition(el);
    }
}
```

**Rule 2 — AnimationManager is a shared service, not an owner.**

The AnimationManager provides utility methods (tween, fade, slide) that managers call. It does not own any DOM elements:

```js
class AnimationManager {
    constructor(game) {
        this.game = game;
        this.activeAnimations = new Set();
        this.bgaAnimations = null;
    }

    async slideTo(element, targetX, targetY, duration = 300) {
        // Utility: moves any element
        // Caller (manager) owns the element
    }
}
```

**Rule 3 — BgaAnimations Manager is a framework-level service.**

The BgaAnimations `Manager` class handles animation scheduling and frame management. It is shared across BgaCards stocks and any custom animations:

```js
// Shared — created once in Game.js or CardManager
this.animationManager = new BgaAnimations.Manager({
    animationsActive: () => this.game.bga.gameui.bgaAnimationsActive(),
});
```

**Rule 4 — No cross-manager animation of the same element.**

Two managers must never animate the same DOM element. If a card transitions from hand (CardManager) to board (BoardManager), one manager must transfer ownership cleanly.

### 3.3 Ownership Transfer During Animation

When a card moves from hand to board, ownership transfers from CardManager to BoardManager:

```js
class CardManager {
    async playCardToBoard(cardId, boardX, boardY) {
        // 1. Remove from my stock (no animation)
        await this.handStock.removeCards([this.cards[cardId]]);

        // 2. Signal Game.js for board placement
        this.game.onCardPlacedOnBoard(cardId, boardX, boardY);
    }
}

class BoardManager {
    async placeCardOnBoard(cardId, x, y) {
        // 3. Create element in my container
        const el = this.createCardElement(cardId);
        this.getCell(x, y).appendChild(el);

        // 4. Animate placement (I own the element now)
        el.classList.add('card-placed');
        return this.waitForTransition(el);
    }
}
```

---

## 4. Animation Types

### 4.1 Type Classification

| Type | Duration | Interruptible? | Uses BgaAnimations? | Example |
|---|---|---|---|---|
| **Card movement** | 200-500ms | Yes | Yes | Hand → tableau, deck → hand |
| **Counter update** | 300-800ms | Yes | No (ebg.counter) | Score +5, resource count |
| **Board placement** | 200-400ms | Yes | Optional | Tile placed on grid |
| **Token movement** | 200-500ms | Yes | Yes | Meeple to new space |
| **Highlighting** | Instant | N/A | No | Selectable card glow |
| **Camera/viewport** | 300-600ms | Yes | Yes | Scroll to active area |
| **UI transitions** | 200-300ms | Yes | No (CSS) | Dialog open/close, panel slide |
| **Particle/effect** | 500-1500ms | Yes | Yes | Resource gain sparkle |

### 4.2 Card Movement

The most common animation type. Uses BgaCards stock operations:

```js
async animateDeal(card, fromStock, toStock) {
    // BgaCards handles the animation internally
    await fromStock.removeCards([card]);
    await toStock.addCards([card]);
}

async animatePlayCard(cardId, targetStock) {
    const card = this.cards[cardId];
    await this.handStock.removeCards([card]);
    await targetStock.addCards([card]);
}
```

### 4.3 Counter Updates

Counter animations use `ebg.counter`:

```js
notif_updateScore(notif) {
    const counter = this.counters[notif.args.player_id];
    counter.toValue(notif.args.score);  // ← animated
    // counter.toValue() does NOT return a Promise
    // The notification framework's minDuration ensures visibility
}
```

Since `ebg.counter.toValue()` does not return a Promise, the notification handler completes synchronously. The framework's `minDuration` setting ensures the counter animation has time to play:

```js
this.bga.notifications.setupPromiseNotifications({
    minDuration: 500,  // ← ensures 500ms minimum per notification
});
```

### 4.4 Board Placement

For tile or token placement on a board:

```js
async animatePlaceTile(x, y, tileType) {
    const cell = this.getCell(x, y);
    const tile = document.createElement('div');
    tile.className = `tile tile-${tileType} tile-placing`;
    cell.appendChild(tile);

    // Force layout, then animate
    tile.offsetHeight;  // ← force reflow
    tile.classList.remove('tile-placing');
    tile.classList.add('tile-placed');

    return this.waitForTransition(tile);
}

waitForTransition(el) {
    return new Promise(resolve => {
        const handler = () => {
            el.removeEventListener('transitionend', handler);
            resolve();
        };
        el.addEventListener('transitionend', handler);
        // Fallback: resolve after timeout
        setTimeout(resolve, 600);
    });
}
```

### 4.5 Highlighting

Highlighting is instantaneous, not animated. It uses CSS class toggling:

```css
.card.selectable {
    box-shadow: 0 0 8px rgba(76, 175, 80, 0.6);
    cursor: pointer;
    transition: box-shadow 0.15s ease;  /* quick fade, not a "scene" */
}

.card.selected {
    box-shadow: 0 0 12px rgba(255, 193, 7, 0.8);
    transform: translateY(-8px);
}
```

### 4.6 Camera / Viewport

Scroll or pan to the active area:

```js
async animateScrollToActive() {
    const activeArea = this.container.querySelector('.active');
    if (activeArea) {
        activeArea.scrollIntoView({ behavior: 'smooth', block: 'center' });
        // CSS scroll-behavior handles the animation
        // No Promise needed — scrollIntoView is not awaitable
    }
}
```

### 4.7 UI Transitions

Dialog open/close, panel slide, and other UI transitions use CSS:

```css
.dialog-overlay {
    opacity: 0;
    transition: opacity 0.2s ease;
}
.dialog-overlay.active {
    opacity: 1;
}

.dialog-box {
    transform: translateY(-20px);
    transition: transform 0.25s ease;
}
.dialog-overlay.active .dialog-box {
    transform: translateY(0);
}
```

### 4.8 Particle / Effect Animations

For visual effects like resource gain sparkles or score popups:

```js
async animateGainEffect(playerId, resources) {
    const panel = this.getPlayerPanel(playerId);
    const rect = panel.getBoundingClientRect();

    const effect = document.createElement('div');
    effect.className = 'gain-effect';
    effect.textContent = `+${resources}`;
    effect.style.left = `${rect.left + rect.width / 2}px`;
    effect.style.top = `${rect.top}px`;

    document.body.appendChild(effect);
    effect.offsetHeight;  // force reflow
    effect.classList.add('gain-effect-animate');

    return this.waitForTransition(effect).then(() => effect.remove());
}
```

---

## 5. Queue Architecture

### 5.1 Dual Queue Model

```
┌─────────────────────────────────────────────────────────────────────┐
│  FRAMEWORK NOTIFICATION QUEUE                                        │
│  (sequential, managed by BGA framework)                              │
│                                                                      │
│  notif_A → [handler runs → await Promise] → notif_B → [handler...]  │
│                                                                      │
│  One notification at a time. Queue blocks on Promise.               │
└─────────────────────────────────────────────────────────────────────┘
                               │
                    ┌──────────┴──────────┐
                    ▼                     ▼
        ┌──────────────────┐  ┌──────────────────┐
        │ SYNCHRONOUS      │  │ ASYNCHRONOUS     │
        │ Handler (no      │  │ Handler (returns  │
        │ animation)       │  │ Promise)          │
        │                  │  │                   │
        │ Queue proceeds   │  │ Queue blocks      │
        │ immediately      │  │ until Promise     │
        │ (after minDur)   │  │ resolves          │
        └──────────────────┘  └────────┬─────────┘
                                       │
                                       ▼
                            ┌──────────────────┐
                            │ ANIMATION QUEUE   │
                            │ (per-manager)     │
                            │                   │
                            │ BgaCards animates  │
                            │ CSS transitions   │
                            │ Promises resolve  │
                            └──────────────────┘
```

### 5.2 Framework Notification Queue

The framework dispatches notifications sequentially. Each notification handler runs and the queue waits for its return value:

```js
// Simplified internal behaviour of the notification queue
async processNotifications(batch) {
    for (const notif of batch) {
        const handler = this['notif_' + notif.type];
        if (handler) {
            const promise = handler.call(this, notif);
            if (promise instanceof Promise) {
                await promise;  // ← queue blocks here
            }
        }
        // Enforce minimum duration
        await this.waitMinDuration(notif);
    }
}
```

See [notification-patterns.md §7.1](./notification-patterns.md#71-sequential-processing).

### 5.3 Parallel vs Sequential Execution

**Within a single notification:** Use `Promise.all` for parallel animations:

```js
// PARALLEL: multiple cards deal simultaneously
async notif_drawCards(notif) {
    const promises = notif.args.cards.map(card =>
        this.cardMgr.animateDeal(card)
    );
    return Promise.all(promises);
    // All cards animate in parallel; queue waits for ALL to finish
}
```

**Across multiple notifications:** Always sequential:

```js
// SEQUENTIAL: notif_A animation completes before notif_B starts
notif_A → animate (300ms) → notif_B → animate (300ms) → notif_C
// Total: 600ms of animation + overhead
```

### 5.4 Grouping

Related animations should be grouped into a single notification:

```php
// SERVER: one notification for the complete action
$this->notifications->cardPlayed($player, $cardId, $resources);

// CLIENT: one handler, parallel animations
notif_cardPlayed(notif) {
    return Promise.all([
        this.cardMgr.animatePlayCard(notif.args.card_id),
        this.playerPanelMgr.animateGainResources(notif.args.player_id, notif.args.resources),
    ]);
}
```

### 5.5 Synchronization Barriers

When animations from different managers must be synchronized, use the notification handler as the barrier:

```js
notif_complexEffect(notif) {
    // Both managers animate in parallel
    // The handler's Promise.all ensures both complete before next notification
    return Promise.all([
        this.cardMgr.animateEffect(notif.args.card_id),
        this.boardMgr.animateEffect(notif.args.board_x, notif.args.board_y),
    ]);
}
```

For complex multi-step sequences, use server-side `simplePause` notifications:

```php
// SERVER: three-step animation sequence
$this->notifications->cardRevealed($player, $cardId);
$this->bga->notify->all('simplePause', '', ['time' => 800]);
$this->notifications->cardEffect($cardId, $effect);
$this->bga->notify->all('simplePause', '', ['time' => 500]);
$this->notifications->cardResolved($cardId);
```

---

## 6. Promise Architecture

### 6.1 Why Handlers Return Promises

Notification handlers return Promises to tell the queue: "Wait for me." This is the mechanism that synchronizes animation timing with the notification stream.

```js
// Handler WITHOUT animation: synchronous, queue does NOT wait
notif_updateScore(notif) {
    this.counters[notif.args.player_id].toValue(notif.args.score);
    // No return value → queue proceeds after minDuration
}

// Handler WITH animation: returns Promise, queue WAITS
notif_cardPlayed(notif) {
    return this.cardMgr.animatePlayCard(notif.args.card_id);
    // Queue blocks until animation completes
}
```

### 6.2 Await Chains

For sequential animations within a single notification:

```js
async notif_multiStep(notif) {
    // Step 1
    await this.cardMgr.animateReveal(notif.args.card_id);

    // Step 2 (starts after step 1 completes)
    await this.boardMgr.animateEffect(notif.args.effect);

    // Step 3 (starts after step 2 completes)
    await this.playerPanelMgr.animateScoreChange(notif.args.score);
}
```

The outer await chain blocks the notification queue for the combined duration.

### 6.3 Promise.all

For parallel animations within a single notification:

```js
async notif_drawCards(notif) {
    const cards = notif.args.cards;
    await Promise.all(cards.map(c => this.cardMgr.animateDeal(c)));
    // Queue resumes when the SLOWEST card animation finishes
}
```

### 6.4 Error Handling

Animation errors should never break the notification queue:

```js
async notif_cardPlayed(notif) {
    try {
        await this.cardMgr.animatePlayCard(notif.args.card_id);
    } catch (e) {
        // Animation failed — log but don't crash
        console.warn('Animation failed:', e);
        // State cache is already correct, so the game is not affected
        // Just move the element to its final position
        this.cardMgr.moveToFinalPosition(notif.args.card_id);
    }
}
```

**Rule:** Animation failures are not game logic failures. They should not throw exceptions that propagate to the framework error handler.

### 6.5 Cancellation

Any ongoing animation must be cancellable. When a reconnect or refreshUI occurs, all in-progress animations are cancelled:

```js
class AnimationManager {
    constructor() {
        this.activeAnimations = new Set();
    }

    track(promise) {
        const wrapped = promise.then(
            () => this.activeAnimations.delete(wrapped),
            () => this.activeAnimations.delete(wrapped)
        );
        this.activeAnimations.add(wrapped);
        return wrapped;
    }

    cancelAll() {
        for (const anim of this.activeAnimations) {
            // BgaAnimations handles its own cancellation
            // For CSS transitions: remove transitioning elements
        }
        this.activeAnimations.clear();
    }
}
```

### 6.6 Promise Flow Diagram

```
NOTIFICATION HANDLER
       │
       ├── update state cache          ← synchronous, immediate
       ├── check fast mode             ← synchronous
       │
       ├── if skip → update DOM now    ← synchronous, return undefined
       │                ⇒ queue proceeds after minDuration
       │
       └── if animate → start animation  ← returns Promise
                          │
                    ┌─────▼──────┐
                    │  Promise   │
                    │            │
                    │  CSS/Bga   │
                    │  Anim.     │
                    │            │
                    │  resolve() │──── then → queue advances
                    │  or        │
                    │  reject()  │──── catch → queue advances (logged)
                    └────────────┘
```

---

## 7. Animation Managers

### 7.1 Three Approaches

| Approach | Complexity | When to Use |
|---|---|---|
| **Manager-local helpers** | Low | Simple games, one or two animation types |
| **Dedicated AnimationManager** | Medium | Games with multiple animation types needing coordination |
| **BgaAnimations shared service** | Medium-High | Card games using BgaCards, rich animations |

### 7.2 Manager-Local Animation Helpers

The simplest approach: each manager has its own animation methods:

```js
class BoardManager {
    async animatePlaceTile(x, y, tileType) {
        const cell = this.getCell(x, y);
        const tile = this.createTileElement(tileType);

        cell.appendChild(tile);
        tile.offsetHeight;  // force reflow
        tile.classList.add('tile-visible');

        return this.waitForTransition(tile);
    }

    waitForTransition(el) {
        return new Promise(resolve => {
            const onEnd = () => { el.removeEventListener('transitionend', onEnd); resolve(); };
            el.addEventListener('transitionend', onEnd);
            setTimeout(resolve, 600);
        });
    }
}
```

**Pros:** Simple, no shared state, clear ownership.
**Cons:** Duplication if multiple managers need similar helpers.

### 7.3 Dedicated AnimationManager

A shared service for common animation patterns:

```js
class AnimationManager {
    constructor(game) {
        this.game = game;
        this.activeAnimations = new Set();
    }

    // Card-style slide animation
    async slideElement(el, fromRect, toRect, duration = 300) {
        el.style.position = 'fixed';
        el.style.left = fromRect.left + 'px';
        el.style.top = fromRect.top + 'px';
        el.style.width = fromRect.width + 'px';
        el.style.height = fromRect.height + 'px';
        el.style.transition = `all ${duration}ms ease`;

        el.offsetHeight;  // force reflow
        el.style.left = toRect.left + 'px';
        el.style.top = toRect.top + 'px';
        el.style.width = toRect.width + 'px';
        el.style.height = toRect.height + 'px';

        const promise = this.waitForTransition(el, duration);
        this.activeAnimations.add(promise);
        return promise.finally(() => this.activeAnimations.delete(promise));
    }

    // Fade in
    async fadeIn(el, duration = 200) {
        el.style.opacity = '0';
        el.style.display = 'block';
        el.offsetHeight;
        el.style.transition = `opacity ${duration}ms ease`;
        el.style.opacity = '1';
        return this.waitForTransition(el, duration);
    }

    // Fade out
    async fadeOut(el, duration = 200) {
        el.style.transition = `opacity ${duration}ms ease`;
        el.style.opacity = '0';
        return this.waitForTransition(el, duration).then(() => {
            el.style.display = 'none';
        });
    }

    waitForTransition(el, maxDuration) {
        return new Promise(resolve => {
            const handler = () => { el.removeEventListener('transitionend', handler); resolve(); };
            el.addEventListener('transitionend', handler);
            setTimeout(resolve, maxDuration + 50);
        });
    }

    cancelAll() {
        for (const anim of this.activeAnimations) {
            // BgaAnimations handles cleanup internally
        }
        this.activeAnimations.clear();
    }

    isAnimating() {
        return this.activeAnimations.size > 0;
    }
}
```

### 7.4 BgaAnimations Shared Service

The BgaAnimations framework library provides a full animation system:

```js
// Setup (shared)
const BgaAnimations = await importEsmLib('bga-animations', '1.x');

this.bgaAnimationMgr = new BgaAnimations.Manager({
    animationsActive: () => this.game.bga.gameui.bgaAnimationsActive(),
});

this.bgaAnimationMgr.onAnimStart(() => {
    // Animation started — framework can show loading indicator
});

this.bgaAnimationMgr.onAnimEnd(() => {
    // All animations complete — framework can hide indicator
});
```

See [bga-developer-handbook.md §11](./bga-developer-handbook.md#11-advanced-bga-components).

---

## 8. BgaAnimations

### 8.1 Architecture

The BgaAnimations library provides three layers:

```
BgaAnimations.Manager      ← scheduling, lifecycle, callbacks
       │
       ├── Animation objects   ← individual animation definitions
       │
       └── Animation queue     ← per-manager queue for sequenced animations
```

### 8.2 Manager

The `Manager` class is the entry point:

```js
this.animationManager = new BgaAnimations.Manager({
    animationsActive: () => this.game.bga.gameui.bgaAnimationsActive(),
});
```

**Key methods:**

```js
// Check if animations are globally active
this.animationManager.isActive();

// Clear all queued animations (used on reconnect)
this.animationManager.clear();

// Animation lifecycle callbacks
this.animationManager.onAnimStart(callback);
this.animationManager.onAnimEnd(callback);
```

### 8.3 Animation Objects

Animation objects define a single animation:

```js
const anim = new BgaAnimations.Animation({
    duration: 300,
    easing: 'ease-in-out',
    onUpdate: (progress) => {
        // progress: 0 to 1
        element.style.transform = `translateX(${progress * 100}px)`;
    },
    onEnd: () => {
        // Cleanup
    },
});

// Play
this.animationManager.play(anim);

// Returns a Promise that resolves when animation completes
await anim.ready;
```

### 8.4 Animation Composition

Multiple animations can be composed:

```js
// Sequence: play animations one after another
const sequence = new BgaAnimations.SequenceAnimation([anim1, anim2, anim3]);
this.animationManager.play(sequence);

// Parallel: play animations simultaneously
const parallel = new BgaAnimations.ParallelAnimation([anim1, anim2]);
this.animationManager.play(parallel);
```

### 8.5 Animation Sequencing

For complex sequences, BgaAnimations provides built-in sequencing:

```js
const anim1 = new BgaAnimations.Animation({ ... });
const anim2 = new BgaAnimations.Animation({ ... });
const anim3 = new BgaAnimations.Animation({ ... });

// Sequential
await this.animationManager.sequence([anim1, anim2, anim3]);

// Parallel
await this.animationManager.parallel([anim1, anim2, anim3]);

// Custom timing
await this.animationManager.delay(500);
```

---

## 9. BgaCards Integration

### 9.1 Stock Animations

BgaCards stocks have built-in animation support:

```js
// Setup with shared animation manager
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

### 9.2 Card Movement

Moving cards between stocks is animated automatically:

```js
// Single card — animated
async animateCardToTableau(cardId) {
    const card = this.cards[cardId];
    // removeCards and addCards are asynchronous and animated
    await this.handStock.removeCards([card]);
    await this.tableauStock.addCards([card]);
}

// Batch — parallel animation
async animateDrawCards(cards) {
    await Promise.all(cards.map(c =>
        this.deckStock.removeCards([c]).then(() =>
            this.handStock.addCards([c])
        )
    ));
}

// Using BgaCards bundled move:
this.cardsManager.moveCard(card, fromStock, toStock);
```

### 9.3 Decks

Deck animations include optional card flip:

```js
// Drawing from deck — card slides from deck to hand
async animateDrawFromDeck(card) {
    await this.deckStock.removeCards([card]);
    // Optional: card flip animation
    await this.animateCardFlip(card);
    await this.handStock.addCards([card]);
}
```

### 9.4 Hands

Hand stock arranges cards in a fan pattern. Adding/removing cards reflows the fan:

```js
// Adding to hand — fan reflows automatically
await this.handStock.addCards([card]);
// BgaCards handles the fan spacing animation

// Removing from hand — fan closes the gap
await this.handStock.removeCards([card]);
```

### 9.5 Line Stocks

Line stocks arrange cards in a row. Useful for tableaus and displays:

```js
// Adding to line — cards slide into position
await this.tableauStock.addCards([card]);
// Existing cards may shift to make room

// Removing from line — cards slide to fill gap
await this.tableauStock.removeCards([card]);
```

### 9.6 Custom Stocks

For game-specific stock behaviour, extend BgaCards stocks:

```js
class MyCustomStock extends BgaCards.LineStock {
    constructor(manager, container, options = {}) {
        super(manager, container);
        this.rows = options.rows || 1;
    }

    // Custom animation for this stock
    async addCards(cards) {
        await super.addCards(cards);
        // Custom positioning logic
        this.adjustForRows();
    }
}
```

---

## 10. Performance

### 10.1 DOM Batching

Batch DOM reads and writes around animation setup:

```js
// BAD: interleaved reads and writes during animation setup
for (const card of cards) {
    const rect = this.handStock.getCardElement(card.id).getBoundingClientRect();  // read
    this.animationTarget.style.left = rect.left + 'px';  // write
    const rect2 = this.tableauStock.getCardElement(card.id).getBoundingClientRect(); // read
    this.animationTarget.style.top = rect2.top + 'px';  // write
}

// GOOD: batch reads, then batch writes
const positions = {};
for (const card of cards) {
    positions[card.id] = {
        from: this.handStock.getCardElement(card.id).getBoundingClientRect(),
        to: this.tableauStock.getCardElement(card.id).getBoundingClientRect(),
    };
}
// All reads complete — now write
for (const [id, pos] of Object.entries(positions)) {
    this.animationTarget.style.left = pos.from.left + 'px';
    // ...
}
```

### 10.2 Layout Thrashing

Avoid properties that force layout recalculations during animation:

```js
// BAD: reading layout properties in animation loop
function animate(el, duration) {
    const start = performance.now();
    function frame(now) {
        const progress = (now - start) / duration;
        el.style.left = (progress * 100) + 'px';
        const width = el.offsetWidth;  // ← forces reflow!
        el.style.width = (width + 1) + 'px';
        if (progress < 1) requestAnimationFrame(frame);
    }
    requestAnimationFrame(frame);
}

// GOOD: CSS transitions / BgaAnimations handles this internally
el.style.transition = 'left 300ms ease';
el.style.left = '100px';
```

### 10.3 GPU Acceleration

Use CSS properties that trigger GPU compositing:

```css
/* GOOD: GPU-accelerated properties */
.card {
    transform: translateX(0);    /* ← GPU accelerated */
    opacity: 1;                  /* ← GPU accelerated */
}

/* AVOID: CPU-layout properties */
.card {
    left: 0;                     /* ← triggers layout */
    top: 0;                      /* ← triggers layout */
}
```

When animating positions, prefer `transform` over `left/top`:

```js
// BAD: layout-triggering animation
el.style.left = '100px';
el.style.top = '200px';

// GOOD: GPU-accelerated animation
el.style.transform = 'translate(100px, 200px)';
```

BgaCards and BgaAnimations use `transform` internally.

### 10.4 CSS Transitions

Prefer CSS transitions over JavaScript-driven animations for simple cases:

```css
.card {
    transition: transform 300ms ease, opacity 200ms ease;
}

.card.played {
    transform: translateY(-20px) scale(1.05);
    opacity: 0.8;
}
```

```js
// Trigger CSS transition
el.classList.add('played');

// Wait for transition to complete
await this.waitForTransition(el);
```

### 10.5 Large Animation Sets

For batch operations involving many elements:

```js
// Batch deal: deal 8 cards to 4 players
async animateBatchDeal(dealMap) {
    const promises = [];
    for (const [playerId, cards] of Object.entries(dealMap)) {
        for (const card of cards) {
            // Offset each card's animation start slightly
            const delay = promises.length * 50;
            promises.push(
                new Promise(resolve => setTimeout(resolve, delay))
                    .then(() => this.animateDealToPlayer(card, playerId))
            );
        }
    }
    return Promise.all(promises);
}
```

For very large batches (50+ elements), consider:

- **Staggering** animation start times (50-100ms offset)
- **Grouping** elements into batches of 5-10
- **Skipping** animations entirely (fast mode)

---

## 11. Fast Mode

### 11.1 Purpose

Fast mode allows players to skip or accelerate animations. This is essential for:
- Experienced players who have seen the animations many times
- Players with slow connections
- Accessibility (motion sensitivity)
- Mobile devices (performance)

### 11.2 Checking the Preference

```js
class AnimationManager {
    isFastMode() {
        return !this.game.bga.gameui.bgaAnimationsActive()
            || this.game.getLocalPreference('fast_notif') === 1;
    }
}
```

### 11.3 Skipping Animations

When fast mode is active, skip the animation and update the DOM immediately:

```js
class CardManager {
    async animatePlayCard(cardId) {
        if (this.game.animationMgr.isFastMode()) {
            // Skip animation — move DOM instantly
            this.moveCardDirectly(cardId, 'tableau');
            return;
        }

        // Animate normally
        const card = this.cards[cardId];
        await this.handStock.removeCards([card]);
        await this.tableauStock.addCards([card]);
    }

    moveCardDirectly(cardId, targetLocation) {
        const card = this.cards[cardId];
        // Direct DOM manipulation without animation
        this.container.querySelector(`#card-${cardId}`).remove();
        // or use non-animated stock methods
        this.handStock.removeCards([card], { silent: true });
        this.tableauStock.addCards([card], { silent: true });
    }
}
```

### 11.4 Reduced Animations

Instead of binary on/off, provide a reduced mode:

```js
class AnimationManager {
    getAnimationDuration(baseDuration) {
        if (this.isFastMode()) return 0;
        if (this.isReducedMode()) return Math.min(baseDuration, 150);
        return baseDuration;
    }
}
```

### 11.5 Per-Notification Fast Mode (Earth Pattern)

Earth checks fast mode per notification, skipping animations for other players' actions while animating the player's own:

```js
notif_playerGainSoil(notif) {
    if (this.useFastNotification(notif.args.playerId)) {
        // Skip animation, update value immediately
        this.playerBoardMgr.setSoilCount(notif.args.playerId, notif.args.totalSoilCount);
        return;
    }
    // Animate
    return this.playerBoardMgr.animateGainSoil(notif.args.playerId, notif.args.totalSoilCount);
}

useFastNotification(playerId) {
    if (this.animationMgr.isFastMode()) return true;
    // Only animate own actions fully
    return playerId !== this.playerId;
}
```

See [notification-patterns.md §13.4](./notification-patterns.md#134-fast-mode-earth-pattern).

### 11.6 Accessibility

Provide a player preference for animation reduction:

```json
// gamepreferences.jsonc
{
    "100": {
        "name": "Animation speed",
        "values": {
            "1": { "name": "Full animations", "tmdisplay": "Full" },
            "2": { "name": "Reduced animations", "tmdisplay": "Reduced" },
            "3": { "name": "No animations", "tmdisplay": "None" }
        },
        "default": 1
    }
}
```

```js
getAnimationPreference() {
    return this.game.getLocalPreference('animation_speed') || 1;
}

shouldAnimate() {
    return this.getAnimationPreference() !== 3;
}

getScaleFactor() {
    const pref = this.getAnimationPreference();
    return pref === 1 ? 1.0 : pref === 2 ? 0.3 : 0;
}
```

---

## 12. Reconnect

### 12.1 Cancelling Animations

When a reconnect or refreshUI occurs, all in-progress animations must be cancelled immediately:

```js
notif_refreshUI(notif) {
    // 1. Cancel all animations
    this.animationMgr.cancelAll();
    this.cardMgr.cancelAnimations();

    // 2. Remove transitional CSS classes
    this.container.querySelectorAll('.animating, .placing, .fading')
        .forEach(el => el.classList.remove('animating', 'placing', 'fading'));

    // 3. Rebuild from snapshot
    const datas = notif.args.datas;
    this.cardMgr.reset(datas.cards);
    this.boardMgr.reset(datas.board);
    this.playerPanelMgr.reset(datas.players);
}
```

### 12.2 Rebuilding UI

After cancelling animations, the UI is rebuilt from the snapshot. No animation recovery is needed because the state cache is the authoritative snapshot:

```js
class CardManager {
    reset(cardsData) {
        // Cancel any pending BgaCards animations
        this.cardsManager?.getAnimManager()?.clear();

        // Clear stocks
        this.handStock.removeAll();
        this.tableauStock.removeAll();

        // Repopulate from fresh data (no animation)
        this.cards = cardsData;
        const cards = Object.values(cardsData);
        this.handStock.addCards(cards.filter(c => c.location === 'hand'), { silent: true });
        this.tableauStock.addCards(cards.filter(c => c.location === 'play'), { silent: true });
    }
}
```

### 12.3 Animation Recovery

Animation recovery is not needed — the state is rebuilt from scratch. However, the animation system must be in a clean state:

```js
class AnimationManager {
    reset() {
        this.cancelAll();
        // Clear BgaAnimations queue
        this.bgaAnimations?.clear();
        // Reset internal state
        this.activeAnimations.clear();
        this.animationCounter = 0;
    }
}
```

### 12.4 Reconnect During Animation Diagram

```
ANIMATION IN PROGRESS
       │
       │  (card mid-flight between hand and tableau)
       │
       ▼
RECONNECT TRIGGERED (page refresh or disconnect)
       │
       ▼
┌─────────────────────────────────────────┐
│ 1. Page reloads                           │
│    old Game.js destroyed                  │
│    old DOM discarded                      │
│    old animation state lost              │
└─────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│ 2. getAllDatas() called                  │
│    Returns: card.location = 'play'       │
│    (the correct state, as committed      │
│     by the server transaction)           │
└─────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│ 3. setup(gamedatas)                      │
│    Game.js creates new managers          │
│    CardManager.reset()                   │
│      → cards = { 42: { loc: 'play' } }  │
│      → renders card in tableau           │
│      → NO ANIMATION (fresh setup)        │
└─────────────────────────────────────────┘
       │
       ▼
┌─────────────────────────────────────────┐
│ 4. Client ready                          │
│    Card is correctly in tableau          │
│    No trace of interrupted animation     │
└─────────────────────────────────────────┘
```

---

## 13. Spectators

### 13.1 Different Animation Policies

Spectators should receive the same notification stream but may have different animation policies:

```js
notif_cardPlayed(notif) {
    if (this.bga.players.isCurrentPlayerSpectator()) {
        // Spectator: skip card movement animation
        this.cardMgr.moveToFinalPosition(notif.args.card_id);
        return;
    }
    // Player: animate
    return this.cardMgr.animatePlayCard(notif.args.card_id);
}
```

### 13.2 Late Join

A spectator joining late does not see animations. The setup from `getAllDatas()` renders the current state without animation:

```js
setup(gamedatas) {
    // ... initialization ...
    this.cardMgr.setup(gamedatas.cards);
    // Cards appear in their current positions — no entrance animations
}
```

### 13.3 Replay

During gamelog replay, spectators should skip animations for efficiency:

```js
setupNotifications() {
    const isSpectator = this.bga.players.isCurrentPlayerSpectator();
    this.bga.notifications.setupPromiseNotifications({
        minDuration: isSpectator ? 1 : 300,  // Faster replay for spectators
        minDurationNoText: 1,
        ignoreNotifications: isSpectator ? [
            ['cardPlayed', () => true],           // Skip all card animations
            ['gainResources', () => true],        // Skip all gain animations
        ] : [],
    });
}
```

---

## 14. Anti-Patterns

### 14.1 Animation Modifies State

**Symptom:** An animation handler updates the state cache after the animation completes, leaving a window where the cache is wrong.

```js
// BAD: state updated after animation
async notif_cardPlayed(notif) {
    await this.cardMgr.animateSlide(notif.args.card_id);  // Animation first
    this.cards[notif.args.card_id].location = 'play';     // State after ← WRONG
}
```

**Solution:** Update state cache before the animation starts. See §2.1.

### 14.2 DOM-Driven State

**Symptom:** Game state is read from the DOM instead of the state cache.

```js
// BAD: reading state from DOM
getCardLocation(cardId) {
    const el = document.getElementById(`card-${cardId}`);
    return el.closest('.hand') ? 'hand' : 'tableau';  // ← fragile, DOM may be mid-animation
}

// GOOD: reading state from cache
getCardLocation(cardId) {
    return this.cards[cardId].location;  // ← always correct
}
```

### 14.3 Blocking Animations

**Symptom:** A single long animation blocks the entire notification queue, causing the UI to appear frozen.

```js
// BAD: 5-second animation blocks all notifications
async notif_slowEffect(notif) {
    await this.wait(5000);  // ← queue blocked for 5 seconds
}
```

**Solution:** Break long animations into steps using server-side notifications:

```php
// SERVER: send multiple notifications for a long effect
$this->notifications->effectStarted($player);
$this->bga->notify->all('simplePause', '', ['time' => 1500]);
$this->notifications->effectMidpoint($player);
$this->bga->notify->all('simplePause', '', ['time' => 1500]);
$this->notifications->effectCompleted($player);
```

### 14.4 Nested Callbacks

**Symptom:** Animation sequencing uses nested callbacks instead of Promises.

```js
// BAD: nested callbacks
notif_cardPlayed(notif) {
    this.cardMgr.animateSlide(notif.args.card_id, () => {
        this.playerPanelMgr.animateScore(notif.args.score, () => {
            this.boardMgr.animateEffect(notif.args.effect, () => {
                // ...
            });
        });
    });
}
```

**Solution:** Use async/await with Promises:

```js
// GOOD: async/await
async notif_cardPlayed(notif) {
    await this.cardMgr.animateSlide(notif.args.card_id);
    await this.playerPanelMgr.animateScore(notif.args.score);
    await this.boardMgr.animateEffect(notif.args.effect);
}
```

### 14.5 Long Animation Chains

**Symptom:** A single notification triggers 10+ sequential animations, taking 5+ seconds total.

```js
// BAD: too many sequential animations
async notif_complex(notif) {
    await this.cardMgr.animateA();
    await this.cardMgr.animateB();
    await this.cardMgr.animateC();
    await this.cardMgr.animateD();
    await this.cardMgr.animateE();
    // ... queue blocked for many seconds
}
```

**Solution:** Group related animations into parallel batches, or move the sequencing to the server side using `simplePause`.

### 14.6 Mixed Ownership

**Symptom:** Two managers animate the same element.

```js
// BAD: CardManager and BoardManager both animate the same card
class CardManager {
    animatePlayCard(cardId) {
        const el = document.getElementById(`card-${cardId}`);
        this.animateSlide(el, 'hand', 'tableau');  // ← moves element
    }
}

class BoardManager {
    animatePlayCard(cardId) {
        const el = document.getElementById(`card-${cardId}`);
        this.animateBounce(el);  // ← also moves same element!
    }
}
```

**Solution:** Each element is owned by exactly one manager. Transfer ownership before the other manager touches it. See §3.3.

### 14.7 Animations That Affect Gameplay

**Symptom:** Animation timing influences game state.

```js
// BAD: game logic depends on animation completion
notif_cardPlayed(notif) {
    // Wait for reveal animation, then show choices
    await this.cardMgr.animateReveal(notif.args.cardId);
    // The reveal animation must complete before showing choices
    // If animation is skipped (fast mode), choices appear immediately
    // This is acceptable ONLY if the server sends choices in a separate notification
}
```

**Solution:** The server controls timing through the notification stream. The client never waits for animations to make gameplay decisions. If choices depend on a card being revealed, the server sends the choice state only after the reveal is committed.

---

## 15. Templates

### 15.1 Canonical Animation Manager

```js
class AnimationManager {
    constructor(game) {
        this.game = game;
        this.activeAnimations = new Set();

        // BgaAnimations setup
        this.initBgaAnimations();
    }

    async initBgaAnimations() {
        const BgaAnimations = await importEsmLib('bga-animations', '1.x');
        this.bgaAnimations = new BgaAnimations.Manager({
            animationsActive: () => this.game.bga.gameui.bgaAnimationsActive(),
        });
    }

    // ── ANIMATION HELPERS ──

    async slideTo(element, fromRect, toRect, duration = 300) {
        element.style.position = 'fixed';
        element.style.left = fromRect.left + 'px';
        element.style.top = fromRect.top + 'px';
        element.style.width = fromRect.width + 'px';
        element.style.height = fromRect.height + 'px';
        element.style.transition = `all ${duration}ms ease-in-out`;
        element.style.zIndex = '1000';

        element.offsetHeight;  // force reflow

        element.style.left = toRect.left + 'px';
        element.style.top = toRect.top + 'px';
        element.style.width = toRect.width + 'px';
        element.style.height = toRect.height + 'px';

        return this.track(this.waitForTransition(element, duration));
    }

    async fadeIn(element, duration = 200) {
        element.style.display = '';
        element.style.opacity = '0';
        element.offsetHeight;
        element.style.transition = `opacity ${duration}ms ease`;
        element.style.opacity = '1';
        return this.track(this.waitForTransition(element, duration));
    }

    async fadeOut(element, duration = 200) {
        element.style.transition = `opacity ${duration}ms ease`;
        element.style.opacity = '0';

        await this.waitForTransition(element, duration);
        element.style.display = 'none';
    }

    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    // ── TRACKING ──

    track(promise) {
        const wrapped = promise
            .then(() => this.activeAnimations.delete(wrapped))
            .catch(() => this.activeAnimations.delete(wrapped));
        this.activeAnimations.add(wrapped);
        return wrapped;
    }

    cancelAll() {
        this.bgaAnimations?.clear();
        this.activeAnimations.clear();
    }

    isAnimating() {
        return this.activeAnimations.size > 0;
    }

    // ── FAST MODE ──

    isFastMode() {
        return this.game.bga.gameui && !this.game.bga.gameui.bgaAnimationsActive();
    }

    getDuration(base) {
        return this.isFastMode() ? 0 : base;
    }

    // ── INTERNAL ──

    waitForTransition(element, maxDuration) {
        return new Promise(resolve => {
            const onEnd = () => {
                element.removeEventListener('transitionend', onEnd);
                resolve();
            };
            element.addEventListener('transitionend', onEnd);
            setTimeout(resolve, maxDuration + 100);
        });
    }
}
```

### 15.2 Canonical Animation Helper

For manager-local use without a shared AnimationManager:

```js
// Mixin or base class for managers that need animation
const Animatable = {
    waitForTransition(element, maxDuration = 400) {
        return new Promise(resolve => {
            const handler = () => {
                element.removeEventListener('transitionend', handler);
                resolve();
            };
            element.addEventListener('transitionend', handler);
            setTimeout(resolve, maxDuration + 50);
        });
    },

    animateCSS(element, className, duration = 300) {
        element.classList.add(className);
        return this.waitForTransition(element, duration).then(() => {
            element.classList.remove(className);
        });
    },

    shouldAnimate() {
        return this.game?.bga?.gameui?.bgaAnimationsActive() !== false;
    },
};

// Usage
class CardManager {
    async animateHighlight(cardId) {
        if (!this.shouldAnimate()) return;
        const el = this.getElementById(cardId);
        if (el) await this.animateCSS(el, 'card-highlight-anim', 400);
    }
}
Object.assign(CardManager.prototype, Animatable);
```

### 15.3 Canonical Notification Animation

The standard pattern for an animated notification handler:

```js
notif_cardPlayed(notif) {
    const args = notif.args;

    // 1. Update state cache immediately (always)
    this.cardMgr.updateCardLocation(args.card_id, args.target_location);

    // 2. Check animation conditions
    if (this.animationMgr.isFastMode()) {
        this.cardMgr.moveToFinalPosition(args.card_id);
        return;  // No Promise → queue advances after minDuration
    }

    // 3. Return animation Promise
    return this.cardMgr.animateToLocation(args.card_id, args.target_location);
}
```

### 15.4 Canonical Parallel Animation

```js
notif_drawCards(notif) {
    const args = notif.args;

    // Update cache synchronously
    const cards = args.cards;
    for (const card of cards) {
        this.cardMgr.addCardToCache(card);
    }

    if (this.animationMgr.isFastMode()) {
        this.cardMgr.renderCardsDirectly(cards);
        return;
    }

    // Animate all cards in parallel
    const promises = cards.map(card =>
        this.cardMgr.animateDealFromDeck(card)
    );
    return Promise.all(promises);
}

// Sequential version (if order matters)
async notif_multiStepEffect(notif) {
    // Step 1: reveal card
    await this.cardMgr.animateReveal(notif.args.card_id);

    // Step 2: show effect
    await this.boardMgr.animateEffect(notif.args.effect_x, notif.args.effect_y);

    // Step 3: update score
    await this.playerPanelMgr.animateScoreChange(notif.args.player_id, notif.args.score);
}
```

---

## 16. Checklists

### 16.1 Architecture Review

- [ ] State cache is updated BEFORE animation starts (never after)
- [ ] Animation state is separate from game state
- [ ] Animation never modifies authoritative state
- [ ] Each DOM element is animated by exactly one manager
- [ ] AnimationManager is a shared service, not an owner
- [ ] BgaAnimations Manager is shared across BgaCards stocks
- [ ] Notification handlers return Promises for async animations
- [ ] Synchronous handlers do not block the notification queue
- [ ] `Promise.all` is used for parallel animations within one notification
- [ ] Sequential animations within one notification use async/await
- [ ] Long animation sequences are broken across server notifications
- [ ] No nested callbacks (use Promises)
- [ ] Animations are cancellable on reconnect
- [ ] Fast mode skips all animations and updates DOM directly
- [ ] Animations are disabled during gamelog replay for spectators

### 16.2 Performance Review

- [ ] CSS transitions preferred over JavaScript-driven animations
- [ ] GPU-accelerated properties used (`transform`, `opacity`, not `left`/`top`)
- [ ] DOM reads and writes are batched (no layout thrashing)
- [ ] Large animation batches are staggered (50-100ms offset)
- [ ] `requestAnimationFrame` is used for custom JS animations
- [ ] `DocumentFragment` is used for batch element creation
- [ ] No forced reflows in animation loops (`offsetWidth`, `offsetHeight`)
- [ ] Animation durations are reasonable (200-500ms typical)
- [ ] Fast mode provides instant feedback
- [ ] Reduced mode provides abbreviated animations
- [ ] Memory: animation elements are cleaned up after completion
- [ ] No memory leaks from unresolved Promises

### 16.3 UX Review

- [ ] Animations are not essential for gameplay (all info available without them)
- [ ] Animations have a clear purpose (not decorative)
- [ ] Card movements follow predictable paths
- [ ] Counter animations show the final value (not just the delta)
- [ ] Animation duration respects the notification framework's minDuration
- [ ] Fast mode is available as a player preference
- [ ] Reduced animation mode is available for accessibility
- [ ] Reconnect produces the correct state without animation artifacts
- [ ] Spectators have appropriate animation policies
- [ ] Hover effects are CSS-driven, not JS animation
- [ ] Dialogs have simple open/close transitions
- [ ] The UI does not feel "slow" due to excessive animation

---

## References

- [client-ui-architecture.md](./client-ui-architecture.md) — animation integration (§12), manager pattern (§5), state vs UI (§11), performance (§13)
- [client-synchronization-architecture.md](./client-synchronization-architecture.md) — notification lifecycle (§5), queue processing (§5.3), client consistency (§9)
- [notification-patterns.md](./notification-patterns.md) — notification sequencing (§7), minimum duration (§7.3), fast mode (§13.4), simplePause (§7.4)
- [game-flow-architecture.md](./game-flow-architecture.md) — execution pipeline (§2), transaction model, client processing (§2.2 step 8)
- [state-machine-architecture.md](./state-machine-architecture.md) — state transitions, args, _no_notify for skip states
- [bga-developer-handbook.md](../foundation/bga-developer-handbook.md) — BgaAnimations API (§11), BgaCards (§7), Counters
- [bga-ai-implementation-reference.md](../bga-ai-implementation-reference.md) — BgaAnimations setup (§11), ESM imports (§14)
- [reference-project-analysis.md](../foundation/reference-project-analysis.md) — Earth animation patterns (§4.2), client architecture ratings (§5)
