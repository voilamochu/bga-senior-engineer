<?php
namespace AGR\Cards\Actions;

class ActionHollow extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionHollow';
    $this->name = clienttranslate('Hollow');
    $this->desc = ['1<CLAY>'];
    $this->tooltip = [clienttranslate('Accumulate 1 clay each turn')];
    $this->container = 'left';
    $this->size = 's';
    $this->accumulate = 'right';

    $this->accumulation = [CLAY => 1];
    $this->players = [3];
    $this->flow = [
      'action' => COLLECT,
    ];
  }
}
