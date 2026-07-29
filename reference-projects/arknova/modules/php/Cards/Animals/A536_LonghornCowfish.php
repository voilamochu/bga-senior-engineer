<?php

namespace ARK\Cards\Animals;

class A536_LonghornCowfish extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A536_LonghornCowfish';
    $this->number = 536;
    $this->name = clienttranslate('Longhorn Cowfish');
    $this->latin = clienttranslate('Lactoria cornuta');
    $this->cost = 9;
    $this->appeal = 4;
    $this->enclosureSize = 2;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 1,
    ];
    $this->categories = [SEA_ANIMAL];
    $this->continents = [AFRICA];
    $this->wave = true;
    $this->reefAbility = [BOOST => SPONSORS];
  }
}
