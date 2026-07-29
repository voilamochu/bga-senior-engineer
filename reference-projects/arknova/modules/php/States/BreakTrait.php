<?php

namespace ARK\States;

use ARK\Core\Globals;
use ARK\Core\Notifications;
use ARK\Core\Engine;
use ARK\Core\Stats;
use ARK\Helpers\FlowConvertor;
use ARK\Managers\Players;
use ARK\Managers\ActionCards;
use ARK\Managers\ZooCards;
use ARK\Managers\Meeples;
use ARK\Managers\Farmers;
use ARK\Managers\Actions;
use ARK\Helpers\Utils;

trait BreakTrait
{
  /**
   * Discard cards phase
   */
  public function stBreakPreCards()
  {
    if (Globals::isSolo()) {
      // move topmost token to donation
      $tokens = Meeples::breakClearSoloTokens()->toArray();
      Notifications::slideMeeples($tokens);
      if (count($tokens) == 2) {
        Globals::setEndTriggered(true);
        Globals::getEndRemainingPlayers() == [];
        if (Globals::getEndFinalScoringDone() !== true) {
          // Trigger discard state
          Engine::setup(
            [
              'action' => DISCARD_SCORING,
              'pId' => 'all',
              'args' => ['current' => Players::getActive()->getId()],
            ],
            ''
          );
          Engine::proceed();
        } else {
          // Goto scoring state
          $this->gamestate->jumpToState(\ST_PRE_END_OF_GAME);
        }
        return;
      }
    }

    Globals::setMustBreak(false);
    Notifications::startBreak();
    Stats::incBreaks();

    // Compute the list of players with too much cards in hand and make them active
    $players = Players::getAll();
    $pIds = [];
    $selection = [];

    foreach ($players as $pId => $player) {
      if (count($player->getHand()) > $player->getHandLimit()) {
        $pIds[] = $pId;
        self::giveExtraTime($player->getId(), 60);
      } else {
        $selection[$pId] = [];
      }
    }
    Globals::setBreakDiscardSelection($selection);

    if (!empty($pIds)) {
      $this->gamestate->setPlayersMultiactive($pIds, 'refill', true);
      $this->gamestate->nextState('discard');
    } else {
      $this->gamestate->nextState('refill');
    }
  }

  ////////////////////////////////////////////////////////////////////////
  //  _     ____  _                       _    ____              _
  // / |   |  _ \(_)___  ___ __ _ _ __ __| |  / ___|__ _ _ __ __| |___
  // | |   | | | | / __|/ __/ _` | '__/ _` | | |   / _` | '__/ _` / __|
  // | |_  | |_| | \__ \ (_| (_| | | | (_| | | |__| (_| | | | (_| \__ \
  // |_(_) |____/|_|___/\___\__,_|_|  \__,_|  \____\__,_|_|  \__,_|___/
  ////////////////////////////////////////////////////////////////////////

  public function argsBreakDiscard()
  {
    $selection = Globals::getBreakDiscardSelection();
    $args = ['_private' => []];
    foreach (Players::getAll() as $pId => $player) {
      $hand = $player->getHand();
      $args['_private'][$pId] = [
        'n' => $hand->count() - $player->getHandLimit(),
        'cards' => $hand->getIds(),
        'selection' => $selection[$pId] ?? null,
      ];
    }

    return $args;
  }

  public function actBreakDiscardSelectCards($cardIds)
  {
    self::checkAction('actBreakDiscardSelectCards');

    $player = Players::getCurrent();
    $selection = Globals::getBreakDiscardSelection();
    $selection[$player->getId()] = $cardIds;
    Globals::setBreakDiscardSelection($selection);
    Notifications::updateBreakDiscardSelection($player, $this->argsBreakDiscard());

    $this->updateActivePlayersBreakDiscardSelection();
  }

  public function actCancelBreakDiscardSelection()
  {
    $this->gamestate->checkPossibleAction('actCancelBreakDiscardSelection');

    $player = Players::getCurrent();
    $selection = Globals::getBreakDiscardSelection();
    unset($selection[$player->getId()]);
    Globals::setBreakDiscardSelection($selection);
    Notifications::updateBreakDiscardSelection($player, $this->argsBreakDiscard());

    $this->updateActivePlayersBreakDiscardSelection();
  }

