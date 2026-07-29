<?php
namespace ARK\Cards\Animals;

class A463_PanamanianWhitefacedCapuchin extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A463_PanamanianWhitefacedCapuchin';
    $this->number = 463;
    $this->name = clienttranslate('Panamanian White-faced Capuchin');
    $this->latin = clienttranslate('Cebus imitator - Vulnerable');
    $this->cost = 11;
    $this->appeal = 5;
    $this->enclosureSize = 2;
    $this->categories = [PRIMATE];
    $this->continents = [AMERICAS];
    $this->prerequisites = [
      PARTNER_ZOO => 1,
    ];
    $this->ability = [PILFERING => 1];
    $this->soloAbility = [SPRINT => 1];
  }
}
