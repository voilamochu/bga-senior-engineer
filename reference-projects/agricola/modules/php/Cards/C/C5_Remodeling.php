<?php

namespace AGR\Cards\C;

use AGR\Models\MinorImprovement;

class C5_Remodeling extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C5_Remodeling';
    $this->name = clienttranslate('Remodeling');
    $this->deck = 'C';
    $this->number = 5;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate('You immediately get 1 <CLAY> for each clay room and for each major improvement you have.'),
    ];
    $this->passing = true;
    $this->cost = [
      FOOD => 1,
    ];
    $this->isCorbariusOrDulcinaria = true;
  }

  public function onBuy($player)
  {
    $majors = $player->getCards(MAJOR, true)->count();
    $rooms = $player->getRoomType() == 'roomClay' ? $player->countRooms() : 0;

    if ($majors + $rooms > 0) {
      return $this->gainNode([CLAY => $majors + $rooms]);
    }
  }
}
