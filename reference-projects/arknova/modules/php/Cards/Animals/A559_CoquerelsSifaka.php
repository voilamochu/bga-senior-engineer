<?php

namespace ARK\Cards\Animals;

class A559_CoquerelsSifaka extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A559_CoquerelsSifaka';
    $this->number = 559;
    $this->name = clienttranslate("Coquerel's Sifaka");
    $this->latin = clienttranslate('Propithecus coquereli - Endangered');
    $this->cost = 17;
    $this->appeal = 7;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosureSize = 3;
    $this->categories = [PRIMATE];
    $this->continents = [AFRICA];
    $this->ability = [MONKEY_GANG => null, JUMPING => 2];
  }
}
