<?php
namespace AGR\Cards\C;

class C77_ClaySupply extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C77_ClaySupply';
    $this->name = clienttranslate('Clay Supply');
    $this->deck = 'C';
    $this->number = 77;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 1 <CLAY> on each of the next 3 round spaces. At the start of these rounds, you get the <CLAY>.'
      ),
    ];
    $this->cost = [
      FOOD => 1,
    ];
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([CLAY => 1], 3);      
  }
}
