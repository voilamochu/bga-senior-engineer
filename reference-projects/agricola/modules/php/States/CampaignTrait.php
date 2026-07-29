<?php

namespace AGR\States;

use AGR\Core\Globals;
use AGR\Core\Notifications;
use AGR\Managers\Campaign;
use AGR\Managers\PlayerCards;
use AGR\Managers\Players;

trait CampaignTrait
{
  function argsChoosePermanent()
  {
    $player = Players::getAll()->first();
    $last = Campaign::lastResult();
    $success = $last != null && $last['hit'];

    $cards = [];
    if ($success && Campaign::canMakePermanent()) {
      foreach (Campaign::getEligibleOccupations($player) as $card) {
        $cards[] = ['id' => $card->getId(), 'name' => $card->getName()];
      }
    }

    $goal = $last == null ? Campaign::getGoal() : $last['goal'];
    $nextGoal = Campaign::nextGoal($goal);
    return [
      'success' => $success,
      'score' => $last == null ? 0 : $last['score'],
      'goal' => $goal,
      'nextGoal' => $nextGoal,
      // The 8-game arc is complete once a win takes the goal past the fixed table
      'completedArc' => $success && !in_array($nextGoal, Campaign::GOALS),
      // True only on the win that clears the final fixed target (67) — completes the campaign
      'justCompletedCampaign' => $success && $goal == 67,
      // Game 1: making a permanent is mandatory. Games 2+: optional (player may choose none).
      'firstGame' => $last != null && $last['game'] == 1,
      '_private' => [
        'active' => [
          'cards' => $cards,
        ],
      ],
    ];
  }

  function actChoosePermanent($cardId)
  {
    self::checkAction('actChoosePermanent');
    $last = Campaign::lastResult();
    if ($last == null || !$last['hit']) {
      throw new \BgaVisibleSystemException('You can only make an occupation permanent after reaching the goal');
    }
    if (!Campaign::canMakePermanent()) {
      throw new \BgaVisibleSystemException('You already have the maximum number of permanent occupations');
    }
    $player = Players::getAll()->first();
    $eligible = Campaign::getEligibleOccupations($player)->getIds();
    if (!in_array($cardId, $eligible)) {
      throw new \BgaVisibleSystemException('You cannot make this occupation permanent');
    }

    $card = PlayerCards::getMany([$cardId])->first();
    Campaign::makePermanent($cardId);
    Notifications::campaignPermanentChosen($player, $card);
    $this->gamestate->nextState('next');
  }

  // Continue to the next game without adding a permanent: a miss (retry, no food), extended play,
  // a win where no eligible occupation was played, or — from game 2 on — a win where the player
  // declines to make a permanent (it's only mandatory in game 1).
  function actProceed()
  {
    self::checkAction('actProceed');
    $last = Campaign::lastResult();
    $won = $last != null && $last['hit'];
    $isFirstGame = $last != null && $last['game'] == 1;

    // Only game 1 forces the reward: you must make one of your played occupations permanent.
    // After that it's optional, so the player may continue without choosing.
    if ($isFirstGame && $won && Campaign::canMakePermanent() && !Campaign::getEligibleOccupations(Players::getAll()->first())->empty()) {
      throw new \BgaVisibleSystemException('You must choose an occupation to make permanent');
    }

    $this->gamestate->nextState('next');
  }

  function actEndCampaign()
  {
    self::checkAction('actEndCampaign');
    Notifications::campaignComplete(Globals::getCampaignResults(), Campaign::GOALS);
    // No seed box in campaign mode (replaying a single game's config isn't meaningful here)
    $this->gamestate->jumpToState(\ST_END_GAME);
  }

  function stCampaignNextGame()
  {
    Campaign::setupNextGame();

    // Refresh the whole board in place (no page reload). refreshUI/refreshHand are the
    // same mechanisms used by turn-restart; campaignNewGame resets the action-card row,
    // turn counter, harvest icons and campaign panel.

    // Prime the client card cache first: refreshUI/refreshHand strip static fields
    // (name/desc) assuming the cache is warm, but the new game deals cards never seen.
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
    Notifications::campaignNewGame(
      Campaign::getGameNumber(),
      Campaign::getGoal(),
      $datas['cards'],
      $datas['campaign'],
      $datas['turn']
    );

    $this->gamestate->nextState('intro');
  }

  function argsCampaignNewGameIntro()
  {
    $perms = [];
    foreach (Globals::getPermanentOccupations() as $cId) {
      $cards = PlayerCards::getMany([$cId], false);
      if ($cards->count() > 0) {
        $perms[] = ['id' => $cId, 'name' => $cards->first()->getName()];
      }
    }

    $last = Campaign::lastResult();
    return [
      'gameNumber' => Campaign::getGameNumber(),
      'goal' => Campaign::getGoal(),
      'permanentList' => $perms,
      'bonusFood' => Campaign::lastBonusFood(),
      'retry' => $last != null && !$last['hit'],
      'campaign' => Campaign::getUiData(),
    ];
  }

  function actStartCampaignGame()
  {
    self::checkAction('actStartCampaignGame');
    // Permanents' immediate (onBuy) effects fire at the start of round 1 (turn 1), not here at
    // turn 0 as that breaks some cards. stPreparation consumes this flag.
    Globals::setCampaignPermanentsPending(true);
    $this->gamestate->jumpToState(ST_BEFORE_START_OF_TURN);
  }

  function stCampaignPlayPermanents()
  {
    if (empty(Campaign::getUnplayedPermanents())) {
      $this->gamestate->jumpToState(ST_PREPARATION);
      return;
    }

    $this->gamestate->jumpToState(ST_GENERIC_NEXT_PLAYER);
    $this->gamestate->changeActivePlayer(Players::getAll()->first()->getId());
    Campaign::setupPermanentsFlow(['state' => ST_PREPARATION]);
  }
}
