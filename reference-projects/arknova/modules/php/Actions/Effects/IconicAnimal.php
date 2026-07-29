<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Helpers\Utils;
use ARK\Core\Engine;
use ARK\Models\Player;

class IconicAnimal extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_ICONIC_ANIMAL;
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function countIcon()
  {
    $icon = 0;
    foreach (Players::getAll() as $pId => $player) {
      $icon += $player->countCardIcon($this->getN());
    }
    return min($icon, 8);
  }

  public function getDescription(): string|array
  {
    return [
      'log' => clienttranslate('Iconic Animal: Gain ${resources_desc}'),
      'args' => [
        'resources_desc' => Utils::resourcesToStr([APPEAL => $this->countIcon()]),
      ],
    ];
  }

  public function stIconicAnimal()
  {
    $player = Players::getActive();
    $player->incAppeal($this->countIcon(), true, clienttranslate('Iconic animal'));
    $this->resolveAction([]);
  }
}
