<?php

namespace ARK\Maps;

class Map6 extends \ARK\Models\ZooMap
{
  protected $id = '6';
  public function __construct($player)
  {
    $this->name = clienttranslate('Research Institute');
    $this->desc = clienttranslate(
      'Active if <INSTITUTE> is connected: Each time you play an animal card, ignore 1 condition of your choice.'
    );
    parent::__construct($player);
  }

  public function canUseEffect()
  {
    // Bottom left cell must be connected
    return $this->hasBuildingAtPos(CORNER);
  }

  protected $terrains = [
    WATER => ['0_1', '0_3', '5_10', '5_12', '7_4', '7_6', '8_1', '8_3', '8_11'],
    ROCK => ['2_7', '2_9', '3_6', '3_8', '4_1', '5_0', '7_10'],
  ];
  protected $bonuses = [
    '1_2' => [XTOKEN => 1],
    '1_8' => [CLEVER => 1],
    '2_5' => [MONEY => 5],
    '3_0' => [XTOKEN => 1],
    '3_10' => [TAKE_IN_RANGE_OR_DECK => 1],
    '5_2' => [TAKE_IN_RANGE_OR_DECK => 1],
    '6_11' => [REPUTATION => 1],
    '7_2' => [UNIVERSITY => 1],
    '8_7' => [MONEY => 5],
  ];
  protected $upgradeNeeded = ['4_3', '5_4', '6_1', '7_0'];

  protected $bonusSpaces = [
    ['type' => INCOME, 'bonus' => [SNAPPING => 1]],
    ['type' => INCOME, 'bonus' => [BONUS_SIZE_2_ENCLOSURE => 1]],
    ['type' => INCOME, 'bonus' => [MONEY => 5]],
    ['type' => INCOME, 'bonus' => [CLEVER => 2]],
    ['type' => BONUS, 'bonus' => [BONUS_WORKER => 1]],
    ['type' => BONUS, 'bonus' => [MONEY => 12]],
    ['type' => BONUS, 'bonus' => [XTOKEN => 3]],
  ];

  protected $lastWorkerBonus = [CONSERVATION => 2];
  protected $partnerZooBonuses = [
    2 => [BONUS_UPGRADE_CARD => 1],
    3 => [\BONUS_WORKER => 1],
    4 => [CONSERVATION => 1],
  ];
  protected $facBonuses = [
    2 => [\BONUS_UPGRADE_CARD => 1],
  ];
}
