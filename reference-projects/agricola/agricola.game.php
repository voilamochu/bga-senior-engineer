<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * agricola implementation : © Timothée Pecatte <tim.pecatte@gmail.com>, Vincent Toper <vincent.toper@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * agricola.game.php
 *
 * This is the main file for your game logic.
 *
 * In this PHP file, you are going to defines the rules of the game.
 *
 */

$swdNamespaceAutoload = function ($class) {
  $classParts = explode('\\', $class);
  if ($classParts[0] == 'AGR') {
    array_shift($classParts);
    $file = dirname(__FILE__) . '/modules/php/' . implode(DIRECTORY_SEPARATOR, $classParts) . '.php';
    if (file_exists($file)) {
      require_once $file;
    } else {
      var_dump('Cannot find file : ' . $file);
    }
  }
};
spl_autoload_register($swdNamespaceAutoload, true, true);

require_once APP_GAMEMODULE_PATH . 'module/table/table.game.php';

use AGR\Managers\Meeples;
use AGR\Managers\ActionCards;
use AGR\Managers\PlayerCards;
use AGR\Managers\Players;
use AGR\Managers\Scores;
use AGR\Managers\Campaign;
use AGR\Core\Globals;
use AGR\Core\Preferences;
use AGR\Core\Stats;
use AGR\Core\Engine;
use AGR\Managers\Fences;

class agricola extends Table
{
  use AGR\DebugTrait;
  use AGR\States\DraftTrait;
  use AGR\States\TurnTrait;
  use AGR\States\ActionTrait;
  use AGR\States\HarvestTrait;
  use AGR\States\CampaignTrait;

  function dummy()
  {
  }

  // First function to be call: setup the object
  // (Note: the "basic" setup is used for the table creation, where there is no player and no game state)
  // function setTable($table_id, $gamename, $gameid, $basicSetup = false)
  // {
  //   parent::setTable($table_id, $gamename, $gameid, $basicSetup = false);
  //   $this->gamestate = new CustomGamestate();
  //   $this->gamestate->loadStateMachine($this, $this->gamename, $this->table_id, $this->isSandBoxGame());
  // }

  public static $instance = null;
  function __construct()
  {
    parent::__construct();
    self::$instance = $this;
    self::initGameStateLabels([
      'logging' => 10,
    ]);
    Engine::boot();
    Stats::checkExistence();
  }

  public static function get()
  {
    return self::$instance;
  }

  // Make sure old tables with pre-revamped option sets still work
  protected function forceOptionCompatibility($options)
  {
    $invalid = false;

    // The table is in normal or competitive mode, but there is a missing draft or deck setting
    if (
      isset($options[OPTION_COMPETITIVE_LEVEL]) &&
      ($options[OPTION_COMPETITIVE_LEVEL] == 1 || $options[OPTION_COMPETITIVE_LEVEL] == 2) &&
      (!isset($options[OPTION_DRAFT]) || !isset($options[OPTION_DECKS]))
    ) {
      $invalid = true;
    }

    // The table has a value for OPTION_DECKS that is out of range
    // (the old ID for OPTION_DRAFT is now mapped to OPTION_DECKS)
    if (isset($options[OPTION_DECKS]) && $options[OPTION_DECKS] > 2) {
      $invalid = true;
    }

    // There's no value for OPTION_SCORING
    if (!isset($options[OPTION_SCORING])) {
      $invalid = true;
    }

    // Deck selection is set to OPTION_DECKS_CUSTOM, but there are missing values for individual decks
    if (isset($options[OPTION_DECKS]) && $options[OPTION_DECKS] == OPTION_DECKS_CUSTOM) {
      if (
        !isset($options[OPTION_DECK_BASE]) ||
        !isset($options[OPTION_DECK_A]) ||
        !isset($options[OPTION_DECK_B]) ||
        !isset($options[OPTION_DECK_C]) ||
        !isset($options[OPTION_DECK_D]) ||
        !isset($options[OPTION_DECK_E]) ||
        !isset($options[OPTION_DECK_CD])
      ) {
        $invalid = true;
     }
    }

    // If we found an invalid combination, get the right values from the old IDs
    if ($invalid) {
      $options[OPTION_SCORING] = $options[107] ?? 0;
      $options[OPTION_DRAFT] = $options[106] ?? 0;
      // Stale pre-2024 OPTION_DRAFT values stored at this key are out of range for
      // OPTION_DECKS and would filter out every card; reset so the default applies
      if (isset($options[OPTION_DECKS]) && $options[OPTION_DECKS] > 2) {
        unset($options[OPTION_DECKS]);
      }
    }

    // Safety: default these if ever absent from $options (e.g. legacy/partial option sets)
    $options[OPTION_SNAKE_OPENING] = $options[OPTION_SNAKE_OPENING] ?? OPTION_SNAKE_OPENING_DISABLED;
    $options[OPTION_ADDITIONAL_SPACES] = $options[OPTION_ADDITIONAL_SPACES] ?? OPTION_ADDITIONAL_SPACES_DISABLED;

    return $options;
  }

