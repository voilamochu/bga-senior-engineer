<?php
namespace ARK\Cards\Sponsors;
use ARK\Helpers\Utils;

class S264_FreerangeNewWorldMonkeys extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S264_FreerangeNewWorldMonkeys';
    $this->number = 264;
    $this->name = clienttranslate('Free-range New World Monkeys');
    $this->lvl = 5;
    $this->categories = [PRIMATE];
    $this->prerequisites = [\MAX_25_APPEAL => 1];
    $this->effects = [
      IMMEDIATE => [clienttranslate('Gain 1 appeal for every connected space with placement bonus.')],
      ENDGAME => [clienttranslate('Gain 1 conservation point for every 2 isolated spaces with placement bonuses.')],
    ];
  }

  public function getImmediate()
  {
    $map = $this->getPlayer()->map();
    $bonusCells = $map->getPlacementBonusHexes();
    $connectedCells = $map->getConnectedCells();
    $cells = Utils::intersectZones($bonusCells, $connectedCells);
    $n = count($cells);
    return $n == 0 ? [] : [[APPEAL => $n]];
  }

  public function score()
  {
    $map = $this->getPlayer()->map();
    $isolatedCells = $map->getIsolatedCells();
    $bonusCells = $map->getPlacementBonusHexes();
    $cells = Utils::intersectZones($bonusCells, $isolatedCells);
    $n = intdiv(count($cells), 2);
    $this->scoreConservation($n);
  }
}
