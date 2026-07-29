<?php

namespace ARK\Cards\Animals;

class A555_GoldenSnubnosedMonkey extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A555_GoldenSnubnosedMonkey';
    $this->number = 555;
    $this->name = clienttranslate('Golden Snub-nosed Monkey');
    $this->latin = clienttranslate('Rhinopithecus roxellana - Endangered');
    $this->cost = 18;
    $this->appeal = 6;
    $this->conservation = 1;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosureSize = 3;
    $this->categories = [PRIMATE];
    $this->continents = [ASIA];
    $this->prerequisites = [
      SCIENCE => 1,
    ];
    $this->ability = [PILFERING => 1];
    $this->soloAbility = [SPRINT => 1];
  }
}
