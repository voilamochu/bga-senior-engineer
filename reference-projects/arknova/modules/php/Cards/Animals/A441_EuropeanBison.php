<?php
namespace ARK\Cards\Animals;

class A441_EuropeanBison extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A441_EuropeanBison';
    $this->number = 441;
    $this->name = clienttranslate('European Bison');
    $this->latin = clienttranslate('Bos bonasus - Near Threatened');
    $this->cost = 19;
    $this->appeal = 6;
    $this->enclosureSize = 5;
    $this->categories = [HERBIVORE];
    $this->continents = [EUROPE, EUROPE];
    $this->prerequisites = [
      PARTNER_ZOO => 1,
    ];
    $this->ability = [SPONSOR_MAGNET => null];
  }
}
