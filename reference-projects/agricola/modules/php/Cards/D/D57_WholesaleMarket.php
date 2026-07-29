<?php
namespace AGR\Cards\D;

class D57_WholesaleMarket extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D57_WholesaleMarket';
    $this->name = clienttranslate('Wholesale Market');
    $this->deck = 'D';
    $this->number = 57;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate('Place 1 <FOOD> on each remaining round space. At the start of these rounds, you get the <FOOD>.'),
    ];
    $this->vp = 3;
    $this->cost = [
      WOOD => 2,
      VEGETABLE => 2,
    ];
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function onBuy($player) {
    return $this->futureMeeplesNode([FOOD => 1], 14);      
  }
}
