<?php
namespace AGR\States;
use AGR\Core\Globals;
use AGR\Managers\ActionCards;
use AGR\Managers\PlayerCards;
use AGR\Managers\Players;
use AGR\Core\Notifications;
use AGR\Core\Stats;

trait DraftTrait
{
  function actOrderCards($cardIds)
  {
    $player = Players::getCurrent();
    foreach ($cardIds as $i => $cardId) {
      $card = PlayerCards::getSingle($cardId);
      if (is_null($card) || $card->isPlayed() || $card->getPId() != $player->getId()) {
        throw new \BgaVisibleSystemException("You can't reorder that card:" . $card->getId());
      }

      PlayerCards::setState($cardId, $i);
    }
  }

  /**
   * Starting number of cards depending on each round
   */
  function getDraftStartingNumberOfCards()
  {
    $map = [
      OPTION_PICK_7_OUT_OF_10 => 10,
      OPTION_DRAFT_7_SIMULTANEOUS => 7,
      OPTION_DRAFT_8_SIMULTANEOUS => 8,
      OPTION_DRAFT_9_SIMULTANEOUS => 9,
      OPTION_DRAFT_10_SIMULTANEOUS => 10,
      OPTION_DRAFT_7_OCCUPATIONS => 7,
      OPTION_DRAFT_8_OCCUPATIONS => 8,
      OPTION_DRAFT_9_OCCUPATIONS => 9,
      OPTION_DRAFT_10_OCCUPATIONS => 10,
      OPTION_DRAFT_7_MINORS => 7,
      OPTION_DRAFT_8_MINORS => 8,
      OPTION_DRAFT_9_MINORS => 9,
      OPTION_DRAFT_10_MINORS => 10,
      OPTION_DRAFT_FREE => -1,
      OPTION_SEED_MODE => 0,
      OPTION_DRAFT_LIVING_HAND => 7,
    ];

    return $map[Globals::getDraftMode()] ?? 0;
  }

  /**
   * Protocol of draft
   */
  function getDraftProtocol()
  {
    $map = [
      OPTION_PICK_7_OUT_OF_10 => ONE_SHOT,
      OPTION_DRAFT_FREE => ONE_SHOT,
      OPTION_DRAFT_7_SIMULTANEOUS => SIMULTANEOUS,
      OPTION_DRAFT_8_SIMULTANEOUS => SIMULTANEOUS,
      OPTION_DRAFT_9_SIMULTANEOUS => SIMULTANEOUS,
      OPTION_DRAFT_10_SIMULTANEOUS => SIMULTANEOUS,
      OPTION_DRAFT_7_OCCUPATIONS => OCCUPATION_FIRST,
      OPTION_DRAFT_8_OCCUPATIONS => OCCUPATION_FIRST,
      OPTION_DRAFT_9_OCCUPATIONS => OCCUPATION_FIRST,
      OPTION_DRAFT_10_OCCUPATIONS => OCCUPATION_FIRST,
      OPTION_DRAFT_7_MINORS => MINOR_FIRST,
      OPTION_DRAFT_8_MINORS => MINOR_FIRST,
      OPTION_DRAFT_9_MINORS => MINOR_FIRST,
      OPTION_DRAFT_10_MINORS => MINOR_FIRST,
      OPTION_DRAFT_LIVING_HAND => SIMULTANEOUS,
    ];

    return $map[Globals::getDraftMode()] ?? null;
  }

  /**
   * Get total number of rounds
   */
  function getDraftTotalNumberOfTurns()
  {
    if (Globals::getDraftMode() == OPTION_DRAFT_LIVING_HAND) {
      return 4;
    }

    $protocol = $this->getDraftProtocol();
    $map = [
      ONE_SHOT => 1,
      SIMULTANEOUS => 7,
    ];
    return $map[$protocol] ?? 14;
  }

  /**
   * Give draft type for a given round (defaults to global draftTurn for legacy compatibility)
   */
  function getDraftType($turn = null)
  {
    $protocol = $this->getDraftProtocol();
    if ($turn === null) {
      $turn = Globals::getDraftTurn();
    }

    $res = [
      OCCUPATION => 0,
      MINOR => 0,
    ];
    if ($protocol == ONE_SHOT) {
      $res[OCCUPATION] = 7;
      $res[MINOR] = 7;
    } elseif ($protocol == SIMULTANEOUS) {
      $res[OCCUPATION] = 1;
      $res[MINOR] = 1;
    } elseif (($protocol == OCCUPATION_FIRST && $turn <= 7) || ($protocol == MINOR_FIRST && $turn > 7)) {
      $res[OCCUPATION] = 1;
    } elseif (($protocol == MINOR_FIRST && $turn <= 7) || ($protocol == OCCUPATION_FIRST && $turn > 7)) {
      $res[MINOR] = 1;
    }
    return $res;
  }
  
  //Returns true if this game is using the new async draft system.
  private function isAsyncDraft()
  {
    // New per-slot globals: check if any slot has been initialised (> 0)
    if (Globals::getDraftPlayer1Turn() > 0) return true;
    // Legacy fallback: old games used a single JSON blob
    return !empty(Globals::getDraftPlayerTurns());
  }

