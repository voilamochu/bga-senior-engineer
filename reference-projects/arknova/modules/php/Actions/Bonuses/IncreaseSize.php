<?php

namespace ARK\Actions\Bonuses;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Stats;
use ARK\Managers\Buildings;
use ARK\Models\Player;
use ARK\Actions\Build;

class IncreaseSize extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_INCREASE_SIZE;
  }

  public function isOptional(): bool
  {
    return true;
  }

  public function argsIncreaseSize()
  {
    $map = [
      'size-1' => 'size-2',
      'size-2' => 'size-3',
      'size-3' => 'size-4',
      'size-4' => 'size-5',
    ];

    $player = Players::getActive();
    $sizes = [];
    foreach ($player->map()->getRegularEnclosures() as $building) {
      $newSize = $map[$building['type']] ?? null;
      if (!is_null($newSize) && !in_array($newSize, $sizes)) {
        $sizes[] = $newSize;
      }
    }
    sort($sizes);

    return [
      'allBuildings' => $sizes,
      'allAffordableBuildings' => $sizes,
      'buildings' => Build::getPlayableBuildingsAux($player, false, $sizes, ['mustCoverOneBuilding' => true]),
    ];
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Increase size of one standard enclosure');
  }

  public function actIncreaseSize($buildingType, $pos, $rotation)
  {
    self::checkAction('actIncreaseSize');
    $args = $this->getArgs();
    $option = Build::getCheckedOption($buildingType, $pos, $rotation, $args);
    $player = Players::getActive();

    // Place it on the board
    $map = $player->map();
    $hexes = $map->getCoveredHexes($buildingType, $pos, $rotation, false);
    $buildingId = null;
    foreach ($hexes as $hex) {
      $building = $map->getBuildingAtPos($hex);
      if (!is_null($building)) $buildingId = $building['id'];
    }
    $coveredBuilding = Buildings::getSingle($buildingId);

    list($building, $bonuses) = $player->map()->addBuilding($buildingType, $pos, $rotation);
    if ($coveredBuilding['state'] == 1) {
      $bonuses[] = [APPEAL => 2, 'sourceId' => 'S269_ConferenceOnAustralia'];
      $building['state'] = 1;
      Buildings::setState($building['id'], 1);
      $player->map()->refresh();
    }
    Buildings::remove($buildingId);
    Notifications::increaseSize($player, $coveredBuilding, $building);
    $player->map()->refresh();

    $this->insertBonusesFlow($bonuses, clienttranslate('placement bonus'));

    $this->resolveAction([]);
  }
}
