<?php

namespace AGR\States;

use AGR\Core\Engine;
use AGR\Core\Globals;
use AGR\Core\Notifications;
use AGR\Core\Stats;
use AGR\Managers\ActionCards;
use AGR\Managers\Actions;
use AGR\Managers\Campaign;
use AGR\Managers\Farmers;
use AGR\Managers\Meeples;
use AGR\Managers\PlayerCards;
use AGR\Managers\Players;
use AGR\Managers\Scores;
use AGR\Helpers\Log;

trait TurnTrait
{
  /**
   * Get unique turnId
   **/
  public static function setTurnId($hasNoTurnId = false)
  {
    if ($hasNoTurnId) {
      Globals::setTurnId('');
    } else {
      $turnId = Globals::getTurn();
      $player = Players::getActive();
      $placedFarmers = $player->countPlacedFarmers();

      $turnId = $turnId . '_' . $player->getId() . '_' . $placedFarmers;
      Globals::setTurnId($turnId);
    }
  }

  /**
   * State function when starting a turn
   *  useful to intercept for some cards that happens at that moment
   */
  function stBeforeStartOfTurn()
  {
    // Solo "draft from all cards": one-time choice of how action cards are revealed.
    // Neutral-state jump lets changeActivePlayer take effect before the activeplayer state.
    if (
      Globals::getTurn() == 0 &&
      Globals::isSolo() &&
      !Campaign::isActive() &&
      Globals::getDraftMode() == OPTION_DRAFT_FREE &&
      Globals::getSoloActionCardMode() == 0
    ) {
      $this->gamestate->jumpToState(ST_GENERIC_NEXT_PLAYER);
      $this->gamestate->changeActivePlayer(Players::getAll()->first()->getId());
      $this->gamestate->jumpToState(ST_SOLO_ACTION_CARD_MODE);
      return;
    }

    // 0) Make children grow up
    $children = Farmers::growChildren();
    if (!empty($children)) {
      Notifications::growChildren($children);
      Notifications::updateHarvestCosts();
    }

    $skipped = Players::getAll()
      ->filter(function ($player) {
        return $player->isZombie();
      })
      ->getIds();
    Globals::setSkippedPlayers($skipped);

    // 1) Listen for cards BeforeStartOfTurn
    $this->checkCardListeners('BeforeStartOfTurn', 'stPreparationRevealAction');
  }

  /**
   * Prepare the current turn
   */
  function stPreparationRevealAction()
  {
    $turn = Globals::incTurn();
    Globals::setGameFlowPhase('preparation');
    Notifications::startNewTurn($turn);

    if (Globals::getSoloActionCardMode() == SOLO_ACTION_CARDS_CHOOSE && count(ActionCards::getHiddenStageCards($turn)) > 1) {
      $this->gamestate->jumpToState(ST_CHOOSE_ACTION_CARD);
      return;
    }

    $this->revealNextActionCard($turn);
  }

  function revealNextActionCard($turn)
  {
    // Reveal new action card
    $card = ActionCards::draw()->first();
    Globals::setLastRevealed($card->getId());
    Notifications::revealActionCard($card);
    // Listen for cards AfterRevealAction

    $this->checkCardListeners('AfterRevealAction', 'stPreparationListener');

    if (Globals::isSnakeOpening() && $turn == 1 && !Globals::isSnakeOpeningFlipped()) {
      Notifications::message(
        clienttranslate('Snake opening: in Round 1, the second farmer is placed in reverse turn order.')
      );
    }
  }

  public function actSoloActionCardMode($mode)
  {
    $choose = $mode == 'choose';
    Globals::setSoloActionCardMode($choose ? SOLO_ACTION_CARDS_CHOOSE : SOLO_ACTION_CARDS_RANDOM);
    Notifications::message(
      $choose
        ? clienttranslate('${player_name} will choose the action card revealed each round')
        : clienttranslate('Action cards are revealed in random order'),
      $choose ? ['player' => Players::getActive()] : []
    );
    $this->gamestate->jumpToState(ST_BEFORE_START_OF_TURN);
  }

  public function argsChooseActionCard()
  {
    $cards = [];
    foreach (ActionCards::getHiddenStageCards(Globals::getTurn()) as $card) {
      $cards[] = ['id' => $card->getId(), 'name' => $card->getName()];
    }
    return ['cards' => $cards];
  }

