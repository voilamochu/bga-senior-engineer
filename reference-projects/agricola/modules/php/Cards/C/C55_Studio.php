<?php

namespace AGR\Cards\C;

use AGR\Models\MinorImprovement;

class C55_Studio extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C55_Studio';
    $this->name = clienttranslate('Studio');
    $this->deck = 'C';
    $this->number = 55;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the feeding phase of each harvest, you can use this card to turn exactly 1 <WOOD>/<CLAY>/<STONE> into 2/2/3 <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      CLAY => '1',
      REED => '1',
    ];
    $this->isCorbariusOrDulcinaria = true;
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
      'doableRequiresChild' => true,
      'customDescription' => 'Pay building resource, gain <FOOD>',
      'childs' => [
        $this->payGainNode([WOOD => 1], [FOOD => 2], null, false),
        $this->payGainNode([CLAY => 1], [FOOD => 2], null, false),
        $this->payGainNode([STONE => 1], [FOOD => 3], null, false),
      ]
    ];
  }
}