  /*
   * setupNewGame:
   */
  protected function setupNewGame($players, $options = [])
  {
    $options = self::forceOptionCompatibility($options);

    $options[OPTION_DECKS] ??= OPTION_DECKS_ALL;
    $options[OPTION_DECK_A] ??= OPTION_DECK_ENABLED;
    $options[OPTION_DECK_B] ??= OPTION_DECK_ENABLED;
    $options[OPTION_DECK_C] ??= OPTION_DECK_ENABLED;
    $options[OPTION_DECK_D] ??= OPTION_DECK_ENABLED;
    $options[OPTION_DECK_E] ??= OPTION_DECK_ENABLED;
    $options[OPTION_DECK_CD] ??= OPTION_DECK_ENABLED;
    $options[OPTION_DECK_BASE] ??= OPTION_DECK_ENABLED;

    if (
      $options[OPTION_DECKS] == OPTION_DECKS_CUSTOM &&
      $options[OPTION_DECK_BASE] == OPTION_DECK_DISABLED &&
      $options[OPTION_DECK_A] == OPTION_DECK_DISABLED &&
      $options[OPTION_DECK_B] == OPTION_DECK_DISABLED &&
      $options[OPTION_DECK_C] == OPTION_DECK_DISABLED &&
      $options[OPTION_DECK_D] == OPTION_DECK_DISABLED &&
      $options[OPTION_DECK_E] == OPTION_DECK_DISABLED &&
      $options[OPTION_DECK_CD] == OPTION_DECK_DISABLED
    ) {
      $options[OPTION_COMPETITIVE_LEVEL] = OPTION_COMPETITIVE_BEGINNER;
      $options[OPTION_DRAFT] = 0;
    }

    // Player count check: the option can arrive stale from a multiplayer table (hidden by
    // displaycondition but still stored, and Studio quick-play skips startcondition)
    $campaign = count($players) == 1 && ($options[OPTION_CAMPAIGN] ?? OPTION_CAMPAIGN_DISABLED) == OPTION_CAMPAIGN_ENABLED;

    Players::setupNewGame($players, $options);
    Meeples::setupNewGame($players, $options);
    Globals::setupNewGame($players, $options);
    if ($campaign) {
      Globals::setCampaignOptions($options);
      Campaign::setup();
    }
    Preferences::setupNewGame($players, $this->player_preferences);
    ActionCards::setupNewGame($players, $options);
    PlayerCards::setupNewGame($players, $options);
    Stats::checkExistence();

    $this->setGameStateInitialValue('logging', false);
  }

