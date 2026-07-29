<?php

namespace ARK\Cards\Animals;

class A490_GouldsMonitor extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A490_GouldsMonitor';
    $this->number = 490;
    $this->name = clienttranslate("Gould's Monitor");
    $this->latin = clienttranslate('Varanus gouldii');
    $this->cost = 15;
    $this->appeal = 6;
    $this->enclosureSize = 3;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 2,
    ];
    $this->categories = [REPTILE];
    $this->continents = [AUSTRALIA];
    $this->ability = [SCAVENGING => 2];
  }
}
