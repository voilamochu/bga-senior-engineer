<?php

namespace ARK\Cards\Animals;

class A551_LoggerheadSeaTurtle extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A551_LoggerheadSeaTurtle';
    $this->number = 551;
    $this->name = clienttranslate('Loggerhead Sea Turtle');
    $this->latin = clienttranslate('Caretta caretta - vulnerable');
    $this->cost = 23;
    $this->appeal = 8;
    $this->enclosureSize = 4;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM, REPTILE_HOUSE],
      'cubes' => 3,
    ];
    $this->categories = [SEA_ANIMAL, REPTILE];
    $this->continents = [EUROPE];
    $this->ability = [SCUBA_DIVE => null, MARKETING => null];
    $this->wave = true;
  }
}
