<?php
namespace AGR\Cards\D;
use AGR\Managers\Meeples;

class D54_TroutPool extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D54_TroutPool';
    $this->name = clienttranslate('Trout Pool');
    $this->deck = 'D';
    $this->number = 54;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At the start of each work phase, if there are at least 3 <FOOD> on the __Fishing__ accumulation space, you get 1 <FOOD> from the general supply.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      CLAY => 2,
    ];
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'startOfWork';
  }

  public function onPlayerStartOfWork($player, $event)
  {
    $n = Meeples::getResourcesOnCard('ActionFishing', null, FOOD)->count();
    
    if ($n >= 3) {
	  return $this->gainNode([FOOD => 1]);
    }
  }  
}
