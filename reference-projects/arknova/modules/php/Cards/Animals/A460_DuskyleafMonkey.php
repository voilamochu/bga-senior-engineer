<?php
namespace ARK\Cards\Animals;

class A460_DuskyleafMonkey extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A460_DuskyleafMonkey';
    $this->number = 460;
    $this->name = clienttranslate('Dusky-leaf Monkey');
    $this->latin = clienttranslate('Trachypithecus obscurus - Endangered');
    $this->cost = 12;
    $this->appeal = 5;
    $this->enclosureSize = 2;
    $this->categories = [PRIMATE];
    $this->continents = [ASIA];
    $this->ability = [CLEVER => null];
  }
}
