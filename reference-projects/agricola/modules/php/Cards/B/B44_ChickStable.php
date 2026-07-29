<?php
namespace AGR\Cards\B;

class B44_ChickStable extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B44_ChickStable';
    $this->name = clienttranslate('Chick Stable');
    $this->deck = 'B';
    $this->number = 44;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Add 3 and 4 to the current round and place 2 <FOOD> on each corresponding round space. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->costs = [[WOOD => 1], [CLAY => 1]];
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player)
  {
    return $this->futureMeeplesNode([FOOD => 2], ['+3', '+4']);
  }
}
