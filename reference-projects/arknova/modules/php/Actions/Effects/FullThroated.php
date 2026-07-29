<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Core\Engine;
use ARK\Models\Player;

class FullThroated extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_FULL_THROATED;
  }

  public function isDoable(Player $player): bool
  {
    return !is_null($player->getNextWorkerInSupply());
  }

  public function isOptional(): bool
  {
    $player = Players::getActive();
    return is_null($player->getNextWorkerInSupply());
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Gain 1 new association worker');
  }

  public function stFullThroated()
  {
    $player = Players::getActive();

    $bonuses = $player->gainWorker();
    $n = $player->countWorkersOnBoard();
    $msgs = [
      1 => clienttranslate('1st worker bonus'),
      2 => clienttranslate('2nd worker bonus'),
      3 => clienttranslate('last worker bonus'),
    ];
    $this->insertBonusesFlow($bonuses, $msgs[$n]);
    $this->resolveAction([]);
  }
}
