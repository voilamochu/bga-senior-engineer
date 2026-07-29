<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Core\Stats;
use ARK\Models\Player;

class Perception extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_PERCEPTION;
  }

  public function getParams()
  {
    $args = $this->getCtxArgs();
    return [$args['n'], $args['m'] ?? intdiv($args['n'], 2)];
  }

  public function getDescription(): string|array
  {
    list($draw, $keep) = $this->getParams();
    return [
      'log' => clienttranslate('Perception ${n}'),
      'args' => [
        'n' => $draw,
      ],
    ];
  }

  public function isIrreversible(?Player $player = null): bool
  {
    return true;
  }

  public function stPrePerception()
  {
    list($draw, $keep) = $this->getParams();
    $player = Players::getActive();
    $cards = ZooCards::draw($player, $draw);
    Stats::incCardsDrawn($player, $draw);
    Globals::setEffectPerception($cards->getIds());
    Notifications::prePerception($player, $cards, $draw, $keep);
  }

  public function argsPerception()
  {
    $player = Players::getActive();
    list($draw, $keep) = $this->getParams();

    return [
      'n' => $draw,
      'm' => $keep,
      '_private' => [
        'active' => [
          'cardIds' => Globals::getEffectPerception(),
        ],
      ],
    ];
  }

  public function actPerception($cardIdsToDiscard)
  {
    $this->checkAction('actPerception');
    $player = Players::getActive();
    list($draw, $keep) = $this->getParams();
    if (count($cardIdsToDiscard) != $draw - $keep) {
      throw new \BgaVisibleSystemException('Wrong number of cards to discard. Should not happen');
    }
    if (!empty(array_diff($cardIdsToDiscard, Globals::getEffectPerception()))) {
      throw new \BgaVisibleSystemException('Invalid card to discard. Should not happen');
    }

    $cardsToDiscard = ZooCards::getMany($cardIdsToDiscard);
    ZooCards::discard($cardIdsToDiscard);
    Stats::incCardsDrawn($player, count($cardIdsToDiscard));
    $cardIdsToKeep = array_diff(Globals::getEffectPerception(), $cardIdsToDiscard);
    $cardsToKeep = ZooCards::getMany($cardIdsToKeep);
    Globals::setEffectPerception([]);
    Notifications::perception($player, $cardsToDiscard, $cardsToKeep);
    $this->resolveAction([]);
  }
}
