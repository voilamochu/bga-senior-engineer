<?php

namespace ARK\Cards\Animals;

class A505_BaldEagle extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A505_BaldEagle';
    $this->number = 505;
    $this->name = clienttranslate('Bald Eagle');
    $this->latin = clienttranslate('Haliaeetus leucocephalus');
    $this->cost = 23;
    $this->appeal = 8;
    $this->enclosureRequirements = [
      WATER => 1,
    ];
    $this->enclosureSize = 4;
    $this->specialEnclosure = [
      'types' => [LARGE_BIRD_AVIARY],
      'cubes' => 1,
    ];
    $this->categories = [BIRD];
    $this->continents = [AMERICAS];
    $this->prerequisites = [
      UPGRADED_ANIMALS_CARD => 1,
    ];
    $this->ability = [DETERMINATION => null];
  }
}
