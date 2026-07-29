<?php
namespace AGR\Cards\B;

class B173_Sweeper extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B173_Sweeper';
    $this->name = ('Sweeper');
    $this->deck = 'B';
    $this->number = 173;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      (
        'Each time you use an action space with the (meeple) symbol, place 1 food on this card. Once this game, you can turn this card face down to get the food on it.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
