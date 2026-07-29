<?php

namespace ARK\Cards\Animals;

class A431_AsianElephant extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A431_AsianElephant';
    $this->number = 431;
    $this->name = clienttranslate('Asian Elephant');
    $this->latin = clienttranslate('Elephas maximus - Endangered');
    $this->cost = 33;
    $this->appeal = 8;
    $this->conservation = 1;
    $this->enclosureRequirements = [
      WATER => 1,
    ];
    $this->enclosureSize = 5;
    $this->categories = [HERBIVORE];
    $this->continents = [ASIA, ASIA];
    $this->prerequisites = [
      UPGRADED_ANIMALS_CARD => 1,
    ];
    $this->ability = [RESISTANCE => null];
  }
}
