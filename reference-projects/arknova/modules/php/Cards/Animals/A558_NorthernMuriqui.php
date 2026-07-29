<?php

namespace ARK\Cards\Animals;

class A558_NorthernMuriqui extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A558_NorthernMuriqui';
    $this->number = 558;
    $this->name = clienttranslate('Northern Muriqui');
    $this->latin = clienttranslate('Brachyteles hypoxanthus -Critically Endangered');
    $this->cost = 16;
    $this->appeal = 6;
    $this->conservation = 1;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosureSize = 4;
    $this->categories = [PRIMATE];
    $this->continents = [AMERICAS];
    $this->prerequisites = [UNIVERSITY => 1];
    $this->ability = [MONKEY_GANG => null];
  }
}
