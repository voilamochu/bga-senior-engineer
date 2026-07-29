<?php
namespace AGR\Cards\Actions;

class ActionWesternQuarry extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionWesternQuarry';
    $this->name = clienttranslate('Western Quarry');
    $this->desc = ['1<STONE>'];
    $this->tooltip = [clienttranslate('Accumulate 1 stone each turn')];
    $this->accumulate = 'bottom';

    $this->stage = 2;
    $this->accumulation = [STONE => 1];
    $this->flow = [
      'action' => COLLECT,
    ];
  }
}
