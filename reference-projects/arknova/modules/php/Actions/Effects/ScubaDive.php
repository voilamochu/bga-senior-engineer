<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Core\Engine;
use ARK\Helpers\Utils;
use ARK\Helpers\FlowConvertor;
use ARK\Actions\Build;
use ARK\Models\Player;
use ARK\Core\Stats;

class ScubaDive extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_SCUBA_DIVE;
  }

  public function getDescription(): array
  {
    return [
      'log' => clienttranslate('Scuba Dive ${n}'),
      'args' => [
        'n' => $this->getN(),
      ]
    ];
  }

  public function getN()
  {
    $n = parent::getN();
    if (is_null($n)) {
      $player = Players::getActive();
      $icons = $player->countCardIcons();
      $n = $icons[SEA_ANIMAL] + $icons[REPTILE];
    }
    return $n;
  }

  public function isIrreversible(?Player $player = null): bool
  {
    return true;
  }

  public function stPreScubaDive()
  {
    $player = Players::getActive();
    $n = $this->getN();
    $cards = ZooCards::draw($player, $n);
    Notifications::preScubaDive($player, $cards);
    Globals::setEffectScubaDive($cards->getIds());
  }

  public function argsScubaDive()
  {
    $cards = ZooCards::getMany(Globals::getEffectScubaDive());
    $sponsors = $cards->filter(function ($card) {
      return $card->getType() == CARD_SPONSOR;
    });

    return [
      'n' => $this->getN(),
      '_private' => [
        'active' => [
          'cardIds' => $sponsors->getIds(),
        ],
      ],
    ];
  }

  public function stScubaDive()
  {
    $args = $this->getArgs();
    $sponsors = $args['_private']['active']['cardIds'];

    if (empty($sponsors)) {
      // No animals => discard all cards
      $this->failScubaDive();
    } elseif (count($sponsors) == 1) {
      // Only one animal => automatically discards the other ones
      $this->actScubaDive($sponsors[0], true);
    }
  }

  public function failScubaDive()
  {
    $player = Players::getActive();
    $cardIdsToDiscard = Globals::getEffectScubaDive();
    $cardsToDiscard = ZooCards::getMany($cardIdsToDiscard);
    ZooCards::discard($cardIdsToDiscard);
    Globals::setEffectScubaDive([]);
    Notifications::failScubaDive($player, $cardsToDiscard);
    $this->resolveAction([], true);
  }

  public function actScubaDive($cardId, $isAuto = false)
  {
    $this->checkAction('actScubaDive', $isAuto);

    $player = Players::getActive();
    if (!in_array($cardId, Globals::getEffectScubaDive())) {
      throw new \BgaVisibleSystemException('Invalid card to keep. Should not happen');
    }
    $card = ZooCards::get($cardId);
    if ($card->getType() != CARD_SPONSOR) {
      throw new \BgaVisibleSystemException('Invalid card to keep, not a sponsor. Should not happen');
    }

    $cardIdsToDiscard = array_diff(Globals::getEffectScubaDive(), [$cardId]);
    $cardsToDiscard = ZooCards::getMany($cardIdsToDiscard);
    Stats::incCardsDrawn($player, count($cardIdsToDiscard));
    ZooCards::discard($cardIdsToDiscard);
    Globals::setEffectScubaDive([]);
    Notifications::scubaDive($player, $cardsToDiscard, $card);
    $this->resolveAction([], $isAuto);
  }
}
