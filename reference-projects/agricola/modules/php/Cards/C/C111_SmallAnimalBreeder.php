<?php
namespace AGR\Cards\C;
use AGR\Core\Globals;

class C111_SmallAnimalBreeder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C111_SmallAnimalBreeder';
    $this->name = clienttranslate('Small Animal Breeder');
    $this->deck = 'C';
    $this->number = 111;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Before the start of each round, if you have <FOOD> equal to or higher than the upcoming round number (e.g., 8+ <FOOD> before round 8), you get 1 <FOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'BeforeStartOfTurn';
  }
  
  public function onPlayerBeforeStartOfTurn($player, $event)
  {
    $food = $player->countReserveResource(FOOD);      
    $turn = Globals::getTurn();

    // turn counter isn't updated until after this listener
    if ($food >= $turn+1) {
      return $this->gainNode([FOOD => 1]);
    }
  }
}
