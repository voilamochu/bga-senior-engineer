<?php

namespace ARK\Actions;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Stats;
use ARK\Helpers\Utils;
use ARK\Models\Player;

class MoneyIncome extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_MONEY_INCOME;
  }

  public function getDescription(): string|array
  {
    return $this->getCtxArg('type') == 'all'
      ? clienttranslate('Take all <MONEY> income')
      : [
        'log' => clienttranslate('Gain ${resources_desc}'),
        'args' => [
          'resources_desc' => Utils::resourcesToStr([MONEY => $this->getGain()], true),
        ],
      ];
  }

  public function isIndependent(?Player $player = null): bool
  {
    return $this->checkAllSiblingsAreGainMoney();
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function getGain()
  {
    $args = $this->getCtxArgs();
    $player = Players::get($this->ctx->getPId());
    $money = 0;
    if ($args['type'] == 'appeal') {
      $money += $player->getIncomeFromAppeal();
    } elseif ($args['type'] == 'kiosk') {
      $money += $player->map()->getKioskIncome();
    } elseif ($args['type'] == 'map') {
      $money += $player->map()->getIncome()[0][MONEY] ?? 0;
    }

    return $money;
  }

  public function stMoneyIncome()
  {
    $player = Players::get($this->ctx->getPId());
    $args = $this->getCtxArgs();
    $source = $this->ctx->getSource() ?? null;
    $sourceId = $this->ctx->getSourceId() ?? null;
    if (is_null($source) && !is_null($sourceId)) {
      $source = ZooCards::getSingle($sourceId)->getName();
    }

    // Increase resource and notify
    $money = $this->getGain();
    $player->incMoney($money, false);
    Stats::incMoneyGainedIncome($player, $money);
    unset($args['pId']);

    // Get the new amount and update the real bonus
    $args[MONEY] = $money;
    unset($args['type']);

    // Notify
    Notifications::gain($player, $args, $source);

    $this->checkAfterListeners($player, ['gain' => $args]);
    $this->resolveAction();
  }
}