  /***********************************************
   * Async draft helpers
   ***********************************************/

  // Map player ID → per-slot global name (draftPlayer1Turn .. draftPlayer4Turn)
  private function draftTurnGlobalName($pId)
  {
    $no = Players::get($pId)->getNo();
    return 'DraftPlayer' . $no . 'Turn';
  }

  private function getPlayerDraftTurn($pId)
  {
    // New per-slot globals (each player's turn is its own DB row — no concurrent overwrites)
    $getter = 'get' . $this->draftTurnGlobalName($pId);
    $val = Globals::$getter();
    if ($val > 0) return $val;
    // Legacy fallback
    $turns = Globals::getDraftPlayerTurns();
    return $turns[$pId] ?? 1;
  }

  private function incPlayerDraftTurn($pId)
  {
    $setter = 'set' . $this->draftTurnGlobalName($pId);
    $newVal = $this->getPlayerDraftTurn($pId) + 1;
    Globals::$setter($newVal);
    return $newVal;
  }

  // Build {pId => turn} map from per-slot globals (used for notifications and args)
  private function getAllPlayerDraftTurns()
  {
    $turns = [];
    foreach (Players::getAll() as $pId => $player) {
      $turns[$pId] = $this->getPlayerDraftTurn($pId);
    }
    return $turns;
  }

  private function getDraftPredecessor($pId)
  {
    foreach (Players::getAll() as $candidatePId => $candidate) {
      if (Players::getNextId($candidatePId) == $pId) {
        return $candidatePId;
      }
    }
    return null;
  }

  // Returns the last turn of the first phase for two-phase draft modes (occ+minor),
  // or null for single-phase modes.
  private function getDraftPhaseEndTurn()
  {
    $protocol = $this->getDraftProtocol();
    return ($protocol == OCCUPATION_FIRST || $protocol == MINOR_FIRST) ? 7 : null;
  }

  private function isLastTurnOfCurrentPhase($turn)
  {
    $endTurn = $this->getDraftPhaseEndTurn();
    return $endTurn !== null && $turn == $endTurn;
  }

  private function isPhaseStart($turn)
  {
    $endTurn = $this->getDraftPhaseEndTurn();
    return $endTurn !== null && $turn == $endTurn + 1;
  }

  private function buildDraftChoiceDescription($type)
  {
    $choice = [
      'log' => '',
      'args' => [
        'minor' => $type[MINOR],
        'occupation' => $type[OCCUPATION],
      ],
    ];
    if ($type[OCCUPATION] > 0 && $type[MINOR] > 0) {
      $n = $type[MINOR];
      $choice['args']['n'] = $n;
      $choice['log'] = $n == 1
        ? clienttranslate('1 minor improvement and 1 occupation')
        : clienttranslate('${n} minor improvements and ${n} occupations');
    } elseif ($type[OCCUPATION] > 0) {
      $choice['log'] = $type[OCCUPATION] == 1
        ? clienttranslate('1 occupation')
        : clienttranslate('${occupation} occupations');
    } elseif ($type[MINOR] > 0) {
      $choice['log'] = $type[MINOR] == 1
        ? clienttranslate('1 minor improvement')
        : clienttranslate('${minor} minor improvements');
    }
    return $choice;
  }

  /**
   * Send a newDraftPile notification and clear the player's last-pool snapshot,
   * since they now have a fresh pack to look at.
   */
  private function sendNewDraftPile($pId, $cards, $turn, $type, $choice)
  {
    $lastPool = Globals::getDraftLastPool() ?? [];
    if (isset($lastPool[$pId])) {
      unset($lastPool[$pId]);
      Globals::setDraftLastPool($lastPool);
    }
    Notifications::newDraftPile($pId, $cards, $turn, $type, $choice);
  }

  /**
   * After promoting a new pile for a player, auto-pick if it's their last round
   * and the pile has exactly the cards they need (nothing to decide).
   * Returns true if the draft ended as a result (so callers can exit early).
   */
  private function maybeAutoPickLastCard($pId)
  {
    $myTurn = $this->getPlayerDraftTurn($pId);
    $isLastOverall = $myTurn == $this->getDraftTotalNumberOfTurns();
    if (!$isLastOverall) {
      return false;
    }

    $isDraftNPassing = in_array(Globals::getDraftMode(), [
      OPTION_DRAFT_7_SIMULTANEOUS, OPTION_DRAFT_7_OCCUPATIONS, OPTION_DRAFT_7_MINORS,
      OPTION_DRAFT_8_SIMULTANEOUS, OPTION_DRAFT_8_OCCUPATIONS, OPTION_DRAFT_8_MINORS,
      OPTION_DRAFT_9_SIMULTANEOUS, OPTION_DRAFT_9_OCCUPATIONS, OPTION_DRAFT_9_MINORS,
      OPTION_DRAFT_10_SIMULTANEOUS, OPTION_DRAFT_10_OCCUPATIONS, OPTION_DRAFT_10_MINORS,
    ]);
    if (!$isDraftNPassing) {
      return false;
    }

    $type = $this->getDraftType($myTurn);
    $player = Players::get($pId);
    $available = $player->getCards()->filter(function ($card) use ($type) {
      return $card->getLocation() == 'draft' && $type[$card->getType()] > 0;
    });

    $needed = $type[OCCUPATION] + $type[MINOR];
    if ($available->count() != $needed) {
      return false;
    }

    foreach ($available as $card) {
      $this->actDraftAdd($card->getId(), $player);
    }
    return $this->processPlayerConfirmation($pId, true);
  }

