<?php

namespace ARK\Cards\Animals;

class A477_VeiledChameleon extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A477_VeiledChameleon';
    $this->number = 477;
    $this->name = clienttranslate('Veiled Chameleon');
    $this->latin = clienttranslate('Chamaeleo calyptratus');
    $this->cost = 14;
    $this->appeal = 4;
    $this->enclosureSize = 1;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 0,
    ];
    $this->categories = [REPTILE];
    $this->continents = [ASIA];
    $this->ability = [SNAPPING => 1];
  }
}
