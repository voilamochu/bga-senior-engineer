<?php

namespace ARK\Cards\FinalScoring;

class F015_CateredPicnicAreas extends \ARK\Models\FinalScoring
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'F015_CateredPicnicAreas';
    $this->number = 15;
    $this->name = clienttranslate('Catered Picnic Areas');
    $this->icon = 'KIOSK-PLUS-PAVILION';
    $this->desc = clienttranslate('Gain <CONSERVATION> for **sets of kiosk and pavilion** (do not have to be adjacent).');
    $this->scoreMap = [2 => 1, 3 => 2, 4 => 3, 5 => 4];
  }

  public function getQuantity()
  {
    $nKiosks = $this->getPlayer()
      ->map()
      ->getBuildingsOfType(KIOSK)
      ->count();
    $nPavilions = $this->getPlayer()
      ->map()
      ->getBuildingsOfType(PAVILION)
      ->count();

    return min($nKiosks, $nPavilions);
  }
}
