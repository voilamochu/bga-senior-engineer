<?php

namespace AGR\Actions;

use AGR\Core\Engine;
use AGR\Core\Notifications;
use AGR\Helpers\Utils;
use AGR\Managers\PlayerCards;
use AGR\Managers\Players;

class Pay extends \AGR\Models\Action
{
  public function getState()
  {
    return ST_PAY;
  }

  public function getDescription($ignoreResources = false)
  {
    $args = $this->argsPay(null, true);
    $combinations = $args['combinations'];

    $harvestFlag = $this->getCtxArgs()['harvest'] ?? false;
    if ($this->isHarvest() && $harvestFlag) {
      return $this->getHarvestDescription();
    }

    // one combination => automatic allocation
    if (count($args['combinations']) == 1) {
      $cost = reset($args['combinations']);
      return [
        'log' => clienttranslate('Pay ${resources_desc}'),
        'args' => [
          'resources_desc' => Utils::resourcesToStr($cost),
        ],
      ];
    } else {
      return clienttranslate('Pay cost');
    }
  }

  public function getPlayer()
  {
    $args = $this->getCtxArgs();
    $pId = $args['pId'] ?? Players::getActiveId();
    return Players::get($pId);
  }

  public function isDoable($player, $ignoreResources = false)
  {
    $args = $this->argsPay($player);
    $harvestFlag = $this->getCtxArgs()['harvest'] ?? false;
    return $ignoreResources || ($this->isHarvest() && $harvestFlag) || !empty($args['combinations']);
  }

  public function isAutomatic($player = null)
  {
    $args = $this->argsPay();
    return count($args['combinations']) <= 1;
  }

  /**
   * Allow for args as $ctx instead of a tree node => useful for harvest
   */
  public function getCtxArgs()
  {
    return $this->ctx == null ? null : (is_array($this->ctx) ? $this->ctx : $this->ctx->getArgs());
  }

  public function argsPay($player = null, $ignoreResources = false)
  {
    $player = $player ?? Players::getActive();
    $args = $this->getCtxArgs();
    $nb = $args['nb'] ?? 0;
    $allCombinations = self::computeAllBuyableCombinations($player, $args['costs'], $nb, $ignoreResources, true);
    $combinations = self::keepOnlyOptimals(self::computeAllBuyableCombinations($player, $args['costs'], $nb, $ignoreResources));

    // Remove NB and fetch card names for UI
    $cardNames = [];
    foreach ($combinations as &$combination) {
      unset($combination['nb']);
      if (isset($combination['card'])) {
        $cardNames[$combination['card']] = PlayerCards::get($combination['card'])->getName();
      }

      foreach ($combination['sources'] ?? [] as $cardId) {
      // Hide the "(option k)" suffix in the top banner, looked weird for Resource Hoarder
      $cardNames[$cardId] = PlayerCards::get($cardId)->getName();
    }
    }
    unset($combination);

    foreach ($allCombinations as &$combination) {
      unset($combination['nb']);
      if (isset($combination['card'])) {
        $cardNames[$combination['card']] = PlayerCards::get($combination['card'])->getName();
      }

      foreach ($combination['sources'] ?? [] as $cardId) {
        $cardNames[$cardId] = PlayerCards::get($cardId)->getName();
      }
    }
    unset($combination);

    return [
      'i18n' => ['source'],
      'nb' => $nb,
      'source' => $args['source'] ?? '',
      'cId' => $args['cId'] ?? null,
      'sourceInfo' => $args['sourceInfo'] ?? [],
      'combinations' => $combinations,
      'allCombinations' => $allCombinations,
      'cardNames' => $cardNames,
      'descSuffix' => count($combinations) > 1 ? '' : 'auto',
    ];
  }

  public function stPay()
  {
    $args = $this->argsPay();
    $player = $this->getPlayer();
    $harvestFlag = $this->getCtxArgs()['harvest'] ?? false;

    // one combination => automatic allocation
    if (count($args['combinations']) == 1) {
      $cost = reset($args['combinations']);
      $this->actPay($cost, true);
    } elseif (empty($args['combinations'])) {
      // if we are in harvest we process harvest pay
      if ($this->isHarvest() && $harvestFlag) {
        $this->actPayHarvest();
      } else {
        throw new \BgaVisibleSystemException("No option to pay");
      }
    }
  }