  public function updateActivePlayersBreakDiscardSelection()
  {
    // Compute players that still need to select their card
    // => use that instead of BGA framework feature because in some rare case a player
    //    might become inactive eventhough the selection failed (seen in Agricola and Rauha at least already)
    $selection = Globals::getBreakDiscardSelection();
    $players = Players::getAll();
    $ids = $players->getIds();
    $ids = array_diff($ids, array_keys($selection));

    // At least one player need to make a choice
    if (!empty($ids)) {
      $this->gamestate->setPlayersMultiactive($ids, 'done', true);
    }
    // Everyone is done => discard cards and proceed
    else {
      $selection = Globals::getBreakDiscardSelection();
      foreach ($players as $pId => $player) {
        $cardIds = $selection[$pId];
        if (empty($cardIds)) {
          continue;
        }

        $cards = ZooCards::getMany($cardIds);
        ZooCards::discard($cardIds);
        Notifications::discardCards($player, $cards, null, clienttranslate('${player_name} discards ${n} cards during break'));
      }

      $this->gamestate->nextState('done');
    }
  }

  ////////////////////////////////////////
  //  ____      ____       __ _ _ _
  // |___ \    |  _ \ ___ / _(_) | |
  //   __) |   | |_) / _ \ |_| | | |
  //  / __/ _  |  _ <  __/  _| | | |
  // |_____(_) |_| \_\___|_| |_|_|_|
  ////////////////////////////////////////

  public function stBreakRefill()
  {
    // Remove tokens on cards (multiplier, constriction, venom)
    $deletedTokens = Meeples::breakCleanupTokens();
    if (!empty($deletedTokens)) {
      Notifications::breakCleanupTokens($deletedTokens->toArray());
    }

    // Move association workers back to player notepad
    $workers = Meeples::breakReturnWorkers();
    if (!empty($workers)) {
      Notifications::breakReturnWorkers($workers);
    }

    // Replenish zoo universities & partner zoos
    $meeples = Meeples::breakRefill();
    if (!empty($meeples)) {
      Notifications::breakRefill($meeples);
    }

    // remove first 2 cards and replenish
    $cards = ZooCards::getPool(2);
    list($discarded, $assigned, $meeples) = ZooCards::discard($cards->getIds());
    Notifications::discardPoolCardsBreak($discarded);
    Notifications::markAssign($assigned, $meeples);
    ZooCards::fillPool();

    // go to income
    $this->initCustomTurnOrder('breakIncome', Players::getTurnOrder(Globals::getBreakPlayer()), ST_BREAK_INCOME, ST_BREAK_FINISH);
  }

  //////////////////////////////////////////////////
  //  ____     ___
  // | ___|   |_ _|_ __   ___ ___  _ __ ___   ___
  // |___ \    | || '_ \ / __/ _ \| '_ ` _ \ / _ \
  //  ___) |   | || | | | (_| (_) | | | | | |  __/
  // |____(_) |___|_| |_|\___\___/|_| |_| |_|\___|
  //////////////////////////////////////////////////
  public function stBreakIncome()
  {
    // Refill if pool is empty
    ZooCards::fillPool();

    $player = Players::getActive();
    $bonuses = $player->getIncome();
    list($immediate, $after) = FlowConvertor::getFlow($bonuses, 'map bonus space', 'incomeBonusSpace');
    if ((empty($immediate) && empty($after)) || $player->isZombie()) {
      $this->nextPlayerCustomOrder('breakIncome');
    } else {
      Engine::setup(
        [
          'type' => \NODE_PARALLEL,
          'childs' => array_merge($immediate, $after),
          'flag' => 'ChooseIncomeNode',
        ],
        ['order' => 'breakIncome']
      );
      Engine::proceed();
    }

    // to avoid restarting turn after income
    Engine::checkpoint();
  }

  //////////////////////////////////////////////////////////////////////////
  //    __      ____                _      ____                  _
  //   / /_    |  _ \ ___  ___  ___| |_   / ___|___  _   _ _ __ | |_ ___ _ __
  //  | '_ \   | |_) / _ \/ __|/ _ \ __| | |   / _ \| | | | '_ \| __/ _ \ '__|
  //  | (_) |  |  _ <  __/\__ \  __/ |_  | |__| (_) | |_| | | | | ||  __/ |
  //   \___(_) |_| \_\___||___/\___|\__|  \____\___/ \__,_|_| |_|\__\___|_|
  //////////////////////////////////////////////////////////////////////////
  public function stBreakFinish()
  {
    Globals::setBreak(0);
    Globals::setBreakPlayer(-1);
    Notifications::finishBreak();
    // Refill if pool is empty
    ZooCards::fillPool();

    // Cleanup globals
    Globals::setUsedVenom(false);
    Globals::setVenomPaid(false);
    Globals::setVenomTriggered(false);
    Globals::setEffectMap4(false);
    Globals::setEffectCamouflage(0);
    Globals::setMapT1Used(false);
    Globals::setActiveT1Effect(false);
    Globals::setLandscapeGardener(false);
    Globals::setSponsors2Gained(false);

    // Init of next player
    $this->gamestate->nextState('next');
  }
}
