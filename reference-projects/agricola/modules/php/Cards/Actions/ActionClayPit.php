<?php
namespace AGR\Cards\Actions;

class ActionClayPit extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionClayPit';
    $this->name = clienttranslate('Clay Pit');
    $this->desc = ['1<CLAY>'];
    $this->size = 's';
    $this->accumulate = 'right';
    $this->tooltip = [clienttranslate('Accumulate 1 clay each turn')];

    $this->accumulation = [CLAY => 1];
    $this->flow = [
      'action' => COLLECT,
    ];
  }
}
