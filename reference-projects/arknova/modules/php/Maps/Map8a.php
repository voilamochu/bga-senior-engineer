<?php

namespace ARK\Maps;

class Map8a extends Map8
{
  protected $id = '8a';

  public function getHollywoodCells()
  {
    return [['x' => 1, 'y' => 10], ['x' => 4, 'y' => 11], ['x' => 7, 'y' => 8]];
  }

  protected $terrains = [
    WATER => ['0_9', '1_0', '1_4', '2_5', '4_3', '5_4', '6_3'],
    ROCK => ['0_11', '1_12', '2_11', '3_12', '4_9', '5_2', '5_12', '6_11', '7_10'],
  ];
  protected $bonuses = [
    '0_7' => [XTOKEN => 1],
    '1_2' => [MONEY => 5],
    '3_0' => [XTOKEN => 1],
    '4_5' => [REPUTATION => 1],
    '6_1' => [REPUTATION => 1],
    '7_4' => [XTOKEN => 1],
    '7_12' => [CLEVER => 1],
    '8_9' => [TAKE_IN_RANGE_OR_DECK => 1],
  ];
  protected $upgradeNeeded = ['2_7', '3_6', '5_10'];
}
