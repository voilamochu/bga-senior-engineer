<?php
namespace AGR\Cards\Actions;

class ActionGrove extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionGrove';
    $this->name = clienttranslate('Grove');
    $this->desc = ['2<WOOD>'];
    $this->tooltip = [clienttranslate('Accumulate 2 wood each turn')];
    $this->size = 's';
    $this->container = 'left';
    $this->accumulate = 'left';

    $this->players = [3, 4];
    $this->accumulation = [WOOD => 2];
    $this->flow = [
      'action' => COLLECT,
    ];
  }
}
