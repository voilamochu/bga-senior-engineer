<?php
namespace ARK\Cards\Animals;

class A404_Caracal extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A404_Caracal';
    $this->number = 404;
    $this->name = clienttranslate('Caracal');
    $this->latin = clienttranslate('Caracal caracal');
    $this->cost = 9;
    $this->appeal = 4;
    $this->enclosureSize = 2;
    $this->categories = [PREDATOR];
    $this->continents = [AFRICA];
    $this->ability = [HUNTER => 2];
  }
}
