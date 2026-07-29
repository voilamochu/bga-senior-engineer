<?php
namespace AGR\Cards\Actions;

class ActionPigMarket extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionPigMarket';
    $this->name = clienttranslate('Pig Market');
    $this->desc = ['1<PIG>'];
    $this->tooltip = [clienttranslate('Accumulate 1 wild boar each turn')];
    $this->accumulate = 'bottom';

    $this->stage = 3;
    $this->accumulation = [PIG => 1];
    $this->flow = [
      'action' => COLLECT,
    ];
  }
}
