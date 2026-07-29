<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Core\Notifications;
use ARK\Managers\ActionCards;
use ARK\Core\Globals;
use ARK\Core\Stats;
use ARK\Models\Player;

class VenomPay extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_VENOM_PAY;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Pay Venom fee');
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return !$this->needToPay($player) || $player->getMoney() >= 2;
  }

  public function isDoable(Player $player): bool
  {
    return !$this->needToPay($player) || $player->getMoney() >= 2 || ($player->canUseMap(4) && $player->getHand()->count() > 0);
  }

  public static function needToPay($player)
  {
    if (Globals::isUsedVenom() || Globals::isVenomPaid()) {
      return false;
    }
    foreach ($player->getActionCards() as $card) {
      if (count($card->getMeeplesOnIt(VENOM)) > 0) {
        return true;
      }
    }
    return false;
  }

  public function argsVenomPay()
  {
    $player = Players::getActive();
    return [
      'doable' => $player->getMoney() >= 2
    ];
  }

  public function stVenomPay()
  {
    $player = Players::getActive();
    if ($this->needToPay($player)) {
      if ($player->getMoney() >= 2) {
        $player->pay(2, true, clienttranslate('Venom'));
        Globals::setVenomPaid(true);
        $this->resolveAction(['pay']);
      }
    } else {
      $this->resolveAction([]);
    }
  }
}
