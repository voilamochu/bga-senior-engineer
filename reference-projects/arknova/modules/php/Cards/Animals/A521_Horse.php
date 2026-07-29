<?php

namespace ARK\Cards\Animals;

class A521_Horse extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A521_Horse';
    $this->number = 521;
    $this->name = clienttranslate('Horse');
    $this->latin = clienttranslate('Equus caballus');
    $this->cost = 7;
    $this->reputation = 1;
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
