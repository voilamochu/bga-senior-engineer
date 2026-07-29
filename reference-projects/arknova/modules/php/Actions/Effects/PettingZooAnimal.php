<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Helpers\Utils;
use ARK\Core\Engine;
use ARK\Models\Player;

class PettingZooAnimal extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_PETTING_ZOO_ANIMAL;
  }

  public function getGain()
  {
    $nIcons = $this->getPlayer()->countCardIcon(PET);
    return $nIcons * 3;
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function getDescription(): string|array
  {
    return [
      'log' => clienttranslate('Petting zoo: gain <APPEAL:${n}>'),
      'args' => [
        'n' => $this->getGain(),
      ],
    ];
  }

  public function stPettingZooAnimal()
  {
    $player = Players::getActive();
    $player->incAppeal($this->getGain(), true, clienttranslate('Petting Zoo Animal action'));
    $this->resolveAction([]);
  }
}