  /**
   * Core logic for applying one player's confirmed draft selection.
   * Returns true if the draft ended (nextState was called), false otherwise.
   * Callers must exit immediately on true to avoid double nextState calls.
   */
  private function processPlayerConfirmation($pId, $isAutopick = false)
  {
    $myTurn = $this->getPlayerDraftTurn($pId);
    $totalTurns = $this->getDraftTotalNumberOfTurns();
    $nextPId = Players::getNextId($pId);

    // Move selection → hand and record stats
    $cards = PlayerCards::confirmDraftSelectionForPlayer($pId);
    foreach ($cards as $card) {
      Stats::setNextCard($pId, $card->getCode(), $myTurn);
      Notifications::confirmDraftSelection($card);
    }
    // For autopick only: refresh hand so the card appears before the draft-is-over
    // notification fires. Normal confirmations don't need this — the confirmDraftSelection
    // notifications already update the UI.
    if ($isAutopick) {
      $player = Players::get($pId);
      $handCards = PlayerCards::getOfPlayer($pId)->filter(fn($card) => $card->getLocation() == 'hand');
      Notifications::refreshHand($player, $handCards->ui());
    }

    // On the last turn of the overall draft OR the last turn of the current phase,
    // discard remaining draft cards rather than queuing them to the next player.
    if ($myTurn >= $totalTurns || $this->isLastTurnOfCurrentPhase($myTurn)) {
      PlayerCards::discardPlayerRemainingDraftCards($pId);
    } else {
      // Snapshot unchosen card IDs before they move to 'passing', so the player can
      // still review them while waiting for their next pack.
      $unchosen = Players::get($pId)->getCards()->filter(fn($c) => $c->getLocation() == 'draft');
      $lastPool = Globals::getDraftLastPool() ?? [];
      $lastPool[$pId] = array_map(fn($c) => $c->getId(), iterator_to_array($unchosen));
      Globals::setDraftLastPool($lastPool);
      PlayerCards::queueDraftCardsForNextPlayer($pId, $myTurn);
    }

    // Increment this player's personal turn and broadcast updated progress to all
    $this->incPlayerDraftTurn($pId);
    $allTurns = $this->getAllPlayerDraftTurns();
    $total = $this->getDraftTotalNumberOfTurns();
    Notifications::draftProgress($allTurns, $total);

    // If next player has no active draft pile, promote their oldest queued pile and activate them
    if (!PlayerCards::hasDraftCards($nextPId)) {
      $nextTurn = $this->getPlayerDraftTurn($nextPId);
      if ($nextTurn > $total && !$this->isPhaseStart($nextTurn)) {
        // Next player is past their last turn — discard any orphaned passing cards
        PlayerCards::discardPlayerPassingCards($nextPId);
      } elseif (PlayerCards::promoteOldestPassingPile($nextPId)) {
        $nextType = $this->getDraftType($nextTurn);
        $nextCards = Players::get($nextPId)->getCards()->filter(function ($card) use ($nextType) {
          return $card->getLocation() == 'draft' && $nextType[$card->getType()] > 0;
        })->toArray();
        $nextChoice = $this->buildDraftChoiceDescription($nextType);
        $this->sendNewDraftPile($nextPId, $nextCards, $nextTurn, $nextType, $nextChoice);
        $this->gamestate->setPlayersMultiactive([$nextPId], 'done');
        $this->giveExtraTime($nextPId, 90);
        if ($this->maybeAutoPickLastCard($nextPId)) {
          return true; // Draft ended in cascade — stop here
        }
      }
    }

    // If the nextPId cascade recursively processed pId's next turn(s) already
    // (e.g. nextPId's auto-pick promoted a pile for pId and ran pId's phase-start),
    // pId's DB turn will have advanced beyond myTurn+1. Nothing more to do here.
    if ($this->getPlayerDraftTurn($pId) > $myTurn + 1) {
      return false;
    }

    // Phase transition: if this player just crossed into a new phase (e.g. occ→minor),
    // activate their pre-dealt phase2 cards immediately without waiting for a passing pile
    $newTurn = $this->getPlayerDraftTurn($pId);
    if ($this->isPhaseStart($newTurn)) {
      PlayerCards::activatePhase2Cards($pId);
      $phaseType = $this->getDraftType($newTurn);
      $phaseCards = Players::get($pId)->getCards()->filter(function ($card) use ($phaseType) {
        return $card->getLocation() == 'draft' && $phaseType[$card->getType()] > 0;
      })->toArray();
      $phaseChoice = $this->buildDraftChoiceDescription($phaseType);
      $this->sendNewDraftPile($pId, $phaseCards, $newTurn, $phaseType, $phaseChoice);
      $this->gamestate->setPlayersMultiactive([$pId], 'done');
      $this->giveExtraTime($pId, 90);
      if ($this->maybeAutoPickLastCard($pId)) {
        return true;
      }
      return false;
    }

    // Check if this player has a queued pile ready — if so, re-activate with new pile
    // Guard: only self-promote if we don't already have draft cards (prevents pile merging)
    // and the player hasn't already completed all their turns
    $newTurn = $this->getPlayerDraftTurn($pId);
    if ($newTurn > $total && !$this->isPhaseStart($newTurn)) {
      // Player is past their last turn — discard any orphaned passing cards
      PlayerCards::discardPlayerPassingCards($pId);
    } elseif (!PlayerCards::hasDraftCards($pId) && PlayerCards::promoteOldestPassingPile($pId)) {
      $newType = $this->getDraftType($newTurn);
      $newCards = Players::get($pId)->getCards()->filter(function ($card) use ($newType) {
        return $card->getLocation() == 'draft' && $newType[$card->getType()] > 0;
      })->toArray();
      $newChoice = $this->buildDraftChoiceDescription($newType);
      $this->sendNewDraftPile($pId, $newCards, $newTurn, $newType, $newChoice);
      // Activate AFTER newDraftPile so onEnteringState fires when cards are already in DOM,
      // preventing a double-slide (onEnteringState would otherwise re-render them).
      $this->gamestate->setPlayersMultiactive([$pId], 'done');
      $this->giveExtraTime($pId, 90);
      if ($this->maybeAutoPickLastCard($pId)) {
        return true; // Draft ended in cascade
      }
      return false;
    }

    // The auto-pick cascade may have already promoted a new pile for this player and
    // reactivated them — leave them active instead of falling through to draftWaiting
    if (PlayerCards::hasDraftCards($pId)) {
      return false;
    }

    // No more cards for this player right now — check if the whole draft is done
    if ($this->checkDraftComplete()) {
      return true;
    }

    // Tell this player who they're waiting for, then deactivate them
    $predecessorPId = $this->getDraftPredecessor($pId);
    Notifications::draftWaiting($pId, $predecessorPId, $allTurns, $total);
    $this->gamestate->setPlayerNonMultiactive($pId, 'done');
    return false;
  }

