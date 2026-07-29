<?php
namespace ARK\Cards\Animals;

class A422_AustralianSeaLion extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A422_AustralianSeaLion';
    $this->number = 422;
    $this->name = clienttranslate('Australian Sea Lion');
    $this->latin = clienttranslate('Neophoca cinerea - Endangered');
    $this->cost = 18;
    $this->appeal = 7;
    $this->conservation = 1;
    $this->enclosureRequirements = [
      WATER => 1,
    ];
    $this->enclosureSize = 4;
    $this->categories = [PREDATOR];
    $this->continents = [AUSTRALIA];
    $this->prerequisites = [
      PARTNER_ZOO => 1,
    ];
    $this->ability = [SUNBATHING => 3];
  }
}
