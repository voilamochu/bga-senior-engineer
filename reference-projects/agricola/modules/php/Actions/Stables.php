<?php
namespace AGR\Actions;

use AGR\Managers\Players;
use AGR\Managers\PlayerCards;
use AGR\Core\Globals;
use AGR\Core\Notifications;
use AGR\Helpers\Utils;

class Stables extends \AGR\Models\Action
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->description = clienttranslate('Build stables');
  }

  public function getState()
  {
    return ST_STABLE;
  }

  public function getDescription($ignoreResources = false)
  {
    if ($this->isSettlementOnly()) {
      return '';
    }
    return parent::getDescription($ignoreResources);
  }

  function getCosts($player)
  {
    $costs = $this->getCtxArgs()['costs'] ?? Utils::formatCost([WOOD => 2]);
    $this->checkCostModifiers($costs, $player);
    return $costs;
  }

  // For Farm Hand. If Farm Hand stable possible, create an OR of (Farm Hand stable / Normal stables). Then pay at the end in a settlement-only node (because Feed Fence / Carpenter's Apprentice).
  private function isSettlementOnly()
  {
    $args = $this->getCtxArgs();
    return !empty($args['settlementOnly']);
  }

  private function isSelectionOnly()
  {
    $args = $this->getCtxArgs();
    return !empty($args['selectionOnly']);
  }

  private function getDeferredSettlementData($pId)
  {
    $player = Players::get($pId);
    if (!$player->hasPlayedCard('B85_FarmHand')) {
      return null;
    }

    $data = PlayerCards::get('B85_FarmHand')->getExtraDatas('farmHandDeferredStables');
    return is_array($data) ? $data : null;
  }

  private function setDeferredSettlementData($pId, $data)
  {
    $player = Players::get($pId);
    if (!$player->hasPlayedCard('B85_FarmHand')) {
      return;
    }

    PlayerCards::get('B85_FarmHand')->setExtraDatas('farmHandDeferredStables', $data);
  }

  // Needed for Carpenter's Apprentice to calculate a mixed payment menu
  private function buildCombinedStableCosts($baseCosts, $normalToPay, $discountedCount)
  {
    $costs = $baseCosts;

    if (!isset($costs['trades']) || !is_array($costs['trades'])) {
      return $costs;
    }

    $out = [];
    $seen = [];

    $pushUnique = function ($trade) use (&$out, &$seen) {
      $t = $trade;
      unset($t['sources']);
      $sig = json_encode($t);
      if (isset($seen[$sig])) {
        return;
      }
      $seen[$sig] = true;
      $out[] = $trade;
    };

    foreach ($costs['trades'] as $trade) {
      // Non-wood trades are kept exactly once (eg Feed Fence clay max=1)
      if (!isset($trade[WOOD])) {
        $pushUnique($trade);
        continue;
      }

      // Normal version (cap to normalToPay)
      $cap = $normalToPay;
      if (isset($trade['max'])) {
        $cap = min($cap, $trade['max']);
      }
      if ($cap > 0) {
        $t = $trade;
        $t['max'] = $cap;
        $pushUnique($t);
      }

      // Discounted version (wood - 1, cap to discountedCount)
      if ($discountedCount > 0) {
        $dt = $trade;

        $dt[WOOD] = max(0, $dt[WOOD] - 1);
        if (($dt[WOOD] ?? 0) === 0) {
          unset($dt[WOOD]);
        }

        $dCap = $discountedCount;
        if (isset($dt['max'])) {
          $dCap = min($dCap, $dt['max']);
        }
        if ($dCap > 0) {
          $dt['max'] = $dCap;
          $dt['sources'] = array_values(array_unique(array_merge($dt['sources'] ?? [], ['C88_CarpentersApprentice'])));
          $pushUnique($dt);
        }
      }
    }

    $costs['trades'] = $out;
    return $costs;
  }

  // 0/1 for unpaid Farm Hand stable in current action
  private function getUnpaidFarmHandCount($player)
  {
    $pId = $player->getId();
    if (!$player->hasPlayedCard('B85_FarmHand')) {
      return 0;
    }

    $farmHandCard = PlayerCards::get('B85_FarmHand');
    if ($farmHandCard->hasBuiltFarmHandStable() && !$farmHandCard->isFarmHandPaid() && \AGR\Managers\Stables::hasFarmHandStable($pId)) {
      return 1;
    }

    return 0;
  }

  // Stable baseline before this action started
  private function getStableCountBeforeThisAction($player, $normalBuiltInAction, $farmHandBuiltInAction)
  {
    $pId = $player->getId();

    $currentTotal = count($player->board()->getStables());
    if (\AGR\Managers\Stables::hasFarmHandStable($pId)) {
      $currentTotal += 1;
    }

    return max(0, $currentTotal - ($normalBuiltInAction + $farmHandBuiltInAction));
  }

  // Carpenter's Apprentice: stable #3 and #4 each cost 1 wood less
  private function countCarpenterDiscounts($before, $totalBuilt)
  {
    $discountedCount = 0;
    $after = $before + $totalBuilt;

    if ($before < 3 && $after >= 3) {
      $discountedCount++;
    }
    if ($before < 4 && $after >= 4) {
      $discountedCount++;
    }

    return $discountedCount;
  }

  // Canonical affordability engine for any plan (normal + Farm Hand)
  public function canAffordStablePlan($player, $normalCount, $farmHandCount)
  {
    $totalBuilt = $normalCount + $farmHandCount;
    if ($totalBuilt <= 0) {
      return true;
    }

    $discountedCount = 0;
    if ($player->hasPlayedCard('C88_CarpentersApprentice')) {
      $pId = $player->getId();
      $pending = $this->getDeferredSettlementData($pId);
      $pendingNormalCount = (is_array($pending) && is_array($pending['stables'] ?? null)) ? count($pending['stables']) : 0;
      $pendingFarmHandCount = $this->getUnpaidFarmHandCount($player);

      $before = $this->getStableCountBeforeThisAction($player, $pendingNormalCount, $pendingFarmHandCount);
      $discountedCount = $this->countCarpenterDiscounts($before, $totalBuilt);
    }

    $normalToPay = $totalBuilt - $discountedCount;
    $combinedCosts = $this->buildCombinedStableCosts($this->getCosts($player), $normalToPay, $discountedCount);
    return $player->canBuy($combinedCosts, $totalBuilt);
  }

  public function isAutomatic($player = null)
  {
    return $this->isSettlementOnly();
  }

  public function isDoable($player, $ignoreResources = false)
  {
    if ($this->isSettlementOnly()) {
      $pending = $this->getDeferredSettlementData($player->getId());
      $normalCount = count(($pending['stables'] ?? []));
      $farmHandCount = $this->getUnpaidFarmHandCount($player);

      if ($normalCount + $farmHandCount <= 0) {
        return false;
      }

      return $ignoreResources || $this->canAffordStablePlan($player, $normalCount, $farmHandCount);
    }

    $farmHandCount = $this->getUnpaidFarmHandCount($player);
    $hasAnyStableToProcess = $player->countStablesInReserve() > 0 || $farmHandCount > 0;
    if (!$hasAnyStableToProcess) {
      return false;
    }

    $maxBuildableNormal = $this->getMaxBuildableStables($player);

    // In selection-only mode (Farm Hand wrapper), this leaf only represents
    // selecting normal stables. If none are affordable, skip this leaf entirely
    // and let OR auto-pass into settlement-only payment.
    if ($this->isSelectionOnly()) {
      return $maxBuildableNormal > 0;
    }

    if ($ignoreResources) {
      return true;
    }

    return $maxBuildableNormal > 0 || ($farmHandCount > 0 && $this->canAffordStablePlan($player, 0, $farmHandCount));
  }

  public function getMaxBuildableStables($player)
  {
    $available = $player->countStablesInReserve();
    $farmHandCount = $this->getUnpaidFarmHandCount($player);

    if (isset($this->getCtxArgs()['max'])) {
      $available = min($available, $this->getCtxArgs()['max']);
    }

    $maxSelectable = 0;

    for ($n = 1; $n <= $available; $n++) {
      if ($this->canAffordStablePlan($player, $n, $farmHandCount)) {
        $maxSelectable = $n;
      }
    }

    return $maxSelectable;
  }

  public function argsStables()
  {
    $player = Players::getActive();
    $ctx = $this->getCtxArgs();

    if ($this->isSettlementOnly()) {
      return [
        'zones' => [],
        'min' => 0,
        'max' => 0,
        'farmHandStable' => $player->board()->getFarmHandTopLeft(),
      ];
    }

    $max = $this->getMaxBuildableStables($player);
    $inReserve = $player->countStablesInReserve();
    $farmHandCount = $this->getUnpaidFarmHandCount($player);
    $descSuffix = ($max == $inReserve ? 'nomore' : '');

    $zones = isset($ctx['zones']) ? $ctx['zones'] : $player->board()->getFreeZones(false);

    return [
      'zones' => $zones,
      'min' => $farmHandCount > 0 ? 0 : 1,
      'max' => $max,
      'descSuffix' => $descSuffix,
      'farmHandStable' => $player->board()->getFarmHandTopLeft(),
    ];
  }

  public function actStables($stables, $isAuto = false)
  {
    if (!($isAuto && $this->isSettlementOnly())) {
      self::checkAction('actStables', $isAuto);
    }

    $player = Players::getActive();
    $pId = $player->getId();

    if ($this->isSettlementOnly()) {
      $pending = $this->getDeferredSettlementData($pId);
      $deferredStables = ($pending !== null && is_array($pending['stables'] ?? null)) ? $pending['stables'] : [];
      $newUsedSpaces = $pending['newUsedSpaces'] ?? 0;

      $this->finalizeStablesBuild($player, $deferredStables, $newUsedSpaces);
      $this->setDeferredSettlementData($pId, null);
      return;
    }

    if ($stables === null) {
      $stables = [];
    }

    if (!is_array($stables)) {
      throw new \BgaVisibleSystemException('Invalid stables selection.');
    }

    $selectedCount = count($stables);

    if ($player->countStablesInReserve() < $selectedCount) {
      throw new \BgaUserException(
        sprintf(
          clienttranslate('You do not have enough stables in your reserve. Remaining stables: %s'),
          $player->countStablesInReserve()
        )
      );
    }

    $args = $this->argsStables();
    $min = $args['min'] ?? 1;
    if ($selectedCount < $min) {
      throw new \BgaUserException(clienttranslate('You must build at least 1 stable.'));
    }

    if ($args['max'] < $selectedCount) {
      throw new \feException('Too many stables to create');
    }

    $unusedSpaces = $player->board()->getFreeZones();

    foreach ($stables as &$stable) {
      $player->board()->addStable($stable);
    }

    if ($selectedCount > 0) {
      Notifications::stables($player, $stables);
    }

    $newUsedSpaces = count($unusedSpaces) - count($player->board()->getFreeZones());

    if ($this->isSelectionOnly()) {
      $this->setDeferredSettlementData($pId, [
        'stables' => $stables,
        'newUsedSpaces' => $newUsedSpaces,
      ]);

      $this->resolveAction([
        'stables' => $stables,
      ]);
      return;
    }

    $this->finalizeStablesBuild($player, $stables, $newUsedSpaces);
  }

  public function stStables()
  {
    if ($this->isSettlementOnly()) {
      $this->actStables(null, true);
    }
  }

  private function finalizeStablesBuild($player, $stables, $newUsedSpaces)
  {
    $pId = $player->getId();
    $farmHandCount = $this->getUnpaidFarmHandCount($player);
    $farmHandCard = $farmHandCount === 1 ? PlayerCards::get('B85_FarmHand') : null;

    $normalCount = count($stables);
    $totalBuilt = $normalCount + $farmHandCount;

    if ($totalBuilt <= 0) {
      throw new \BgaUserException(clienttranslate('You must build at least 1 stable.'));
    }

    // Farm Hand stable should count for numStablesBuiltThisTurn
    if ($farmHandCount === 1) {
      $turnId = Globals::getTurnId();
      $data = Globals::getStablesTurnId();

      if (!isset($data[$pId])) {
        $data[$pId] = [];
      }

      $data[$pId][] = $turnId;
      Globals::setStablesTurnId($data);
    }

    $this->checkAfterListeners($player, [
      'stables' => $stables,
      'newUsedSpaces' => $newUsedSpaces,
      'farmHandStableBuilt' => ($farmHandCount === 1),
    ]);

    $discountedCount = 0;
    if ($player->hasPlayedCard('C88_CarpentersApprentice')) {
      $before = $this->getStableCountBeforeThisAction($player, $normalCount, $farmHandCount);
      $discountedCount = $this->countCarpenterDiscounts($before, $totalBuilt);
    }

    $normalToPay = $totalBuilt - $discountedCount;
    $baseCosts = $this->getCosts($player);
    $combinedCosts = $this->buildCombinedStableCosts($baseCosts, $normalToPay, $discountedCount);
    $player->pay($totalBuilt, $combinedCosts, clienttranslate('Stables'), true, [
      'sourceAction' => 'Stables',
      'actionCardId' => $this->ctx != null ? $this->ctx->getCardId() : null,
    ]);

    if ($discountedCount > 0 && $player->hasPlayedCard('C88_CarpentersApprentice')) {
      PlayerCards::get('C88_CarpentersApprentice')->incStats('saved', WOOD, $discountedCount);
    }

    $cardId = $this->getCtxArgs()['cardId'] ?? ($this->ctx != null ? $this->ctx->getCardId() : null);
    if (!is_null($cardId) && $normalCount > 0) {
      $cards = PlayerCards::getMany([$cardId], false);
      if ($cards->count() === 1) {
        $card = $cards->first();
        if ($card->getPId() === $player->getId()) {
          $card->incStats('gain', STABLE, $normalCount);
        }
      }
    }

    if ($farmHandCount === 1 && $farmHandCard !== null) {
      $farmHandCard->setFarmHandPaid(true);
    }

    $checkpoint = $this->getCtxArgs()['checkpoint'] ?? false;
    $this->resolveAction([
      'stables' => $stables,
      'farmHandStableBuilt' => ($farmHandCount === 1),
    ], $checkpoint);
  }
}
