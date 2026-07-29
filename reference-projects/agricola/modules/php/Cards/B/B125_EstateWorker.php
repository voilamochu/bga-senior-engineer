<?php
namespace AGR\Cards\B;

class B125_EstateWorker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B125_EstateWorker';
    $this->name = clienttranslate('Estate Worker');
    $this->deck = 'B';
    $this->number = 125;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 1 <WOOD>, 1 <CLAY>, 1 <REED>, and 1 <STONE> in this order on the next 4 round spaces. At the start of these rounds, you get the respective building resource.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->futureMeeplesNode([WOOD => 1], ['+1']),
        $this->futureMeeplesNode([CLAY => 1], ['+2']),
        $this->futureMeeplesNode([REED => 1], ['+3']),
        $this->futureMeeplesNode([STONE => 1], ['+4']),        
      ]
    ];
  }
}
