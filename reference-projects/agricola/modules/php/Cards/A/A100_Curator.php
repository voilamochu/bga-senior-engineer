<?php

namespace AGR\Cards\A;

use AGR\Models\Occupation;

class A100_Curator extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A100_Curator';
    $this->name = clienttranslate('Curator');
    $this->deck = 'A';
    $this->number = 100;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the returning home phase of each round, if you return at least 3 people from accumulation spaces, you can buy 1 bonus <SCORE> for 1 <FOOD>.'
      ),
    ];
    $this->extraVp = true;
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
    $this->bannedWeak1or2p = true;
    $this->bannedWeak3or4p = true;
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'StartReturnHome';
  }

  public function onPlayerStartReturnHome($player, $event)
  {
    $n = $player->getFarmersOnAccumulationSpaces();

    if ($n > 2) {
      return [
        'type' => NODE_SEQ,
        'optional' => true,
        'childs' => [
          $this->payGainNode([FOOD => 1], [SCORE => 1], null, false),
        ]
      ];
    }
  }
}