  /*
   * getAllDatas:
   */
  public function getAllDatas()
  {
    $this->maybeAutoResolveStuckEngine();
    $pId = self::getCurrentPId();
    return [
      'prefs' => Preferences::getUiData($pId),
      'players' => Players::getUiData($pId),
      'cards' => ActionCards::getUiData(),
      'playerCards' => PlayerCards::getUiData(),
      'meeples' => Meeples::getUiData(),
      'scores' => Globals::isLiveScoring() ? Scores::compute() : null,
      'canceledNotifIds' => AGR\Helpers\Log::getCanceledNotifIds(),

      'turn' => Globals::getTurn(),
      'gameFlowPhase' => Globals::getGameFlowPhase(),
      'harvest' => Globals::getHarvest(),
      'fieldPhase' => Globals::getFieldPhase(),
      'breedPhase' => Globals::getBreedPhase(),
      'isAdditional' => Globals::isAdditional(),
      'isBeginner' => Globals::isBeginner(),
      'seed' => (!Campaign::isActive() && Globals::getTurn() == 15) ? Globals::getGameSeed() : false,

      'engine' => Globals::getEngine(), // DEBUG : TODO remove

      // Very special case to avoid notification size limit
      'draft' =>
        Globals::getDraftMode() != OPTION_DRAFT_FREE || Players::get($pId) === null
          ? []
          : Players::get($pId)
            ->getCards()
            ->filter(function ($card) {
              return in_array($card->getLocation(), ['draft', 'selection']);
            })
            ->toArray(),

      // Only expose per-player draft turns for new async games (getDraftTurn() === 0).
      // Old sync games have getDraftTurn() > 0 and should not show the async progress pills.
      'draftPlayerTurns' => (Globals::getDraftMode() != OPTION_DRAFT_DISABLED && Globals::getDraftTurn() === 0) ? $this->getAllPlayerDraftTurns() : null,
      'draftTotalTurns' => (Globals::getDraftMode() != OPTION_DRAFT_DISABLED && Globals::getDraftTurn() === 0) ? $this->getDraftTotalNumberOfTurns() : null,
      'draftFirstPlayer' => (Globals::getDraftMode() != OPTION_DRAFT_DISABLED) ? Globals::getFirstPlayer() : null,

      // Snake opening: visible during draft (turn 0) and round 1 only
      'snakeOpening' => Globals::isSnakeOpening(),
      'snakeTurnOrder' => Globals::isSnakeOpening() ? Players::getTurnOrder() : null,

      'campaign' => Campaign::getUiData(),
    ];
  }

  /*
   * getGameProgression:
   */
  function getGameProgression()
  {
    if (Campaign::isActive()) {
      // Progress across the standard 8-game arc (caps at 100% for extended play)
      $cleared = Campaign::getGameNumber() - 1;
      return min(100, ($cleared * 14 + Globals::getTurn()) * 100 / (8 * 14));
    }
    return (Globals::getTurn() * 100) / 14;
  }

  function actChangePreference($pref, $value)
  {
    Preferences::set($this->getCurrentPId(), $pref, $value);
  }

  ///////////////////////////////////////////////
  ///////////////////////////////////////////////
  ////////////   Custom Turn Order   ////////////
  ///////////////////////////////////////////////
  ///////////////////////////////////////////////
  public function initCustomTurnOrder(
    $key,
    $order,
    $callback,
    $endCallback,
    $loop = false,
    $autoNext = true,
    $args = []
  ) {
    if ($order == HARVEST) {
      // Handle players skipped by harvest
      $order = Players::getHarvestTurnOrder(null, $key);
    }

    $turnOrders = Globals::getCustomTurnOrders();
    $turnOrders[$key] = [
      'order' => $order ?? Players::getTurnOrder(),
      'index' => -1,
      'callback' => $callback,
      'args' => $args, // Useful mostly for auto card listeners
      'endCallback' => $endCallback,
      'loop' => $loop,
    ];
    Globals::setCustomTurnOrders($turnOrders);

    // Snake opening: ensure labour starts unflipped in round 1
    if ($key === 'labor' && Globals::isSnakeOpening() && Globals::getTurn() == 1) {
      Globals::setSnakeOpeningFlipped(false);
    }

    if ($autoNext) {
      $this->nextPlayerCustomOrder($key);
    }
  }

