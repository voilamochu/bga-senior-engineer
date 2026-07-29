<?php

namespace AGR\Cards\D;

use AGR\Models\Occupation;

class D133_BeerTentOperator extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D133_BeerTentOperator';
    $this->name = clienttranslate('Beer Tent Operator');
    $this->deck = 'D';
    $this->number = 133;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the feeding phase of each harvest, you can use this card to turn 1 <WOOD> plus 1 <GRAIN> into 1 bonus <SCORE> and 2 <FOOD>.'
      ),
    ];
    $this->players = '3+';
    $this->extraVp = true;
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'HarvestFeedingPhase';
  }

  public function onPlayerHarvestFeedingPhase($player, $event)
  {
    return $this->payGainNode([WOOD => 1, GRAIN => 1], [SCORE => 1, FOOD => 2]);
  }
}
