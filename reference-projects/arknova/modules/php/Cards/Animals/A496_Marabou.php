<?php

namespace ARK\Cards\Animals;

class A496_Marabou extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A496_Marabou';
    $this->number = 496;
    $this->name = clienttranslate('Marabou');
    $this->latin = clienttranslate('Leptoptilos crumeniferus');
    $this->cost = 10;
    $this->appeal = 4;
    $this->enclosureSize = 3;
    $this->specialEnclosure = [
      'types' => [LARGE_BIRD_AVIARY],
      'cubes' => 1,
    ];
    $this->categories = [BIRD];
    $this->continents = [AFRICA];
    $this->ability = [SCAVENGING => 2];
  }
}
