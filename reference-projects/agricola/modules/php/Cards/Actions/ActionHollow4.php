<?php
namespace AGR\Cards\Actions;

class ActionHollow4 extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionHollow4';
    $this->name = clienttranslate('Hollow');
    $this->actionCardType = 'Hollow';
    $this->desc = ['2<CLAY>'];
    $this->tooltip = [clienttranslate('Accumulate 2 clay each turn')];
    $this->container = 'left';
    $this->size = 's';
    $this->accumulate = 'right';

    $this->players = [4];
    $this->accumulation = [CLAY => 2];
    $this->flow = [
      'action' => COLLECT,
    ];
  }
}
