<?php
namespace AGR\Cards\Actions;

class ActionFishing extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionFishing';
    $this->name = clienttranslate('Fishing');
    $this->desc = ['1<FOOD>'];
    $this->tooltip = [clienttranslate('Accumulate 1 food each turn')];
    $this->size = 's';
    $this->accumulate = 'right';

    $this->accumulation = [FOOD => 1];
    $this->flow = [
      'action' => COLLECT,
    ];
  }
}
