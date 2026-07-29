<?php
namespace ARK\Cards\Animals;

class A461_HorsfieldsTarsier extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A461_HorsfieldsTarsier';
    $this->number = 461;
    $this->name = clienttranslate("Horsfield's Tarsier");
    $this->latin = clienttranslate('Cephalopachus bancanus - Vulnerable');
    $this->cost = 14;
    $this->appeal = 3;
    $this->reputation = 2;
    $this->enclosureSize = 1;
    $this->categories = [PRIMATE];
    $this->continents = [ASIA];
    $this->prerequisites = [
      PARTNER_ZOO => 1,
    ];
    $this->ability = [JUMPING => 4];
  }
}
