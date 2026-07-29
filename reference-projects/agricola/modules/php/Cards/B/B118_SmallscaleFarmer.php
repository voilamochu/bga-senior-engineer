<?php
namespace AGR\Cards\B;
use AGR\Helpers\CardRulings;

class B118_SmallscaleFarmer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B118_SmallscaleFarmer';
    $this->name = clienttranslate('Small-scale Farmer');
    $this->deck = 'B';
    $this->number = 118;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'As long as you live in a house with exactly 2 rooms, at the start of each round, you get 1 <WOOD>.'
      ),
    ];
    $this->players = '1+';

    $this->rulings = CardRulings::fromKeys([
      'ROOM_FROM_CARDS_NOT_COUNTED',
    ]);
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'StartOfTurn' && $this->getPlayer()->countRooms() == 2;
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return $this->gainNode([WOOD => 1]);
  }
}
