<?php

namespace AGR\Cards\C;

use AGR\Helpers\Utils;
use AGR\Models\MinorImprovement;

class C42_RavenousHunger extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C42_RavenousHunger';
    $this->name = clienttranslate('Ravenous Hunger');
    $this->deck = 'C';
    $this->number = 42;
    $this->category = GOODS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Immediately after each time you use the __Vegetable Seeds__ action space, you can place another person on an accumulation space and get 1 additional good of the accumulating type.'
      ),
    ];
    $this->cost = [
      GRAIN => 1,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'VegetableSeeds', 'player', true) || $this->isCollectEvent($event);
  }

  public function onPlayerImmediatelyAfterPlaceFarmer($player, $args)
  {
    $cards = [
      'ActionCattleMarket',
      'ActionClayPit',
      'ActionCopse',
      'ActionCopseAdd',
      'ActionEasternQuarry',
      'ActionFishing',
      'ActionForest',
      'ActionForestSolo',
      'ActionGrove',
      'ActionHollow',
      'ActionHollow4',
      'ActionMeetingPlaceSolo',
      'ActionPigMarket',
      'ActionReedBank',
      'ActionSheepMarket',
      'ActionTravelingPlayers',
      'ActionWesternQuarry',
    ];

    return [
      'countAsUse' => true,
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->flagCardNode(),
        $this->setTurnIdNode(),
        [
          'action' => PLACE_FARMER,
          'pId' => $player->getId(),
          'args' => [
            'constraints' => $cards,
          ],
          'source' => $this->name,
        ],
        $this->unflagCardNode(),
      ],
    ];
  }

  public function onPlayerAfterCollect($player, $event)
  {
    if ($this->isFlagged()) {
      $flow = [];
      $actionCard = Utils::getActionCard($event['actionCardId']);
      $acc = $actionCard->getAccumulation();
      foreach ($acc as $resource => $amount) {
        $flow[] = [
          'action' => GAIN,
          'args' => [$resource => 1],
          'source' => $this->name,
        ];
      }
      return ['type' => NODE_SEQ, 'childs' => $flow];
    }
  }
}
