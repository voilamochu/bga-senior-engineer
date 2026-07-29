<?php

namespace ARK\Maps;

class Map13 extends \ARK\Models\ZooMap
{
  protected $id = '13';
  public function __construct($player)
  {
    $this->name = clienttranslate('Drawing Board');
    $this->desc = clienttranslate(
      'You start with a free size-2 enclosure at the center. <QUARTERS> If you completely cover an area on your map, immediately and during each break gain the depicted bonus.'
    );
    parent::__construct($player);
  }
  protected $fullMapBonus = 0;

  protected $terrains = [
    WATER => ['0_11', '1_10', '2_9', '3_8', '5_4', '6_3', '7_2', '8_1'],
    ROCK => ['0_1', '1_2', '2_3', '3_4', '5_8', '6_9', '7_10', '8_11'],
  ];
  protected $bonuses = [
    '1_4' => [MARK => 1],
    '1_8' => [MARK => 1],
    '1_12' => [XTOKEN => 1],
    '3_2' => [MONEY => 2],
    '3_6' => [TAKE_IN_RANGE_OR_DECK => 1],
    '4_9' => [XTOKEN => 1],
    '5_2' => [MONEY => 2],
    '5_6' => [CLEVER => 1],
    '7_4' => [APPEAL => 1],
    '7_8' => [APPEAL => 1],
    '7_12' => [XTOKEN => 1],
  ];
  protected $upgradeNeeded = ['8_3', '8_9'];

  protected $bonusSpaces = [
    ['type' => INCOME, 'bonus' => [SNAPPING => 1]],
    ['type' => INCOME, 'bonus' => [CUT_DOWN => 1]],
    ['type' => INCOME, 'bonus' => [MONEY => 5]],
    ['type' => BONUS, 'bonus' => [ADAPT => 3]],
    ['type' => BONUS, 'bonus' => [BONUS_WORKER => 1]],
    ['type' => BONUS, 'bonus' => [MONEY => 12]],
    ['type' => BONUS, 'bonus' => [XTOKEN => 3]],
  ];

  protected $lastWorkerBonus = null;
  protected $partnerZooBonuses = [
    2 => [BONUS_UPGRADE_CARD => 1],
    3 => [BONUS_WORKER => 1],
    4 => [CONSERVATION => 1],
  ];
  protected $facBonuses = [
    2 => [BONUS_UPGRADE_CARD => 1],
    3 => [CONSERVATION => 2],
  ];



  protected $quarters = [
    // NORTH
    [
      'hexes' => ['1_0', '2_1', '3_0', '3_2', '4_1', '4_3', '5_0', '5_2', '6_1', '7_0'],
      'bonus' => [MONEY => 3]
    ],
    // EAST
    [
      'hexes' => ['5_6', '6_5', '6_7', '7_4', '7_6', '7_8', '8_3', '8_5', '8_7', '8_9'],
      'bonus' => [APPEAL => 2]
    ],
    // SOUTH
    [
      'hexes' => ['1_12', '2_11', '3_12', '3_10', '4_11', '4_9', '5_12', '5_10', '6_11', '7_12'],
      'bonus' => [REPUTATION => 1]
    ],
    // WEST
    [
      'hexes' => ['3_6', '2_5', '2_7', '1_4', '1_6', '1_8', '0_3', '0_5', '0_7', '0_9'],
      'bonus' => [HUNTER => 4]
    ],
  ];

  // Cache status to avoid too much computation
  protected $cachedStatus = null;
  public function getStatus(): ?array
  {
    if (is_null($this->cachedStatus)) {
      $this->refreshCachedStatus();
    }
    return $this->cachedStatus;
  }

  public function refreshCachedStatus()
  {
    $status = [];
    foreach ($this->quarters as $i => $quarter) {
      $full = true;
      foreach ($quarter['hexes'] as $hexId) {
        if (!$this->hasBuildingAtPos($this->getHexFromId($hexId))) {
          $full = false;
          break;
        }
      }
      $status[$i] = $full;
    }
    $this->cachedStatus = $status;
    return $status;
  }


  protected function fetchDatas()
  {
    parent::fetchDatas();
    $this->refreshCachedStatus();
  }

  protected function addBuildingAux($building, $isRepositioning = false, $previousBonuses = [])
  {
    $oldStatus = $this->cachedStatus;
    list($building, $bonuses) = parent::addBuildingAux($building, $isRepositioning, $previousBonuses);
    $newStatus = $this->refreshCachedStatus();

    // Check for newly filled up quarters
    foreach ($this->quarters as $i => $quarter) {
      if (!$oldStatus[$i] && $newStatus[$i]) {
        $bonus = $quarter['bonus'];
        $bonus['source'] = clienttranslate('Map 13 quarter bonus');
        $bonuses[] = $bonus;
      }
    }

    return [$building, $bonuses];
  }

  public function getIncome($ui = false)
  {
    $bonuses = [];
    $status = $this->cachedStatus;

    if ($ui) {
      foreach ($this->quarters as $i => $quarter) {
        if ($status[$i]) {
          $bonuses[] = $quarter['bonus'];
        }
      }
    } else {
      foreach ($this->quarters as $i => $quarter) {
        $bonuses[] = [MAP_13_INCOME => $i];
      }
    }

    return $bonuses;
  }

  public function getQuarterIncome($i)
  {
    $status = $this->cachedStatus;
    $quarter = $this->quarters[$i];

    return $status[$i] ? $quarter['bonus'] : null;
  }
}
