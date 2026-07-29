<?php

namespace ARK\Actions\Bonuses;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Stats;
use ARK\Helpers\Utils;
use ARK\Actions\Association;
use ARK\Models\Player;

class Expedition extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_EXPEDITION;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Expedition');
  }

  public function isDoable(Player $player): bool
  {
    return $this->getHiredPerson($player)->count() > 0;
  }

  public function getHiredPerson(Player $player)
  {
    return $player->getPlayedCards(CARD_SPONSOR)->filter(fn($card) => $card->isPerson());
  }

  public function argsExpedition()
  {
    $player = Players::getActive();
    return [
      'cardIds' => $this->getHiredPerson($player)->getIds(),
    ];
  }

  public function actExpedition($cardId)
  {
    // Sanity checks
    self::checkAction('actExpedition');
    $player = Players::getActive();
    $args = $this->getArgs();
    if (!in_array($cardId, $args['cardIds'])) {
      throw new \BgaVisibleSystemException('Cannot discard that card. Should not happen');
    }

    $card = ZooCards::getSingle($cardId);
    ZooCards::discard($cardId);
    Notifications::expedition($player, $card);
    $this->insertAsChild(['action' => GAIN, 'args' => [CONSERVATION => 1], 'sourceId' => $this->ctx->getSourceId()]);
    $this->resolveAction([$cardId]);
  }
}
