<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Models\Player;

class Resistance extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_RESISTANCE;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Draw 2 final scoring cards and keep 1');
  }

  public function isIrreversible(?Player $player = null): bool
  {
    return true;
  }

  public function stPreResistance()
  {
    $player = Players::getActive();
    $cards = ZooCards::draw($player, 2, 'scoringDeck', 'scoringHand');
    Notifications::preResistance($player, $cards);
    Globals::setEffectResistance($cards->getIds());
  }

  public function argsResistance()
  {
    $cards = ZooCards::getMany(Globals::getEffectResistance());

    return [
      '_private' => [
        'active' => [
          'cardIds' => $cards->getIds(),
        ],
      ],
    ];
  }

  public function actResistance($cardId)
  {
    $this->checkAction('actResistance');

    $player = Players::getActive();
    if (!in_array($cardId, Globals::getEffectResistance())) {
      throw new \BgaVisibleSystemException('Invalid card to keep. Should not happen');
    }

    $card = ZooCards::getSingle($cardId);
    $cardIdsToDiscard = array_diff(Globals::getEffectResistance(), [$cardId]);
    $cardsToDiscard = ZooCards::getMany($cardIdsToDiscard);
    foreach ($cardIdsToDiscard as $cId) {
      ZooCards::insertAtBottom($cId, 'scoringDeck');
    }
    Globals::setEffectResistance([]);
    Notifications::resistance($player, $cardsToDiscard, $card);
    $this->resolveAction([]);
  }
}
