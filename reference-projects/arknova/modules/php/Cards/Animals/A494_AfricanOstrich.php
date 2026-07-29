<?php

namespace ARK\Cards\Animals;

class A494_AfricanOstrich extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A494_AfricanOstrich';
    $this->number = 494;
    $this->name = clienttranslate('African Ostrich');
    $this->latin = clienttranslate('Struthio camelus');
    $this->cost = 20;
    $this->appeal = 8;
    $this->enclosureSize = 5;
    $this->specialEnclosure = [
      'types' => [LARGE_BIRD_AVIARY],
      'cubes' => 4,
    ];
    $this->categories = [BIRD];
    $this->continents = [AFRICA];
    $this->ability = [SPRINT => 2];
  }
}
