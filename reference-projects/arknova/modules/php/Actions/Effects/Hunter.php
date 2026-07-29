<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Core\Stats;
use ARK\Models\Player;

class Hunter extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_HUNTER;
  }

  public function getDescription(): string|array
  {
    return [
      'log' => clienttranslate('Hunter ${n}'),
      'args' => [
        'n' => $this->getN(),
      ],
    ];
  }

  public function getN()
  {
    $n = parent::getN();
    if ($n == PREDATOR) {
      $n = Players::getActive()->countCardIcon(PREDATOR);
    }
    return $n;
  }

  public function isIrreversible(?Player $player = null): bool
  {
    return true;
  }

  public function stPreHunter()
  {
    $player = Players::getActive();
    $cards = ZooCards::draw($player, $this->getN());
    Notifications::preHunter($player, $cards);
    Stats::incCardsDrawn($player, $this->getN());
    Globals::setEffectHunter($cards->getIds());
  }

  public function argsHunter()
  {
    $player = Players::getActive();
    $cards = ZooCards::getMany(Globals::getEffectHunter());
    $animals = $cards->filter(function ($card) {
      return $card->getType() == CARD_ANIMAL;
    });

    return [
      'n' => $this->getN(),
      '_private' => [
        'active' => [
          'cardIds' => $animals->getIds(),
        ],
      ],
    ];
  }

  public function stHunter()
  {
    $args = $this->getArgs();
    $animals = $args['_private']['active']['cardIds'];

    if (empty($animals)) {
      // No animals => discard all cards
      $this->failHunter();
    } elseif (count($animals) == 1) {
      // Only one animal => automatically discards the other ones
      $this->actHunter($animals[0], true);
    }
  }

  public function failHunter()
  {
    $player = Players::getActive();
    $cardIdsToDiscard = Globals::getEffectHunter();
    $cardsToDiscard = ZooCards::getMany($cardIdsToDiscard);
    ZooCards::discard($cardIdsToDiscard);
    Globals::setEffectHunter([]);
    Notifications::failHunter($player, $cardsToDiscard);
    $this->resolveAction([], true);
  }

  public function actHunter($cardId, $isAuto = false)
  {
    $this->checkAction('actHunter', $isAuto);

    $player = Players::getActive();
    if (!in_array($cardId, Globals::getEffectHunter())) {
      throw new \BgaVisibleSystemException('Invalid card to keep. Should not happen');
    }
    $card = ZooCards::get($cardId);
    if ($card->getType() != CARD_ANIMAL) {
      throw new \BgaVisibleSystemException('Invalid card to keep, not an animal. Should not happen');
    }

    $cardIdsToDiscard = array_diff(Globals::getEffectHunter(), [$cardId]);
    $cardsToDiscard = ZooCards::getMany($cardIdsToDiscard);
    Stats::incCardsDrawn($player, count($cardIdsToDiscard));
    ZooCards::discard($cardIdsToDiscard);
    Globals::setEffectHunter([]);
    Notifications::hunter($player, $cardsToDiscard, $card);
    $this->resolveAction([], $isAuto);
  }
}
