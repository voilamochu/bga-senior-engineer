<?php
namespace AGR\Cards\A;

class A172_BoatPainter extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A172_BoatPainter';
    $this->name = ('Boat Painter');
    $this->deck = 'A';
    $this->number = 172;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      (
        'At the end of each work phase, if both the "Fishing" and "Traveling Players" accumulation spaces are occupied, you get your choice of 1 grain or 2 food.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
