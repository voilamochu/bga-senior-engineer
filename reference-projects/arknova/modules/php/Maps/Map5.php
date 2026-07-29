<?php

namespace ARK\Maps;

class Map5 extends \ARK\Models\ZooMap
{
  protected $id = '5';
  public function __construct($player)
  {
    $this->name = clienttranslate('Park Restaurant');
    $this->desc = clienttranslate('Income: gain <MONEY:1> income for each covered space adjacent to the <RESTAURANT>.');
    parent::__construct($player);
  }

  public function getRestaurant()
  {
    return ['x' => 4, 'y' => 5];;
  }

  public function getIncome()
  {
    if ($this->hasBuildingAtPos($this->getRestaurant())) {
      return [];
    }

    $n = 0;
    foreach ($this->getNeighbours($this->getRestaurant()) as $cell) {
      $n += $this->hasBuildingAtPos($cell) ? 1 : 0;
    }

    return $n == 0 ? [] : [[MONEY => $n]];
  }

  protected $terrains = [
    WATER => ['0_5', '1_4', '2_9', '4_3', '5_8', '5_10', '7_6', '8_7'],
    ROCK => ['1_8', '2_3', '2_5', '4_5', '6_1', '6_9', '7_4'],
  ];
  protected $bonuses = [
    '1_0' => [XTOKEN => 1],
    '1_6' => [XTOKEN => 1],
    '2_7' => [BONUS_SPONSOR => 1],
    '4_1' => [CLEVER => 1],
    '4_9' => [TAKE_IN_RANGE_OR_DECK => 1],
    '6_3' => [TAKE_IN_RANGE_OR_DECK => 1],
    '6_7' => [TAKE_IN_RANGE_OR_DECK => 1],
    '6_11' => [MONEY => 5],
    '8_3' => [REPUTATION => 1],
  ];
  protected $upgradeNeeded = ['1_2', '1_10', '5_12'];

  protected $bonusSpaces = [
    ['type' => INCOME, 'bonus' => [SNAPPING => 1]],
    ['type' => INCOME, 'bonus' => [BONUS_SIZE_2_ENCLOSURE => 1]],
    ['type' => INCOME, 'bonus' => [MONEY => 5]],
    ['type' => BONUS, 'bonus' => [BONUS_SPECIAL_ENCLOSURES => 1]],
    ['type' => BONUS, 'bonus' => [BONUS_WORKER => 1]],
    ['type' => BONUS, 'bonus' => [MONEY => 12]],
    ['type' => BONUS, 'bonus' => [XTOKEN => 3]],
  ];

  protected $lastWorkerBonus = [CONSERVATION => 1];
  protected $partnerZooBonuses = [
    2 => [BONUS_UPGRADE_CARD => 1],
    3 => [\BONUS_WORKER => 1],
    4 => [CONSERVATION => 2],
  ];
  protected $facBonuses = [
    2 => [\BONUS_UPGRADE_CARD => 1],
  ];
}
