<?php
namespace AGR\Cards\Actions;
use AGR\Managers\Farmers;

class ActionCopseAdd extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionCopseAdd';
    $this->name = clienttranslate('Copse');
    $this->actionCardType = 'Copse';
    $this->desc = ['1<WOOD>'];
    $this->tooltip = [clienttranslate('Accumulate 1 wood each turn')];
    $this->container = 'add';
    $this->size = 's';
    $this->accumulate = 'right';

    $this->isAdditional = true;
    $this->players = [2];
    $this->accumulation = [WOOD => 1];
    $this->flow = [
      'action' => COLLECT,
    ];
  }
}
