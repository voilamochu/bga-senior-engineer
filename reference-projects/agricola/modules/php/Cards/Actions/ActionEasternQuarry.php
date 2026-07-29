<?php
namespace AGR\Cards\Actions;

class ActionEasternQuarry extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionEasternQuarry';
    $this->name = clienttranslate('Eastern Quarry');
    $this->desc = ['1<STONE>'];
    $this->tooltip = [clienttranslate('Accumulate 1 stone each turn')];
    $this->accumulate = 'bottom';

    $this->stage = 4;
    $this->accumulation = [STONE => 1];
    $this->flow = [
      'action' => COLLECT,
    ];
  }
}
