<?php

namespace ARK\Maps;

use ARK\Core\Globals;

class Map4 extends \ARK\Models\ZooMap
{
  protected $id = '4';
  public function __construct($player)
  {
    $this->name = clienttranslate('Commercial Harbor');
    $this->desc = \clienttranslate(
      'Active if <HARBOR> is connected: Once during your turn, you may discard 1 hand card for <MONEY:3> (at any time).'
    );
    parent::__construct($player);
  }

  public function canUseEffect()
  {
    // Once per turn only + bottom left cell must be connected
    return !Globals::isEffectMap4() && $this->hasBuildingAtPos(CORNER);
  }

  protected $terrains = [
    WATER => ['0_9', '1_4', '1_12', '2_3', '2_5', '3_4', '5_0', '8_1', '8_3'],
    ROCK => ['3_8', '3_10', '5_12', '6_5', '6_7', '7_2', '7_10'],
  ];
  protected $bonuses = [
    '0_5' => [REPUTATION => 1],
    '1_2' => [XTOKEN => 1],
    '2_9' => [CLEVER => 1],
    '3_2' => [XTOKEN => 1],
    '3_6' => [TAKE_IN_RANGE_OR_DECK => 1],
    '5_4' => [XTOKEN => 1],
    '6_1' => [TAKE_IN_RANGE_OR_DECK => 1],
    '7_4' => [MULTIPLIER => 1],
    '8_11' => [MONEY => 5],
  ];
  protected $upgradeNeeded = ['6_3', '8_5', '8_7', '8_9'];

  protected $bonusSpaces = [
    ['type' => INCOME, 'bonus' => [SNAPPING => 1]],
    ['type' => INCOME, 'bonus' => [BONUS_SIZE_2_ENCLOSURE => 1]],
    ['type' => INCOME, 'bonus' => [MONEY => 5]],
    ['type' => BONUS, 'bonus' => [UNIVERSITY => 1]],
    ['type' => BONUS, 'bonus' => [BONUS_WORKER => 1]],
    ['type' => BONUS, 'bonus' => [MONEY => 12]],
    ['type' => BONUS, 'bonus' => [XTOKEN => 3]],
  ];

  protected $lastWorkerBonus = [CONSERVATION => 1];
  protected $partnerZooBonuses = [
    2 => [BONUS_UPGRADE_CARD => 1],
    3 => [\BONUS_WORKER => 1],
    4 => [CONSERVATION => 1],
  ];
  protected $facBonuses = [
    2 => [\BONUS_UPGRADE_CARD => 1],
    3 => [\CONSERVATION => 1],
  ];
}
