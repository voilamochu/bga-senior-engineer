<?php

namespace AGR\Cards\A;

use AGR\Core\Globals;
use AGR\Models\MinorImprovement;

class A20_DoubleTurnPlow extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A20_DoubleTurnPlow';
    $this->name = clienttranslate('Double-Turn Plow');
    $this->deck = 'A';
    $this->number = 20;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate('When you play this card, you can immediately plow up to 2 fields.')
    ];
    $this->cost = [GRAIN => '1'];
    $this->conditionalCost = [FOOD => '1'];
    $this->prerequisite = clienttranslate('Play in Round 3 (5) or Before');
    $this->isArtifexOrBubulcus = true;
  }

  public function getBaseCosts()
  {
    return [[
      GRAIN => 1,
      FOOD => Globals::getTurn() > 3 ? 1 : 0,
    ]];
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if (Globals::getTurn() > 5) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => PLOW,
          'optional' => true,
          'args' => ['source' => $this->name, 'cardId' => $this->id],
        ],
        [
          'action' => PLOW,
          'optional' => true,
          'args' => ['source' => $this->name, 'cardId' => $this->id],
        ]
      ]
    ];
  }
}
