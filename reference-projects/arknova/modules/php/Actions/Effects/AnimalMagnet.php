<?php

namespace ARK\Actions\Effects;

use ARK\Core\Notifications;
use ARK\Core\Stats;
use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Models\Player;

class AnimalMagnet extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_ANIMAL_MAGNET;
  }

  public function getDescription(): string
  {
    return clienttranslate('Take all animal cards from display');
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function stAnimalMagnet()
  {
    $player = Players::getActive();
    $cards = ZooCards::getPool()->filter(fn($card) => $card->getType() == CARD_ANIMAL);

    if (!$cards->empty()) {
      foreach ($cards as $cardId => $card) {
        ZooCards::addToHand($cardId, $player);
      }

      Stats::incCardsSnapped($player, $cards->count());
      Notifications::animalMagnet($player, $cards);
    }

    $this->resolveAction([$cards->getIds()]);
  }
}
