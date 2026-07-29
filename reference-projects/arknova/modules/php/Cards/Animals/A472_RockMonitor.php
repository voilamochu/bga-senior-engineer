<?php

namespace ARK\Cards\Animals;

class A472_RockMonitor extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A472_RockMonitor';
    $this->number = 472;
    $this->name = clienttranslate('Rock Monitor');
    $this->latin = clienttranslate('Varanus albigularis');
    $this->cost = 12;
    $this->appeal = 5;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosureSize = 2;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 1,
    ];
    $this->categories = [REPTILE];
    $this->continents = [AFRICA];
    $this->ability = [SUNBATHING => 3];
  }
}
