<?php
namespace ARK\Cards\Animals;

class A423_NewZealandSeaLion extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A423_NewZealandSeaLion';
    $this->number = 423;
    $this->name = clienttranslate('New Zealand Sea Lion');
    $this->latin = clienttranslate('Phocarctos hookeri - Endangered');
    $this->cost = 17;
    $this->appeal = 6;
    $this->enclosureRequirements = [
      WATER => 1,
    ];
    $this->enclosureSize = 3;
    $this->categories = [PREDATOR];
    $this->continents = [AUSTRALIA];
    $this->ability = [PACK => null];
  }
}
