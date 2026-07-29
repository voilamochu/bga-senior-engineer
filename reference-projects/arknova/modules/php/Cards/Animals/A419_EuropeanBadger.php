<?php
namespace ARK\Cards\Animals;

class A419_EuropeanBadger extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A419_EuropeanBadger';
    $this->number = 419;
    $this->name = clienttranslate('European Badger');
    $this->latin = clienttranslate('Meles meles');
    $this->cost = 5;
    $this->appeal = 3;
    $this->enclosureSize = 2;
    $this->categories = [PREDATOR];
    $this->continents = [EUROPE];
    $this->ability = [BOOST => ANIMALS];
  }
}
