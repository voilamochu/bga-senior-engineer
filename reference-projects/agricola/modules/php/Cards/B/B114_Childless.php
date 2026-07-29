<?php
namespace AGR\Cards\B;
use AGR\Helpers\CardRulings;

class B114_Childless extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B114_Childless';
    $this->name = clienttranslate('Childless');
    $this->deck = 'B';
    $this->number = 114;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At the start of each round, if you have at least 3 rooms but only 2 people, you get 1 <FOOD> and 1 crop of your choice (<GRAIN> or <VEGETABLE>)'
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
      $event['type'] == 'StartOfTurn' &&
      $this->getPlayer()->countRooms() >= 3 &&
      $this->getPlayer()->countFarmers() == 2;
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    return [
      'type' => NODE_XOR,
      'childs' => [$this->gainNode([FOOD => 1, GRAIN => 1]), $this->gainNode([FOOD => 1, VEGETABLE => 1])],
    ];
  }
}
