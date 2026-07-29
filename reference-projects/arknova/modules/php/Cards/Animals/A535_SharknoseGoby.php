<?php

namespace ARK\Cards\Animals;

class A535_SharknoseGoby extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A535_SharknoseGoby';
    $this->number = 535;
    $this->name = clienttranslate('Sharknose Goby');
    $this->latin = clienttranslate('Elacatinus evelynae');
    $this->cost = 7;
    $this->appeal = 2;
    $this->enclosureSize = 1;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 1,
    ];
    $this->categories = [SEA_ANIMAL];
    $this->continents = [AMERICAS];
    $this->ability = [SYMBIOSIS => 1];
    $this->reefAbility = [HELPFUL => null];
    $this->wave = true;
  }
}
