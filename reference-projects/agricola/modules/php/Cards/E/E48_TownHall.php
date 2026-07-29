<?php
namespace AGR\Cards\E;

class E48_TownHall extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E48_TownHall';
    $this->name = clienttranslate('Town Hall');
    $this->deck = 'E';
    $this->author = 'inoshishi';
    $this->number = 48;
    $this->category = FOOD;
    $this->desc = [
      clienttranslate(
        'In the feeding phase of each harvest, if you live in a clay or stone house, you get 1 or 2 <FOOD>, respectively.'
      ),
    ];
    $this->vp = 2;
    $this->cost = [
      WOOD => 2,
      CLAY => 2,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'HarvestFeedingPhase';
  }

  public function onPlayerHarvestFeedingPhase($player, $event)
  {
    if($player->getRoomType() == 'roomClay'){
      return $this->gainNode([FOOD => 1]);
    }
    elseif($player->getRoomType() == 'roomStone'){
      return $this->gainNode([FOOD => 2]);
    }
  }
}