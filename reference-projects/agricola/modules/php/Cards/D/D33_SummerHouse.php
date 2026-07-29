<?php
namespace AGR\Cards\D;

class D33_SummerHouse extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D33_SummerHouse';
    $this->name = clienttranslate('Summer House');
    $this->deck = 'D';
    $this->number = 33;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'During scoring, if you live in a stone house, you get 2 bonus <SCORE> for each unused farmyard space orthogonally adjacent to your house. (You still lose the points for these unused spaces.)'
      ),
    ];
    $this->cost = [
      WOOD => '3',
      STONE => '1',
    ];
    $this->extraVp = true;
    $this->prerequisite = clienttranslate('Still in Wooden House');
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedStrong1or2p = true;
    $this->bannedStrong3or4p = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    // Still in wooden house
    $roomType = $player->getRoomType();
    if ($roomType != 'roomWood') {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function computeBonusScore()
  {
    $bonus = 0;
    $player = $this->getPlayer();
    foreach($player->board()->getFreeZones() as $zone) {
      if($player->board()->isAdjacentToType($zone['x'],$zone['y'],'roomStone')){
        $bonus = $bonus + 2;
      }
    }
    $this->addBonusScoringEntry($bonus);
  }
}