  public function actPay($cost, $isAuto = false)
  {
    // Sanity checks
    self::checkAction('actPay', $isAuto);
    $args = $this->argsPay();
    if (!in_array($cost, $args['combinations'])) {
      throw new \BgaVisibleSystemException('Cost not authorized');
    }

    $this->updateSourceCardStatsFromCost($cost, $args['allCombinations'] ?? $args['combinations']);

    $player = Players::getActive();
    $paying = [];
    if (isset($cost['card'])) {
      // Paying by returning a card
      $card = PlayerCards::get($cost['card']);
      $card->resetStats();
      if ($cost['card'] == 'A60_OrientalFireplace') {
        PlayerCards::moveToDiscardpile($cost['card']);
      } else {
        $card->returnToBoard();
      }
      Notifications::payWithCard($player, $card, $args['source']);
      // A41_VegetableSlicer
      if (
        $player->hasPlayedCard('A41_VegetableSlicer') && $args['source'] == 'Cooking Hearth' &&
        ($cost['card'] == 'Major_Fireplace1' || $cost['card'] == 'Major_Fireplace2' || $cost['card'] == 'A60_OrientalFireplace')
      ) {
        $flow = PlayerCards::get('A41_VegetableSlicer')->gainNode([WOOD => 2, VEGETABLE => 1]);
        Engine::insertAsChild($flow);
      }

      // Specialcase D60_LargePottery
      // Return Pottery + pay resources
      if ($cost['card'] == 'Major_Pottery') {
        $deleted = [];
        foreach ($cost as $resource => $amount) {
          if ($resource == 'sources' || $resource == 'card' || $resource == 'bonusChoiceIndex') {
            continue;
          }
          $deleted = array_merge($deleted, $player->useResource($resource, $amount));
        }
        if (!empty($deleted)) {
          Notifications::payResources(
            $player,
            $deleted,
            $args['source'],
            $cost['sources'] ?? [],
            $args['cardNames'],
            $args['sourceInfo'] ?? []
          );
        }
      }
      $paying = ['card' => $cost['card']];
    } elseif (isset($this->getCtxArgs()['to'])) {
      // Payment to a player
      $moved = [];
      foreach ($cost as $resource => $amount) {
        if ($resource == 'sources' || $resource == 'bonusChoiceIndex') {
          continue;
        }
        $moved = array_merge($moved, $player->payResourceTo($this->getCtxArgs()['to'], $resource, $amount));
        if (!empty($moved) && !is_null($args['cId'])) {
          $card = PlayerCards::get($args['cId']);
          $statKey = $card->getPId() == $this->getCtxArgs()['to'] ? 'receivedPayment' : 'paidToOthers';
          $cardStats = $card->incStats($statKey, $resource, $amount);
        }
      }
      if (!empty($moved)) {
        Notifications::payResourcesTo(
          $player,
          $moved,
          $args['source'],
          $cost['sources'] ?? [],
          $args['cardNames'],
          Players::get($this->getCtxArgs()['to'])
        );
      }

      // Recipient needs AfterReceive reaction (for E103_Wolf etc)

      $event = [
        'pId' => $this->getCtxArgs()['to'],
        'type' => 'action',
        'action' => 'Receive',
        'method' => 'AfterReceive',
        'meeples' => $moved,
        'source' => $args['source'],
        'from' => $player->getId(),
        'cardNames' => $args['cardNames'],
        'sourceInfo' => $args['sourceInfo'],
      ];
      $reaction = PlayerCards::getReaction($event);
      if (!is_null($reaction)) {
        Engine::insertAsChild($reaction);
      }

      $paying = $moved;
    } else {
      // "Normal" payment with ressources
      // Delete meeples and notify
      $preferIds = $this->getCtxArgs()['meeples'] ?? null;
      $deleted = [];
      foreach ($cost as $resource => $amount) {
        if ($resource == 'sources' || $resource == 'bonusChoiceIndex') {
          continue;
        }
        $deleted = array_merge($deleted, $player->useResource($resource, $amount, $preferIds));
      }
      if (!empty($deleted)) {
        Notifications::payResources(
          $player,
          $deleted,
          $args['source'],
          $cost['sources'] ?? [],
          $args['cardNames'],
          $args['sourceInfo'] ?? []
        );
      }
      $paying = $deleted;
    }

    Notifications::updateDropZones($player);
    $player->forceReorganizeIfNeeded();
    $this->checkAfterListeners($player, [
      'cost' => $cost,
      'paying' => $paying,
      'source' => $args['source'],
      'cardNames' => $args['cardNames'],
      'sourceInfo' => $args['sourceInfo']
    ]);
    // Resolve the node
    $this->resolveAction(['cost' => $cost]);
  }

