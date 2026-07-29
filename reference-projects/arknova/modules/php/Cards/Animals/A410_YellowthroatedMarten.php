<?php
namespace ARK\Cards\Animals;

class A410_YellowthroatedMarten extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A410_YellowthroatedMarten';
    $this->number = 410;
    $this->name = clienttranslate('Yellow-throated Marten');
    $this->latin = clienttranslate('Martes flavigula');
    $this->cost = 7;
    $this->appeal = 3;
    $this->enclosureSize = 1;
    $this->categories = [PREDATOR];
    $this->continents = [ASIA];
    $this->ability = [HUNTER => 1];
  }
}
