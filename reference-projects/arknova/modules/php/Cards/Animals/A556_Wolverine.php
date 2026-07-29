<?php

namespace ARK\Cards\Animals;

class A556_Wolverine extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A556_Wolverine';
    $this->number = 556;
    $this->name = clienttranslate('Wolverine');
    $this->latin = clienttranslate('Gulo gulo');
    $this->cost = 15;
    $this->appeal = 5;
    $this->reputation = 1;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosureSize = 3;
    $this->categories = [PREDATOR, BEAR];
    $this->continents = [EUROPE];
    $this->ability = [BOOST => ASSOCIATION];
  }
}
