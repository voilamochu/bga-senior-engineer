<?php

namespace ARK\Cards\Animals;

class A470_WesternGreenMamba extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A470_WesternGreenMamba';
    $this->number = 470;
    $this->name = clienttranslate('Western Green Mamba');
    $this->latin = clienttranslate('Dendroaspis viridis');
    $this->cost = 13;
    $this->appeal = 6;
    $this->enclosureSize = 2;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 1,
    ];
    $this->categories = [REPTILE];
    $this->continents = [AFRICA];
    $this->prerequisites = [
      AFRICA => 1,
      REPTILE => 1,
    ];
    $this->ability = [VENOM => 2];
    $this->soloAbility = [INVENTIVE => 2];
  }

  public function getInventiveTokens()
  {
    return 2;
  }
}
