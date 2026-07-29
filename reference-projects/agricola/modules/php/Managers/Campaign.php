<?php
namespace AGR\Managers;

use AGR\Core\Globals;
use AGR\Core\Engine;
use AGR\Core\Notifications;
use AGR\Core\Stats;
use AGR\Helpers\Collection;

/*
 * Campaign : solo campaign of chained games.
 *  - Campaign state lives in Globals (single game instance == one whole campaign).
 *  - Between games the board is torn down and rebuilt. Permanent occs have their own location
 *  - The goal (Globals::campaignGoal) advances one step per game won.
 */
class Campaign
{
  const GOALS = [50, 55, 59, 62, 64, 65, 66, 67];

  public static function isActive()
  {
    return Globals::isCampaign();
  }

  /* Which game of the campaign you are on (starting count from 1) */
  public static function getGameNumber()
  {
    $goal = self::getGoal();
    $i = array_search($goal, self::GOALS);
    return $i !== false ? $i + 1 : 8 + ($goal - 67);
  }

  public static function getPermanentCount()
  {
    return count(Globals::getPermanentOccupations());
  }

  public static function canMakePermanent()
  {
    return self::getPermanentCount() < 7;
  }

  // either the next goal in the list, or just add 1 to the goal if finished the 8 initial targets
  public static function nextGoal($goal)
  {
    $i = array_search($goal, self::GOALS);
    if ($i !== false && $i < 7) { // 0..6 have a higher fixed target; 7 (goal 67) is the last
      return self::GOALS[$i + 1];
    }
    return $goal + 1;
  }

  public static function getGoal()
  {
    $g = Globals::getCampaignGoal();
    return $g > 0 ? $g : self::GOALS[0];
  }

  public static function setup()
  {
    Globals::setCampaignGoal(self::GOALS[0]);
    Globals::setPermanentOccupations([]);
    Globals::setCampaignResults([]);
  }

  public static function getFinalScore()
  {
    $pId = Players::getAll()->first()->getId();
    $scores = Scores::compute();
    return $scores[$pId]['total'] ?? 0;
  }

  public static function lastResult()
  {
    $results = Globals::getCampaignResults();
    return empty($results) ? null : end($results);
  }

  public static function recordEndOfGame()
  {
    $goal = self::getGoal();
    $score = self::getFinalScore();
    $hit = $score >= $goal;

    $results = Globals::getCampaignResults();
    $results[] = [
      'game' => self::getGameNumber(),
      'goal' => $goal,
      'score' => $score,
      'hit' => $hit,
    ];
    Globals::setCampaignResults($results);
  }

  // Occupations played this game that are not already permanent
  public static function getEligibleOccupations($player)
  {
    $perms = Globals::getPermanentOccupations();

    $eligible = new Collection();
    foreach ($player->getCards(OCCUPATION, true) as $card) {
      $id = $card->getId();
      if (!in_array($id, $perms)) {
        $eligible[$id] = $card;
      }
    }
    return $eligible;
  }

  // Caller (actChoosePermanent) only passes eligible cards, so it's never already permanent
  public static function makePermanent($cardId)
  {
    $perms = Globals::getPermanentOccupations();
    $perms[] = $cardId;
    Globals::setPermanentOccupations($perms);
  }

  // Tear down the board and deal the next game; permanent occupations are kept (staged out of play)
  public static function setupNextGame()
  {
    $options = Globals::getCampaignOptions();
    $permanentIds = Globals::getPermanentOccupations();

    // Solo campaign: one player. The shared setup helpers below still expect a pId-keyed map.
    $player = Players::getAll()->first();
    $pId = $player->getId();
    $players = [$pId => $player];

    // A retry (previous game missed the goal) replays with the same hand of cards and no food
    $last = self::lastResult();
    $retry = $last != null && !$last['hit'];

    // A retry re-deals the hand as recorded when it was dealt (playing a card can move it out of
    // the player's ownership: solo passing, Moonshine, Illusionist)
    $retryOccs = [];
    $retryMinors = [];
    if ($retry) {
      $dealt = Globals::getCampaignDealtHand();
      $retryOccs = $dealt[OCCUPATION] ?? [];
      $retryMinors = $dealt[MINOR] ?? [];
      // Tables dealt before the hand was recorded: reconstruct it from card locations
      if (empty($retryOccs) && empty($retryMinors)) {
        foreach ($player->getCards(OCCUPATION) as $card) {
          if (!in_array($card->getId(), $permanentIds)) {
            $retryOccs[] = $card->getId();
          }
        }
        foreach ($player->getCards(MINOR) as $card) {
          $retryMinors[] = $card->getId();
        }
        foreach (PlayerCards::getSelectQuery()->where('card_location', 'box')->get() as $card) {
          if ($card->getType() == OCCUPATION && !in_array($card->getId(), $permanentIds)) {
            $retryOccs[] = $card->getId();
          } elseif ($card->getType() == MINOR) {
            $retryMinors[] = $card->getId();
          }
        }
      }
    }

    // 1) Teardown: every meeple, and every card except the permanent occupations
    Meeples::DB()->delete()->run();
    if (empty($permanentIds)) {
      PlayerCards::DB()->delete()->run();
    } else {
      PlayerCards::DB()->delete()->whereNotIn('card_id', $permanentIds)->run();
      PlayerCards::DB()
        ->update([
          'extra_datas' => null,
          'player_id' => null,
          'card_location' => 'campaignPermanent',
          'card_state' => 0,
        ])
        ->whereIn('card_id', $permanentIds)
        ->run();
    }

    // 2) Reset per-game globals; reaching the goal advances to the next game's target
    Globals::resetForNextCampaignGame();
    // The stats table spans the whole campaign, but Breed Registry (D36) reads these three at
    // scoring and 'during the game' on the card means the current campaign game only
    Stats::setBoardSheep($player, 0);
    Stats::setCardsSheep($player, 0);
    Stats::setConvertedSheep($player, 0);
    if ($last != null && $last['hit']) {
      Globals::setCampaignGoal(self::nextGoal(self::getGoal()));
    }

    // 3) Rebuild the board, action spaces and hands
    Meeples::setupNewGame($players, $options);
    ActionCards::setupNewGame($players, $options);
    if ($retry) {
      PlayerCards::setupCampaignMajors($players, $options);
      PlayerCards::dealSpecificHand($pId, $retryOccs, $retryMinors);
    } else {
      PlayerCards::setupCampaignGame($players, $options, $permanentIds);
    }

    // 4) Reset score to the per-game baseline
    $base = Globals::isLiveScoring() ? -14 : 0;
    Players::DB()->update(['player_score' => $base, 'player_score_aux' => 0], $pId);

    // 5) Bonus starting food for exceeding the previous goal (derived from the last result; 0 on a miss)
    $bonus = self::lastBonusFood();
    if ($bonus > 0) {
      Meeples::create([['type' => FOOD, 'player_id' => $pId, 'location' => 'reserve', 'nbr' => $bonus]]);
    }
  }

