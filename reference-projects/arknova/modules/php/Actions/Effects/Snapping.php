<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Core\Notifications;
use ARK\Managers\ZooCards;
use ARK\Core\Engine;
use ARK\Core\Stats;
use ARK\Helpers\Utils;
use ARK\Models\Player;

class Snapping extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_SNAPPING;
  }

  public function isOptional(): bool
  {
    $args = $this->argsSnapping();
    return empty($args['cardIds']);
  }

  public function getDescription(): string|array
  {
    $constraint = $this->getCtxArg('constraint') ?? '';
    if ($constraint == CARD_SPONSOR) {
      return clienttranslate('Snap 1 Sponsor Card');
    }

    return [
      'log' => clienttranslate('Snap up to ${n} cards'),
      'args' => [
        'n' => $this->getN(),
      ],
    ];
  }

  public function argsSnapping()
  {
    $player = Players::getActive();
    $cards = ZooCards::getPool();
    $isWazaSmall = $this->getCtxArg('wazaSmall') ?? false;
    if ($isWazaSmall) {
      $cards = $cards->filter(fn($animal) => $animal->getType() == \CARD_ANIMAL && $animal->isSmall());
    }

    $constraint = $this->getCtxArg('constraint') ?? '';
    if ($constraint == CARD_SPONSOR) {
      $cards = $cards->filter(fn($card) => $card->getType() == CARD_SPONSOR);
    }

    return [
      'n' => $this->getN(),
      'cardIds' => $cards->getIds(),
      'canRefill' => $this->getCtxArg('canRefill') ?? false,
      'descSuffix' => $isWazaSmall ? 'wazaSmall' : '',
    ];
  }

  public function actSnapCard($cardId)
  {
    // Sanity checks
    self::checkAction('actSnapCard');
    $args = $this->argsSnapping();
    if (!in_array($cardId, $args['cardIds'])) {
      throw new \BgaVisibleSystemException('This card cannot be snapped. Should not happen');
    }

    // Move the card to player's hand
    $player = Players::getActive();
    $zooCard = ZooCards::getSingle($cardId);
    if ($zooCard->isMarked()) {
      $this->insertAsChild($zooCard->removeMarkForMoney($player->getId()));
    }
    ZooCards::addToHand($cardId, $player);
    Notifications::snapCard($player, $zooCard);
    Stats::incCardsSnapped($player);

    // Keep going or finish action
    if ($this->getN() > 1) {
      $this->duplicateAction(['n' => $this->getN() - 1, 'canRefill' => true]);
    }
    $this->resolveAction([]);
  }

  public function actReplenish()
  {
    // Sanity checks
    self::checkAction('actReplenish');
    $args = $this->argsSnapping();
    if (!$args['canRefill']) {
      throw new \BgaVisibleSystemException('Cannot replenish. Should not happen');
    }
    $this->checkCanTakeIrreversible();

    // Refill pool of cards
    ZooCards::fillPool();
    $this->duplicateAction(['n' => $this->getN(), 'canRefill' => false], true);
    $this->resolveAction(['refill'], true);
  }
}
