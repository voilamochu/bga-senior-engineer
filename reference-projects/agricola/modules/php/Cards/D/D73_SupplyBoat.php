<?php

namespace AGR\Cards\D;

use AGR\Models\MinorImprovement;

class D73_SupplyBoat extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D73_SupplyBoat';
    $this->name = clienttranslate('Supply Boat');
    $this->deck = 'D';
    $this->number = 73;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time after you use the __Fishing__ accumulation space, you can choose to buy 1 <GRAIN> for 1 <FOOD>, or 1 <VEGETABLE> for 3 <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 1,
    ];
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];
  }

  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event, FOOD) &&
      $event['actionCardId'] == 'ActionFishing';
  }

  public function onPlayerAfterCollect($player, $event)
  {
    $pId = $this->getPlayer()->getId();
    return [
      'type' => NODE_XOR,
      'pId' => $pId,
      'optional' => true,
      'doableRequiresChild' => true,
      'childs' => [
        $this->payGainNode([FOOD => 1], [GRAIN => 1], null, false),
        $this->payGainNode([FOOD => 3], [VEGETABLE => 1], null, false),
      ]
    ];
  }
}
