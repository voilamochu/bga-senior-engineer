<?php

namespace ARK\Cards\Animals;

class A543_CoastalMantaRay extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A543_CoastalMantaRay';
    $this->number = 543;
    $this->name = clienttranslate('Coastal Manta Ray');
    $this->latin = clienttranslate('Mobula alfredi - Vulnerable');
    $this->cost = 23;
    $this->appeal = 8;
    $this->reputation = 1;
    $this->enclosureSize = 5;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 4,
    ];
    $this->categories = [SEA_ANIMAL];
    $this->continents = [ASIA];
    $this->prerequisites = [
      UPGRADED_ANIMALS_CARD => 1,
    ];
    $this->ability = [GLIDE => 3];
    $this->wave = true;
    $this->reefAbility = [CONSERVATION => 1];
  }
}
