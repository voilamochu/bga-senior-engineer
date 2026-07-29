<?php

namespace ARK\Cards\Animals;

class A557_VietnamesePotbelliedPig extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A557_VietnamesePotbelliedPig';
    $this->number = 557;
    $this->name = clienttranslate('Vietnamese Pot-bellied Pig');
    $this->latin = clienttranslate('Sus scrofa f. domestica');
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
