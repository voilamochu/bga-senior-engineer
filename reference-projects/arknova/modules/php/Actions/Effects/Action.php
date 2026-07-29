<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Engine;
use ARK\Managers\Meeples;
use ARK\Models\Player;

class Action extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_ACTION;
  }

  public function getDescription(): string|array
  {
    return [
      'log' => clienttranslate('Perform ${n}'),
      'args' => [
        'n' => $this->getN(),
      ],
    ];
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function stAction()
  {
    $player = Players::getActive();

    $type = $this->getN();
    $card = $player->getActionCardOfType($type);

    Engine::insertAsChild([
      'action' => CHOOSE_ACTION_CARD,
      'pId' => $player->getId(),
      'args' => ['cardId' => $card->getId(), 'canGainXToken' => false],
      'optional' => true,
    ]);

    $this->resolveAction([]);
  }
}
