<?php
namespace AGR\Cards\B;
use AGR\Core\Engine;

class B123_RoofBallaster extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B123_RoofBallaster';
    $this->name = clienttranslate('Roof Ballaster');
    $this->deck = 'B';
    $this->number = 123;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you can immediately pay 1 <FOOD> to get 1 <STONE> for each room you have.'
      ),
    ];
    $this->players = '1+';

    $this->rulings = [
      clienttranslate('The 1 <FOOD> cost is in total, not per room. It is paid in addition to the occupation cost.'),
    ];
  }

  public function onBuy($player)
  {
    return $this->payGainNode([FOOD => 1], [STONE => $player->countRooms()]);
  }
}
