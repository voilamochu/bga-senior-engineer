<?php
namespace AGR\Cards\E;
use AGR\Core\Globals;

class E168_AnimalTamersApprentice extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E168_AnimalTamersApprentice';
    $this->name = clienttranslate("Animal Tamer's Apprentice");
    $this->deck = 'E';
    $this->author = 'nwoll';
    $this->number = 168;
    $this->category = 'ANIMALS_-_ALL';
    $this->desc = [
      clienttranslate(
        'At the start of each round, you get 1 <SHEEP>/<PIG>/<CATTLE> for each unoccupied wood/clay/stone room in your house.'
      ),
    ];
    $this->players = '4+';
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'StartOfTurn';
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    $roomnum = $player->countRooms();
    $occupied = (($player->countFarmers() - $player->countExtraRoom()) > 0) ? ($player->countFarmers() - $player->countExtraRoom()) : 0;
    $unoccupied =  $roomnum - $occupied;
    if($unoccupied <= 0){
      return;
    }
    if($player->getRoomType() == 'roomStone'){
      return $this->gainNode([CATTLE=>$unoccupied]);
    }
    if($player->getRoomType() == 'roomClay'){
      return $this->gainNode([PIG=>$unoccupied]);
    }
    if($player->getRoomType() == 'roomWood'){
      return $this->gainNode([SHEEP=>$unoccupied]);
    }
  }
}
