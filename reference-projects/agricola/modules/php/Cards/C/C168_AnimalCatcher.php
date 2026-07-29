<?php

namespace AGR\Cards\C;

use AGR\Core\Engine;
use AGR\Core\Globals;
use AGR\Models\Occupation;

class C168_AnimalCatcher extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C168_AnimalCatcher';
    $this->name = clienttranslate('Animal Catcher');
    $this->deck = 'C';
    $this->number = 168;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Day Laborer__ action space, instead of 2 <FOOD>, you can get 3 different animals from the general supply. If you do, you must pay 1 <FOOD> each harvest left to play.'
      ),
    ];
    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;
  }

  public function onPlayerComputePlaceFarmerFlow($player, &$args)
  {
    $map = [6, 6, 6, 6, 6, 5, 5, 5, 4, 4, 3, 3, 2, 2, 1];
    $n = $map[Globals::getTurn()];

    if ($args['actionCardType'] != 'DayLaborer') {
      return;
    }

    $args['flow'] = [
      'type' => NODE_XOR,
      'pId' => $player->getId(),
      'childs' => [
        $args['flow'],
        [
          'type' => NODE_SEQ,
          'childs' => [
            $this->gainNode([SHEEP => 1, PIG => 1, CATTLE => 1]),
            [
              'action' => SPECIAL_EFFECT,
              'args' => [
                'cardId' => $this->id,
                'method' => 'payFood',
                'args' => [$n],
              ],
            ],
            [
              'action' => SPECIAL_EFFECT,
              'args' => [
                'cardId' => $this->id,
                'method' => 'bumpUsed',
              ],
            ],
          ]
        ]
      ]
    ];
  }

  public function getPayFoodDescription($n)
  {
    return [
      'log' => clienttranslate('Pay ${resources_desc}'),
      'args' => [
        'resources_desc' => $n . '<FOOD>',
      ],
    ];
  }

  public function payFood($n)
  {
    $flow = [$this->payNode([FOOD => $n])];
    Engine::insertAsChild(['type' => NODE_SEQ, 'childs' => $flow]);
  }

  public function bumpUsed()
  {
    $this->incStats('used');
  }

  public function isIndependentBumpUsed()
  {
    return true;
  }
}