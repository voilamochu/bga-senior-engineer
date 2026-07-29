<?php
namespace ARK\Cards\Animals;

class A424_AustralianDingo extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A424_AustralianDingo';
    $this->number = 424;
    $this->name = clienttranslate('Australian Dingo');
    $this->latin = clienttranslate('Canis lupus dingo');
    $this->cost = 13;
    $this->appeal = 3;
    $this->enclosureSize = 2;
    $this->categories = [PREDATOR];
    $this->continents = [AUSTRALIA];
    $this->ability = [PACK => null];
  }
}
