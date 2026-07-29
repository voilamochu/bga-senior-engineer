<?php
namespace AGR\Cards\Actions;

class ActionSheepMarket extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionSheepMarket';
    $this->name = clienttranslate('Sheep Market');
    $this->desc = ['1<SHEEP>'];
    $this->tooltip = [clienttranslate('Accumulate 1 sheep each turn')];
    $this->accumulate = 'bottom';

    $this->stage = 1;
    $this->accumulation = [SHEEP => 1];
    $this->flow = [
      'action' => COLLECT,
    ];
  }
}
