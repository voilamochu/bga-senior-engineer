<?php
namespace ARK\Cards\Animals;

class A456_BarbaryMacaque extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A456_BarbaryMacaque';
    $this->number = 456;
    $this->name = clienttranslate('Barbary Macaque');
    $this->latin = clienttranslate('Macaca sylvanus - Endangered');
    $this->cost = 13;
    $this->appeal = 6;
    $this->enclosureSize = 2;
    $this->categories = [PRIMATE];
    $this->continents = [AFRICA];
    $this->prerequisites = [
      PARTNER_ZOO => 1,
    ];
    $this->ability = [PILFERING => 1];
    $this->soloAbility = [SPRINT => 1];
  }
}
