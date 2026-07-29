<?php

namespace ARK\Maps;

class Map5a extends Map5
{
  protected $id = '5a';

  protected $terrains = [
    WATER => ['0_5', '1_4', '2_9', '4_7', '5_12', '6_5', '7_6', '8_7'],
    ROCK => ['1_8', '2_3', '4_5', '5_0', '6_1', '6_9', '8_5'],
  ];
  protected $bonuses = [
    '1_0' => [CLEVER => 1],
    '1_6' => [XTOKEN => 1],
    '1_10' => [TAKE_IN_RANGE_OR_DECK => 1],
    '2_5' => [BONUS_SPONSOR => 1],
    '4_1' => [XTOKEN => 1],
    '4_11' => [TAKE_IN_RANGE_OR_DECK => 1],
    '6_7' => [TAKE_IN_RANGE_OR_DECK => 1],
    '6_11' => [MONEY => 5],
    '7_2' => [REPUTATION => 1],
  ];
  protected $upgradeNeeded = ['0_1', '8_3', '8_9'];
  protected $bonusSpaces = [
    ['type' => INCOME, 'bonus' => [SNAPPING => 1]],
    ['type' => INCOME, 'bonus' => [BONUS_SIZE_2_ENCLOSURE => 1]],
    ['type' => INCOME, 'bonus' => [MONEY => 5]],
    ['type' => BONUS, 'bonus' => [BONUS_SPECIAL_ENCLOSURES_WITH_AQUARIUM => 1]],
    ['type' => BONUS, 'bonus' => [BONUS_WORKER => 1]],
    ['type' => BONUS, 'bonus' => [MONEY => 12]],
    ['type' => BONUS, 'bonus' => [XTOKEN => 3]],
  ];
}
