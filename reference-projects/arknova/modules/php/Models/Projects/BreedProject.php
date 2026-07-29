<?php

namespace ARK\Models\Projects;

class BreedProject extends Project
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->category = PROJECT_BREED;
    $this->slots = [
      ['gain' => [CONSERVATION => 2, \REPUTATION => 2]],
      ['gain' => [CONSERVATION => 1, \REPUTATION => 2]],
      ['gain' => [CONSERVATION => 2]],
    ];
  }

  public function canPlaySlot($player, $slot, $bonusIcon = 0)
  {
    $cards = $player->getPlayedAnimal($this->icon);
    foreach ($cards as $cId => $card) {
      $continent = $card->getContinent();
      if ($player->hasPartnerZoo($continent)) {
        return true;
      }
    }

    return false;
  }
}
