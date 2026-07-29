<?php

namespace ARK\Actions\Bonuses;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Stats;
use ARK\Managers\Buildings;
use ARK\Actions\Build;
use ARK\Helpers\Utils;

class ReconstructionPlaceBack extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_RECONSTRUCTION_PLACE_BACK;
  }

  public function getBuildingsToPlaceBack()
  {
    return Buildings::getInLocation('hold');
  }

  public function argsReconstructionPlaceBack()
  {
    $player = Players::getActive();
    $buildings = $this->getBuildingsToPlaceBack();
    $buildingTypes = $buildings->map(fn($b) => $b['type']);

    return [
      'buildings' => Build::getPlayableBuildingsAux($player, false, $buildingTypes),
      'allBuildings' => $buildings,
    ];
  }

  public function actReconstructionPlaceBack($buildingType, $pos, $rotation, $buildingId)
  {
    self::checkAction('actReconstructionPlaceBack');

    $args = $this->getArgs();
    $player = Players::getActive();

    // Sanity check
    $player = Players::getActive();
    $options = $args['buildings'][$buildingType] ?? null;
    if (is_null($options)) {
      throw new \BgaVisibleSystemException('You cannot build that type of building. Should not happen');
    }
    $optionIndex = Utils::search($options, function ($option) use ($pos) {
      return $option['pos']['x'] == $pos['x'] && $option['pos']['y'] == $pos['y'];
    });
    $option = $optionIndex === false ? null : $options[$optionIndex];
    if (is_null($option) || !in_array($rotation, $option['rotations'])) {
      throw new \BgaVisibleSystemException('You cannot build this building here. Should not happen');
    }
    $building = $args['allBuildings'][$buildingId] ?? null;
    if (is_null($building)) {
      throw new \BgaVisibleSystemException('Invalid building to place back. Should not happen');
    }

    // Place it on the board
    $previousBonuses = $this->getCtxArg('previousBonuses');
    list($building, $bonuses) = $player->map()->placeBackBuilding($buildingId, $pos, $rotation, $previousBonuses);

    Notifications::placeBackBuilding($player, $building);

    $this->insertBonusesFlow($bonuses);

    // Are we done yet ?
    if ($this->getBuildingsToPlaceBack()->count() > 0) {
      $this->duplicateAction();
    }

    $this->resolveAction([$buildingId, $pos, $rotation]);
  }
}
