<?php
namespace AGR\Cards\A;
use AGR\Managers\Players;

class A46_ClawKnife extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A46_ClawKnife';
    $this->name = clienttranslate('Claw Knife');
    $this->deck = 'A';
    $this->number = 46;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Sheep Market__ accumulation space, place 1 <FOOD> on each of the next 2 round spaces. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 1,
    ];
    $this->prerequisite = clienttranslate('Exactly 1 Pasture');
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $playerBoard = $player->board();	  
	$pastures = count($playerBoard->getPastures(true));
	
	if ($pastures != 1) {
	  return false;
	}
	
    return parent::isBuyable($player, $ignoreResources, $args);
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'SheepMarket');
  }  
  
  public function onPlayerPlaceFarmer($player, $args)
  {
    return $this->futureMeeplesNode([FOOD => 1], 2);
  }
}
