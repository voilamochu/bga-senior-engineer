<?php

namespace ARK\Cards\Animals;

class A539_AmericanWhitespottedFilefish extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A539_AmericanWhitespottedFilefish';
    $this->number = 539;
    $this->name = clienttranslate('American Whitespotted Filefish');
    $this->latin = clienttranslate('Cantherhines macrocerus');
    $this->cost = 12;
    $this->appeal = 5;
    $this->enclosureSize = 1;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 1,
    ];
    $this->categories = [SEA_ANIMAL];
    $this->continents = [AMERICAS];
    $this->wave = true;
    $this->reefAbility = [CLEVER => null];
  }
}
