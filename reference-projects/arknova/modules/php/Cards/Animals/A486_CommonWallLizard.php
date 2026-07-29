<?php

namespace ARK\Cards\Animals;

class A486_CommonWallLizard extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A486_CommonWallLizard';
    $this->number = 486;
    $this->name = clienttranslate('Common Wall Lizard');
    $this->latin = clienttranslate('Podarcis muralis');
    $this->cost = 4;
    $this->appeal = 2;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosureSize = 1;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 0,
    ];
    $this->categories = [REPTILE];
    $this->continents = [EUROPE];
  }
}
