<?php

namespace ARK\Actions;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Helpers\Utils;
use ARK\Helpers\Collection;
use ARK\Models\Player;

class SearchCard extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_SEARCH_CARD;
  }

  public function isIrreversible(?Player $player = null): bool
  {
    return true;
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function getDescription(): string|array
  {
    switch ($this->getN()) {
      case PREDATOR:
        return clienttranslate('Take 1 Predator card from deck');
      case BIRD:
        return clienttranslate('Take 1 Bird card from deck');
      case SEA_ANIMAL:
        return clienttranslate('Take 1 Sea Animal card from deck');
      case PRIMATE:
        return clienttranslate('Take 1 Primate card from deck');
      case REPTILE:
        return clienttranslate('Take 1 Reptile card from deck');
      case HERBIVORE:
        return clienttranslate('Take 1 Herbivore card from deck');
      case CARD_SPONSOR:
        return clienttranslate('Take 1 Sponsor card from the deck');
      case SPONSOR_PERSON:
        return clienttranslate('Take 1 person Sponsor card from deck');
    }
    return "";
  }

  public function stSearchCard()
  {
    $this->checkCanTakeIrreversible();

    $player = Players::getActive();
    $n = $this->getN();

    // Source ?
    $source = $this->ctx->getSource() ?? null;
    $sourceId = $this->ctx->getSourceId() ?? null;
    if (is_null($source) && !is_null($sourceId)) {
      $source = ZooCards::getSingle($sourceId);
    }

    self::stSearchCardAux($player, $n, $source);
    $this->resolveAction([], true);
  }

  public static function stSearchCardAux($player, $icon, $source)
  {
    $card = ZooCards::searchCard($player, $icon);
    Notifications::searchCard($player, $card, $icon, $source);
  }
}
