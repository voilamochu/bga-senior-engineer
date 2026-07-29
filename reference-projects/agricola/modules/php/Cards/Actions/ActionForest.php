<?php
namespace AGR\Cards\Actions;

class ActionForest extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionForest';
    $this->name = clienttranslate('Forest');
    $this->desc = ['3<WOOD>'];
    $this->tooltip = [clienttranslate('Accumulate 3 wood each turn')];
    $this->size = 's';
    $this->accumulate = 'left';

    $this->players = [2, 3, 4];
    $this->accumulation = [WOOD => 3];
    $this->flow = [
      'action' => COLLECT,
    ];
  }
}
