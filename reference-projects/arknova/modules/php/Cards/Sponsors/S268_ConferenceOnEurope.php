<?php

namespace ARK\Cards\Sponsors;

use ARK\Managers\Meeples;

class S268_ConferenceOnEurope extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'S268_ConferenceOnEurope';
    $this->number = 268;
    $this->name = clienttranslate('Conference On Europe');
    $this->lvl = 5;
    $this->effects = [
      IMMEDIATE => [clienttranslate('Gain 2 money for 1 to 2 Europe icons, 5 money for 3 to 4 Europe icons, or 10 money for 5 or more Europe icons in your zoo.')],
      PASSIVE => [clienttranslate('For each Europe icon you play into your zoo: Mark one card')],
      ENDGAME => [clienttranslate('Gain 1 conservation point if you have made at least 3 donations during the game.')],
    ];
    $this->continents = [EUROPE];

    $this->listeningIcon = EUROPE;
    $this->listeningBonuses = [[MARK => 1]];
  }

  public function getImmediate()
  {
    return [[MONEY => EUROPE, 'map' => [0, 2, 2, 5, 5, 10]]];
  }

  public function score()
  {
    $n = Meeples::getTokensOnDonation()->filter(fn($m) => $m['pId'] == $this->pId)->count();
    if ($n >= 3) {
      $this->scoreConservation(1);
    }
  }
}
