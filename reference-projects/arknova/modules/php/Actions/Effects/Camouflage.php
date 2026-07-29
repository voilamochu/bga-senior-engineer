<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Core\Notifications;
use ARK\Models\Player;
use ARK\Core\Globals;

class Camouflage extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_CAMOUFLAGE;
  }

  public function getDescription(): string
  {
    return clienttranslate('Camouflage');
  }

  public function isDoable(Player $player): bool
  {
    return true;
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function isIndependent(?Player $player = null): bool
  {
    return true;
  }

  public function stCamouflage()
  {
    $player = Players::getActive();
    Globals::incEffectCamouflage();

    Notifications::message(clienttranslate('${player_name} may ignore one condition for the next animal they play this turn (Camouflage effect)'), ['player' => $player]);

    $this->resolveAction([]);
  }
}
