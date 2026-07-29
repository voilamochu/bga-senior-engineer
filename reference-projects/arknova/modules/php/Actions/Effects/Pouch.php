<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Models\ZooCard;
use ARK\Models\Player;

class Pouch extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_POUCH;
  }

  public function getDescription(): string|array
  {
    return [
      'log' => clienttranslate('Pouch ${n}'),
      'args' => [
        'n' => $this->getN(),
      ],
    ];
  }

  public function isOptional(): bool
  {
    return true;
  }

  public function isDoable(Player $player): bool
  {
    return $player->getHand()->count() > 0;
  }

  public function argsPouch()
  {
    $player = Players::getActive();

    return [
      'n' => $this->getN(),
      '_private' => [
        'active' => [
          'cardIds' => $player->getHand()->getIds(),
        ],
      ],
    ];
  }

  public function actPouch($cardIdsToPouch)
  {
    $this->checkAction('actPouch');
    $player = Players::getActive();
    $mapEffect = $this->getCtxArg('mapEffect') ?? false;

    if (count($cardIdsToPouch) > $this->getN()) {
      throw new \BgaVisibleSystemException('Wrong number of cards to pouch. Should not happen');
    }
    if (!empty(array_diff($cardIdsToPouch, $player->getHand()->getIds()))) {
      throw new \BgaVisibleSystemException('Invalid card to pouch. Should not happen');
    }

    $sourceId = $this->ctx->getSourceId();
    if (!is_null($sourceId)) {
      $origin = ZooCards::get($sourceId);
      $pouched = $origin->getExtraDatas('pouch') ?? 0;
      $pouched += count($cardIdsToPouch);
      $origin->setExtraDatas('pouch', $pouched);
    } else {
      $sourceId = 'mapPouched';
    }

    $appeal = 2 * count($cardIdsToPouch);
    $player->incAppeal($appeal, false);
    $cardsToPouch = ZooCards::getMany($cardIdsToPouch);
    foreach ($cardsToPouch as $card) {
      $card->setLocation($sourceId);
      $card->setState(POUCHED);
    }

    Notifications::pouch($player, $cardsToPouch, $appeal, $sourceId);
    $this->resolveAction([]);
  }
}
