<?php
namespace AGR\Cards\D;
use AGR\Core\Globals;

class D67_ReapHook extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D67_ReapHook';
    $this->name = clienttranslate('Reap Hook');
    $this->deck = 'D';
    $this->number = 67;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 1 <GRAIN> on each of the next 3 of the round spaces 4, 7, 9, 11, 13, and 14. At the start of these rounds, you get the <GRAIN>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function onBuy($player) {
    $turns = [4, 7, 9, 11, 13, 14];

    for ($i = 0; $i < 6; $i++) {
      if (Globals::getTurn() < $turns[$i]) {
        $turns = array_slice($turns, $i, min(3, 6-$i));
        break;
      }
    }
    
    return $this->futureMeeplesNode([GRAIN => 1], $turns);    
  }
}
