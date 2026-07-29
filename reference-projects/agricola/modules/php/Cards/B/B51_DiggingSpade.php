<?php
namespace AGR\Cards\B;
use AGR\Core\Globals;

// NOTE: "on your farmyard" seems to exclude locations like C11 (Wildlife Reserve)
// but B104_SheepWalker uses the same wording and doesn't exclude them

class B51_DiggingSpade extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B51_DiggingSpade';
    $this->name = clienttranslate('Digging Spade');
    $this->deck = 'B';
    $this->number = 51;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use a clay accumulation space, you also get a number of <FOOD> equal to the number of <PIG> in your farmyard.'
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
    if (Globals::getTurn() < 7) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }
  
  public function isListeningTo($event)
  {
    return $this->isBeforeCollectEvent($event, CLAY);
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    $pigs = $player->countAnimalsOnBoard()[PIG];
	if ($pigs > 0) {
	  return $this->gainNode([FOOD => $pigs]);
	}
  }

}
