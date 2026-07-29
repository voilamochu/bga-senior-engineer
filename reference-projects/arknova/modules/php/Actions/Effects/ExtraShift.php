<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Models\ZooCard;
use ARK\Managers\Meeples;
use ARK\Models\Player;

class ExtraShift extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_EXTRA_SHIFT;
  }

  public function getDescription(): string|array
  {
    return [
      'log' => clienttranslate('Extra Shift ${n}'),
      'args' => [
        'n' => $this->getN(),
      ],
    ];
  }

  public function isOptional(): bool
  {
    return !$this->isDoable(Players::getActive());
  }

  public function isDoable(Player $player): bool
  {
    return $player->countUsedWorkers() > 0;
  }

  public function getOccupiedWorkers(Player $player)
  {
    $occupied = [];
    for ($i = 2; $i < 6; $i++) {
      $workers = $player->getWorkersInSlot($i);
      if ($workers->count() > 0) {
        $occupied[$i] = $workers->getIds();
      }
    }
    return $occupied;
  }

  public function argsExtraShift()
  {
    $workingWorkers = $this->getOccupiedWorkers(Players::getActive());

    return [
      'n' => $this->getN(),
      'slots' => array_keys($workingWorkers)
    ];
  }

  public function actExtraShift($slot)
  {
    $this->checkAction('actExtraShift');
    $player = Players::getActive();
    $args = $this->getArgs();

    if (!in_array($slot, $args['slots'])) {
      throw new \BgaVisibleSystemException('Incorrect slot. Should not happen');
    }

    $workingWorker = $player->getWorkersInSlot($slot)->first();
    $workingWorker = Meeples::moveBackWorker($workingWorker['id']);
    Notifications::extraShift($player, $workingWorker, $slot);

    $this->resolveAction([]);
  }
}