  protected function updateSourceCardStatsFromCost($cost, $combinations)
  {
    $sources = $cost['sources'] ?? [];
    if (isset($cost['card']) || empty($sources)) {
      return;
    }

    foreach ($sources as $cardId) {
      $reference = self::findReferenceCombination($cost, $combinations, $cardId);
      if (is_null($reference)) {
        continue;
      }

      $delta = self::computeCostDelta($cost, $reference);
      foreach ($delta['saved'] as $resource => $amount) {
        PlayerCards::get($cardId)->incStats('saved', $resource, $amount);
      }
      foreach ($delta['paid'] as $resource => $amount) {
        PlayerCards::get($cardId)->incStats('paid', $resource, $amount);
      }
    }
  }

  /***************************************
   ***************************************
   ************** HARVEST  ***************
   ***************************************
   **************************************/
  public function getHarvestDescription()
  {
    $player = Players::getActive();
    // if (!isset($this->getCtxArgs()['costs']['fees'])) {
    //   throw new \feException(print_r(\debug_print_backtrace()));
    // }
    $cost = $this->getCtxArgs()['costs']['fees'][0];
    $begging = 0;
    foreach ($cost as $resource => $amount) {
      if (in_array($resource, ['nb', 'sources', 'sourcesDesc'])) {
        continue;
      }
      $reserve = $player->countReserveResource($resource);
      if ($reserve < $amount) {
        $cost[$resource] = $reserve;
        $begging += $amount - $reserve;
      }
    }

    $desc = [
      'log' => clienttranslate('Pay ${resources_desc} to feed your family'),
      'args' => [
        'resources_desc' => Utils::resourcesToStr($cost),
      ],
    ];
    if ($begging > 0) {
      $desc['log'] = clienttranslate('Pay ${resources_desc} and take ${n} beggar cards to feed your family');
      $desc['args']['n'] = $begging;
    }

    return $desc;
  }

  public function actPayHarvest()
  {
    // Sanity checks
    self::checkAction('actPay', true);
    $cost = $this->getCtxArgs()['costs']['fees'][0];

    $player = Players::getActive();
    $deleted = [];
    $begging = 0;
    foreach ($cost as $resource => $amount) {
      if (in_array($resource, ['nb', 'sources', 'sourcesDesc'])) {
        continue;
      }
      $reserve = $player->countReserveResource($resource);
      if ($reserve < $amount) {
        $begging += $amount - $reserve;
        $amount = $reserve;
      }

      $deleted = array_merge($deleted, $player->useResource($resource, $amount));
    }

    Notifications::payResources($player, $deleted, clienttranslate('Harvest'));
    if ($begging != 0) {
      $created = $player->createResourceInReserve(BEGGING, $begging);
      Notifications::begging($player, $created);
    }

    // Resolve the node
    $this->resolveAction($cost);
  }

  /***************************************
   ***************************************
   ********* STATIC COMPUTATIONS *********
   ***************************************
   **************************************/
  /*
   * FORMAT DOCUMENTATION
   *
   * $costs : [
   *    'fee' => [ 'resourceType' => amount]  -> this need to be paid no matter how many units we choose
   *    'fees' => [ fee1, fee2, ...], -> you need to pay at least one of the fees
   *    'trades' => [
   *        [
   *          'max' => maxAmount of time you can use this cost to buy units
   *          'nb' => amount of units we obtain by paying this
   *          'sources' => array of card ids implied by that cost
   *          'resourceType1' => amount,
   *           ....
   *        ]
   *    ]
   *    'cards' => [
   *        'type' => Type of card that can be exchanged
   *        'list' => authorized card for the exchange
   *    ],
   *    'bonuses' => [ -> list of cost reducers applied once to the TOTAL
   *        [
   *          'optional' => true/false,
   *          'resourceType' => amount,
   *          'sources' => array of card ids implied by that bonuses
   *          ...
   *        ]
   *     ],
   * ];
   *
   * $combination : [
   *   'nb' => amout of units we can obtain with this combination
   *   'resourceType1' => amount,
   *    ....
   * ]
   */
  public static function canPayFee($player, $costs)
  {
    return self::canBuy($player, $costs, 0);
  }

