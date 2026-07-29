<?php
namespace ARK\Cards\Animals;

class A465_GoldenLionTamarin extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A465_GoldenLionTamarin';
    $this->number = 465;
    $this->name = clienttranslate('Golden Lion Tamarin');
    $this->latin = clienttranslate('Leontopithecus rosalia - Endangered');
    $this->cost = 10;
    $this->appeal = 4;
    $this->enclosureSize = 1;
    $this->categories = [PRIMATE];
    $this->continents = [AMERICAS];
    $this->ability = [CLEVER => null];
  }
}
