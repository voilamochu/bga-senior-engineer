<?php
namespace ARK\Cards\Animals;

class A436_AmericanBison extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A436_AmericanBison';
    $this->number = 436;
    $this->name = clienttranslate('American Bison');
    $this->latin = clienttranslate('Bos bison - Near Threatened');
    $this->cost = 18;
    $this->appeal = 4;
    $this->enclosureSize = 5;
    $this->categories = [HERBIVORE];
    $this->continents = [AMERICAS, AMERICAS];
    $this->prerequisites = [
      AMERICAS => 3,
    ];
    $this->ability = [ICONIC_ANIMAL => AMERICAS];
  }
}
