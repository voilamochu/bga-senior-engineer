<?php

namespace ARK\Cards\Sponsors;

class S211_ExpertOnEurope extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S211_ExpertOnEurope';
    $this->number = 211;
    $this->name = clienttranslate('Expert On Europe');
    $this->lvl = 5;
    $this->continents = [EUROPE];
    $this->effects = [
      IMMEDIATE => [clienttranslate('Gain 1 appeal for each Europe icon in your zoo.')],
      PASSIVE => [
        clienttranslate(
          'For each Europe icon you play into your zoo, you may place a 1-space enclosure on your zoo map for free. The usual building rules apply.'
        ),
      ],
      ENDGAME => [clienttranslate('Gain 1 conservation point if you have 5 or more occupied 1-space enclosures in your zoo.')],
    ];

    $this->listeningIcon = EUROPE;
    $this->listeningBonuses = [[BONUS_SIZE_1_ENCLOSURE => 1]];
    $this->person = true;
  }

  public function getImmediate()
  {
    return [[APPEAL => EUROPE]];
  }

  public function score()
  {
    $nEnclosures = $this->getPlayer()
      ->map()
      ->getBuildingsOfType('size-1')
      ->filter(function ($enclosure) {
        return $enclosure['state'] == 1;
      })
      ->count();

    $this->scoreConservation($nEnclosures, [5 => 1]);
  }
}
