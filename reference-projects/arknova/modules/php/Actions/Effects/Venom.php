<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Managers\Meeples;
use ARK\Models\Player;

class Venom extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_VENOM;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Give venom token(s)');
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function stVenom()
  {
    $player = Players::getActive();
    $appeal = $player->getAppeal();

    $players = [];
    $meeples = [];
    foreach (Players::getAll() as $pId => $otherPlayer) {
      if (
        $otherPlayer->getId() == $player->getId() ||
        $otherPlayer->getAppeal() < 5 || // not impacted if less than 5 appeal
        $otherPlayer->getAppeal() <= $appeal // must have more appeal
      ) {
        continue;
      }

      if ($otherPlayer->hasPlayedCard('S225_QuarantineLab')) {
        Notifications::message(
          clienttranslate('${player_name} is not affected by Venom thanks to their Quarantine Lab sponsor card'),
          ['player' => $otherPlayer]
        );
        continue;
      }

      $players[] = $otherPlayer;
      for ($i = 1; $i <= $this->getN(); $i++) {
        $card = $otherPlayer->getActionCardInPosition($i);
        if (count($card->getMeeplesOnIt(VENOM)) != 0) {
          // if there is already a venom token, next card
          continue;
        }

        $meeples[] = Meeples::addOnActionCard(VENOM, $card->getId(), $otherPlayer->getId());
      }
    }

    if (!empty($meeples)) {
      Notifications::venom($player, $meeples, $players);
    }
    $this->resolveAction([$meeples]);
  }
}
