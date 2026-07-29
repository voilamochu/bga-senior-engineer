<?php
namespace ARK\Cards\Animals;

class A515_AustralianPelican extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A515_AustralianPelican';
    $this->number = 515;
    $this->name = clienttranslate('Australian Pelican');
    $this->latin = clienttranslate('Pelecanus conspicillatus');
    $this->cost = 13;
    $this->appeal = 5;
    $this->enclosureRequirements = [
      WATER => 2,
    ];
    $this->enclosureSize = 4;
    $this->categories = [BIRD];
    $this->continents = [AUSTRALIA];
    $this->ability = [ACTION => BUILD];
  }
}
