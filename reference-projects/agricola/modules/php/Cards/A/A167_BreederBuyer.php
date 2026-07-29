<?php

namespace AGR\Cards\A;

use AGR\Models\Occupation;

class A167_BreederBuyer extends Occupation
{
  protected $map = [
    'roomWood' => SHEEP,
    'roomClay' => PIG,
    'roomStone' => CATTLE,
  ];

  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A167_BreederBuyer';
    $this->name = clienttranslate('Breeder Buyer');
    $this->deck = 'A';
    $this->number = 167;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you build at least 1 wood/clay/stone room and at least 1 stable on the same turn, you also get 1 <SHEEP>/<PIG>/<CATTLE>.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
    $this->bannedWeak3or4p = true;
  }

  public function onBuy($player): ?array
  {
    $roomType = $this->roomTypeBuiltThisTurn($player);
    if ($this->numStablesBuiltThisTurn($player) > 0 && !is_null($roomType)) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->setUsedOnTurnIdNode(),
          $this->gainNode([$this->map[$roomType] => 1])
        ]
      ];
    } else {
      $this->setUsedOnTurnId('');
      return null;
    }
  }

  public function isListeningTo($event): bool
  {
    return ($this->isActionEvent($event, 'Stables') || $this->isActionEvent($event, 'Construct')) &&
      $this->usableThisTurn();
  }

  public function onPlayerAfterConstruct($player, $event): ?array
  {
    if ($this->numStablesBuiltThisTurn($player) > 0) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->setUsedOnTurnIdNode(),
          $this->gainNode([$this->map[$event['roomType']] => 1])
        ]
      ];
    }
    return null;
  }

  public function onPlayerAfterStables($player, $event): ?array
  {
    $roomType = $this->roomTypeBuiltThisTurn($player);
    if (!is_null($roomType)) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->setUsedOnTurnIdNode(),
          $this->gainNode([$this->map[$roomType] => 1])
        ]
      ];
    }
    return null;
  }
}