  public function actChooseActionCard($cardId)
  {
    $turn = Globals::getTurn();
    $chosen = null;
    foreach (ActionCards::getHiddenStageCards($turn) as $card) {
      if ($card->getId() == $cardId) {
        $chosen = $card;
      }
    }
    if ($chosen == null) {
      throw new \BgaVisibleSystemException('This action card cannot be chosen');
    }

    // Swap the chosen card into the current turn slot
    if ($chosen->getTurn() != $turn) {
      $current = ActionCards::getInLocation(['turn', $turn], HIDDEN)->first();
      ActionCards::move($current->getId(), $chosen->getLocation());
      ActionCards::move($cardId, 'turn_' . $turn);
    }

    $this->revealNextActionCard($turn);
  }

  function stPreparationListener()
  {
    $this->checkCardListeners('Preparation', 'stPreparation');
  }

  /**
   * Prepare the current turn
   */
  function stPreparation()
  {
    // Solo campaign: play permanent occupations at the start of round  1.
    // Playing them before this causes bugs with cards that care about what round it is.
    // The player needs to be able to play them in the order they choose, as some cards might give resources etc used by other cards.
    if (Campaign::isActive() && Globals::isCampaignPermanentsPending()) {
      Globals::setCampaignPermanentsPending(false);
      $this->gamestate->jumpToState(ST_CAMPAIGN_PLAY_PERMANENTS);
      return;
    }

    // Fill up accumulation spots
    $resourceIds = ActionCards::accumulate();
    Notifications::accumulate(Meeples::getMany($resourceIds));
    // Fired here (not in stStartLaborDay) so it shares a packet with the
    // round-reveal notifications — avoids an extra 1s archive-replay pause.
    Notifications::startWork();

    // Change first player and check start of turn trigger
    $firstPlayer = Globals::getFirstPlayer();
    Stats::incFirstPlayer($firstPlayer);
    $this->initCustomDefaultTurnOrder('startOfTurn', 'stStartOfTurn', 'stPreStartofWorkPhase');
  }

  /*
   *  c) Collect players' resources on action cards
   */
  function stStartOfTurn()
  {
    $pId = Players::getActiveId();
    $player = Players::getActive();
    $turn = Globals::getTurn();

    // Get triggered cards
    $event = [
      'type' => 'StartOfTurn',
      'method' => 'StartOfTurn',
      'pId' => $pId,
    ];
    $reaction = PlayerCards::getReaction($event, false);

    // Get meeple to receive
    $resources = Meeples::getResourcesOnCard('turn_' . $turn, $pId);

    // Collect plain ids to batch
    $plainIds = [];

    // Store up nodes for post receive (Tree Farm Joiner, Confidant etc). Need to happen after
    // all receives to avoid bugs with eg Shaving Horse proccing on separate wood receives
    $bonusNodes = [];

    foreach ($resources as $id => $res) {
      // x carries the source card id (for stat attribution in Receive).
      $sourceCard = is_null($res['x']) ? null : PlayerCards::get($res['x']);

      // Route individually only when necessary (pay-gated like Plowman, Small Greenhouse
      // or misc like Bookmark)
      if (!is_null($sourceCard) && method_exists($sourceCard, 'getReceiveFlow')) {
        Meeples::fixMeepleType($res);
        $reaction['childs'][] = $sourceCard->getReceiveFlow($res);
      } else {
        if (!is_null($sourceCard) && method_exists($sourceCard, 'getPostReceiveBonus')) {
          Meeples::fixMeepleType($res);
          $bonusNodes[] = $sourceCard->getPostReceiveBonus($res);
        }
        $plainIds[] = $id;
      }
    }

    // enqueue ONE batched RECEIVE for all plain round pickups
    if (!empty($plainIds)) {
      $reaction['childs'][] = [
        'action' => RECEIVE,
        'args' => [
          'meeples' => $plainIds,         // batch
          'meeple' => $plainIds[0], // <-- legacy key for getDescription()/getMeeple()
          'type' => 'round',
        ],
      ];
    }

    // post-receive bonuses resolve after all round-start goods have been collected
    foreach ($bonusNodes as $bonusNode) {
      $reaction['childs'][] = $bonusNode;
    }

    // D22 Work Permit: farmer placed on round space at buy time
    $farmers = Farmers::getOnCard('turn_' . $turn, $pId);
    foreach ($farmers as $farmer) {
      if ($farmer['state'] == SUPPLY) {
        Meeples::moveToCoords($farmer['id'], 'reserve');
        Notifications::returnToSupplyOne($player, Farmers::getMany($farmer['id']));
        Notifications::message(clienttranslate('${player_name} can place an extra person from their supply this round'), [
          'player_name' => $player->getName(),
        ]);

        $reaction['childs'][] = [
          'action' => SPECIAL_EFFECT,
          'pId' => $pId,
          'args' => [
            'cardId' => 'D22_WorkPermit',
            'method' => 'activate',
          ]
        ];
      }
    }

    // E96_Elder
    if (Globals::getTurn() == 1 && !$player->hasPlayedCard('E101_Blighter')) {
      $hand = $player->getHand(OCCUPATION);

      foreach ($hand as $card) {
        if ($card->getId() == 'E96_Elder') {
          $reaction['childs'][] = [
            'action' => OCCUPATION,
            'optional' => true,
            'pId' => $player->getId(),
            'args' => [
              'cost' => [],
              'allowedCards' => ['E96_Elder']
            ],
          ];
          break;
        }
      }
    }

    if (empty($reaction['childs'])) {
      // No reaction => just go to next player
      $this->nextPlayerCustomOrder('startOfTurn');
    } else {
      // Reaction => boot up the Engine
      Engine::setup($reaction, ['method' => 'stClearStartOfTurn']);
      Engine::proceed();
    }
  }

