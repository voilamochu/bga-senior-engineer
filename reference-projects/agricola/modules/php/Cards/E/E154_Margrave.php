<?php
namespace AGR\Cards\E;
use AGR\Managers\Players;

class E154_Margrave extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E154_Margrave';
    $this->name = clienttranslate('Margrave');
    $this->deck = 'E';
    $this->author = 'guy';
    $this->number = 154;
    $this->category = 'BONUS_POINTS';
    $this->desc = [
      clienttranslate(
        'Once you live in a stone house, you get 2 <FOOD> each time any player renovates and, during scoring, 1 bonus <SCORE> for each wood house and clay house.'
      ),
    ];
    $this->extraVp = true;
    $this->players = '4+';
  }

  public function isListeningTo($event)
  {
    return $this->getPlayer()->getRoomType() == 'roomStone' && $this->isActionEvent($event, 'Renovation', 'opponent');
  }

  public function onOpponentAfterRenovation($player, $event)
  {
     return $this->gainNode([FOOD => 2]);
  }

  public function computeBonusScore()
  {
    if($this->getPlayer()->getRoomType() != 'roomStone'){
      return;
    }
    $count_not_stone = 0;
    foreach (Players::getAll() as $player) {
      if ($player->getRoomType() != 'roomStone' && ($this->pId != $player->getId())) {
        $count_not_stone = $count_not_stone + 1;
      }
    }
    $this->addBonusScoringEntry($count_not_stone);
  }

}
