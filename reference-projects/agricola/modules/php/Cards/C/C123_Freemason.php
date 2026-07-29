<?php
namespace AGR\Cards\C;
use AGR\Helpers\CardRulings;

class C123_Freemason extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C123_Freemason';
    $this->name = clienttranslate('Freemason');
    $this->deck = 'C';
    $this->number = 123;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'As long as you live in a <CLAY>/<STONE> house with exactly 2 rooms, at the start of each work phase, you get 2 <CLAY>/<STONE>.'
      ),
    ];
    $this->players = '1+';

    $this->rulings = CardRulings::fromKeys([
      'ROOM_FROM_CARDS_NOT_COUNTED',
    ]);
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'startOfWork' &&
      (($this->getPlayer()->getRoomType() == 'roomStone' && $this->getPlayer()->countRooms() == 2) ||
        ($this->getPlayer()->getRoomType() == 'roomClay' && $this->getPlayer()->countRooms() == 2));
  }

  public function onPlayerStartOfWork($player, $event)
  {
    if ($this->getPlayer()->getRoomType() == 'roomStone') {
      return $this->gainNode([STONE => 2]);
    } elseif ($this->getPlayer()->getRoomType() == 'roomClay') {
      return $this->gainNode([CLAY => 2]);
    }
  }
}
