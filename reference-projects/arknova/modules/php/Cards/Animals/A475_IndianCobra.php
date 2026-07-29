<?php

namespace ARK\Cards\Animals;

class A475_IndianCobra extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A475_IndianCobra';
    $this->number = 475;
    $this->name = clienttranslate('Indian Cobra');
    $this->latin = clienttranslate('Naja naja - Vulnerable');
    $this->cost = 13;
    $this->appeal = 6;
    $this->enclosureSize = 2;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 1,
    ];
    $this->categories = [REPTILE];
    $this->continents = [ASIA];
    $this->prerequisites = [
      SCIENCE => 2,
      UPGRADED_ANIMALS_CARD => 1,
    ];
    $this->ability = [HYPNOSIS => 3];
    $this->soloAbility = [DETERMINATION => null];
  }
}
