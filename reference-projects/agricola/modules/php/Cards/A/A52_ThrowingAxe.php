<?php
namespace AGR\Cards\A;
use AGR\Managers\Meeples;
use AGR\Core\Globals;

class A52_ThrowingAxe extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A52_ThrowingAxe';
    $this->name = clienttranslate('Throwing Axe');
    $this->deck = 'A';
    $this->number = 52;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use a wood accumulation space while there is at least 1 <PIG> on the __Pig Market__ accumulation space, you also get 2 <FOOD>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->prerequisite = clienttranslate('Play in Round 7 or Later');
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $turn = Globals::getTurn();
    if ($turn < 7) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }
  
  public function isListeningTo($event)
  {
	$marketHasPigs = Meeples::getResourcesOnCard('ActionPigMarket', null, PIG)->count();
	
    return ($this->isBeforeCollectEvent($event, WOOD) && $marketHasPigs);	  	  
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return $this->gainNode([FOOD => 2]);
  }  
  
}
