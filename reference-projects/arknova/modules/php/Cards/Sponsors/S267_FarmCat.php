<?php

namespace ARK\Cards\Sponsors;

class S267_FarmCat extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->supported = [MW];
    $this->id = 'S267_FarmCat';
    $this->number = 267;
    $this->name = clienttranslate('Farm Cat');
    $this->lvl = 5;
    $this->effects = [
      IMMEDIATE => [clienttranslate('Gain 1 appeal if the sum of your kiosks and pavilions is 1 to 3, 3 appeal if it is 4 to 6, or 5 appeal if the sum is 7 or more.')],
      ENDGAME => [
        clienttranslate('Gain 1 conservation point for each of your kiosks with at least 3 empty building spaces next to it (up to a maximum of 3 conservation points).'),
        clienttranslate('Building spaces are all spaces on your zoo map except rock and water spaces.')
      ],
    ];
    $this->prerequisites = [MAX_25_APPEAL => 1];
    $this->categories = [PREDATOR];
  }

  public function getImmediate()
  {
    $nKiosks = $this->getPlayer()
      ->map()
      ->getBuildingsOfType(KIOSK)
      ->count();
    $nPavilions = $this->getPlayer()
      ->map()
      ->getBuildingsOfType(PAVILION)
      ->count();
    $n = $nKiosks + $nPavilions;

    $m = 0;
    if ($n >= 1) $m = 1;
    if ($n >= 4) $m = 3;
    if ($n >= 7) $m = 5;

    return $m == 0 ? [] : [[APPEAL => $m]];
  }

  public function score()
  {
    $nKiosks = 0;
    $map = $this->getPlayer()->map();
    foreach ($map->getBuildingsOfType(KIOSK) as $building) {
      $cells = $map->getEnclosureNeighbourHexes($building);
      $nFree = 0;
      foreach ($cells as $cell) {
        if ($map->isCellAvailableToBuild($cell, [UPGRADED_BUILD_CARD => true])) {
          $nFree++;
        }
      }

      if ($nFree >= 3) {
        $nKiosks++;
        if ($nKiosks >= 3) break;
      }
    }

    if ($nKiosks > 0) {
      $this->scoreConservation($nKiosks);
    }
  }
}
