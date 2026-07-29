<?php

namespace AGR\Cards\A;

class A10_WoodenShed extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A10_WoodenShed';
    $this->name = clienttranslate('Wooden Shed');
    $this->deck = 'A';
    $this->number = 10;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'This card can only be played via a __Major Improvement__ action. It provides room for one person. You may no longer renovate.'
      ),
    ];
    $this->cost = [
      WOOD => '2',
      REED => '1',
    ];
    $this->prerequisite = clienttranslate('Still in Wooden House');
    $this->holder = true;
    $this->isArtifexOrBubulcus = true;

    $this->rulings = [
      clienttranslate('May not be played on the __House Redevelopment__ action space, as the __Renovate__ action is mandatory and comes before the improvement action.'),
      clienttranslate('May be played through the effect of a card, such as __Angler__ (A095).'),
    ];
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    // Still in wooden house
    $roomType = $player->getRoomType();
    if ($roomType != 'roomWood') {
      return false;
    }

    // Action space needs to be Major Improvement
    if ($args['actionType'] != 'MajorOrMinor' && $args['actionType'] != 'Major') {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }
}
