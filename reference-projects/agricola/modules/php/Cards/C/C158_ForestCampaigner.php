<?php

namespace AGR\Cards\C;

use AGR\Managers\ActionCards;
use AGR\Managers\Meeples;
use AGR\Models\Occupation;

class C158_ForestCampaigner extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C158_ForestCampaigner';
    $this->name = clienttranslate('Forest Campaigner');
    $this->deck = 'C';
    $this->number = 158;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time before you place a person, if there are at least 8 <WOOD> total on accumulation spaces, you get 1 <FOOD>.'
      ),
    ];
    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedWeak3or4p = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, null);
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    $count_wood = 0;
    $spaces = ActionCards::getAccumulationSpaces(WOOD);
    foreach ($spaces as $space) {
      $count_wood = $count_wood + Meeples::getResourcesOnCard($space->getId())->count();
    }
    if ($count_wood >= 8) {
      return $this->gainNode([FOOD => 1]);
    }
  }

  public function onPlayerComputeArgsPlaceFarmer($player)
  {
    $added = [];

    $cards = ActionCards::getVisible($player);

    foreach ($cards as $card) {
      $added[] = ['actionCardId' => $card->getId(), 'ignoreResources' => true];
    }
    return $added;
  }
}
