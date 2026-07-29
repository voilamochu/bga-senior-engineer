<?php

namespace ARK\Cards\Animals;

class A491_FrilledLizard extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A491_FrilledLizard';
    $this->number = 491;
    $this->name = clienttranslate('Frilled Lizard');
    $this->latin = clienttranslate('Chlamydosaurus kingii');
    $this->cost = 12;
    $this->appeal = 5;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosureSize = 2;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 1,
    ];
    $this->categories = [REPTILE];
    $this->continents = [AUSTRALIA];
    $this->ability = [SPRINT => 1];
  }
}
