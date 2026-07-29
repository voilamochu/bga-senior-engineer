<?php
namespace AGR\Cards\E;

use AGR\Helpers\Utils;

class E2_RenovationMaterials extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E2_RenovationMaterials';
    $this->name = clienttranslate('Renovation Materials');
    $this->deck = 'E';
    $this->author = 'luki';
    $this->number = 2;
    $this->category = 'PASSING_-_ACTION_-_FARMYARD';
    $this->desc = [clienttranslate('Immediately renovate to clay at no cost. (You must pay the cost of this card though.)')];
    $this->passing = true;
    $this->cost = [
      CLAY => 3,
      REED => 1,
    ];
    $this->prerequisite = clienttranslate('Wooden House');
    $this->implemented = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $roomType = $player->getRoomType();
    if (!($roomType == 'roomWood')) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function onBuy($player)
  {
    return [
      'action' => RENOVATION,
      'pId' => $this->pId,
      'args' => ['costs' => Utils::formatCost([]), 'toClay' => true],
    ];
  }
}
