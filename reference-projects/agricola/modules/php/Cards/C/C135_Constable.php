<?php
namespace AGR\Cards\C;

use AGR\Core\Globals;
use AGR\Managers\Players;

class C135_Constable extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C135_Constable';
    $this->name = clienttranslate('Constable');
    $this->deck = 'C';
    $this->number = 135;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'If there are still 1/3/6/9 complete rounds left to play, you immediately get 1/2/3/4 <WOOD>. During scoring, each player with no negative points in any scoring line gets 3 bonus <SCORE>.'
      ),
    ];
    $this->players = '3+';
    $this->extraVp = true;
    $this->sharedScoring = true;
    $this->map = [
      0 => 0,
      1 => 1,
      2 => 1,
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
  }

  public function onBuy($player)
  {
    if (Globals::getTurn() < 14) {
      return $this->gainNode([WOOD => $this->map[14 - Globals::getTurn()]]);
    }
  }

  public function computeSpecialScore($scores)
  {
    foreach ($scores as $pId => $score) {
      $negativeEntry = false;
      foreach ($score as $type => $values) {
        if ($type == 'total') {
          continue;
        }

        if (isset($values['entries'])) {
          foreach ($values['entries'] as $entry) {
            if ($entry['score'] < 0) {
              $negativeEntry = true;
            }
          }
        }
      }

      $this->addBonusScoringEntry($negativeEntry ? 0 : 3, null, Players::get($pId));
    }
  }
}
