<?php
namespace ARK\Cards\Animals;

class A512_EurasianEagleowl extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A512_EurasianEagleowl';
    $this->number = 512;
    $this->name = clienttranslate('Eurasian Eagle-owl');
    $this->latin = clienttranslate('Bubo bubo');
    $this->cost = 10;
    $this->appeal = 4;
    $this->enclosureSize = 2;
    $this->categories = [BIRD];
    $this->continents = [EUROPE];
    $this->prerequisites = [
      PARTNER_ZOO => 1,
    ];
    $this->ability = [PERCEPTION => 4];
  }
}