  /**
   * Check if all draft cards have been processed; if so, wrap up and transition out.
   */
  private function checkDraftComplete()
  {
    if (PlayerCards::countCardsInDraftLocations() > 0) {
      return false;
    }

    if (Globals::getDraftMode() == OPTION_DRAFT_LIVING_HAND) {
      PlayerCards::discardRemainingDraftPools();
    }
    Notifications::draftIsOver();
    $this->saveSeed();
    $this->gamestate->nextState('done');
    return true;
  }

  /**
   * Entry point for the draft phase.
   * For new games (draftTurn == 0): initialises the async per-player system.
   * For legacy games (draftTurn > 0): re-entered from stApplyDraft each round.
   */
  function stDraftGame()
  {
    // If draft is disabled, skip this phase (solo Living Hand deals the hand directly, no draft)
    if (
      Globals::getDraftMode() == OPTION_DRAFT_DISABLED ||
      (Globals::getDraftMode() == OPTION_DRAFT_LIVING_HAND && Globals::isSolo())
    ) {
      foreach (Players::getAll() as $pId => $player) {
        foreach ($player->getHand() as $card) {
          Stats::setNextCard($pId, $card->getCode(), 0);
        }
      }
      $this->saveSeed();
      // Solo campaign: show the game-1 summary modal before the first turn.
      // Activate the solo player first — a neutral-state jump lets changeActivePlayer
      // take effect before the activeplayer state (mirrors nextPlayerCustomOrder).
      if (Globals::isCampaign()) {
        $this->gamestate->jumpToState(ST_GENERIC_NEXT_PLAYER);
        $this->gamestate->changeActivePlayer(Players::getAll()->first()->getId());
        $this->gamestate->jumpToState(ST_CAMPAIGN_NEW_GAME_INTRO);
        return;
      }
      $this->gamestate->nextState('noDraft');
      return;
    }

    // If "load seed" mode is selected, skip to this phase
    if (Globals::getDraftMode() == OPTION_SEED_MODE) {
      $this->gamestate->setAllPlayersMultiactive();
      $this->gamestate->nextState('seed');
      return;
    }

    // Legacy sync path: draftTurn > 0 means this game started before the async rewrite
    if (Globals::getDraftTurn() > 0) {
      $turn = Globals::incDraftTurn();
      $totalTurns = $this->getDraftTotalNumberOfTurns();
      if ($turn > $totalTurns) {
        foreach (Players::getAll() as $pId => $player) {
          foreach (PlayerCards::getSelectQuery()->wherePlayer($pId)->where('card_location', 'passing')->get() as $card) {
            Stats::setNextDiscardedCard($pId, $card->getCode());
          }
        }
        if (Globals::getDraftMode() == OPTION_DRAFT_LIVING_HAND) {
          PlayerCards::discardRemainingDraftPools();
        }
        Notifications::draftIsOver();
        $this->saveSeed();
        $this->gamestate->nextState('startTurn');
        return;
      }
      PlayerCards::passCards();
      $players = Players::getAll()->getIds();
      foreach ($players as $pId) {
        $this->giveExtraTime($pId, 90);
      }
      $this->gamestate->setPlayersMultiactive($players, 'draft');
      $this->gamestate->nextState('draft');
      return;
    }

    // New async path: initialise per-player turn counters (round 1 for everyone)
    $players = Players::getAll()->getIds();
    foreach ($players as $pId) {
      $setter = 'set' . $this->draftTurnGlobalName($pId);
      Globals::$setter(1);
    }
    foreach ($players as $pId) {
      $this->giveExtraTime($pId, 90);
    }
    $this->gamestate->setPlayersMultiactive($players, 'done');
    $this->gamestate->nextState('draft');
  }

