<?php
namespace AGR\Cards\D;

class D179_Bullcatcher extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D179_Bullcatcher';
    $this->name = ('Bullcatcher');
    $this->deck = 'D';
    $this->number = 179;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      (
        'When both action spaces on round spaces 3 and 6 are occupied, you can use this card with a person to get 1 cattle and 2 food.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
