<?php
namespace AGR\Cards\B;

class B172_CattleCaregiver extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B172_CattleCaregiver';
    $this->name = ('Cattle Caregiver');
    $this->deck = 'B';
    $this->number = 172;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      ('At the start of each round, if 3/4/5+ players each have at least 1 cattle, you get 1/2/3 food.'),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
