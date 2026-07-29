<?php

namespace ARK\Maps;

class Map4a extends Map4
{
  protected $id = '4a';
  protected $asset = '4';

  protected $bonuses = [
    '0_5' => [TAKE_IN_RANGE_OR_DECK => 1],
    '1_2' => [BONUS_SPONSOR => 1],
    '1_10' => [TAKE_IN_RANGE_OR_DECK => 1],
    '3_2' => [XTOKEN => 1],
    '3_6' => [TAKE_IN_RANGE_OR_DECK => 1],
    '4_11' => [TAKE_IN_RANGE_OR_DECK => 1],
    '5_4' => [XTOKEN => 1],
    '6_1' => [REPUTATION => 1],
    '6_9' => [CLEVER => 1],
    '7_4' => [MULTIPLIER => 1],
  ];
  protected $upgradeNeeded = ['0_1', '5_6', '6_3', '8_5'];
}
