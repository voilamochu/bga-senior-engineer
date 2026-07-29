<?php

namespace ARK\Cards\Animals;

class A510_WhiteStork extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A510_WhiteStork';
    $this->number = 510;
    $this->name = clienttranslate('White Stork');
    $this->latin = clienttranslate('Ciconia ciconia');
    $this->cost = 9;
    $this->appeal = 4;
    $this->enclosureSize = 4;
    $this->specialEnclosure = [
      'types' => [LARGE_BIRD_AVIARY],
      'cubes' => 1,
    ];
    $this->categories = [BIRD];
    $this->continents = [EUROPE];
    $this->prerequisites = [
      EUROPE => 1,
    ];
    $this->ability = [MULTIPLIER => BUILD];
  }
}
