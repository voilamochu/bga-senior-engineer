<?php
namespace ARK\Cards\Animals;

class A502_GreatHornbill extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A502_GreatHornbill';
    $this->number = 502;
    $this->name = clienttranslate('Great Hornbill');
    $this->latin = clienttranslate('Buceros bicornis - Vulnerable');
    $this->cost = 13;
    $this->appeal = 6;
    $this->enclosureSize = 2;
    $this->categories = [BIRD];
    $this->continents = [ASIA];
    $this->ability = [BOOST => BUILD];
  }
}
