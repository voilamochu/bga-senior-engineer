<?php

namespace ARK\Cards\Animals;

class A530_OrangeClownfish extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A530_OrangeClownfish';
    $this->number = 530;
    $this->name = clienttranslate('Orange Clownfish');
    $this->latin = clienttranslate('Amphiprion percula');
    $this->cost = 10;
    $this->appeal = 3;
    $this->enclosureSize = 1;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 1,
    ];
    $this->categories = [SEA_ANIMAL];
    $this->continents = [AUSTRALIA];
    $this->wave = true;
    $this->reefAbility = [APPEAL => 2];
  }
}
