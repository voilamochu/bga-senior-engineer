<?php
namespace AGR\Cards\E;
use AGR\Core\Globals;
use AGR\Helpers\Utils;
use AGR\Managers\Players;


class E136_AnimalHusbandryWorker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E136_AnimalHusbandryWorker';
    $this->name = clienttranslate('Animal Husbandry Worker');
    $this->deck = 'E';
    $this->author = 'really_unknow4';
    $this->number = 136;
    $this->category = 'BONUS_POINTS_-_4_WOOD_CARD_COMPETITION';
    $this->desc = [
      clienttranslate(
        'If there are still 3/6/9 complete rounds left to play, you immediately get 2/3/4 <WOOD> and a __Build Fences__ action. During scoring, each player with the most pastures gets 2 <SCORE>.'
      ),
    ];
    $this->extraVp = true;
    $this->sharedScoring = true;
    $this->players = '3+';
    $this->rulings = [
      clienttranslate('This card only gives a __Build Fences__ action if there are at least 3 complete rounds left to play.'),
    ];
  }

   public function onBuy($player)
  {
    $remainingTurns = 14 - Globals::getTurn();
    $woodMap = [0, 0, 0, 2, 2, 2, 3, 3, 3, 4];
    $toGain = $woodMap[$remainingTurns] ?? 4;

    if ($toGain != 0) {
      return [
        'type' => NODE_SEQ,
        'childs' => [
          $this->gainNode([WOOD => $toGain]),
          [
            'action' => FENCING,
             'optional'=>true,
            'source' => $this->name,
            'args' => ['costs' => Utils::formatCost([WOOD => 1])],
          ],
        ]
      ];
    }
  }

  public function computeBonusScore()
  {
    $max = 0;
    $bonusPlayers = [];
    foreach (Players::getAll() as $player) {
      $pastures = 0;
      $zones = $player->board()->getAnimalsDropZones();
      
      foreach ($zones as $zone) {
        if ($zone['type'] != 'pasture') {
          continue;
        }
        $pastures++;
      }

      if ($pastures > $max) {
        $max = $pastures;
        $bonusPlayers = [];
      }
      
      if ($pastures == $max) {
        $bonusPlayers[] = $player;
      }
    }
    foreach ($bonusPlayers as $player) {
      $this->addBonusScoringEntry(2, null, $player);
    }
  }
}
