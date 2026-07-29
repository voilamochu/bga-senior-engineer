<?php
namespace ARK\Cards\Animals;

class A406_SiberianTiger extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A406_SiberianTiger';
    $this->number = 406;
    $this->name = clienttranslate('Siberian Tiger');
    $this->latin = clienttranslate('Panthera tigris altaica - Endangered');
    $this->cost = 30;
    $this->appeal = 10;
    $this->conservation = 2;
    $this->reputation = 1;
    $this->enclosureSize = 5;
    $this->categories = [PREDATOR];
    $this->continents = [ASIA];
    $this->prerequisites = [ASIA => 3];
  }
}
