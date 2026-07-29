<?php

namespace ARK\Maps;

use ARK\Core\Globals;

class MapT1 extends \ARK\Models\ZooMap
{
  protected $id = 'T1';
  public function __construct($player)
  {
    $this->name = clienttranslate('Tournament 1');
    $this->desc = clienttranslate('Active if <HAND-CARDS> unlocked: once during your turn, you may discard 1 hand card to increase the strength of an action by <STRENGTH:+1>');
    parent::__construct($player);
  }

  public function canUseEffect()
  {
    if (Globals::isMapT1Used()) {
      return false;
    }

    $spaces = $this->player->getOccupiedBonusesSpaces();
    return !in_array(0, $spaces);
  }


  protected $terrains = [
    WATER => ['1_4', '4_3', '5_2', '7_6', '7_12', '8_3', '8_5', '8_11'],
    ROCK => ['0_1', '0_9', '0_11', '1_0', '2_5', '3_6', '4_9', '7_10'],
  ];
  protected $bonuses = [
    '1_2' => [XTOKEN => 1],
    '1_6' => [TAKE_IN_RANGE_OR_DECK => 1],
    '1_10' => [MONEY => 5],
    '3_0' => [XTOKEN => 1],
    '3_4' => [SCAVENGING => 3],
    '4_7' => [BONUS_SPONSOR => 1],
    '4_11' => [MARK => 1],
    '6_5' => [TAKE_IN_RANGE_OR_DECK => 1],
    '6_9' => [CLEVER => 1],
    '7_2' => [MONEY => 5],
    '8_9' => [TAKE_IN_RANGE_OR_DECK => 1],
  ];
  protected $upgradeNeeded = ['2_7', '5_0', '5_4'];

  protected $bonusSpaces = [
    ['type' => INCOME, 'bonus' => [TAKE_IN_RANGE_OR_DECK => 1]],
    ['type' => INCOME, 'bonus' => [BONUS_SIZE_2_ENCLOSURE => 1]],
    ['type' => INCOME, 'bonus' => [MONEY => 5]],
    ['type' => BONUS, 'bonus' => [REPUTATION => 3]],
    ['type' => BONUS, 'bonus' => [BONUS_WORKER => 1]],
    ['type' => BONUS, 'bonus' => [MONEY => 12]],
    ['type' => BONUS, 'bonus' => [XTOKEN => 3]],
  ];

  protected $workersBonuses = [
    1 => [REPUTATION => 1],
    2 => [REPUTATION => 1],
    3 => [CONSERVATION => 1]
  ];
  protected $partnerZooBonuses = [
    3 => [BONUS_WORKER => 1],
    4 => [CONSERVATION => 1],
  ];
  protected $facBonuses = [
    3 => [CONSERVATION => 1],
  ];
  protected $facPartnerZooLinkedBonuses = [
    1 => [BONUS_UPGRADE_CARD => 1],
    2 => [BONUS_UPGRADE_CARD => 1],
  ];
}
