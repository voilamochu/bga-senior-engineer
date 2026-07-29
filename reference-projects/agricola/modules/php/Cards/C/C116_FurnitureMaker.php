<?php

namespace AGR\Cards\C;

use AGR\Models\Occupation;

class C116_FurnitureMaker extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C116_FurnitureMaker';
    $this->name = clienttranslate('Furniture Maker');
    $this->deck = 'C';
    $this->number = 116;
    $this->isCorbariusOrDulcinaria = true;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <WOOD>. Each time you play an occupation after this one, you get 1 <WOOD> for each <FOOD> paid as occupation cost.'
      ),
    ];
    $this->players = '1+';
  }

  public function onBuy($player)
  {
    return $this->gainNode([WOOD => 1]);
  }

  public function isListeningTo($event)
  {
    return isset($event['sourceInfo']) &&
      isset($event['sourceInfo']['sourceAction']) &&
      $this->isActionEvent($event, 'Pay') &&
      $event['sourceInfo']['sourceAction'] == 'Occupation' && $event['sourceInfo']['cardId'] != $this->id;
  }

  public function onPlayerAfterPay($player, $event)
  {
    $cost = $event['cost'];
    $food = 0;
    if (isset($cost[FOOD]) && $cost[FOOD] != 0) {
      $food += $cost[FOOD];
    }
    if (isset($cost[FOOD_TRAVEL]) && $cost[FOOD_TRAVEL] != 0) {
      $food += $cost[FOOD_TRAVEL];
    }
    if ($food > 0) {
      return $this->gainNode([WOOD => $food]);
    }
  }
}