  public function initCustomDefaultTurnOrder($key, $callback, $endCallback, $loop = false, $autoNext = true)
  {
    $this->initCustomTurnOrder($key, null, $callback, $endCallback, $loop, $autoNext);
  }

  public function nextPlayerCustomOrder($key)
  {
    $turnOrders = Globals::getCustomTurnOrders();
    if (!isset($turnOrders[$key])) {
      throw new BgaVisibleSystemException(
        'Asking for the next player of a custom turn order not initialized : ' . $key
      );
    }

    // Increase index and save
    $o = $turnOrders[$key];
    $i = $o['index'] + 1;

    // Snake opening: when labor would loop for the first time in round 1,
    // flip the order so second placements go in reverse turn order.
    if (
      $key === 'labor'
      && $o['loop']
      && $i == count($o['order'])
      && Globals::isSnakeOpening()
      && !Globals::isSnakeOpeningFlipped()
      && Globals::getTurn() == 1
    ) {
      $turnOrders[$key]['order'] = array_reverse($o['order']);
      Globals::setSnakeOpeningFlipped(true);

      // Refresh local copy for the loop calculation below
      $o = $turnOrders[$key];
    }

    if ($i == count($o['order']) && $o['loop']) {
      $i = 0;
    }
    
    $turnOrders[$key]['index'] = $i;
    Globals::setCustomTurnOrders($turnOrders);

    if ($i < count($o['order'])) {
      $this->gamestate->jumpToState(ST_GENERIC_NEXT_PLAYER);
      $this->gamestate->changeActivePlayer($o['order'][$i]);
      $this->jumpToOrCall($o['callback'], $o['args']);
    } else {
      $this->endCustomOrder($key);
    }
  }

  public function endCustomOrder($key)
  {
    $turnOrders = Globals::getCustomTurnOrders();
    if (!isset($turnOrders[$key])) {
      throw new BgaVisibleSystemException('Asking for ending a custom turn order not initialized : ' . $key);
    }

    $o = $turnOrders[$key];
    $turnOrders[$key]['index'] = count($o['order']);
    Globals::setCustomTurnOrders($turnOrders);
    $callback = $o['endCallback'];
    $this->jumpToOrCall($callback);
  }

  public function jumpToOrCall($mixed, $args = [])
  {
    if (is_int($mixed) && array_key_exists($mixed, $this->gamestate->states)) {
      $this->gamestate->jumpToState($mixed);
    } elseif (method_exists($this, $mixed)) {
      $method = $mixed;
      $this->$method($args);
    } else {
      throw new BgaVisibleSystemException('Failing to jumpToOrCall  : ' . $mixed);
    }
  }

  /********************************************
   ******* GENERIC CARD LISTENERS CHECK ********
   ********************************************/
  /*
   * A lot of time you want to loop through all the player to see if a card react or not
   *  => this is achieved using custom turn order with an arg containing the eventType
   *  => the custom order will call the genericPlayerCheckListeners that will getReaction from cards if any
   */
  public function checkCardListeners($typeEvent, $endCallback, $event = [], $order = null)
  {
    $event['type'] = $typeEvent;
    $event['method'] = $typeEvent;
    $this->initCustomTurnOrder($typeEvent, $order, 'genericPlayerCheckListeners', $endCallback, false, true, $event);
  }

  function genericPlayerCheckListeners($event)
  {
    $pId = Players::getActiveId();
    $event['pId'] = $pId;
    $reaction = PlayerCards::getReaction($event);

    if (is_null($reaction)) {
      // No reaction => just go to next player
      $this->nextPlayerCustomOrder($event['type']);
    } else {
      // Reaction => boot up the Engine
      Engine::setup($reaction, ['order' => $event['type']]);
      Engine::proceed();
    }
  }

