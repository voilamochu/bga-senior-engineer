<?php

namespace ARK\Cards\Animals;

class A473_CommonAgama extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A473_CommonAgama';
    $this->number = 473;
    $this->name = clienttranslate('Common Agama');
    $this->latin = clienttranslate('Agama agama');
    $this->cost = 9;
    $this->appeal = 3;
    $this->enclosureSize = 1;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 0,
    ];
    $this->categories = [REPTILE];
    $this->continents = [AFRICA];
    $this->ability = [SUNBATHING => 2];
  }
}
