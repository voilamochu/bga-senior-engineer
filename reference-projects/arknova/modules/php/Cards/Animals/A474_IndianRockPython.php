<?php

namespace ARK\Cards\Animals;

class A474_IndianRockPython extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A474_IndianRockPython';
    $this->number = 474;
    $this->name = clienttranslate('Indian Rock Python');
    $this->latin = clienttranslate('Python molurus');
    $this->cost = 14;
    $this->appeal = 7;
    $this->enclosureSize = 2;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 1,
    ];
    $this->categories = [REPTILE];
    $this->continents = [ASIA];
    $this->prerequisites = [
      REPTILE => 2,
    ];
    $this->ability = [CONSTRICTION => null];
    $this->soloAbility = [CLEVER => null];
  }
}
