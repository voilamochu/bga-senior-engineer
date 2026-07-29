<?php

namespace ARK\Cards\Animals;

class A527_CoconutLorikeet extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A527_CoconutLorikeet';
    $this->number = 527;
    $this->name = clienttranslate('Coconut Lorikeet');
    $this->latin = clienttranslate('Trichoglossus haematodus');
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
