<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Models\ZooCard;
use ARK\Models\Player;

class Digging extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_DIGGING;
  }

  public function getDescription(): string|array
  {
    return [
      'log' => clienttranslate('Digging ${n}'),
      'args' => [
        'n' => $this->getN(),
      ],
    ];
  }

  public function isOptional(): bool
  {
    return true;
  }

  public function argsDigging()
  {
    $player = Players::getActive();

    return [
      'n' => $this->getN(),
      '_private' => [
        'active' => [
          'cardIds' => $player
            ->getHand()
            ->merge(ZooCards::getPool())
            ->getIds(),
        ],
      ],
    ];
  }

  public function actDigging($cardToDiscard)
  {
    // Sanity checks
    $this->checkAction('actDigging');
    if (count($cardToDiscard) > 1) {
      throw new \BgaVisibleSystemException('Too many cards selected. Should not happen');
    }
    if (count($cardToDiscard) == 0) {
      throw new \BgaUserException(clienttranslate('You must select one card'));
    }
    if (!empty(array_diff($cardToDiscard, $this->argsDigging()['_private']['active']['cardIds']))) {
      throw new \BgaVisibleSystemException('Invalid card to discard. Should not happen');
    }
    $this->checkCanTakeIrreversible();

    $player = Players::getActive();
    $oCard = ZooCards::get($cardToDiscard);

    // From hand
    if ($oCard->getLocation() == 'hand') {
      ZooCards::discard($cardToDiscard);
      $newCards = ZooCards::draw($player, 1);
      Notifications::digging($player, 'hand', ZooCards::getMany($cardToDiscard), $newCards);
    }
    // From display
    else {
      list($discarded, $assigned, $meeples) = ZooCards::discard($cardToDiscard);
      Notifications::digging($player, 'display', $discarded, null);
      Notifications::markAssign($assigned, $meeples);
      ZooCards::fillPool();
    }

    if ($this->getN() > 1) {
      $this->duplicateAction(['n' => $this->getN() - 1], true);
    }

    $this->resolveAction([], true);
  }
}