  /**
   * Compute the available pool of cards for each player.
   * For legacy games (draftPlayerTurns === null), all players share the same turn
   * from the global draftTurn. For new async games, turns are tracked per-player.
   */
  function argsDraftPlayers()
  {
    $total = $this->getDraftTotalNumberOfTurns();
    $playerTurns = $this->isAsyncDraft() ? $this->getAllPlayerDraftTurns() : null;

    // Legacy sync path: all players share the same global turn.
    // Return format matches the original sync draft exactly — _private[$pId] is
    // a flat array of card objects, NOT wrapped in {cards, turn, ...}.
    if (empty($playerTurns)) {
      $type = $this->getDraftType();

      $args = [];
      foreach (Players::getAll() as $pId => $player) {
        $args[$pId] = $player
          ->getCards()
          ->filter(function ($card) use ($type) {
            return in_array($card->getLocation(), ['draft', 'selection']) && $type[$card->getType()] > 0;
          })
          ->toArray();
      }

      // Compute correct description
      $choice = [
        'log' => '',
        'args' => [
          'minor' => $type[MINOR],
          'occupation' => $type[OCCUPATION],
        ],
      ];
      if ($type[OCCUPATION] > 0 && $type[MINOR] > 0) {
        $choice['log'] = clienttranslate('${minor} minor improvement(s) and ${occupation} occupation(s)');
      } elseif ($type[OCCUPATION] > 0) {
        $choice['log'] = $type[OCCUPATION] == 1 ? clienttranslate('an occupation') : '${occupation} occupations';
      } elseif ($type[MINOR] > 0) {
        $choice['log'] = $type[MINOR] == 1 ? clienttranslate('a minor improvement') : '${minor} minor improvements';
      }

      // Avoid notification size limit
      if (Globals::getDraftMode() == OPTION_DRAFT_FREE) {
        $args = [];
      }

      return [
        '_private' => $args,
        'type' => $type,
        'i18n' => 'draftChoice',
        'draftChoice' => $choice,
        'turn' => Globals::getDraftTurn(),
        'total' => $total,
      ];
    }

    // Async path: each player may be on a different turn
    $lastPoolMap = Globals::getDraftLastPool() ?? [];
    $privateArgs = [];
    foreach (Players::getAll() as $pId => $player) {
      $turn = $playerTurns[$pId] ?? 1;
      $type = $this->getDraftType($turn);
      $cards = Globals::getDraftMode() == OPTION_DRAFT_FREE ? [] : $player
        ->getCards()
        ->filter(function ($card) use ($type) {
          return in_array($card->getLocation(), ['draft', 'selection']) && $type[$card->getType()] > 0;
        })
        ->toArray();
      // If waiting (no active cards), include last pack's unchosen cards for display
      $lastPool = [];
      if (empty($cards) && isset($lastPoolMap[$pId])) {
        foreach ($lastPoolMap[$pId] as $cardId) {
          $card = PlayerCards::get($cardId);
          if ($card) $lastPool[] = $card;
        }
      }
      $privateArgs[$pId] = [
        'cards' => $cards,
        'lastPool' => $lastPool,
        'turn' => $turn,
        'type' => $type,
        'draftChoice' => $this->buildDraftChoiceDescription($type),
      ];
    }

    // Public description uses the earliest (minimum) active turn for observer display
    $minTurn = empty($playerTurns) ? 1 : min(array_values($playerTurns));
    $minType = $this->getDraftType($minTurn);

    return [
      '_private' => $privateArgs,
      'type' => $minType,
      'i18n' => ['draftChoice'],
      'draftChoice' => $this->buildDraftChoiceDescription($minType),
      'turn' => $minTurn,
      'total' => $total,
      'playerTurns' => $playerTurns,
      'firstPlayer' => Globals::getFirstPlayer(),
    ];
  }

