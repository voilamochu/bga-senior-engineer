<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Core\Stats;
use ARK\Models\Player;

class Trade extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_TRADE;
  }

  public function getDescription(): array
  {
    return [
      'log' => clienttranslate('Trade ${n}'),
      'args' => [
        'n' => $this->getN(),
      ],
    ];
  }

  public function isOptional(): bool
  {
    return true;
  }

  public function isDoable(Player $player): bool
  {
    return  $player->countXTokens() >= 1 || $player->getMoney() >= 5;
  }

  public function argsTrade(): array
  {
    $player = Players::getActive();

    $canGainReputation = $this->getCtxArg(REPUTATION) ?? false; // SPONSORS1
    if ($player->getReputation() == 9 && !$player->isCardUpgraded(CARDS)) {
      $canGainReputation = false;
    }

    return [
      'n' => $this->getN(),
      'tradable' => [
        XTOKEN => $player->countXTokens(),
        MONEY => intdiv($player->getMoney(), 5),
      ],
      'canGainReputation' => $canGainReputation,
    ];
  }

  public function actTrade($tradeType, $gainType, $i)
  {
    $this->checkAction('actTrade');

    $player = Players::getActive();
    $args = $this->argsTrade();
    if (!in_array($tradeType, [XTOKEN, MONEY])) {
      throw new \BgaVisibleSystemException('Invalid type of trade. Should not happen');
    }
    if (!in_array($gainType, [XTOKEN, MONEY, REPUTATION])) {
      throw new \BgaVisibleSystemException('Invalid type of trade. Should not happen');
    }
    if ($gainType == REPUTATION && !$args['canGainReputation']) {
      throw new \BgaVisibleSystemException('Invalid type of trade. Should not happen');
    }

    $max = $args['tradable'][$tradeType];
    if ($i > $max) {
      throw new \BgaVisibleSystemException('Invalid trade. Should not happen');
    }

    $money = 5 * $i;
    $xtoken = $i;
    $reputation = $i;
    $bonuses = [];

    if ($tradeType == MONEY) {
      $player->incMoney(-$money, false);
      if ($gainType == REPUTATION) {
        $bonuses = $player->incReputation($xtoken, false);
        $bonus = [MONEY => -$money, REPUTATION => $reputation];
        $msg = clienttranslate('${player_name} trades <MONEY:${money}> for <REPUTATION:${reputation}> (Trade effect)');
      } else {
        $player->incXToken($xtoken, false);
        $bonus = [MONEY => -$money, XTOKEN => $xtoken];
        $msg = clienttranslate('${player_name} trades <MONEY:${money}> for ${xtoken}<XTOKEN> (Trade effect)');
      }
    } else {
      $player->incXToken(-$xtoken, false);
      if ($gainType == REPUTATION) {
        $bonuses = $player->incReputation($xtoken, false);
        $bonus = [XTOKEN => -$xtoken, REPUTATION => $reputation];
        $msg = clienttranslate('${player_name} trades ${xtoken}<XTOKEN> for <REPUTATION:${reputation}> (Trade effect)');
      } else {
        $bonuses = $player->incMoney($money, false);
        $bonus = [MONEY => $money, XTOKEN => -$xtoken];
        $msg = clienttranslate('${player_name} trades ${xtoken}<XTOKEN> for <MONEY:${money}> (Trade effect)');
      }
    }

    $this->insertBonusesFlow($bonuses);
    Notifications::trade($player, $bonus, $msg, $xtoken, $money, $reputation);

    $this->resolveAction([]);
  }
}
