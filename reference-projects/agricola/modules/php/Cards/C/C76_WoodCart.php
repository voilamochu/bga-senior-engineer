<?php

namespace AGR\Cards\C;

use AGR\Models\MinorImprovement;

class C76_WoodCart extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C76_WoodCart';
    $this->name = clienttranslate('Wood Cart');
    $this->deck = 'C';
    $this->number = 76;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [clienttranslate('Each time you use a wood accumulation space, you get 2 additional <WOOD>.')];
    $this->cost = [
      WOOD => 3,
    ];
    $this->prerequisite = clienttranslate('3 Occupations');
    $this->occupationPrerequisites = ['min' => 3];
  }

  public function isListeningTo($event)
  {
    return $this->isBeforeCollectEvent($event, WOOD);
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return $this->gainNode([WOOD => 2]);
  }
}
