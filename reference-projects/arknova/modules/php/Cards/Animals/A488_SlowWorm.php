<?php

namespace ARK\Cards\Animals;

class A488_SlowWorm extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A488_SlowWorm';
    $this->number = 488;
    $this->name = clienttranslate('Slow Worm');
    $this->latin = clienttranslate('Anguis fragilis - Near Threatened');
    $this->cost = 4;
    $this->appeal = 2;
    $this->enclosureRequirements = [
      WATER => 1,
    ];
    $this->enclosureSize = 1;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 0,
    ];
    $this->categories = [REPTILE];
    $this->continents = [EUROPE];
  }
}
