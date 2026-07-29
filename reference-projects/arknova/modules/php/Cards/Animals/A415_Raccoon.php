<?php
namespace ARK\Cards\Animals;

class A415_Raccoon extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A415_Raccoon';
    $this->number = 415;
    $this->name = clienttranslate('Raccoon');
    $this->latin = clienttranslate('Procyon lotor');
    $this->cost = 11;
    $this->appeal = 4;
    $this->enclosureSize = 1;
    $this->categories = [PREDATOR, BEAR];
    $this->continents = [AMERICAS];
    $this->ability = [BOOST => ASSOCIATION];
  }
}
