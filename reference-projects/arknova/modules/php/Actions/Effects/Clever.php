<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Engine;
use ARK\Managers\ActionCards;
use ARK\Models\ZooCard;
use ARK\Models\Player;

class Clever extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_CLEVER;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Move 1 action card to <STRENGTH:1>');
  }

  public function isOptional(): bool
  {
    return true;
  }

  public function argsClever()
  {
    $player = Players::getActive();
    return [
      'cardIds' => $player->getActionCards()->getIds(),
    ];
  }

  public function actClever($cardId)
  {
    $this->checkAction('actClever');
    $player = Players::getActive();
    $card = ActionCards::get($cardId);
    if ($card->getPId() != $player->getId()) {
      throw new \BgaVisibleSystemException('It\'s not your card. Should not happen');
    }

    $type = $card->getActionType();
    $actionCards = $player->moveActionCard($type, 1);
    Notifications::clever($player, $card, 1, $actionCards);
    $this->resolveAction(['cardId' => $cardId]);
  }
}
