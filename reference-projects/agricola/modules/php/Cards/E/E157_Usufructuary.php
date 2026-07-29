<?php
namespace AGR\Cards\E;
use AGR\Managers\Players;

class E157_Usufructuary extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E157_Usufructuary';
    $this->name = clienttranslate('Usufructuary');
    $this->deck = 'E';
    $this->author = 'tamos';
    $this->number = 157;
    $this->category = FOOD;
    $this->desc = [
      clienttranslate(
        'When you play this card as your first occupation, you immediately get 1 <FOOD> for every other occupation in play (by any player), up to a maximum of 7 <FOOD>.'
      ),
    ];
    $this->players = '4+';
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Occupation');
  }

  public function onPlayerAfterOccupation($player, $event)
  {
    if($player->countOccupations()!=1){
      return;
    }
    $other_occupation = 0;
    foreach (Players::getAll() as $player2) {
      if ($this->pId != $player2->getId()) {
        $other_occupation = $other_occupation + $player2->countOccupations();
      }
    }
    if($other_occupation <= 0){
      return;
    }
    if($other_occupation <= 7){
      return $this->gainNode([FOOD => $other_occupation]);
    }
    else{
      return $this->gainNode([FOOD => 7]);
    }
  }
}
