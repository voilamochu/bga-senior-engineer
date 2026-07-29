<?php
namespace AGR\Cards\B;

class B10_Caravan extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B10_Caravan';
    $this->name = clienttranslate('Caravan');
    $this->deck = 'B';
    $this->number = 10;
    $this->category = FARM_PLANNER;
    $this->desc = [clienttranslate('This card provides room for 1 person.')];
    $this->cost = [
      WOOD => 3,
      FOOD => 3,
    ];
    $this->holder = true;
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;
  }
}
