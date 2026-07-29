<?php
namespace AGR\Cards\C;

class C173_TopOuter extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C173_TopOuter';
    $this->name = ('Top-Outer');
    $this->deck = 'C';
    $this->number = 173;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      (
        'Each time the "House Building" action space on the game board extension is used, you get all of the food from the "Traveling Players" accumulation space.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
