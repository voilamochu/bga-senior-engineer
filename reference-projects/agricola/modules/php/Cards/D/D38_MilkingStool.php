<?php
namespace AGR\Cards\D;

class D38_MilkingStool extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D38_MilkingStool';
    $this->name = clienttranslate('Milking Stool');
    $this->deck = 'D';
    $this->number = 38;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'In the field phase of each harvest, if you have at least 1/3/5 <CATTLE>, you get 1/2/3 <FOOD>. During scoring, you get 1 bonus <SCORE> for every 2 <CATTLE> you have.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->extraVp = true;
    $this->prerequisite = clienttranslate('2 Occupations');
    $this->occupationPrerequisites = ['min' => 2];
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'HarvestFieldPhase';
  }

  public function onPlayerHarvestFieldPhase($player)
  {
    $n = $player->getExchangeResources()[CATTLE];
    if ($n == 0) {
      return null;
    }

    $bonus = $n >= 3 ? 2 : 1;
    if ($n >= 5) {
      $bonus = 3;
    }

    return $this->gainNode([FOOD => $bonus]);
  }

  public function computeBonusScore()
  {
    $player = $this->getPlayer();
    $n = $player->getExchangeResources()[CATTLE];
    $bonus = intdiv($n, 2);
    if ($bonus != 0) {
      $this->addBonusScoringEntry($bonus);
    }
  }
}
