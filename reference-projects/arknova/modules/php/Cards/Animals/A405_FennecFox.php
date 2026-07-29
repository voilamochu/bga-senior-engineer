<?php
namespace ARK\Cards\Animals;

class A405_FennecFox extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A405_FennecFox';
    $this->number = 405;
    $this->name = clienttranslate('Fennec Fox');
    $this->latin = clienttranslate('Vulpes zerda');
    $this->cost = 8;
    $this->appeal = 3;
    $this->enclosureSize = 1;
    $this->categories = [PREDATOR];
    $this->continents = [AFRICA];
    $this->ability = [CLEVER => null];
  }
}
