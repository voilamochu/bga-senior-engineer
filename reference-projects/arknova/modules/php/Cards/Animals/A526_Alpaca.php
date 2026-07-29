<?php

namespace ARK\Cards\Animals;

class A526_Alpaca extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A526_Alpaca';
    $this->number = 526;
    $this->name = clienttranslate('Alpaca');
    $this->latin = clienttranslate('Vicugna pacos');
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
