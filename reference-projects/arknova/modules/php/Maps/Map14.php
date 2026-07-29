<?php

namespace ARK\Maps;

class Map14 extends \ARK\Models\ZooMap
{
  protected $id = '14';
  public function __construct($player)
  {
    $this->name = clienttranslate('Lagoon');
    $this->desc = clienttranslate(
      'Everytime you gain a new association worker (including your first worker at the start of the game), find a person Sponsor card and take it to your hand.'
    );
    parent::__construct($player);
  }

  protected $terrains = [
    WATER => ['2_9', '3_2', '5_10', '6_1', '7_12', '8_1', '8_3', '8_9', '8_11'],
    ROCK => ['0_3', '1_8', '3_4', '5_4', '6_3', '7_0', '8_5'],
  ];
  protected $bonuses = [
    '0_7' => [TAKE_IN_RANGE_OR_DECK => 1],
    '1_2' => [REPUTATION => 1],
    '1_10' => [XTOKEN => 1],
    '2_7' => [XTOKEN => 1],
    '3_0' => [CLEVER => 1],
    '3_8' => [BONUS_WAVE => 1],
    '5_2' => [TAKE_IN_RANGE_OR_DECK => 1],
    '5_12' => [MONEY => 5],
    '6_5' => [BONUS_WAVE => 1],
    '7_2' => [SHARK_ATTACK => 1],
    '7_10' => [SHARK_ATTACK => 1],
    '8_7' => [BONUS_WAVE => 1]
  ];
  protected $upgradeNeeded = ['1_4', '5_8', '6_11'];

  protected $bonusSpaces = [
    ['type' => INCOME, 'bonus' => [SNAPPING => 1]],
    ['type' => INCOME, 'bonus' => [BONUS_SIZE_2_ENCLOSURE => 1]],
    ['type' => INCOME, 'bonus' => [MONEY => 5]],
    ['type' => INCOME, 'bonus' => [BONUS_FREE_SPONSOR_PERSON => 1]],
    ['type' => BONUS, 'bonus' => [BONUS_WORKER => 1]],
    ['type' => BONUS, 'bonus' => [MONEY => 12]],
    ['type' => BONUS, 'bonus' => [XTOKEN => 3]],
  ];

  protected $workersBonuses = [
    1 => [SEARCH_CARD => SPONSOR_PERSON],
    2 => [SEARCH_CARD => SPONSOR_PERSON],
    3 => ['multiple' => [[CONSERVATION => 1], [SEARCH_CARD => SPONSOR_PERSON]]]
  ];
  protected $partnerZooBonuses = [
    2 => [BONUS_UPGRADE_CARD => 1],
    3 => [BONUS_WORKER => 1],
    4 => [CONSERVATION => 1],
  ];
  protected $facBonuses = [
    2 => [BONUS_UPGRADE_CARD => 1],
    3 => [CONSERVATION => 1],
  ];
}
