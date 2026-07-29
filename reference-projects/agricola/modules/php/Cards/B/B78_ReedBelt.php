<?php
namespace AGR\Cards\B;

class B78_ReedBelt extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B78_ReedBelt';
    $this->name = clienttranslate('Reed Belt');
    $this->deck = 'B';
    $this->number = 78;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 1 <REED> on each of the remaining space for rounds 5, 8, 10, and 12. At the start of these rounds, you get the <REED>.'
      ),
    ];
    $this->cost = [
      FOOD => 2,
    ];
    $this->isArtifexOrBubulcus = true;
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([REED => 1], [5, 8, 10, 12]);      
  }  
}
