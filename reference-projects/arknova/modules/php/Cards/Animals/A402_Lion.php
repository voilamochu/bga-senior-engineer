<?php
namespace ARK\Cards\Animals;

class A402_Lion extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A402_Lion';
    $this->number = 402;
    $this->name = clienttranslate('Lion');
    $this->latin = clienttranslate('Panthera leo - Vulnerable');
    $this->cost = 16;
    $this->appeal = 9;
    $this->enclosureSize = 4;
    $this->categories = [PREDATOR];
    $this->continents = [AFRICA];
    $this->prerequisites = [PREDATOR => 3];
    $this->ability = [PACK => null];
  }
}
