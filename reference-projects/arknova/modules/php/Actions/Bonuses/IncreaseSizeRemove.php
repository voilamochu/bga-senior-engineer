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

class IncreaseSizeRemove extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_INCREASE_SIZE_PLACE_BACK;
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

    return [
      'buildings' => Build::getPlayableBuildingsAux($player, false, $sizes, ['mustCoverOneBuilding' => true]),
    ];
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Increase size of one standard enclosure');
  }

  public function actIncreaseSize(int $buildingId)
  {
    self::checkAction('actIncreaseSizeRemove');

    $player = Players::getActive();
    $args = $this->getArgs();
    if (!in_array($buildingId, $args['buildingIds'])) {
      throw new \BgaVisibleSystemException('Invalid building. Should not happen');
    }

    $building = Buildings::getSingle($buildingId);
    Notifications::increaseSizeRemove($player, $building);
    $player->map()->refresh(); // Refresh ZooMap cached datas

    $this->insertAsChild([
      'action' => INCREASE_SIZE_PLACE_BACK,
      'args' => ['buildingId' => $buildingId]
    ]);

    $this->resolveAction([]);
  }
}
