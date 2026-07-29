<?php
namespace AGR\Cards\C;

class C79_StoneCart extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C79_StoneCart';
    $this->name = clienttranslate('Stone Cart');
    $this->deck = 'C';
    $this->number = 79;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 1 <STONE> on each remaining even-numbered round space. At the start of these rounds, you get the <STONE>.'
      ),
    ];
    $this->cost = [
      WOOD => 2,
    ];
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([STONE => 1], [2, 4, 6, 8, 10, 12, 14]);      
  }
}
