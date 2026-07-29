<?php

namespace AGR\Cards\D;

use AGR\Core\Notifications;
use AGR\Managers\PlayerCards;
use AGR\Helpers\CardRulings;

class D27_Retraining extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D27_Retraining';
    $this->name = clienttranslate('Retraining');
    $this->deck = 'D';
    $this->number = 27;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'At the end of each turn in which you renovate, you can exchange your __Joinery__ for the __Pottery__ or your __Pottery__ for the __Basketmaker\'s Workshop__.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      FOOD => '1',
    ];
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];
    $this->isCorbariusOrDulcinaria = true;

    $this->rulings = CardRulings::fromKeys([
      'TURN_MEANS_PERSON_ACTION',
    ]);
  }

  public function isListeningTo($event)
  {
    return ($this->isActionEvent($event, 'Renovation') && !$this->isFlagged()) || $this->isActionEvent($event, 'PlaceFarmer');
  }

  public function onPlayerAfterRenovation($player, $event)
  {
    return $this->flagCardNode();
  }

  public function onPlayerAfterPlaceFarmer($player, $args)
  {
    if (!$this->isFlagged()) {
      return;
    }
    $joineryPlayed = $player->hasPlayedCard('Major_Joinery');
    $potteryPlayed = $player->hasPlayedCard('Major_Pottery');
    $notPlayedMajors = PlayerCards::getAvailables(MAJOR)->filter(function ($card) {
      return !$card->isPlayed();
    });
    $potteryCanBuy = $notPlayedMajors->reduce(function ($carry, $card) {
      return $carry || $card->getId() == 'Major_Pottery';
    }, false);
    $basketCanBuy = $notPlayedMajors->reduce(function ($carry, $card) {
      return $carry || $card->getId() == 'Major_Basket';
    }, false);
    $option = 0;
    if ($joineryPlayed && $potteryCanBuy) {
      $option = 1;
    }
    if ($potteryPlayed && $basketCanBuy) {
      $option = 2;
    }
    if ($option == 0) {
      return $this->unflagCardNode();
    }
    return [
      'countAsUse' => true,
      'type' => NODE_SEQ,
      'childs' => [
        [
          'type' => NODE_SEQ,
          'optional' => true,
          'childs' => [
            [
              'action' => SPECIAL_EFFECT,
              'args' => [
                'cardId' => $this->id,
                'method' => 'retrainingEffect',
                'args' => [
                  $option == 1 ? 'Major_Joinery' : 'Major_Pottery',
                  $option == 1 ? 'Major_Pottery' : 'Major_Basket',
                ],
              ],
            ]
          ]
        ],
        $this->unflagCardNode(),
      ]
    ];
  }

  public function getRetrainingEffectDescription()
  {
    return clienttranslate('Retraining Effect');
  }

  public function retrainingEffect($cost, $get)
  {
    $player = $this->getPlayer();
    $costCard = PlayerCards::getCardInstance($cost);
    $getCard = PlayerCards::getCardInstance($get);
    $costCard->resetStats();
    $costCard->returnToBoard();
    Notifications::payWithCard($player, $costCard, $getCard->getName());
    $getCard->giveToPlayer($player->getId());
    Notifications::buyCard($getCard, $player);
  }
}
