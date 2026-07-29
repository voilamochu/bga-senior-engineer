<?php

namespace AGR\Cards\B;

use AGR\Models\Occupation;

class B153_Housemaster extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B153_Housemaster';
    $this->name = clienttranslate('Housemaster');
    $this->deck = 'B';
    $this->number = 153;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'During scoring, total the base point values of your major improvements. The smallest value counts double. If the total is at least 5/7/9/11, you get 1/2/3/4 bonus <SCORE>.'
      ),
    ];
    $this->players = '4+';
    $this->extraVp = true;
    $this->isArtifexOrBubulcus = true;
  }

  public function computeBonusScore()
  {
    $bonus = 0;
    $player = $this->getPlayer();

    $points = [];
    foreach ($player->getCards(MAJOR, true) as $major) {
      $points[] = $major->getScore();
    }

    // Always add D59_EarthOven and D60_LargePottery as with 3VP it always makes sense to add
    // SpecialCase A60_OrientalFireplace.
    //     If smallest other VP value is 3VP, then doubling that is better than counting A60_OrientalFireplace
    //     If smallest other VP value is 2VP, then 2* 2VP == 2VP + 2* 1VP == 4VP
    $orientalVp = 0;
    $playedMinors = $player->getCards(MINOR, true);
    foreach ($playedMinors as $minor) {
      if ($minor->id == 'A60_OrientalFireplace') {
        $orientalVp = $minor->getScore();
      }
    }

    // No check for only A60_OrientalFireplace > 0 needed as total is only 2 then.
    if ($points == []) {
      return null;
    }

    // Only add A60_OrientalFireplace if min($points) > 1
    if ($orientalVp == 0 || min($points) > 1) {
      $total = array_sum($points) + min($points);
    } else {
      $total = array_sum($points) + min($points) + $orientalVp;
    }

    if ($total >= 5) $bonus = 1;
    if ($total >= 7) $bonus = 2;
    if ($total >= 9) $bonus = 3;
    if ($total >= 11) $bonus = 4;

    if ($bonus > 0) {
      $this->addBonusScoringEntry($bonus);
    }
  }
}