  ////////////////////////////////////
  ////////////   Zombie   ////////////
  ////////////////////////////////////
  /*
   * zombieTurn:
   *   This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
   *   You can do whatever you want in order to make sure the turn of this player ends appropriately
   */
  public function zombieTurn($state, $activePlayer)
  {
    $skipped = Globals::getSkippedPlayers();
    if (!in_array((int) $activePlayer, $skipped)) {
      $skipped[] = (int) $activePlayer;
      Globals::setSkippedPlayers($skipped);
    }

    $stateName = $state['name'];
    if ($state['type'] === 'activeplayer') {
      if ($stateName == 'confirmTurn') {
        Engine::confirm();
      } elseif ($stateName == 'confirmPartialTurn') {
        Engine::confirmPartialTurn();
      }
      // Living Hand states bypass the engine — handle zombie explicitly
      elseif ($stateName == 'livingHandRefill') {
        PlayerCards::clearLivingHandOffer($activePlayer);
        $this->nextPlayerCustomOrder('labor');
      }
      elseif ($stateName == 'livingHandPassingDecision') {
        // Auto-decline pending card for zombie, then advance
        $pending = Globals::getLivingHandPendingPassing();
        if (is_array($pending) && isset($pending[$activePlayer])) {
          foreach ($pending[$activePlayer] as $cardId) {
            PlayerCards::moveToDiscardpile($cardId);
          }
          unset($pending[$activePlayer]);
          Globals::setLivingHandPendingPassing($pending);
        }
        $this->nextPlayerCustomOrder('labor');
      }
      // Clear all node of player
      elseif (Engine::getNextUnresolved() != null) {
        Engine::clearZombieNodes($activePlayer);
        Engine::proceed();
      } else {
        $this->gamestate->nextState('zombiePass');
      }
    } elseif ($state['type'] === 'multipleactiveplayer') {
      // Make sure player is in a non blocking status for role turn
      $this->gamestate->setPlayerNonMultiactive($activePlayer, '');
    }
  }

  /////////////////////////////////////
  //////////   DB upgrade   ///////////
  /////////////////////////////////////
  // You don't have to care about this until your game has been published on BGA.
  // Once your game is on BGA, this method is called everytime the system detects a game running with your old Database scheme.
  // In this case, if you change your Database scheme, you just have to apply the needed changes in order to
  //   update the game database and allow the game to continue to run with your new version.
  /////////////////////////////////////
  /*
   * upgradeTableDb
   *  - int $from_version : current version of this game database, in numerical form.
   *      For example, if the game was running with a release of your game named "140430-1345", $from_version is equal to 1404301345
   */
  public function upgradeTableDb($from_version)
  {
    if ($from_version <= 2107011810) {
      $sql = "UPDATE `DBPREFIX_cards` SET card_location = 'selection' WHERE card_location = 'hand' AND card_state = 1";
      self::applyDbUpgradeToAllDB($sql);
    }

    if ($from_version <= 2109171326) {
      $sql = "SELECT * FROM `global_variables` WHERE `name` = 'engine' AND `value` LIKE '%\"trigger\": 3%' ";
      $row = self::getUniqueValueFromDB($sql);
      $val = $row == null ? 'false' : 'true';
      $sql = "INSERT INTO `DBPREFIX_global_variables` (`name`, `value`) VALUES ('harvest', '$val')";
      self::applyDbUpgradeToAllDB($sql);
    }
  }

  /////////////////////////////////////////////////////////////
  // Exposing protected method, please use at your own risk //
  /////////////////////////////////////////////////////////////

  // Exposing protected method getCurrentPlayerId
  public static function getCurrentPId()
  {
    return self::get()->getCurrentPlayerId();
  }
}
