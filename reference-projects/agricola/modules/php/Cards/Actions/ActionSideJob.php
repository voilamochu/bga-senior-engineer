<?php
namespace AGR\Cards\Actions;
use AGR\Helpers\Utils;

class ActionSideJob extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionSideJob';
    $this->name = clienttranslate('Side Job');
    $this->desc = ['1<WOOD> <ARROW-1X> <BARN>', '+ <BREAD>'];
    $this->tooltip = [clienttranslate('Build 1 stable for 1 wood and/or bake bread')];

    $this->isBeginner = true;
    $this->flow = [
      'type' => NODE_OR,
      'childs' => [
        [
          'action' => STABLES,
          'args' => [
            'max' => 1,
            'costs' => Utils::formatCost([WOOD => 1, 'max' => 1]),
          ],
        ],
        [
          'action' => EXCHANGE,
          'args' => [
            'trigger' => BREAD,
          ],
        ],
      ],
    ];
  }
}
