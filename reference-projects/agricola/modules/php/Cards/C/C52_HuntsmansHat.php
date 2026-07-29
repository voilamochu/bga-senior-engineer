<?php

namespace AGR\Cards\C;

use AGR\Managers\Meeples;
use AGR\Models\MinorImprovement;

class C52_HuntsmansHat extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C52_HuntsmansHat';
    $this->name = clienttranslate("Huntsman's Hat");
    $this->deck = 'C';
    $this->number = 52;
    $this->category = FOOD_PROVIDER;
    $this->desc = [clienttranslate('For each new <PIG> you get from the effect of an action space, you also get 1 <FOOD>.'),
    ];
    $this->vp = 1;
    $this->cost = [
      REED => '1',
    ];
    $this->prerequisite = clienttranslate('Cooking Improvement');
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if (!$player->canCook()) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onPlayerComputePlaceFarmerFlow($player, &$args)
  {
    if ($args['actionCardType'] == 'AnimalMarket') {
      $args['flow'] = [
        'type' => NODE_XOR,
        'pId' => $player->getId(),
        'childs' => [
          $this->gainNode([SHEEP => 1, FOOD => 1]),
          $this->gainNode([PIG => 1, FOOD => 1]),
          $this->payGainNode([FOOD => 1], [CATTLE => 1], $this->name, false)
        ],
      ];
    } elseif ($args['actionCardType'] == 'PigMarket') {
      $n = Meeples::getResourcesOnCard('ActionPigMarket', null, PIG)->count();
      $args['flow'] = [
        'type' => NODE_SEQ,
        'childs' => [
          $args['flow'],
          $this->gainNode([FOOD => $n]),
        ],
      ];
    }
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Gain') && ($event['fromActionSpace'] ?? false);
  }

  public function onPlayerAfterGain($player, $event)
  {
    $n = 0;
    foreach ($event['meeples'] as $meeple) {
      if ($meeple['type'] == PIG) {
        $n++;
      }
    }
    if ($n > 0) {
      return $this->gainNode([FOOD => $n]);
    }
  }
}
