<?php

namespace ARK\Maps;

class Map0 extends \ARK\Models\ZooMap
{
  public function __construct($player)
  {
    $this->name = clienttranslate('Map 0');
    $this->desc = clienttranslate('No special ability.');
    parent::__construct($player);
  }

  protected $id = '0';
  protected $terrains = [
    WATER => ['0_1', '0_3', '1_0', '1_10', '2_7', '3_0', '5_4', '7_12', '8_9', '8_11'],
    ROCK => ['0_11', '1_12', '2_1', '3_2', '5_8', '5_12', '6_3', '6_9', '6_11'],
  ];
  protected $bonuses = [
    '0_9' => [REPUTATION => 2],
    '1_2' => [TAKE_IN_RANGE_OR_DECK => 1],
    '2_5' => [TAKE_IN_RANGE_OR_DECK => 1],
    '3_8' => [XTOKEN => 1],
    '3_12' => [TAKE_IN_RANGE_OR_DECK => 1],
    '4_1' => [XTOKEN => 1],
    '4_5' => [MONEY => 10],
    '5_6' => [CLEVER => 1],
    '5_10' => [XTOKEN => 1],
    '7_2' => [MONEY => 5],
    '7_10' => [REPUTATION => 2],
    '8_7' => [MONEY => 5],
  ];
  protected $upgradeNeeded = ['6_5', '7_4'];

  protected $bonusSpaces = [
    ['type' => INCOME, 'bonus' => [SNAPPING => 1]],
    ['type' => INCOME, 'bonus' => [BONUS_SIZE_2_ENCLOSURE => 1]],
    ['type' => INCOME, 'bonus' => [MONEY => 5]],
    ['type' => INCOME, 'bonus' => [CONSERVATION => 1]],
    ['type' => BONUS, 'bonus' => [BONUS_WORKER => 1]],
    ['type' => BONUS, 'bonus' => [MONEY => 12]],
    ['type' => BONUS, 'bonus' => [XTOKEN => 3]],
  ];

  protected $lastWorkerBonus = [CONSERVATION => 2];
  protected $partnerZooBonuses = [
    2 => [BONUS_UPGRADE_CARD => 1],
    3 => [\BONUS_WORKER => 1],
    4 => [\CONSERVATION => 2],
  ];
  protected $facBonuses = [
    2 => [\BONUS_UPGRADE_CARD => 1],
    3 => [\CONSERVATION => 2],
  ];
}
