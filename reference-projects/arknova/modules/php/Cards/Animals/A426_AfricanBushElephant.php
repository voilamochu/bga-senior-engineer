<?php
namespace ARK\Cards\Animals;

class A426_AfricanBushElephant extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A426_AfricanBushElephant';
    $this->number = 426;
    $this->name = clienttranslate('African Bush Elephant');
    $this->latin = clienttranslate('Loxodonta africana - Endangered');
    $this->cost = 36;
    $this->appeal = 10;
    $this->reputation = 1;
    $this->enclosureSize = 5;
    $this->categories = [HERBIVORE];
    $this->continents = [AFRICA, AFRICA];
    $this->prerequisites = [
      UPGRADED_ANIMALS_CARD => 1,
    ];
    $this->ability = [RESISTANCE => null];
  }
}
