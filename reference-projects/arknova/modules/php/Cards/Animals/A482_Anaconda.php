<?php

namespace ARK\Cards\Animals;

class A482_Anaconda extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A482_Anaconda';
    $this->number = 482;
    $this->name = clienttranslate('Anaconda');
    $this->latin = clienttranslate('Eunectes murinus');
    $this->cost = 13;
    $this->appeal = 6;
    $this->enclosureRequirements = [
      WATER => 1,
    ];
    $this->enclosureSize = 2;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 1,
    ];
    $this->categories = [REPTILE];
    $this->continents = [AMERICAS];
    $this->prerequisites = [
      PARTNER_ZOO => 1,
    ];
    $this->ability = [CONSTRICTION => null];
    $this->soloAbility = [CLEVER => null];
  }
}
