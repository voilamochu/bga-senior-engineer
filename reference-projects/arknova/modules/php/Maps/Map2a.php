<?php

namespace ARK\Maps;

class Map2a extends Map2
{
  protected $id = '2a';

  protected $terrains = [
    WATER => ['0_1', '3_8', '4_9', '5_8', '5_10', '6_1', '7_0'],
    ROCK => ['0_3', '0_9', '0_11', '1_4', '1_10', '4_1', '5_0', '5_4', '8_5'],
  ];
  protected $bonuses = [
    '0_5' => [TAKE_IN_RANGE_OR_DECK => 1],
    '1_2' => [PARTNER_ZOO => 1],
    '1_12' => [REPUTATION => 1],
    '5_2' => [TAKE_IN_RANGE_OR_DECK => 1],
    '7_2' => [BONUS_SPONSOR => 1],
    '7_6' => [XTOKEN => 1],
    '7_10' => [CLEVER => 1],
  ];
  protected $upgradeNeeded = ['1_0', '2_1', '2_5', '3_0', '5_6'];
}
