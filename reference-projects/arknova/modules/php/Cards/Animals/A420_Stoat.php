<?php
namespace ARK\Cards\Animals;

class A420_Stoat extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A420_Stoat';
    $this->number = 420;
    $this->name = clienttranslate('Stoat');
    $this->latin = clienttranslate('Mustela erminea');
    $this->cost = 4;
    $this->appeal = 3;
    $this->enclosureSize = 1;
    $this->categories = [PREDATOR];
    $this->continents = [EUROPE];
    $this->prerequisites = [
      EUROPE => 1,
    ];
    $this->ability = [HUNTER => 1];
  }
}
