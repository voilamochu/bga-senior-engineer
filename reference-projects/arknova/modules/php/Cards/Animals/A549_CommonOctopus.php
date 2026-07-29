<?php

namespace ARK\Cards\Animals;

class A549_CommonOctopus extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A549_CommonOctopus';
    $this->number = 549;
    $this->name = clienttranslate('Common Octopus');
    $this->latin = clienttranslate('Octopus vulgaris');
    $this->cost = 9;
    $this->appeal = 5;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosureSize = 3;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 2,
    ];
    $this->categories = [SEA_ANIMAL];
    $this->continents = [EUROPE];
    $this->prerequisites = [
      SCIENCE => 1,
    ];
    $this->ability = [CAMOUFLAGE => null];
    $this->wave = true;
  }
}
