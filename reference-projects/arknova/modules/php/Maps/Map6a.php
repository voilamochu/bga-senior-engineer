<?php

namespace ARK\Maps;

class Map6a extends Map6
{
  protected $id = '6a';
  protected $asset = '6';

  protected $bonuses = [
    '1_2' => [REPUTATION => 1],
    '1_8' => [MONEY => 5],
    '2_5' => [XTOKEN => 1],
    '3_2' => [XTOKEN => 1],
    '4_11' => [MONEY => 5],
    '5_2' => [CLEVER => 1],
    '7_2' => [UNIVERSITY => 1],
    '7_12' => [TAKE_IN_RANGE_OR_DECK => 1],
    '8_5' => [TAKE_IN_RANGE_OR_DECK => 1],
  ];
  protected $upgradeNeeded = ['4_3', '4_9', '6_1', '7_0'];
}
