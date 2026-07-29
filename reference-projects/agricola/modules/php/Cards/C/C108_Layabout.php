<?php
namespace AGR\Cards\C;

use AGR\Core\Globals;
use AGR\Core\Notifications;

class C108_Layabout extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C108_Layabout';
    $this->name = clienttranslate('Layabout');
    $this->deck = 'C';
    $this->number = 108;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you must skip the next harvest. (You also do not have to feed your family that harvest.)'
      ),
    ];
    $this->players = '1+';
  }

  public function onBuy($player)
  {
    $pass = Globals::getPassHarvest() ?? [];
    $pass[] = $player->getId();
    Globals::setPassHarvest($pass);
    Notifications::message(clienttranslate('${player_name} will skip next harvest phase.'), [
      'player_name' => $player->getName(),
    ]);
  }
}
