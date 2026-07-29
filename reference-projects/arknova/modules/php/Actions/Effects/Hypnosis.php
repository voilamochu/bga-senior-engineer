<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Helpers\Utils;
use ARK\Core\Notifications;
use ARK\Core\Engine;
use ARK\Managers\Meeples;
use ARK\Models\Player;

class Hypnosis extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_HYPNOSIS;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Hypnosis: target a player with most <APPEAL>');
  }

  public function isOptional(): bool
  {
    $player = Players::getActive();
    return !$this->isDoable($player);
  }

  public function isDoable(Player $player): bool
  {
    $args = $this->argsHypnosis($player);
    return !empty($args['pIds']);
  }

  public function isAutomatic(?Player $player = null): bool
  {
    $args = $this->argsHypnosis($player);
    return count($args['pIds']) < 2;
  }

  public function preHypnosis()
  {
    $player = $player ?? Players::getActive();
    foreach (Players::getAll() as $pId => $otherPlayer) {
      if ($pId == $player->getId() && $otherPlayer->hasPlayedCard('S225_QuarantineLab')) {
        Notifications::message(
          clienttranslate('${player_name} is not affected by Hypnosis thanks to their Quarantine Lab sponsor card'),
          ['player' => $otherPlayer]
        );
        break;
      }
    }
  }

  public function argsHypnosis($player = null)
  {
    $player = $player ?? Players::getActive();
    $pIds = [];
    $maxAppeal = max($player->getAppeal(), 5); // not impacted if less than 5 appeal

    foreach (Players::getAll() as $pId => $otherPlayer) {
      // XOR because having quarantine lab can't be used to skip your track to determine max appeal
      if ($pId == $player->getId() xor $otherPlayer->hasPlayedCard('S225_QuarantineLab')) {
        continue;
      }

      $appeal = $otherPlayer->getAppeal();
      if ($appeal > $maxAppeal) {
        $pIds = [$otherPlayer->getId()];
        $maxAppeal = $appeal;
      } elseif ($appeal == $maxAppeal) {
        $pIds[] = $otherPlayer->getId();
      }
    }

    Utils::filter($pIds, fn($pId) => $pId != $player->getId());
    return ['pIds' => $pIds];
  }

  public function stHypnosis()
  {
    $args = $this->getArgs();
    if (count($args['pIds']) < 2) {
      $pId = $args['pIds'][0];
      $this->actHypnosis($pId, true);
    }
  }

  public function actHypnosis($pId, $auto = false)
  {
    self::checkAction('actHypnosis', $auto);
    $player = Players::getActive();
    $args = $this->argsHypnosis();

    // No one to target
    if ($auto && empty($args['pIds'])) {
      $this->resolveAction([]);
      return;
    }

    if (!in_array($pId, $args['pIds'])) {
      throw new \BgaVisibleSystemException('You cannot select this player id. Should not happen');
    }

    // Notify
    $target = Players::get($pId);
    Notifications::hypnosis($player, $target);

    // Insert after finishing action
    $cards = $target->getActionCards()->filter(function ($card) {
      return $card->getStrength() <= 3;
    });
    $this->pushAfterFinishingChilds([
      [
        'action' => CHOOSE_ACTION_CARD,
        'pId' => $player->getId(),
        'args' => [
          'hypnosisCards' => $cards->getIds(),
          'hypnosis' => true,
          'hypnosisPId' => $pId,
        ],
      ],
    ]);

    $this->resolveAction([$pId]);
  }
}
