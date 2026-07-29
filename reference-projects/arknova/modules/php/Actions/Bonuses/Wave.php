<?php

namespace ARK\Actions\Bonuses;

use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Managers\Players;
use ARK\Models\Player;

class Wave extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_WAVE;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Discard first card of display and replenish');
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function isIndependent(?Player $player = null): bool
  {
    return false;
  }

  public function isIrreversible(?Player $player = null): bool
  {
    return true;
  }

  public function stWave()
  {
    $this->checkCanTakeIrreversible();
    $player = Players::getActive();

    // Discard first card on the display
    $card = null;
    for ($i = 1; is_null($card) && $i <= 6; $i++) {
      $card = ZooCards::getInLocation(['pool', $i])->first();
    }

    if (!is_null($card)) {
      list($discarded, $assigned, $meeples) = ZooCards::discard([$card->getId()]);
      Notifications::discardPoolCardsWaveBonus($player, $discarded);
      if (!$assigned->empty()) {
        Notifications::markAssign($assigned, $meeples);
      }
    }

    // Fill pool
    ZooCards::fillPool();

    $this->resolveAction([], true);
  }
}
