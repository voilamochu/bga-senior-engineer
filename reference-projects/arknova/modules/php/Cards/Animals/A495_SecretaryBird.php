<?php

namespace ARK\Cards\Animals;

class A495_SecretaryBird extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A495_SecretaryBird';
    $this->number = 495;
    $this->name = clienttranslate('Secretary Bird');
    $this->latin = clienttranslate('Sagittarius serpentarius - Endangered');
    $this->cost = 14;
    $this->appeal = 4;
    $this->conservation = 1;
    $this->enclosureSize = 4;
    $this->specialEnclosure = [
      'types' => [LARGE_BIRD_AVIARY],
      'cubes' => 1,
    ];
    $this->categories = [BIRD];
    $this->continents = [AFRICA];
  }
}
