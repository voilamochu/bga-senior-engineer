<?php
namespace AGR\Cards\D;

class D44_ForestWell extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D44_ForestWell';
    $this->name = clienttranslate('Forest Well');
    $this->deck = 'D';
    $this->number = 44;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 1 <FOOD> on each remaining round space, up to the amount of <WOOD> in your supply. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      STONE => 1,
      FOOD => 1,
    ];
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function onBuy($player)
  {
    $n = $player->countReserveResource(WOOD);
    
    return $this->futureMeeplesNode([FOOD => 1], $n);    
  }  
}