  /**
   * Clear potential meeples that were left on the card by the player
   */
  function stClearStartOfTurn()
  {
    $pId = Players::getActiveId();
    $turn = Globals::getTurn();

    // Delete any remeaning meeples
    $resources = Meeples::getResourcesOnCard('turn_' . $turn, $pId);
    if ($resources->count() > 0) {
      foreach ($resources as $id => $res) {
        Meeples::DB()->delete($id);
      }
      Notifications::silentKill($resources->toArray());
    }

    $this->nextPlayerCustomOrder('startOfTurn');
  }

  function stPreStartofWorkPhase()
  {
    $this->checkCardListeners('EndOfPreparationPhase', 'stStartofWorkPhase');
    //$this->initCustomDefaultTurnOrder('startOfWork', 'stStartofWorkPhase', 'stStartLaborDay');
  }

  function stStartofWorkPhase()
  {
    // Bassinet: reset each work phase
    Globals::setBassinetFirstSpace('');

    Globals::setSkipNext([]);
    $this->checkCardListeners('startOfWork', 'stStartLaborDay');
  }

  function stStartLaborDay()
  {
    Globals::setObtainedResourcesDuringWork([]);
    Globals::setFarmyardGoodsPlacedDuringWork([]);
    Globals::setWorkPhase(true);
    Globals::setGameFlowPhase('work');

    // Change first player and start labor
    $this->initCustomDefaultTurnOrder('labor', ST_LABOR, ST_END_WORK_PHASE, true);
  }

