<?php

namespace AGR\Cards\A;

use AGR\Core\Engine;
use AGR\Managers\Players;
use AGR\Models\MinorImprovement;

class A29_AleBenches extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A29_AleBenches';
    $this->name = clienttranslate('Ale-Benches');
    $this->deck = 'A';
    $this->number = 29;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the returning home phase of each round, you can pay exactly 1 <GRAIN> from your supply to get 1 bonus <SCORE>. If you do, each other player gets 1 <FOOD>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->extraVp = true;
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'ReturnHome';
  }

  public function onPlayerReturnHome($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->payGainNode([GRAIN => 1], [SCORE => 1], null, false),
        [
          'action' => SPECIAL_EFFECT,
          'auto' => true,
          'args' => [
            'cardId' => $this->id,
            'method' => 'giveFood',
          ],
        ],
      ],
    ];
  }

  public function giveFood()
  {
    $player = $this->getPlayer();
    $flow = [];
    foreach (Players::getAll() as $player2) {
      if ($player->getId() == $player2->getId()) {
        continue;
      }
      $flow[] = $this->gainNode([FOOD => 1], $player2->getId());
    }
    if ($flow != []) {
      Engine::insertAsChild(['type' => NODE_SEQ, 'childs' => $flow]);
    }
  }
}
