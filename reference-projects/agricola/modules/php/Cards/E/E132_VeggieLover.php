<?php

namespace AGR\Cards\E;

use AGR\Models\Occupation;

class E132_VeggieLover extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E132_VeggieLover';
    $this->name = clienttranslate('Veggie Lover');
    $this->deck = 'E';
    $this->number = 132;
    $this->category = 'BONUS_POINTS';
    $this->desc = [
      clienttranslate('[Harvest]'),
      '<GRAIN_VEG_STACK> <ARROW-1X> 6<FOOD>',
      clienttranslate('[Scoring]'),
      '1/2/3 <GRAIN_VEG_STACK> <ARROW-1X> 2/4/6 <SCORE>'
    ];
    $this->extraVp = true;
    $this->players = '3+';

    $this->rulings = [
      clienttranslate('If crops are exchanged for bonus points, they do not count in the normal scoring.'),
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && ($event['type'] == 'BeforeEndOfGame' || $this->isPlayerEvent($event) && $event['type'] == 'HarvestFeedingPhase');
  }

  public function onPlayerHarvestFeedingPhase($player, $event)
  {
    return $this->payGainNode([GRAIN => 1, VEGETABLE => 1], [FOOD => 6], null, true);
  }

  public function onPlayerBeforeEndOfGame($player, $event)
  {
    return [
      'type' => NODE_XOR,
      'optional' => true,
      'doableRequiresChild' => true,
      'customDescription' => 'Pay <GRAIN> and <VEGETABLE>, gain <SCORE>',
      'childs' => [
        $this->payGainNode([GRAIN => 1, VEGETABLE => 1], [SCORE => 2], null, false),
        $this->payGainNode([GRAIN => 2, VEGETABLE => 2], [SCORE => 4], null, false),
        $this->payGainNode([GRAIN => 3, VEGETABLE => 3], [SCORE => 6], null, false),
      ]
    ];
  }
}