<?php
namespace AGR\Cards\A;

class A180_AnimalBrander extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A180_AnimalBrander';
    $this->name = ('Animal Brander');
    $this->deck = 'A';
    $this->number = 180;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      (
        'Each time you use the "Animal Market" action space, you can pay 1 food to use the same option twice (instead of once).'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
