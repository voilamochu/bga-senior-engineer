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

class Cards extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_CARDS;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Draw cards');
  }

  public function getParameters()
  {
    // if it's a bonus, no matter the action we can only draw/take a card
    if (($this->getCtxArg('bonus') ?? false) === true) {
      return [1, 0, 0, 0, 0];
    }

    $data = $this->getCtxArg('parameters') ?? [
      1 => [[], [1, 1, 0], [1, 0, 0], [2, 1, 0], [2, 0, 0], [3, 1, 1]],
      2 => [[], [1, 0, 0], [2, 1, 0], [2, 0, 1], [3, 1, 1], [4, 1, 1]],
    ]; // FALLBACK LEGACY CODE : TODO REMOVE IN A WHILE
    $strength = min($this->getStrength(), 5); // Useless to have strength > 5
    $params = $data[$this->getLevel()][$strength];

    // Number of already taken cards
    $nTaken = $this->getCtxArg('taken') ?? 0;
    $params[] = $nTaken;
    // Can no longer snap if already took a card
    if ($nTaken > 0) {
      $params[2] = 0;
    }

    // Number of already snapped cards
    $nSnapped = $this->getCtxArg('snapped') ?? 0;
    $params[] = $nSnapped;
    if ($nSnapped > 0) {
      $params[0] = 0;
      $params[1] = 0;
      $params[2] -= $nSnapped;
    }

    return $params;
  }

  public function isUpgraded()
  {
    if (($this->getCtxArg('bonus') ?? false) === true) {
      return true;
    }
    return parent::isUpgraded();
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return false;
  }

  public function isOptional(): bool
  {
    return ($this->getCtxArg('snapped') ?? 0) > 0;
  }

  public function argsCards()
  {
    $player = Players::getActive();
    list($nDraw, $nDiscard, $nSnap, $nTaken, $nSnapped) = $this->getParameters();

    return [
      'n' => $nDraw - $nTaken,
      // Cards from display
      'cardIds' => $this->isUpgraded() ? $player->getCardsInReputationRange()->getIds() : [],
      'snap' => $nSnap > 0 ? ZooCards::getPool()->getIds() : [],
      'nSnap' => $nSnap,
      'nDraw' => $nDraw,

      // Title
      'i18n' => ['source', 'discard'],
      'discard' =>
      $nDiscard == 0
        ? ''
        : [
          'log' => clienttranslate('(and discard ${m})'),
          'args' => ['m' => $nDiscard],
        ],
      'source' => $nSnapped ? '' : ($this->isUpgraded() ? clienttranslate('deck or within reputation range') : clienttranslate('deck')),
      'descSuffix' => $nSnap > 0 ? ($nSnapped ? 'snaponly' : 'snap') : '',
      'strength' => $this->getStrength(),
      'strength_icon' => '',
    ];
  }

  public function stCards()
  {
    list($nDraw,,, $nTaken,) = $this->getParameters();
    if ($this->isAutomatic()) {
      $this->actDrawCards($nDraw - $nTaken, true);
    }
  }

  public function actDrawCards($nInDeck, $auto = false)
  {
    // Sanity check
    self::checkAction('actDrawCards', $auto);
    list($nDraw, $nDiscard, $canSnap, $nTaken, $nSnapped) = $this->getParameters();
    $player = Players::getActive();
    if ($nTaken + $nInDeck > $nDraw) {
      throw new \BgaVisibleSystemException('Too many cards taken. Should not happen');
    }

    // Draw cards and notify
    $cards = ZooCards::draw($player, $nInDeck);
    Stats::incCardsDrawn($player, $nInDeck);
    Notifications::drawCards($player, $cards);
    $this->loopOrResolve($nInDeck, true);
  }

  public function actTakeCard($cardId)
  {
    // Sanity check
    self::checkAction('actTakeCard');
    // Can only take card from display if upgraded
    if (!$this->isUpgraded()) {
      throw new \BgaVisibleSystemException('Cannot take cards with a level 1 card');
    }
    $args = $this->argsCards();
    if (!in_array($cardId, $args['cardIds'])) {
      throw new \BgaVisibleSystemException('This card cannot be taken. Should not happen');
    }

    // move cards
    $player = Players::getActive();
    $zooCard = ZooCards::getSingle($cardId);
    if ($zooCard->isMarked()) {
      $this->insertAsChild($zooCard->removeMarkForMoney($player->getId()));
    }
    $zooCard = ZooCards::addToHand($cardId, $player);
    Notifications::takeCardInRange($player, $zooCard);
    Stats::incCardsTaken($player);
    $this->loopOrResolve(1);
  }

  protected function loopOrResolve($takens, $checkpoint = false)
  {
    list($nDraw, $nDiscard, $nSnap, $nTaken, $nSnapped) = $this->getParameters();

    // Are we done taking cards ?
    $nTaken += $takens;
    if ($nTaken == $nDraw) {
      // Do we need to discard some cards ?
      if ($nDiscard != 0) {
        $this->insertAsChild(['action' => DISCARD, 'args' => ['n' => $nDiscard]]);
      }
      $this->resolveAction(['take'], $checkpoint);
    } else {
      // Loop on same node with updated args
      $this->duplicateAction(['taken' => $nTaken]);
      $this->resolveAction(['take'], $checkpoint);
    }
  }

  public function actSnapCard($cardId)
  {
    self::checkAction('actSnapCard');
    list(,, $nSnap,, $nSnapped) = $this->getParameters();
    if ($nSnap == 0) {
      throw new \BgaVisibleSystemException('You cannot snap a card. Should not happen');
    }
    $args = $this->argsCards();
    if (!in_array($cardId, $args['snap'])) {
      throw new \BgaVisibleSystemException('This card cannot be snapped. Should not happen');
    }

    $player = Players::getActive();
    $zooCard = ZooCards::getSingle($cardId);
    if ($zooCard->isMarked()) {
      $this->insertAsChild($zooCard->removeMarkForMoney($player->getId()));
    }
    ZooCards::addToHand($cardId, $player);
    Notifications::snapCard($player, $zooCard);
    Stats::incCardsSnapped($player);

    // CARDS3
    $nSnapped += 1;
    if ($nSnapped < $nSnap) {
      $this->duplicateAction(['snapped' => $nSnapped]);
    }

    $this->resolveAction(['snap']);
  }
}
