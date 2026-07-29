<?php

namespace ARK\Actions;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Core\Notifications;
use ARK\Managers\ActionCards;
use ARK\Core\Engine;
use ARK\Core\Stats;
use ARK\Core\Globals;
use ARK\Helpers\Utils;
use ARK\Models\Player;

class AdvanceBreak extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_ADVANCE_BREAK;
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function getDescription(): string|array
  {
    return [
      'log' => clienttranslate('Break <BREAK:${n}>'),
      'args' => [
        'n' => $this->getN(),
      ],
    ];
  }

  public function stAdvanceBreak()
  {
    if (Globals::isSolo()) {
      $this->resolveAction([]);
      return;
    }

    // Increase the break
    $n = $this->getN();
    $break = Globals::getBreak();
    $maxBreak = Globals::getMaxBreak();

    // Make sure to not give XTOKEN bonus if already at full break
    if ($break < $maxBreak) {
      $break = min($n + $break, $maxBreak);
      Globals::setBreak($break);
      if ($break == $maxBreak) {
        Globals::setMustBreak(true);
        $player = Players::getActive();
        Stats::incBreaksTriggered($player);

        // player gains 1 xToken
        $this->insertBonusesFlow([[XTOKEN => 1]], clienttranslate('triggering break'));
      }
    }
    Notifications::advanceBreak(Players::getActive(), $n, $break, $maxBreak);

    $this->resolveAction([]);
  }
}
