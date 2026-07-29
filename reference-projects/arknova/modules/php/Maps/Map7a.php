<?php

namespace ARK\Maps;

class Map7a extends Map7
{
    protected $id = '7a';

    public function getKioskCells()
    {
        return [['x' => 0, 'y' => 5], ['x' => 5, 'y' => 10], ['x' => 7, 'y' => 4]];
    }

    protected $terrains = [
        WATER => ['0_1', '7_0', '7_10', '8_1', '8_3', '8_5', '8_11'],
        ROCK => ['0_3', '1_8', '2_9', '3_4', '4_1', '4_3', '4_9', '5_0', '5_8'],
    ];
    protected $bonuses = [
        '0_5' => [KIOSK => 1],
        '1_2' => [REPUTATION => 1],
        '1_10' => [REPUTATION => 1],
        '2_5' => [XTOKEN => 1],
        '3_2' => [TAKE_IN_RANGE_OR_DECK => 1],
        '4_7' => [TAKE_IN_RANGE_OR_DECK => 1],
        '5_2' => [XTOKEN => 1],
        '5_10' => [KIOSK => 1],
        '6_1' => [TAKE_IN_RANGE_OR_DECK => 1],
        '7_4' => [KIOSK => 1],
        '7_12' => [MONEY => 5],
        '8_9' => [BONUS_SPONSOR => 1],
    ];
    protected $upgradeNeeded = ['3_6', '3_10', '3_12'];
}
