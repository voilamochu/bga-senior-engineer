<?php

namespace ARK\Cards\Animals;

class A544_CaribbeanReefShark extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A544_CaribbeanReefShark';
    $this->number = 544;
    $this->name = clienttranslate('Caribbean Reef Shark');
    $this->latin = clienttranslate('Carcharhinus perezi - Endangered');
    $this->cost = 20;
    $this->appeal = 5;
    $this->reputation = 1;
    $this->enclosureSize = 4;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 3,
    ];
    $this->categories = [SEA_ANIMAL, PREDATOR];
    $this->continents = [AMERICAS];
    $this->ability = [];
    $this->wave = true;
    $this->reefAbility = [SHARK_ATTACK => 1];
  }
}
