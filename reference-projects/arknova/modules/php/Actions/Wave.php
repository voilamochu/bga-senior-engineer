<?php

namespace ARK\Actions;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Stats;
use ARK\Helpers\Utils;
use ARK\Models\Player;

class Wave extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_WAVE;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Discard first card of display and replenish');
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function isIndependent(?Player $player = null): bool
  {
    return false;
  }

  public function stWave()
  {

    $this->resolveAction();
  }
}
