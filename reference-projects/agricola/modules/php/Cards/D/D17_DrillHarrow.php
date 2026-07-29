<?php

namespace AGR\Cards\D;

use AGR\Actions\Sow;
use AGR\Models\MinorImprovement;

class D17_DrillHarrow extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D17_DrillHarrow';
    $this->name = clienttranslate('Drill Harrow');
    $this->deck = 'D';
    $this->number = 17;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Each time before you take an unconditional __Sow__ action, you can pay 3 <FOOD> to plow 1 field.'),
    ];
    $this->cost = [
      WOOD => '1',
    ];
    $this->isCorbariusOrDulcinaria = true;
  }

  public function onPlayerIsDoable($player, &$args)
  {
    if ($args['isDoable']) {
      return;
    }

    if ($args['action'] == SOW) {
      $args['isDoable'] = true;
    }
  }

  public function isListeningTo($event)
  {
    return $this->isBeforeEvent($event, 'Sow') &&
      (!isset($event['args']) || Sow::isUnconditional($event['args']));
  }

  public function onPlayerBeforeSow($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'uiShowOnlyActionDescWhenCombined' => true,
      'optional' => true,
      'childs' => [
        $this->payNode([FOOD => 3], clienttranslate('Drill Harrow\'s effect')),
        [
          'action' => PLOW,
          'cardId' => $this->id,
          'source' => $this->name,
        ],
      ],
    ];
  }
}
