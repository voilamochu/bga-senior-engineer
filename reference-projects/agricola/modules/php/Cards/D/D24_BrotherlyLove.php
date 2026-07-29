<?php

namespace AGR\Cards\D;

use AGR\Core\Globals;
use AGR\Managers\ActionCards;
use AGR\Managers\Farmers;
use AGR\Models\MinorImprovement;

class D24_BrotherlyLove extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D24_BrotherlyLove';
    $this->name = clienttranslate('Brotherly Love');
    $this->deck = 'D';
    $this->number = 24;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'As long as you have exactly 4 people, in the work phase of each round, you can place your third and fourth person immediately after one another, even on the same action space.'
      ),
    ];
    $this->cost = [
      FOOD => 1,
    ];
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    $player = $this->getPlayer();

    return $this->isActionEvent($event, 'PlaceFarmer') &&
      $player->countFarmers() == 4 &&
      $player->countPlacedFarmers() == 3;
  }

  public function onPlayerAfterPlaceFarmer($player, $args)
  {
    return [
      'countAsUse' => true,
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->flagCardNode(),
        $this->setTurnIdNode(),
        [
          'action' => PLACE_FARMER,
        ],
        $this->unflagCardNode(),
      ],
    ];
  }

  public function onPlayerComputeArgsPlaceFarmer($player)
  {
    $added = [];

    $cards = ActionCards::getVisible($player);
    foreach ($cards as $card) {
      if ($this->checkCondition($card->getId())) {
        $added[] = ['actionCardId' => $card->getId(), 'playerConstraint' => 'dummy'];
      }
    }

    return $added;
  }

  public function checkCondition($cId)
  {
    $player = $this->getPlayer();

    if (!$this->isFlagged()) {
      return false;
    }

    $farmers = Farmers::getOnCard($cId);
    foreach ($farmers as $farmer) {
      if ($farmer['pId'] != $player->getId()) {
        return false;
      }

      if ($farmer['pId'] == $player->getId()) {
        $placed = Globals::getPlacedFarmers()[$player->getId()];
        if ($placed[2] != $farmer['id']) {
          return false;
        }
      }
    }

    return true;
  }
}
