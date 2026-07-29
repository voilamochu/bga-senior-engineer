<?php

namespace ARK\Cards\Animals;

class A541_BluespottedRibbontailRay extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A541_BluespottedRibbontailRay';
    $this->number = 541;
    $this->name = clienttranslate('Bluespotted Ribbontail Ray');
    $this->latin = clienttranslate('Taeniura lymma - Near threatened');
    $this->cost = 16;
    $this->appeal = 7;
    $this->enclosureSize = 2;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 1,
    ];
    $this->categories = [SEA_ANIMAL];
    $this->continents = [ASIA];
    $this->wave = true;
    $this->reefAbility = [DIGGING => 1];
  }
}