  /**
   * Activate next player with a farmer available
   */
  function stLabor()
  {
    $player = Players::getActive();

    if (Globals::getDraftMode() == OPTION_DRAFT_LIVING_HAND && $this->maybeEnterLivingHandPassingDecision($player)) {
      return;
    }

    if (Globals::getDraftMode() == OPTION_DRAFT_LIVING_HAND && $this->maybeStartLivingHandRefillBeforeTurn($player)) {
      return;
    }

    $skipNext = Globals::getSkipNext() ?? [];

    if (in_array($player->getId(), $skipNext)) {
      $skipNext = array_diff($skipNext, [$player->getId()]);
      Globals::setSkipNext($skipNext);

      Notifications::message(clienttranslate('${player_name} is skipped due to a card effect'), [
        'player_name' => $player->getName(),
      ]);
      $this->nextPlayerCustomOrder('labor');
      return;
    }

    // Already out of round ? => Go to the next player if one is left
    $skipped = Globals::getSkippedPlayers();
    if (in_array($player->getId(), $skipped)) {
      // Everyone is out of round => end it
      $remaining = array_diff(Players::getAll()->getIds(), $skipped);
      if (empty($remaining)) {
        $this->endCustomOrder('labor');
      } else {
        $this->nextPlayerCustomOrder('labor');
      }
      return;
    }

    // No farmer to allocate ?
    if (!$player->hasFarmerAvailable() && !$player->hasAdoptiveAvailable() && !$player->hasGuestRoomAvailable() && !$player->hasWorkPermitAvailable() && !$player->hasTelegramAvailable()) {
      // E125 Delayed Wayfarer: If this player can still use the card for an extra placement, do not mark them as out of the round yet.
      if ($player->hasPlayedCard('E125_DelayedWayfarer')) {
        $wayfarer = PlayerCards::get('E125_DelayedWayfarer');
        if (!$wayfarer->canBeActivated()) {
          // Card cannot be used *now*. Check if it is still live for this round
          // and there is a farmer in reserve. In that case, keep the player in
          // the round but move on to the next player.
          $playedTurn = $wayfarer->getExtraDatas('turn');
          if ($playedTurn === Globals::getTurn() && $player->hasFarmerInReserve()) {
            $this->nextPlayerCustomOrder('labor');
            return;
          }

          // Otherwise, treat this player as out of the round.
          $skipped[] = $player->getId();
          Globals::setSkippedPlayers($skipped);
          $this->nextPlayerCustomOrder('labor');
          return;
        }
      } else {
        $skipped[] = $player->getId();
        Globals::setSkippedPlayers($skipped);
        $this->nextPlayerCustomOrder('labor');
        return;
      }
    }

    self::giveExtraTime($player->getId());

    $args = [];
    PlayerCards::applyEffects($player, 'resetFlags', $args);

    Engine::checkpoint();
    $options = [];

    // Normal placement option only if the player actually has a farmer available
    if ($player->hasFarmerAvailable()) {
      $options[] = [
        'action' => PLACE_FARMER,
        'pId' => $player->getId(),
      ];
    }

    // A22 Telegram
    if ($player->hasTelegramAvailable()) {
      $options[] = [
        'type' => NODE_SEQ,
        'childs' => [
          PlayerCards::get('A22_Telegram')->unflagCardNode(),
          [
            'action' => PLACE_FARMER,
            'pId' => $player->getId(),
            'args' => [
              'fromSupply' => true,
              'source' => 'A22_Telegram',
            ],
          ],
        ]
      ];
    }

    // D53 Tea House
    if ($player->hasPlayedCard('D53_TeaHouse') && PlayerCards::get('D53_TeaHouse')->canBeActivated()) {
      $options[] = [
        'type' => NODE_SEQ,
        'childs' => [
          PlayerCards::get('D53_TeaHouse')->flagCardNode(),
          PlayerCards::get('D53_TeaHouse')->gainNode([FOOD => 1], null, true)
        ]
      ];
    }

    // D22 Work Permit
    if ($player->hasWorkPermitAvailable()) {
      $options[] = [
        'type' => NODE_SEQ,
        'childs' => [
          PlayerCards::get('D22_WorkPermit')->unflagCardNode(),
          [
            'action' => PLACE_FARMER,
            'pId' => $player->getId(),
            'args' => [
              'fromSupply' => true,
              'source' => 'D22_WorkPermit',
            ],
          ],
        ]
      ];
    }

    // E22 Guest Room
    if ($player->hasPlayedCard('E22_GuestRoom') && PlayerCards::get('E22_GuestRoom')->canBeActivated()) {
      $options[] = PlayerCards::get('E22_GuestRoom')->activateFlow();
    }

    // E62 Sour Dough
    if ($player->hasPlayedCard('E62_SourDough') && PlayerCards::get('E62_SourDough')->canBeActivated()) {
      $options[] = [
        'action' => EXCHANGE,
        'pId' => $player->getId(),
        'args' => [
          'trigger' => BREAD,
          'specialSource' => 'E62_SourDough',
        ],
      ];
    }

    // E93 Motivator
    if ($player->hasPlayedCard('E93_Motivator') && PlayerCards::get('E93_Motivator')->canBeActivated()) {
      $options[] = [
        'action' => PLACE_FARMER,
        'pId' => $player->getId(),
        'args' => [
          'fromSupply' => true,
          'source' => 'E93_Motivator',
        ],
      ];
    }

    // E125 Delayed Wayfarer
    if ($player->hasPlayedCard('E125_DelayedWayfarer')) {
      $wayfarer = PlayerCards::get('E125_DelayedWayfarer');
      if ($wayfarer->canBeActivated()) {
        $options[] = $wayfarer->getExtraPlacementNode();
      }
    }

    $node = [
      'type' => NODE_XOR,
      // When the player has no regular farmers, any remaining options (e.g. Work Permit) are optional —
      // they can pass to forfeit them (e.g. to keep the supply person for family growth).
      'optional' => !$player->hasFarmerAvailable(),
      'childs' => $options
    ];

    $adoptiveAvailable = $player->hasAdoptiveAvailable();
    $guestRoomAvailable = $player->hasGuestRoomAvailable();

    if (!$player->hasFarmerAvailable() && ($adoptiveAvailable || $guestRoomAvailable)) {
      $options = [];
      if ($adoptiveAvailable) {
        $card = PlayerCards::get('A92_AdoptiveParents');
        $options[] = $card->getStartOfRoundChoice($player);
      }
      if ($guestRoomAvailable) {
        $card = PlayerCards::get('E22_GuestRoom');
        $options[] = $card->getStartOfRoundChoice($player);
      }
      $node = [
        'type' => NODE_XOR,
        'childs' => $options
      ];
    }

    // Set turnId
    $this->setTurnId();

    // Inserting leaf PLACE_FARMER
    if (Globals::getDraftMode() == OPTION_DRAFT_LIVING_HAND) {
      Engine::setup($node, ['method' => 'livingHandAfterTurn']);
    } elseif (!$player->hasFarmerAvailable() && ($player->hasWorkPermitAvailable() || $player->hasTelegramAvailable())) {
      Engine::setup($node, ['method' => 'stAfterSupplyFarmerChoice']);
    } else {
      Engine::setup($node, ['order' => 'labor']);
    }
    Engine::proceed();
  }

