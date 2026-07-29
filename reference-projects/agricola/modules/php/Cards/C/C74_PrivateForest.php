<?php
namespace AGR\Cards\C;

class C74_PrivateForest extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C74_PrivateForest';
    $this->name = clienttranslate('Private Forest');
    $this->deck = 'C';
    $this->number = 74;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 1 <WOOD> on each remaining even-numbered round space. At the start of these rounds, you get the <WOOD>.'
      ),
    ];
    $this->cost = [
      FOOD => 2,
    ];
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([WOOD => 1], [2, 4, 6, 8, 10, 12, 14]);      
  }
}
