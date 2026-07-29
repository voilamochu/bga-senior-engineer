<?php

namespace ARK\Cards\Animals;

class A545_LongcombSawfish extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A545_LongcombSawfish';
    $this->number = 545;
    $this->name = clienttranslate('Longcomb Sawfish');
    $this->latin = clienttranslate('Pristis zijsron - critically endangered');
    $this->cost = 21;
    $this->appeal = 9;
    $this->conservation = 1;
    $this->enclosureSize = 4;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 3,
    ];
    $this->categories = [SEA_ANIMAL, SEA_ANIMAL];
    $this->continents = [AFRICA];
    $this->prerequisites = [SEA_ANIMAL => 1];
    $this->ability = [CUT_DOWN => null];
    $this->wave = true;
  }
}
