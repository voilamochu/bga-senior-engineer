<?php
namespace ARK\Cards\Animals;

class A466_BolivianRedHowler extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A466_BolivianRedHowler';
    $this->number = 466;
    $this->name = clienttranslate('Bolivian Red Howler');
    $this->latin = clienttranslate('Alouatta sara - Near Threatened');
    $this->cost = 12;
    $this->appeal = 6;
    $this->enclosureSize = 3;
    $this->categories = [PRIMATE];
    $this->continents = [AMERICAS];
    $this->ability = [BOOST => CARDS];
  }
}