  // Called after the player is offered a Work Permit / Telegram placement with no regular farmers left.
  // If they passed (card still flagged), unflag and end their round.
  public function stAfterSupplyFarmerChoice()
  {
    $player = Players::getActive();
    $passed = false;

    if ($player->hasWorkPermitAvailable()) {
      PlayerCards::get('D22_WorkPermit')->unflagCard();
      $passed = true;
    }

    if ($player->hasTelegramAvailable()) {
      PlayerCards::get('A22_Telegram')->unflagCard();
      $passed = true;
    }

    if ($passed) {
      $skipped = Globals::getSkippedPlayers();
      $skipped[] = $player->getId();
      Globals::setSkippedPlayers($skipped);
    }

    $this->nextPlayerCustomOrder('labor');
  }

  public function livingHandAfterTurn()
  {
    $this->stLivingHandAfterTurn();
  }
  
  /********************************
   ********************************
   ********** FLOW CHOICE *********
   ********************************
   ********************************/
  function argsResolveChoice()
  {
    $player = Players::getActive();
    $args = [
      'choices' => Engine::getNextChoice($player),
      'allChoices' => Engine::getNextChoice($player, true),
      'previousEngineChoices' => Globals::getEngineChoices(),
    ];
    $this->addArgsAnytimeAction($args, 'resolveChoice');
    return $args;
  }

  function actChooseAction($choiceId)
  {
    $player = Players::getActive();
    Engine::chooseNode($player, $choiceId);
  }

  public function stResolveStack()
  {
  }

  public function stResolveChoice()
  {
    if ($this->takeIndependentMandatoryAnytimeActionIfAny()) {
      return;
    }
  }

  // Check if a mandatory bake with no grain should be allowed because player already did an optional bake 
  // earlier in the turn: deals with Small Potter's Oven confusing edge case. See bug 223552.
  public function stImpossibleAction()
  {
    $node = Engine::getNextUnresolved();
    if ($node === null || $node->getAction() === null) {
      return;
    }

    $action = Actions::get($node->getAction(), $node);
    if (method_exists($action, 'isAlreadySatisfied') && $action->isAlreadySatisfied()) {
      Engine::resolve(PASS);
      Engine::proceed();
    }
  }

  function argsImpossibleAction()
  {
    $player = Players::getActive();
    $node = Engine::getNextUnresolved();

    $args = [
      'desc' => $node->getDescription(),
      'previousEngineChoices' => Globals::getEngineChoices(),
      // If a player gets to an impossible action without the ability to restart their turn, the game can get stuck
      // For example: player has Teacher's Desk, plays Dairy Crier on Improvements (creating checkpoints), but can't afford an improvement.
      // Escape hatch is intentionally narrow: it allows an illegal move in order to save the game from a dead end.
      // The alternative - allow restarts through partial turn confirms - seems even worse
      'canAbandon' => $this->isAbandonableNode($node) && !Log::canRestartTurn(),
    ];
    $this->addArgsAnytimeAction($args, 'impossibleAction');
    return $args;
  }

