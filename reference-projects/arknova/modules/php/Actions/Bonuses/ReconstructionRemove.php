<?php

namespace ARK\Actions\Bonuses;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Stats;
use ARK\Managers\Buildings;
use ARK\Models\Player;

class ReconstructionRemove extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_RECONSTRUCTION_REMOVE;
  }

  public function isOptional(): bool
  {
    return true;
  }

  public function argsReconstructionRemove()
  {
    $player = Players::getActive();
    return [
      'buildingIds' => $player->map()->getBuildings()->getIds(),
    ];
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Reposition up to 3 buildings');
  }

  public function actReconstructionRemove(array $buildingIds)
  {
    self::checkAction('actReconstructionRemove');

    $player = Players::getActive();
    $args = $this->getArgs();
    foreach ($buildingIds as $buildingId) {
      if (!in_array($buildingId, $args['buildingIds'])) {
        throw new \BgaVisibleSystemException('Invalid building. Should not happen');
      }
    }

    // Store some informations about current
    $alreadyScoredBonuses = [];
    $map = $player->map();
    if ($map->countEmptySpaces() == 0) {
      $alreadyScoredBonuses[] = 'full';
    }

    Buildings::moveOnHold($buildingIds);
    $buildings = Buildings::getMany($buildingIds);
    Notifications::reconstructionRemove($player, $buildings);
    $player->map()->refresh(); // Refresh ZooMap cached datas

    $this->insertAsChild([
      'action' => RECONSTRUCTION_PLACE_BACK,
      'args' => ['previousBonuses' => $alreadyScoredBonuses]
    ]);

    $this->resolveAction([]);
  }
}
