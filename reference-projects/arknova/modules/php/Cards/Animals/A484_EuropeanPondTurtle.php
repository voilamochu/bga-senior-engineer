<?php

namespace ARK\Cards\Animals;

class A484_EuropeanPondTurtle extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A484_EuropeanPondTurtle';
    $this->number = 484;
    $this->name = clienttranslate('European Pond Turtle');
    $this->latin = clienttranslate('Emys orbicularis - Near Threatened');
    $this->cost = 9;
    $this->appeal = 4;
    $this->enclosureRequirements = [
      WATER => 1,
    ];
    $this->enclosureSize = 1;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 1,
    ];
    $this->categories = [REPTILE];
    $this->continents = [EUROPE];
  }
}
