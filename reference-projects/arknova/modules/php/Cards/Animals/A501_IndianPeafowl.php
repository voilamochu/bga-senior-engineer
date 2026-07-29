<?php
namespace ARK\Cards\Animals;

class A501_IndianPeafowl extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A501_IndianPeafowl';
    $this->number = 501;
    $this->name = clienttranslate('Indian Peafowl');
    $this->latin = clienttranslate('Pavo cristatus');
    $this->cost = 18;
    $this->appeal = 7;
    $this->enclosureSize = 3;
    $this->categories = [BIRD];
    $this->continents = [ASIA];
    $this->prerequisites = [
      ASIA => 1,
    ];
    $this->ability = [POSTURING => 2];
  }
}
