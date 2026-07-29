<?php

namespace ARK\Cards\Animals;

class A506_KingVulture extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A506_KingVulture';
    $this->number = 506;
    $this->name = clienttranslate('King Vulture');
    $this->latin = clienttranslate('Sarcoramphus papa');
    $this->cost = 12;
    $this->appeal = 9;
    $this->enclosureSize = 3;
    $this->specialEnclosure = [
      'types' => [LARGE_BIRD_AVIARY],
      'cubes' => 1,
    ];
    $this->categories = [BIRD];
    $this->continents = [AMERICAS];
    $this->prerequisites = [
      BIRD => 3,
    ];
    $this->ability = [SCAVENGING => 5];
  }
}
