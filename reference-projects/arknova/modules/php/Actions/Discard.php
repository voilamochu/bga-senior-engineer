<?php

namespace ARK\Actions;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Core\Notifications;
use ARK\Managers\ZooCards;
use ARK\Core\Engine;
use ARK\Core\Stats;
use ARK\Helpers\Utils;
use ARK\Models\Player;

class Discard extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_DISCARD;
  }

  public function argsDiscard()
  {
    $player = Players::getActive();
    $args = $this->getCtxArgs();
    // $toDiscard = 0;

    // if (isset($args['nDiscard'])) {
    //   $toDiscard = $args['nDiscard'];
    // }
    // if (isset($args['resetDeck'])) {
    //   $toDiscard = $player->getMaxHand() - $player->getHand()->count();
    // }

    return [
      'n' => $args['n'],
      '_private' => ['active' => ['cardIds' => $player->getHand()->getIds()]],
    ];
  }

  public function actDiscard($cardIds)
  {
    self::checkAction('actDiscard');
    $player = Players::getActive();
    $args = $this->argsDiscard();

    if (count($cardIds) != $args['n']) {
      throw new \BgaVisibleSystemException(clienttranslate('You must discard the correct number of cards'));
    }

    foreach ($cardIds as $cId) {
      if (!in_array($cId, $args['_private']['active']['cardIds'])) {
        throw new \BgaVisibleSystemException('This card cannot be discarded');
      }
    }
    ZooCards::discard($cardIds);
    Notifications::discardCards($player, ZooCards::getMany($cardIds));
    Stats::incCardsDiscarded($player, count($cardIds));

    $this->resolveAction([]);
  }
}
