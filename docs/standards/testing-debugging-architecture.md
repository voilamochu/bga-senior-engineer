# BGA Testing & Debugging Architecture — Engineering Standard

**Document purpose:** Define the complete engineering methodology for verifying, debugging, validating, profiling, and maintaining Board Game Arena implementations throughout development. This is the canonical reference for how professional BGA engineers build confidence that a game is correct.

**Applicability:** All new BGA game implementations. Existing projects should use this document as a reference when building test infrastructure, debugging state issues, or preparing for release.

**Cross-references:**
- [domain-architecture.md](./domain-architecture.md) — testing implications (§15), layered architecture (§2), dependency rules (§14)
- [action-architecture.md](./action-architecture.md) — validation architecture (§6), error handling (§12), undo implications (§13)
- [persistence-architecture.md](./persistence-architecture.md) — transactions (§9), concurrency (§10), migration strategy (§15)
- [state-machine-architecture.md](./state-machine-architecture.md) — debugging state machines (§16), state lifecycle (§4), zombie (§13.6)
- [game-flow-architecture.md](./game-flow-architecture.md) — execution pipeline (§2), error handling (§10), transaction boundaries (§5)
- [client-synchronization-architecture.md](./client-synchronization-architecture.md) — notification lifecycle (§5), reconnect verification (§6)
- [client-ui-architecture.md](./client-ui-architecture.md) — rendering (§7), performance (§13)
- [animation-architecture.md](./animation-architecture.md) — animation testing, fast mode verification
- [notification-patterns.md](./notification-patterns.md) — notification verification, i18n testing, replay
- [reference-project-analysis.md](../foundation/reference-project-analysis.md) — debugging infrastructure ratings, Agricola seed loading
- [bga-developer-handbook.md](../foundation/bga-developer-handbook.md) — PHPUnit setup, pre-release checklist
- [bga-ai-implementation-reference.md](../foundation/bga-ai-implementation-reference.md) — testing and debugging (§18)

---

## Table of Contents

