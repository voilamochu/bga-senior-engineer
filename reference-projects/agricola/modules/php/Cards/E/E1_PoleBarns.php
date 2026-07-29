<?php
namespace AGR\Cards\E;
use AGR\Helpers\Utils;
use AGR\Managers\Fences;

class E1_PoleBarns extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E1_PoleBarns';
    $this->name = clienttranslate('Pole Barns');
    $this->deck = 'E';
    $this->author = 'oxmond';
    $this->number = 1;
    $this->category = 'PASSING_-_FARMYARD';
    $this->desc = [clienttranslate('You can immediately build up to 3 stables at no cost. (You must pay the cost of this card though.)')];
    $this->passing = true;
    $this->cost = [
      WOOD => 2,
    ];
    $this->prerequisite = clienttranslate('15 Fences Built');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $pId = $player->getId();
    $n = Fences::getOnBoard($pId)->count();
    if ($n < 15) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    return [
      'action' => \STABLES,
      'args' => [
        'max' => 3,
        'costs' => Utils::formatCost([WOOD => 0]),
      ],
    ];
  }
}
