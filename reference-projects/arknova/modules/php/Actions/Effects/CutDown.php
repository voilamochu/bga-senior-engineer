<?php

namespace ARK\Actions\Effects;

use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Helpers\Collection;
use ARK\Managers\Buildings;
use ARK\Models\Player;

class CutDown extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_CUT_DOWN;
  }

  public function getDescription(): string
  {
    return clienttranslate('Cut Down');
  }

  public function isDoable(Player $player): bool
  {
    return $this->getRemovableEnclosures($player)->count() > 0;
  }

  public function isOptional(): bool
  {
    return true;
  }

  public function getRemovableEnclosures(Player $player): Collection
  {
    return $player->map()->getEmptyRegularEnclosures()->map(fn($enclosure) => $enclosure['size']);
  }

  public function argsCutDown()
  {
    $player = Players::getActive();

    return [
      'enclosures' => $this->getRemovableEnclosures($player),
    ];
  }

  public function actCutDown($buildingId)
  {
    self::checkAction('actCutDown');

    $player = Players::getActive();
    $args = $this->argsCutDown();
    $size = $args['enclosures'][$buildingId] ?? null;
    if (is_null($size)) {
      throw new \BgaVisibleSystemException('Invalid enclosure. Should not happen');
    }

    $player->map()->removeBuilding($buildingId);
    $money = 2 * $size;
    $bonuses = $player->incMoney($money, false);
    Notifications::cutDown($player, $buildingId, $size, $money);
    $this->insertBonusesFlow($bonuses);

    $this->resolveAction([]);
  }
}
