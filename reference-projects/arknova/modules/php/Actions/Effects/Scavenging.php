<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Models\Player;

class Scavenging extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_SCAVENGING;
  }

  public function getDescription(): string|array
  {
    return [
      'log' => clienttranslate('Draw ${n} from shuffled discard, keep 1'),
      'args' => [
        'n' => $this->getN(),
      ],
    ];
  }

  public function isIrreversible(?Player $player = null): bool
  {
    return true;
  }

  public function stPreScavenging()
  {
    $player = Players::getActive();
    ZooCards::shuffle('discard');
    $cards = ZooCards::draw($player, $this->getN(), 'discard');
    Notifications::preScavenging($player, $cards);
    Globals::setEffectScavenging($cards->getIds());
  }

  public function argsScavenging()
  {
    $player = Players::getActive();
    $cards = ZooCards::getMany(Globals::getEffectScavenging());

    return [
      'n' => $this->getN(),
      '_private' => [
        'active' => [
          'cardIds' => $cards->getIds(),
        ],
      ],
    ];
  }

  public function actScavenging($cardId)
  {
    $this->checkAction('actScavenging');
    if (!in_array($cardId, Globals::getEffectScavenging())) {
      throw new \BgaVisibleSystemException('Invalid card to keep. Should not happen');
    }

    $player = Players::getActive();
    $card = ZooCards::get($cardId);
    $cardIdsToDiscard = array_diff(Globals::getEffectScavenging(), [$cardId]);
    $cardsToDiscard = ZooCards::getMany($cardIdsToDiscard);
    ZooCards::discard($cardIdsToDiscard);
    Globals::setEffectScavenging([]);
    Notifications::scavenging($player, $cardsToDiscard, $card);

    $this->resolveAction([]);
  }
}
