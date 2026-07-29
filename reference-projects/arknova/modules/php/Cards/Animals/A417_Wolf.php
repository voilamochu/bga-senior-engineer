<?php
namespace ARK\Cards\Animals;

class A417_Wolf extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A417_Wolf';
    $this->number = 417;
    $this->name = clienttranslate('Wolf');
    $this->latin = clienttranslate('Canis lupus');
    $this->cost = 12;
    $this->appeal = 4;
    $this->enclosureSize = 4;
    $this->categories = [PREDATOR];
    $this->continents = [EUROPE];
    $this->ability = [PACK => null];
  }
}
