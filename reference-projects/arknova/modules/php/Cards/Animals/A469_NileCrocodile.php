<?php

namespace ARK\Cards\Animals;

class A469_NileCrocodile extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A469_NileCrocodile';
    $this->number = 469;
    $this->name = clienttranslate('Nile Crocodile');
    $this->latin = clienttranslate('Crocodylus niloticus');
    $this->cost = 13;
    $this->appeal = 9;
    $this->enclosureRequirements = [
      WATER => 1,
    ];
    $this->enclosureSize = 5;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 3,
    ];
    $this->categories = [REPTILE];
    $this->continents = [AFRICA];
    $this->prerequisites = [
      REPTILE => 3,
    ];
    $this->ability = [SNAPPING => 2];
  }
}
