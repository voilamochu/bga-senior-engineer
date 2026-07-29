<?php
namespace ARK\Cards\Animals;

class A516_NorhternCassowary extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A516_NorhternCassowary';
    $this->number = 516;
    $this->name = clienttranslate('Northern Cassowary');
    $this->latin = clienttranslate('Casuarius unappendiculatus');
    $this->cost = 12;
    $this->appeal = 6;
    $this->enclosureSize = 3;
    $this->categories = [BIRD];
    $this->continents = [AUSTRALIA];
    $this->prerequisites = [
      PARTNER_ZOO => 1,
    ];
    $this->ability = [MULTIPLIER => BUILD];
  }
}
