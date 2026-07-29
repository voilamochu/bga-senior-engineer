<?php

namespace ARK\Cards\Animals;

class A533_BlacksideHawkfish extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A533_BlacksideHawkfish';
    $this->number = 533;
    $this->name = clienttranslate('Blackside Hawkfish');
    $this->latin = clienttranslate('Paracirrhites forsteri');
    $this->cost = 11;
    $this->appeal = 4;
    $this->enclosureSize = 2;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 1,
    ];
    $this->categories = [SEA_ANIMAL];
    $this->continents = [AUSTRALIA];
    $this->wave = true;
    $this->reefAbility = [POSTURING => 1];
  }
}
