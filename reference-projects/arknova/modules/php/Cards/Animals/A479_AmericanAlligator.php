<?php

namespace ARK\Cards\Animals;

class A479_AmericanAlligator extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A479_AmericanAlligator';
    $this->number = 479;
    $this->name = clienttranslate('American Alligator');
    $this->latin = clienttranslate('Alligator mississippiensis');
    $this->cost = 18;
    $this->appeal = 7;
    $this->enclosureRequirements = [
      WATER => 1,
    ];
    $this->enclosureSize = 4;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 2,
    ];
    $this->categories = [REPTILE];
    $this->continents = [AMERICAS];
    $this->ability = [SNAPPING => 1];
  }
}
