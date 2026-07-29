<?php

namespace ARK\Maps;

use ARK\Managers\Meeples;
use ARK\Helpers\Utils;

class Map9 extends \ARK\Models\ZooMap
{
  protected $id = '9';
  public function __construct($player)
  {
    $this->name = clienttranslate('Geographical Zoo');
    $this->desc = clienttranslate(
      'Once per continent area: when you accomodate a matching animal in an enclosure there, gain 1 of these bonuses : <REPUTATION:B:1>, <MONEY:B:4>, <CLEVER:B:1>, <APPEAL:B:2>, <KIOSK-PAVILION:B:1>. All continents markers removed: <CONSERVATION:1>'
    );
    parent::__construct($player);
  }

  protected $terrains = [
    WATER => ['1_2', '2_3', '2_7', '4_5', '4_9', '6_7', '8_9', '8_11'],
    ROCK => ['0_9', '0_11', '1_0', '1_12', '6_1', '7_0', '8_1', '7_12'],
  ];
  protected $bonuses = [
    '0_1' => [BONUS_SPONSOR => 1],
    '1_8' => [TAKE_IN_RANGE_OR_DECK => 1],
    '2_1' => [MONEY => 5],
    '3_6' => [TAKE_IN_RANGE_OR_DECK => 1],
    '4_11' => [CLEVER => 1],
    '5_4' => [TAKE_IN_RANGE_OR_DECK => 1],
    '7_2' => [XTOKEN => 1],
    '7_8' => [XTOKEN => 1],
  ];
  protected $upgradeNeeded = ['4_7', '5_6'];

  protected $bonusSpaces = [
    ['type' => INCOME, 'bonus' => [SNAPPING => 1]],
    ['type' => INCOME, 'bonus' => [BONUS_SIZE_2_ENCLOSURE => 1]],
    ['type' => INCOME, 'bonus' => [MONEY => 5]],
    ['type' => BONUS, 'bonus' => [MAP9 => 1]],
    ['type' => BONUS, 'bonus' => [BONUS_WORKER => 1]],
    ['type' => BONUS, 'bonus' => [MONEY => 12]],
    ['type' => BONUS, 'bonus' => [XTOKEN => 3]],
  ];

  protected $lastWorkerBonus = null;
  protected $partnerZooBonuses = [
    2 => [BONUS_UPGRADE_CARD => 1],
    3 => [BONUS_WORKER => 1],
    4 => [CONSERVATION => 2],
  ];
  protected $facBonuses = [
    2 => [BONUS_UPGRADE_CARD => 1],
    3 => [CONSERVATION => 1],
  ];

  protected $continentAreas = [
    EUROPE => ['3_0', '3_2', '4_1', '4_3', '5_0', '5_2'],
    ASIA => ['6_5', '7_4', '7_6', '8_3', '8_5', '8_7'],
    AMERICAS => ['0_3', '0_5', '0_7', '1_4', '1_6', '2_5'],
    AUSTRALIA => ['5_8', '5_10', '5_12', '6_9', '6_11', '7_10'],
    AFRICA => ['1_10', '2_9', '2_11', '3_8', '3_10', '3_12'],
  ];

  public function fillEnclosure($enclosureId, $animal, $n = null)
  {
    list($enclosure) = parent::fillEnclosure($enclosureId, $animal, $n);

    // Do we have a cube on that continent ?
    $continent = $animal->getContinent();
    $covering = false;
    if (!is_null(Meeples::getTokenOnContinentArea($this->pId, $continent))) {
      // Are we covering that continent ?
      $hexes = $this->getBuildingCoveredHexes($enclosure, false);
      foreach ($hexes as $hex) {
        $id = $this->getCellId($hex);
        if (in_array($id, $this->continentAreas[$continent])) {
          $covering = true;
          break;
        }
      }
    }

    return $covering ? [$enclosure, $continent] : [$enclosure, null];
  }
}
