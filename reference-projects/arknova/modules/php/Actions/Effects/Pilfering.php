<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Helpers\Utils;
use ARK\Core\Notifications;
use ARK\Core\Engine;
use ARK\Managers\Meeples;
use ARK\Models\Player;

class Pilfering extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_PILFERING;
  }

  public function getDescription(): string|array
  {
    $log =
      $this->getN() == 1
      ? clienttranslate('Draw card or take ${resources_desc} from player with most <APPEAL>')
      : clienttranslate('Draw card or take ${resources_desc} from player with most <APPEAL> and most <CONSERVATION>');

    return [
      'log' => $log,
      'args' => [
        'resources_desc' => Utils::resourcesToStr([MONEY => 5]),
      ],
    ];
  }

  public function isOptional(): bool
  {
    $player = Players::getActive();
    return !$this->isDoable($player);
  }

  public function isDoable(Player $player): bool
  {
    list($appealPIds, $conservationPIds) = $this->getPlayersAhead($player);
    return !empty($appealPIds) || !empty($conservationPIds);
  }

  public function getPlayersAhead($player)
  {
    $appealPIds = [];
    $conservationPIds = [];
    $maxAppeal = max(5, $player->getAppeal());
    $maxConservation = max(1, $player->getConservation());
    $n = $this->getN();

    foreach (Players::getAll() as $pId => $othPlayer) {
      if (
        $othPlayer->getId() == $player->getId() ||
        $othPlayer->hasPlayedCard('S225_QuarantineLab') ||
        $othPlayer->getAppeal() < 5
      ) {
        continue;
      }

      if ($othPlayer->getAppeal() > $maxAppeal) {
        $appealPIds = [$othPlayer->getId()];
        $maxAppeal = $othPlayer->getAppeal();
      } elseif ($othPlayer->getAppeal() == $maxAppeal) {
        $appealPIds[] = $othPlayer->getId();
      }

      if ($n == 2) {
        if ($othPlayer->getConservation() > $maxConservation) {
          $conservationPIds = [$othPlayer->getId()];
          $maxConservation = $othPlayer->getConservation();
        } elseif ($othPlayer->getConservation() == $maxConservation) {
          $conservationPIds[] = $othPlayer->getId();
        }
      }
    }

    return [$appealPIds, $conservationPIds];
  }

  public function argsPilfering()
  {
    $player = Players::getActive();
    list($appealPIds, $conservationPIds) = $this->getPlayersAhead($player);
    $n = $this->getN();

    return [
      'descSuffix' => $n == 2 ? 'multiple' : '',
      'appealPIds' => $appealPIds,
      'conservationPIds' => $conservationPIds,
      'n' => $n,
    ];
  }

  // public function stPilfering()
  // {
  //   $args = $this->argsPilfering();
  //   if (count($args['appeal']) < 2 && count($args['conservation']) < 2) {
  //     $appealPId = array_key_first($args['appeal']);
  //     $conservationPId = array_key_first($args['conservation']);

  //     $this->actPilfering($appealPId, $conservationPId, true);
  //   }
  // }

  public function actPilfering($appealPId, $conservationPId, $auto = false)
  {
    self::checkAction('actPilfering', $auto);
    if ($auto && empty($args['appealPIds']) && empty($args['conservationPIds'])) {
      $this->resolveAction([]);
      return;
    }

    $player = Players::getActive();
    $args = $this->argsPilfering();

    foreach (Players::getAll() as $pId => $otherPlayer) {
      if ($otherPlayer->getId() == $player->getId() && $otherPlayer->hasPlayedCard('S225_QuarantineLab')) {
        Notifications::message(
          clienttranslate('${player_name} is not affected by Pilfering thanks to their Quarantine Lab sponsor card'),
          ['player' => $otherPlayer]
        );
        break;
      }
    }

    // Target for appeal
    $target1 = null;
    if (!empty($args['appealPIds'])) {
      if (!in_array($appealPId, $args['appealPIds'])) {
        throw new \BgaVisibleSystemException('You cannot select this player id. Should not happen');
      }

      $this->insertAsChild([
        'action' => PILFERING_EXECUTE,
        'pId' => $appealPId,
        'args' => ['pId' => $player->getId()],
      ]);
      $target1 = Players::get($appealPId);
    }

    // Target for conservation
    $target2 = null;
    if (!empty($args['conservationPIds'])) {
      if (!in_array($conservationPId, $args['conservationPIds'])) {
        throw new \BgaVisibleSystemException('You cannot select this player id. Should not happen');
      }

      $this->insertAsChild([
        'action' => PILFERING_EXECUTE,
        'pId' => $conservationPId,
        'args' => ['pId' => $player->getId()],
      ]);
      $target2 = Players::get($conservationPId);
    }

    Notifications::pilfering($player, $target1, $target2);
    $this->resolveAction([$appealPId, $conservationPId]);
  }
}
