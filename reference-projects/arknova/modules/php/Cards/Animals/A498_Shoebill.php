<?php

namespace ARK\Cards\Animals;

class A498_Shoebill extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A498_Shoebill';
    $this->number = 498;
    $this->name = clienttranslate('Shoebill');
    $this->latin = clienttranslate('Balaeniceps rex - Vulnerable');
    $this->cost = 9;
    $this->appeal = 3;
    $this->conservation = 1;
    $this->enclosureSize = 1;
    $this->specialEnclosure = [
      'types' => [LARGE_BIRD_AVIARY],
      'cubes' => 1,
    ];
    $this->categories = [BIRD];
    $this->continents = [AFRICA];
    $this->prerequisites = [
      SCIENCE => 2,
    ];
  }
}
