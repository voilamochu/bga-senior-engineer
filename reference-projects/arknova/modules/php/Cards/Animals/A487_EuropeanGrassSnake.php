<?php

namespace ARK\Cards\Animals;

class A487_EuropeanGrassSnake extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A487_EuropeanGrassSnake';
    $this->number = 487;
    $this->name = clienttranslate('European Grass Snake');
    $this->latin = clienttranslate('Natrix natrix');
    $this->cost = 8;
    $this->appeal = 3;
    $this->enclosureRequirements = [
      WATER => 1,
    ];
    $this->enclosureSize = 1;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 0,
    ];
    $this->categories = [REPTILE];
    $this->continents = [EUROPE];
    $this->ability = [CLEVER => null];
  }
}
