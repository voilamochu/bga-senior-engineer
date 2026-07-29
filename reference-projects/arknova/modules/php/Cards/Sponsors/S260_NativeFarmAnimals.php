<?php
namespace ARK\Cards\Sponsors;
use ARK\Helpers\Utils;

class S260_NativeFarmAnimals extends \ARK\Models\Sponsor
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'S260_NativeFarmAnimals';
    $this->number = 260;
    $this->name = clienttranslate('Native Farm Animals');
    $this->lvl = 5;
    $this->categories = [HERBIVORE];
    $this->prerequisites = [\MAX_25_APPEAL => 1];
    $this->effects = [
      IMMEDIATE => [
        clienttranslate(
          'Gain 1 appeal for each border space that is connected, but itself not covered (only building spaces count).'
        ),
      ],
      ENDGAME => [
        clienttranslate(
          'Gain 1 conservation point for every 6 empty building spaces that form a contiguous group. Building spaces are all spaces on your zoo map except rock and water spaces.'
        ),
        \clienttranslate(
          'Empty spaces requiring upgraded Build action card also count, even if you were not normally allowed to build on them because you have not upgraded the Build Action card. '
        ),
        clienttranslate('Each group of 6 contiguous empty spaces counts, whether it is contiguous with other groups or not.'),
      ],
    ];
  }

  public function getImmediate()
  {
    $player = $this->getPlayer();
    $borderCells = $player->map()->getBorderCells();
    $connectedCells = $player->map()->getConnectedCells();
    $nonBuildingCells = $player->map()->getNonBuildingCells();
    $cells = Utils::intersectZones($borderCells, $connectedCells);
    $cells = Utils::diffZones($cells, $nonBuildingCells);
    $n = count($cells);
    return $n == 0 ? [] : [[APPEAL => $n]];
  }

  public function score()
  {
    $map = $this->getPlayer()->map();
    $grid = $map->createGrid(INFTY);
    $mark = 0;
    $components = [];

    foreach ($grid as $x => $col) {
      foreach ($col as $y => $t) {
        $cell = ['x' => $x, 'y' => $y];
        if ($grid[$x][$y] < INFTY || !is_null($map->getBuildingAtPos($cell)) || !$map->isBuildingCell($cell)) {
          continue;
        }

        $mark++;
        $componentSize = 0;
        $queue = [$cell];
        while (!empty($queue)) {
          $p = array_pop($queue);
          if ($grid[$p['x']][$p['y']] < INFTY) {
            continue;
          }

          $componentSize++;
          $grid[$p['x']][$p['y']] = $mark;
          foreach ($map->getNeighbours($p) as $neighbour) {
            if (
              $grid[$neighbour['x']][$neighbour['y']] < INFTY ||
              !is_null($map->getBuildingAtPos($neighbour)) ||
              !$map->isBuildingCell($neighbour)
            ) {
              continue;
            }
            $queue[] = $neighbour;
          }
        }
        $components[] = $componentSize;
      }
    }

    $n = 0;
    foreach ($components as $size) {
      $n += intdiv($size, 6);
    }
    if ($n > 0) {
      $this->scoreConservation($n);
    }
  }
}
