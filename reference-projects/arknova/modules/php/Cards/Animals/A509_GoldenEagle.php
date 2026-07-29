<?php

namespace ARK\Cards\Animals;

class A509_GoldenEagle extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A509_GoldenEagle';
    $this->number = 509;
    $this->name = clienttranslate('Golden Eagle');
    $this->latin = clienttranslate('Aquila chrysaetos');
    $this->cost = 20;
    $this->appeal = 7;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosureSize = 5;
    $this->specialEnclosure = [
      'types' => [LARGE_BIRD_AVIARY],
      'cubes' => 1,
    ];
    $this->categories = [BIRD];
    $this->continents = [EUROPE];
    $this->prerequisites = [
      UPGRADED_ANIMALS_CARD => 1,
    ];
    $this->ability = [DETERMINATION => null];
  }
}
