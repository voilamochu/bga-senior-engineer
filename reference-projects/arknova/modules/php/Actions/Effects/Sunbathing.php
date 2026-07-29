<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Models\Player;

class Sunbathing extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_SUNBATHING;
  }

  public function isOptional(): bool
  {
    return true;
  }

  public function getDescription(): string|array
  {
    return [
      'log' => clienttranslate('Sell up to ${n} cards for <MONEY:4> each'),
      'args' => [
        'n' => $this->getN(),
      ],
    ];
  }

  public function argsSunbathing()
  {
    $player = Players::getActive();

    return [
      'n' => $this->getN(),
      '_private' => [
        'active' => [
          'cardIds' => $player->getHand()->getIds(),
        ],
      ],
    ];
  }

  public function actSunbathing($cardsToSell)
  {
    $this->checkAction('actSunbathing');
    $player = Players::getActive();
    if (count($cardsToSell) == 0) {
      throw new \BgaUserException(clienttranslate('Please select a card or pass the action'));
    }

    if (count($cardsToSell) > $this->getN()) {
      throw new \BgaVisibleSystemException('Wrong number of cards to sell. Should not happen');
    }
    if (!empty(array_diff($cardsToSell, $player->getHand()->getIds()))) {
      throw new \BgaVisibleSystemException('Invalid card to sell. Should not happen');
    }

    ZooCards::discard($cardsToSell);
    $money = 4 * count($cardsToSell);
    $bonuses = $player->incMoney($money, false);
    Notifications::sunbathing($player, ZooCards::getMany($cardsToSell), $money);
    $this->insertBonusesFlow($bonuses);

    $this->resolveAction([]);
  }
}
