<?php

namespace ARK\Cards\Sponsors;

class S210_ExpertOnTheAmericas extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S210_ExpertOnTheAmericas';
    $this->number = 210;
    $this->name = clienttranslate('Expert On The Americas');
    $this->lvl = 4;
    $this->continents = [AMERICAS];
    $this->effects = [
      IMMEDIATE => [clienttranslate('Gain 1 appeal for each Americas icon in your zoo.')],
      PASSIVE => [
        clienttranslate(
          'For each Americas icon you play into your zoo, you may place 1 kiosk on your zoo map for free. The usual building rules apply, including the distance rule for kiosks.'
        ),
      ],
      ENDGAME => [clienttranslate('Gain 1 conservation point if you have 5 or more kiosks in your zoo.')],
    ];

    $this->listeningIcon = AMERICAS;
    $this->listeningBonuses = [[KIOSK => 1]];
    $this->person = true;
  }

  public function getImmediate()
  {
    return [[APPEAL => AMERICAS]];
  }

  public function score()
  {
    $nKiosks = $this->getPlayer()
      ->map()
      ->getBuildingsOfType(KIOSK)
      ->count();
    $this->scoreConservation($nKiosks, [5 => 1]);
  }
}
