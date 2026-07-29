<?php
namespace AGR\Cards\Actions;

class ActionTravelingPlayers extends \AGR\Models\ActionCard
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'ActionTravelingPlayers';
    $this->name = clienttranslate('Traveling Players');
    $this->desc = ['1<FOOD>'];
    $this->tooltip = [clienttranslate('Accumulate 1 food each turn')];
    $this->container = 'left';
    $this->accumulate = 'left';
    $this->size = 's';

    $this->players = [4];
    $this->accumulation = [FOOD => 1];
    $this->flow = [
      'action' => COLLECT,
    ];
  }
}
