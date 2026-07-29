<?php
namespace ARK\Cards\Animals;

class A437_Muskox extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A437_Muskox';
    $this->number = 437;
    $this->name = clienttranslate('Muskox');
    $this->latin = clienttranslate('Ovibos moschatus');
    $this->cost = 13;
    $this->appeal = 5;
    $this->enclosureSize = 4;
    $this->categories = [HERBIVORE];
    $this->continents = [AMERICAS];
    $this->prerequisites = [
      AMERICAS => 1,
    ];
    $this->ability = [SPONSOR_MAGNET => null];
  }
}
