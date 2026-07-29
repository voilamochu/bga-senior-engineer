<?php
namespace AGR\Cards\C;

class C179_BovinePioneer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C179_BovinePioneer';
    $this->name = ('Bovine Pioneer');
    $this->deck = 'C';
    $this->number = 179;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      ('Each time you create at least one new pasture from unfenced farmyard spaces you get 1 cattle.'),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
