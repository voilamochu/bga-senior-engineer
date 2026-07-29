<?php

namespace AGR\Cards\B;

use AGR\Core\Globals;
use AGR\Models\Occupation;

class B159_LieutenantGeneral extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B159_LieutenantGeneral';
    $this->name = clienttranslate('Lieutenant General');
    $this->deck = 'B';
    $this->number = 159;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'For each field tile that another player places next to an existing field tile, you get 1 <FOOD> from the general supply. In round 14, you get 1 <GRAIN> instead.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Plow', 'opponent');
  }

  public function onOpponentAfterPlow($player, $event)
  {
    if (!$player->board()->isAdjacentToType($event['field']['x'], $event['field']['y'], 'field')) {
      return;
    }
    $turn = Globals::getTurn();
    if ($turn <= 13) {
      return $this->gainNode([FOOD => 1]);
    } else {
      return $this->gainNode([GRAIN => 1]);
    }
  }
}