<?php
namespace AGR\Cards\Actions;

class ActionResourceMarket extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionResourceMarket';
    $this->name = clienttranslate('Resource Market');
    $this->desc = ['+1<REED>/<STONE> +1<FOOD>'];
    $this->tooltip = [clienttranslate('Take your choice of 1 reed or 1 stone as well as 1 food')];
    $this->container = 'left';

    $this->players = [3];
    $this->flow = [
      'type' => NODE_XOR,
      'childs' => [
        ['action' => GAIN, 'actionId' => 'Market1', 'args' => [REED => 1, FOOD => 1]],
        ['action' => GAIN, 'actionId' => 'Market2', 'args' => [STONE => 1, FOOD => 1]],
      ],
    ];
  }
}
