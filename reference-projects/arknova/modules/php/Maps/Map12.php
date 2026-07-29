<?php

namespace ARK\Maps;

class Map12 extends \ARK\Models\ZooMap
{
  protected $id = '12';
  public function __construct($player)
  {
    $this->name = clienttranslate('Artificial Intelligence');
    $this->desc = clienttranslate(
      'Conceal the leftmost open action strength (the "3" always remains open). Action strength is increased to the next highest visible number.'
    );
    parent::__construct($player);
  }

  protected $terrains = [
    WATER => ['0_7', '0_9', '1_6', '3_0', '4_5', '5_12', '7_6'],
    ROCK => ['0_5', '1_12', '3_10', '4_7', '5_0', '5_4', '5_6', '6_11', '7_0'],
  ];
  protected $bonuses = [
    '0_1' => [REPUTATION => 1],
    '0_11' => [BONUS_STRENGTH => 1],
    '1_4' => [CLEVER => 1],
    '1_8' => [CLEVER => 1],
    '2_11' => [XTOKEN => 1],
    '3_6' => [TAKE_IN_RANGE_OR_DECK => 1],
    '4_1' => [MONEY => 5],
    '5_10' => [XTOKEN => 1],
    '6_3' => [MARK => 1],
    '7_10' => [ADAPT => 1],
    '8_1' => [BONUS_STRENGTH => 1],
    '8_7' => [TAKE_IN_RANGE_OR_DECK => 1]
  ];
  protected $upgradeNeeded = ['2_5', '5_8', '6_1'];

  protected $bonusSpaces = [
    ['type' => INCOME, 'bonus' => [SNAPPING => 1]],
    ['type' => INCOME, 'bonus' => [BONUS_SIZE_2_ENCLOSURE => 1]],
    ['type' => INCOME, 'bonus' => [MONEY => 5]],
    ['type' => BONUS, 'bonus' => [ANIMAL_MAGNET => 1]],
    ['type' => BONUS, 'bonus' => [BONUS_WORKER => 1]],
    ['type' => BONUS, 'bonus' => [MONEY => 12]],
    ['type' => BONUS, 'bonus' => [XTOKEN => 3]],
  ];

  protected $workersBonuses = [
    2 => [BONUS_STRENGTH => 1],
    3 => [CONSERVATION => 1]
  ];

  protected $partnerZooBonuses = [
    3 => [BONUS_WORKER => 1],
    4 => [CONSERVATION => 2],
  ];
  protected $facBonuses = [
    2 => [BONUS_STRENGTH => 1],
  ];
  protected $facPartnerZooLinkedBonuses = [
    1 => [BONUS_UPGRADE_CARD => 1],
    2 => [BONUS_UPGRADE_CARD => 1],
  ];

  public function getStatus(): array
  {
    // How much strength bonus do we have ?
    $n = 0;

    // From the map
    if ($this->hasBuildingAtPos(['x' => 0, 'y' => 11])) {
      $n++;
    }
    // From the map bis
    if ($this->hasBuildingAtPos(['x' => 8, 'y' => 1])) {
      $n++;
    }
    // From fac
    if ($this->player->countUniversities() >= 2) {
      $n++;
    }
    // From workers
    if ($this->player->getWorkersInSupply()->count() <= 1) {
      $n++;
    }

    return ['bonusStrength' => $n];
  }
}
