<?php
namespace AGR\Cards\B;
use AGR\Core\Globals;

class B14_Hawktower extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B14_Hawktower';
    $this->name = clienttranslate('Hawktower');
    $this->deck = 'B';
    $this->number = 14;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Place a stone room on round space 12. If you live in a stone house at the start of the round, you can build the stone room at no cost. Otherwise, discard the stone room.'
      ),
    ];
    $this->cost = [
      CLAY => 2,
    ];
    $this->prerequisite = clienttranslate('Play in Round 7 or Before');
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $turn = Globals::getTurn();
    if ($turn > 7) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }
  
  public function onBuy($player)
  {
    return $this->futureMeeplesNode(['roomStone' => 1], [12]);
  }
}
