<?php

namespace ARK\Cards\Animals;

class A425_TasmanianDevil extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A425_TasmanianDevil';
    $this->number = 425;
    $this->name = clienttranslate('Tasmanian Devil');
    $this->latin = clienttranslate('Sarcophilus harrisii - Endangered');
    $this->cost = 11;
    $this->appeal = 4;
    $this->reputation = 1;
    $this->enclosureSize = 1;
    $this->categories = [PREDATOR];
    $this->continents = [AUSTRALIA];
    $this->prerequisites = [
      SCIENCE => 1,
    ];
    $this->ability = [POUCH => 1];
  }
}
