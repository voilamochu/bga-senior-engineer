<?php

namespace ARK\Actions;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Stats;
use ARK\Helpers\Utils;
use ARK\Models\Player;

class RemoveMark extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_REMOVE_MARK;
  }

  public function getDescription(): string|array
  {
    return '';
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function isIndependent(?Player $player = null): bool
  {
    return true;
  }

  public function stRemoveMark()
  {
    $args = $this->getCtxArgs();
    Notifications::removeMark($args['token']);
    $this->resolveAction();
  }
}
