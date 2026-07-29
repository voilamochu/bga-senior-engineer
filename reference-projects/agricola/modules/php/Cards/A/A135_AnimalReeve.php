<?php
namespace AGR\Cards\A;
use AGR\Core\Globals;
use AGR\Managers\Players;

class A135_AnimalReeve extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A135_AnimalReeve';
    $this->name = clienttranslate('Animal Reeve');
    $this->deck = 'A';
    $this->number = 135;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'If there are still 1/3/6/9 complete rounds left to play, you immediately get 1/2/3/4 <WOOD>. During scoring, each player with 2/3/4+ animals of each type gets 1/3/5 bonus <SCORE>.'
      ),
    ];
    $this->players = '3+';
    $this->extraVp = true;
    $this->sharedScoring = true;
    $this->isArtifexOrBubulcus = true;
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
    $map = [0, 0, 1, 3, 5];
    $bonusPlayers = [];

    foreach (Players::getAll() as $player) {
      $sheep = $player->countAnimalsOnBoard()[SHEEP];
      $pigs = $player->countAnimalsOnBoard()[PIG];
      $cattle = $player->countAnimalsOnBoard()[CATTLE];

      $sets = min([$sheep, $pigs, $cattle, 4]);
      $bonus = $map[$sets];

      if ($bonus > 0) {
        $bonusPlayers[$player->getId()] = $bonus;
      }
    }

    foreach ($bonusPlayers as $player => $bonus) {
      $this->addBonusScoringEntry($bonus, null, $player);
    }
  }
}
