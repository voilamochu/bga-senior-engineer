<?php
namespace AGR\Cards\B;

class B20_ChainFloat extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B20_ChainFloat';
    $this->name = clienttranslate('Chain Float');
    $this->deck = 'B';
    $this->number = 20;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Add 7, 8, and 9 to the current round and place 1 field on each corresponding round space. At the start of these rounds, you can plow the field.'
      ),
    ];
    $this->cost = [
      WOOD => 3,
    ];
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player)
  {
    return $this->futureMeeplesNode([FIELD => 1], ['+7', '+8', '+9']);
  }  
}
