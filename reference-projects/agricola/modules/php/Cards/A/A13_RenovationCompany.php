<?php
namespace AGR\Cards\A;
use AGR\Helpers\Utils;

class A13_RenovationCompany extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A13_RenovationCompany';
    $this->name = clienttranslate('Renovation Company');
    $this->deck = 'A';
    $this->number = 13;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 3 <CLAY>. Immediately after, you can renovate without paying any building resources.'
      ),
    ];
    $this->cost = [
      WOOD => 4,
    ];
    $this->prerequisite = clienttranslate('In Wooden House with Exactly 2 Rooms');
    $this->isArtifexOrBubulcus = true;

    $this->rulings = [
      clienttranslate('The renovation can be declined, but if you decline you cannot renovate for free later.'),
    ];
  }
  
  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $roomType = $player->getRoomType();
    $rooms = $player->countRooms();
      
    if (!($roomType == 'roomWood' && $rooms == 2)) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }
  
  public function onBuy($player)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        $this->gainNode([CLAY => 3]),
        [
          'type' => NODE_SEQ,
          'optional' => true,
          'childs' => [
            [
              'action' => RENOVATION,
              'pId' => $this->pId,
              'args' => ['costs' => Utils::formatCost([])],
            ]
          ]
        ]
      ]
    ];
  }  
}
