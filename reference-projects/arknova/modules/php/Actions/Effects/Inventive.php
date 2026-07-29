<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Core\Engine;
use ARK\Models\Player;

class Inventive extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_INVENTIVE;
  }

  public function getDescription(): string|array
  {
    $tokens = ZooCards::get($this->ctx->getSourceId())->getInventiveTokens();
    return [
      'log' => clienttranslate('Gain ${n} <XTOKEN>'),
      'args' => [
        'n' => $tokens,
      ],
    ];
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function stInventive()
  {
    $player = Players::getActive();

    $tokens = ZooCards::get($this->ctx->getSourceId())->getInventiveTokens();

    if ($tokens == 0) {
      $this->resolveAction([]);
      return;
    }

    $player->incXToken($tokens, true, clienttranslate('Inventive'));
    $this->resolveAction([]);
  }
}
