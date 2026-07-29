<?php
namespace AGR\Cards\A;

class A177_Middleman extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A177_Middleman';
    $this->name = ('Middleman');
    $this->deck = 'A';
    $this->number = 177;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      (
        'Place 1 stone and 1 food on all action spaces with the (meeple) symbol on the game board extension. Next time you place a person on them, you get the goods.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
