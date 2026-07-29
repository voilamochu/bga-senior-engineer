<?php

namespace ARK\Cards\Animals;

class A500_LongbilledVulture extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A500_LongbilledVulture';
    $this->number = 500;
    $this->name = clienttranslate('Long-billed Vulture');
    $this->latin = clienttranslate('Gyps indicus - Critically Endangered');
    $this->cost = 20;
    $this->appeal = 5;
    $this->conservation = 1;
    $this->reputation = 1;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosureSize = 4;
    $this->specialEnclosure = [
      'types' => [LARGE_BIRD_AVIARY],
      'cubes' => 1,
    ];
    $this->categories = [BIRD];
    $this->continents = [ASIA];
    $this->prerequisites = [
      SCIENCE => 1,
    ];
    $this->ability = [SCAVENGING => 3];
  }
}
