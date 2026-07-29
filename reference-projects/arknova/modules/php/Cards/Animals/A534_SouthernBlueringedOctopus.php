<?php

namespace ARK\Cards\Animals;

class A534_SouthernBlueringedOctopus extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A534_SouthernBlueringedOctopus';
    $this->number = 534;
    $this->name = clienttranslate('Southern Blue-ringed Octopus');
    $this->latin = clienttranslate('Hapalochlaena maculosa');
    $this->cost = 12;
    $this->appeal = 4;
    $this->enclosureSize = 2;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 1,
    ];
    $this->categories = [SEA_ANIMAL];
    $this->continents = [AUSTRALIA];
    $this->ability = [VENOM => 1];
    $this->soloAbility = [INVENTIVE => 1];
    $this->wave = true;
    $this->reefAbility = [MONEY => 3];
  }
}
