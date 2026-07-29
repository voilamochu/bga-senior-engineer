<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Globals;
use ARK\Core\Engine;
use ARK\Helpers\Utils;
use ARK\Models\Player;

class Pack extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_PACK;
  }

  public function getDescription(): string|array
  {
    return [
      'log' => clienttranslate('Gain <APPEAL:1> for each predator icon: gain ${resources_desc}'),
      'args' => [
        'resources_desc' => Utils::resourcesToStr([APPEAL => $this->getGain()]),
      ],
    ];
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function getGain()
  {
    $player = Players::getActive();
    return $player->countCardIcon(PREDATOR);
  }

  public function stPack()
  {
    $player = Players::getActive();
    $predators = $this->getGain();

    if ($predators > 0) {
      $player->incAppeal($predators, true, clienttranslate('Pack action'));
    }

    $this->resolveAction([$predators]);
  }
}
