<?php
namespace AGR\Cards\A;

class A178_CarpentersBoy extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A178_CarpentersBoy';
    $this->name = ("Carpenter's Boy");
    $this->deck = 'A';
    $this->number = 178;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [('Each time another player builds a room, you immediately get 1 wood.')];
    $this->players = '5+';
    $this->implemented = false;
  }
}
