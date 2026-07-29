<?php

namespace ARK;

use ARK\Core\Globals;
use ARK\Managers\Players;
use ARK\Managers\Meeples;
use ARK\Managers\Fences;
use ARK\Managers\ActionCards;
use ARK\Managers\ZooCards;
use ARK\Core\Engine;
use ARK\Core\Game;
use ARK\Models\PlayerBoard;
use ARK\Core\Notifications;
use ARK\Helpers\Utils;
use ARK\Helpers\Log;
use ARK\Helpers\Collection;
use ARK\Managers\Buildings;
use ARK\Models\ZooCard;

trait DebugTrait
{
  function undoToStep($stepId)
  {
    Log::undoToStep($stepId);
  }

  function tp()
  {
    $this->gamestate->jumpToState(ST_PRE_END_OF_GAME);
    // $player = Players::getCurrent();
    // $player->pay(21, true, "");
    // $player->incXToken(4);
    // $player->incMoney(50);
    //    Log::clearUndoableStepNotifications(true);
    // Meeples::addBonusToPlayer($player, BONUS_SPONSOR_MONEY_MW, 1);
    // $card = ZooCards::getSingle("A505_BaldEagle");
    // var_dump($card->isMarked());
  }

  function dv()
  {
    // Globals::setEndTriggered(true);
    $this->gamestate->jumpToState(ST_END_GAME);
  }

  function vt()
  {
    // $this->actChoiceCards(['Association4', 'Animals4']);
    // throw new \feException(print_r(Players::getCurrent()->getActionCards()));
    // throw new \feException(print_r(Players::getCurrent()->getActionCards()));
    // $this->actTakeAtomicAction('actAssociation', [4, 9, 'fac-science-primate']);
    // $this->actTakeAtomicAction('actTakeCards', [['A402_Lion'], 1]);
    // $this->actTakeAtomicAction('actBoost', [5]);
    $this->actTakeAtomicAction('actSymbiosis', ['A534_SouthernBlueringedOctopus', VENOM]);
    // $this->actBreakDiscard(['A448_Koala', 'A454_RingtailedLemur', 'A463_PanamanianWhitefacedCapuchin']);
    // $this->actBreakDiscard(['A520_Sheep']);
    // Globals::setEndRemainingPlayers([]);
    // Globals::setEndTriggered(false);
    // $this->actTakeAtomicAction('actDominance', ['A403_Leopard']);
    // $this->actTakeAtomicAction('actAssociation', [['strength' => 3, 'token' => 2, 'workers' => 1]]);
    // $this->actTakeAtomicAction('actAssociation', [
    //   [
    //     'strength' => 5,
    //     'workers' => 1,
    //     'bonus' => 9,
    //     'cardId' => 'P118_ReleaseSavanna',
    //     'support' => 'P118_ReleaseSavanna-1',
    //     'animalId' => 'A408_SlothBear',
    //   ],
    // ]);

    // ActionCards::setupNewGame(Players::getAll()->toAssoc(), []);
    // Engine::insertAsChild(['action' => GAIN, 'args' => [XTOKEN => 5]]);
    // Engine::insertAtRoot(['action' => MAP4], false);
    // Engine::proceed();
    // throw new \feException(print_r(Globals::getEngine()));
    // $bonuses = Players::getActive()->incReputation(2);
    // $bonuses = [UNIVERSITY => 1];
    // $this->insertBonusesFlow($bonuses, '', 'bonusTile');
    // Players::getActive()->incXToken(8);
    // Buildings::setupNextGame();
    // $this->setupNextGame();
    // throw new \feException(print_r(ZooCards::get('F009_DiverseSpeciesZoo')->score()));
    //
    // $this->actTakeAtomicAction('actMoveAnimals', ['A493_ThornyDevil', 17, 1]);
  }

  function tv()
  {
    // throw new \feException(print_r(ZooCards::get('A401_Cheetah')->getIcons()));
    // $this->actTakeAtomicAction('actGainUniversity', [2]);
    ZooCards::get('F003_ResearchZoo')->score();
    // Players::getActive()->updateScore();
    // Notifications::endOfGame();
  }

  function score($cardId)
  {
    $card = ZooCards::get($cardId);
    // if (!$card->isPlayed()) {
    //   throw new \feException('not played');
    // }

    $card->score();
  }

  function resolveDebug()
  {
    Engine::resolveAction([]);
    Engine::proceed();
  }

  function allVisible()
  {
    $sql = "UPDATE `cards` set `card_state` = 1 where `card_location` like 'turn%'";
    self::DbQuery($sql);
  }

  function playCardAux($cardId, $doAction = true)
  {
    $player = Players::getCurrent();
    $pId = $player->getId();

    $sql = "SELECT * FROM cards WHERE card_id = '$cardId' LIMIT 1";
    $card = self::getUniqueValueFromDB($sql);

    if (is_null($card)) {
      $sql = "UPDATE cards set card_id = '$cardId' where player_id = $pId AND `card_location` <> 'inPlay' LIMIT 1";
    } else {
      $sql = "UPDATE cards set player_id = $pId where card_id = '$cardId'";
    }
    self::DbQuery($sql);

    if ($doAction) {
      $this->actTakeAtomicAction([$cardId]);
    }
  }

  function addHand($cardId)
  {
    $player = Players::getCurrent();
    $pId = $player->getId();
    $sql = "UPDATE cards set player_id = $pId, card_location = 'hand' where card_id = '$cardId'";
    self::DbQuery($sql);
    Notifications::drawCards($player, new Collection([ZooCards::get($cardId)]));
    // $this->insertAsChild([
    //   'action' => CHOOSE_ACTION_CARD,
    //   'pId' => $player->getId(),
    // ]);
    // Engine::resolveAction();
    //    Engine::proceed();
  }

