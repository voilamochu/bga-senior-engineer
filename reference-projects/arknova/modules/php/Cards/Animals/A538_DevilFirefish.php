<?php

namespace ARK\Cards\Animals;

class A538_DevilFirefish extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A538_DevilFirefish';
    $this->number = 538;
    $this->name = clienttranslate('Devil Firefish');
    $this->latin = clienttranslate('Pterois miles');
    $this->cost = 16;
    $this->appeal = 6;
    $this->enclosureSize = 2;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 1,
    ];
    $this->categories = [SEA_ANIMAL];
    $this->continents = [ASIA];
    $this->prerequisites = [
      SCIENCE => 1,
    ];
    $this->ability = [VENOM => 1];
    $this->soloAbility = [INVENTIVE => 1];
    $this->wave = true;
    $this->reefAbility = [INVENTIVE => 1];
  }
}
