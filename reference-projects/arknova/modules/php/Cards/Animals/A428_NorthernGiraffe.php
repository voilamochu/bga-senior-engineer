<?php
namespace ARK\Cards\Animals;

class A428_NorthernGiraffe extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A428_NorthernGiraffe';
    $this->number = 428;
    $this->name = clienttranslate('Northern Giraffe');
    $this->latin = clienttranslate('Giraffa camelopardalis - Vulnerable');
    $this->cost = 16;
    $this->appeal = 7;
    $this->enclosureSize = 3;
    $this->categories = [HERBIVORE];
    $this->continents = [AFRICA];
    $this->ability = [BOOST => SPONSORS];
  }
}
