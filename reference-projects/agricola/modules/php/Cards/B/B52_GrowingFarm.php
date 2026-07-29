<?php
namespace AGR\Cards\B;
use AGR\Core\Globals;

class B52_GrowingFarm extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B52_GrowingFarm';
    $this->name = clienttranslate('Growing Farm');
    $this->deck = 'B';
    $this->number = 52;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'You can only play this card if you have at least as many pasture spaces as the number of completed rounds. If you do, you get a number of <FOOD> equal to the current round.'
      ),
    ];
    $this->vp = 2;
    $this->cost = [
      CLAY => 2,
      REED => 1,
    ];
    $this->prerequisite = clienttranslate('see below');
    $this->isArtifexOrBubulcus = true;
    $this->bannedWeak1or2p = true;
  }
  
  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $coveredZones = $player->board()->countCoveredZonesByPastures();
    $turn = Globals::getTurn();

    if ($coveredZones < $turn-1) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }
  
  public function onBuy($player)
  {
    $turn = Globals::getTurn();
 
    return $this->gainNode([FOOD => $turn]);
  }
}
