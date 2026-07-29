<?php

namespace AGR\Cards\B;

use AGR\Helpers\Utils;
use AGR\Models\MinorImprovement;

class B15_CarpentersBench extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B15_CarpentersBench';
    $this->name = clienttranslate("Carpenter's Bench");
    $this->deck = 'B';
    $this->number = 15;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Immediately after each time you use a wood accumulation space, you can use the taken wood (and only that) to build exactly 1 pasture. If you do, one of the fences is free.'
      ),
    ];
    $this->cost = [
      WOOD => '1',
    ];
    $this->isArtifexOrBubulcus = true;
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;

    $this->rulings = [
      clienttranslate('Subdividing a pasture does not qualify as building.'),
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event, WOOD, true);
  }

  public function onPlayerImmediatelyAfterCollect($player, $event)
  {
    $n = 0;
    foreach($event['meeples'] as $a=>$b){
      if($b['type']=='wood'){
        $n=$n+1;
      }
    }
    return [
      'countAsUse' => true,
      'type' => NODE_SEQ,
      'pId' => $this->pId,
      'optional' => true,
      'childs' => [
        [
          'action' => FENCING,
          'args' => [
            'CarpentersBench' => true,
            'costs' => Utils::formatCost([WOOD => 1]),
            'benchWood' => $n,
          ],
        ],
      ]
    ];
  }

  public function onPlayerComputeCostsFencing($player, &$args)
  {
    if (!in_array('B15_CarpentersBench', $args['costs']['constraints'] ?? [])) {
      return;
    }
    Utils::addCost($args['costs'], [
      'max' => 1,
      'nb' => 1,
      'sources' => [$this->id],
    ]);
  }
}
