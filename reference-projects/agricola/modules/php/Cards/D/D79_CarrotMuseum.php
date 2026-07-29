<?php

namespace AGR\Cards\D;

use AGR\Core\Globals;
use AGR\Models\MinorImprovement;

class D79_CarrotMuseum extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D79_CarrotMuseum';
    $this->name = clienttranslate('Carrot Museum');
    $this->deck = 'D';
    $this->number = 79;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At the end of rounds 8, 10, and 12, you get 1 <STONE> for each vegetable field you have and a number of <WOOD> equal to the number of <VEGETABLE> in your supply.'
      ),
    ];
    $this->vp = 2;
    $this->cost = [
      WOOD => 1,
      CLAY => 2,
    ];
    $this->prerequisite = clienttranslate('Play in Round 8 or Before');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $turn = Globals::getTurn();
    if ($turn > 8) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] === 'EndOfRound';
  }

  public function onPlayerEndOfRound($player, $event)
  {
    $turn = Globals::getTurn();
    if (!in_array($turn, [8, 10, 12])) {
      return null;
    }

    $vegFields = count($player->board()->getVegetableFields());
    $veg = $player->countReserveResource(VEGETABLE);

    if ($vegFields === 0 && $veg === 0) {
      return null;
    }

    $args = ['pId' => $this->pId];

    if ($vegFields != 0) {
      $args[STONE] = $vegFields;
    }

    if ($veg != 0) {
      $args[WOOD] = $veg;
    }

    return $this->gainNode($args);
  }
}
