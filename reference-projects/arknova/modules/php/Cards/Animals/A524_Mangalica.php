<?php

namespace ARK\Cards\Animals;

class A524_Mangalica extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A524_Mangalica';
    $this->number = 524;
    $this->name = clienttranslate('Mangalica');
    $this->latin = clienttranslate('Sus scrofa form. domestica');
    $this->cost = 7;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [PETTING_ZOO],
      'cubes' => 1,
    ];
    $this->categories = [PET];
    $this->continents = [];
    $this->ability = [PETTING_ZOO_ANIMAL => null, DIGGING => 1];
  }
}
