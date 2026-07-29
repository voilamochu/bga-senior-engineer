<?php

namespace ARK\States;

use ARK\Core\Globals;
use ARK\Core\Notifications;
use ARK\Core\Engine;
use ARK\Core\Stats;
use ARK\Core\Preferences;
use ARK\Helpers\Collection;
use ARK\Managers\Players;
use ARK\Managers\ActionCards;
use ARK\Managers\ZooCards;
use ARK\Managers\Meeples;
use ARK\Managers\Buildings;
use ARK\Managers\Actions;
use ARK\Helpers\Log;
use ARK\Helpers\Utils;

trait SetupTrait
{
  /*
   * setupNewGame:
   */
  protected function setupNewGame($players, $options = [])
  {
    Globals::setupNewGame($players, $options);
    Players::setupNewGame($players, $options);
    Preferences::setupNewGame($players, $this->player_preferences);
    ZooCards::setupNewGame($players, $options);
    Meeples::setupNewGame($players, $options);
    Stats::checkExistence();

    Globals::setFirstPlayer($this->getNextPlayerTable()[0]);

    if (
      Globals::getSoloChallenge() < 1
      && (Globals::isFirstGame() || Globals::isBeginner() || Globals::getSameMap() != 0)
    ) {
      // We can init meeples and finish ZooCard setup
      foreach (Players::getAll() as $pId => $player) {
        $this->setupPlayer($player, false);
      }

      // Draw cards only if not MW
      if (!Globals::isMarineWorld()) {
        ZooCards::initialDraw();
      }
    }

    $this->setGameStateInitialValue('logging', false);
    $this->activeNextPlayer();
  }

  /*
   * Next game for solo challenge
   */
  protected function setupNextGame()
  {
    Globals::setupNewGame([1], [], true);
    Players::setupNextGame();
    Globals::setInitialMapSelection([]);
    Globals::setInitialSelection([]);
    ActionCards::setupNextGame();
    Buildings::setupNextGame();

    // remove all cards
    $player = Players::getActive();
    ZooCards::move(ZooCards::getInLocation('base_%')->getIds(), 'previousBase');
    ZooCards::move(ZooCards::getInLocation('pool_%')->getIds(), 'previousCards');
    ZooCards::move(ZooCards::getInLocation('projects_%')->getIds(), 'previousCards');
    ZooCards::move(ZooCards::getInLocation('scoringHand')->getIds(), 'previousCards');
    ZooCards::move(ZooCards::getInLocation('hand')->getIds(), 'previousCards');
    ZooCards::move(ZooCards::getInLocation('inPlay')->getIds(), 'previousCards');
    ZooCards::move(ZooCards::getInLocation('rescueStation')->getIds(), 'previousCards');
    ZooCards::move(ZooCards::getInLocation('discard')->getIds(), 'previousCards');

    // init base projects
    for ($i = 0; $i < 3; $i++) {
      ZooCards::pickForLocation(1, 'projectDeck', 'base_' . $i);
    }

    Meeples::setupNextGame();
    Globals::fetch();
    Notifications::removeActionCards();

    // Notify
    $datas = $this->getAllDatas();
    Notifications::refreshUI($datas);
    $player = Players::getActive();
    Notifications::refreshHand($player, $player->getHand()->ui());
    // Transition
    $this->gamestate->jumpToState(ST_SETUP_BRANCH);
  }

  /*
   * Setup an individual player => associate the map
   */
  protected function setupPlayer($player, $notif = false)
  {
    $pId = $player->getId();
    $meeples = Meeples::setupPlayer($pId);
    if (Globals::isMarineWorld()) {
      $cards = new Collection();
    } else {
      $cards = ActionCards::setupPlayer($pId);
    }

    $mapId = $player->getMapId();
    $mapStatValue = (int) $mapId;
    if ($mapId == 'A') {
      $mapStatValue = 100;
    } else if ($mapId == 'T1') {
      $mapStatValue = 200;
    } elseif (in_array($mapId, ALTERNATIVE_MAPS)) {
      $mapStatValue += 100;
    }
    Stats::setMap($player, $mapStatValue);

    // Create buildings for map A
    $buildings = [];
    if ($mapId == 'A') {
      $buildings[] = Buildings::add($pId, 'size-3', ['x' => 0, 'y' => 9], 0);
      $buildings[] = Buildings::add($pId, 'kiosk', ['x' => 0, 'y' => 7], 0);
      Stats::incBuiltEnclosures($pId);
      Stats::incBuiltKiosks($pId);
      Stats::incCoveredHexes($pId, 4);
    }

    // Create building for map 13
    if ($mapId == 13) {
      $buildings[] = Buildings::add($pId, 'size-2', ['x' => 4, 'y' => 5], 0);
      Stats::incBuiltEnclosures($pId);
      Stats::incCoveredHexes($pId, 2);
    }

    if ($notif) {
      Notifications::setupPlayer($player, $mapId, $cards, $meeples, $buildings);
    }
  }


