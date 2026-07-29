<?php
namespace AGR\Cards\C;

use AGR\Managers\PlayerCards;

class C65_Granary extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C65_Granary';
    $this->name = clienttranslate('Granary');
    $this->deck = 'C';
    $this->number = 65;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 1 <GRAIN> each on the remaining spaces for rounds 8, 10, and 12. At the start of these rounds, you get the <GRAIN>.'
      ),
    ];
    $this->vp = 1;
    $this->costs = [[WOOD => 3], [CLAY => 3]];
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([GRAIN => 1], [8, 10, 12]);      
  }
}
