<?php

namespace AGR\Cards\A;

use AGR\Helpers\Utils;

class A1_Shelter extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A1_Shelter';
    $this->name = clienttranslate('Shelter');
    $this->deck = 'A';
    $this->number = 1;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'You can immediately build a stable at no cost, but only if you place it in a pasture covering exactly 1 farmyard space.'
      ),
    ];
    $this->passing = true;
    $this->isArtifexOrBubulcus = true;
  }

  public function onBuy($player)
  {
    return [
      'action' => \STABLES,
      'args' => [
        'max' => 1,
        'costs' => Utils::formatCost([WOOD => 0]),
        'zones' => $player->board()->getPastureZonesBySize(1)
      ],
    ];
  }
}