  public function stSetupBranch()
  {
    $this->gamestate->setAllPlayersMultiactive();
    if (Globals::getSoloChallenge() < 1 && (Globals::isFirstGame() || Globals::isBeginner() || Globals::getSameMap() != 0)) {
      if (Globals::isMarineWorld()) {
        $this->initActionCardsSelection();
        if (Globals::isSolo()) {
          $this->stFinishActionCardDraft();
        } else {
          $this->gamestate->jumpToState(ST_INITIAL_ACTION_CARD_DRAFT);
        }
      } else {
        // Automatically gave same map to each player already
        $this->gamestate->nextState('selection');
      }
    } else {
      $mapping = [];
      // Free setup : propose all maps to all players
      if (Globals::isFreeSetup()) {
        foreach (Players::getAll() as $pId => $player) {
          $maps = Globals::getAvailableMapsIds();
          $mapping[$pId] = $maps;
        }
      }
      // Normal setup : propose two map to each players
      else {
        $soloChallengeData = Globals::getSoloChallengeData();
        $possibleMaps = Globals::getAvailableMapsIds();
        $possibleMaps = array_diff($possibleMaps, $soloChallengeData['maps'] ?? []);
        shuffle($possibleMaps);
        $i = 0;
        foreach (Players::getAll() as $pId => $player) {
          $mapping[$pId] = [$possibleMaps[2 * $i], $possibleMaps[2 * $i + 1]];
          $soloChallengeData['maps'][] = $possibleMaps[2 * $i];
          $soloChallengeData['maps'][] = $possibleMaps[2 * $i + 1];
          $i++;
        }
        Globals::setSoloChallengeData($soloChallengeData);
        Globals::setSoloChallenge(Globals::getSoloChallenge() - 1);
      }

      // Save that into the global and proceed to the mapSelection state
      Globals::setPossibleMaps($mapping);
      $this->gamestate->nextState('mapSelection');
    }
  }

  ///////////////////////////
  //  __  __
  // |  \/  | __ _ _ __
  // | |\/| |/ _` | '_ \
  // | |  | | (_| | |_) |
  // |_|  |_|\__,_| .__/
  //              |_|
  ///////////////////////////

  public function argsInitialMapSelection(): array
  {
    $args = ['_private' => []];

    $possibleMaps = Globals::getPossibleMaps();
    $selection = Globals::getInitialMapSelection();
    foreach (Players::getAll() as $pId => $player) {
      $args['_private'][$pId] = [
        'maps' => $possibleMaps[$pId],
        'selection' => $selection[$pId] ?? null,
      ];
    }

    return $args;
  }

  public function actSelectMap(string $mapId)
  {
    // Sanity checks
    $this->gamestate->checkPossibleAction('actSelectMap');
    $player = Players::getCurrent();
    $possibleMaps = Globals::getPossibleMaps();
    if (!in_array($mapId, $possibleMaps[$player->getId()])) {
      throw new \BgaVisibleSystemException('You cannot select that zoo map. Should not happen');
    }

    $selection = Globals::getInitialMapSelection();
    $selection[$player->getId()] = $mapId;
    Globals::setInitialMapSelection($selection);
    Notifications::updateInitialMapSelection($player, $this->argsInitialMapSelection());

    // Compute players that still need to select their zoo map
    // => use that instead of BGA framework feature because in some rare case a player
    //    might become inactive eventhough the selection failed (seen in Agricola and Rauha at least already)
    $players = Players::getAll();
    $ids = $players->getIds();
    $ids = array_diff($ids, array_keys($selection));

    // At least one player need to make a choice
    if (!empty($ids)) {
      $this->gamestate->setPlayersMultiactive($ids, 'done', true);
    }
    // Everyone is done => proceed
    else {
      $this->finishZooMapSetup();
    }
  }

  /**
   * finishZooMapSetup: assign each map to the corresponding player, then continue setup
   */
  public function finishZooMapSetup()
  {
    $selection = Globals::getInitialMapSelection();
    foreach (Players::getAll() as $pId => $player) {
      $mapId = $selection[$pId];
      $player->setMapId($mapId);
      $this->setupPlayer($player, true);
    }

    // Branch depending on whether marine worlds is on
    if (Globals::isMarineWorld()) {
      $this->initActionCardsSelection();
      if (Globals::isSolo()) {
        $this->stFinishActionCardDraft();
      } else {
        $this->gamestate->setAllPlayersMultiactive();
        $this->gamestate->nextState('cardSelection');
      }
    } else {
      // Draw 8 zoo cards for each player
      ZooCards::initialDraw();

      $this->gamestate->setAllPlayersMultiactive();
      $this->gamestate->nextState('done');
    }
  }


