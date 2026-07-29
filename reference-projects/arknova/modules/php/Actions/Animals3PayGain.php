<?php

namespace ARK\Actions;

use ARK\Core\Notifications;
use ARK\Models\Player;
use ARK\Managers\Players;
use ARK\Core\Engine;

class Animals3PayGain extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_ANIMALS3_PAYGAIN;
  }

  public function getDescription(): string
  {
    return clienttranslate('Pay <MONEY:2> to gain <APPEAL:1>');
  }

  public function isOptional(): bool
  {
    return true;
  }

  public function isDoable(Player $player): bool
  {
    return $player->getMoney() >= 2;
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function stAnimals3PayGain()
  {
    $player = Players::getActive();
    $player->pay(2, false);
    $player->incAppeal(1, false);
    Notifications::getBonuses(
      $player,
      [MONEY => -2, APPEAL => 1],
      '',
      ['source' => clienttranslate('Animals3 ability')],
      clienttranslate('${player_name} pays <MONEY:2> to gain <APPEAL:1> (${source})')
    );

    $this->resolveAction([]);
  }
}
