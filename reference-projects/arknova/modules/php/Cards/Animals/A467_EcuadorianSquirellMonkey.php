<?php
namespace ARK\Cards\Animals;

class A467_EcuadorianSquirellMonkey extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A467_EcuadorianSquirellMonkey';
    $this->number = 467;
    $this->name = clienttranslate('Ecuadorian Squirrel Monkey');
    $this->latin = clienttranslate('Saimiri macrodon');
    $this->cost = 12;
    $this->appeal = 5;
    $this->enclosureSize = 2;
    $this->categories = [PRIMATE];
    $this->continents = [AMERICAS];
    $this->ability = [CLEVER => null];
  }
}
