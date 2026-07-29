<?php

namespace ARK\Cards\Animals;

class A553_Tambaqui extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A553_Tambaqui';
    $this->number = 553;
    $this->name = clienttranslate('Tambaqui');
    $this->latin = clienttranslate('Colossoma macropomum');
    $this->cost = 15;
    $this->appeal = 5;
    $this->enclosureSize = 1;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 1,
    ];
    $this->categories = [SEA_ANIMAL, HERBIVORE];
    $this->continents = [AMERICAS];
    $this->ability = [ADAPT => 1];
    $this->wave = true;
  }
}
