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

class Posturing extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_POSTURING;
  }

  public function getDescription(): string|array
  {
    return [
      'log' => clienttranslate('Place up to ${n} kiosk or pavilion for free'),
      'args' => [
        'n' => $this->getN(),
      ],
    ];
  }

  public function isOptional(): bool
  {
    return true;
  }

  public function isDoable(Player $player): bool
  {
    return $this->getPlayableBuildings($player, true);
  }

  public function getPlayableBuildings($player, $checkIsDoable = false)
  {
    return Build::getPlayableBuildingsAux($player, $checkIsDoable, ['pavilion', 'kiosk']);
  }

  public function argsPosturing()
  {
    $player = Players::getActive();

    return [
      'n' => $this->getN(),
      'allBuildings' => ['pavilion', 'kiosk'],
      'buildings' => $this->getPlayableBuildings($player),
      'free' => true,
    ];
  }

  public function actBuild($buildingType, $pos, $rotation)
  {
    self::checkAction('actBuild');
    $player = Players::getActive();
    $args = $this->argsPosturing();
    $bonuses = Build::actBuildAux($buildingType, $pos, $rotation, $args, $this->ctx->getSourceId());
    $this->insertBonusesFlow($bonuses, clienttranslate('placement bonus'));

    if ($this->getN() > 1) {
      $this->duplicateAction(['n' => $this->getN() - 1]);
    }
    $this->resolveAction([]);
  }
}