  private function isAbandonableNode($node)
  {
    return $node !== null
      && $node->getAction() === IMPROVEMENT
      && $node->getCardId() === 'ActionMajorImprovement';
  }

  public function actAbandonStuckAction()
  {
    self::checkAction('actAbandonStuckAction');
    $node = Engine::getNextUnresolved();
    if (!$this->isAbandonableNode($node)) {
      throw new \BgaVisibleSystemException('Cannot abandon: this action is not abandonable');
    }
    if (Log::canRestartTurn()) {
      throw new \BgaVisibleSystemException('Cannot abandon: restart your turn instead');
    }

    $player = Players::getActive();
    Notifications::message(
      clienttranslate('${player_name} cannot complete the mandatory action and abandons it'),
      ['player_name' => $player->getName()]
    );

    Engine::resolveAction([]);
    Engine::proceed();
  }

  /*******************************
   ******* CONFIRM / RESTART ******
   ********************************/
  public function argsConfirmTurn()
  {
    $data = [
      'previousEngineChoices' => Globals::getEngineChoices(),
      'automaticAction' => false,
    ];
    $this->addArgsAnytimeAction($data, 'confirmTurn');
    return $data;
  }

  public function argsConfirmPartialTurn()
  {
    $data = $this->argsConfirmTurn();

    $node = Engine::getNextUnresolved();
    $opponentPId = is_null($node) ? null : $node->getPId();
    if ($opponentPId !== null) {
      $opponent = Players::get($opponentPId);
      $data['player_name'] = $opponent->getName();
      $data['player_id'] = $opponentPId;

      $cardName = self::firstTriggeringCardName($node);
      if (!is_null($cardName)) {
        $data['card_name'] = $cardName;
        $data['i18n'] = array_merge($data['i18n'] ?? [], ['card_name']);
      } else {
        $data['descSuffix'] = 'nocard';
        $data['card_name'] = '';
      }
    }

    return $data;
  }

  private static function firstTriggeringCardName($node)
  {
    // Walk down from $node (covers pre-stActivateCard ACTIVATE_CARD leaves with args.cardId,
    // and post-stActivateCard subtrees tagged with sourceId)
    $stack = [$node];
    while (!empty($stack)) {
      $current = array_shift($stack);
      $cardId = $current->getSourceId() ?? ($current->getArgs()['cardId'] ?? null);
      if (!is_null($cardId)) {
        $card = PlayerCards::get($cardId);
        if (!is_null($card)) {
          return $card->getName();
        }
      }
      foreach ($current->getChilds() as $child) {
        if (!$child->isResolved()) {
          $stack[] = $child;
        }
      }
    }

    // Walk up: dynamically-inserted descendants of an activated card flow don't inherit
    // sourceId (only the original tagTree pass does), but an ancestor will have it
    $current = $node->getParent();
    while ($current !== null) {
      $cardId = $current->getSourceId();
      if (!is_null($cardId)) {
        $card = PlayerCards::get($cardId);
        if (!is_null($card)) {
          return $card->getName();
        }
      }
      $current = $current->getParent();
    }
    return null;
  }

  public function stConfirmTurn()
  {
    $player = Players::getActive();

    if ($this->takeIndependentMandatoryAnytimeActionIfAny()) {
      return;
    }

    // Check user preference to bypass if DISABLED is picked
    $pref = Players::getActive()->getPref(OPTION_CONFIRM);

    $exception = 
      $player->hasPlayedCard('E85_MasterTanner') && 
      $player->hasPlayedCard('D82_HuntingTrophy') && 
      ActionCards::get('D82_HuntingTrophy')->getExtraDatas('turnUsed') == Globals::getTurn();
    if ($pref == OPTION_CONFIRM_DISABLED && Engine::getNextUnresolved() == [] && !$exception) {
      $this->actConfirmTurn(true);
    }
  }

  public function actConfirmTurn($auto = false)
  {
    if ($auto) {
      $this->gamestate->checkPossibleAction('actConfirmTurn');
    } else {
      self::checkAction('actConfirmTurn');
    }
    Engine::confirm();
  }

