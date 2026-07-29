<?php

namespace ARK\Cards\Animals;

class A529_MagnificentSeaAnemone extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'A529_MagnificentSeaAnemone';
    $this->number = 529;
    $this->name = clienttranslate('Magnificent Sea Anemone');
    $this->latin = clienttranslate('Heteractis magnifica');
    $this->cost = 6;
    $this->appeal = 3;
    $this->enclosureSize = 1;
    $this->noRegularEnclosure = true;
    $this->specialEnclosure = [
      'types' => [AQUARIUM],
      'cubes' => 1,
    ];
    $this->categories = [SEA_ANIMAL];
    $this->continents = [AUSTRALIA];
    $this->wave = true;
    $this->reefAbility = [MARK => 1];
  }
}
