<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Core\Engine;
use ARK\Models\Player;

class Jumping extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_JUMPING;
  }

  public function getDescription(): string|array
  {
    return [
      'log' => clienttranslate('Jumping ${n}'),
      'args' => [
        'n' => $this->getN(),
      ],
    ];
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function stJumping()
  {
    $this->pushParallelChild([
      'type' => NODE_SEQ,
      'childs' => [
        ['action' => ADVANCE_BREAK, 'args' => ['n' => $this->getN()], 'source' => \clienttranslate('Jumping')],
        ['action' => GAIN, 'args' => [MONEY => $this->getN()], 'source' => clienttranslate('Jumping action')],
      ],
    ]);
    $this->resolveAction([]);
  }
}
