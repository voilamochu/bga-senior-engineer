<?php
namespace ARK\Cards\Animals;

class A429_GrevysZebra extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A429_GrevysZebra';
    $this->number = 429;
    $this->name = clienttranslate("Grevy's Zebra");
    $this->latin = clienttranslate('Equus grevyi - Endangered');
    $this->cost = 12;
    $this->appeal = 6;
    $this->reputation = 1;
    $this->enclosureSize = 2;
    $this->categories = [HERBIVORE];
    $this->continents = [AFRICA];
    $this->prerequisites = [AFRICA => 3];
    $this->ability = [BOOST => SPONSORS, FLOCK_ANIMAL => 2];
  }
}
