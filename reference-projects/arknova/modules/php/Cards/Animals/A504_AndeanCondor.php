<?php

namespace ARK\Cards\Animals;

class A504_AndeanCondor extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A504_AndeanCondor';
    $this->number = 504;
    $this->name = clienttranslate('Andean Condor');
    $this->latin = clienttranslate('Vultur gryphus - Vulnerable');
    $this->cost = 17;
    $this->appeal = 7;
    $this->reputation = 1;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosureSize = 5;
    $this->specialEnclosure = [
      'types' => [LARGE_BIRD_AVIARY],
      'cubes' => 1,
    ];
    $this->categories = [BIRD];
    $this->continents = [AMERICAS];
    $this->prerequisites = [
      BIRD => 1,
    ];
    $this->ability = [SCAVENGING => 4];
  }
}
