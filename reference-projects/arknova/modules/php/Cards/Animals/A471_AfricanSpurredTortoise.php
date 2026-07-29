<?php

namespace ARK\Cards\Animals;

class A471_AfricanSpurredTortoise extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A471_AfricanSpurredTortoise';
    $this->number = 471;
    $this->name = clienttranslate('African Spurred Tortoise');
    $this->latin = clienttranslate('Centrochelys sulcata - Endangered');
    $this->cost = 22;
    $this->appeal = 6;
    $this->conservation = 1;
    $this->enclosureSize = 3;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 2,
    ];
    $this->categories = [REPTILE];
    $this->continents = [AFRICA];
    $this->ability = [SUNBATHING => 3];
  }
}
