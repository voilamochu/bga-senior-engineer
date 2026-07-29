<?php
namespace AGR\Cards\A;

class A125_Priest extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A125_Priest';
    $this->name = clienttranslate('Priest');
    $this->deck = 'A';
    $this->number = 125;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, if you live in a clay house with exactly 2 rooms, you immediately get 3 <CLAY>, 2 <REED> and 2 <STONE>.'
      ),
    ];
    $this->players = '1+';

    $this->rulings = [
      clienttranslate('You must play this card after renovating to <CLAY> in order for it to have any effect.'),
    ];
  }

  public function onBuy($player)
  {
    if ($player->countRooms() == 2 && $player->getRoomType() == 'roomClay') {
      return $this->gainNode([CLAY => 3, REED => 2, STONE => 2]);
    }
  }
}
