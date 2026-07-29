<?php

namespace ARK\Cards\Animals;

class A552_GreenSeaTurtle extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A552_GreenSeaTurtle';
    $this->number = 552;
    $this->name = clienttranslate('Green Sea Turtle');
    $this->latin = clienttranslate('Chelonia mydas - endangered');
    $this->cost = 17;
    $this->appeal = 6;
    $this->enclosureSize = 3;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM, REPTILE_HOUSE],
      'cubes' => 2,
    ];
    $this->categories = [SEA_ANIMAL, REPTILE];
    $this->continents = [AMERICAS];
    $this->ability = [SCUBA_DIVE => null];
    $this->wave = true;
  }
}
