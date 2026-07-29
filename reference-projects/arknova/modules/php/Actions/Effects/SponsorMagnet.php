<?php

namespace ARK\Actions\Effects;

use ARK\Core\Notifications;
use ARK\Core\Stats;
use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Models\Player;

class SponsorMagnet extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_SPONSOR_MAGNET;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Take all sponsor cards from display');
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function stSponsorMagnet()
  {
    $player = Players::getActive();
    $cards = ZooCards::getPool()->filter(function ($card) {
      return $card->getType() == CARD_SPONSOR;
    });

    if (!$cards->empty()) {
      foreach ($cards as $cardId => $card) {
        ZooCards::addToHand($cardId, $player);
      }

      Stats::incCardsSnapped($player, $cards->count());
      Notifications::sponsorMagnet($player, $cards);
    }
    $this->resolveAction([$cards->getIds()]);
  }
}