  ///////////////////////////////////////////////////////////////
  //     _        _   _                ____              _     
  //    / \   ___| |_(_) ___  _ __    / ___|__ _ _ __ __| |___ 
  //   / _ \ / __| __| |/ _ \| '_ \  | |   / _` | '__/ _` / __|
  //  / ___ \ (__| |_| | (_) | | | | | |__| (_| | | | (_| \__ \
  // /_/   \_\___|\__|_|\___/|_| |_|  \____\__,_|_|  \__,_|___/
  ///////////////////////////////////////////////////////////////

  //  ____             __ _   
  // |  _ \ _ __ __ _ / _| |_ 
  // | | | | '__/ _` | |_| __|
  // | |_| | | | (_| |  _| |_ 
  // |____/|_|  \__,_|_|  \__|


  public function initActionCardsSelection()
  {
    $msg = clienttranslate('Starting the first round of action cards draft phase.');

    // Creation of the cards
    $draft = [];
    $actionCards = ActionCards::$MWActionCards;
    shuffle($actionCards);
    foreach (Players::getAll() as $pId => $player) {
      for ($i = 0; $i < 3; $i++) {
        $draft[$pId]['draft'][] = array_shift($actionCards);
      }

      if (Globals::isSolo()) {
        $draft[$pId]['selected'] = $draft[$pId]['draft'];
        $draft[$pId]['draft'] = [];
        $msg = clienttranslate('Drawing random unique action cards.');
      }
    }

    $draft['remaining'] = $actionCards;
    Globals::setActionCardsDraft($draft);
    Globals::incActionCardsDraftRound();
    Notifications::message($msg);
  }

  public function argsInitialActionCardsSelection()
  {
    $draft = Globals::getActionCardsDraft();
    $selection = Globals::getInitialActionCardsSelection();
    $args = ['_private' => []];
    foreach (Players::getAll() as $pId => $player) {
      $args['_private'][$pId] = [
        'previous' => ActionCards::getInstances($draft[$pId]['selected'] ?? [])->ui(),
        'cards' => ActionCards::getInstances($draft[$pId]['draft'])->ui(),
        'selection' => $selection[$pId] ?? null,
      ];
    }

    return $args;
  }

  public function actSelectActionCard($cardType)
  {
    // Sanity checks
    $this->gamestate->checkPossibleAction('actSelectActionCard');
    $player = Players::getCurrent();
    $draft = Globals::getActionCardsDraft();
    if (!in_array($cardType, $draft[$player->getId()]['draft'])) {
      throw new \BgaVisibleSystemException('You cannot select that action card. Should not happen');
    }

    $selection = Globals::getInitialActionCardsSelection();
    $selection[$player->getId()] = $cardType;
    Globals::setInitialActionCardsSelection($selection);
    Notifications::updateInitialActionCardSelection($player, $this->argsInitialActionCardsSelection());

    $this->updateActivePlayersInitialActionCardsSelection();
  }

  public function actCancelActionCardSelection()
  {
    $this->gamestate->checkPossibleAction('actCancelActionCardSelection');

    $player = Players::getCurrent();
    $selection = Globals::getInitialActionCardsSelection();
    unset($selection[$player->getId()]);
    Globals::setInitialActionCardsSelection($selection);
    Notifications::updateInitialActionCardSelection($player, $this->argsInitialActionCardsSelection());

    $this->updateActivePlayersInitialActionCardsSelection();
  }

