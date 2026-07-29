<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Models\Player;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;

class MonkeyGang extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_MONKEY_GANG;
  }

  public function getDescription(): string
  {
    return clienttranslate('Monkey Gang: <SEARCH-PRIMATE>');
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function isIrreversible(?Player $player = null): bool
  {
    return true;
  }

  public function stMonkeyGang()
  {
    $this->checkCanTakeIrreversible();

    $player = Players::getActive();
    $card = ZooCards::searchCard($player, PRIMATE);
    Notifications::searchCard($player, $card, PRIMATE, MONKEY_GANG);
    $this->resolveAction([], true);
  }
}
