<?php

namespace ARK\Cards\Animals;

use ARK\Managers\Players;

class A411_GrizzlyBear extends \ARK\Models\Animal
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A411_GrizzlyBear';
    $this->number = 411;
    $this->name = clienttranslate('Grizzly Bear');
    $this->latin = clienttranslate('Ursus arctos horribilis');
    $this->cost = 22;
    $this->appeal = 9;
    $this->enclosureSize = 5;
    $this->categories = [PREDATOR, BEAR];
    $this->continents = [AMERICAS];
    $this->prerequisites = [
      PREDATOR => 2,
      UPGRADED_ANIMALS_CARD => 1,
    ];
    $this->ability = [INVENTIVE => 0, FULL_THROATED => null];
  }

  public function getInventiveTokens()
  {
    $icons = 0;
    foreach (Players::getAll() as $pId => $player) {
      $icons += $player->countCardIcon(BEAR);
    }
    // maximum 3 XToken earned
    return min($icons, 3);
  }
}
