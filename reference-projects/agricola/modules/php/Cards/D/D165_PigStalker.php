<?php

namespace AGR\Cards\D;

use AGR\Helpers\Utils;
use AGR\Managers\ActionCards;
use AGR\Managers\Farmers;
use AGR\Models\Occupation;
use AGR\Helpers\CardRulings;

class D165_PigStalker extends Occupation
{
  protected $map = [
    1 => [2,],
    2 => [1, 3],
    3 => [2, 4],
    4 => [3,],
    8 => [9,],
    9 => [8,],
    10 => [11,],
    11 => [10,],
  ];

  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D165_PigStalker';
    $this->name = clienttranslate('Pig Stalker');
    $this->deck = 'D';
    $this->number = 165;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use an animal accumulation space, you get an additional 1 <PIG> if you occupy a Round 1-14 action space which is immediately to the left or right of that accumulation space.'
      ),
    ];

    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedWeak1or2p = true;
    $this->bannedWeak3or4p = true;

    $this->rulings = CardRulings::fromKeys([
      'UPDATED_DIGITAL',
    ]);
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'SheepMarket') ||
      $this->isActionCardEvent($event, 'PigMarket') ||
      $this->isActionCardEvent($event, 'CattleMarket');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    $animalMarketTurnNo = Utils::getActionCard($event['actionCardId'])->getTurn();

    $farmerLocations = [];
    foreach (Farmers::getAllOfPlayer($player->getId()) as $loc) {
      $farmerLocations[] = $loc['location'];
    }

    $cards = ActionCards::getVisible($player)
      ->filter(function ($space) use ($animalMarketTurnNo) {
        return in_array($space->getTurn(), $this->map[$animalMarketTurnNo]);
      });

    $actionCardNames = [];
    foreach ($cards as $card) {
      $actionCardNames[] = $card->getId();
    }

    if (!empty(array_intersect($farmerLocations, $actionCardNames))) {
      return $this->gainNode([PIG => 1]);
    }
  }
}
