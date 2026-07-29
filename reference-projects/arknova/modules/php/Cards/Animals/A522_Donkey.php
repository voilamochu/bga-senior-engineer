<?php

namespace ARK\Cards\Animals;

class A522_Donkey extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A522_Donkey';
    $this->number = 522;
    $this->name = clienttranslate('Donkey');
    $this->latin = clienttranslate('Equus asinus asinus');
    $this->cost = 7;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [PETTING_ZOO],
      'cubes' => 1,
    ];
    $this->categories = [PET];
    $this->continents = [];
    $this->ability = [PETTING_ZOO_ANIMAL => null, INVENTIVE => 1];
  }
}
