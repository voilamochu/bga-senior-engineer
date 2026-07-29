<?php

namespace AGR\Cards\D;

use AGR\Models\Occupation;

class D164_PetGrower extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D164_PetGrower';
    $this->name = clienttranslate('Pet Grower');
    $this->deck = 'D';
    $this->number = 164;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [clienttranslate
    (
      'Each time you use an animal accumulation space, if afterward you have no animal in your house, you also get 1 <SHEEP>.'
    ),
    ];
    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer') &&
      ($event['actionCardType'] == 'SheepMarket' ||
        $event['actionCardType'] == 'PigMarket' ||
        $event['actionCardType'] == 'CattleMarket');
  }

  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    $zones = $player->board()->getAnimalsDropZonesWithAnimals();

    foreach ($zones as $zone) {
      if (in_array($zone['type'], ['room', 'D148_special'], true) && ($zone['animals'] > 0)) {
        return;
      }
    }
    return $this->gainNode([SHEEP => 1]);
  }
}
