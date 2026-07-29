<?php

namespace ARK\Maps;

use ARK\Helpers\Utils;


class Map1 extends \ARK\Models\ZooMap
{
  protected $id = '1';
  public function __construct($player)
  {
    $this->name = clienttranslate('Observation Tower');
    $this->desc = clienttranslate(
      'Gain <APPEAL:2> every time you flip a standard enclosure to its occupied side next to the <TOWER>.'
    );
    parent::__construct($player);
  }

  public function getTower()
  {
    return ['x' => 1, 'y' => 6];
  }
  public function canUseEffect()
  {
    return is_null($this->getBuildingAtPos($this->getTower()));
  }

  public function isMapPower($enclosures)
  {
    // only for standard enclosures
    foreach ($enclosures as $enclosure) {
      if (!in_array($enclosure['type'], \REGULAR_ENCLOSURES)) {
        continue;
      }

      if ($this->isBuildingAdjacentTo($enclosure, $this->getTower())) {
        return true;
      }
    }
    return false;
  }

  protected $terrains = [
    WATER => ['4_5', '5_6', '5_12', '6_7', '7_6', '8_5', '8_7'],
    ROCK => ['0_5', '0_7', '0_9', '1_0', '1_6', '2_1', '3_0', '3_10', '3_12'],
  ];
  protected $bonuses = [
    '0_1' => [XTOKEN => 1],
    '0_11' => [MONEY => 5],
    '3_6' => [TAKE_IN_RANGE_OR_DECK => 1],
    '4_1' => [CLEVER => 1],
    '4_11' => [REPUTATION => 1],
    '6_5' => [MONEY => 5],
    '7_2' => [TAKE_IN_RANGE_OR_DECK => 1],
    '7_8' => [XTOKEN => 1],
    '7_12' => [XTOKEN => 1],
  ];
  protected $upgradeNeeded = ['3_4', '4_7', '5_8'];

  protected $bonusSpaces = [
    ['type' => INCOME, 'bonus' => [SNAPPING => 1]],
    ['type' => INCOME, 'bonus' => [BONUS_SIZE_2_ENCLOSURE => 1]],
    ['type' => INCOME, 'bonus' => [MONEY => 5]],
    ['type' => INCOME, 'bonus' => [BONUS_SPONSOR => 1]],
    ['type' => BONUS, 'bonus' => [BONUS_WORKER => 1]],
    ['type' => BONUS, 'bonus' => [MONEY => 12]],
    ['type' => BONUS, 'bonus' => [XTOKEN => 3]],
  ];

  protected $lastWorkerBonus = null;
  protected $partnerZooBonuses = [
    2 => [BONUS_UPGRADE_CARD => 1],
    3 => [\BONUS_WORKER => 1],
    4 => [\CONSERVATION => 2],
  ];
  protected $facBonuses = [
    2 => [\BONUS_UPGRADE_CARD => 1],
    3 => [\CONSERVATION => 1],
  ];
}
