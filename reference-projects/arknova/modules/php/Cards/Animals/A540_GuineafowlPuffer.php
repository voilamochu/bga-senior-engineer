<?php

namespace ARK\Cards\Animals;

class A540_GuineafowlPuffer extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A540_GuineafowlPuffer';
    $this->number = 540;
    $this->name = clienttranslate('Guineafowl Puffer');
    $this->latin = clienttranslate('Arothron meleagris');
    $this->cost = 15;
    $this->appeal = 5;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 2,
    ];
    $this->enclosureSize = 2;
    $this->categories = [SEA_ANIMAL];
    $this->continents = [ASIA];
    $this->ability = [VENOM => 1];
    $this->soloAbility = [INVENTIVE => 1];
    $this->wave = true;
    $this->reefAbility = [REPUTATION => 1];
  }
}
