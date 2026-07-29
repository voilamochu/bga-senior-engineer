<?php

namespace ARK\Actions;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Stats;
use ARK\Helpers\Utils;
use ARK\Models\Player;

class GainMarked extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_GAIN_MARKED;
  }

  public function getDescription(): string|array
  {
    $player = $this->getPlayer();

    return [
      'log' => clienttranslate('Let ${player_name} gain <MONEY:2> (marked card)'),
      'args' => [
        'player_name' => $player->getName(),
      ],
    ];
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function isIndependent(?Player $player = null): bool
  {
    return true;
  }

  public function getPlayer(): Player
  {
    $args = $this->getCtxArgs();
    $pId = $args['token']['pId'] ?? Players::getActiveId();
    return Players::get($pId);
  }


  public function stGainMarked()
  {
    $player = $this->getPlayer();
    $args = $this->getCtxArgs();
    $source = $this->ctx->getSource() ?? null;
    $sourceId = $this->ctx->getSourceId() ?? null;
    if (is_null($source) && !is_null($sourceId)) {
      $source = ZooCards::getSingle($sourceId);
    }

    // Increase resource and notify
    $bonuses = $player->incMoney(2, false);
    Notifications::gainMarked($player, $args['token'], $source);
    $this->insertBonusesFlow($bonuses);

    $this->resolveAction();
  }
}