  public static function canBuy($player, $costs, $n = 1)
  {
    // Handle major improvements that can be bought with another card
    if (isset($costs['cards'])) {
      $playerCards = $player->getCards($costs['cards']['type'], true)->getIds();
      foreach ($costs['cards']['list'] as $cardId) {
        if (in_array($cardId, $playerCards)) {
          return true;
        }
      }
    }

    // Pass $n as target so the combination search prunes anything with nb > $n,
    // which is enough to answer "can the player afford $n of this?" without
    // computing the full 2^|trades| powerset.
    $combinations = self::computeAllBuyableCombinations($player, $costs, $n);
    return \array_reduce(
      $combinations,
      function ($acc, $combination) use ($n) {
        return $acc || ($combination['nb'] ?? -1) == $n;
      },
      false
    );
  }

  public static function maxBuyableAmount($player, $costs)
  {
    // Compute all buyable combinations
    $combinations = self::computeAllBuyableCombinations($player, $costs);

    // Reduce to get the max
    return \array_reduce(
      $combinations,
      function ($acc, $combination) {
        return max($acc, $combination['nb'] ?? -1);
      },
      -1 // -1 instead of 0 to distinguish the case where player can afford the fee or not
    );
  }

  /**
   * Compute all the possibles combinations that a player can buy
   * @param $target (opt) : keep only combinations with nb == target
   */
  protected static function computeAllBuyableCombinations($player, $costs, $target = null, $ignoreResources = false, $keepReferenceCosts = false)
  {
    $reserve = $player->getExchangeResources();

    // Compute an artifical maxReserve to reduce computations by applying all potentially available bonuses
    $maxReserve = $reserve;
    $bonuses = $costs['bonuses'] ?? [];
    foreach ($bonuses as $bonus) {
      $choices = $bonus['choices'] ?? [$bonus]; // Handle bonuses with choices, eg "1 building resource of bonus"
      foreach ($choices as $choice) {
        foreach ($choice as $resource => $amount) {
          if ($resource != 'optional' && $resource != 'sources' && $amount < 0) {
            $maxReserve[$resource] += -$amount;
          }
        }
      }
    }

    // Start with an empty list of possible combinations
    $combinations = [];

    // First handle the fee
    $fees = $costs['fees'] ?? [$costs['fee'] ?? []];
    foreach ($fees as $baseCost) {
      $baseCost['nb'] = 0;
      self::pushAux($baseCost, $combinations, $maxReserve, false, $ignoreResources || $keepReferenceCosts, $keepReferenceCosts);
    }

    // Then loop over all possible trades
    $trades = $costs['trades'] ?? [];
    foreach ($trades as $trade) {
      $n = 1; // The number of time we want to use the trade
      $max = $trade['max'] ?? 15; // Just to ensure we won't have an infinite loop
      unset($trade['max']);
      $trade['nb'] = $trade['nb'] ?? 1;

      $previousCombinations = $combinations;
      do {
        $newCombinationPushed = false;
        foreach ($previousCombinations as $combination) {
          self::addCostAux($combination, $trade, $n);
          if ($target != null && $combination['nb'] > $target) {
            continue;
          }

          $pushed = self::pushAux($combination, $combinations, $maxReserve, false, $ignoreResources || $keepReferenceCosts, $keepReferenceCosts);
          $newCombinationPushed = $newCombinationPushed || $pushed;
        }
      } while ($newCombinationPushed && $n++ < $max);
    }

    // If target specified, keep only the combinations matching target
    if ($target != null) {
      Utils::filter($combinations, function ($combination) use ($target) {
        return ($combination['nb'] ?? -1) == $target;
      });
    }

    // Then handle bonuses
    foreach ($bonuses as $bonus) {
      $oldCombinations = $combinations;

      // Check if bonus is optional or not
      $optional = $bonus['optional'] ?? false;
      unset($bonus['optional']);
      $combinations = ($optional || $keepReferenceCosts) ? $combinations : [];

      foreach ($oldCombinations as $comb) {
        $choices = $bonus['choices'] ?? [$bonus]; // Handle bonuses with choices, eg "1 building resource of bonus"
        foreach ($choices as $i => $choice) {
          $combination = $comb;
          if (self::canApplyBonus($combination, $choice)) {
            self::addCostAux($combination, $choice);
            $sources = $choice['sources'] ?? [];
            if (count($sources) == 1 && $sources[0] == 'E123_ResourceHoarder') {
              // now we only add bonusChoiceIndex for E123
              if (!isset($combination['bonusChoiceIndex'])) {
                $combination['bonusChoiceIndex'] = [];
              }
              $combination['bonusChoiceIndex'][$sources[0]] = $i;
            }
          }
          self::pushAux($combination, $combinations, $maxReserve, false, $ignoreResources || $keepReferenceCosts, $keepReferenceCosts);
        }
      }
    }

    // Keep only combinations with >= 0 resource amount that fit inside RESERVE (and not maxReserve).
    $oldCombinations = $combinations;
    $combinations = [];
    foreach ($oldCombinations as $combination) {
      self::pushAux($combination, $combinations, $reserve, true, $ignoreResources || $keepReferenceCosts, $keepReferenceCosts);
    }

    // possibility to exchange one card for some major improvements
    if (isset($costs['cards']['list']) && !empty($costs['cards']['list'])) {
      $cards = $player->getCards(null, true)->getIds();
      foreach ($cards as $card) {
        // TODO: Change hardcoded CLAY + STONE to read values from $costs
        // Specialcase for D60_LargePottery
        if (in_array($card, $costs['cards']['list'])) {
          if ($card == 'Major_Pottery') {
            $combinations[] = ['nb' => 1, 'card' => $card];
          } else {
            $combinations[] = ['nb' => 1, 'card' => $card];
          }
        }
      }
    }

    return $combinations;
  }

