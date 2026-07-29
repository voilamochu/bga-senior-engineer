<?php

namespace ARK\Cards\Animals;

class A485_CommonEuropeanAdder extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A485_CommonEuropeanAdder';
    $this->number = 485;
    $this->name = clienttranslate('Common European Adder');
    $this->latin = clienttranslate('Vipera berus');
    $this->cost = 10;
    $this->appeal = 2;
    $this->enclosureSize = 1;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 0,
    ];
    $this->categories = [REPTILE];
    $this->continents = [EUROPE];
    $this->prerequisites = [
      PARTNER_ZOO => 1,
    ];
    $this->ability = [HYPNOSIS => 3];
    $this->soloAbility = [DETERMINATION => null];
  }
}