  public function actConfirmPartialTurn()
  {
    self::checkAction('actConfirmPartialTurn');
    Engine::confirmPartialTurn();
  }

  public function actRestart()
  {
    self::checkAction('actRestart');
    if (Globals::getEngineChoices() < 1) {
      throw new \BgaVisibleSystemException('No choice to undo');
    }
    Engine::restart();
  }

  public function actUndoToStep($stepId)
  {
    self::checkAction('actRestart');
    Engine::undoToStep($stepId);
  }

  /********************************
   ********************************
   ********** END OF TURN *********
   ********************************
   ********************************/
  function stEndWorkPhase()
  {
    // Listen for cards onEndWorkPhase
    $this->checkCardListeners('EndWorkPhase', 'stAfterWorkPhase');
  }

  function stAfterWorkPhase()
  {
    // Listen for cards onEndWorkPhase
    $this->checkCardListeners('AfterWorkPhase', 'stBeforeReturnHome');
  }

  function stBeforeReturnHome()
  {
    Globals::setGameFlowPhase('returning-home');
    Notifications::startReturnHome();
    // Listen for cards onBeforeReturnHome
    $this->checkCardListeners('BeforeReturnHome', 'stStartReturnHome');
  }

  function stStartReturnHome()
  {
    // ReturnHome phase does not count as someones turn
    $this->setTurnId(true);

    // Listen for cards onStartReturnHome
    $this->checkCardListeners('StartReturnHome', 'stReturnHome');
  }

  function stReturnHome()
  {
    Players::returnHome();

    Notifications::returnHome(Farmers::getAllAvailable());

    $fromSupply = Farmers::getTemporary();
    if (!$fromSupply->empty()) {
      $farmers = Notifications::returnHomeToReserve($fromSupply);

      foreach ($fromSupply as $farmer) {
        Farmers::setFarmerState($farmer, SUPPLY);
      }
    }

    Globals::setWorkPhase(false);

    // Listen for cards onReturnHome
    $this->checkCardListeners('ReturnHome', ST_PRE_END_OF_TURN2);
  }

  function stPreEndOfTurn()
  {
    // Next turn or harvest
    $turn = Globals::getTurn();
    $harvest = [4, 7, 9, 11, 13, 14];
    if (in_array($turn, $harvest)) {
      $this->checkCardListeners('BeforeHarvest', ST_START_HARVEST);
    } else {
      $this->gamestate->nextState('end');
    }
  }

  function stEndOfTurn()
  {
    if (Globals::isHarvest()) {
      Globals::setSkipHarvest([]);
      Globals::setSkipFieldAndBreed([]);
      // Bring home children born onto cards during the AfterHarvest phase (eg Eternal Rye -> Hayloft Barn)
      if (!Farmers::getChildren(null)->empty()) {
        Players::returnHome();
        Notifications::returnHomeSilent(Farmers::getAllAvailable());
      }
    }

    Globals::setHarvest(false);
    $this->checkCardListeners('EndOfRound', 'stAfterEndOfTurn');
  }

  function stAfterEndOfTurn()
  {
    if (Globals::getTurn() == 14) {
      $this->checkCardListeners('AfterEndOfRound', ST_PRE_END_OF_GAME);
      return;
    }

    $this->checkCardListeners('AfterEndOfRound', ST_BEFORE_START_OF_TURN);
  }

  function stPreEndOfGame()
  {
    Globals::setReservedResourcesForScoring([]);
    Globals::setGameFlowPhase('endgame');
    $this->checkCardListeners('BeforeEndOfGame', 'stLaunchEndOfGame');
  }

  function stLaunchEndOfGame()
  {
    foreach (PlayerCards::getAllCardsWithMethod('EndOfGame') as $card) {
      $card->onEndOfGame();
    }
    Globals::setTurn(15);
    Globals::setLiveScoring(true);
    Scores::update(true);

    if (Campaign::isActive()) {
      // campaign endgame follows its own flow
      Campaign::recordEndOfGame();
      $pId = Players::getAll()->first()->getId();
      $this->gamestate->changeActivePlayer($pId);
      $this->gamestate->jumpToState(ST_CAMPAIGN_CHOOSE_PERMANENT);
      return;
    }

    Notifications::seed(Globals::getGameSeed());
    $this->gamestate->jumpToState(\ST_END_GAME);
  }
}