  /**
   * Sum two assoc arrays of resources (multiplied by a coefficient)
   */
  protected static function addCostAux(&$combination, $unitCost, $times = 1)
  {
    $combination['sources'] = array_unique(array_merge($combination['sources'] ?? [], $unitCost['sources'] ?? []));

    $conditions = [];
    if (isset($unitCost['conditions'])) {
      $conditions = $unitCost['conditions'];
    }

    foreach ($unitCost as $resource => $cost) {
      if ($resource == 'sources' || $resource == 'conditions') {
        continue;
      }

      foreach ($conditions as $condition => $amt) {
        if ($condition == 'minNumRooms' && $combination['nb'] < $amt) {
          continue 2;
        }
      }

      $combination[$resource] = ($combination[$resource] ?? 0) + $times * $cost;
      if ($combination[$resource] == 0) {
        unset($combination[$resource]);
      }
    }
  }

  /**
   * Check if a bonus can be applied
   */
  protected static function canApplyBonus($combination, $bonus)
  {
    if (isset($bonus['conditions'])) {
      foreach ($bonus['conditions'] as $requirement => $amt) {
        if ($requirement == 'minNumRooms' && $combination['nb'] < $amt) {
          return false;
        }
      }
    }

    foreach ($bonus as $resource => $amount) {
      if (
        $resource != 'optional' &&
        $resource != 'sources' &&
        $resource != 'conditions' &&
        $amount < 0 &&
        ($combination[$resource] ?? 0) + $amount < 0
      ) {
        return false;
      }
    }

    return true;
  }

  /**
   * Auxiliary push function that test if the combination is not already present
   *  AND if the resources are compatible with reserve
   */
  protected static function pushAux(
    $combination,
    &$combinations,
    $reserve,
    $checkNonNegative = false,
    $ignoreResources = false,
    $keepSourceVariants = false
  ) {
    if ($combinations === false) {
      $combinations = [];
    }

    // First look for duplicates. Normally we collapse combinations with the same
    // resource signature regardless of sources — that keeps the UI-facing list small.
    // But when $keepSourceVariants is set (allCombinations path used for saved/paid
    // attribution), combinations that share resources but differ by which cards
    // supplied the discount must both be preserved — otherwise the delta algorithm
    // can't find an exclusion-based reference and over-credits whichever card won the
    // dedup race.
    //
    // Always strip nested arrays (sources/bonusChoiceIndex) before array_diff_assoc
    // to avoid "Array to string conversion" warnings, then check sources separately
    // when source-aware dedup is requested.
    foreach ($combinations as $c) {
      $cStripped = $c;
      $dStripped = $combination;
      unset($cStripped['sources']);
      unset($dStripped['sources']);
      unset($cStripped['bonusChoiceIndex']);
      unset($dStripped['bonusChoiceIndex']);
      $t1 = array_diff_assoc($cStripped, $dStripped);
      $t2 = array_diff_assoc($dStripped, $cStripped);
      if (empty($t1) && empty($t2)) {
        if ($keepSourceVariants) {
          $cSources = $c['sources'] ?? [];
          $dSources = $combination['sources'] ?? [];
          sort($cSources);
          sort($dSources);
          if ($cSources !== $dSources) {
            continue;
          }
        }
        return false;
      }
    }

    // Then check reserve
    if (!$ignoreResources) {
      foreach ($combination as $res => $n) {
        if ($res == 'nb' || $res == 'sources' || $res == 'bonusChoiceIndex') {
          continue;
        }

        $amountInReserve = $reserve[$res] ?? 0;
        if ($amountInReserve < $n) {
          return false;
        }

        if ($n < 0 && $checkNonNegative) {
          return false;
        }
      }
    }

    $combinations[] = $combination;
    return true;
  }

