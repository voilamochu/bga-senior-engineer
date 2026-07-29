<?php
namespace ARK\Cards\Animals;

class A445_CrestedPorcupine extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A445_CrestedPorcupine';
    $this->number = 445;
    $this->name = clienttranslate('Crested Porcupine');
    $this->latin = clienttranslate('Hystric cristata');
    $this->cost = 8;
    $this->appeal = 3;
    $this->enclosureSize = 1;
    $this->categories = [HERBIVORE];
    $this->continents = [EUROPE];
    $this->ability = [DIGGING => 2];
  }
}
