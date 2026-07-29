<?php
namespace AGR\Cards\Actions;

class ActionCattleMarket extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionCattleMarket';
    $this->name = clienttranslate('Cattle Market');
    $this->desc = ['1<CATTLE>'];
    $this->tooltip = [clienttranslate('Accumulate 1 cattle each turn')];
    $this->accumulate = 'bottom';

    $this->stage = 4;
    $this->accumulation = [CATTLE => 1];
    $this->flow = [
      'action' => COLLECT,
    ];
  }
}
