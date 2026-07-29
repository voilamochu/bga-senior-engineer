<?php

namespace ARK\Actions;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Core\Notifications;
use ARK\Managers\ActionCards;
use ARK\Core\Engine;
use ARK\Core\Stats;
use ARK\Helpers\Utils;
use ARK\Models\Player;

class UpgradeCard extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_UPGRADE_CARD;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Upgrade a card');
  }

  public function argsUpgradeCard()
  {
    $player = Players::getActive();
    $actionCards = $player->getActionCards()->filter(function ($card) {
      return $card->getLevel() == 1;
    });
    return ['actionCardIds' => $actionCards->getIds()];
  }

  public function actUpgradeCard($cardId)
  {
    self::checkAction('actUpgradeCard');
    $args = $this->argsUpgradeCard();
    if (!in_array($cardId, $args['actionCardIds'])) {
      throw new \BgaVisibleSystemException('This card cannot be upgraded. Should not happen');
    }

    $player = Players::getActive();
    $card = ActionCards::get($cardId);
    $card->setLevel(2);
    Notifications::upgradeCard($player, ActionCards::get($cardId));

    Stats::incUpgradedCards($player);
    $statName = 'incUpgradedAction' . $card->getName();
    Stats::$statName($player);

    $this->resolveAction(['cardId' => $cardId]);
  }
}
