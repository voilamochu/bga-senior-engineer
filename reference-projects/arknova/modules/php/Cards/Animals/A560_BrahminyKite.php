<?php

namespace ARK\Cards\Animals;

class A560_BrahminyKite extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A560_BrahminyKite';
    $this->number = 560;
    $this->name = clienttranslate('Brahminy Kite');
    $this->latin = clienttranslate('Haliastur indus');
    $this->cost = 12;
    $this->appeal = 6;
    $this->enclosureRequirements = [
      WATER => 1,
      ROCK => 1,
    ];
    $this->enclosureSize = 3;
    $this->specialEnclosure = [
      'types' => [LARGE_BIRD_AVIARY],
      'cubes' => 1,
    ];
    $this->categories = [BIRD];
    $this->continents = [AUSTRALIA];
    $this->ability = [HUNTER => 2];
  }
}
