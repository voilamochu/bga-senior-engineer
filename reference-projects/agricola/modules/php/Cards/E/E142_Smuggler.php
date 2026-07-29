<?php

namespace AGR\Cards\E;

use AGR\Models\Occupation;

class E142_Smuggler extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E142_Smuggler';
    $this->name = clienttranslate('Smuggler');
    $this->deck = 'E';
    $this->author = 'chriss';
    $this->number = 142;
    $this->category = 'CROPS';
    $this->desc = [
      clienttranslate(
        'In the feeding phase of each harvest, you can exchange up to 2 goods as follows:'
      ),
      '[<WOOD> <ARROW> <GRAIN>]',
      clienttranslate(
        'or'
      ),
      '[<GRAIN> <ARROW> <STONE>]'
    ];
    $this->players = '3+';
    $this->implemented = true;

    $this->rulings = [
      clienttranslate('You can use the 2 exchanges to turn 1 <WOOD> into 1 <STONE>.'),
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'HarvestFeedingPhase';
  }

  public function onPlayerHarvestFeedingPhase($player, $event)
  {
    return [
      'type' => NODE_XOR,
      'optional' => true,
      'countAsUse' => true,
      'childs' => [
        $this->payGainNode([WOOD => 2], [GRAIN => 2], null, false),
        $this->payGainNode([GRAIN => 2], [STONE => 2], null, false),
        [
          'type' => NODE_OR,
          'optional' => true,
          'childs' => [
            $this->payGainNode([WOOD => 1], [GRAIN => 1], null, false),
            $this->payGainNode([GRAIN => 1], [STONE => 1], null, false),
          ],
        ],
      ],
    ];
  }
}
