<?php
namespace ARK\Cards\Animals;

class A450_CommonWombat extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A450_CommonWombat';
    $this->number = 450;
    $this->name = clienttranslate('Common Wombat');
    $this->latin = clienttranslate('Vombatus ursinus');
    $this->cost = 9;
    $this->appeal = 4;
    $this->enclosureSize = 2;
    $this->categories = [HERBIVORE];
    $this->continents = [AUSTRALIA];
    $this->ability = [POUCH => 1];
  }
}
