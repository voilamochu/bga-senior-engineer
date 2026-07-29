<?php

namespace AGR\Cards\D;

use AGR\Models\MinorImprovement;

class D19_PulverizerPlow extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D19_PulverizerPlow';
    $this->name = clienttranslate('Pulverizer Plow');
    $this->deck = 'D';
    $this->number = 19;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Immediately after each time you use a clay accumulation space, you can pay 1 <CLAY> to plow 1 field. If you do, place that 1 <CLAY> on the accumulation space.'
      ),
    ];
    $this->cost = [
      WOOD => '2',
    ];
    $this->prerequisite = clienttranslate('1 Occupation');
    $this->occupationPrerequisites = ['min' => 1];
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;
  }

  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event, CLAY);
  }

  public function onPlayerAfterCollect($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->moveResourcesToSpaceNode([CLAY => 1], $event['actionCardId']),
        [
          'action' => PLOW,
          'cardId' => $this->id,
          'source' => $this->name,
        ],
      ]
    ];
  }
}
