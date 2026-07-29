<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Core\Engine;
use ARK\Helpers\Utils;
use ARK\Helpers\FlowConvertor;
use ARK\Actions\Build;
use ARK\Models\Player;

class Peacocking extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_PEACOCKING;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Place a large bird aviary for free');
  }

  public function isOptional(): bool
  {
    return true;
  }

  public function isDoable(Player $player): bool
  {
    return !$player->map()->hasBuilding(LARGE_BIRD_AVIARY) && $this->getPlayableBuildings($player, true);
  }

  public function getPlayableBuildings($player, $checkIsDoable = false)
  {
    return Build::getPlayableBuildingsAux($player, $checkIsDoable, [LARGE_BIRD_AVIARY]);
  }

  public function argsPeacocking()
  {
    $player = Players::getActive();

    return [
      'n' => $this->getN(),
      'allBuildings' => [LARGE_BIRD_AVIARY],
      'buildings' => $this->getPlayableBuildings($player),
      'free' => true,
    ];
  }

  public function actBuild($buildingType, $pos, $rotation)
  {
    self::checkAction('actBuild');
    $player = Players::getActive();
    $args = $this->argsPeacocking();
    $bonuses = Build::actBuildAux($buildingType, $pos, $rotation, $args, $this->ctx->getSourceId());
    $this->insertBonusesFlow($bonuses, clienttranslate('placement bonus'));

    if ($this->getN() > 1) {
      $this->duplicateAction(['n' => $this->getN() - 1]);
    }
    $this->resolveAction([]);
  }
}
