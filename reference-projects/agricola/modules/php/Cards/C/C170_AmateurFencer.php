<?php
namespace AGR\Cards\C;

class C170_AmateurFencer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C170_AmateurFencer';
    $this->name = ('Amateur Fencer');
    $this->deck = 'C';
    $this->number = 170;
    $this->category = FARM_PLANNER;
    $this->desc = [
      (
        'When you play this card, if you have no pastures yet, you can immediately fence exactly 1 space in your farmyard without paying wood for the fences.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
    $this->isCorbariusOrDulcinaria = true;
  }
}
