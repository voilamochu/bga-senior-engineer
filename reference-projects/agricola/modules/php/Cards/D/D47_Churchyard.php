<?php
namespace AGR\Cards\D;

class D47_Churchyard extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D47_Churchyard';
    $this->name = clienttranslate('Churchyard');
    $this->deck = 'D';
    $this->number = 47;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 2 <FOOD> on each remaining round space. At the start of these rounds, you get the <FOOD>. (*Occupations and Improvements)'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      STONE => 1,
      REED => 1,
    ];
    $this->prerequisite = clienttranslate('10 Cards* in Front of You');
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if ($player->getPlayedCards()->count() < 10) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }
  
  public function onBuy($player)
  {
    return $this->futureMeeplesNode([FOOD => 2], 14);      
  }
}
