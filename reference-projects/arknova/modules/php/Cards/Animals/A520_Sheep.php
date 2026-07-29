<?php

namespace ARK\Cards\Animals;

class A520_Sheep extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A520_Sheep';
    $this->number = 520;
    $this->name = clienttranslate('Sheep');
    $this->latin = clienttranslate('Ovis gmelini aries');
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
