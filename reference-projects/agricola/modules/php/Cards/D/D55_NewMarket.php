<?php
namespace AGR\Cards\D;
use AGR\Helpers\Utils;

class D55_NewMarket extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D55_NewMarket';
    $this->name = clienttranslate('New Market');
    $this->deck = 'D';
    $this->number = 55;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate('Each time you use an action space card on round spaces 8 to 11, you get 1 additional <FOOD>.'),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 1,
      CLAY => 1,
    ];
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardTurnEvent($event, [8, 9, 10, 11]);
  }
  
  public function onPlayerPlaceFarmer($player, $event)
  {
    return $this->gainNode([FOOD => 1]);    
  }
}
