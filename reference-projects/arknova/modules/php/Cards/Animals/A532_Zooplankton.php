<?php

namespace ARK\Cards\Animals;

class A532_Zooplankton extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A532_Zooplankton';
    $this->number = 532;
    $this->name = clienttranslate('Zooplankton');
    $this->latin = '';
    $this->cost = 4;
    $this->appeal = 1;
    $this->enclosureSize = 1;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 0,
    ];
    $this->categories = [SEA_ANIMAL];
    $this->continents = [];
    $this->ability = [SEA_ANIMAL_MAGNET => null];
    $this->wave = true;
  }
}
