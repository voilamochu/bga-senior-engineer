<?php

namespace ARK\Maps;

use ARK\Helpers\Utils;

class Map8 extends \ARK\Models\ZooMap
{
  protected $id = '8';
  public function __construct($player)
  {
    $this->name = clienttranslate('Hollywood Hills');
    $this->desc = clienttranslate(
      'Each time you cover an <HOLLYWOOD>, reveal cards from the deck and add the first Sponsor card to your hand. All <HOLLYWOOD> covered: Each Sponsor card <STRENGTH:-1> when played.'
    );
    parent::__construct($player);
  }

  public function getHollywoodCells()
  {
    return [['x' => 1, 'y' => 10], ['x' => 4, 'y' => 11], ['x' => 6, 'y' => 9]];
  }

  public function canUseEffect()
  {
    // All the spaces should be covered
    foreach ($this->getHollywoodCells() as $cell) {
      if (!$this->hasBuildingAtPos($cell)) {
        return false;
      }
    }

    return true;
  }

  public function addBuilding($buildingType, $pos, $rotation)
  {
    // Useful for CONFERENCE ON AUSTRALIA if H is already covered
    $hexesH = $this->getHollywoodCells();
    Utils::filter($hexesH, fn($hex) => !$this->hasBuildingAtPos($hex));

    list($building, $bonuses) = parent::addBuilding($buildingType, $pos, $rotation);
    // RECONSTRUCTION SPONSOR prevent H
    if ($this->player->hasPlayedCard('S280_Reconstruction')) {
      return [$building, $bonuses];
    }

    // How many H covered ?
    $hexes = self::getCoveredHexes($buildingType, $pos, $rotation, false);
    $cells = Utils::intersectZones($hexesH, $hexes);
    if (!empty($cells)) {
      $bonuses[] = [MAP8 => count($cells)];
    }

    return [$building, $bonuses];
  }

  protected $terrains = [
    WATER => ['1_0', '1_4', '2_5', '4_3', '5_2', '5_4', '6_3'],
    ROCK => ['0_9', '0_11', '1_12', '2_11', '3_12', '4_9', '5_12', '6_11', '7_10'],
  ];
  protected $bonuses = [
    '0_7' => [CLEVER => 1],
    '1_2' => [XTOKEN => 1],
    '3_0' => [XTOKEN => 1],
    '4_5' => [XTOKEN => 1],
    '6_1' => [MONEY => 5],
    '7_4' => [TAKE_IN_RANGE_OR_DECK => 1],
    '8_9' => [REPUTATION => 1],
  ];
  protected $upgradeNeeded = ['2_7', '3_6', '3_10'];

  protected $bonusSpaces = [
    ['type' => INCOME, 'bonus' => [SNAPPING => 1]],
    ['type' => INCOME, 'bonus' => [BONUS_SIZE_2_ENCLOSURE => 1]],
    ['type' => INCOME, 'bonus' => [MONEY => 5]],
    ['type' => BONUS, 'bonus' => [\PARTNER_ZOO => 1]],
    ['type' => BONUS, 'bonus' => [BONUS_WORKER => 1]],
    ['type' => BONUS, 'bonus' => [MONEY => 12]],
    ['type' => BONUS, 'bonus' => [XTOKEN => 3]],
  ];

  protected $lastWorkerBonus = [CONSERVATION => 1];
  protected $partnerZooBonuses = [
    2 => [BONUS_UPGRADE_CARD => 1],
    3 => [\BONUS_WORKER => 1],
    4 => [\CONSERVATION => 1],
  ];
  protected $facBonuses = [
    2 => [\BONUS_UPGRADE_CARD => 1],
    3 => [\CONSERVATION => 1],
  ];
}
