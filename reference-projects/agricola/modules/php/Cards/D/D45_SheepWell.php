<?php
namespace AGR\Cards\D;

class D45_SheepWell extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D45_SheepWell';
    $this->name = clienttranslate('Sheep Well');
    $this->deck = 'D';
    $this->number = 45;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 1 <FOOD> on each of the next round spaces, up to the number of <SHEEP> you have. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->vp = 2;
    $this->cost = [
      STONE => 2,
    ];
  }

  public function onBuy($player)
  {
    $n = $player->getExchangeResources()[SHEEP];
    
    return $this->futureMeeplesNode([FOOD => 1], $n);
  }    
}
