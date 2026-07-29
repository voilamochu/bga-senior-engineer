<?php

namespace ARK\Cards\Animals;

class A546_SandTigerShark extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A546_SandTigerShark';
    $this->number = 546;
    $this->name = clienttranslate('Sand Tiger Shark');
    $this->latin = clienttranslate('Carcharias taurus - critically endangered');
    $this->cost = 32;
    $this->appeal = 8;
    $this->conservation = 1;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosureSize = 5;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 4,
    ];
    $this->categories = [SEA_ANIMAL, PREDATOR];
    $this->continents = [AUSTRALIA];
    $this->prerequisites = [
      UPGRADED_ANIMALS_CARD => 1,
    ];
    $this->ability = [SHARK_ATTACK => 2];
    $this->wave = true;
  }
}
