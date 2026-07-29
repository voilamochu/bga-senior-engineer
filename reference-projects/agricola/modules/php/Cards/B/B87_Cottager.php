<?php

namespace AGR\Cards\B;

use AGR\Managers\Actions;
use AGR\Models\Occupation;
use AGR\Helpers\CardRulings;

class B87_Cottager extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B87_Cottager';
    $this->name = clienttranslate('Cottager');
    $this->deck = 'B';
    $this->number = 87;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Day Laborer__ action space, you can also either build exactly 1 room or renovate your house. Either way, you have to pay the cost.'
      ),
    ];
    $this->players = '1+';

    $this->rulings = CardRulings::fromKeys([
      'TRIGGERS_BEFORE_ACTION_SPACE',
    ]);
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'DayLaborer');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    $childs = [];
    if (Actions::get(CONSTRUCT)->isDoable($player, true)) {
      $childs[] = [
        'action' => CONSTRUCT,
        'args' => [
          'max' => 1,
          'trueAction' => false
        ],
      ];
    }

    if (Actions::get(RENOVATION)->isDoable($player, true)) {
      $childs[] = [
        'action' => RENOVATION,
        'pId' => $this->pId,
      ];
    }

    if (!empty($childs)) {
      return [
        'countAsUse' => true,
        'type' => NODE_XOR,
        'pId' => $this->pId,
        'actionId' => $this->id,
        'optional' => true,
        'childs' => $childs,
      ];
    }
  }
}
