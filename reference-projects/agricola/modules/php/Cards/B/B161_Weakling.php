<?php

namespace AGR\Cards\B;

use AGR\Managers\ActionCards;
use AGR\Managers\Meeples;
use AGR\Models\Occupation;

class B161_Weakling extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B161_Weakling';
    $this->name = clienttranslate('Weakling');
    $this->deck = 'B';
    $this->number = 161;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time it is your turn in the work phase, if there are one or more accumulation spaces with 5+ goods on them and you do not use any of them, you get 1 <VEGETABLE>.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
    $this->bannedStrong3or4p = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, null);
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    $spaces = ActionCards::getAccumulationSpaces();
    $flag = false;
    foreach ($spaces as $space) {
      if (Meeples::getResourcesOnCard($space->getId())->count() >= 5) {
        $flag = true;
        if ($space->getId() == $event['actionCardId']) {
          return;
        }
      }
    }
    if ($flag) {
      return $this->gainNode([VEGETABLE => 1]);
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
