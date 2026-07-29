<?php
namespace AGR\Cards\B;

class B39_Loom extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B39_Loom';
    $this->name = clienttranslate('Loom');
    $this->deck = 'B';
    $this->number = 39;
    $this->category = POINTS_PROVIDER;
    $this->extraVp = true;
    $this->desc = [
      clienttranslate(
        'In the field phase of each harvest, if you have at least 1/4/7 <SHEEP>, you get 1/2/3 <FOOD>. During scoring, you get 1 bonus <SCORE> for every 3 <SHEEP>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 2,
    ];
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
      $event['type'] == 'HarvestFieldPhase' &&
      $this->getPlayer()->countAnimalsOnBoard()[SHEEP] != 0;
  }

  public function onPlayerHarvestFieldPhase($player, $event)
  {
    $foodMap = [0, 1, 1, 1, 2, 2, 2, 3];
    $n = $foodMap[$player->countAnimalsOnBoard()[SHEEP]] ?? 3;
    return $this->gainNode([FOOD => $n]);
  }

  public function computeBonusScore()
  {
    $player = $this->getPlayer();
    $sheeps = $player->countAnimalsOnBoard()[SHEEP];
    if ($sheeps >= 3) {
      $this->addBonusScoringEntry(intdiv($sheeps, 3));
    }
  }
}
