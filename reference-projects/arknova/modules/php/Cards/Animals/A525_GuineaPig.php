<?php

namespace ARK\Cards\Animals;

class A525_GuineaPig extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A525_GuineaPig';
    $this->number = 525;
    $this->name = clienttranslate('Guinea Pig');
    $this->latin = clienttranslate('Cavia porcellus form. domestica');
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
