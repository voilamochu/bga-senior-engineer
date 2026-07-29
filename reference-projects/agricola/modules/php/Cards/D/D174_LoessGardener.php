<?php
namespace AGR\Cards\D;

class D174_LoessGardener extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D174_LoessGardener';
    $this->name = ('Loess Gardener');
    $this->deck = 'D';
    $this->number = 174;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      ('Each time you use the "Clay Pit" accumulation space, you can also buy 1 vegetable for 1 food.'),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
