<?php

namespace ARK\Cards\Animals;

class A459_RedshankedDouc extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A459_RedshankedDouc';
    $this->number = 459;
    $this->name = clienttranslate('Red-shanked Douc');
    $this->latin = clienttranslate('Pygathrix nemaeus - Critically Endangered');
    $this->cost = 17;
    $this->appeal = 7;
    $this->reputation = 1;
    $this->enclosureSize = 4;
    $this->categories = [PRIMATE];
    $this->continents = [ASIA];
    $this->prerequisites = [
      SCIENCE => 1,
    ];
    $this->ability = [INVENTIVE => 0];
  }

  public function getInventiveTokens()
  {
    $icons = $this->getPlayer()->countCardIcon(PRIMATE);
    $map = [0 => 1, 1 => 1, 2 => 1, 3 => 2, 4 => 2];
    return $map[$icons] ?? 3;
  }
}
