<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Models\Player;

class Map11EffectUnstore extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_MAP11_UNSTORE;
  }

  public function getDescription(): string
  {
    return clienttranslate('Unstore a card');
  }

  public function isDoable(Player $player): bool
  {
    return $player->getStoredCards()->count() > 0;
  }

  public function argsMap11EffectUnstore()
  {
    $player = Players::getActive();

    return [
      'n' => 1,
      '_private' => [
        'active' => [
          'cardIds' => $player->getStoredCards()->getIds(),
        ],
      ],
    ];
  }

  public function actMap11EffectUnstore($cardToStore)
  {
    $this->checkAction('actMap11EffectUnstore');
    $args = $this->getArgs();
    if (!in_array($cardToStore, $args['_private']['active']['cardIds'])) {
      throw new \BgaVisibleSystemException('Invalid card to sell. Should not happen');
    }

    // Move the card
    $player = Players::getActive();
    ZooCards::move($cardToStore, 'hand');
    Notifications::unstoreCard($player, ZooCards::getSingle($cardToStore));

    $this->resolveAction([]);
  }
}
