<?php

namespace ARK\Maps;

class Map11 extends \ARK\Models\ZooMap
{
  protected $id = '11';
  public function __construct($player)
  {
    $this->name = clienttranslate('Caves');
    $this->desc = clienttranslate(
      '<STORE> Store: Place 1 hand card under your notepad (can be taken back into your hand at any time). During each break, gain 2 money per stored card.'
    );
    parent::__construct($player);
  }

  public function canUseEffect()
  {
    return $this->player->getStoredCards()->count() > 0;
  }


  public function getIncome()
  {
    $n = $this->player->getStoredCards()->count();
    return [[MONEY => 2 * $n]]; // Always return income even if empty to make sure it's dynamic
  }


  protected $terrains = [
    WATER => ['0_1', '0_9', '4_11', '5_10', '6_7', '7_10', '8_9'],
    ROCK => ['0_7', '2_1', '2_5', '3_0', '4_5', '5_2', '5_12', '6_3', '8_11'],
  ];
  protected $bonuses = [
    '0_5' => [XTOKEN => 1],
    '1_2' => [TAKE_IN_RANGE_OR_DECK => 1],
    '1_10' => [STORE => 1],
    '3_2' => [STORE => 1],
    '4_3' => [EXTRA_SHIFT => 1],
    '4_9' => [TAKE_IN_RANGE_OR_DECK => 1],
    '5_0' => [CLEVER => 1],
    '6_5' => [XTOKEN => 1],
    '7_2' => [MONEY => 5],
    '7_8' => [STORE => 1],
    '7_12' => [REPUTATION => 1],
  ];
  protected $upgradeNeeded = ['2_3', '4_1', '5_4'];

  protected $bonusSpaces = [
    ['type' => INCOME, 'bonus' => [SNAPPING => 1]],
    ['type' => INCOME, 'bonus' => [BONUS_SIZE_2_ENCLOSURE => 1]],
    ['type' => INCOME, 'bonus' => [MONEY => 5]],
    ['type' => BONUS, 'bonus' => [BONUS_UPGRADE_CARD => 1]],
    ['type' => BONUS, 'bonus' => [BONUS_WORKER => 1]],
    ['type' => BONUS, 'bonus' => [MONEY => 12]],
    ['type' => BONUS, 'bonus' => [XTOKEN => 3]],
  ];

  protected $workersBonuses = [
    2 => [BONUS_UPGRADE_CARD => 1],
    3 => [CONSERVATION => 1]
  ];

  protected $partnerZooBonuses = [
    3 => [BONUS_WORKER => 1],
    4 => [CONSERVATION => 1],
  ];
  protected $facBonuses = [];
  protected $facPartnerZooLinkedBonuses = [
    1 => [BONUS_UPGRADE_CARD => 1],
    2 => [EXTRA_SHIFT => 1],
    3 => [CONSERVATION => 1]
  ];
}
