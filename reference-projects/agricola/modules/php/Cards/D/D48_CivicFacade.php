<?php
namespace AGR\Cards\D;

class D48_CivicFacade extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D48_CivicFacade';
    $this->name = clienttranslate('Civic Facade');
    $this->deck = 'D';
    $this->number = 48;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Before the start of each round, if you have more occupations than improvements in your hand, you get 1 <FOOD>.'
      ),
    ];
    $this->cost = [
      CLAY => 1,
    ];
    $this->prerequisite = clienttranslate('3 Rooms');
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedLiving = true;
  }
  
  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if ($player->countRooms() < 3) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'BeforeStartOfTurn';
  }
  
  public function onPlayerBeforeStartOfTurn($player, $event)
  {
    $occs = count($player->getHand(OCCUPATION));
    $improvements = count($player->getHand(MINOR));
    
    if ($occs > $improvements) {
      return $this->gainNode([FOOD => 1]);
    }
  }
}
