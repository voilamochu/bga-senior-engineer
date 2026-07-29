<?php

namespace AGR\Cards\A;

use AGR\Helpers\Utils;
use AGR\Models\MinorImprovement;

class A14_CarpentersHammer extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A14_CarpentersHammer';
    $this->name = clienttranslate("Carpenter's Hammer");
    $this->deck = 'A';
    $this->number = 14;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Each time you build at least 2 wood/clay/stone rooms at once, you get a total discount of 2 <REED> as well as 2 <WOOD>/3 <CLAY>/4 <STONE>.'
      ),
    ];
    $this->cost = [
      WOOD => '1',
    ];
    $this->isArtifexOrBubulcus = true;
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;
  }

  public function orderComputeCostsConstruct()
  {
    return [['>', 'A123_FrameBuilder']];
  }

  public function onPlayerComputeCostsConstruct($player, &$args)
  {
    // Discount reed
    Utils::addBonus($args['costs'], [REED => -2], $this->id, false, ['minNumRooms' => 2]);

    // Discount wood / clay / stone
    $roomType = $player->getRoomType();
    if ($roomType == 'roomWood') {
      $res = WOOD;
      $amt = 2;
    } elseif ($roomType == 'roomClay') {
      $res = CLAY;
      $amt = 3;
    } else {
      $res = STONE;
      $amt = 4;
    }
    Utils::addBonus($args['costs'], [$res => -$amt], $this->id, false, ['minNumRooms' => 2]);
  }
}
