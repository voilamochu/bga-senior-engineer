<?php

namespace AGR\Cards\A;

use AGR\Managers\ActionCards;
use AGR\Managers\Players;
use AGR\Models\MinorImprovement;

class A28_ForestSchool extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A28_ForestSchool';
    $this->name = clienttranslate('Forest School');
    $this->deck = 'A';
    $this->number = 28;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'You can consider the __Lessons__ action spaces not occupied. You can replace each <FOOD> that an occupation costs with <WOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => '1',
      CLAY => '1',
    ];
    $this->isArtifexOrBubulcus = true;
  }

  public function onPlayerComputeCostsOccupation($player, &$args)
  {
    foreach ($args['costs']['trades'] as $trade) {
      if (isset($trade[FOOD])) {
        $food = $trade[FOOD];
        for ($i = 1; $i <= $food; $i++) {
          $trade[FOOD] -= $i;
          if ($trade[FOOD] <= 0) {
            unset($trade[FOOD]);
          }
          $trade[WOOD] = $i;
          $trade['sources'][] = $this->id;
          $args['costs']['trades'][] = $trade;
        }
      }
    }
  }

  public function onPlayerComputeArgsPlaceFarmer($player)
  {
    $added = [];

    $cards = ActionCards::getVisible($player)
      ->filter(function ($space) {
        return $space->getActionCardType() == 'Lessons';
      });

    foreach ($cards as $card) {
      $added[] = ['actionCardId' => $card->getId(), 'playerConstraint' => 'dummy'];
    }

    return $added;
  }
}