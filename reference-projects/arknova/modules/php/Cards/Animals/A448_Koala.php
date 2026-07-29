<?php
namespace ARK\Cards\Animals;

class A448_Koala extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A448_Koala';
    $this->number = 448;
    $this->name = clienttranslate('Koala');
    $this->latin = clienttranslate('Phascolarctos cinereus - Vulnerable');
    $this->cost = 21;
    $this->appeal = 8;
    $this->reputation = 1;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosureSize = 3;
    $this->categories = [HERBIVORE, BEAR];
    $this->continents = [AUSTRALIA];
    $this->prerequisites = [
      AUSTRALIA => 1,
    ];
    $this->ability = [POUCH => 2];
  }
}
