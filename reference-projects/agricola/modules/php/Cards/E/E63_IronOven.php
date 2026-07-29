<?php

namespace AGR\Cards\E;

use AGR\Helpers\Utils;
use AGR\Models\MinorImprovement;

class E63_IronOven extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E63_IronOven';
    $this->name = clienttranslate('Iron Oven');
    $this->deck = 'E';
    $this->author = 'barbarossa89';
    $this->number = 63;
    $this->category = 'FOOD_-_GRAIN';
    $this->desc = [
      clienttranslate('[__Bake Bread__ action:]'),
      '<GRAIN> <ARROW-1X> 6<FOOD>',
      clienttranslate(
        'When you play this card, you can immediately take a __Bake Bread__ action.'
      ),

    ];
    $this->vp = 2;
    $this->cost = [
      STONE => '3',
    ];
    $this->implemented = true;
    $this->exchanges = [Utils::formatExchange([GRAIN => [FOOD => 6], 'max' => 1], $this->name, [BREAD])];
    $this->isBakingImprovement = true;
  }

  protected function onBuy($player)
  {
    return [
      'action' => EXCHANGE,
      'optional' => true,
      'args' => [
        'trigger' => BREAD,
      ],
    ];
  }
}
