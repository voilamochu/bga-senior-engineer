<?php

namespace ARK\Cards\Animals;

class A550_CompassJellyfish extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A550_CompassJellyfish';
    $this->number = 550;
    $this->name = clienttranslate('Compass Jellyfish');
    $this->latin = clienttranslate('Chrysaora hysoscella');
    $this->cost = 10;
    $this->appeal = 6;
    $this->enclosureSize = 3;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 2,
    ];
    $this->categories = [SEA_ANIMAL];
    $this->continents = [EUROPE];
    $this->prerequisites = [REPUTATION => 3];
    $this->ability = [GLIDE => 2];
    $this->wave = true;
  }
}
