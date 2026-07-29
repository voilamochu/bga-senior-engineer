<?php

namespace AGR\Cards\A;

use AGR\Core\Globals;
use AGR\Models\MinorImprovement;

class A74_StableTree extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A74_StableTree';
    $this->name = clienttranslate('Stable Tree');
    $this->deck = 'A';
    $this->number = 74;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you build 1 or more stables on your turn, place 1 <WOOD> on each of the next 3 round spaces. At the start of these rounds, you get the <WOOD>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->isArtifexOrBubulcus = true;

    $this->rulings = [
      clienttranslate('Stables which are not built during your turn, e.g. with __Stable Planner__ (A089) or __Groom__ (B089), do not trigger this card.'),
    ];
  }

  public function onBuy($player): ?array
  {
    $placeWood = $this->placeWoodNode($player);
    if (is_null($placeWood)) {
      $this->setUsedOnTurnId('');
      return null;
    } else {
      return $placeWood;
    }
  }

  public function isListeningTo($event): bool
  {
    return $this->isActionEvent($event, 'Stables') && $this->usableThisTurn();
  }

  public function onPlayerAfterStables($player, $event): ?array
  {
    $turnId = Globals::getTurnId();
    $pId = strval($player->getId());
    if (!$pId || strpos($turnId, $pId) === false) return null;

    return $this->placeWoodNode($player);
  }

  private function placeWoodNode($player): ?array
  {
    $numStablesBuiltThisTurn = $this->numStablesBuiltThisTurn($player);
    if ($numStablesBuiltThisTurn >= 1) {
      return [
        'type' => NODE_SEQ,
        'optional' => false,
        'childs' => [
          $this->setUsedOnTurnIdNode(),
          $this->futureMeeplesNode([WOOD => 1], 3)
        ]
      ];
    }
    return null;
  }
}
