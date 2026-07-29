<?php

namespace ARK\Cards\Animals;

class A481_GalApagosGiantTortoise extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A481_GalApagosGiantTortoise';
    $this->number = 481;
    $this->name = clienttranslate('Galapagos Giant Tortoise');
    $this->latin = clienttranslate('Chelonoidis nigra porteri - Critically Endangered');
    $this->cost = 30;
    $this->appeal = 8;
    $this->conservation = 2;
    $this->reputation = 1;
    $this->enclosureSize = 3;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 2,
    ];
    $this->categories = [REPTILE];
    $this->continents = [AMERICAS];
    $this->prerequisites = [
      AMERICAS => 2,
      UPGRADED_ANIMALS_CARD => 1,
    ];
    $this->ability = [SUNBATHING => 4];
  }
}
