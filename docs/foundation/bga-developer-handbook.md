# BGA Studio Developer Handbook

**Version:** 1.0  
**Generated:** July 6, 2026  
**Source:** Official BGA Studio documentation (en.doc.boardgamearena.com)  
**Generation method:** Automated extraction and consolidation of official BGA documentation

---

## Table of Contents

- [Chapter 1: Introduction to BGA Studio](#chapter-1-introduction-to-bga-studio)
- [Chapter 2: Development Environment Setup](#chapter-2-development-environment-setup)
- [Chapter 3: Game Project Fundamentals](#chapter-3-game-project-fundamentals)
- [Chapter 4: State Machine Architecture](#chapter-4-state-machine-architecture)
- [Chapter 5: Server-Side Game Logic (PHP)](#chapter-5-server-side-game-logic-php)
- [Chapter 6: Client-Side Game Interface (JavaScript)](#chapter-6-client-side-game-interface-javascript)
- [Chapter 7: Game UI Components and Graphics](#chapter-7-game-ui-components-and-graphics)
- [Chapter 8: UI Enhancements and Interactivity](#chapter-8-ui-enhancements-and-interactivity)
- [Chapter 9: Internationalization (i18n)](#chapter-9-internationalization-i18n)
- [Chapter 10: Bots and Artificial Intelligence](#chapter-10-bots-and-artificial-intelligence)
- [Chapter 11: Advanced BGA Components](#chapter-11-advanced-bga-components)
- [Chapter 12: Testing and Debugging](#chapter-12-testing-and-debugging)
- [Chapter 13: Game Development and Publishing](#chapter-13-game-development-and-publishing)
- [Chapter 14: Migration and Compatibility](#chapter-14-migration-and-compatibility)
- [Chapter 15: Reference and Resources](#chapter-15-reference-and-resources)

---

## Chapter 1: Introduction to BGA Studio

### What is Board Game Arena Studio?

Board Game Arena Studio is a platform to build online board game adaptations using the Board Game Arena platform. It provides developers with the tools, framework, and infrastructure needed to create digital versions of board games that can be played by millions of users worldwide.

**BGA Studio website:** https://studio.boardgamearena.com

### Discover BGA Studio in 5 Presentations

To get started with BGA Studio, the platform provides five introductory presentations that cover the essential aspects of game development:

1. **5 reasons why you should use BGA Studio for your online board game** - Explains the benefits and advantages of the platform
2. **The 8 steps to create a board game on Board Game Arena** - A complete walkthrough of the development process
3. **The BGA Framework at a glance** - Overview of the technical framework and architecture
4. **Focus on BGA game state machine** - Deep dive into the state machine system that controls game flow
5. **BGA developers guidelines** - Best practices and standards for BGA development

These presentations provide an excellent foundation for understanding both the "why" and "how" of BGA Studio development.

### Is BGA Studio Development Right for You?

Before diving into BGA Studio development, it's important to assess whether it aligns with your goals and skills.

**BGA Studio is right for you if:**
- You like board games and programming and think it would be a good hobby
- You really want to fix bugs in already published games
- You want to create a game from public domain (e.g., Chinese checkers, bridge, etc.)
- You were hired or persuaded by somebody else to create game adaptations on BGA

**BGA Studio is NOT right for you if:**
- You think you can implement any of the games from the top 100 on BoardGameGeek
- You're an amateur game designer wanting to playtest your prototype
- You're trying to create something that's not a board game adaptation
- You're a student who just took your first programming course

### Required Skills for BGA Studio Projects

BGA Studio development requires proficiency in multiple technologies and concepts:

**Languages you need to know or learn:**
- JavaScript
- PHP
- SQL
- HTML
- CSS
- English (for documentation and communication)

**Technical concepts you need to understand:**
- Object-oriented programming
- Web development fundamentals
- Database development
- Development tool setup and remote file synchronization
- Image manipulation software

### How to Create a BGA Studio Development Account

Registering on BGA Studio is simple and automatic:

1. Go to https://studio.boardgamearena.com/
2. Find the section "Join the BGA Studio developers team"
3. Fill out the form and submit

**What you receive after registration:**
- One login/password to access files through SFTP
- One login/password to access the database
- Ten logins with numeric suffixes (dev0-dev9) for testing
- One "admin" login to manage projects

---

## Chapter 2: Development Environment Setup

### Choosing and Setting Up Your Development Environment

A proper development environment is crucial for efficient BGA Studio development. This chapter covers setting up Visual Studio Code, the recommended IDE for BGA development.

### Visual Studio Code Setup

Microsoft Visual Studio Code is a lightweight IDE/editor available for all desktop platforms. It's the recommended choice for BGA Studio development.

**Download VSCode:** https://code.visualstudio.com

### Recommended VSCode Extensions

Use the **BGA Extension Pack** to download all recommended extensions with one click.

**Essential Extensions:**
1. **PHP Intelephense** - For PHP intellisense
2. **PHP Debug** - For debugging support
3. **SQLTools** - For database management
4. **Prettier** - Code formatter
5. **SFTP (by Natizyskunk)** - For file synchronization

### Intellisense and Auto-complete

#### JavaScript Intellisense

Since BGA adopted ES Modules and modernized the framework, we can enjoy full IDE support. If you try this with legacy Dojo projects, it may not work well.

A good way to improve auto-complete is to use TypeScript and SCSS. See: https://en.doc.boardgamearena.com/Using_Typescript_and_Scss

**Note:** The modern framework still uses Dojo libraries (ebg/stock, ebg/zone) internally, so understanding the legacy patterns remains useful.

#### PHP Intellisense

The "_ide_helper.php" file, included in every project, helps your IDE understand the available framework functions.

### Studio File Reference

This is a quick reference for the files used to implement a game:

**img/** - Contains game images  
**gameinfos.jsonc** - Game meta-information  
**dbmodel.sql** - Database schema  
**modules/php/Game.php** - Main game logic  
**modules/js/Game.js** - Client interface  
**modules/php/States/** - State classes  
**misc/** - Studio-only storage (1MB limit)

---

## Chapter 3: Game Project Fundamentals

### Understanding BGA Game Project Structure

A BGA game project consists of multiple files and directories that work together to create a complete game implementation.

### Game Meta-Information: gameinfos.jsonc

The gameinfos.jsonc file contains the meta-information for your game.

```json
{
    "players": [3, 4, 6],
    "suggest_player_number": 3,
    "player_colors": ["ff0000", "008000", "0000ff"]
}
```

### Game Database Model: dbmodel.sql

The dbmodel.sql file specifies the database schema of your game.

```sql
CREATE TABLE IF NOT EXISTS `card` (
    `card_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `card_type` varchar(16) NOT NULL,
    `card_type_arg` int(11) NOT NULL,
    `card_location` varchar(16) NOT NULL,
    `card_location_arg` int(11) NOT NULL,
    PRIMARY KEY (`card_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
```

### Game Material Description: material.inc.php

The material.inc.php file describes the material of your game (cards, tokens, etc.).

```php
$this->token_types = [
    'card_role_survey' => [
        'type' => 'card_role',
        'name' => clienttranslate("Survey"),
    ],
];
```

---

## Chapter 4: State Machine Architecture

### Understanding the BGA State Machine

The state machine is the heart of BGA game development. It controls the flow of the game, determines which players can act at any given time, and manages transitions between different game phases.

### State Classes Approach (Recommended)

State classes allow you to create a PHP class for each game state, providing better code organization and maintainability.

#### Example State Class Structure

```php
<?php
declare(strict_types=1);

namespace Bga\Games\<MyGameName>\States;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;

class PlayerTurn extends GameState
{
    function __construct(
        protected Game $game,
    ) {
        parent::__construct($game,
            id: 2,
            type: StateType::ACTIVE_PLAYER,
            description: clienttranslate('${actplayer} must play a card or pass'),
            descriptionMyTurn: clienttranslate('${you} must play a card or pass'),
            transitions: [
                'playCard' => 3,
                'pass' => 4,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    #[PossibleAction]
    public function actPlayCard(int $cardId, int $activePlayerId, array $args): string
    {
        // Validate and execute
        $this->game->validateCardPlay($cardId, $activePlayerId);
        $this->game->playCard($cardId, $activePlayerId);
        return 'playCard';
    }

    public function getArgs(): array
    {
        return [
            'possibleCards' => $this->game->getPossibleCards(),
        ];
    }
}
```

#### State Class Constructor Parameters

**id (Mandatory)**
- Must be a positive integer, unique between all states
- **Number 1 is reserved for the first game state** - do not modify it
- **Number 99 is reserved for the last game state** - do not modify it

**type (Mandatory)**
- `StateType::ACTIVE_PLAYER` - One player is active and must play
- `StateType::MULTIPLE_ACTIVE_PLAYER` - Multiple players can be active
- `StateType::PRIVATE` - Private parallel states during multiactive phases
- `StateType::GAME` - No player active; transitional state

**transitions**
- Defines which game state(s) you can jump to
- Format: `['transitionName' => targetStateId]`

**initialPrivate**
- Used during multiactive states to specify an initial private state
- Can also be a class name: `initialPrivate: PlaceCard::class`

### Magic Parameters for State Methods

State methods accept magic parameters automatically filled by the framework:

**For getArgs():**
- `int $activePlayerId` / `int $active_player_id` - for ACTIVE_PLAYER states
- `int $activePlayerNo` / `int $active_player_no` - for ACTIVE_PLAYER states
- `int $playerId` / `int $player_id` - for PRIVATE states
- `int $playerNo` / `int $player_no` - for PRIVATE states

**For act* methods:**
- `array $args` - state args
- `int $activePlayerId` / `int $active_player_id` - active player (may differ from current)
- `int $currentPlayerId` / `int $current_player_id` - player who triggered action

### Skipped States (_no_notify Flag)

To optimize performance for states that will be automatically skipped, use the `_no_notify` flag:

```php
public function getArgs(): array
{
    $playableCardsIds = $this->game->getPlayableCards();
    return [
        'playableCardsIds' => $playableCardsIds,
        '_no_notify' => count($playableCardsIds) === 0,
    ];
}

function onEnteringState(int $activePlayerId, array $args): void {
    if ($args['_no_notify']) {
        return $this->actPass($activePlayerId);
    }
}
```

### Private Data in getArgs (_private)

Private data can be sent to specific players only:

```php
public function getArgs(): array {
    return [
        '_private' => [
            $activePlayerId => [
                'somePrivateData' => $this->game->getSomePrivateData($activePlayerId)
            ]
        ],
        'possibleMoves' => $this->game->getPossibleMoves()
    ];
}
```

---

## Chapter 5: Server-Side Game Logic (PHP)

### Understanding the Game Logic File

The main game logic file (`modules/php/Game.php`) is where you implement the core rules of your game.

### Core Methods

**setupNewGame(array $players)**
- Initial setup of the game
- Initialize game state, deal cards, set up the board

**getAllDatas(int $currentPlayerId)**
- Retrieve all game data during a complete reload
- Accepts magic parameters `$currentPlayerId` / `$current_player_id`

**Zombie Mode Methods**

**zombie(int $playerId): string**
- Called when a player is in zombie mode (disconnected)
- Must be implemented for all non-GAME states

**giveExtraTime(int $playerId): void**
- Give extra time to a player (typically called when giving turn)
- Should be called on every player turn

**upgradeTableDb(int $fromVersion): void**
- Migrate database after release if schema changes
- Handle `dbmodel.sql` changes for existing games

### Using BGA Globals

Sometimes you want a single global value for your game without creating a specific DB table.

```php
$this->bga->globals->set('firstPlayerId', array_keys($players)[0]);
$value = $this->bga->globals->get('firstPlayerId');
$this->bga->globals->delete('firstPlayerId');
```

### Notifications

Notifications communicate game events to the client.

```php
$this->bga->notify->all('cardPlayed', clienttranslate('${player_name} played ${card_name}'), [
    'i18n' => ['card_name'],
    'player_id' => $playerId,
    'card_id' => $cardId,
    'card_name' => $this->getCardName($cardId),
]);
```

---

## Chapter 6: Client-Side Game Interface (JavaScript)

### Understanding the Game Interface File

The main game interface file (`modules/js/Game.js`) defines actions and handles notifications.

### Framework Sub-Components

The BGA framework provides organized access via the `bga` object:
- `bga.actions` - Action execution
- `bga.notifications` - Notification handling
- `bga.players` - Player information
- `bga.gamearea` - Game area DOM access
- `bga.images` - Image loading

### Performing Actions

```javascript
// Simple action
this.bga.actions.performAction('pass');

// Standard call with args
this.bga.actions.performAction('actPlayCard', { id: this.selectedCardId });

// Call with reaction to exception
this.bga.actions.performAction('actPlayCard', { id: this.selectedCardId })
    .catch(() => { this.selectedCardId = undefined; });
```

### Notifications

```javascript
setupNotifications() {
    // Modern (promise-based) — preferred:
    this.bga.notifications.setupPromiseNotifications();
    // Legacy alternative, still supported:
    // this.bga.notifications.subscribe('cardPlayed', (args) => this.onCardPlayed(args));
}
```

---

## Chapter 7: Game UI Components and Graphics

### Stock Component

Stock is a JavaScript component for displaying sets of elements.

```javascript
const [stock] = await importDojoLibs(['ebg/stock']);
this.playerHand = new ebg.stock();
this.playerHand.create(this.bga.gameui, $('myhand'), 60, 90);
```

### Deck Component (Server-Side)

Deck manages cards in your game.

```php
$this->cards = $this->deckFactory->createDeck('card');
$cards = $this->cards->getCardsInLocation('hand', $player_id);
$this->cards->moveCard($card_id, 'hand', $player_id);
```

### BgaCards Component (Recommended for Card Games)

**Why Use BgaCards**
- Better performance
- More features
- Easier API
- Better TypeScript support
- Integrated animation support via bga-animations

**BgaCards Architecture**
BgaCards uses a Manager/Stock pattern where the Manager handles card creation and the Stocks manage display.

**Stock Types:**
- `BgaCards.LineStock` - Horizontal or vertical card line
- `BgaCards.HandStock` - Fan of cards (hand display)
- `BgaCards.Deck` - Card deck with draw pile appearance
- `BgaCards.SlotStock` - Cards in slots/grid positions
- `BgaCards.VoidStock` - Discard pile

#### Importing BgaCards

```javascript
const BgaAnimations = await importEsmLib('bga-animations', '1.x'); // REQUIRED
const BgaCards = await importEsmLib('bga-cards', '1.x');
```

#### Setting Up BgaCards

```javascript
setup: function(gamedatas) {
    // Create the animation manager
    this.animationManager = new BgaAnimations.Manager({
        animationsActive: () => this.bga.gameui.bgaAnimationsActive(),
    });

    // Create the card manager
    this.cardsManager = new BgaCards.Manager({
        animationManager: this.animationManager,
        type: 'mygame-card',
        getId: (card) => card.id,
        setupFrontDiv: (card, div) => {
            div.dataset.type = card.type;
            div.dataset.typeArg = card.type_arg;
        },
    });
}
```

#### Creating Card Stocks

```javascript
// LineStock - for hand, table, or deck displays
this.cardStock = new BgaCards.LineStock(this.cardsManager, document.getElementById('card_stock'));

// HandStock - for fan-style hand display
this.handStock = new BgaCards.HandStock(this.cardsManager, document.getElementById('player_hand'));

// Deck - for draw piles
this.deck = new BgaCards.Deck(this.cardsManager, document.getElementById('deck'));
```

#### Adding and Removing Cards

```javascript
// Add cards to stock
this.cardStock.addCards([
    { id: 1, type: 1, type_arg: 5 },
    { id: 2, type: 2, type_arg: 10 }
]);

// Remove cards from stock
this.cardStock.removeCards([{ id: 1, type: 1, type_arg: 5 }]);

// Move cards between stocks (async)
this.cardStock.addCards(cards).then(() => {
    this.otherStock.addCards(cards);
});
```

#### Important BgaCards Notes

- **BgaAnimations dependency**: Must import and instantiate bga-animations
- **Array format**: Card data must be in array format (`Object.values()` for map objects)
- **Type casting**: PHP returns types as strings; may need conversion for BgaCards
- **Async methods**: `addCards()` and `removeCards()` return Promises

---

## Chapter 8: UI Enhancements and Interactivity

### BGA Studio Guidelines

#### Layouts
- Game should fit square ratio and show everything active player needs
- Use vertical scroll for secondary info
- Center play area on all devices

#### Action Bar
- Center main awaited actions
- Place cancel/undo on the right
- Keep to max 4 buttons + context

#### Player Panels
- Keep panels compact (4-player game on mobile must not occupy more than ¼ of screen)

### Game Options and Preferences

**gameoptions.jsonc structure:**
```json
{
    "100": {
        "name": "Game Setup",
        "values": {
            "1": { "name": "Standard", "tmdisplay": "Standard" },
            "2": { "name": "Expert", "tmdisplay": "Expert" }
        }
    }
}
```

---

## Chapter 9: Internationalization (i18n)

### Localization Overview

Localization happens largely on the CLIENT. Games must be developed in English.

### Translation Requirements

1. **Server-side strings:** Wrap in `clienttranslate()`:
   ```php
   $my_response = clienttranslate('Please translate this string');
   ```

2. **Client-side strings:** Wrap in `_()`:
   ```javascript
   _('This is my string')
   ```

3. **Notification messages:** Sent with `clienttranslate()` on server

4. **State descriptions:** Wrapped in `clienttranslate()` on server

---

## Chapter 10: Bots and Artificial Intelligence

### Bot Implementation

Bots are implemented as server-side logic in PRIVATE states during MULTIPLE_ACTIVE_PLAYER states.

#### Bot Player Table

```sql
CREATE TABLE IF NOT EXISTS `playerextra` (
    `player_id` int(10) unsigned NOT NULL,
    `player_name` varchar(32) NOT NULL,
    `player_color` varchar(6) NOT NULL,
    `player_no` int(10) NOT NULL,
    `player_ai` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

---

## Chapter 11: Advanced BGA Components

- **BgaAnimations** - Animation system
- **BgaAutofit** - Auto-fit text to container
- **BgaCards** - Card management (see Chapter 7)
- **BgaDice** - Dice display
- **BgaScoreSheet** - End-game score sheet
- **ExpandableSection** - Collapsible UI sections
- **Counter** - Numeric counters with animation

---

## Chapter 12: Testing and Debugging

### PHP Testing with PHPUnit

```bash
cd /home/<username>/php-composer
composer require --dev phpunit/phpunit ^13
```

```php
// Example test
final class BoardManagerTest extends TestCase
{
    public function testGetPossibleMoves(): void
    {
        $game = $this->createGame(8);
        $boardManager = $this->createBoardManager($game);
        $moves = $boardManager->getPossibleMoves(1);
        self::assertSame(['3' => ['4' => true]], $moves);
    }
}
```

---

## Chapter 13: Game Development and Publishing

### Pre-Release Checklist

Before moving from Dev to Alpha:

**Server Side:**
- Use `giveExtraTime()` when giving turn to a player
- Game progression implemented (`getGameProgression()`)
- Zombie turn implemented (`zombie()`)
- Statistics defined meaningfully
- Database schema sufficient for expansions

**Client Side:**
- Use `bgaPerformAction` only on player actions
- Strings ready for translation

---

## Chapter 14: Migration and Compatibility

### BGA Studio Migration Guide

The migration from PHP 7.4 to PHP 8.4 includes:
- State class migration from states.inc.php
- Modern API usage
- Notification system updates

---

## Chapter 15: Reference and Resources

### Official Documentation
- https://en.doc.boardgamearena.com - Official BGA documentation
- https://studio.boardgamearena.com - BGA Studio control panel

### Community Resources
- Discord: https://discord.gg/YxEUacY
- Developers Forum: https://en.doc.boardgamearena.com/Studio#Developers_forum

---

# Appendices

## Appendix A: Glossary

| Term | Definition |
|------|------------|
| ACTIVE_PLAYER | State type where exactly one player can act |
| MULTIPLE_ACTIVE_PLAYER | State type where multiple players can act simultaneously |
| PRIVATE | State type for individual player decisions during multiactive phases |
| GAME | State type for automatic transitions (no player input) |
| BgaCards | Modern JS component for card display and management |
| Stock | Legacy JS component for displaying sets of game elements |
| Deck | PHP component for managing cards and game pieces |
| Zombie | Disconnected player who is auto-played |
| giveExtraTime() | PHP method to add time to player's turn timer |
| upgradeTableDb() | PHP method for post-release database migrations |
| _no_notify | Flag to skip client notification for quickly-skipped states |
| _private | Key to send data to specific players only |

## Appendix B: Source Documentation Pages

The handbook was generated from the following official BGA documentation pages:

- BgaCards - https://en.doc.boardgamearena.com/BgaCards
- State classes: State directory - https://en.doc.boardgamearena.com/State_classes:_State_directory
- Main game logic: Game.php - https://en.doc.boardgamearena.com/Main_game_logic:_Game.php
- BGA Studio Migration Guide - https://en.doc.boardgamearena.com/BGA_Studio_Migration_Guide
- PlayerCounter and TableCounter - https://en.doc.boardgamearena.com/PlayerCounter_and_TableCounter
- Deck - https://en.doc.boardgamearena.com/Deck
- Stock - https://en.doc.boardgamearena.com/Stock
- Zone - https://en.doc.boardgamearena.com/Zone
- BgaAnimations - https://en.doc.boardgamearena.com/BgaAnimations
- Bots and Artificial Intelligence - https://en.doc.boardgamearena.com/Bots_and_Artificial_Intelligence

## Appendix C: Abbreviations

| Abbreviation | Meaning |
|--------------|---------|
| BGA | Board Game Arena |
| SFTP | Secure File Transfer Protocol |
| AJAX | Asynchronous JavaScript and XML |
| IDE | Integrated Development Environment |
| SQL | Structured Query Language |
| ELO | Rating system for player skill |
| EBG | Espace BGA (legacy framework namespace) |
| i18n | Internationalization |

## Appendix D: Further Reading

- **BGA Studio Guidelines** - https://en.doc.boardgamearena.com/BGA_Studio_Guidelines
- **Pre-release checklist** - https://en.doc.boardgamearena.com/Pre-release_checklist
- **Post-release phase** - https://en.doc.boardgamearena.com/Post-release_phase
- **BGA game Lifecycle** - https://en.doc.boardgamearena.com/BGA_game_Lifecycle
- **BGA Code Sharing** - https://en.doc.boardgamearena.com/BGA_Code_Sharing
- **Common board game elements** - https://en.doc.boardgamearena.com/Common_board_game_elements_image_resources