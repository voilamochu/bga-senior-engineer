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

  public function actMap11EffectStore($cardsToStore)
  {
    $this->checkAction('actMap4');
    $player = Players::getActive();
    if (count($cardsToStore) > 1) {
      throw new \BgaVisibleSystemException('Wrong number of cards to sell. Should not happen');
    }
    if (!empty(array_diff($cardsToStore, $player->getHand()->getIds()))) {
      throw new \BgaVisibleSystemException('Invalid card to sell. Should not happen');
    }

    ZooCards::discard($cardsToStore);
    $money = 3 * count($cardsToStore);
    $player->incMoney($money, false);
    Globals::setEffectMap4(true);
    Notifications::sunbathing($player, ZooCards::getMany($cardsToStore), $money);
    $this->resolveAction([]);
  }
}
