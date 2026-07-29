<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Models\Player;
use ARK\Core\Engine;
use ARK\Helpers\FlowConvertor;

class Map13Income extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_MAP13_INCOME;
  }

  public function getFlow($player)
  {
    $bonus = $player->map()->getQuarterIncome($this->getCtxArg("n"));
    if (is_null($bonus)) return null;
    $bonuses = [$bonus];
    list($immediateBonuses, $afterFinishingBonuses) = FlowConvertor::getFlow($bonuses, clienttranslate('Map 13 quarter bonus'));
    return $immediateBonuses[0];
  }

  public function getFlowTree($player)
  {
    $flow = $this->getFlow($player);
    return is_null($flow) ? null : Engine::buildTree($flow);
  }

  public function getDescription(): string|array
  {
    $player = Players::getActive();
    $tree = $this->getFlowTree($player);
    return is_null($tree) ? "" : $tree->getDescription();
  }

  public function doNotDisplayIfNotDoable(): bool
  {
    return true;
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function isDoable(Player $player): bool
  {
    $tree = $this->getFlowTree($player);
    return !is_null($tree) && $tree->isDoable($player);
  }

  public function isOptional(): bool
  {
    $player = Players::getActive();
    return !$this->isDoable($player);
  }

  public function stMap13Income()
  {
    $player = Players::getActive();
    $node = $this->ctx;
    $node->replace($this->getFlowTree($player));
    $this->resolveAction([]);
  }
}
