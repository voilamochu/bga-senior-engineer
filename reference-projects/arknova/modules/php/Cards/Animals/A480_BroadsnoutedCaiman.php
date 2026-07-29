<?php

namespace ARK\Cards\Animals;

class A480_BroadsnoutedCaiman extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A480_BroadsnoutedCaiman';
    $this->number = 480;
    $this->name = clienttranslate('Broad-snouted Caiman');
    $this->latin = clienttranslate('Caiman latirostris');
    $this->cost = 16;
    $this->appeal = 6;
    $this->enclosureRequirements = [
      WATER => 1,
    ];
    $this->enclosureSize = 4;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 2,
    ];
    $this->categories = [REPTILE];
    $this->continents = [AMERICAS];
    $this->ability = [SNAPPING => 1];
  }
}
