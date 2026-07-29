<?php
namespace AGR\Actions;

use AGR\Core\Engine;
use AGR\Core\Notifications;
use AGR\Helpers\UserException;
use AGR\Helpers\Utils;
use AGR\Managers\Fences;
use AGR\Managers\PlayerCards;
use AGR\Managers\Players;


class Fencing extends \AGR\Models\Action
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->description = clienttranslate('Build fences');
  }

  protected function isOpenAirFarmer()
  {
    return $this->getCtxArgs()['openAirFarmer'] ?? false;
  }

  protected function isMiniPasture()
  {
    return $this->getCtxArgs()['miniPasture'] ?? false;
  }

  protected function isCarpentersBench()
  {
    return $this->getCtxArgs()['CarpentersBench'] ?? false;
  }

  protected function getCarpentersBenchWoodBudget()
  {
    if (!$this->isCarpentersBench()) {
      return 0;
    }

    if (isset($this->getCtxArgs()['benchWood'])) {
      return max(0, $this->getCtxArgs()['benchWood']);
    }

    return max(0, ($this->getCtxArgs()['max'] ?? 1) - 1);
  }

  protected function isFieldFences()
  {
    return $this->getCtxArgs()['fieldFences'] ?? false;
  }

  protected function isBriarHedge($player)
  {
    return $player->hasPlayedCard('E16_BriarHedge');
  }

  protected function isMidnightFencer()
  {
    return $this->getCtxArgs()['midnightFencer'] ?? false;
  }

  protected function canUseWoodPalisades($player)
  {
    return
      !$this->isMidnightFencer() &&
      !($this->getCtxArgs()['noWoodPalisades'] ?? false) &&
      $player->hasPlayedCard('B30_WoodPalisades');
  }

  protected function getFencingMaxArg($player)
  {
    $defaultMaxArg = $this->canUseWoodPalisades($player) ? count($player->board()->getFencableZones()) : 15;
    return $this->getCtxArgs()['max'] ?? $defaultMaxArg;
  }

  protected function isBorderFenceSpace($fence)
  {
    return $fence['x'] == 0 || $fence['x'] == 10 || $fence['y'] == 0 || $fence['y'] == 6;
  }

  protected function fenceSpaceKey($fence)
  {
    return $fence['x'] . '_' . $fence['y'];
  }

  protected function countNormalFencesOnBoard($player)
  {
    $count = 0;
    foreach (Fences::getOnBoard($player->getId()) as $fence) {
      if (!Fences::isWoodPalisade($fence)) {
        $count++;
      }
    }
    return $count;
  }

  protected function estimatePaidNormalFencesForMax($player, $normalFenceCount, $freeFencePotential, $normalFencesOnBoard = null)
  {
    $paid = max(0, $normalFenceCount - $freeFencePotential);

    if ($player->hasPlayedCard('C88_CarpentersApprentice')) {
      $current = $normalFencesOnBoard ?? $this->countNormalFencesOnBoard($player);
      $start = $current + 1;
      $end = $current + $normalFenceCount;
      $apprenticeFree = max(0, min($end, 15) - max($start, 13) + 1);
      $paid = max(0, $paid - $apprenticeFree);
    }

    return $paid;
  }

  protected function canAffordPaidNormalsWithReservedWood(
    $player,
    $costsModified,
    $paidNormalCount,
    $reservedWood,
    &$combosCache,
    $benchWoodBudget = null
  ) {
    if (!array_key_exists($paidNormalCount, $combosCache)) {
      $payProbe = new Pay([
        'nb' => $paidNormalCount,
        'costs' => $costsModified,
      ]);
      $combosCache[$paidNormalCount] = $payProbe->argsPay($player)['combinations'] ?? [];
    }

    $woodInReserve = $player->countReserveResource(WOOD);
    foreach ($combosCache[$paidNormalCount] as $combination) {
      $woodUsedForNormals = $combination[WOOD] ?? 0;
      $reserveOk = $woodInReserve - $woodUsedForNormals >= $reservedWood;
      $benchOk = is_null($benchWoodBudget) || ($woodUsedForNormals + $reservedWood <= $benchWoodBudget);
      if ($reserveOk && $benchOk) {
        return true;
      }
    }

    return false;
  }

  private function getMidnightDonorCaps($player)
  {
    $caps = [];
    foreach (Players::getAll() as $p) {
      if ($p->getId() == $player->getId()) continue;
      $caps[$p->getId()] = min(2, Fences::countAvailable($p->getId()));
    }
    return $caps;
  }

  public function isDoable($player, $ignoreResources = false)
  {
    $nFences = self::getMaxBuildableFences($player, $ignoreResources);
    return $player->board()->canCreateNewPasture($nFences);
  }

  /**
   * Shared setup for all fence-max calculations.
   * Returns a context array used by computeMaxFencesGivenContext.
   */
  private function buildFencingContext($player, $ignoreResources = false)
  {
    $chooseFromE74InAdvance = 0;
    if ($player->hasPlayedCard('E74_AshTrees')) {
      $chooseFromE74InAdvance = PlayerCards::get('E74_AshTrees')->getExtraDatas('E74Used');
      if ($chooseFromE74InAdvance == 0) {
        $chooseFromE74InAdvance = Fences::countAvailable($player->getId(), 'E74_AshTrees');
      }
    }

    $available = Fences::countAvailable($player->getId()) + $chooseFromE74InAdvance;
    $constraints = $this->getConstraints();
    $costsModified = $this->getCosts($player, $constraints);
    $maxBuyable = $ignoreResources ? 15 : $player->maxBuyableAmount($costsModified);

    $fieldFreePotential = 0;
    if ($this->isFieldFences() && !$ignoreResources) {
      $fieldFreePotential = count($player->board()->getAvailableFieldFences());
      $maxBuyable += $fieldFreePotential;
    }
    // a fence cannot be FieldFence and BriarHedge at the same time
    $borderFreePotential = 0;
    $availableBorderFenceCount = count($player->board()->getAvailableBorderFences());
    if ($this->isBriarHedge($player) && !$ignoreResources) {
      $borderFreePotential = $availableBorderFenceCount;
      $maxBuyable += $borderFreePotential;
    }
    if ($player->hasPlayedCard('E74_AshTrees')) {
      $maxBuyable += $chooseFromE74InAdvance;
    }

    $normalFencesOnBoard = null;
    if (!$ignoreResources && $player->hasPlayedCard('C88_CarpentersApprentice')) {
      $normalFencesOnBoard = $this->countNormalFencesOnBoard($player);
      $neededToReach12 = max(0, 12 - $normalFencesOnBoard);
      if ($maxBuyable >= $neededToReach12) {
        $maxBuyable = max($maxBuyable, max(0, 15 - $normalFencesOnBoard));
      }
    }

    $maxArg = $this->getFencingMaxArg($player);
    $availableNormal = min($available, $maxArg);
    $maxNormal = min($availableNormal, $maxBuyable);

    return [
      'chooseFromE74InAdvance' => $chooseFromE74InAdvance,
      'costsModified' => $costsModified,
      'fieldFreePotential' => $fieldFreePotential,
      'borderFreePotential' => $borderFreePotential,
      'availableBorderFenceCount' => $availableBorderFenceCount,
      'normalFencesOnBoard' => $normalFencesOnBoard,
      'maxArg' => $maxArg,
      'availableNormal' => $availableNormal,
      'maxNormal' => $maxNormal,
    ];
  }

  /**
   * Core Wood Palisades-aware max computation for a fixed palisade count, given a pre-built context.
   * Shared by getMaxBuildableFences (loop) and getMaxBuildableFencesForWoodPalisadesCount.
   */
  private function computeMaxFencesGivenContext($player, $woodPalisadesCount, $ctx, $ignoreResources, $benchWoodBudget, &$combosCache)
  {
    $maxArg = $ctx['maxArg'];
    $maxPalisadesByBorder = min($ctx['availableBorderFenceCount'], $maxArg);

    if ($woodPalisadesCount > $maxPalisadesByBorder) {
      return 0;
    }

    $normalLimit = min($ctx['maxNormal'], $maxArg - $woodPalisadesCount);
    if ($normalLimit < 0) {
      return 0;
    }

    if ($ignoreResources) {
      return $normalLimit + $woodPalisadesCount;
    }

    $maxPalisadesByWood = intdiv($player->countReserveResource(WOOD), 2);
    if ($woodPalisadesCount > $maxPalisadesByWood) {
      return 0;
    }

    // BriarHedge fix: WP on border spaces don't get the free-fence benefit for normal fences.
    $effectiveBorderFree = max(0, $ctx['borderFreePotential'] - $woodPalisadesCount);
    $freeFencePotential = $ctx['fieldFreePotential'] + $effectiveBorderFree + $ctx['chooseFromE74InAdvance'];
    $reservedWoodForPalisades = 2 * $woodPalisadesCount;

    for ($n = $normalLimit; $n >= 0; $n--) {
      if ($this->isMiniPasture()) {
        if ($player->countReserveResource(WOOD) >= $reservedWoodForPalisades) {
          return $n + $woodPalisadesCount;
        }
        continue;
      }

      if ($this->isOpenAirFarmer()) {
        if ($player->countReserveResource(WOOD) >= 2 + $reservedWoodForPalisades) {
          return $n + $woodPalisadesCount;
        }
        continue;
      }

      $paidNormals = $this->estimatePaidNormalFencesForMax($player, $n, $freeFencePotential, $ctx['normalFencesOnBoard']);

      if ($this->canAffordPaidNormalsWithReservedWood(
        $player,
        $ctx['costsModified'],
        $paidNormals,
        $reservedWoodForPalisades,
        $combosCache,
        $benchWoodBudget
      )) {
        return $n + $woodPalisadesCount;
      }
    }

    return 0;
  }

  public function getMaxBuildableFences($player, $ignoreResources = false)
  {
    if ($this->isMidnightFencer()) {
      return $this->getCtxArgs()['max'] ?? 0;
    }

    $ctx = $this->buildFencingContext($player, $ignoreResources);

    if (!$this->canUseWoodPalisades($player)) {
      if ($this->isCarpentersBench()) {
        $combosCache = [];
        return $this->computeMaxFencesGivenContext(
          $player, 0, $ctx, $ignoreResources, $this->getCarpentersBenchWoodBudget(), $combosCache
        );
      }
      return min($ctx['maxArg'], $ctx['maxNormal']);
    }

    $maxPalisadesByBorder = min($ctx['availableBorderFenceCount'], $ctx['maxArg']);
    if ($ignoreResources) {
      return min($ctx['maxArg'], $ctx['availableNormal'] + $maxPalisadesByBorder);
    }

    $maxPalisadesByWood = intdiv($player->countReserveResource(WOOD), 2);
    $maxPalisades = min($maxPalisadesByBorder, $maxPalisadesByWood);
    $benchWoodBudget = $this->isCarpentersBench() ? $this->getCarpentersBenchWoodBudget() : null;
    $combosCache = [];

    $best = 0;
    for ($p = 0; $p <= $maxPalisades; $p++) {
      $best = max($best, $this->computeMaxFencesGivenContext($player, $p, $ctx, false, $benchWoodBudget, $combosCache));
    }

    return min($ctx['maxArg'], $best);
  }

  protected function getMaxBuildableFencesForWoodPalisadesCount($player, $woodPalisadesCount, $ignoreResources = false)
  {
    $woodPalisadesCount = max(0, $woodPalisadesCount);

    if ($this->isMidnightFencer()) {
      return $woodPalisadesCount == 0 ? ($this->getCtxArgs()['max'] ?? 0) : 0;
    }

    $ctx = $this->buildFencingContext($player, $ignoreResources);

    if (!$this->canUseWoodPalisades($player)) {
      if ($woodPalisadesCount > 0) {
        return 0;
      }
      if ($this->isCarpentersBench()) {
        $combosCache = [];
        return $this->computeMaxFencesGivenContext(
          $player, 0, $ctx, $ignoreResources, $this->getCarpentersBenchWoodBudget(), $combosCache
        );
      }
      return min($ctx['maxArg'], $ctx['maxNormal']);
    }

    $benchWoodBudget = $this->isCarpentersBench() ? $this->getCarpentersBenchWoodBudget() : null;
    $combosCache = [];

    return $this->computeMaxFencesGivenContext($player, $woodPalisadesCount, $ctx, $ignoreResources, $benchWoodBudget, $combosCache);
  }

  public function getState()
  {
    return ST_FENCING;
  }

  public function getConstraints()
  {
    $constraints = [];
    if ($this->isMiniPasture()) {
      $constraints[] = 'B2_MiniPasture';
    }
    if ($this->isCarpentersBench()) {
      $constraints[] = 'B15_CarpentersBench';
    }
    if ($this->isOpenAirFarmer()) {
      $constraints[] = 'B149_OpenAirFarmer';
    }
    return $constraints;
  }

  function argsFencing()
  {
    $player = Players::getActive();
    $inReserve = Fences::countAvailable($player->getId());
    $max = self::getMaxBuildableFences($player);
    $descSuffix = $this->isMiniPasture() ? 'minipasture' :
      ($this->isFieldFences() ? 'fieldfences' :
        ($this->isCarpentersBench() ? 'CarpentersBench' :
          ($this->isOpenAirFarmer() ? 'openAirFarmer' :
            ($max == $inReserve ? 'nomore' : '')
          )
        )
      );
    $constraints = $this->getConstraints();
    $donorCaps = null;
      if ($this->isMidnightFencer()) {
        $donorCaps = $this->getMidnightDonorCaps($player);
      }
    $woodPalisadesOptions = [];
    if ($this->canUseWoodPalisades($player)) {
      $maxArg = $this->getFencingMaxArg($player);
      $maxByBorder = min(count($player->board()->getAvailableBorderFences()), $maxArg);
      $maxByWood = intdiv($player->countReserveResource(WOOD), 2);
      $maxPalisades = min($maxByBorder, $maxByWood);
      for ($count = 0; $count <= $maxPalisades; $count++) {
        $woodPalisadesOptions[$count] = $this->getMaxBuildableFencesForWoodPalisadesCount($player, $count);
      }
    }

    return [
      'costs' => self::getCosts($player, $constraints),
      'max' => $max,
      'zones' => $player->board()->getFencableZones(),
      'miniPasture' => $this->isMiniPasture(),
      'CarpentersBench' => $this->isCarpentersBench(),
      'fieldFences' => $this->isFieldFences(),
      'openAirFarmer' => $this->isOpenAirFarmer(),
      'BriarHedge' => $this->isBriarHedge($player),
      'woodPalisades' => $this->canUseWoodPalisades($player),
      'woodPalisadesOptions' => $woodPalisadesOptions,
      'descSuffix' => $descSuffix,
      'midnightFencer' => $this->isMidnightFencer(),
      'donorCaps' => $donorCaps,
    ];
  }

  function getCosts($player, $constraints)
  {
    $costs = $this->getCtxArgs()['costs'] ?? Utils::formatCost([WOOD => 1]);
    $costs['constraints'] = $constraints;
    $this->checkCostModifiers($costs, $player);
    return $costs;
  }

  /**
   * Create fences
   * @param $fences associative array x,y
   **/
  function actFencing($fences, $donors = null, $woodPalisadesCount = 0)
  {
    self::checkAction('actFence');
    $player = Players::getActive();
    $woodPalisadesCount = max(0, (int) $woodPalisadesCount);

    if ($this->isMidnightFencer()) {
      if ($woodPalisadesCount > 0) {
        throw new \BgaUserException(totranslate('Wood Palisades cannot be used with Midnight Fencer.'));
      }
      $this->actMidnightFencerInternal($player, $fences, $donors);
      return;
    }

    $normalFencesBefore = $this->countNormalFencesOnBoard($player);
    $fieldFences = $player->board()->getAvailableFieldFences();
    $borderFences = $player->board()->getAvailableBorderFences();
    $min = $this->getCtxArgs()['min'] ?? 0;

    $unusedSpaces = $player->board()->getFreeZones();

    // Sanity checks on number of fences
    if (count($fences) < $min) {
      throw new \BgaVisibleSystemException("You need to build at least " . strval($min) . " fences");
    }

    if ($woodPalisadesCount > 0 && !$this->canUseWoodPalisades($player)) {
      throw new \BgaUserException(totranslate('You cannot use Wood Palisades right now.'));
    }

    $maxForRequestedPalisades = $this->getMaxBuildableFencesForWoodPalisadesCount($player, $woodPalisadesCount);
    if (count($fences) > $maxForRequestedPalisades) {
      throw new \BgaVisibleSystemException('You can\'t build that many fences with your resources');
    }

    $woodPalisadeKeys = [];
    if ($woodPalisadesCount > 0) {
      $woodPalisadeCandidates = [];
      foreach ($fences as $fence) {
        if ($this->isBorderFenceSpace($fence)) {
          $woodPalisadeCandidates[$this->fenceSpaceKey($fence)] = $fence;
        }
      }
      $woodPalisadeCandidates = array_values($woodPalisadeCandidates);
      usort($woodPalisadeCandidates, function ($a, $b) {
        return $a['x'] == $b['x'] ? $a['y'] - $b['y'] : $a['x'] - $b['x'];
      });

      if ($woodPalisadesCount > count($woodPalisadeCandidates)) {
        throw new \BgaUserException(totranslate('You selected too many Wood Palisades.'));
      }

      for ($i = 0; $i < $woodPalisadesCount; $i++) {
        $woodPalisadeKeys[$this->fenceSpaceKey($woodPalisadeCandidates[$i])] = true;
      }
    }

    $normalFenceSpaces = count($fences) - count($woodPalisadeKeys);
    $normalFenceSupply = Fences::countAvailable($player->getId());
    if ($player->hasPlayedCard('E74_AshTrees')) {
      $normalFenceSupply += Fences::countAvailable($player->getId(), 'E74_AshTrees');
    }
    if ($normalFenceSpaces > $normalFenceSupply) {
      throw new \BgaUserException(
        totranslate('You do not have enough fence pieces for the non-palisade fences you selected.')
      );
    }

    // Add them to board
    $playerBoard = $player->board();
    $oldPastures = $playerBoard->getPastures();

    $buildFromE74 = 0;
    $builtWoodPalisades = 0;
    $chooseFromE74InAdvance = 0;
    if ($player->hasPlayedCard('E74_AshTrees')) {
      $chooseFromE74InAdvance = PlayerCards::get('E74_AshTrees')->getExtraDatas('E74Used');
    }
    foreach ($fences as &$fence) {
      if (isset($woodPalisadeKeys[$this->fenceSpaceKey($fence)])) {
        $playerBoard->addWoodPalisadeFence($fence);
        $builtWoodPalisades++;
        continue;
      }

      if ($chooseFromE74InAdvance > 0) {
        $playerBoard->addFence($fence, 'E74_AshTrees');
        $chooseFromE74InAdvance--;
        $buildFromE74++;
      } else {
        if (Fences::countAvailable($player->getId()) > 0) {
          $playerBoard->addFence($fence);
        } else {
          $playerBoard->addFence($fence, 'E74_AshTrees');
          $buildFromE74++;
        }
      }
    }
    if ($player->hasPlayedCard('E74_AshTrees')) {
      PlayerCards::get('E74_AshTrees')->setExtraDatas('E74Used', 0);
    }

    // Then run sanity checks with $raiseException = true to auto rollback in case of invalid choice
    $playerBoard->areFencesValid(true);
    $playerBoard->arePasturesValid(true);
    $playerBoard->areCropStacksValid(true);

    $pastures = $playerBoard->getPastures(true);
    $newPastures = [
      // Useful for ShepherdsCrook
      'all' => Utils::diffPastures($pastures, $oldPastures, false),
      'new' => Utils::diffPastures($pastures, $oldPastures, true),
    ];

    if ($this->isMiniPasture()) {
      // throw new \feException(print_r($newPastures));
      $miniPastureError = clienttranslate('You must fence exactly one farmyard space');
      if (count($newPastures['new']) != 1 || count($newPastures['all']) != 1) {
        throw new UserException($miniPastureError);
      }

      foreach ($newPastures['new'] as $pasture) {
        if (isset($pasture['nodes']) && count($pasture['nodes']) != 1) {
          throw new \BgaUserException($miniPastureError);
        }
      }
    }

    if ($this->isCarpentersBench()) {
      $CarpentersBenchError = clienttranslate('You must fence exactly one pasture');
      if (count($newPastures['new']) != 1 || count($newPastures['all']) != 1) {
        throw new UserException($CarpentersBenchError);
      }
    }

    if ($this->isOpenAirFarmer()) {
      $openAirFarmerError = clienttranslate('You must fence exactly two farmyard spaces');
      if (count($newPastures['new']) != 1 || count($newPastures['all']) != 1) {
        throw new UserException($openAirFarmerError);
      }

      foreach ($newPastures['new'] as $pasture) {
        if (isset($pasture['nodes']) && count($pasture['nodes']) != 2) {
          throw new \BgaUserException($openAirFarmerError);
        }
      }
    }

    if (!$player->board()->areAnimalsValid(false)) {
      Engine::insertAsChild([
        'action' => REORGANIZE,
      ]);
    }

    $countFieldFences = 0;
    if ($this->isFieldFences()) {
      foreach ($fences as $fen) {
        if (isset($woodPalisadeKeys[$this->fenceSpaceKey($fen)])) {
          continue;
        }
        if (in_array(['x' => $fen['x'], 'y' => $fen['y']], $fieldFences)) {
          $countFieldFences++;
        }
      }
    }

    $countBorderFences = 0;
    if ($this->isBriarHedge($player)) {
      foreach ($fences as $fen) {
        if (isset($woodPalisadeKeys[$this->fenceSpaceKey($fen)])) {
          continue;
        }
        if (in_array(['x' => $fen['x'], 'y' => $fen['y']], $borderFences)) {
          $countBorderFences++;
        }
      }
    }

    $normalFenceCount = count($fences) - $builtWoodPalisades;
    $freeFenceCount = $countFieldFences + $countBorderFences + $buildFromE74;
    $finalCount = max(0, $normalFenceCount - $freeFenceCount);

    $apprenticeFree = 0;
    if ($player->hasPlayedCard('C88_CarpentersApprentice')) {
      $buildingNow = $normalFenceCount;
      $start = $normalFencesBefore + 1;
      $end = $normalFencesBefore + $buildingNow;

      // intersection of [start..end] with [13..15]
      $apprenticeFree = max(0, min($end, 15) - max($start, 13) + 1);
    }

    $finalCountAfterApprentice = max(0, $finalCount - $apprenticeFree);

    $constraints = $this->getConstraints();
    $costsModified = $this->getCosts($player, $constraints);

    if ($this->isFieldFences() && $this->isBriarHedge($player)) {
      if (!$player->canBuy($costsModified, $finalCountAfterApprentice)) {
        throw new \BgaUserException(
          totranslate('You must build fences on the edges or adjacent to fields if you want to build that many fences')
        );
      }
    } else if ($this->isFieldFences()) {
      if (!$player->canBuy($costsModified, $finalCountAfterApprentice)) {
        throw new \BgaUserException(
          totranslate('You must build more fences adjacent to fields if you want to build that many fences')
        );
      }
    } else if ($this->isBriarHedge($player)) {
      if (!$player->canBuy($costsModified, $finalCountAfterApprentice)) {
        throw new \BgaUserException(
          totranslate('You must build fences on the edges if you want to build that many fences')
        );
      }
    }

    $benchWoodBudget = $this->isCarpentersBench() ? $this->getCarpentersBenchWoodBudget() : null;
    if (!$this->isMiniPasture() && !$this->isOpenAirFarmer()) {
      $reservedWoodForPalisades = 2 * $builtWoodPalisades;
      if ($builtWoodPalisades > 0 || !is_null($benchWoodBudget)) {
        $combosCache = [];
        if (
          !$this->canAffordPaidNormalsWithReservedWood(
            $player,
            $costsModified,
            $finalCountAfterApprentice,
            $reservedWoodForPalisades,
            $combosCache,
            $benchWoodBudget
          )
        ) {
          if (!is_null($benchWoodBudget)) {
            throw new \BgaUserException(
              totranslate('With Carpenter\'s Bench, you can only spend the wood just taken from the accumulation space.')
            );
          }
          throw new \BgaUserException(totranslate('You do not have enough wood to place that many Wood Palisades.'));
        }
      }
    } else if ($builtWoodPalisades > 0) {
      $neededWoodForPalisades = 2 * $builtWoodPalisades;
      $needed = $neededWoodForPalisades + ($this->isOpenAirFarmer() ? 2 : 0);
      if ($player->countReserveResource(WOOD) < $needed) {
        throw new \BgaUserException(totranslate('You do not have enough wood to place that many Wood Palisades.'));
      }
    }

    $newUsedSpaces = count($unusedSpaces) - count($player->board()->getFreeZones());

    // Notify
    Notifications::constructFences($player, $fences);
    $player->forceReorganizeIfNeeded();
    $this->checkAfterListeners($player, [
      'fences' => $fences,
      'woodPalisades' => $builtWoodPalisades,
      'newPastures' => $newPastures,
      'newUsedSpaces' => $newUsedSpaces,
    ]);

    // Proceed
    if (!$this->isMiniPasture()) {
      if (!$this->isOpenAirFarmer()) {
        if ($finalCountAfterApprentice  > 0) {
          $player->pay($finalCountAfterApprentice, $costsModified, clienttranslate('Fencing'));
        }
      } else {
        $player->pay(2, Utils::formatCost([WOOD => 1]), clienttranslate('Fencing'));
      }
    }
    if ($builtWoodPalisades > 0) {
      $player->pay(
        0,
        Utils::formatFee([WOOD => 2 * $builtWoodPalisades]),
        PlayerCards::get('B30_WoodPalisades')->getName()
      );
    }

    // Stat attribution
    if ($countFieldFences > 0 && $player->hasPlayedCard('C16_FieldFences')) {
      PlayerCards::get('C16_FieldFences')->incStats('saved', WOOD, $countFieldFences);
    }
    if ($countBorderFences > 0 && $player->hasPlayedCard('E16_BriarHedge')) {
      PlayerCards::get('E16_BriarHedge')->incStats('saved', WOOD, $countBorderFences);
    }
    if ($buildFromE74 > 0 && $player->hasPlayedCard('E74_AshTrees')) {
      PlayerCards::get('E74_AshTrees')->incStats('saved', WOOD, $buildFromE74);
    }
    if ($apprenticeFree > 0 && $player->hasPlayedCard('C88_CarpentersApprentice')) {
      PlayerCards::get('C88_CarpentersApprentice')->incStats('saved', WOOD, $apprenticeFree);
    }
    $this->resolveAction([
      'fences' => $fences,
      'woodPalisades' => $builtWoodPalisades,
    ]);
  }

  private function actMidnightFencerInternal($player, $fences, $donors)
  {
    if ($donors === null) {
      throw new \BgaUserException('You must choose who to take the fences from');
    }

    $caps = $this->getMidnightDonorCaps($player);

    // Validate totals
    $total = count($fences);
    $sum = 0;

    foreach ($donors as $pid => $n) {
      if (!isset($caps[$pid])) {
        throw new \BgaUserException('Invalid donor');
      }
      if ($n < 0 || $n > $caps[$pid]) {
        throw new \BgaUserException('Too many fences taken from a player');
      }
      $sum += $n;
    }

    if ($sum != $total) {
      throw new \BgaUserException('You must allocate all fences to donors');
    }

    // Respect ctx max
    $max = $this->getCtxArgs()['max'] ?? 0;
    if ($total > $max) {
      throw new \BgaUserException('Too many fences');
    }

    $playerBoard = $player->board();
    $oldPastures = $playerBoard->getPastures();

    $unusedSpaces = $player->board()->getFreeZones();

    // Build a deterministic donor queue
    $queue = [];
    foreach ($donors as $pid => $n) {
      for ($i = 0; $i < $n; $i++) {
        $queue[] = $pid;
      }
    }

    foreach ($fences as &$fence) {
      $fromPid = array_shift($queue);
      $playerBoard->addFenceFromOpponentReserve($fence, $fromPid);
    }
    unset($fence);

    $playerBoard->areFencesValid(true);
    $playerBoard->arePasturesValid(true);
    $playerBoard->areCropStacksValid(true);

    $pastures = $playerBoard->getPastures(true);
    $newPastures = [
      'all' => Utils::diffPastures($pastures, $oldPastures, false),
      'new' => Utils::diffPastures($pastures, $oldPastures, true),
    ];

    if (!$player->board()->areAnimalsValid(false)) {
      Engine::insertAsChild(['action' => REORGANIZE]);
    }

    $newUsedSpaces = count($unusedSpaces) - count($player->board()->getFreeZones());

    Notifications::constructFences($player, $fences);
    $player->forceReorganizeIfNeeded();

    $this->checkAfterListeners($player, [
      'fences' => $fences,
      'newPastures' => $newPastures,
      'newUsedSpaces' => $newUsedSpaces,
    ]);

    $this->resolveAction(['fences' => $fences, 'donors' => $donors]);
  }

}
