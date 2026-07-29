<?php
namespace AGR\Cards\E;

use AGR\Managers\Players;

class E14_WoodSaw extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E14_WoodSaw';
    $this->name = clienttranslate('Wood Saw');
    $this->deck = 'E';
    $this->author = 'chris';
    $this->number = 14;
    $this->category = 'FARMYARD_-_HOUSE_BUILDING_OR_RENOVATION';
    $this->desc = [
      clienttranslate('Each time all other players have more people than you, you can take a __Build Rooms__ action without placing a person.'),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->players = '2+';
    $this->implemented = true;
  }

  public function isListeningTo($event)
  {
    $player = $this->getPlayer();
    return $this->isAnytime($event) && $this->haveLeastFarmer($player);
  }

  public function onPlayerAtAnytime($player, $event)
  {
    return [
      'action' => CONSTRUCT,
      'cardId' => $this->id,
      'optional' => true,
    ];
  }

  function haveLeastFarmer($currentPlayer)
  {
    $count = $currentPlayer->countFarmers();
    $c = 0;
    foreach (Players::getAll() as $player) {
      if ($player->getId() != $currentPlayer->getId()) {
        if ($player->countFarmers() > $count) {
          $c++;
        }
      }
    }
    return $c > 0 && $c + 1 == count(Players::getAll());
  }
}
