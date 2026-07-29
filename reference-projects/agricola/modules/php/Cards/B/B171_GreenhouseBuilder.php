<?php
namespace AGR\Cards\B;

class B171_GreenhouseBuilder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B171_GreenhouseBuilder';
    $this->name = ('Greenhouse Builder');
    $this->deck = 'B';
    $this->number = 171;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      (
        'This is an action space for you only. It provides a choice of "Fencing", "House Redevelopment", or "Vegetable Seeds" if the corresponding action space is already in play.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
