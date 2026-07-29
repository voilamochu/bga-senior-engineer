<?php

namespace ARK\Maps;

class Map3 extends \ARK\Models\ZooMap
{
  protected $id = '3';
  public function __construct($player)
  {
    $this->name = clienttranslate('Silver Lake');
    $this->desc = clienttranslate('Profitable area around the Lake.');
    parent::__construct($player);
  }

  protected $terrains = [
    WATER => ['0_11', '1_6', '2_3', '2_5', '2_7', '5_10', '5_12', '6_1', '6_9'],
    ROCK => ['3_12', '4_11', '5_0', '6_3', '7_0', '8_5', '8_7'],
  ];
  protected $bonuses = [
    '0_5' => [MONEY => 2],
    '0_7' => [MONEY => 2],
    '1_2' => [MONEY => 2],
    '1_4' => [MONEY => 2],
    '1_8' => [MONEY => 2],
    '2_1' => [MONEY => 2],
    '2_9' => [MONEY => 2],
    '3_2' => [MONEY => 2],
    '3_4' => [MONEY => 2],
    '3_6' => [MONEY => 2],
    '3_8' => [MONEY => 2],

    '0_3' => [REPUTATION => 1],
    '1_12' => [TAKE_IN_RANGE_OR_DECK => 1],
    '4_9' => [REPUTATION => 1],
    '5_2' => [CLEVER => 1],
    '5_6' => [TAKE_IN_RANGE_OR_DECK => 1],
    '6_11' => [XTOKEN => 1],
    '7_2' => [BONUS_SPONSOR => 1],
    '8_9' => [XTOKEN => 1],
  ];
  protected $upgradeNeeded = ['0_1', '1_0', '1_2', '1_4', '2_1'];

  protected $bonusSpaces = [
    ['type' => INCOME, 'bonus' => [SNAPPING => 1]],
    ['type' => INCOME, 'bonus' => [BONUS_SIZE_2_ENCLOSURE => 1]],
    ['type' => INCOME, 'bonus' => [MONEY => 5]],
    ['type' => BONUS, 'bonus' => [DETERMINATION => 1]],
    ['type' => BONUS, 'bonus' => [BONUS_WORKER => 1]],
    ['type' => BONUS, 'bonus' => [MONEY => 12]],
    ['type' => BONUS, 'bonus' => [XTOKEN => 3]],
  ];

  protected $lastWorkerBonus = null;
  protected $partnerZooBonuses = [
    2 => [BONUS_UPGRADE_CARD => 1],
    3 => [\BONUS_WORKER => 1],
    4 => [CONSERVATION => 1],
  ];
  protected $facBonuses = [
    2 => [\BONUS_UPGRADE_CARD => 1],
    3 => [\CONSERVATION => 2],
  ];
}