- [1. Testing Philosophy](#1-testing-philosophy)
- [2. Testing Pyramid](#2-testing-pyramid)
- [3. Server Testing](#3-server-testing)
- [4. Client Testing](#4-client-testing)
- [5. Notification Verification](#5-notification-verification)
- [6. State Machine Verification](#6-state-machine-verification)
- [7. Synchronization Validation](#7-synchronization-validation)
- [8. Debugging Architecture](#8-debugging-architecture)
- [9. Replay and Reproduction](#9-replay-and-reproduction)
- [10. Performance Validation](#10-performance-validation)
- [11. Error Handling](#11-error-handling)
- [12. CI Validation](#12-ci-validation)
- [13. Anti-Patterns](#13-anti-patterns)
- [14. Templates](#14-templates)
- [15. Checklists](#15-checklists)

---

## 1. Testing Philosophy

### 1.1 Five Principles

**Principle 1 — Server Authority.** The server is the sole source of truth. Tests must verify that the server enforces correct state, validates all inputs, and rejects illegal actions. Client tests verify presentation only.

**Principle 2 — Deterministic Execution.** BGA games should produce deterministic results from the same inputs. Tests must be repeatable — same seed, same actions, same outcome. Non-determinism (shuffles, random draws) is controlled through seed management.

**Principle 3 — Small Verifiable Units.** The layered architecture of a well-structured BGA project enables layered testing. Value objects test pure logic. Managers test domain invariants. Actions test orchestration. Each layer is tested independently.

**Principle 4 — Layered Testing.** Each architectural layer has a distinct testing strategy:

| Layer | Testing Strategy | Framework |
|---|---|---|
| **Value Objects** | Pure unit tests | PHPUnit |
| **Models** | Pure unit tests | PHPUnit |
| **Helpers** | Pure unit tests | PHPUnit |
| **Managers** | Integration tests with mock Game | PHPUnit |
| **Actions** | Integration through state-action pipeline | PHPUnit |
| **State classes** | Integration with state machine | PHPUnit |
| **Notifications** | Payload verification | PHPUnit + manual |
| **Client code** | Visual verification + manual | Manual + screenshots |
| **Full game** | Seed-driven simulation | Manual + replay |

**Principle 5 — Regression Prevention.** Every bug fix adds a test that reproduces the bug. The test passes only when the fix is correct. This prevents the bug from recurring.

---

## 2. Testing Pyramid

### 2.1 The Canonical Testing Pyramid

```
                        ┌──────────┐
                        │  Manual  │
                        │  Play    │  ← Exploratory testing,
                        │  Testing │    acceptance testing
                        └────┬─────┘
                             │
                        ┌────▼─────┐
                        │  Replay  │
                        │  Valid.  │  ← Full game replay from gamelog
                        └────┬─────┘
                             │
                        ┌────▼─────┐
                        │  Full    │
                        │  Game    │  ← Multi-step action sequences
                        │  Flows   │    (seed-driven, deterministic)
                        └────┬─────┘
                             │
              ┌──────────────┼──────────────┐
              │              │              │
         ┌────▼────┐   ┌────▼────┐   ┌────▼────┐
         │  State  │   │ Action  │   │ Notif.  │
         │  Trans. │   │ Tests   │   │ Verif.  │
         └────┬────┘   └────┬────┘   └────┬────┘
              │              │              │
         ┌────▼────┐   ┌────▼────┐   ┌────▼────┐
         │Manager  │   │Manager  │   │Manager  │
         │Tests    │   │Tests    │   │Tests    │
         └────┬────┘   └────┬────┘   └────┬────┘
              │              │              │
         ┌────▼──────────────▼──────────────▼────┐
         │         Unit Tests                     │
         │  Value Objects  │  Models  │  Helpers  │
         └────────────────────────────────────────┘
```

### 2.2 Test Distribution

| Layer | Tests | Run Frequency | Automation |
|---|---|---|---|
| **Unit tests** | Hundreds | Every commit | Full automatic |
| **Manager tests** | 50-200 | Every commit | Full automatic |
| **Action tests** | 20-100 | Every commit | Full automatic |
| **State transition tests** | 10-50 | Every commit | Full automatic |
| **Notification verification** | Per notification type | Per PR | Semi-automatic |
| **Full game flows** | 5-20 per scenario | Per release | Semi-automatic |
| **Replay validation** | Archive-dependent | Per release | Automatic |
| **Manual play testing** | As needed | Continuous | Manual |

### 2.3 What Each Layer Tests

| Layer | Tests | Does NOT Test |
|---|---|---|
| **Unit** | Pure logic: arithmetic on Resources, Card cost computation, enum validation | Database, framework, state machine |
| **Manager** | CRUD operations, invariant enforcement, validation methods | Cross-manager orchestration, full action pipeline |
| **Action** | Validation rejection, delegation correctness, transition return | Database isolation, notification delivery |
| **State** | Transition legality, arg computation, auto-resolve logic | Full state machine graph |
| **Notification** | Payload completeness, i18n arrays, private data isolation | Client handler execution |
| **Full game** | Multi-step action sequences, end-game scoring, edge cases | All UI rendering |
| **Replay** | Archive correctness, regression detection | New feature behaviour |

---

## 3. Server Testing

### 3.1 Value Object Tests

Value objects have zero framework dependencies and are the most testable components:

```php
final class ResourcesTest extends TestCase
{
    public function testAdd(): void
    {
        $a = new Resources(['coin' => 3, 'wood' => 1]);
        $b = new Resources(['coin' => 2, 'stone' => 1]);
        $result = $a->add($b);
        $this->assertTrue($result->equals(new Resources([
            'coin' => 5, 'wood' => 1, 'stone' => 1,
        ])));
    }

    public function testImmutability(): void
    {
        $a = new Resources(['coin' => 3]);
        $a->add(new Resources(['coin' => 2]));
        // Original unchanged
        $this->assertEquals(3, $a->get('coin'));
    }

    public function testHasAtLeast(): void
    {
        $a = new Resources(['coin' => 5, 'wood' => 2]);
        $this->assertTrue($a->hasAtLeast(new Resources(['coin' => 3])));
        $this->assertFalse($a->hasAtLeast(new Resources(['coin' => 6])));
    }

    public function testNegativeValuesRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Resources(['coin' => -1]);
    }
}
```

### 3.2 Model Tests

Models wrap data and compute derived values:

```php
final class CardTest extends TestCase
{
    public function testGetCost(): void
    {
        $card = new Card(
            id: 1, type: 'farm', typeArg: '1',
            location: 'hand', locationArg: 0
        );
        $cost = $card->getCost();
        $this->assertInstanceOf(Resources::class, $cost);
        $this->assertTrue($cost->hasAtLeast(new Resources(['coin' => 1])));
    }

    public function testIsPlayable(): void
    {
        $hand = new Card(1, 'farm', '1', 'hand', 0);
        $play = new Card(2, 'farm', '1', 'play', 0);
        $this->assertTrue($hand->isPlayable());
        $this->assertFalse($play->isPlayable());
    }

    public function testToUi(): void
    {
        $card = new Card(1, 'farm', '1', 'hand', 0);
        $ui = $card->toUi();
        $this->assertSame(1, $ui['id']);
        $this->assertSame('hand', $ui['location']);
        $this->assertArrayNotHasKey('cost', $ui); // Not in UI output
    }
}
```

### 3.3 Manager Tests

Manager tests require a mock or lightweight Game instance:

```php
final class CardsManagerTest extends TestCase
{
    private Game $game;
    private Cards $cards;

    protected function setUp(): void
    {
        $this->game = $this->createMock(Game::class);
        // Configure mock for DB operations
        $this->cards = new Cards($this->game);
    }

    public function testCreateCard(): void
    {
        $this->game->method('getUniqueValueFromDB')
            ->willReturn(42);

        $card = $this->cards->createCard('farm', '1', 'deck');

        $this->assertNotNull($card);
        $this->assertEquals(42, $card->getId());
    }

    public function testPlayCardMovesToPlayLocation(): void
    {
        $this->cards->createCard('farm', '1', 'hand', 1);
        $card = $this->cards->playCard(42, 1);

        $this->assertEquals('play', $card->getLocation());
    }

    public function testPlayCardThrowsIfNotInHand(): void
    {
        $this->expectException(\BgaUserException::class);
        $this->cards->playCard(999, 1);
    }
}
```

For managers that use static methods (Agricola/ArkNova pattern), testing requires a different approach — typically through integration tests that exercise the Engine:

```php
final class GainActionTest extends TestCase
{
    public function testGainResources(): void
    {
        // Setup: create tree with Gain node
        $tree = new SeqNode([
            new Gain(Resources::fromArray(['coin' => 3])),
        ]);
        Engine::buildFromTree($tree, 1);

        // Execute
        Engine::proceed();

        // Verify
        $player = Players::get(1);
        $this->assertEquals(3, $player->getCoins());
    }
}
```

### 3.4 Action Tests

Actions validate parameters, delegate to managers, and return transitions:

```php
final class ActPlayCardTest extends TestCase
{
    public function testValidActionReturnsTransition(): void
    {
        $state = $this->createState();
        $result = $state->actPlayCard(42, 1, []);

        $this->assertEquals('cardPlayed', $result);
    }

    public function testInvalidCardThrows(): void
    {
        $this->expectException(\BgaUserException::class);
        $state = $this->createState();
        $state->actPlayCard(999, 1, []);
    }

    private function createState(): PlayerTurn
    {
        $game = $this->createMock(Game::class);
        return new PlayerTurn($game);
    }
}
```

### 3.5 State Tests

State tests verify args computation and auto-resolve:

```php
final class PlayerTurnStateTest extends TestCase
{
    public function testGetArgsIncludesPlayableCards(): void
    {
        $state = $this->createState();
        $args = $state->getArgs(1);

        $this->assertArrayHasKey('playableCards', $args);
    }

    public function testAutoResolveWhenNoCards(): void
    {
        $state = $this->createState();
        $args = $state->getArgs(1);

        // If no playable cards, _no_notify is set
        if (empty($args['playableCards'])) {
            $this->assertTrue($args['_no_notify']);
        }
    }
}
```

### 3.6 Persistence Tests

Test that database operations are correct and transactional:

```php
final class TransactionTest extends TestCase
{
    public function testRollbackOnException(): void
    {
        $manager = new Cards($this->createMock(Game::class));

        try {
            $manager->beginTransaction();
            $manager->createCard('farm', '1', 'hand', 1);
            throw new \Exception('Simulated failure');
            $manager->commit();  // Never reached
        } catch (\Exception $e) {
            $manager->rollback();
        }

        // Card should not exist
        $this->assertNull($manager->find(1));
    }

    public function testAtomicUpdate(): void
    {
        // Test that conditional UPDATE works as expected
        $result = $this->game->DbQuery(
            "UPDATE card SET card_location = 'play'
             WHERE card_id = 42 AND card_location = 'hand'"
        );
        $this->assertEquals(1, $result);
    }
}
```

### 3.7 Notification Tests

Verify notification payloads are correct:

```php
final class NotificationsTest extends TestCase
{
    public function testCardPlayedPayload(): void
    {
        $player = $this->createMock(Player::class);
        $player->method('getId')->willReturn(1);
        $player->method('getName')->willReturn('Alice');

        // Capture the notification
        $captured = [];
        Game::get()->expects($this->once())
            ->method('notifyAllPlayers')
            ->willReturnCallback(function ($type, $log, $args) use (&$captured) {
                $captured = compact('type', 'log', 'args');
            });

        Notifications::cardPlayed($player, 42, 'Farm');

        $this->assertEquals('cardPlayed', $captured['type']);
        $this->assertArrayHasKey('card_id', $captured['args']);
        $this->assertEquals(42, $captured['args']['card_id']);
        $this->assertContains('card_name', $captured['args']['i18n']);
    }
}
```

---

## 4. Client Testing

### 4.1 Testing Constraints

Client testing in BGA is primarily manual due to:
- The Dojo/ESM framework dependency
- The BGA platform environment (not easily headless)
- DOM-based rendering that is tightly coupled to the framework

**Strategy:** Focus on structural correctness (caches, state management) over visual correctness. Use manual testing for visual verification.

### 4.2 Manager State Tests

Client managers maintain state caches. These can be tested structurally:

```js
// cardMgr.test.js (conceptual)
describe('CardManager', () => {
    let mgr;

    beforeEach(() => {
        mgr = new CardManager(mockGame, 'container');
        mgr.setup({
            1: { id: 1, location: 'hand', locationArg: 1 },
            2: { id: 2, location: 'deck', locationArg: 0 },
        });
    });

    it('updates cache on card played', () => {
        mgr.onCardPlayed({ card_id: 1, target_location: 'play' });
        expect(mgr.cards[1].location).toBe('play');
    });

    it('maintains idempotent handlers', () => {
        mgr.onCardPlayed({ card_id: 1, target_location: 'play' });
        mgr.onCardPlayed({ card_id: 1, target_location: 'play' }); // replay
        expect(mgr.cards[1].location).toBe('play'); // No error
    });
});
```

### 4.3 Selection Tests

Selection state is pure logic and easy to test:

```js
describe('SelectionManager', () => {
    let sel;

    beforeEach(() => {
        sel = new SelectionManager(mockGame);
        sel.setSelectable([1, 2, 3]);
    });

    it('selects an item', () => {
        sel.select(2);
        expect(sel.getSelected()).toBe(2);
    });

    it('deselects on new selection', () => {
        sel.select(1);
        sel.select(2);
        expect(sel.getSelected()).toBe(2);
        expect(sel.isSelected(1)).toBe(false);
    });

    it('rejects selection of non-selectable item', () => {
        sel.select(99);
        expect(sel.getSelected()).toBeNull();
    });

    it('clears all selection state', () => {
        sel.select(1);
        sel.clear();
        expect(sel.getSelected()).toBeNull();
        expect(sel.getSelectables()).toEqual([]);
    });
});
```

### 4.4 Dialog Tests

Dialog logic is callbacks and visibility:

```js
describe('DialogManager', () => {
    it('shows dialog and calls confirm callback', () => {
        const onConfirm = jest.fn();
        mgr.confirm('Proceed?', onConfirm);

        const btn = document.querySelector('.dialog-buttons button:first-child');
        btn.click();

        expect(onConfirm).toHaveBeenCalled();
    });

    it('closes on cancel', () => {
        const onCancel = jest.fn();
        mgr.confirm('Proceed?', jest.fn(), onCancel);

        const btn = document.querySelector('.dialog-buttons button:last-child');
        btn.click();

        expect(onCancel).toHaveBeenCalled();
        expect(document.querySelector('.dialog-overlay')).toBeNull();
    });
});
```

### 4.5 Animation Tests

Animation logic is verified structurally:

```js
describe('AnimationManager', () => {
    it('skips animation in fast mode', () => {
        animMgr.setFastMode(true);
        const result = animMgr.getDuration(300);
        expect(result).toBe(0);
    });

    it('tracks active animations', async () => {
        const promise = animMgr.track(Promise.resolve());
        expect(animMgr.isAnimating()).toBe(true);

        await promise;
        expect(animMgr.isAnimating()).toBe(false);
    });

    it('cancels all animations', () => {
        animMgr.track(new Promise(() => {})); // Never resolves
        animMgr.cancelAll();
        expect(animMgr.isAnimating()).toBe(false);
    });
});
```

### 4.6 Reconnect and Spectator Tests

```js
describe('Reconnect handling', () => {
    it('resets manager state from snapshot', () => {
        mgr.setup({ 1: { id: 1, location: 'hand' } });
        mgr.reset({ 1: { id: 1, location: 'play' } });
        expect(mgr.cards[1].location).toBe('play');
    });

    it('clears stocks on reset', () => {
        mgr.setup({ 1: { id: 1, location: 'hand' } });
        mgr.reset({});
        expect(mgr.handStock.getAllCards()).toHaveLength(0);
    });
});

describe('Spectator handling', () => {
    it('skips private notification handlers', () => {
        spectatorGame.bga.players.isCurrentPlayerSpectator = () => true;
        const handler = notif_pDrawCards.bind(spectatorGame);
        // Should not throw
        expect(() => handler({ args: { cards: [] } })).not.toThrow();
    });
});
```

### 4.7 Fast Mode Tests

```js
describe('Fast mode', () => {
    it('renders directly without animation', () => {
        animMgr.setFastMode(true);
        const spy = jest.spyOn(mgr, 'moveCardDirectly');

        mgr.animatePlayCard(1);

        expect(spy).toHaveBeenCalledWith(1, 'tableau');
    });
});
```

---

## 5. Notification Verification

### 5.1 Payload Validation

Every notification must be verified for:

```php
// Notification audit test
final class NotificationAuditTest extends TestCase
{
    /**
     * @dataProvider notificationProvider
     */
    public function testNotificationPayload(string $method, array $args): void
    {
        $captured = [];
        Game::get()->expects($this->once())
            ->method('notifyAllPlayers')
            ->willReturnCallback(function ($type, $log, $data) use (&$captured) {
                $captured = compact('type', 'log', 'data');
            });

        Notifications::$method(...$args);

        // Every notification must have:
        $this->assertNotEmpty($captured['type'], 'Notification type must not be empty');
        $this->assertNotEmpty($captured['log'], 'Log string must not be empty');
        $this->assertStringContainsString('${', $captured['log'], 'Log must use ${placeholders}');

        // Every placeholder must have a corresponding arg
        preg_match_all('/\$\{(\w+)\}/', $captured['log'], $matches);
        foreach ($matches[1] as $placeholder) {
            $this->assertArrayHasKey($placeholder, $captured['data'],
                "Log placeholder \${$placeholder} has no matching arg");
        }

        // i18n array must cover all translatable args
        if (isset($captured['data']['i18n'])) {
            foreach ($captured['data']['i18n'] as $key) {
                $this->assertArrayHasKey($key, $captured['data'],
                    "i18n key '{$key}' not found in args");
            }
        }
    }

    public static function notificationProvider(): array
    {
        $player = new Player(1, 'Alice', 'ff0000');
        return [
            'cardPlayed' => ['cardPlayed', [$player, 42, 'Farm']],
            'gainResources' => ['gainResources', [$player, new Resources(['coin' => 3])]],
            'scoreUpdated' => ['scoreUpdated', [$player, 15]],
        ];
    }
}
```

### 5.2 Ordering Verification

Verify that related notifications are sent in the correct order:

```php
public function testActionNotificationOrder(): void
{
    $order = [];
    Game::get()->expects($this->any())
        ->method('notifyAllPlayers')
        ->willReturnCallback(function ($type) use (&$order) {
            $order[] = $type;
        });

    // Execute action that sends multiple notifications
    $state->actPlayCard(42, 1, []);

    // Assert order
    $this->assertEquals(['cardPlayed', 'scoreUpdated'], $order);
}
```

### 5.3 Visibility Verification

Verify private data is not exposed publicly:

```php
public function testPrivateDataNotInPublicArgs(): void
{
    $captured = [];
    Game::get()->expects($this->once())
        ->method('notifyAllPlayers')
        ->willReturnCallback(function ($type, $log, $data) use (&$captured) {
            $captured = $data;
        });

    Notifications::cardPlayed($player, 42, 'Farm');

    // Private card data must NOT be in the public payload
    $this->assertArrayNotHasKey('hand', $captured);
    $this->assertArrayNotHasKey('private_data', $captured);
}
```

### 5.4 Refresh Consistency

Verify that `refreshUI` produces the same state as the actual notification stream:

```php
public function testRefreshMatchesNotificationState(): void
{
    // Apply action
    $state->actPlayCard(42, 1, []);

    // Get snapshot
    $snapshot = $game->getAllDatas(1);

    // Rebuild from snapshot
    $game->notifications->refreshUI($snapshot);

    // Verify snapshot matches current state
    $this->assertEquals('play', $snapshot['cards'][42]['location']);
}
```

---

## 6. State Machine Verification

### 6.1 Entry Verification

Every state must be reachable from at least one predecessor:

```php
public function testAllStatesReachable(): void
{
    $stateMachine = $this->getStateMachine();
    $reachable = $this->computeReachableStates($stateMachine);

    foreach (StateIds::cases() as $id) {
        $this->assertContains($id->value, $reachable,
            "State {$id->name} ({$id->value}) is unreachable");
    }
}

public function testAllStatesLeadToEnd(): void
{
    $stateMachine = $this->getStateMachine();

    foreach (StateIds::cases() as $id) {
        if ($id === StateIds::GAME_END) continue;
        $paths = $this->findAllPathsToEnd($stateMachine, $id->value);
        $this->assertNotEmpty($paths,
            "State {$id->name} has no path to game end (99)");
    }
}
```

### 6.2 Transition Verification

Every declared transition must map to a valid target:

```php
public function testAllTransitionsValid(): void
{
    foreach ($this->getAllStates() as $stateId => $state) {
        foreach ($state['transitions'] as $key => $target) {
            $this->assertArrayHasKey($target, $this->getAllStates(),
                "State {$stateId} transition '{$key}' targets invalid state {$target}");
        }
    }
}
```

### 6.3 Impossible Transition Verification

Verify that invalid transitions are rejected:

```php
public function testCantPlayCardWhenNotYourTurn(): void
{
    $this->expectException(\BgaSystemException::class);
    // Simulate another player acting
    $state->actPlayCard(42, 2, []); // Player 2 is not active
}
```

### 6.4 Recovery Testing

Test the `ST_IMPOSSIBLE_MANDATORY_ACTION` escape hatch:

```php
public function testImpossibleActionShowsRecovery(): void
{
    // Force a state where no legal action exists
    $this->forceEmptyDeck();
    $this->forceMandatoryDraw();

    $args = $this->getStateArgs();
    $this->assertArrayHasKey('restartPossible', $args);
    $this->assertTrue($args['restartPossible']);
}
```

### 6.5 Terminal State Verification

Every game must eventually reach state 99:

```php
public function testGameEnds(): void
{
    $seed = 12345;
    $actions = $this->loadGameSequence('test_game_end');

    foreach ($actions as $action) {
        $state = $this->executeAction($action);
        if ($state === 99) break;
    }

    $this->assertEquals(99, $state, 'Game did not reach end state');
}
```

---

## 7. Synchronization Validation

### 7.1 getAllDatas Parity

Verify `getAllDatas()` produces correct state after any sequence of actions:

```php
public function testGetAllDatasAfterAction(): void
{
    // Execute an action
    $this->executeAction('actPlayCard', ['cardId' => 42]);

    // Get snapshot
    $datas = $this->game->getAllDatas(1);

    // Verify snapshot matches expected state
    $this->assertEquals('play', $datas['cards'][42]['location']);
    $this->assertEquals(1, $datas['players'][1]['score']);
}

public function testGetAllDatasForSpectator(): void
{
    $datas = $this->game->getAllDatas(null);

    // Spectator should not see private data
    $this->assertArrayNotHasKey('hand', $datas);
}
```

### 7.2 Reconnect Validation

Simulate a reconnect and verify state matches:

```php
public function testReconnectProducesCorrectState(): void
{
    // Save mid-game state
    $expectedState = $this->captureGameState();

    // Simulate reconnect
    $this->simulatePageReload();
    $reconnectedState = $this->game->getAllDatas(1);

    // Must match
    $this->assertEquals($expectedState, $reconnectedState);
}
```

### 7.3 refreshUI Validation

Verify `refreshUI` produces the same visual state as the cumulative notification stream:

```php
public function testRefreshUIIsEquivalent(): void
{
    // Apply several actions
    $this->executeAction('actPlayCard', ['cardId' => 42]);
    $this->executeAction('actPass', []);

    // Get after state
    $afterState = $this->game->getAllDatas(1);

    // Apply refreshUI
    $this->game->notifications->refreshUI($afterState);

    // State must be equivalent
    $this->assertEquals($afterState, $this->game->getAllDatas(1));
}
```

### 7.4 Notification Replay Validation

Verify that replaying notifications produces the correct final state:

```php
public function testNotificationReplay(): void
{
    $initialState = $this->game->getAllDatas(1);

    // Collect notifications from an action
    $notifications = [];
    $this->captureNotifications($notifications);
    $this->executeAction('actPlayCard', ['cardId' => 42]);

    // Reset to initial state
    $this->restoreState($initialState);

    // Replay notifications
    foreach ($notifications as $notif) {
        $this->client->processNotification($notif);
    }

    // State should match
    $this->assertEquals($this->game->getAllDatas(1), $initialState);
}
```

### 7.5 Race Condition Testing

Test concurrency for simultaneous actions:

```php
public function testSimultaneousClaimConflict(): void
{
    // Two players try to claim the same card
    $result1 = $this->simulateConcurrentAction(1, 'actClaimCard', ['cardId' => 42]);
    $result2 = $this->simulateConcurrentAction(2, 'actClaimCard', ['cardId' => 42]);

    // Exactly one should succeed
    $succeeded = array_filter([$result1, $result2], fn($r) => $r['success']);
    $this->assertCount(1, $succeeded);
}
```

---

## 8. Debugging Architecture

### 8.1 Logging Strategy

Structured logging with levels:

```php
class Logger
{
    public static function debug(string $message, array $context = []): void
    {
        if (!self::isDebugMode()) return;
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = $trace[1] ?? [];
        self::write('DEBUG', $message, $context, $caller);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    private static function write(string $level, string $message, array $context, array $caller = []): void
    {
        $entry = [
            'time' => microtime(true),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];
        if ($caller) {
            $entry['file'] = $caller['file'] ?? '';
            $entry['line'] = $caller['line'] ?? '';
        }
        // Write to BGA debug output
        self::output(json_encode($entry));
    }

    private static function isDebugMode(): bool
    {
        return defined('DEBUG_MODE') && DEBUG_MODE === true;
    }
}
```

### 8.2 Structured Logging

Include correlation IDs across requests for traceability:

```php
class Logger
{
    private static string $correlationId = '';

    public static function init(): void
    {
        self::$correlationId = bin2hex(random_bytes(8));
    }

    public static function getCorrelationId(): string
    {
        return self::$correlationId;
    }
}
```

### 8.3 Debug Flags

Feature flags that enable debug-only behaviour:

```php
class DebugTrait
{
    public function actDebugAddResource(string $type, int $amount, ?int $playerId = null): void
    {
        if (!$this->isDevelopmentEnvironment()) {
            throw new \BgaSystemException('Debug actions disabled in production');
        }
        $playerId = $playerId ?? $this->getCurrentPlayerId();
        $this->players->addResource($playerId, $type, $amount);
        // Notify the current player only
        $this->notifications->debugResourceAdded($playerId, $type, $amount);
    }

    public function actDebugSetSeed(int $seed): void
    {
        if (!$this->isDevelopmentEnvironment()) {
            throw new \BgaSystemException('Debug actions disabled in production');
        }
        Globals::setGameSeed($seed);
    }

    private function isDevelopmentEnvironment(): bool
    {
        // BGA Studio environments have this constant
        return defined('STUDIO') && STUDIO === true;
    }
}
```

### 8.4 Assertions

Use assertions to catch invariant violations during development:

```php
class Player extends Model
{
    public function spendResources(Resources $cost): void
    {
        assert($this->resources->hasAtLeast($cost),
            "Player {$this->id} cannot afford " . $cost->toStr());

        $this->resources = $this->resources->subtract($cost);
    }
}

class Manager
{
    protected function assertTableOwnership(string $table): void
    {
        assert(in_array($table, $this->ownedTables),
            "Manager " . static::class . " does not own table '{$table}'");
    }
}
```

### 8.5 Developer Diagnostics

Tools for diagnosing issues during development:

```php
// In DebugTrait
public function actDebugDumpState(): void
{
    $this->notifications->debugStateDump([
        'players' => $this->players->getAll(),
        'cards' => $this->cards->getAll(),
        'board' => $this->board->getState(),
        'globals' => Globals::getAll(),
        'state' => $this->gamestate->state(),
    ]);
}

public function actDebugValidateState(): array
{
    $errors = [];

    // Verify all invariants
    foreach ($this->players->getAll() as $player) {
        if ($player->getScore() < 0) {
            $errors[] = "Player {$player->getId()} has negative score";
        }
        if ($player->getResources()->get('coin') < 0) {
            $errors[] = "Player {$player->getId()} has negative coins";
        }
    }

    // Check card counts match
    $expected = $this->cards->countCardsByLocation();
    $actual = $this->cards->countCardsByLocationFromDb();
    if ($expected !== $actual) {
        $errors[] = "Card count mismatch";
    }

    return $errors;
}
```

### 8.6 Studio Tools

Agricola's debugging infrastructure is the reference standard:

```
Debug features (from Agricola, ★★★★★):
  - DebugTrait.php with Studio debug actions
  - Seed loading system for reproducible game setup
  - bug-triage.md and bugspage1.json for bug tracking
  - Dedicated "check combos" debug state
  - Per-card debug actions for each card type

ArkNova debugging (★★★★☆):
  - DebugTrait.php with generic debug actions
  - Less structured than Agricola

Earth debugging (★★★★☆):
  - Debug.php in both BX/ and EA/ modules
  - Studio debug actions and state inspection

Arnak debugging (★★★☆☆):
  - Minimal debug infrastructure
  - No seed loading, no debug trait
  - Relies on BGA studio defaults
```

See [reference-project-analysis.md §1.2](./reference-project-analysis.md#12-subsystem-ratings).

---

## 9. Replay and Reproduction

### 9.1 Deterministic Reproduction

Reproduce bugs with a fixed seed and action sequence:

```php
class ReplayTest extends TestCase
{
    public function testReproduceBug123(): void
    {
        // Load the exact seed from the bug report
        $seed = 48392;
        srand($seed);

        // Replay the action sequence
        $actions = [
            ['actPlayCard', ['cardId' => 5]],
            ['actUseAbility', ['abilityId' => 3]],
            ['actPass', []],
        ];

        foreach ($actions as [$action, $args]) {
            try {
                $this->executeAction($action, $args);
            } catch (\Exception $e) {
                $this->fail("Bug #123 reproduced: {$e->getMessage()}");
            }
        }
    }
}
```

### 9.2 Seed Management

```php
class SeedManager
{
    private const SEED_GLOBAL = 'gameSeed';

    public static function initialize(): void
    {
        $seed = Globals::get(self::SEED_GLOBAL);
        if ($seed === null) {
            $seed = random_int(0, PHP_INT_MAX);
            Globals::set(self::SEED_GLOBAL, $seed);
        }
        srand($seed);
    }

    public static function setSeed(int $seed): void
    {
        Globals::set(self::SEED_GLOBAL, $seed);
        srand($seed);
    }

    public static function getSeed(): int
    {
        return (int)Globals::get(self::SEED_GLOBAL, 0);
    }
}
```

### 9.3 Replay Architecture

```
┌─────────────────────────────────────────────────────────────┐
│  REPLAY SYSTEM                                                │
│                                                              │
│  ┌──────────┐    ┌──────────┐    ┌──────────┐               │
│  │ Bug      │───►│ Seed +   │───►│ Replay   │               │
│  │ Report   │    │ Action   │    │ Engine   │               │
│  │          │    │ Sequence │    │          │               │
│  └──────────┘    └──────────┘    └─────┬────┘               │
│                                        │                     │
│                                        ▼                     │
│                               ┌──────────────────┐           │
│                               │ Assert state at  │           │
│                               │ each step         │           │
│                               └──────────────────┘           │
│                                        │                     │
│                                        ▼                     │
│                               ┌──────────────────┐           │
│                               │ Pass / Fail      │           │
│                               │ + diagnostic     │           │
│                               └──────────────────┘           │
└─────────────────────────────────────────────────────────────┘
```

### 9.4 Minimal Reproduction

When a bug is found, reduce it to the minimal sequence:

```php
public function testMinimalReproduction(): void
{
    // Start with full sequence, remove actions until the bug disappears
    // Then add back the last removed action
    $minimal = [
        ['actPlayCard', ['cardId' => 5]],
        // Bug only appears with these two specific cards
        ['actPlayCard', ['cardId' => 12]],
    ];

    $this->expectException(\BgaUserException::class);
    foreach ($minimal as [$action, $args]) {
        $this->executeAction($action, $args);
    }
}
```

### 9.5 Regression Capture

Every bug fix includes a regression test:

```php
public function testRegressionBug123(): void
{
    $actions = $this->loadBugSequence('bug-123');

    // Before the fix, this would throw
    // After the fix, it completes successfully
    foreach ($actions as [$action, $args]) {
        $this->executeAction($action, $args);
    }

    // Verify final state
    $this->assertEquals(15, $this->game->getAllDatas(1)['players'][1]['score']);
}
```

---

## 10. Performance Validation

### 10.1 Profiling

Profile expensive operations:

```php
class Profiler
{
    private static array $marks = [];

    public static function mark(string $label): void
    {
        self::$marks[] = [
            'label' => $label,
            'time' => microtime(true),
            'memory' => memory_get_usage(),
        ];
    }

    public static function report(): array
    {
        $report = [];
        for ($i = 1; $i < count(self::$marks); $i++) {
            $prev = self::$marks[$i - 1];
            $curr = self::$marks[$i];
            $report[] = [
                'label' => $curr['label'],
                'duration_ms' => ($curr['time'] - $prev['time']) * 1000,
                'memory_delta' => $curr['memory'] - $prev['memory'],
            ];
        }
        return $report;
    }
}
```

### 10.2 Query Count Monitoring

Monitor database query count per action:

```php
class QueryMonitor
{
    private static int $queryCount = 0;

    public static function increment(): void
    {
        self::$queryCount++;
    }

    public static function getCount(): int
    {
        return self::$queryCount;
    }

    public static function reset(): void
    {
        self::$queryCount = 0;
    }
}

// Wrap Game::DbQuery
class MonitoredGame extends Game
{
    public function DbQuery(string $sql): ?int
    {
        QueryMonitor::increment();
        return parent::DbQuery($sql);
    }
}
```

```php
public function testActionQueryCount(): void
{
    QueryMonitor::reset();
    $this->executeAction('actPlayCard', ['cardId' => 42]);

    $this->assertLessThan(10, QueryMonitor::getCount(),
        'actPlayCard should execute fewer than 10 queries');
}
```

### 10.3 Notification Payload Size

Monitor notification payload size:

```php
public function testNotificationPayloadSize(): void
{
    $captured = [];
    Game::get()->expects($this->any())
        ->method('notifyAllPlayers')
        ->willReturnCallback(function ($type, $log, $data) use (&$captured) {
            $size = strlen(json_encode($data));
            $captured[] = compact('type', 'size');
            $this->assertLessThan(1024, $size,
                "Notification '{$type}' payload exceeds 1KB");
        });

    $this->executeAction('actPlayCard', ['cardId' => 42]);
}
```

### 10.4 Animation Performance

Verify animations do not block the queue excessively:

```php
public function testAnimationDuration(): void
{
    // Fast mode should skip animations
    $this->game->setFastMode(true);
    $start = microtime(true);
    $this->executeAction('actDrawCards', ['count' => 5]);
    $duration = (microtime(true) - $start) * 1000;

    $this->assertLessThan(500, $duration,
        'Drawing 5 cards should complete in under 500ms in fast mode');
}
```

---

## 11. Error Handling

### 11.1 Recoverable Errors

```php
public function testRecoverableErrorShowsUserMessage(): void
{
    $this->expectException(\BgaUserException::class);
    $this->expectExceptionMessage('not enough coins');

    $this->executeAction('actBuyCard', ['cardId' => 99]);
}

public function testRecoverableErrorDoesNotMutateState(): void
{
    $before = $this->game->getAllDatas(1);

    try {
        $this->executeAction('actBuyCard', ['cardId' => 99]);
    } catch (\BgaUserException $e) {
        // Expected
    }

    $after = $this->game->getAllDatas(1);
    $this->assertEquals($before, $after, 'Failed action must not change state');
}
```

### 11.2 Fatal Errors

```php
public function testFatalErrorOnCorruptState(): void
{
    $this->expectException(\BgaSystemException::class);

    // Force invalid state
    $this->forceCorruptPlayerState();
    $this->executeAction('actPlayCard', ['cardId' => 42]);
}
```

### 11.3 Assertion Testing

```php
public function testAssertionsEnabledInDevelopment(): void
{
    // Assertions should fire when invariants are violated
    $player = new Player(1, 'Test', 'ff0000');
    $player->setResources(new Resources(['coin' => 5]));

    // This should trigger assertion: spending 10 when only have 5
    if (assert_options(ASSERT_ACTIVE)) {
        $this->expectException(\AssertionError::class);
        $player->spendResources(new Resources(['coin' => 10]));
    }
}
```

### 11.4 Zombie Testing

```php
public function testZombieHandlesDisconnect(): void
{
    $result = $this->game->zombie(2, []);
    // Should return a valid transition
    $this->assertNotEmpty($result);

    // Game state should be consistent
    $state = $this->game->gamestate->state();
    $this->assertNotEquals(99, $state['id']); // Game not ended
}

public function testZombieInMultiactive(): void
{
    // Simulate disconnect during multiactive phase
    $this->game->setAllPlayersMultiactive();
    $this->game->zombie(1, []);

    // Player 1 should be deactivated
    $active = $this->game->gamestate->getActivePlayerList();
    $this->assertNotContains(1, $active);
}
```

### 11.5 Impossible Action Recovery

```php
public function testImpossibleActionProvidesEscape(): void
{
    // Force mandatory action that cannot be completed
    $this->forceImpossibleAction();

    // State should transition to recovery
    $state = $this->game->gamestate->state();
    $this->assertEquals('impossibleAction', $state['name']);
    $this->assertContains('actRestart', $state['possibleactions']);
}
```

---

## 12. CI Validation

### 12.1 Static Analysis

```bash
# PHP linting
php -l modules/php/Game.php
php -l modules/php/Managers/*.php

# PHPStan (if configured)
vendor/bin/phpstan analyse modules/php/ --level 5

# ESLint for JavaScript
npx eslint modules/js/

# CSS validation
npx stylelint modules/js/styles/
```

### 12.2 PHP Validation

```bash
# Run all PHPUnit tests
vendor/bin/phpunit

# With coverage
vendor/bin/phpunit --coverage-html coverage/

# Run specific test suite
vendor/bin/phpunit tests/Managers/
vendor/bin/phpunit tests/States/
vendor/bin/phpunit tests/Notifications/
```

### 12.3 JavaScript Validation

```js
// package.json (for CI)
{
    "scripts": {
        "lint": "eslint modules/js/",
        "test": "jest modules/js/ --passWithNoTests"
    }
}
```

### 12.4 Automated Simulations

Full game simulations that exercise the complete game flow:

```php
class FullGameSimulationTest extends TestCase
{
    /** @dataProvider seedProvider */
    public function testFullGameWithSeed(int $seed): void
    {
        $this->initializeGame($seed);

        // Play automated turns until game end
        $maxTurns = 200;
        $turnCount = 0;

        while ($turnCount < $maxTurns) {
            $state = $this->game->gamestate->state();

            if ($state['id'] === 99) {
                // Game ended successfully
                $this->assertGameEndState();
                return;
            }

            // Choose a valid action
            $action = $this->chooseAction($state);
            $this->executeAction($action['name'], $action['args']);
            $turnCount++;
        }

        $this->fail("Game did not end within {$maxTurns} turns (seed: {$seed})");
    }

    public static function seedProvider(): array
    {
        return [
            [12345],
            [67890],
            [11111],
            [22222],
            [33333],
        ];
    }
}
```

### 12.5 Automated Regression Testing Pipeline

```
┌─────────────────────────────────────────────────────────────┐
│  REGRESSION PIPELINE                                         │
│                                                              │
│  Commit ──► Lint ──► Unit Tests ──► Manager Tests            │
│                          │                                    │
│                          ▼                                    │
│                    State Machine Tests                        │
│                          │                                    │
│                          ▼                                    │
│                    Full Game Simulations                      │
│                          │                                    │
│                          ▼                                    │
│                    Report                                     │
│                      ├── Pass → Deploy                        │
│                      └── Fail → Block + Notify               │
└─────────────────────────────────────────────────────────────┘
```

---

## 13. Anti-Patterns

### 13.1 Console Debugging

**Symptom:** Using `console.log` or `var_dump` as the primary debugging method.

```php
// BAD: scattered var_dump in production code
public function someMethod(): void
{
    var_dump($this->getState()); die();
}
```

**Solution:** Use structured logging with levels and correlation IDs. Remove debug output before committing.

### 13.2 Mutable Tests

**Symptom:** Tests share mutable state and depend on execution order.

```php
// BAD: shared mutable state
class ManagerTest extends TestCase
{
    private static $game;  // Mutable static state

    public function testCreateCard(): void
    {
        self::$game = $this->createGame();
    }

    public function testPlayCard(): void
    {
        // Depends on testCreateCard having run first
        self::$game->playCard(42, 1);
    }
}
```

**Solution:** Use `setUp()` to create fresh state for each test. Never depend on test ordering.

### 13.3 Timing-Dependent Tests

**Symptom:** Tests depend on real wall-clock time.

```js
// BAD: depends on animation timing
it('completes animation', async () => {
    await mgr.animatePlayCard(1);
    await sleep(500);  // Magic number — fragile
    expect(el.classList.contains('played')).toBe(true);
});
```

**Solution:** Make animations deterministic in tests. Resolve animation Promises immediately:

```js
// GOOD: mock animation timing
it('updates state after card played', () => {
    mgr.animatePlayCard = async () => {};  // No-op
    mgr.onCardPlayed({ card_id: 1 });
    expect(mgr.cards[1].location).toBe('play');
});
```

### 13.4 Hidden Coupling

**Symptom:** Tests pass in isolation but fail when run as a suite due to hidden framework dependencies.

```php
// BAD: test depends on framework globals
public function testSomething(): void
{
    // Fails if run after another test that changed globals
    $round = Globals::getCurrentRound();
}
```

**Solution:** Reset all global state in `setUp()`:

```php
protected function setUp(): void
{
    parent::setUp();
    Globals::reset();  // Clear all globals
    // Create fresh mock game
    $this->game = $this->createMock(Game::class);
}
```

### 13.5 Over-Mocking

**Symptom:** Tests mock every dependency, testing nothing real.

```php
// BAD: mocks everything, tests nothing
public function testPlayCard(): void
{
    $cards = $this->createMock(Cards::class);
    $cards->expects($this->once())->method('playCard');

    $players = $this->createMock(Players::class);
    $players->expects($this->once())->method('spendResources');

    $notif = $this->createMock(Notifications::class);
    $notif->expects($this->once())->method('cardPlayed');

    $state = new PlayerTurn($cards, $players, $notif);
    $state->actPlayCard(42, 1, []);
}
```

**Solution:** Test the integration of real components where possible. Mock only the framework boundary:

```php
// GOOD: mock framework only, test real managers
public function testPlayCard(): void
{
    $game = $this->createMock(Game::class);
    $cards = new Cards($game);
    $cards->createCard('farm', '1', 'hand', 1);

    $game->method('getObjectFromDB')->willReturn([
        'card_id' => 42,
        'card_location' => 'play',
        'card_location_arg' => 1,
    ]);

    $result = $cards->playCard(42, 1);
    $this->assertEquals('play', $result->getLocation());
}
```

### 13.6 Testing Implementation Instead of Behaviour

**Symptom:** Tests assert internal implementation details, breaking when refactoring.

```php
// BAD: tests private method
public function testPrivateHelper(): void
{
    $reflection = new \ReflectionMethod(Cards::class, 'computeCost');
    $reflection->setAccessible(true);
    $result = $reflection->invoke($cards, 42);
    $this->assertEquals(5, $result);
}
```

**Solution:** Test through public API:

```php
// GOOD: tests public behaviour
public function testPlayCardSpendsCorrectCost(): void
{
    $player = $players->get(1);
    $this->assertEquals(10, $player->getCoins());

    $cards->playCard(42, 1);

    $this->assertEquals(8, $player->getCoins()); // Cost is 2
}
```

---

## 14. Templates

### 14.1 Canonical Unit Test

```php
<?php
declare(strict_types=1);

namespace MyGame\Tests\Models;

use MyGame\Models\Resources;
use PHPUnit\Framework\TestCase;

final class ResourcesTest extends TestCase
{
    private Resources $empty;
    private Resources $coins;
    private Resources $mixed;

    protected function setUp(): void
    {
        $this->empty = Resources::empty();
        $this->coins = new Resources(['coin' => 5]);
        $this->mixed = new Resources(['coin' => 3, 'wood' => 2]);
    }

    // ── Construction ──

    public function testEmptyResources(): void
    {
        $this->assertTrue($this->empty->isEmpty());
        $this->assertEquals(0, $this->empty->get('coin'));
    }

    public function testNegativeValuesRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Resources(['coin' => -1]);
    }

    // ── Arithmetic ──

    public function testAdd(): void
    {
        $result = $this->coins->add(new Resources(['coin' => 3]));
        $this->assertEquals(8, $result->get('coin'));
    }

    public function testImmutability(): void
    {
        $original = $this->coins->get('coin');
        $this->coins->add(new Resources(['coin' => 10]));
        $this->assertEquals($original, $this->coins->get('coin'));
    }

    public function testSubtract(): void
    {
        $result = $this->mixed->subtract(new Resources(['coin' => 1]));
        $this->assertEquals(2, $result->get('coin'));
        $this->assertEquals(2, $result->get('wood'));
    }

    // ── Queries ──

    public function testHasAtLeast(): void
    {
        $this->assertTrue($this->mixed->hasAtLeast(new Resources(['coin' => 3])));
        $this->assertFalse($this->mixed->hasAtLeast(new Resources(['coin' => 4])));
        $this->assertTrue($this->mixed->hasAtLeast(new Resources(['wood' => 2])));
    }

    // ── Factories ──

    public function testFromArray(): void
    {
        $result = Resources::fromArray(['coin' => 3, 'stone' => 1]);
        $this->assertEquals(3, $result->get('coin'));
        $this->assertEquals(1, $result->get('stone'));
    }

    public function testEmptyFactory(): void
    {
        $this->assertTrue(Resources::empty()->isEmpty());
    }
}
```

### 14.2 Canonical Manager Test

```php
final class CardsManagerTest extends TestCase
{
    private Cards $cards;
    private Game $game;

    protected function setUp(): void
    {
        $this->game = $this->createMock(Game::class);
        $this->cards = new Cards($this->game);
    }

    // ── Create ──

    public function testCreateCard(): void
    {
        $this->game->method('getUniqueValueFromDB')->willReturn(42);

        $card = $this->cards->createCard('farm', '1', 'deck');

        $this->assertNotNull($card);
        $this->assertEquals(42, $card->getId());
        $this->assertEquals('deck', $card->getLocation());
    }

    // ── Read ──

    public function testGetExistingCard(): void
    {
        $this->game->method('getObjectFromDB')->willReturn([
            'card_id' => '42',
            'card_type' => 'farm',
            'card_type_arg' => '1',
            'card_location' => 'hand',
            'card_location_arg' => '1',
            'card_state' => '0',
            'extra_datas' => null,
        ]);

        $card = $this->cards->get(42);
        $this->assertEquals('farm', $card->getType());
    }

    public function testGetNonExistingCardThrows(): void
    {
        $this->game->method('getObjectFromDB')->willReturn(null);

        $this->expectException(\BgaSystemException::class);
        $this->cards->get(999);
    }

    // ── Mutate ──

    public function testPlayCardValidatesOwnership(): void
    {
        $this->cards->createCard('farm', '1', 'hand', 1);

        $this->game->method('getObjectFromDB')->willReturnCallback(function ($sql) {
            if (str_contains($sql, 'card_id = 42')) {
                return [
                    'card_id' => '42',
                    'card_location' => 'play',
                    'card_location_arg' => '1',
                ];
            }
            return null;
        });

        $card = $this->cards->playCard(42, 1);
        $this->assertEquals('play', $card->getLocation());
    }

    public function testPlayThrowsOnWrongPlayer(): void
    {
        $this->game->method('getObjectFromDB')->willReturn([
            'card_id' => '42',
            'card_location' => 'hand',
            'card_location_arg' => '1', // Belongs to player 1
        ]);

        $this->expectException(\BgaUserException::class);
        $this->cards->playCard(42, 2); // Player 2 tries to play
    }

    // ── Concurrency ──

    public function testClaimCardAtomic(): void
    {
        $this->game->method('DbQuery')->willReturnCallback(function ($sql) {
            // Simulate: first call succeeds, second fails (0 affected)
            static $callCount = 0;
            $callCount++;
            return $callCount === 1 ? 1 : 0;
        });

        $this->cards->claim(42, 1); // Should succeed

        $this->expectException(\BgaUserException::class);
        $this->cards->claim(42, 2); // Should fail
    }
}
```

### 14.3 Canonical Notification Verification

```php
final class NotificationsTest extends TestCase
{
    private array $captured;

    protected function setUp(): void
    {
        $this->captured = [];
        $game = Game::get();
        $game->method('notifyAllPlayers')
            ->willReturnCallback(function ($type, $log, $data) {
                $this->captured[] = compact('type', 'log', 'data');
            });
    }

    public function testCardPlayedNotification(): void
    {
        $player = new Player(1, 'Alice', 'ff0000');

        Notifications::cardPlayed($player, 42, 'Farm');

        // Type
        $this->assertCount(1, $this->captured);
        $this->assertEquals('cardPlayed', $this->captured[0]['type']);

        // Log
        $log = $this->captured[0]['log'];
        $this->assertStringContainsString('${player_name}', $log);
        $this->assertStringContainsString('${card_name}', $log);

        // Args
        $args = $this->captured[0]['data'];
        $this->assertEquals(1, $args['player_id']);
        $this->assertEquals(42, $args['card_id']);
        $this->assertContains('card_name', $args['i18n']);
    }

    public function testPrivateDataIsolated(): void
    {
        // When notification uses _private, verify other players cannot see it
        // This requires testing notifyPlayer or _private isolation
    }

    public function testNoNotificationOnFailedAction(): void
    {
        // Simulate a transaction rollback
        try {
            $this->game->method('DbQuery')->willThrowException(
                new \BgaUserException('Failed')
            );
            $this->cards->playCard(42, 1);
        } catch (\BgaUserException $e) {
            // Expected
        }

        // No notifications should have been sent
        $this->assertEmpty($this->captured);
    }
}
```

### 14.4 Canonical Replay Test

```php
final class ReplayTest extends TestCase
{
    public function testReproduceBugFromArchive(): void
    {
        // Load gamelog from bug report
        $log = $this->loadGameLog('bug-archive-456.json');

        // Initialize game with same seed
        $seed = $log['seed'];
        $this->initializeGameWithSeed($seed);

        // Replay all actions
        foreach ($log['actions'] as $action) {
            try {
                $this->executeAction($action['name'], $action['args'] ?? []);
            } catch (\Exception $e) {
                $this->fail("Replay failed at step {$action['step']}: {$e->getMessage()}");
            }
        }

        // Verify final state matches expected
        $state = $this->game->getAllDatas(null);
        $expected = $log['expected_state'];
        $this->assertEquals($expected['scores'], $state['scores']);
    }

    /** @dataProvider regressionSeedsProvider */
    public function testRegressionSeeds(int $seed): void
    {
        $this->initializeGameWithSeed($seed);
        $maxTurns = 100;

        for ($i = 0; $i < $maxTurns; $i++) {
            $state = $this->game->gamestate->state();
            if ($state['id'] === 99) return;

            $actions = $this->getLegalActions($state);
            $chosen = $this->pickRandomAction($actions);
            $this->executeAction($chosen['name'], $chosen['args'] ?? []);
        }

        $this->fail("Seed {$seed}: game did not complete in {$maxTurns} turns");
    }

    public static function regressionSeedsProvider(): array
    {
        return [
            [0], [1], [42], [100], [1000], [9999],
        ];
    }
}
```

### 14.5 Canonical Debug Logger

```php
class Logger
{
    private static string $correlationId = '';
    private static bool $enabled = false;

    public static function enable(): void
    {
        self::$enabled = true;
        self::$correlationId = bin2hex(random_bytes(8));
    }

    public static function disable(): void
    {
        self::$enabled = false;
    }

    public static function debug(string $message, array $context = []): void
    {
        if (!self::$enabled) return;
        self::write('DEBUG', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function measure(string $label, callable $fn): mixed
    {
        $start = microtime(true);
        $result = $fn();
        $duration = (microtime(true) - $start) * 1000;
        self::debug("Measured: {$label}", ['duration_ms' => round($duration, 2)]);
        return $result;
    }

    private static function write(string $level, string $message, array $context): void
    {
        $entry = [
            'cid' => self::$correlationId,
            'time' => date('H:i:s.v'),
            'level' => $level,
            'msg' => $message,
            'ctx' => $context,
        ];
        error_log(json_encode($entry));
    }
}
```

---

## 15. Checklists

### 15.1 Development Checklist

- [ ] PHPUnit tests exist for all value objects
- [ ] PHPUnit tests exist for all Models (computed properties, formatting)
- [ ] PHPUnit tests exist for all Managers (CRUD, invariants, validation)
- [ ] PHPUnit tests exist for each action method (valid path + error cases)
- [ ] PHPUnit tests exist for each state class (args computation, transitions)
- [ ] Notification verification tests exist for each notification type
- [ ] Zombie tests exist for every non-GAME state
- [ ] Tests cover: success path, validation failure, concurrency conflict
- [ ] No debug output (var_dump, console.log) in committed code
- [ ] Assertions used for invariant enforcement
- [ ] Logger uses structured format with correlation IDs
- [ ] Debug traits are guarded by `isDevelopmentEnvironment()`

### 15.2 Pre-Review Checklist

- [ ] All PHPUnit tests pass: `vendor/bin/phpunit`
- [ ] No failing tests introduced
- [ ] Code coverage is not significantly reduced
- [ ] All new notification types have payload verification tests
- [ ] All new state transitions have reachability tests
- [ ] All new actions have validation-failure tests
- [ ] Full game simulation runs successfully with 5+ seeds
- [ ] JavaScript linter passes: `npx eslint modules/js/`
- [ ] Static analysis passes: `vendor/bin/phpstan`
- [ ] All debug actions are guarded by environment check

### 15.3 Pre-Release Checklist

- [ ] Full game simulations run with 20+ seeds without failure
- [ ] Replay validation passes on archive data
- [ ] Notification payloads verified (i18n, private isolation, size)
- [ ] Zombie handling tested for every non-GAME state
- [ ] `ST_IMPOSSIBLE_MANDATORY_ACTION` tested for every mandatory action path
- [ ] Reconnect tested: refresh during any state produces correct UI
- [ ] Spectator join produces only public data
- [ ] Fast mode tested: all actions complete without animation
- [ ] Simultaneous action tested: conflicts resolved correctly
- [ ] Database migration tested: `upgradeTableDb` runs without errors on production schema
- [ ] All strings wrapped in `clienttranslate()` or `totranslate()` as appropriate
- [ ] Stats are tracked and verifiable after test games
- [ ] Game progression (`getGameProgression()`) returns reasonable values

### 15.4 Regression Checklist

- [ ] Every bug report generates a regression test
- [ ] Regression test reproduces the bug before fix, passes after
- [ ] Regression test is minimal (eliminates unrelated actions)
- [ ] Regression test uses a fixed seed for determinism
- [ ] Seed archive maintained for historical regression coverage
- [ ] Full regression suite runs in CI before every release
- [ ] Failed regression blocks release
- [ ] Regression test names reference the bug ID (e.g., `testRegressionBug123`)

---

## References

- [domain-architecture.md](./domain-architecture.md) — testing implications (§15), layered architecture (§2), dependency rules (§14)
- [action-architecture.md](./action-architecture.md) — validation architecture (§6), error handling (§12), exception reference (§12.3)
- [persistence-architecture.md](./persistence-architecture.md) — transaction rollback (§9.2), concurrency testing (§10), upgradeTableDb (§15)
- [state-machine-architecture.md](./state-machine-architecture.md) — debugging state machines (§16), state lifecycle (§4), zombie (§13.6)
- [game-flow-architecture.md](./game-flow-architecture.md) — error handling (§10), ST_IMPOSSIBLE_MANDATORY_ACTION (§10.4), execution pipeline (§2)
- [client-synchronization-architecture.md](./client-synchronization-architecture.md) — notification lifecycle (§5), reconnect verification (§6), getAllDatas parity
- [client-ui-architecture.md](./client-ui-architecture.md) — manager state testing, selection testing, rendering verification
- [animation-architecture.md](./animation-architecture.md) — fast mode testing, animation cancellation tests
- [notification-patterns.md](./notification-patterns.md) — notification verification (§15.8), i18n testing, private data isolation (§3)
- [reference-project-analysis.md](../foundation/reference-project-analysis.md) — Agricola debugging infrastructure (§1.2), Earth debugging (§4.2)
- [bga-developer-handbook.md](../foundation/bga-developer-handbook.md) — PHPUnit setup (§12), pre-release checklist (§13), upgradeTableDb
- [bga-ai-implementation-reference.md](../foundation/bga-ai-implementation-reference.md) — testing and debugging (§18), PHP test example