  function stDraftPlayers()
  {
    // For legacy games: auto-pick the last round if only one card per player remains
    if (!$this->isAsyncDraft()) {
      $turn = Globals::getDraftTurn();
      $totalTurns = $this->getDraftTotalNumberOfTurns();
      $mode = Globals::getDraftMode();
      $isDraft7Passing = in_array($mode, [
        OPTION_DRAFT_7_SIMULTANEOUS, OPTION_DRAFT_7_OCCUPATIONS, OPTION_DRAFT_7_MINORS,
      ]);
      if ($isDraft7Passing && $turn == $totalTurns) {
        $args = $this->argsDraftPlayers();
        foreach (Players::getAll() as $pId => $player) {
          foreach ($args['_private'][$pId] as $card) {
            $this->actDraftAdd($card->getId(), $player);
          }
        }
        $this->gamestate->nextState('apply');
      }
      return;
    }
    // Async path: auto-pick is handled per-player in maybeAutoPickLastCard,
    // called after each pile promotion in processPlayerConfirmation.
  }

  /**
   * Add a card to the draft selection
   */
  public function actDraftAdd($cardId, $player = null)
  {
    $card = PlayerCards::get($cardId);
    $player = $player ?? Players::getCurrent();

    // Check card is in hand
    if ($card->getPId() != $player->getId()) {
      throw new \BgaVisibleSystemException('Card is not in hand');
    }

    if ($card->getLocation() == 'selection') {
      // Already selected: client missed the notif (lag/double-click) — resync it instead of erroring
      Notifications::addCardToDraftSelection($player, $card, $card->getState());
      return;
    }
    if ($card->getLocation() != 'draft') {
      throw new \BgaVisibleSystemException('Card has already been selected');
    }

    $pos = PlayerCards::addToSelection($card);
    $check = $this->checkDraftSelection();
    if ($check == -1) {
      throw new \BgaVisibleSystemException('Too many cards drafted. Should not happen');
    }

    Notifications::addCardToDraftSelection($player, $card, $pos);
  }

  /**
   * Check the draft selection of a player
   *  => return -1 if invalid, 0 if incomplete, 1 if fulfilled
   */
  public function checkDraftSelection($player = null)
  {
    $player = $player ?? Players::getCurrent();
    $selection = $player->getDraftSelection();
    $selectionByType = $selection->reduce(
      function ($res, $card) {
        $res[$card->getType()]++;
        return $res;
      },
      [MINOR => 0, OCCUPATION => 0]
    );

    // Legacy: use global getDraftType() (reads global draftTurn).
    // Async: use per-player turn.
    if ($this->isAsyncDraft()) {
      $type = $this->getDraftType($this->getPlayerDraftTurn($player->getId()));
    } else {
      $type = $this->getDraftType();
    }

    if ($type[MINOR] < $selectionByType[MINOR] || $type[OCCUPATION] < $selectionByType[OCCUPATION]) {
      return -1;
    } elseif ($type[MINOR] == $selectionByType[MINOR] && $type[OCCUPATION] == $selectionByType[OCCUPATION]) {
      return 1;
    } else {
      return 0;
    }
  }

  /**
   * Remove a card to the draft selection
   */
  public function actDraftRemove($cardId)
  {
    $card = PlayerCards::get($cardId);
    $player = Players::getCurrent();

    // Check card is in hand
    if ($card->getPId() != $player->getId()) {
      throw new \BgaVisibleSystemException('Card is not in hand');
    }

    if ($card->getLocation() != 'selection') {
      throw new \BgaVisibleSystemException('Card has not already been selected');
    }

    PlayerCards::removeFromSelection($card);
    Notifications::removeCardFromDraftSelection($player, $card);
    $this->gamestate->setPlayersMultiactive([$player->getId()], '');
  }

  /**
   * Confirm a draft selection.
   * Legacy sync: deactivate player; batch-apply when all confirm via stApplyDraft.
   * Async: process this player's confirmation immediately.
   */
  public function actDraftConfirm()
  {
    $player = Players::getCurrent();

    // Guard: reject confirmation if this player has already completed all draft turns
    if ($this->isAsyncDraft()) {
      $pTurn = $this->getPlayerDraftTurn($player->getId());
      $pTotal = $this->getDraftTotalNumberOfTurns();
      if ($pTurn > $pTotal) {
        throw new \BgaVisibleSystemException('Draft is already complete for this player (turn ' . $pTurn . '/' . $pTotal . ')');
      }
    }

    $check = $this->checkDraftSelection($player);
    if ($check != 1) {
      throw new \BgaVisibleSystemException(
        'Your draft selection is not valid: ' . ($check == -1 ? 'too many cards drafted' : 'not enough cards drafted')
      );
    }

    if (!$this->isAsyncDraft()) {
      // Legacy sync: mark player as done, transition to stApplyDraft when all confirm
      $this->gamestate->setPlayerNonMultiactive($player->getId(), 'apply');
      return;
    }

    // Async: process immediately
    $this->processPlayerConfirmation($player->getId());
  }

