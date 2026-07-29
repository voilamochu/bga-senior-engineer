<?php
namespace AGR\Cards\Actions;

class ActionForestSolo extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionForestSolo';
    $this->name = clienttranslate('Forest');
    $this->actionCardType = 'Forest';
    $this->desc = ['2<WOOD>'];
    $this->tooltip = [clienttranslate('Accumulate 2 wood each turn')];
    $this->size = 's';
    $this->accumulate = 'left';

    $this->players = [1];
    $this->accumulation = [WOOD => 2];
    $this->flow = [
      'action' => COLLECT,
    ];
  }
}
