<?php

namespace ARK\Cards\Animals;

class A483_BoaConstrictor extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A483_BoaConstrictor';
    $this->number = 483;
    $this->name = clienttranslate('Boa Constrictor');
    $this->latin = clienttranslate('Boa constrictor');
    $this->cost = 16;
    $this->appeal = 7;
    $this->enclosureSize = 2;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 1,
    ];
    $this->categories = [REPTILE];
    $this->continents = [AMERICAS];
    $this->prerequisites = [
      SCIENCE => 2,
    ];
    $this->ability = [CONSTRICTION => null];
    $this->soloAbility = [CLEVER => null];
  }
}
