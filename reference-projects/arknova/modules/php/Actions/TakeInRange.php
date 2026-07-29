<?php

namespace ARK\Actions;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Core\Notifications;
use ARK\Managers\ZooCards;
use ARK\Core\Stats;
use ARK\Helpers\Utils;
use ARK\Models\Player;

class TakeInRange extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_TAKE_IN_RANGE_OR_DECK;
  }

  public function getDescription(): string|array
  {
    return $this->getN() == 1
      ? clienttranslate('Take 1 card in reputation range or draw')
      : [
        'log' => clienttranslate('Take 1 card in reputation range or draw (${n} times)'),
        'args' => [
          'n' => $this->getN(),
        ],
      ];
  }

  public function getNTaken()
  {
    return $this->getCtxArg('taken') ?? 0;
  }

  public function argsTakeInRange()
  {
    $player = Players::getActive();
    $n = $this->getN();
    $nCurrent = $this->getNTaken() + 1;
    return [
      'cardIds' => $player->getCardsInReputationRange()->getIds(),
      'count' => $n == 1 ? '' : "($nCurrent/$n)",
    ];
  }

  public function actTakeInRange($cardId)
  {
    // Sanity check
    self::checkAction('actTakeInRange');
    $args = $this->argsTakeInRange();
    if (!in_array($cardId, $args['cardIds'])) {
      throw new \BgaVisibleSystemException('This card cannot be taken. Should not happen');
    }

    // Move cards
    $player = Players::getActive();
    $zooCard = ZooCards::getSingle($cardId);
    if ($zooCard->isMarked()) {
      $this->insertAsChild($zooCard->removeMarkForMoney($player->getId()));
    }
    ZooCards::addToHand($cardId, $player);
    Notifications::takeCardInRange($player, $zooCard);
    Stats::incCardsTaken($player);

    $nTaken = $this->getNTaken() + 1;
    if ($nTaken < $this->getN()) {
      $this->duplicateAction(['taken' => $nTaken]);
    }

    $this->resolveAction(['cardId' => $cardId]);
  }

  public function actDrawCard()
  {
    // Sanity check
    self::checkAction('actDrawCard');
    $player = Players::getActive();

    // Draw cards and notify
    $this->checkCanTakeIrreversible();
    $cards = ZooCards::draw($player, 1);
    Notifications::drawCards($player, $cards);

    $nTaken = $this->getNTaken() + 1;
    if ($nTaken < $this->getN()) {
      $this->duplicateAction(['taken' => $nTaken]);
    }

    $this->resolveAction(['draw'], true);
  }
}
