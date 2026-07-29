<?php

namespace ARK\Cards\Sponsors;

use ARK\Helpers\Utils;

class S258_NativeSeabirds extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S258_NativeSeabirds';
    $this->number = 258;
    $this->name = clienttranslate('Native Seabirds');
    $this->lvl = 5;
    $this->categories = [BIRD];
    $this->prerequisites = [\MAX_25_APPEAL => 1];
    $this->effects = [
      IMMEDIATE => [clienttranslate('Gain 1 appeal for every connected water space.')],
      ENDGAME => [
        clienttranslate(
          'Gain 1 conservation point for every 2 isolated water spaces. Example: If you have 5 water spaces that are isolated, you gain 2 conservation point.'
        ),
      ],
    ];
  }

  public function getImmediate()
  {
    $map = $this->getPlayer()->map();
    $waterCells = $map->getWaterHexes();
    $connectedCells = $map->getConnectedCells();
    $cells = Utils::intersectZones($waterCells, $connectedCells);
    $n = count($cells);
    return $n == 0 ? [] : [[APPEAL => $n]];
  }

  public function score()
  {
    $map = $this->getPlayer()->map();
    $isolatedCells = $map->getIsolatedCells();
    $waterCells = $map->getWaterHexes();
    $cells = Utils::intersectZones($waterCells, $isolatedCells);
    $n = intdiv(count($cells), 2);
    $this->scoreConservation($n);
  }
}
