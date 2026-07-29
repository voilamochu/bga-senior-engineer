<?php

namespace AGR\Cards\E;

use AGR\Helpers\Utils;
use AGR\Models\MinorImprovement;

class E64_SimpleOven extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E64_SimpleOven';
    $this->name = clienttranslate('Simple Oven');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 64;
    $this->category = 'FOOD_-_GRAIN';
    $this->desc = [
      clienttranslate('[__Bake Bread__ action:]'),
      '<GRAIN> <ARROW-1X> 3<FOOD>',
      clienttranslate(
        'When you play this card, you can immediately take a __Bake Bread__ action.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      CLAY => 2,
    ];
    $this->implemented = true;
    $this->exchanges = [Utils::formatExchange([GRAIN => [FOOD => 3], 'max' => 1], $this->name, [BREAD])];
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
