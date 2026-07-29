<?php
namespace AGR\Cards\B;

class B169_LivestockSustainer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B169_LivestockSustainer';
    $this->name = ('Livestock Sustainer');
    $this->deck = 'B';
    $this->number = 169;
    $this->category = FARM_PLANNER;
    $this->desc = [
      (
        'For each major improvement built by the other players, you can keep 1 animal on this card (max. 8). You can keep different types here.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
