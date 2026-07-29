<?php

namespace ARK\Actions\Bonuses;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Stats;
use ARK\Helpers\Utils;
use ARK\Actions\Association;
use ARK\Models\Player;

class Donate extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_DONATE;
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function argsDonate($player = null)
  {
    $player = $player ?? Players::getActive();
    list($slot, $cost) = Association::findSmallestPossibleDonation($player);

    return [
      'donation' => $player->getMoney() < $cost ? [] : [$slot, $cost]
    ];
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Make a donation');
  }

  public function isDoable(Player $player): bool
  {
    return !empty($this->argsDonate($player)['donation']);
  }

  public function isOptional(): bool
  {
    return true;
  }

  public function stDonate()
  {
    $player = Players::getActive();

    list($slot, $cost) = $this->getArgs()['donation'];
    $player->pay($cost, false);
    Stats::incMoneyUsedDonations($player, $cost);
    $bonuses = $player->incConservation(1, false);
    $meeple = Meeples::addTokenOnDonationSlot($player, $slot);
    Notifications::donation($player, $cost, $meeple);
    $this->insertBonusesFlow($bonuses, clienttranslate('conservation track bonus'), 'bonusTile');

    $this->resolveAction([]);
  }
}
