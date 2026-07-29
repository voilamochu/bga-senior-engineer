<?php

namespace ARK\Maps;

class Map2 extends \ARK\Models\ZooMap
{
  protected $id = '2';
  public function __construct($player)
  {
    $this->name = clienttranslate('Outdoor Areas');
    $this->desc = clienttranslate('Each standard enclosure adjacent to <GATES> has +2 capacity.');
    parent::__construct($player);
  }

  protected function getGates()
  {
    return ['x' => 5, 'y' => 8];
  }

  protected function fetchDatas()
  {
    parent::fetchDatas();
    if ($this->hasBuildingAtPos($this->getGates())) {
      return;
    }

    foreach ($this->buildings as &$building) {
      if (in_array($building['type'], \REGULAR_ENCLOSURES) && $this->isBuildingAdjacentTo($building, $this->getGates())) {
        $building['size'] = $building['size'] + 2;
      }
    }
  }

  public function addBuilding($buildingType, $pos, $rotation)
  {
    list($building, $bonuses) = parent::addBuilding($buildingType, $pos, $rotation);

    // Update size of building
    if (
      !$this->hasBuildingAtPos($this->getGates()) &&
      in_array($building['type'], \REGULAR_ENCLOSURES) &&
      $this->isBuildingAdjacentTo($building, $this->getGates())
    ) {
      $this->buildings[$building['id']]['size'] = $building['size'] + 2;
      $building['size'] += 2;
    }

    return [$building, $bonuses];
  }

  protected $terrains = [
    WATER => ['3_8', '4_9', '4_11', '5_8', '5_10', '6_1', '7_0'],
    ROCK => ['0_1', '0_3', '0_9', '0_11', '1_4', '1_10', '4_1', '5_0', '8_5'],
  ];
  protected $bonuses = [
    '0_5' => [REPUTATION => 1],
    '1_2' => [PARTNER_ZOO => 1],
    '1_12' => [BONUS_SPONSOR => 1],
    '4_7' => [XTOKEN => 1],
    '5_2' => [TAKE_IN_RANGE_OR_DECK => 1],
    '7_2' => [TAKE_IN_RANGE_OR_DECK => 1],
    '7_6' => [XTOKEN => 1],
    '8_11' => [CLEVER => 1],
  ];
  protected $upgradeNeeded = ['1_0', '2_1', '3_6', '5_6'];

  protected $bonusSpaces = [
    ['type' => INCOME, 'bonus' => [SNAPPING => 1]],
    ['type' => INCOME, 'bonus' => [BONUS_SIZE_2_ENCLOSURE => 1]],
    ['type' => INCOME, 'bonus' => [MONEY => 5]],
    ['type' => BONUS, 'bonus' => [CONSERVATION => 1]],
    ['type' => BONUS, 'bonus' => [BONUS_WORKER => 1]],
    ['type' => BONUS, 'bonus' => [MONEY => 12]],
    ['type' => BONUS, 'bonus' => [XTOKEN => 3]],
  ];

  protected $lastWorkerBonus = [CONSERVATION => 1];
  protected $partnerZooBonuses = [
    2 => [BONUS_UPGRADE_CARD => 1],
    3 => [\BONUS_WORKER => 1],
  ];
  protected $facBonuses = [
    2 => [\BONUS_UPGRADE_CARD => 1],
    3 => [\CONSERVATION => 2],
  ];
}
