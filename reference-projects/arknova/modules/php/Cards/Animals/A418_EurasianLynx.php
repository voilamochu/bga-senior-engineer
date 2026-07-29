<?php
namespace ARK\Cards\Animals;

class A418_EurasianLynx extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A418_EurasianLynx';
    $this->number = 418;
    $this->name = clienttranslate('Eurasian Lynx');
    $this->latin = clienttranslate('Lynx lynx');
    $this->cost = 11;
    $this->appeal = 2;
    $this->enclosureSize = 3;
    $this->categories = [PREDATOR];
    $this->continents = [EUROPE];
    $this->prerequisites = [
      EUROPE => 2,
    ];
    $this->ability = [ICONIC_ANIMAL => EUROPE];
  }
}
