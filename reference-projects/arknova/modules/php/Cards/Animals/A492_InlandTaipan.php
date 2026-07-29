<?php

namespace ARK\Cards\Animals;

class A492_InlandTaipan extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A492_InlandTaipan';
    $this->number = 492;
    $this->name = clienttranslate('Inland Taipan');
    $this->latin = clienttranslate('Oxyuranus microlepidotus');
    $this->cost = 10;
    $this->appeal = 5;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->enclosureSize = 2;
    $this->specialEnclosure = [
      'types' => [REPTILE_HOUSE],
      'cubes' => 1,
    ];
    $this->categories = [REPTILE];
    $this->continents = [AUSTRALIA];
    $this->prerequisites = [
      AUSTRALIA => 1,
      SCIENCE => 1,
    ];
    $this->ability = [VENOM => 2];
    $this->soloAbility = [INVENTIVE => 2];
  }

  public function getInventiveTokens()
  {
    return 2;
  }
}
