<?php

namespace AGR\Cards\D;

use AGR\Models\MinorImprovement;

class D30_ArtisanDistrict extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D30_ArtisanDistrict';
    $this->name = clienttranslate('Artisan District');
    $this->deck = 'D';
    $this->number = 30;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'During scoring, you get 2/5/8 bonus <SCORE> for having 3/4/5 major improvements from the bottom row of the supply board.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      STONE => '1',
    ];
    $this->extraVp = true;
    $this->prerequisite = clienttranslate('3 Occupations');
    $this->occupationPrerequisites = ['min' => 3];
    $this->isCorbariusOrDulcinaria = true;
  }

  public function computeBonusScore()
  {

    $player = $this->getPlayer();
    $bonus = 0;

    foreach ($player->getCards(MAJOR, true) as $major) {
      if (in_array(
        $major->getId(),
        ['Major_ClayOven', 'Major_StoneOven', 'Major_Joinery', 'Major_Pottery', 'Major_Basket']
      )) {
        $bonus++;
      }
    }

    $bonusMap = [0, 0, 0, 2, 5, 8];
    $bonuss = $bonusMap[$bonus] ?? 8;
    $this->addBonusScoringEntry($bonuss);
  }
}
