<?php
namespace AGR\Cards\B;

class B46_ClubHouse extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B46_ClubHouse';
    $this->name = clienttranslate('Club House');
    $this->deck = 'B';
    $this->number = 46;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 1 <FOOD> on each of the next 4 round spaces and 1 <STONE> on the round space after that. At the start of these rounds, you get the respective good.'
      ),
    ];
    $this->vp = 1;
    $this->costs = [[WOOD => 3], [CLAY => 2]];
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->futureMeeplesNode([FOOD => 1], 4),
        $this->futureMeeplesNode([STONE => 1], ['+5']),
      ]
    ];
  }  
}
