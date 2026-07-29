<?php
namespace ARK\Cards\Animals;

class A408_SlothBear extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A408_SlothBear';
    $this->number = 408;
    $this->name = clienttranslate('Sloth Bear');
    $this->latin = clienttranslate('Melursus ursinus - Vulnerable');
    $this->cost = 14;
    $this->appeal = 6;
    $this->enclosureSize = 3;
    $this->categories = [PREDATOR, BEAR];
    $this->continents = [ASIA];
    $this->ability = [BOOST => ASSOCIATION];
  }
}
