<?php

namespace ARK\Actions\Effects;

use ARK\Core\Notifications;
use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Models\Player;

class Sprint extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_SPRINT;
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function getDescription(): string|array
  {
    return [
      'log' => clienttranslate('Sprint ${n}'),
      'args' => [
        'n' => $this->getN(),
      ],
    ];
  }

  public function isIrreversible(?Player $player = null): bool
  {
    return true;
  }

  public function stSprint()
  {
    $this->checkCanTakeIrreversible();
    $player = Players::getActive();
    $cards = ZooCards::draw($player, $this->getN());
    Notifications::sprint($player, $cards);
    $this->resolveAction([], true);
  }
}