  public function updateActivePlayersInitialActionCardsSelection()
  {
    // Compute players that still need to select their card map
    // => use that instead of BGA framework feature because in some rare case a player
    //    might become inactive eventhough the selection failed (seen in Agricola and Rauha at least already)
    $selection = Globals::getInitialActionCardsSelection();
    $players = Players::getAll();
    $ids = $players->getIds();
    $ids = array_diff($ids, array_keys($selection));

    // At least one player need to make a choice
    if (!empty($ids)) {
      $this->gamestate->setPlayersMultiactive($ids, 'done', true);
    }
    // Everyone is done => proceed
    else {
      // Save the choice
      $draft = Globals::getActionCardsDraft();
      foreach (Players::getAll() as $pId => $player) {
        $draft[$pId]['selected'][] = $selection[$pId];
        $method = 'setDrafted' . count($draft[$pId]['selected']);
        Stats::$method($pId, Stats::getCardUid($selection[$pId]));

        // Remove it from the remaining cards
        $key = array_search($selection[$pId], $draft[$pId]['draft']);
        if ($key === false) {
          throw new \BgaVisibleSystemException('Invalid action card draft. Should not happen');
        }
        unset($draft[$pId]['draft'][$key]);
        $draft[$pId]['draft'] = array_values($draft[$pId]['draft']);
      }

      // Move the cards to next player
      $newDraft = $draft;
      foreach (Players::getAll() as $pId => $player) {
        $nextPId = Players::getNextId($pId);
        $cardsLeft = $draft[$pId]['draft'];
        if (count($cardsLeft) == 1) {
          $newDraft[$nextPId]['selected'][] = $cardsLeft[0];
          $newDraft[$nextPId]['draft'] = [];
          Stats::setDrafted3($nextPId, Stats::getCardUid($cardsLeft[0]));
        } else {
          $newDraft[$nextPId]['draft'] = $draft[$pId]['draft'];
        }
      }
      Globals::setActionCardsDraft($newDraft);
      Globals::setInitialActionCardsSelection([]);

      $round = Globals::incActionCardsDraftRound();
      if ($round < 3) {
        Notifications::message(clienttranslate('Starting the second round of action cards draft phase.'));
        $this->gamestate->setAllPlayersMultiactive();
        $this->gamestate->nextState('done');
      } else {
        $this->stFinishActionCardDraft();
      }
    }
  }


  //  _  __               
  // | |/ /___  ___ _ __  
  // | ' // _ \/ _ \ '_ \ 
  // | . \  __/  __/ |_) |
  // |_|\_\___|\___| .__/ 
  //               |_|    

  public function stFinishActionCardDraft()
  {
    $draft = Globals::getActionCardsDraft();
    $remaining = new Collection($draft['remaining']);
    foreach (Players::getAll() as $pId => $player) {
      // Do we have only one card type ?
      $cardTypes = new Collection([]);
      foreach ($draft[$pId]['selected'] as $cardType) {
        $type = substr($cardType, 0, -1);
        if (!$cardTypes->includes($type)) {
          $cardTypes[] = $type;
        }
      }

      // If so, take one more
      if ($cardTypes->count() == 1) {
        $remainingOtherTypes = $remaining->filter(fn($cardType) => !$cardTypes->includes(substr($cardType, 0, -1)));
        if ($remainingOtherTypes->empty()) {
          throw new \BgaVisibleSystemException('No action card of different type remaning. Should not happen');
        }
        $cardType = $remainingOtherTypes->rand();
        $draft[$pId]['selected'][] = $cardType;
        $remaining = $remaining->filter(fn($e) => $e != $cardType);
      }
    }
    Globals::setActionCardsDraft($draft);

    Globals::setInitialActionCardsSelection([]);
    $this->gamestate->setAllPlayersMultiactive();
    $this->gamestate->jumpToState(ST_INITIAL_ACTION_CARD_KEEP);
  }

  public function argsInitialActionCardsKeep()
  {
    $args = ['_private' => []];

    $draft = Globals::getActionCardsDraft();
    $selection = Globals::getInitialActionCardsSelection();
    foreach (Players::getAll() as $pId => $player) {
      $args['_private'][$pId] = [
        'cards' => ActionCards::getInstances($draft[$pId]['selected'])->ui(),
        'selection' => $selection[$pId] ?? null,
      ];
    }

    return $args;
  }

  public function actKeepActionCards($cardIds)
  {
    // Sanity checks
    $this->checkAction('actKeepActionCards');
    $player = Players::getCurrent();
    $pId = $player->getId();
    $draft = Globals::getActionCardsDraft();
    if (!empty(array_diff($cardIds, $draft[$pId]['selected']))) {
      throw new \BgaVisibleSystemException('You cannot select that action card. Should not happen');
    }

    if (count($cardIds) != 2) {
      throw new \BgaVisibleSystemException('You must select 2 action cards. Should not happen');
    }

    $actionCardTypes = [];
    foreach ($cardIds as $cId) {
      if (in_array(substr($cId, 0, -1), $actionCardTypes)) {
        throw new \BgaVisibleSystemException("You must select 2 different type of action cards. Should not happen");
      }
      $actionCardTypes[] = substr($cId, 0, -1);
    }

    $selection = Globals::getInitialActionCardsSelection();
    $selection[$player->getId()] = $cardIds;
    Globals::setInitialActionCardsSelection($selection);
    Notifications::updateInitialActionCardsKeep($player, $this->argsInitialActionCardsKeep());

    $this->updateActivePlayersInitialActionCardsKeep();
  }

