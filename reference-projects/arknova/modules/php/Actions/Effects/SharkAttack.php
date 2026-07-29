<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Core\Engine;
use ARK\Helpers\Utils;
use ARK\Helpers\FlowConvertor;
use ARK\Actions\Build;
use ARK\Models\Player;

class SharkAttack extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_SHARK_ATTACK;
  }

  public function getDescription(): array
  {
    return [
      'log' => clienttranslate('Shark Attack ${n}'),
      'args' => [
        'n' => $this->getN(),
      ]
    ];
  }

  public function isDoable(Player $player): bool
  {
    return $player->getCardsInReputationRange(CARD_ANIMAL)->count() > 0;
  }

  public function isOptional(): bool
  {
    return true;
  }

  public function argsSharkAttack()
  {
    $player = Players::getActive();

    return [
      'n' => $this->getN(),
      'cardIds' => $player->getCardsInReputationRange(CARD_ANIMAL)->getIds()
    ];
  }

  public function actSharkAttack($cardIds)
  {
    self::checkAction('actSharkAttack');

    $player = Players::getActive();
    $args = $this->argsSharkAttack();
    if (count($cardIds) > $args['n']) {
      throw new \BgaVisibleSystemException('Too much discarded cards. Should not happen');
    }

    $appeal = 0;
    $cards = ZooCards::getMany($cardIds);
    foreach ($cards as $cardId => $card) {
      if (!in_array($cardId, $args['cardIds'])) {
        throw new \BgaVisibleSystemException('Invalid card to discard');
      }
      $appeal += $card->getAppeal();
    }

    list($discarded, $assigned, $meeples) = ZooCards::discard($cardIds);
    Notifications::discardCardsOnDisplay(
      $player,
      $discarded,
      clienttranslate('${player_name} discards ${card_names} for the Shark Attack effect'),
    );
    Notifications::markAssign($assigned, $meeples);

    $this->insertAsChild([
      'action' => GAIN,
      'source' => clienttranslate('Shark Attack'),
      'args' => [APPEAL => intdiv($appeal, 2)],
      'pId' => $player->getId(),
    ]);
    $this->resolveAction([]);
  }
}