  function playCard($cardId)
  {
    $this->playCardAux($cardId, true);
  }

  function addCard($cardId)
  {
    $this->playCardAux($cardId, false);
    $sql = "UPDATE cards set card_location = 'inPlay' where card_id = '$cardId'";
    self::DbQuery($sql);
  }

  function drawCard($cardId)
  {
    $this->playCardAux($cardId, false);
    $sql = "UPDATE cards set card_location = 'hand' where card_id = '$cardId'";
    self::DbQuery($sql);
  }

  function engDisplay()
  {
    var_dump(Globals::getEngine());
  }

  function engProceed()
  {
    Engine::proceed();
  }

  /*
   * loadBug: in studio, type loadBug(20762) into the table chat to load a bug report from production
   * client side JavaScript will fetch each URL below in sequence, then refresh the page
   */
  public function loadBug($reportId)
  {
    $db = explode('_', self::getUniqueValueFromDB("SELECT SUBSTRING_INDEX(DATABASE(), '_', -2)"));
    $game = $db[0];
    $tableId = $db[1];
    self::notifyAllPlayers(
      'loadBug',
      "Trying to load <a href='https://boardgamearena.com/bug?id=$reportId' target='_blank'>bug report $reportId</a>",
      [
        'urls' => [
          // Emulates "load bug report" in control panel
          "https://studio.boardgamearena.com/admin/studio/getSavedGameStateFromProduction.html?game=$game&report_id=$reportId&table_id=$tableId",

          // Emulates "load 1" at this table
          "https://studio.boardgamearena.com/table/table/loadSaveState.html?table=$tableId&state=1",

          // Calls the function below to update SQL
          "https://studio.boardgamearena.com/1/$game/$game/loadBugReportSQL.html?table=$tableId&report_id=$reportId",

          // Emulates "clear PHP cache" in control panel
          // Needed at the end because BGA is caching player info
          "https://studio.boardgamearena.com/admin/studio/clearGameserverPhpCache.html?game=$game",
        ],
      ]
    );
  }

  /*
   * loadBugSQL: in studio, this is one of the URLs triggered by loadBug() above
   */
  public function loadBugReportSQL($reportId, $studioPlayersIds)
  {
    $players = self::getObjectListFromDb('SELECT player_id FROM player', true);

    // Change for your game
    // We are setting the current state to match the start of a player's turn if it's already game over
    $sql = ['UPDATE global SET global_value=2 WHERE global_id=1 AND global_value=99'];
    $sql[] = 'ALTER TABLE `gamelog` ADD `cancel` TINYINT(1) NOT NULL DEFAULT 0;';
    $map = [];
    foreach ($players as $index => $pId) {
      $studioPlayer = $studioPlayersIds[$index];
      $map[(int) $pId] = (int) $studioPlayer;

      // All games can keep this SQL
      $sql[] = "UPDATE player SET player_id=$studioPlayer WHERE player_id=$pId";
      $sql[] = "UPDATE global SET global_value=$studioPlayer WHERE global_value=$pId";
      $sql[] = "UPDATE stats SET stats_player_id=$studioPlayer WHERE stats_player_id=$pId";

      // Add game-specific SQL update the tables for your game
      $sql[] = "UPDATE meeples SET player_id=$studioPlayer WHERE player_id=$pId";
      $sql[] = "UPDATE cards SET player_id=$studioPlayer WHERE player_id=$pId";
      $sql[] = "UPDATE actioncards SET player_id=$studioPlayer WHERE player_id=$pId";
      $sql[] = "UPDATE buildings SET player_id=$studioPlayer WHERE player_id=$pId";
      $sql[] = "UPDATE user_preferences SET player_id=$studioPlayer WHERE player_id=$pId";

      // This could be improved, it assumes you had sequential studio accounts before loading
      // e.g., quietmint0, quietmint1, quietmint2, etc. are at the table
      $studioPlayer++;
    }
    $msg =
      "<b>Loaded <a href='https://boardgamearena.com/bug?id=$reportId' target='_blank'>bug report $reportId</a></b><hr><ul><li>" .
      implode(';</li><li>', $sql) .
      ';</li></ul>';
    self::warn($msg);
    self::notifyAllPlayers('message', $msg, []);

    foreach ($sql as $q) {
      self::DbQuery($q);
    }

    /******************
     *** Fix Globals ***
     ******************/

    // Turn orders
    $turnOrders = Globals::getCustomTurnOrders();
    foreach ($turnOrders as $key => &$order) {
      $t = [];
      foreach ($order['order'] as $pId) {
        $t[] = $map[$pId];
      }
      $order['order'] = $t;
    }
    Globals::setCustomTurnOrders($turnOrders);

    // Engine
    $engine = Globals::getEngine();
    $this->loadDebugUpdateEngine($engine, $map);
    Globals::setEngine($engine);

    // First player
    $fp = Globals::getFirstPlayer();
    Globals::setFirstPlayer($map[$fp]);

    self::reloadPlayersBasicInfos();
  }

  function loadDebugUpdateEngine(&$node, $map)
  {
    if (isset($node['pId'])) {
      $node['pId'] = $map[(int) $node['pId']];
    }

    if (isset($node['childs'])) {
      foreach ($node['childs'] as &$child) {
        $this->loadDebugUpdateEngine($child, $map);
      }
    }
  }
}
