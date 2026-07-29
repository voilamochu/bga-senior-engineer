<?php
namespace AGR\Cards\D;

class D78_ReedPond extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D78_ReedPond';
    $this->name = clienttranslate('Reed Pond');
    $this->deck = 'D';
    $this->number = 78;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Place 1 <REED> on each of the next 3 round spaces. At the start of these rounds, you get the <REED>.'
      ),
    ];
    $this->prerequisite = clienttranslate('3 Occupations');
    $this->occupationPrerequisites = ['min' => 3];
  }

  public function onBuy($player)
  {
    return $this->futureMeeplesNode([REED => 1], 3);      
  }
}
