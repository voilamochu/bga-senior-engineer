<?php

namespace ARK\Cards\Sponsors;

use ARK\Helpers\Utils;

class S242_Geologist extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S242_Geologist';
    $this->number = 242;
    $this->name = clienttranslate('Geologist');
    $this->lvl = 5;
    $this->enclosureRequirements = [
      ROCK => 1,
    ];
    $this->effects = [
      IMMEDIATE => [
        clienttranslate(
          'Gain 3 appeal for every 2 rock icons in your zoo. You can find these on this card and otherwise as a requirement in the upper-left corner of cards.'
        ),
      ],
      PASSIVE => [
        clienttranslate(
          'Every time you cover a space adjacent to a rock space, gain 1 money. You gain 1 money for each of these spaces, even if you build several spaces adjacent to rock at the same time with one action.'
        ),
        clienttranslate(
          'You always gain 1 money per space, even if the space on which you are building is adjacent to more than one rock space.'
        ),
      ],
      ENDGAME => [
        clienttranslate(
          'Gain 1 conservation point if all rock spaces are connected, meaning that no rock space is only adjacent to empty spaces.'
        ),
      ],
    ];
    $this->person = true;
  }

  public function getImmediate()
  {
    $n = intdiv($this->countIcon(ROCK), 2);
    return $n == 0 ? [] : [[APPEAL => 3 * $n]];
  }

  public function score()
  {
    $map = $this->getPlayer()->map();
    $isolatedCells = $map->getIsolatedCells();
    $rockCells = $map->getRockHexes();
    $cells = Utils::intersectZones($rockCells, $isolatedCells);
    $n = empty($cells) ? 1 : 0;
    $this->scoreConservation($n);
  }
}
