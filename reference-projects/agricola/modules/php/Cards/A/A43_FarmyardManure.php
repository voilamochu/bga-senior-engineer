<?php

namespace AGR\Cards\A;

use AGR\Models\MinorImprovement;
use AGR\Helpers\CardRulings;

class A43_FarmyardManure extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A43_FarmyardManure';
    $this->name = clienttranslate('Farmyard Manure');
    $this->deck = 'A';
    $this->number = 43;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you build 1 or more stables in one turn, you place 1 <FOOD> on each of the next 3 round spaces. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->prerequisite = clienttranslate('1 Animal');
    $this->isArtifexOrBubulcus = true;

    $this->rulings = CardRulings::fromKeys([
      'OFFTURN_STABLES_DONT_TRIGGER',
    ]);
  }

  public function onBuy($player): ?array
  {
    $placeFood = $this->placeFoodNode($player);
    if (is_null($placeFood)) {
      $this->setUsedOnTurnId('');
      return null;
    } else {
      return $placeFood;
    }
  }

  private function placeFoodNode($player): ?array
  {
    $numStablesBuiltThisTurn = $this->numStablesBuiltThisTurn($player);
    if ($numStablesBuiltThisTurn >= 1) {
      return [
        'type' => NODE_SEQ,
        'optional' => false,
        'childs' => [
          $this->setUsedOnTurnIdNode(),
          $this->futureMeeplesNode([FOOD => 1], 3)
        ]
      ];
    }
    return null;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $sheep = $player->getExchangeResources()[SHEEP];
    $pigs = $player->getExchangeResources()[PIG];
    $cattle = $player->getExchangeResources()[CATTLE];

    if ($sheep == 0 && $pigs == 0 && $cattle == 0) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Stables') && $this->usableThisTurn();
  }

  public function onPlayerAfterStables($player, $event): ?array
  {
    return $this->placeFoodNode($player);
  }
}
