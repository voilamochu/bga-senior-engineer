<?php

namespace ARK\Maps;

class Map1a extends Map1
{
  protected $id = '1a';

  public function getTower()
  {
    return ['x' => 7, 'y' => 6];
  }

  protected $terrains = [
    WATER => ['0_9', '4_5', '5_6', '5_12', '8_3', '8_5', '8_7'],
    ROCK => ['0_5', '0_7', '1_0', '1_6', '2_1', '3_0', '3_10', '3_12', '7_6'],
  ];
  protected $bonuses = [
    '0_1' => [XTOKEN => 1],
    '0_11' => [MONEY => 5],
    '1_8' => [XTOKEN => 1],
    '3_2' => [MONEY => 5],
    '3_6' => [TAKE_IN_RANGE_OR_DECK => 1],
    '4_11' => [REPUTATION => 1],
    '5_4' => [TAKE_IN_RANGE_OR_DECK => 1],
    '7_12' => [XTOKEN => 1],
    '8_1' => [CLEVER => 1]
  ];
}
