<?php

namespace ARK\Cards\Animals;

class A489_SaltwaterCrocodile extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A489_SaltwaterCrocodile';
    $this->number = 489;
    $this->name = clienttranslate('Saltwater Crocodile');
    $this->latin = clienttranslate('Crocodylus porosus');
    $this->cost = 23;
    $this->appeal = 9;
    $this->enclosureRequirements = [
      WATER => 1,
    ];
    $this->enclosureSize = 5;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 3,
    ];
    $this->categories = [REPTILE, REPTILE];
    $this->continents = [AUSTRALIA];
    $this->prerequisites = [
      PARTNER_ZOO => 1,
    ];
    $this->ability = [SNAPPING => 2];
  }
}
