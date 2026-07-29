<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Models\Player;

class Map11EffectStore extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_MAP11_STORE;
  }

  public function getDescription(): string
  {
    return clienttranslate('Store a card');
  }

  public function isDoable(Player $player): bool
  {
    return $player->getHand()->count() > 0 && $player->getStoredCards()->count() < 3;
  }

  public function isOptional(): bool
  {
    return true;
  }

  public function argsMap11EffectStore()
  {
    $player = Players::getActive();

    return [
      'n' => 1,
      '_private' => [
        'active' => [
          'cardIds' => $player->getHand()->getIds(),
        ],
      ],
    ];
  }

  public function actMap11EffectStore($cardToStore)
  {
    $this->checkAction('actMap11EffectStore');
    $args = $this->getArgs();
    if (!in_array($cardToStore, $args['_private']['active']['cardIds'])) {
      throw new \BgaVisibleSystemException('Invalid card to sell. Should not happen');
    }

    // Move the card
    $player = Players::getActive();
    ZooCards::move($cardToStore, 'stored');
    Notifications::storeCard($player, ZooCards::getSingle($cardToStore));

    $this->resolveAction([]);
  }
}
