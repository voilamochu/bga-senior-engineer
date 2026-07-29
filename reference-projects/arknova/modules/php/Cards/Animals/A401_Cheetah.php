<?php
namespace ARK\Cards\Animals;

class A401_Cheetah extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A401_Cheetah';
    $this->number = 401;
    $this->name = clienttranslate('Cheetah');
    $this->latin = clienttranslate('Acinonyx jubatus - Vulnerable');
    $this->cost = 17;
    $this->appeal = 6;
    $this->enclosureSize = 5;
    $this->categories = [PREDATOR, PREDATOR];
    $this->continents = [AFRICA];
    $this->ability = [SPRINT => 3];
  }
}
