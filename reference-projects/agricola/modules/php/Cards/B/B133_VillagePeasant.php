<?php

namespace AGR\Cards\B;

use AGR\Models\Occupation;

class B133_VillagePeasant extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B133_VillagePeasant';
    $this->name = clienttranslate('Village Peasant');
    $this->deck = 'B';
    $this->number = 133;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At the start of scoring, you get a number of <VEGETABLE> equal to the smallest of the numbers of major improvements, minor improvements, and occupations you have.'
      ),
    ];
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'BeforeEndOfGame';
  }

  public function onPlayerBeforeEndOfGame($player, $event)
  {
    $minorMajors = $player->getMajorMinor()->count();
    $majors = $player->getCards(MAJOR, true)->count()-$minorMajors;
    $minors = $player->getCards(MINOR, true)->count();
    $occupations = $player->getCards(OCCUPATION, true)->count();

    

    // minor/major cards are counted as minors. Check if counting them as majors increases $n.
    $n = 0;
    for ($i = 0; $i <= $minorMajors; $i++) {
      $minValue = min([$majors + $i, $minors - $i, $occupations]);
      $n = max([$n, $minValue]);
    }

    if ($n > 0) {
      return $this->gainNode([VEGETABLE => $n]);
    }
  }
}
