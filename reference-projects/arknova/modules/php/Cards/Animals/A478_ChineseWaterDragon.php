<?php

namespace ARK\Cards\Animals;

class A478_ChineseWaterDragon extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A478_ChineseWaterDragon';
    $this->number = 478;
    $this->name = clienttranslate('Chinese Water Dragon');
    $this->latin = clienttranslate('Physignathus cocincinus - Vulnerable');
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
    $this->continents = [ASIA];
    $this->ability = [SUNBATHING => 2];
  }
}
