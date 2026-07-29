<?php

namespace ARK\Actions\Effects;

use ARK\Core\Notifications;
use ARK\Core\Stats;
use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Models\Player;

class SeaAnimalMagnet extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_SEA_ANIMAL_MAGNET;
  }

  public function getDescription(): string
  {
    return clienttranslate('Take all sea animal cards from display');
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function stSeaAnimalMagnet()
  {
    $player = Players::getActive();
    $cards = ZooCards::getPool()->filter(function ($card) {
      return in_array($card->getType(), [CARD_ANIMAL, CARD_SPONSOR]) && in_array(SEA_ANIMAL, $card->getCategories());
    });

    if (!$cards->empty()) {
      foreach ($cards as $cardId => $card) {
        ZooCards::addToHand($cardId, $player);
      }

      Stats::incCardsSnapped($player, $cards->count());
      Notifications::seaAnimalMagnet($player, $cards);
    }

    $this->resolveAction([$cards->getIds()]);
  }
}