  /**
   * Legacy sync apply state — only reached by games that pre-date the async rewrite.
   * Confirms all selections for this round, then loops back to stDraftGame which
   * handles card passing, end-of-draft detection, and player activation.
   */
  public function stApplyDraft()
  {
    foreach (Players::getAll() as $player) {
      if ($this->checkDraftSelection($player) != 1) {
        throw new \BgaVisibleSystemException('Draft selection of player #' . $player->getId() . ' is not valid');
      }
    }

    $turn = Globals::getDraftTurn();
    foreach (Players::getAll() as $pId => $player) {
      foreach ($player->getDraftSelection() as $card) {
        Stats::setNextCard($pId, $card->getCode(), $turn);
      }
    }

    $cards = PlayerCards::confirmDraftSelections();
    foreach ($cards as $card) {
      Notifications::confirmDraftSelection($card);
    }
    Notifications::clearDraftPools();
    $this->gamestate->nextState('draft');
  }

  /******************************
   * Living Hand refill drafting *
   ******************************/

  function stLivingHandAfterTurn()
  {
    $pId = Players::getActiveId();
    $occ = PlayerCards::countHandCardsOfType($pId, OCCUPATION);
    $min = PlayerCards::countHandCardsOfType($pId, MINOR);

    if ($occ < 4 || $min < 4) {
      $this->stStartLivingHandRefill('nextPlayer');
      return;
    }

    $this->nextPlayerCustomOrder('labor');
  }

  public function maybeStartLivingHandRefillBeforeTurn($player)
  {
    if (Globals::getDraftMode() != OPTION_DRAFT_LIVING_HAND) {
      return false;
    }

    if (!$this->livingHandNeedsRefill($player)) {
      return false;
    }

    $this->stStartLivingHandRefill('samePlayer');
    return true;
  }

  private function continueAfterLivingHandRefill()
  {
    $returnMode = Globals::getLivingHandRefillReturn();
    if ($returnMode == 'samePlayer') {
      $this->gamestate->jumpToState(ST_LABOR);
      return;
    }

    $this->nextPlayerCustomOrder('labor');
  }

  function stStartLivingHandRefill($returnMode = 'nextPlayer')
  {
    $pId = Players::getActiveId();
    $player = Players::get($pId);

    Globals::setLivingHandRefillReturn($returnMode);

    // Clear any previous offer (DB + UI)
    PlayerCards::clearLivingHandOffer($pId);
    Notifications::livingHandOfferCleared($pId);

    $needOcc = PlayerCards::countHandCardsOfType($player, OCCUPATION) < 4;
    $needMinor = PlayerCards::countHandCardsOfType($player, MINOR) < 4;

    if (!$needOcc && !$needMinor) {
      // No refill needed; continue normal turn order
      $this->continueAfterLivingHandRefill();
      return;
    }

    $type = $needOcc ? OCCUPATION : MINOR;

    PlayerCards::createLivingHandOffer($pId, $type);
    $offer = PlayerCards::getLivingHandOffer($pId);

    Notifications::livingHandOfferCreated($pId, $offer, $type);

    $this->gamestate->jumpToState(ST_LIVING_HAND_REFILL);
  }

  function argsLivingHandRefill()
  {
    $player = Players::getActive();
    $pId = $player->getId();

    $offer = PlayerCards::getLivingHandOffer($pId)->toArray();

    return [
      '_private' => [
        $pId => [
          'offer' => $offer,
        ],
      ],
      'counts' => [
        'occ' => $player->getHand(OCCUPATION)->count(),
        'minor' => $player->getHand(MINOR)->count(),
      ],
    ];
  }

  public function actLivingHandPick($cardId)
  {
    self::checkAction('actLivingHandPick');

    $player = Players::getCurrent();
    $pId = $player->getId();

    $offer = PlayerCards::getLivingHandOffer($pId);
    if (!in_array($cardId, $offer->getIds(), true)) {
      throw new \BgaUserException(clienttranslate('You must choose a card from the Living Hand offer'));
    }

    PlayerCards::acceptLivingHandOffer($pId, $cardId);

    $pos = PlayerCards::get($cardId)->getState();
    Notifications::livingHandPicked($pId, $cardId, $pos);

    Notifications::refreshHand($player, $player->getHand()->ui());

    $needOcc = PlayerCards::countHandCardsOfType($pId, OCCUPATION) < 4;
    $needMinor = PlayerCards::countHandCardsOfType($pId, MINOR) < 4;

    if ($needOcc || $needMinor) {
      $type = $needOcc ? OCCUPATION : MINOR;

      PlayerCards::createLivingHandOffer($pId, $type);
      $newOffer = PlayerCards::getLivingHandOffer($pId);

      Notifications::livingHandOfferCreated($pId, $newOffer, $type);

      $this->gamestate->jumpToState(ST_LIVING_HAND_REFILL);
    } else {
      Notifications::livingHandOfferCleared($pId, true);
      $this->continueAfterLivingHandRefill();
    }
  }

  private function livingHandNeedsRefill($player)
  {
    $occ = $player->getHand(OCCUPATION)->count();
    $min = $player->getHand(MINOR)->count();
    return ($occ < 4) || ($min < 4);
  }

