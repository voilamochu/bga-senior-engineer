<?php
namespace ARK\Cards\Animals;

class A443_RedDeer extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A443_RedDeer';
    $this->number = 443;
    $this->name = clienttranslate('Red Deer');
    $this->latin = clienttranslate('Cervus elaphus');
    $this->cost = 12;
    $this->appeal = 5;
    $this->enclosureSize = 3;
    $this->categories = [HERBIVORE];
    $this->continents = [EUROPE];
    $this->ability = [FLOCK_ANIMAL => 3];
  }
}
