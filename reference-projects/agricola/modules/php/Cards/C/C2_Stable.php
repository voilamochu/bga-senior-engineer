<?php
namespace AGR\Cards\C;
use AGR\Helpers\Utils;

class C2_Stable extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C2_Stable';
    $this->name = clienttranslate('Stable');
    $this->deck = 'C';
    $this->number = 2;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Immediately build 1 stable. (The stable costs you nothing, but you must pay the cost shown on this card.)'
      ),
    ];
    $this->passing = true;
    $this->cost = [
      WOOD => 1,
    ];
  }

  public function onBuy($player)
  {
    return [
      'action' => \STABLES,
      'args' => [
        'max' => 1,
        'costs' => Utils::formatCost([WOOD => 0]),
      ],
    ];
  }
}
