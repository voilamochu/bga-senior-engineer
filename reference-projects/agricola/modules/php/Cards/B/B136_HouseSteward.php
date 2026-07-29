<?php
namespace AGR\Cards\B;

use AGR\Core\Globals;
use AGR\Managers\Players;

class B136_HouseSteward extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B136_HouseSteward';
    $this->name = clienttranslate('House Steward');
    $this->deck = 'B';
    $this->number = 136;
    $this->category = POINTS_PROVIDER;
    $this->extraVp = true;
    $this->sharedScoring = true;
    $this->desc = [
      clienttranslate(
        'If there are still 1/3/6/9 complete rounds left to play, you immediately get 1/2/3/4 <WOOD>. During scoring, each player with the most rooms gets 3 bonus <SCORE>.'
      ),
    ];
    $this->players = '3+';
    $this->extraVp = true;
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
      $nbRooms = $player->countRooms();
      if ($nbRooms > $max) {
        $max = $nbRooms;
        $bonusPlayers = [];
      }

      if ($nbRooms == $max) {
        $bonusPlayers[] = $player;
      }
    }

    foreach ($bonusPlayers as $player) {
      $this->addBonusScoringEntry(3, null, $player);
    }
  }
}
