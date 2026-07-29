<?php
namespace AGR\Cards\A;

class A173_ClayThief extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A173_ClayThief';
    $this->name = ('Clay Thief');
    $this->deck = 'A';
    $this->number = 173;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      (
        'Once this game, at the start of a work phase of your choice, you can turn this card face down to get all of the clay on the "Hollow" action space.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
