<?php

namespace ARK\Actions;

use ARK\Models\Player;
use ARK\Managers\Players;

class Pay extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_PAY;
  }

  public function getDescription(): array
  {
    return [
      'log' => clienttranslate('Pay <MONEY:${n}>'),
      'args' => [
        'n' => $this->getN()
      ]
    ];
  }

  public function isDoable(Player $player): bool
  {
    return $player->getMoney() >= $this->getN();
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function stPay()
  {
    $player = Players::getActive();
    $player->pay($this->getN(), true, $this->ctx->getSource());
    $this->resolveAction([]);
  }
}
