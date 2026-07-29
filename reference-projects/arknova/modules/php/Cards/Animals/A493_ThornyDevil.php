<?php

namespace ARK\Cards\Animals;

class A493_ThornyDevil extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A493_ThornyDevil';
    $this->number = 493;
    $this->name = clienttranslate('Thorny Devil');
    $this->latin = clienttranslate('Moloch horridus');
    $this->cost = 6;
    $this->appeal = 3;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosureSize = 1;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 0,
    ];
    $this->categories = [REPTILE];
    $this->continents = [AUSTRALIA];
  }
}
