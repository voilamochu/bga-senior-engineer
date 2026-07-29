<?php

namespace ARK\Cards\Animals;

class A519_domesticGoat extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A519_domesticGoat';
    $this->number = 519;
    $this->name = clienttranslate('(domestic) Goat');
    $this->latin = clienttranslate('Capra aegagrus hircus');
    $this->cost = 7;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [PETTING_ZOO],
      'cubes' => 1,
    ];
    $this->categories = [PET];
    $this->continents = [];
    $this->ability = [PETTING_ZOO_ANIMAL => null];
  }
}
