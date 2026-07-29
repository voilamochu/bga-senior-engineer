<?php

namespace ARK\Cards\Sponsors;

use ARK\Helpers\Utils;

class S241_Hydrologist extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S241_Hydrologist';
    $this->number = 241;
    $this->name = clienttranslate('Hydrologist');
    $this->lvl = 5;
    $this->enclosureRequirements = [
      WATER => 1,
    ];
    $this->effects = [
      IMMEDIATE => [
        clienttranslate(
          'Gain 1 appeal for each water icon in your zoo. You can find these on this card and otherwise as a requirement in the upper-left corner of cards.'
        ),
      ],
      PASSIVE => [
        clienttranslate(
          'Every time you cover a space adjacent to a water space, gain 1 money. You gain 1 money for each of these spaces, even if you build several spaces adjacent to water at the same time with one action.'
        ),
        clienttranslate(
          'You always gain 1 money per space, even if the space on which you are building is adjacent to more than one water space.'
        ),
      ],
      ENDGAME => [
        clienttranslate(
          'Gain 1 conservation point if all water spaces are connected, meaning that no water space is only adjacent to empty spaces.'
        ),
      ],
    ];
    $this->person = true;
  }

  public function getImmediate()
  {
    $n = $this->countIcon(WATER);
    return $n == 0 ? [] : [[APPEAL => $n]];
  }

  public function score()
  {
    $map = $this->getPlayer()->map();
    $isolatedCells = $map->getIsolatedCells();
    $waterCells = $map->getWaterHexes();
    $cells = Utils::intersectZones($waterCells, $isolatedCells);
    $n = empty($cells) ? 1 : 0;
    $this->scoreConservation($n);
  }
}
