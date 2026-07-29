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

class ArcheologistBonus extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_ARCHEOLOGIST_BONUS;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Gain an (uncovered) placement bonus');
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function isIndependent(?Player $player = null): bool
  {
    return false;
  }

  public function stArcheologistBonus()
  {
    $player = Players::getActive();
    $map = $player->map();

    $bonusesLeft = [];
    foreach ($map->getBonuses() as $uid => $b) {
      if ($map->hasBuildingAtPos($map->getHexFromId($uid))) {
        continue;
      }

      foreach ($b as $type => $n) {
        $bonusesLeft[] = [
          'action' => TAKE_BONUS,
          'args' => [
            'type' => $type,
            'n' => $n,
            'sourceId' => 'S221_Archeologist',
          ],
        ];
      }
    }

    if (!empty($bonusesLeft)) {
      $this->insertAsChild([
        'type' => \NODE_XOR,
        'optional' => true,
        'childs' => $bonusesLeft,
        'sourceId' => 'S221_Archeologist',
      ]);
    }

    $this->resolveAction([]);
  }
}
