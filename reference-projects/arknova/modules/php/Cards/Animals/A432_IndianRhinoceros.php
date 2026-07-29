<?php
namespace ARK\Cards\Animals;

class A432_IndianRhinoceros extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A432_IndianRhinoceros';
    $this->number = 432;
    $this->name = clienttranslate('Indian Rhinoceros');
    $this->latin = clienttranslate('Rhinoceros unicornis - Vulnerable');
    $this->cost = 25;
    $this->appeal = 9;
    $this->enclosureSize = 4;
    $this->categories = [HERBIVORE];
    $this->continents = [ASIA];
    $this->prerequisites = [
      UPGRADED_ANIMALS_CARD => 1,
    ];
    $this->ability = [ASSERTION => null];
  }
}
