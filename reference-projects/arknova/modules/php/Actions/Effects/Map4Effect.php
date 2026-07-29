<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Models\Player;

class Map4Effect extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_MAP4;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Sell 1 card for <MONEY:3>');
  }

  public function argsMap4Effect()
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

  public function actMap4($cardsToSell)
  {
    $this->checkAction('actMap4');
    $player = Players::getActive();
    if (count($cardsToSell) > 1) {
      throw new \BgaVisibleSystemException('Wrong number of cards to sell. Should not happen');
    }
    if (!empty(array_diff($cardsToSell, $player->getHand()->getIds()))) {
      throw new \BgaVisibleSystemException('Invalid card to sell. Should not happen');
    }

    ZooCards::discard($cardsToSell);
    $money = 3 * count($cardsToSell);
    $player->incMoney($money, false, null, true);
    Globals::setEffectMap4(true);
    Notifications::sunbathing($player, ZooCards::getMany($cardsToSell), $money);
    $this->resolveAction([]);
  }
}
