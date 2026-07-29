<?php

namespace ARK\Cards\Animals;

class A542_HumpheadWrasse extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A542_HumpheadWrasse';
    $this->number = 542;
    $this->name = clienttranslate('Humphead Wrasse');
    $this->latin = clienttranslate('Cheilinus undulatus');
    $this->cost = 22;
    $this->appeal = 6;
    $this->conservation = 1;
    $this->enclosureSize = 3;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 2,
    ];
    $this->categories = [SEA_ANIMAL];
    $this->continents = [AFRICA];
    $this->prerequisites = [UNIVERSITY => 1];
    $this->wave = true;
    $this->reefAbility = [EXTRA_SHIFT => 1];
  }
}
