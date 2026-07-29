<?php
namespace AGR\Cards\B;

class B170_CorralBuilder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B170_CorralBuilder';
    $this->name = ('Corral Builder');
    $this->deck = 'B';
    $this->number = 170;
    $this->category = FARM_PLANNER;
    $this->desc = [
      (
        'When the "Pig Market" and "Cattle Market" action space cards are each revealed (and placed on the round space), you can immediately fence exactly 1 farmyard space without playing wood.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
