<?php
namespace AGR\Cards\Actions;

class ActionCopse extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionCopse';
    $this->name = clienttranslate('Copse');
    $this->desc = ['1<WOOD>'];
    $this->tooltip = [clienttranslate('Accumulate 1 wood each turn')];
    $this->container = 'left';
    $this->size = 's';
    $this->accumulate = 'right';

    $this->players = [4];
    $this->accumulation = [WOOD => 1];
    $this->flow = [
      'action' => COLLECT,
    ];
  }
}
