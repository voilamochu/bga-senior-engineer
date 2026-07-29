<?php
namespace AGR\Cards\D;

class D169_Plowsmith extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D169_Plowsmith';
    $this->name = ('Plowsmith');
    $this->deck = 'D';
    $this->number = 169;
    $this->category = FARM_PLANNER;
    $this->desc = [
      (
        'Each time another player takes at least 4 wood from an accumulation space, you can immediately pay 1 food to plow 1 field.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
