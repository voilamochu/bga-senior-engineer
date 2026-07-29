<?php

namespace ARK\Actions;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Core\Notifications;
use ARK\Managers\ActionCards;
use ARK\Core\Globals;
use ARK\Core\Stats;
use ARK\Helpers\Utils;
use ARK\Models\Player;

class Cleanup extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_CLEANUP;
  }
  public function isIndependent(?Player $player = null): bool
  {
    return true;
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function stCleanUp()
  {
    // Sanity check
    $player = Players::getActive();
    $aCard = ActionCards::get($this->getCtxArg('card'));
    if (is_null($aCard)) {
      throw new \BgaVisibleSystemException('No card enabled. Should not happen');
    }
    $type = $aCard->getActionType();
    $isHypnosis = $this->getCtxArg('hypnosis') ?? false;
    if ($aCard->getStatus() != 1 || ($isHypnosis === false && $aCard->getPId() != $player->getId())) {
      throw new \BgaVisibleSystemException('Card not enabled. Should not happen');
    }

    // Hypnosis : change player
    if ($isHypnosis) {
      $player = Players::get($aCard->getPId());
    }
    Globals::setEffectHypnosis(0);

    // Slide action card to position 1 and notify
    $aCard->setStatus(0);
    $actionCards = $player->moveActionCard($type, 1);
    Notifications::actionCardCleanup($player, $aCard, 1, $actionCards);

    // Clear Venom tokens
    foreach ($aCard->getMeeplesOnIt(VENOM) as $meepleId => $m) {
      $meeple = Meeples::destroy($meepleId);
      Notifications::discardToken($player, VENOM, $meeple);
    }

    // Clear constriction tokens
    foreach ($aCard->getMeeplesOnIt(CONSTRICTION) as $meepleId => $m) {
      $meeple = Meeples::destroy($meepleId);
      Notifications::discardToken($player, CONSTRICTION, $meeple);
    }

    // Make multipliers usable
    $player = Players::getActive(); // Make sure to look at the correct action cards !
    $actionCards = $player->getActionCards();
    $updatedMeeples = [];
    foreach ($actionCards as $cId => $card) {
      foreach ($card->getMeeplesOnIt(MULTIPLIER, \INACTIVE) as $mId => $meeple) {
        Meeples::DB()->update(['meeple_state' => ACTIVE], $mId);
        $updatedMeeples[] = $mId;
      }
    }
    if (count($updatedMeeples) > 0) {
      Notifications::enableMultiplier(Meeples::getMany($updatedMeeples));
    }


    Globals::setActiveActionCard([]);
    $this->resolveAction(['cleanup' => 'done']);
  }
}
