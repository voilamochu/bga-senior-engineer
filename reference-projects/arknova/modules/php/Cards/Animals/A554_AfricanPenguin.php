<?php

namespace ARK\Cards\Animals;

class A554_AfricanPenguin extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A554_AfricanPenguin';
    $this->number = 554;
    $this->name = clienttranslate('African Penguin');
    $this->latin = clienttranslate('Spheniscus demersus');
    $this->cost = 18;
    $this->appeal = 6;
    $this->reputation = 1;
    $this->enclosureRequirements = [
      WATER => 1,
      ROCK => 1,
    ];
    $this->enclosureSize = 3;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 2,
    ];
    $this->categories = [SEA_ANIMAL, BIRD];
    $this->continents = [AFRICA];
    $this->prerequisites = [
      UPGRADED_ANIMALS_CARD => 1,
    ];
    $this->ability = [ADAPT => 2];
    $this->wave = true;
  }
}