  public function maybeEnterLivingHandPassingDecision($player)
  {
    if (Globals::getDraftMode() != OPTION_DRAFT_LIVING_HAND) {
      return false;
    }

    $pending = Globals::getLivingHandPendingPassing();
    $pId = $player->getId();
    $queue = is_array($pending) && isset($pending[$pId]) && is_array($pending[$pId]) ? $pending[$pId] : [];
    if (empty($queue)) {
      return false;
    }

    $this->gamestate->jumpToState(ST_LIVING_HAND_PASSING_DECISION);
    return true;
  }

  function argsLivingHandPassingDecision()
  {
    $player = Players::getActive();
    $pId = $player->getId();

    $pending = Globals::getLivingHandPendingPassing();
    $queue = is_array($pending) && isset($pending[$pId]) && is_array($pending[$pId]) ? $pending[$pId] : [];
    $cardId = empty($queue) ? null : $queue[0];
    $card = is_null($cardId) ? null : PlayerCards::getSingle($cardId);

    return [
      'i18n' => ['card_name'],
      'card_name' => is_null($card) ? clienttranslate('this card') : $card->getName(),
      '_private' => [
        $pId => [
          'card' => $card,
        ],
      ],
    ];
  }

  private function continueAfterLivingHandPassingDecision($pId)
  {
    $pending = Globals::getLivingHandPendingPassing();
    $queue = is_array($pending) && isset($pending[$pId]) && is_array($pending[$pId]) ? $pending[$pId] : [];
    if (!empty($queue)) {
      $this->gamestate->jumpToState(ST_LIVING_HAND_PASSING_DECISION);
      return;
    }

    $this->gamestate->jumpToState(ST_LABOR);
  }

  public function actLivingHandPassDecision($decision)
  {
    self::checkAction('actLivingHandPassDecision');
    if (!in_array($decision, ['keep', 'decline'], true)) {
      throw new \BgaVisibleSystemException('Invalid Living Hand passing decision');
    }

    $player = Players::getCurrent();
    $pId = $player->getId();
    $pending = Globals::getLivingHandPendingPassing();
    if (!is_array($pending)) {
      $pending = [];
    }

    $queue = isset($pending[$pId]) && is_array($pending[$pId]) ? $pending[$pId] : [];
    if (empty($queue)) {
      $this->continueAfterLivingHandPassingDecision($pId);
      return;
    }

    $cardId = array_shift($queue);
    if (empty($queue)) {
      unset($pending[$pId]);
    } else {
      $pending[$pId] = array_values($queue);
    }
    Globals::setLivingHandPendingPassing($pending);

    $card = PlayerCards::getSingle($cardId);
    if (is_null($card) || $card->getPId() != $pId || $card->getLocation() != 'hand' || $card->isPlayed()) {
      $this->continueAfterLivingHandPassingDecision($pId);
      return;
    }

    if ($decision == 'decline') {
      PlayerCards::moveToDiscardpile($cardId);
      Notifications::refreshHand($player, $player->getHand()->ui());
    }

    Notifications::livingHandPassDecision($player, $card, $decision);

    $this->continueAfterLivingHandPassingDecision($pId);
  }

  /**
   * Save/load seed
   */
  public function saveSeed()
  {
    $raw = Players::count() . '|' . ActionCards::getSeed() . '|' . PlayerCards::getSeed();
    $encoded = rtrim(strtr(base64_encode(addslashes(gzcompress($raw, 9))), '+/', '-_'), '=');
    Globals::setGameSeed($encoded);
  }

  public function actLoadSeed($seed)
  {
    $raw = gzuncompress(
      stripslashes(base64_decode(str_pad(strtr($seed, '-_', '+/'), strlen($seed) % 4, '=', STR_PAD_RIGHT)))
    );
    $data = explode('|', $raw);
    if ($data[0] != Players::count()) {
      throw new \BgaUserException(
        'Trying to load a ' . $data[0] . ' players seed in your ' . Players::count() . ' players game'
      );
    }

    // Load action cards
    ActionCards::setSeed($data[1]);

    // Load player cards
    $i = 2;
    PlayerCards::preSeedClear();
    foreach (Players::getAll() as $player) {
      PlayerCards::setSeed($player, $data[$i++]);
    }

    // Prime client cache before refreshHand (which strips static fields).
    $allHandCards = [];
    foreach (Players::getAll() as $player) {
      foreach ($player->getHand() as $card) {
        $allHandCards[] = $card->jsonSerialize();
      }
    }
    Notifications::populateCardCache($allHandCards);

    $datas = $this->getAllDatas();
    Notifications::refreshUI($datas);
    foreach (Players::getAll() as $player) {
      Notifications::refreshHand($player, $player->getHand()->ui());
    }

    // Atomic deactivate-all + transition; plain nextState leaves the calling
    // client flagged active in the dead state, stuck on "Updating".
    $this->gamestate->setPlayersMultiactive([], 'start', true);
  }
}
