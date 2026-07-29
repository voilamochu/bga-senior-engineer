<?php

namespace AGR\Cards\D;

use AGR\Models\Occupation;

class D109_SowingMaster extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D109_SowingMaster';
    $this->name = clienttranslate('Sowing Master');
    $this->deck = 'D';
    $this->number = 109;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <WOOD>. Each time after you use the __Grain Utilization__ or __Cultivation__ action space, you get 2 <FOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
  }

  public function onBuy($player)
  {
    return $this->gainNode([WOOD => 1]);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer') &&
      ($event['actionCardType'] == 'GrainUtilization' || $event['actionCardType'] == 'Cultivation');
  }

  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    return $this->gainNode([FOOD => 2]);
  }
}
