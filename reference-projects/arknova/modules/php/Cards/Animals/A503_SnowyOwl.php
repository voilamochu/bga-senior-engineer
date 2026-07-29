<?php
namespace ARK\Cards\Animals;

class A503_SnowyOwl extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A503_SnowyOwl';
    $this->number = 503;
    $this->name = clienttranslate('Snowy Owl');
    $this->latin = clienttranslate('Bubo scandiacus - Vulnerable');
    $this->cost = 11;
    $this->appeal = 4;
    $this->reputation = 1;
    $this->enclosureSize = 1;
    $this->categories = [BIRD];
    $this->continents = [ASIA];
    $this->prerequisites = [
      BIRD => 2,
    ];
    $this->ability = [PERCEPTION => 4];
  }
}
