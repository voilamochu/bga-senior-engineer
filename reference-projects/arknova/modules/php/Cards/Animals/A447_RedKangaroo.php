<?php
namespace ARK\Cards\Animals;

class A447_RedKangaroo extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A447_RedKangaroo';
    $this->number = 447;
    $this->name = clienttranslate('Red Kangaroo');
    $this->latin = clienttranslate('Macropus rufus');
    $this->cost = 23;
    $this->appeal = 7;
    $this->enclosureSize = 4;
    $this->categories = [HERBIVORE];
    $this->continents = [AUSTRALIA];
    $this->ability = [POUCH => 2, FLOCK_ANIMAL => 4];
  }
}
