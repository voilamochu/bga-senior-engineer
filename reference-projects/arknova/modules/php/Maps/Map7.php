<?php

namespace ARK\Maps;


class Map7 extends \ARK\Models\ZooMap
{
  protected $id = '7';
  public function __construct($player)
  {
    $this->name = clienttranslate('Ice Cream Parlors');
    $this->desc = clienttranslate('All kiosk placement bonuses covered: <INCOME> Gain <MONEY:1> extra income for each kiosk');
    parent::__construct($player);
  }

  public function getKioskCells()
  {
    return [['x' => 1, 'y' => 2], ['x' => 5, 'y' => 4], ['x' => 5, 'y' => 10]];
  }

  public function getIncome()
  {
    // All the spaces should be covered
    foreach ($this->getKioskCells() as $cell) {
      if (!$this->hasBuildingAtPos($cell)) {
        return [[MONEY => 0]];
      }
    }

    $n = count($this->getBuildingsOfType(KIOSK));
    return [[MONEY => $n]]; // Always return income even if empty to make sure it's dynamic
  }

  protected $terrains = [
    WATER => ['7_0', '7_4', '7_10', '8_1', '8_3', '8_5', '8_11'],
    ROCK => ['0_1', '0_3', '1_8', '2_9', '3_4', '4_1', '4_3', '4_9', '5_8'],
  ];
  protected $bonuses = [
    '0_5' => [REPUTATION => 1],
    '1_2' => [KIOSK => 1],
    '1_10' => [REPUTATION => 1],
    '3_2' => [TAKE_IN_RANGE_OR_DECK => 1],
    '3_6' => [BONUS_SPONSOR => 1],
    '4_7' => [TAKE_IN_RANGE_OR_DECK => 1],
    '4_11' => [TAKE_IN_RANGE_OR_DECK => 1],
    '5_4' => [KIOSK => 1],
    '5_10' => [KIOSK => 1],
    '7_2' => [XTOKEN => 1],
    '7_6' => [CLEVER => 1],
    '7_12' => [MONEY => 5],
    '8_9' => [XTOKEN => 1],
  ];
  protected $upgradeNeeded = ['3_8', '3_10', '3_12'];

  protected $bonusSpaces = [
    ['type' => INCOME, 'bonus' => [SNAPPING => 1]],
    ['type' => INCOME, 'bonus' => [BONUS_SIZE_2_ENCLOSURE => 1]],
    ['type' => INCOME, 'bonus' => [MONEY => 5]],
    ['type' => INCOME, 'bonus' => [POUCH => 2]],
    ['type' => BONUS, 'bonus' => [BONUS_WORKER => 1]],
    ['type' => BONUS, 'bonus' => [MONEY => 12]],
    ['type' => BONUS, 'bonus' => [XTOKEN => 3]],
  ];

  protected $lastWorkerBonus = [CONSERVATION => 2];
  protected $partnerZooBonuses = [
    2 => [BONUS_UPGRADE_CARD => 1],
    3 => [\BONUS_WORKER => 1],
  ];
  protected $facBonuses = [
    2 => [\BONUS_UPGRADE_CARD => 1],
    3 => [\CONSERVATION => 1],
  ];
}
