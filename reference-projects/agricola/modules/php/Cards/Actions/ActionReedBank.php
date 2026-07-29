<?php
namespace AGR\Cards\Actions;

class ActionReedBank extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionReedBank';
    $this->name = clienttranslate('Reed Bank');
    $this->desc = ['1<REED>'];
    $this->tooltip = [clienttranslate('Accumulate 1 reed each turn')];
    $this->size = 's';
    $this->accumulate = 'left';

    $this->accumulation = [REED => 1];
    $this->flow = [
      'action' => COLLECT,
    ];
  }
}
