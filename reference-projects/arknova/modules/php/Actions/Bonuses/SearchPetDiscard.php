<?php

namespace ARK\Actions\Bonuses;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Stats;
use ARK\Models\Player;

class SearchPetDiscard extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_SEARCH_PET_DISCARD;
  }

  public function isIrreversible(?Player $player = null): bool
  {
    return true;
  }

  public function stPreSearchPetDiscard()
  {
    // INSERT A CHECKPOINT
  }

  public function argsSearchPetDiscard()
  {
    $discard = ZooCards::getInLocation('discard');
    $pets = $discard->filter(fn($c) => $c->getType() == CARD_ANIMAL && in_array(PET, $c->getCategories()));

    return [
      '_private' => [
        'active' => [
          'cardIds' => $discard->getIds(),
          'petIds' => $pets->getIds(),
          'optional' => $pets->empty(),
        ]
      ]
    ];
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Search discard pile');
  }

  public function actSearchPetDiscard(string $cardId)
  {
    self::checkAction('actSearchPetDiscard');

    $player = Players::getActive();
    $args = $this->getArgs()['_private']['active'];
    if (!in_array($cardId, $args['petIds'])) {
      throw new \BgaVisibleSystemException('Invalid pet animal. Should not happen');
    }

    $card = ZooCards::addToHand($cardId, $player);
    Notifications::searchPetDiscard($player, $card);

    $this->resolveAction([]);
  }

  public function actPassSearchPetDiscard()
  {
    self::checkAction('actPassSearchPetDiscard');

    $player = Players::getActive();
    $args = $this->getArgs()['_private']['active'];
    if (!$args['optional']) {
      throw new \BgaVisibleSystemException('You cant pass. Should not happen');
    }

    Notifications::message(clienttranslate('${player_name} can\'t take any Petting Zoo Animal in discard because the discard does not contain any'), ['player' => $player]);
    $this->resolveAction([]);
  }
}
