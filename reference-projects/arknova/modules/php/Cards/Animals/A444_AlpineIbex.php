<?php
namespace ARK\Cards\Animals;

class A444_AlpineIbex extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A444_AlpineIbex';
    $this->number = 444;
    $this->name = clienttranslate('Alpine Ibex');
    $this->latin = clienttranslate('Capra ibex');
    $this->cost = 10;
    $this->appeal = 5;
    $this->enclosureRequirements = [
      ROCK => 2,
    ];
    $this->enclosureSize = 2;
    $this->categories = [HERBIVORE];
    $this->continents = [EUROPE];
    $this->ability = [JUMPING => 2];
  }
}
