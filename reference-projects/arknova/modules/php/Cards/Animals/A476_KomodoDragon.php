<?php

namespace ARK\Cards\Animals;

class A476_KomodoDragon extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A476_KomodoDragon';
    $this->number = 476;
    $this->name = clienttranslate('Komodo Dragon');
    $this->latin = clienttranslate('Varanus komodoensis - Vulnerable');
    $this->cost = 14;
    $this->appeal = 2;
    $this->enclosureSize = 3;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 2,
    ];
    $this->categories = [REPTILE];
    $this->continents = [ASIA];
    $this->prerequisites = [
      ASIA => 2,
    ];
    $this->ability = [ICONIC_ANIMAL => ASIA];
  }
}
