<?php
namespace ARK\Cards\Animals;

class A434_RedPanda extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A434_RedPanda';
    $this->number = 434;
    $this->name = clienttranslate('Red Panda');
    $this->latin = clienttranslate('Ailurus fulgens - Endangered');
    $this->cost = 16;
    $this->appeal = 6;
    $this->enclosureSize = 2;
    $this->categories = [HERBIVORE, BEAR];
    $this->continents = [ASIA];
    $this->prerequisites = [
      SCIENCE => 2,
    ];
    $this->ability = [MULTIPLIER => SPONSORS];
  }
}
