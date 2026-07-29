<?php

namespace ARK\Cards\Animals;

class A531_PaletteSurgeonfish extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A531_PaletteSurgeonfish';
    $this->number = 531;
    $this->name = clienttranslate('Palette Surgeonfish');
    $this->latin = clienttranslate('Paracanthurus hepatus');
    $this->cost = 9;
    $this->appeal = 4;
    $this->enclosureSize = 1;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 1,
    ];
    $this->categories = [SEA_ANIMAL];
    $this->continents = [ASIA];
    $this->wave = true;
    $this->reefAbility = [TRADE => 1];
  }
}
