<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Models\ZooCard;
use ARK\Managers\Meeples;
use ARK\Models\Player;

class Mark extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_MARK;
  }

  public function getDescription(): string|array
  {
    return [
      'log' => clienttranslate('Mark ${n}'),
      'args' => [
        'n' => $this->getN(),
      ],
    ];
  }

  public function isOptional(): bool
  {
    $player = Players::getActive();
    return !$this->isDoable($player);
  }

  public function isDoable(Player $player): bool
  {
    $args = $this->getArgs();
    return !empty($args['cardIds']);
  }

  public function stMark()
  {
    $args = $this->getArgs();
    if (count($args['cardIds']) == $args['n']) {
      $this->actMark($args['cardIds'], true);
    }
  }

  public function argsMark()
  {
    $cards = ZooCards::getPool(6, CARD_ANIMAL)->filter(function ($card) {
      return $card->getTokensOnIt()->count() == 0;
    });

    return [
      'n' => min($cards->count(), $this->getN()),
      'cardIds' => $cards->getIds(),
    ];
  }

  public function actMark($cardIds, $auto = false)
  {
    $this->checkAction('actMark', $auto);
    $player = Players::getActive();
    $args = $this->getArgs();

    if (count($cardIds) > $args['n']) {
      throw new \BgaVisibleSystemException('Wrong number of cards to mark. Should not happen');
    }
    if (!empty(array_diff($cardIds,  $args['cardIds']))) {
      throw new \BgaVisibleSystemException('Invalid card to mark. Should not happen');
    }
    $meeples = [];
    foreach ($cardIds as $cId) {
      $meeples[] = Meeples::addTokenOnCard($player->getId(), $cId, 1);
    }

    Notifications::mark($player, ZooCards::getMany($cardIds), $meeples);
    $this->resolveAction([]);
  }
}
