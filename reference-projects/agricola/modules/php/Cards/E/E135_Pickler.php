<?php
namespace AGR\Cards\E;

use AGR\Core\Globals;
use AGR\Managers\Players;

class E135_Pickler extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E135_Pickler';
    $this->name = clienttranslate('Pickler');
    $this->deck = 'E';
    $this->author = 'chris';
    $this->number = 135;
    $this->category = 'BONUS_POINTS_-_4_WOOD_CARD_COMPETITION';
    $this->desc = [
      clienttranslate(
        'If there are still 1/3/6/9 complete rounds left to play, you immediately get 1/2/3/4 <WOOD>. During scoring, each player with the most total <VEGETABLE> gets 3 bonus <SCORE>.'
      ),
    ];
    $this->extraVp = true;
    $this->sharedScoring = true;
    $this->players = '3+';

    $this->rulings = [
      clienttranslate('Includes <VEGETABLE> in fields and in supply.'),
    ];
  }

  public function onBuy($player)
  {
    $remainingTurns = 14 - Globals::getTurn();
    $woodMap = [0, 1, 1, 2, 2, 2, 3, 3, 3, 4];
    $toGain = $woodMap[$remainingTurns] ?? 4;
    if ($toGain != 0) {
      return $this->gainNode([WOOD => $toGain]);
    }
  }

  public function computeBonusScore()
  {
    $max = 0;
    $bonusPlayers = [];
    foreach (Players::getAll() as $player) {
      $vegetables = $player->countReserveResource(VEGETABLE) + $player->board()->getGrowingCrops(VEGETABLE)->count();
      if ($vegetables  > $max) {
        $max = $vegetables ;
        $bonusPlayers = [];
      }
      if ($vegetables  == $max) {
        $bonusPlayers[] = $player;
      }
    }
    foreach ($bonusPlayers as $player) {
      $this->addBonusScoringEntry(3, null, $player);
    }
  }
}
