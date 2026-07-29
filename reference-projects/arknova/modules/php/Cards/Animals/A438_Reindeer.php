<?php
namespace ARK\Cards\Animals;

class A438_Reindeer extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A438_Reindeer';
    $this->number = 438;
    $this->name = clienttranslate('Reindeer');
    $this->latin = clienttranslate('Rangifer tarandus - Vulnerable');
    $this->cost = 12;
    $this->appeal = 5;
    $this->enclosureSize = 3;
    $this->categories = [HERBIVORE];
    $this->continents = [AMERICAS];
    $this->ability = [FLOCK_ANIMAL => 3];
  }
}
