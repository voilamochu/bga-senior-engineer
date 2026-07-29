<?php
namespace AGR\Cards\D;

class D170_FoldBuilder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D170_FoldBuilder';
    $this->name = ('Fold Builder');
    $this->deck = 'D';
    $this->number = 170;
    $this->category = FARM_PLANNER;
    $this->desc = [
      (
        'This card is an action space for all. It provides a "Build Fences" action and then 1 sheep. If another player uses it, they must first pay you 1 food.'
      ),
    ];
    $this->players = '5+';
    $this->implemented = false;
  }
}
