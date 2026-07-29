<?php
namespace AGR\Cards\D;

class D173_TownClerk extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D173_TownClerk';
    $this->name = ('Town Clerk');
    $this->deck = 'D';
    $this->number = 173;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      (
        'Each time a major improvement is built, place 1 food on this card. Once this game, you can turn this card face down to get the food on it.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
