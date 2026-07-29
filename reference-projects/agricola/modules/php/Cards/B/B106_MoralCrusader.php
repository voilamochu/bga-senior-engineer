<?php
namespace AGR\Cards\B;
use AGR\Managers\Meeples;
use AGR\Managers\Players;
use AGR\Core\Globals;

class B106_MoralCrusader extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B106_MoralCrusader';
    $this->name = clienttranslate('Moral Crusader');
    $this->deck = 'B';
    $this->number = 106;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Immediately before the start of each round, if there are goods on the remaining round spaces that are promised to you, you get 1 <FOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'BeforeStartOfTurn';
  }
  
  public function onPlayerBeforeStartOfTurn($player, $event)
  {
	$goods = [WOOD, CLAY, STONE, REED, GRAIN, VEGETABLE, FOOD, SHEEP, PIG, CATTLE, WOODPLUS, FOODPLUS, VEGETABLEPLUS];
	$futureGoods = false;
	  
    foreach (range(Globals::getTurn(), 14) as $turn) {
	  $meeples = Meeples::getResourcesOnCard('turn_' . $turn, $player->getId());
	  foreach ($meeples as $meeple) {
        if (in_array($meeple['type'], $goods)) {
		  $futureGoods = true;
          break 2;
        }
	  }
	}
	
	if ($futureGoods) {
      return $this->gainNode([FOOD => 1]);		
	}
  }
}
