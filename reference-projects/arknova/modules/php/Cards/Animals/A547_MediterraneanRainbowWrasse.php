<?php

namespace ARK\Cards\Animals;

class A547_MediterraneanRainbowWrasse extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A547_MediterraneanRainbowWrasse';
    $this->number = 547;
    $this->name = clienttranslate('Mediterranean Rainbow Wrasse');
    $this->latin = clienttranslate('Corris julis');
    $this->cost = 8;
    $this->appeal = 3;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosureSize = 1;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 0,
    ];
    $this->categories = [SEA_ANIMAL];
    $this->continents = [EUROPE];
    $this->ability = [SYMBIOSIS => 1];
    $this->wave = true;
  }
}