  /*
   * Permanent occupations are played one at a time, in the player's chosen order, at the start of
   * round 1: each is brought into play (visible) and its immediate (onBuy) effect resolves as it is
   * played — order can matter (e.g. one occupation gives food another then spends, like Confidant).
   * They sit unowned in the 'campaignPermanent' location until played. Must run with turn >= 1 and
   * the round card revealed — running at turn 0 breaks turn-indexed card listeners (see stPreparation).
   */

  /* Permanents not yet played into play this game (still staged in 'campaignPermanent') */
  public static function getUnplayedPermanents()
  {
    $result = [];
    foreach (Globals::getPermanentOccupations() as $cId) {
      $cards = PlayerCards::getMany([$cId], false);
      $card = $cards->count() > 0 ? $cards->first() : null;
      if ($card != null && $card->getLocation() == 'campaignPermanent') {
        $result[] = ['id' => $cId, 'name' => $card->getName()];
      }
    }
    return $result;
  }

  public static function setupPermanentsFlow($callback)
  {
    $player = Players::getAll()->first();
    $childs = [];
    foreach (self::getUnplayedPermanents() as $perm) {
      $childs[] = [
        'action' => SPECIAL_EFFECT,
        'pId' => $player->getId(),
        'args' => [
          'cardId' => $perm['id'],
          'method' => 'campaignPlayPermanent',
        ],
      ];
    }

    Engine::setup(
      ['type' => NODE_PARALLEL, 'pId' => $player->getId(), 'forceChoice' => true, 'childs' => $childs],
      $callback
    );
    Engine::proceed();
  }

  public static function playPermanentEffect($card)
  {
    $player = Players::getAll()->first();
    $card->giveToPlayer($player->getId());
    Notifications::campaignPlayPermanent($player, $card);

    $flow = $card->getOnBuyFlow($player);
    // A permanent is played for free at the start of a game, where the player may not have the
    // resources its immediate effect needs (e.g. Confidant placing food with no food in supply).
    // If the effect requires payment, make it skippable so unaffordable, non-optional choices
    // can't leave the player stuck with no Pass.
    if ($flow != null && self::flowRequiresPayment($flow)) {
      $flow['optional'] = true;
    }
    if ($flow != null) {
      Engine::insertAsChild($flow);
    }

    Actions::get(OCCUPATION)->checkAfterListeners($player, ['cardId' => $card->getId()]);
  }

  /* Whether an onBuy flow forces a payment anywhere (so it can be made skippable for permanents) */
  private static function flowRequiresPayment($node)
  {
    // An optional subtree always offers a Pass, so it can never leave the player stuck
    if ($node['optional'] ?? false) {
      return false;
    }
    // IMPROVEMENT counts: building one pays its cost inside the purchase flow, not via a PAY
    // node visible here (e.g. Basket Weaver's forced Basketmaker's Workshop build, bug 232756)
    if (in_array($node['action'] ?? null, [PAY, IMPROVEMENT])) {
      return true;
    }
    // Build actions also pay inside their own flow (e.g. Prophet's renovation), unless the
    // card overrides the cost to nothing (Established Person's free renovation stays mandatory)
    if (in_array($node['action'] ?? null, [RENOVATION, FENCING, STABLES, CONSTRUCT])) {
      return !self::isFreeCostOverride($node);
    }
    foreach ($node['childs'] ?? [] as $child) {
      if (self::flowRequiresPayment($child)) {
        return true;
      }
    }
    return false;
  }

  private static function isFreeCostOverride($node)
  {
    $costs = $node['args']['costs'] ?? null;
    if ($costs == null) {
      return false;
    }
    foreach (array_merge($costs['fees'] ?? [], $costs['trades'] ?? []) as $cost) {
      if (!empty($cost)) {
        return false;
      }
    }
    return true;
  }

  public static function getUiData()
  {
    if (!self::isActive()) {
      return null;
    }
    // Only what the client renders: the goal track (goals + current goal) and the score history
    return [
      'goal' => self::getGoal(),
      'goals' => self::GOALS,
      'results' => Globals::getCampaignResults(),
    ];
  }

  /* Food granted at the start of the current game (from the previous game's overshoot) */
  public static function lastBonusFood()
  {
    $last = self::lastResult();
    if ($last == null) {
      return 0;
    }
    return floor(max(0, $last['score'] - $last['goal']) / 2);
  }
}
