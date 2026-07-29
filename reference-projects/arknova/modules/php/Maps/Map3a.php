<?php

namespace ARK\Maps;

class Map3a extends Map3
{
  protected $id = '3a';

  protected $terrains = [
    WATER => ['0_11', '3_6', '4_3', '4_5', '4_7', '5_10', '5_12', '6_1', '6_9'],
    ROCK => ['0_7', '3_12', '4_11', '6_3', '7_0', '8_5', '8_7'],
  ];
  protected $bonuses = [
    '2_5' => [MONEY => 2],
    '2_7' => [MONEY => 2],
    '3_2' => [MONEY => 2],
    '3_4' => [MONEY => 2],
    '3_8' => [MONEY => 2],
    '4_1' => [MONEY => 2],
    '4_9' => [MONEY => 2],
    '5_2' => [MONEY => 2],
    '5_4' => [MONEY => 2],
    '5_6' => [MONEY => 2],
    '5_8' => [MONEY => 2],

    '1_2' => [REPUTATION => 1],
    '1_8' => [TAKE_IN_RANGE_OR_DECK => 1],
    '1_12' => [TAKE_IN_RANGE_OR_DECK => 1],
    '6_11' => [XTOKEN => 1],
    '7_2' => [BONUS_SPONSOR => 1],
    '7_6' => [CLEVER => 1],
    '8_9' => [XTOKEN => 1],
  ];
  protected $upgradeNeeded = ['2_7', '4_1', '5_6'];
}
