<?php

namespace ARK\Cards\Animals;

class A433_GiantPanda extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A433_GiantPanda';
    $this->number = 433;
    $this->name = clienttranslate('Giant Panda');
    $this->latin = clienttranslate('Ailuropoda melanoleuca - Vunerable');
    $this->cost = 27;
    $this->appeal = 10;
    $this->conservation = 2;
    $this->reputation = 1;
    $this->enclosureSize = 3;
    $this->categories = [HERBIVORE, BEAR];
    $this->continents = [ASIA];
    $this->prerequisites = [
      HERBIVORE => 1,
      BEAR => 1,
      PARTNER_ZOO => 1,
    ];
  }
}
