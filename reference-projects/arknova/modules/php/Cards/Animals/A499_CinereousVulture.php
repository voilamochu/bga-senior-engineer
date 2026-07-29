<?php

namespace ARK\Cards\Animals;

class A499_CinereousVulture extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A499_CinereousVulture';
    $this->number = 499;
    $this->name = clienttranslate('Cinereous Vulture');
    $this->latin = clienttranslate('Aegypius monachus - Near Threatened');
    $this->cost = 16;
    $this->appeal = 6;
    $this->enclosureSize = 5;
    $this->specialEnclosure = [
      'types' => [LARGE_BIRD_AVIARY],
      'cubes' => 1,
    ];
    $this->categories = [BIRD];
    $this->continents = [ASIA];
    $this->ability = [SCAVENGING => 3];
  }
}
