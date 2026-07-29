<?php

namespace ARK\Maps;

class Map10 extends \ARK\Models\ZooMap
{
  protected $id = '10';
  public function __construct($player)
  {
    $this->name = clienttranslate('Rescue Station');
    $this->desc = clienttranslate(
      '<MAP10:B:1> Digging 1. If the discarded card is an animal card (except <PET>), slide it under your zoo map at an unoccupied space. The animal with all of its icons counts as "in your zoo". Ignore the rest of the card'
    );
    parent::__construct($player);
  }

  protected $terrains = [
    WATER => ['0_3', '0_5', '1_4', '3_0', '4_1', '5_8', '7_6', '8_7', '8_9'],
    ROCK => ['1_12', '2_11', '3_12', '4_7', '5_6', '7_4'],
  ];
  protected $bonuses = [
    '0_1' => [MAP10 => 1],
    '1_6' => [XTOKEN => 1],
    '1_10' => [XTOKEN => 1],
    '2_3' => [TAKE_IN_RANGE_OR_DECK => 1],
    '4_11' => [MAP10 => 1],
    '5_2' => [XTOKEN => 1],
    '6_5' => [TAKE_IN_RANGE_OR_DECK => 1],
    '7_8' => [MONEY => 5],
    '8_1' => [MAP10 => 1],
    '8_5' => [REPUTATION => 1],
    '8_11' => [CLEVER => 1],
  ];
  protected $upgradeNeeded = ['2_5', '4_9', '5_4'];

  protected $bonusSpaces = [
    ['type' => INCOME, 'bonus' => [SNAPPING => 1]],
    ['type' => INCOME, 'bonus' => [BONUS_SIZE_2_ENCLOSURE => 1]],
    ['type' => INCOME, 'bonus' => [MONEY => 5]],
    ['type' => BONUS, 'bonus' => [REPUTATION => 2]],
    ['type' => BONUS, 'bonus' => [BONUS_WORKER => 1]],
    ['type' => BONUS, 'bonus' => [MONEY => 12]],
    ['type' => BONUS, 'bonus' => [XTOKEN => 3]],
  ];

  protected $lastWorkerBonus = [CONSERVATION => 2];
  protected $partnerZooBonuses = [
    2 => [BONUS_UPGRADE_CARD => 1],
    3 => [BONUS_WORKER => 1],
    4 => [CONSERVATION => 1],
  ];
  protected $facBonuses = [
    2 => [BONUS_UPGRADE_CARD => 1],
  ];
}
