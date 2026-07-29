<?php

namespace ARK\Cards\Animals;

class A548_ShortsnoutedSeahorse extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A548_ShortsnoutedSeahorse';
    $this->number = 548;
    $this->name = clienttranslate('Short-snouted Seahorse');
    $this->latin = clienttranslate('Hippocampus hippocampus');
    $this->cost = 7;
    $this->appeal = 2;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosureSize = 1;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 1,
    ];
    $this->categories = [SEA_ANIMAL];
    $this->continents = [EUROPE];
    $this->ability = [CAMOUFLAGE => null, POUCH => 1];
    $this->wave = true;
  }
}
