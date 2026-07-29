<?php

namespace ARK\Cards\Animals;

class A537_BlackbarTriggerfish extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A537_BlackbarTriggerfish';
    $this->number = 537;
    $this->name = clienttranslate('Blackbar Triggerfish');
    $this->latin = clienttranslate('Rhinecanthus aculeatus');
    $this->cost = 14;
    $this->appeal = 5;
    $this->enclosureSize = 2;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 1,
    ];
    $this->categories = [SEA_ANIMAL];
    $this->continents = [AFRICA];
    $this->prerequisites = [UNIVERSITY => 1];
    $this->wave = true;
    $this->reefAbility = [TAKE_IN_RANGE_OR_DECK => 1];
    $this->ability = [CONSTRICTION => null];
    $this->soloAbility = [CLEVER => null];
  }
}
