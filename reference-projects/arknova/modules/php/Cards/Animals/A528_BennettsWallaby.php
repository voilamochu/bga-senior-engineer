<?php

namespace ARK\Cards\Animals;

class A528_BennettsWallaby extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A528_BennettsWallaby';
    $this->number = 528;
    $this->name = clienttranslate("Bennett's Wallaby");
    $this->latin = clienttranslate('Macropus rufogriseus');
    $this->cost = 7;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [PETTING_ZOO],
      'cubes' => 1,
    ];
    $this->categories = [PET];
    $this->continents = [];
    $this->ability = [PETTING_ZOO_ANIMAL => null, POUCH => 1];
  }
}
