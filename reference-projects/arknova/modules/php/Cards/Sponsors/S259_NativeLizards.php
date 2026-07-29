<?php
namespace ARK\Cards\Sponsors;
use ARK\Helpers\Utils;

class S259_NativeLizards extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S259_NativeLizards';
    $this->number = 259;
    $this->name = clienttranslate('Native Lizards');
    $this->lvl = 5;
    $this->categories = [REPTILE];
    $this->prerequisites = [\MAX_25_APPEAL => 1];
    $this->effects = [
      IMMEDIATE => [clienttranslate('Gain 1 appeal for every connected rock space.')],
      ENDGAME => [
        clienttranslate(
          'Gain 1 conservation point for every 2 isolated rock spaces. Example: If you have 3 rock spaces that are isolated, you gain 1 conservation point.'
        ),
      ],
    ];
  }

  public function getImmediate()
  {
    $map = $this->getPlayer()->map();
    $rockCells = $map->getRockHexes();
    $connectedCells = $map->getConnectedCells();
    $cells = Utils::intersectZones($rockCells, $connectedCells);
    $n = count($cells);
    return $n == 0 ? [] : [[APPEAL => $n]];
  }

  public function score()
  {
    $map = $this->getPlayer()->map();
    $isolatedCells = $map->getIsolatedCells();
    $rockCells = $map->getRockHexes();
    $cells = Utils::intersectZones($rockCells, $isolatedCells);
    $n = intdiv(count($cells), 2);
    $this->scoreConservation($n);
  }
}
