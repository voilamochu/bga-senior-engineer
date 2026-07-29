<?php

namespace ARK\Maps;

class MapA extends \ARK\Models\ZooMap
{
  public function __construct($player)
  {
    $this->name = clienttranslate('Map A');
    $this->desc = clienttranslate(
      'You start with a free size-3 enclosure and a kiosk in the bottom left corner. No special ability. '
    );
    parent::__construct($player);
  }

  protected $id = 'A';
  protected $terrains = [
    WATER => ['2_1', '2_11', '3_12', '4_1', '5_8', '6_7', '7_0', '8_1', '8_3'],
    ROCK => ['1_0', '1_2', '1_12', '2_3', '3_0', '5_4', '6_9'],
  ];
  protected $bonuses = [
    '0_1' => [REPUTATION => 2],
    '0_3' => [XTOKEN => 1],
    '2_5' => [TAKE_IN_RANGE_OR_DECK => 1],
    '2_9' => [MONEY => 5],
    '3_2' => [MONEY => 5],
    '4_7' => [XTOKEN => 1],
    '5_0' => [XTOKEN => 1],
    '5_6' => [TAKE_IN_RANGE_OR_DECK => 1],
    '5_10' => [TAKE_IN_RANGE_OR_DECK => 1],
    '7_2' => [BONUS_WORKER => 1],
    '7_10' => [REPUTATION => 2],
    '8_5' => [MONEY => 10],
  ];
  protected $upgradeNeeded = ['7_12', '8_11'];

  protected $bonusSpaces = [
    ['type' => INCOME, 'bonus' => [SNAPPING => 1]],
    ['type' => INCOME, 'bonus' => [BONUS_SIZE_2_ENCLOSURE => 1]],
    ['type' => INCOME, 'bonus' => [MONEY => 5]],
    ['type' => INCOME, 'bonus' => [CONSERVATION => 1]],
    ['type' => BONUS, 'bonus' => [REPUTATION => 2]],
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