  public function actCancelActionCardsKeep()
  {
    $this->gamestate->checkPossibleAction('actCancelActionCardsKeep');

    $player = Players::getCurrent();
    $selection = Globals::getInitialActionCardsSelection();
    unset($selection[$player->getId()]);
    Globals::setInitialActionCardsSelection($selection);
    Notifications::updateInitialActionCardsKeep($player, $this->argsInitialActionCardsKeep());

    $this->updateActivePlayersInitialActionCardsKeep();
  }

  public function updateActivePlayersInitialActionCardsKeep()
  {
    // Compute players that still need to select their card map
    // => use that instead of BGA framework feature because in some rare case a player
    //    might become inactive eventhough the selection failed (seen in Agricola and Rauha at least already)
    $selection = Globals::getInitialActionCardsSelection();
    $players = Players::getAll();
    $ids = $players->getIds();
    $ids = array_diff($ids, array_keys($selection));

    // At least one player need to make a choice
    if (!empty($ids)) {
      $this->gamestate->setPlayersMultiactive($ids, 'done', true);
    }
    // Everyone is done => proceed
    else {
      $draft = Globals::getInitialActionCardsSelection();
      foreach (Players::getAll() as $pId => $player) {
        $selection = $draft[$pId];
        $cards = [];
        foreach ($selection as $card) {
          $cards[substr($card, 0, -1)] = $card;
        }
        $c = ActionCards::setupPlayer($pId, $cards);
        foreach ($c as $card) {
          $method = 'setActionCard' . $card->getActionType();
          Stats::$method($pId, $card->getNumber());
        }
        Notifications::setupActionCards($player, $c);
      }
      // Draw 8 zoo cards for each player
      ZooCards::initialDraw();
      $this->gamestate->setAllPlayersMultiactive();
      $this->gamestate->nextState('done');
    }
  }



  ///////////////////////////////
  //   ____              _
  //  / ___|__ _ _ __ __| |___
  // | |   / _` | '__/ _` / __|
  // | |__| (_| | | | (_| \__ \
  //  \____\__,_|_|  \__,_|___/
  ///////////////////////////////

  public function argsInitialSelection()
  {
    $selection = Globals::getInitialSelection();
    $args = ['_private' => []];
    foreach (Players::getAll() as $pId => $player) {
      $hand = $player->getHand();
      $args['_private'][$pId] = [
        'cards' => $hand->getIds(),
        'selection' => $selection[$pId] ?? null,
        'n' => $hand->count() - 4,
      ];
    }

    return $args;
  }

  public function actSelectCardsToDiscard($cardIds)
  {
    self::checkAction('actSelect');

    $player = Players::getCurrent();
    $selection = Globals::getInitialSelection();
    $selection[$player->getId()] = $cardIds;
    Globals::setInitialSelection($selection);
    Notifications::updateInitialSelection($player, $this->argsInitialSelection());

    $this->updateActivePlayersInitialSelection();
  }

  public function actCancelSelection()
  {
    $this->gamestate->checkPossibleAction('actCancelSelection');

    $player = Players::getCurrent();
    $selection = Globals::getInitialSelection();
    unset($selection[$player->getId()]);
    Globals::setInitialSelection($selection);
    Notifications::updateInitialSelection($player, $this->argsInitialSelection());

    $this->updateActivePlayersInitialSelection();
  }

  public function updateActivePlayersInitialSelection()
  {
    // Compute players that still need to select their card
    // => use that instead of BGA framework feature because in some rare case a player
    //    might become inactive eventhough the selection failed (seen in Agricola and Rauha at least already)
    $selection = Globals::getInitialSelection();
    $players = Players::getAll();
    $ids = $players->getIds();
    $ids = array_diff($ids, array_keys($selection));

    // At least one player need to make a choice
    if (!empty($ids)) {
      $this->gamestate->setPlayersMultiactive($ids, 'done', true);
    }
    // Everyone is done => discard cards and proceed
    else {
      $selection = Globals::getInitialSelection();
      foreach ($players as $pId => $player) {
        $cardIds = $selection[$pId];
        $cards = ZooCards::get($cardIds);
        ZooCards::discard($cardIds);
        Notifications::discardCards(
          $player,
          $cards,
          null,
          clienttranslate('${player_name} discards 4 cards (initial selection)')
        );
      }

      ZooCards::fillPool(true);
      Log::checkpoint();

      $this->gamestate->nextState('done');
    }
  }
}
