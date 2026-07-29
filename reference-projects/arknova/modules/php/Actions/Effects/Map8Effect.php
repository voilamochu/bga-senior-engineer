<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Helpers\Utils;
use ARK\Helpers\Collection;
use ARK\Models\Player;

class Map8Effect extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_MAP8;
  }

  public function isIrreversible(?Player $player = null): bool
  {
    return true;
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function getDescription(): string|array
  {
    return [
      'log' => clienttranslate('Take ${n} sponsor cards from deck'),
      'args' => [
        'n' => $this->getN(),
      ],
    ];
  }

  public function stMap8Effect()
  {
    $this->checkCanTakeIrreversible();

    // Find n random sponsors in deck
    $player = Players::getActive();
    $cards = new Collection([]);
    for ($i = 0; $i < $this->getN(); $i++) {
      $cards[] = ZooCards::searchCard($player, CARD_SPONSOR);
    }

    Notifications::map8Effect($player, $cards);
    $this->resolveAction([], true);
  }
}
