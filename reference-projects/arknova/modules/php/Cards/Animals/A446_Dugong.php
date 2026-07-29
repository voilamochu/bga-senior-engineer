<?php

namespace ARK\Cards\Animals;

class A446_Dugong extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A446_Dugong';
    $this->number = 446;
    $this->name = clienttranslate('Dugong');
    $this->latin = clienttranslate('Dugong dugon - Vulnerable');
    $this->cost = 25;
    $this->appeal = 9;
    $this->conservation = 1;
    $this->enclosureRequirements = [
      WATER => 2,
    ];
    $this->enclosureSize = 5;
    $this->categories = [HERBIVORE];
    $this->continents = [AUSTRALIA, AUSTRALIA];
    $this->prerequisites = [
      UPGRADED_ANIMALS_CARD => 1,
    ];
    $this->ability = [DIGGING => 4];
  }
}
