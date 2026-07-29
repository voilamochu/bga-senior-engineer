<?php

namespace AGR\Cards\D;

use AGR\Models\Occupation;

class D166_StableMilker extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D166_StableMilker';
    $this->name = clienttranslate('Stable Milker');
    $this->deck = 'D';
    $this->number = 166;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate('Each time you build at least 2 stables on the same turn, you also get 1 <CATTLE>.')
    ];
    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;
  }

  public function onBuy($player): ?array
  {
    $receiveCattle = $this->gainCattleNode($player);
    if (is_null($receiveCattle)) {
      $this->setUsedOnTurnId('');
      return null;
    } else {
      return $receiveCattle;
    }
  }

  private function gainCattleNode($player): ?array
  {
    $numStablesBuiltThisTurn = $this->numStablesBuiltThisTurn($player,);
    if ($numStablesBuiltThisTurn >= 2) {
      return [
        'type' => NODE_SEQ,
        'optional' => false,
        'childs' => [
          $this->setUsedOnTurnIdNode(),
          $this->gainNode([CATTLE => 1])
        ]
      ];
    }
    return null;
  }

  public function isListeningTo($event): bool
  {
    return $this->isActionEvent($event, 'Stables') && $this->usableThisTurn();
  }

  public function onPlayerAfterStables($player, $event): ?array
  {
    return $this->gainCattleNode($player);
  }

}