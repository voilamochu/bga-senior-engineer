<?php
namespace ARK\Cards\Sponsors;

class S257_SideEntrance extends UniqueBuildingSponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S257_SideEntrance';
    $this->number = 257;
    $this->name = clienttranslate('Side Entrance');
    $this->lvl = 3;
    $this->enclosure = SIDE_ENTRANCE;
    $this->effects = [
      IMMEDIATE => [
        clienttranslate(
          'Place the Side Entrance unique building on your zoo map on 2 border spaces. It does not have to be adjacent to existing buildings. Otherwise, the usual building rules apply. From now on, you may build buildings adjacent to the Side Entrance as well.'
        ),
      ],
      INCOME => [
        clienttranslate(
          'Gain 2 money for each building (except empty standard enclosures), that is adjacent to the Side Entrance (similar to a kiosk, except that the Side Entrance itself can be adjacent to a kiosk).'
        ),
      ],
      ENDGAME => [
        clienttranslate(
          'Gain 5 appeal if you have completely covered your zoo map (all spaces except the rock and water spaces).'
        ),
      ],
    ];
  }

  public function getIncome()
  {
    $map = $this->getPlayer()->map();
    $entrance = $map->getBuildingsOfType(SIDE_ENTRANCE)->first();
    if (!is_null($entrance)) {
      $money = 2 * $map->countBuildingNeighbours($entrance);
      return [[\MONEY => $money]];
    }
  }

  public function score()
  {
    $player = $this->getPlayer();
    $nEmpty = $player->map()->countEmptySpaces();
    if ($nEmpty == 0) {
      $player->incAppeal(5, true, $this->getName());
    }
  }
}
