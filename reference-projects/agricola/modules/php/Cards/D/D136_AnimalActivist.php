<?php
namespace AGR\Cards\D;
use AGR\Core\Globals;
use AGR\Managers\Players;

class D136_AnimalActivist extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D136_AnimalActivist';
    $this->name = clienttranslate('Animal Activist');
    $this->deck = 'D';
    $this->number = 136;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'If there are still 3/6/9 complete rounds left to play, you immediately get 2/3/4 <WOOD>. During scoring, each player with the most fenced stables gets 2 bonus <SCORE>.'
      ),
    ];
    $this->players = '3+';
    $this->extraVp = true;
    $this->sharedScoring = true;
    $this->map = [
      0 => 0,
      1 => 0,
      2 => 0,
      3 => 2,
      4 => 2,
      5 => 2,
      6 => 3,
      7 => 3,
      8 => 3,
      9 => 4,
      10 => 4,
      11 => 4,
      12 => 4,
      13 => 4,
      14 => 4,
    ];
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function onBuy($player)
  {
    if (Globals::getTurn() < 12) {
      return $this->gainNode([WOOD => $this->map[14 - Globals::getTurn()]]);
    }
  }

  public function computeBonusScore()
  {
    $max = 0;
    $bonusPlayers = [];
    foreach (Players::getAll() as $player) {
      $fencedStables = 0;
      $zones = $player->board()->getAnimalsDropZones();
      
      foreach ($zones as $zone) {
        if ($zone['type'] != 'pasture') {
          continue;
        }
        $fencedStables += count($zone['stables']);
      }

      if ($fencedStables > $max) {
        $max = $fencedStables;
        $bonusPlayers = [];
      }
      
      if ($fencedStables == $max) {
        $bonusPlayers[] = $player;
      }
    }

    foreach ($bonusPlayers as $player) {
      $this->addBonusScoringEntry(2, null, $player);
    }
  }
}
