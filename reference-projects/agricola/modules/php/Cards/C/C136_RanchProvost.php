<?php
namespace AGR\Cards\C;
use AGR\Core\Globals;
use AGR\Managers\Players;

class C136_RanchProvost extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C136_RanchProvost';
    $this->name = clienttranslate('Ranch Provost');
    $this->deck = 'C';
    $this->number = 136;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'If there are still 3/6/9 complete rounds left to play, you immediately get 2/3/4 <WOOD>. During scoring, each player with a pasture of highest capacity gets 3 bonus <SCORE>.'
      ),
    ];
    $this->players = '3+';
    $this->extraVp = true;
    $this->sharedScoring = true;
    $this->isCorbariusOrDulcinaria = true;
  }

  public function onBuy($player)
  {
    $remainingTurns = 14 - Globals::getTurn();
    $woodMap = [0, 0, 0, 2, 2, 2, 3, 3, 3, 4];
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
      $added = false;
      $zones = $player->board()->getAnimalsDropZones();
      foreach ($zones as $zone) {
        if ($zone['type'] != 'pasture') {
          continue;
        }

        $capacity = $zone['capacity'];
        if ($capacity > $max) {
          $max = $capacity;
          $bonusPlayers = [];
          $added = false;
        }

        if ($capacity == $max) {
          if (!$added) {
            $bonusPlayers[] = $player;
          }
          $added = true;
        }
      }
    }

    foreach ($bonusPlayers as $player) {
      $this->addBonusScoringEntry(3, null, $player);
    }
  }  
}
