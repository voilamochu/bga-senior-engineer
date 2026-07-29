<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Core\Engine;
use ARK\Models\Player;

class Determination extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_DETERMINATION;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Take 1 additional action');
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function stDetermination()
  {
    $player = Players::getActive();
    $this->pushParallelChilds([
      [
        'action' => CHOOSE_ACTION_CARD,
        'pId' => $player->getId(),
      ],
    ]);

    $args = [
      'player' => $player,
    ];

    $source = $this->ctx->getSource() ?? null;
    $sourceId = $this->ctx->getSourceId() ?? null;
    if (is_null($source) && !is_null($sourceId)) {
      $source = ZooCards::getSingle($sourceId);
    }

    if ($source instanceof \ARK\Models\ZooCard) {
      $msg = clienttranslate('${player_name} gets an extra action (${card_name})');
      $args['card_id'] = $source->getId();
      $args['card_name'] = $source->getName();
      $args['i18n'][] = 'card_name';
      $args['preserve'][] = 'card_id';
    } else {
      $msg = clienttranslate('${player_name} gets an extra action (${source})');
      $args['source'] = $source;
      $args['i18n'][] = 'source';
    }

    Notifications::message($msg, $args);

    $this->resolveAction([]);
  }
}
