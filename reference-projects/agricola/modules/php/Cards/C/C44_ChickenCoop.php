<?php
namespace AGR\Cards\C;

use AGR\Managers\PlayerCards;

class C44_ChickenCoop extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C44_ChickenCoop';
    $this->name = clienttranslate('Chicken Coop');
    $this->deck = 'C';
    $this->number = 44;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 1 <FOOD> on each of the next 8 round spaces. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->fee = [REED => 1];
    $this->costs = [[CLAY => 2], [WOOD => 2]];
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([FOOD => 1], 8);
  }
}
