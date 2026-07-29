<?php
namespace AGR\Cards\A;

class A169_OffSiter extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A169_OffSiter';
    $this->name = ('Off-Siter');
    $this->deck = 'A';
    $this->number = 169;
    $this->category = FARM_PLANNER;
    $this->desc = [
      (
        'Once the total printed building cost of all the major improvements you have is at least 9 building resources, this card provides room for 1 person for the rest of the game.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
