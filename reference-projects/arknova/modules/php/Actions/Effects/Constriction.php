<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Managers\Meeples;
use ARK\Models\Player;

class Constriction extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_CONSTRICTION;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Give constriction token(s)');
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function stConstriction()
  {
    $player = Players::getActive();
    $appeal = $player->getAppeal();
    $conservation = $player->getConservation();

    $meeples = [];
    $players = [];
    foreach (Players::getAll() as $pId => $otherPlayer) {
      // not impacted if less than 5 appeal or played QuarantineLab
      if ($otherPlayer->getId() == $player->getId() || $otherPlayer->getAppeal() < 5) {
        continue;
      }

      // How many tokens will this player receive ?
      $toCreate = 0;
      if ($otherPlayer->getAppeal() > $appeal) {
        $toCreate++;
      }
      if ($otherPlayer->getConservation() > $conservation) {
        $toCreate++;
      }

      if ($toCreate > 0 && $otherPlayer->hasPlayedCard('S225_QuarantineLab')) {
        Notifications::message(
          clienttranslate('${player_name} is not affected by Constriction thanks to their Quarantine Lab sponsor card'),
          ['player' => $otherPlayer]
        );
        continue;
      }


      if ($toCreate > 0) {
        $players[] = $otherPlayer;
      }

      for ($i = 5; $i > 5 - $toCreate; $i--) {
        $card = $otherPlayer->getActionCardInPosition($i);
        if (count($card->getMeeplesOnIt(CONSTRICTION)) != 0) {
          // if there is already a constriction token, next card
          continue;
        }
        $meeples[] = Meeples::addOnActionCard(CONSTRICTION, $card->getId(), $otherPlayer->getId());
      }
    }

    if (!empty($meeples)) {
      Notifications::constriction($player, $meeples, $players);
    }
    $this->resolveAction([$meeples]);
  }
}