  /**
   * Auxiliary function that remove non optimal costs
   */
  protected static function keepOnlyOptimals($combinations)
  {
    $result = [];
    foreach ($combinations as $c) {
      if (self::isOptimal($c, $combinations)) {
        $result[] = $c;
        continue;
      }

      // Do not "optimize" cards
      if (isset($c['card'])) {
        $result[] = $c;
      }
    }
    return $result;
  }

  protected static function isOptimal($c, $combinations)
  {
    foreach ($combinations as $c2) {
      if (self::isWorseThan($c, $c2)) {
        return false;
      }
    }
    return true;
  }

  protected static function isWorseThan($c1, $c2)
  {
  // Resource Hoarder: never treat combinations that differ by this card
  // as strictly worse than each other.
    if (
      isset($c1['bonusChoiceIndex']['E123_ResourceHoarder']) ||
      isset($c2['bonusChoiceIndex']['E123_ResourceHoarder'])
    ) {
      return false;
    }

    if ($c1['nb'] != $c2['nb'] || $c1 == $c2) {
      return false;
    }

    foreach ($c1 as $res => $n) {
      if ($res == 'sources' || $res == 'bonusChoiceIndex') {
        continue;
      }
      if (isset($c2[$res]) && $c2[$res] > $n) {
        return false;
      }
    }

    foreach ($c2 as $res => $n) {
      if ($res == 'sources' || $res == 'bonusChoiceIndex') {
        continue;
      }
      if (!isset($c1[$res]) || $c1[$res] < $n) {
        return false;
      }
    }

    return true;
  }

  protected static function findReferenceCombination($cost, $combinations, $excludedSource = null)
  {
    if (isset($cost['card']) || empty($cost['sources'])) {
      return null;
    }

    $bestCandidate = null;
    $bestScore = null;
    $bestSharedSources = null;

    foreach ($combinations as $candidate) {
      if ($candidate == $cost || isset($candidate['card'])) {
        continue;
      }

      $candidateSources = $candidate['sources'] ?? [];
      if (!is_null($excludedSource) && in_array($excludedSource, $candidateSources)) {
        continue;
      }

      $score = self::getCombinationDistance($cost, $candidate);
      $sharedSources = count(array_intersect($cost['sources'], $candidateSources));
      if (
        is_null($bestScore) ||
        $score < $bestScore ||
        ($score == $bestScore && $sharedSources > $bestSharedSources)
      ) {
        $bestScore = $score;
        $bestSharedSources = $sharedSources;
        $bestCandidate = $candidate;
      }
    }

    return $bestCandidate;
  }

  protected static function getCombinationDistance($a, $b)
  {
    $score = 0;
    $keys = array_unique(array_merge(array_keys($a), array_keys($b)));
    foreach ($keys as $key) {
      if (in_array($key, ['sources', 'bonusChoiceIndex', 'card'])) {
        continue;
      }

      $score += abs(($a[$key] ?? 0) - ($b[$key] ?? 0));
    }

    return $score;
  }

  protected static function computeCostDelta($cost, $reference)
  {
    $saved = [];
    $paid = [];
    $keys = array_unique(array_merge(array_keys($cost), array_keys($reference)));

    foreach ($keys as $key) {
      if (in_array($key, ['sources', 'bonusChoiceIndex', 'card'])) {
        continue;
      }

      $delta = ($reference[$key] ?? 0) - ($cost[$key] ?? 0);
      if ($delta > 0) {
        $saved[$key] = $delta;
      } elseif ($delta < 0) {
        $paid[$key] = -$delta;
      }
    }

    return [
      'saved' => $saved,
      'paid' => $paid,
    ];
  }
}
