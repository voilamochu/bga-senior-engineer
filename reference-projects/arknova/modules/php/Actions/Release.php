<?php

namespace ARK\Actions;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Core\Notifications;
use ARK\Managers\ZooCards;
use ARK\Core\Engine;
use ARK\Core\Stats;
use ARK\Helpers\Utils;
use ARK\Models\Player;

class Release extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_RELEASE;
  }

  // public function isDoable(Player $player): bool  // {
  //   $animal = $this->getAnimal();
  //   return !empty($this->getReleasableBuildings($player, $animal));
  // }

  public function isAutomatic(?Player $player = null): bool
  {
    return $this->getOnlyChoice() !== false;
  }

  public function getAnimal()
  {
    return ZooCards::getSingle($this->getCtxArg('card'));
  }

  public function getReleasableBuildings($player, $animal)
  {
    return $player->map()->getReleasableEnclosures($animal);
  }

  public function argsRelease()
  {
    $player = Players::getActive();
    $animal = $this->getAnimal();

    return [
      'i18n' => ['card_name'],
      'card_name' => $animal->getName(),
      'card_id' => $animal->getId(),
      'buildings' => $this->getReleasableBuildings($player, $animal),
    ];
  }

  public function getOnlyChoice()
  {
    $buildings = $this->argsRelease()['buildings'];
    if (count($buildings) == 1) {
      $key = $buildings->getIds()[0];
      if (count($buildings[$key]) == 1) {
        return [$key, $buildings[$key]];
      }

      // Special enclosure => auto if only one way to remove the cubes
      if ($key != REGULAR_ENCLOSURE_TYPE) {
        $animal = $this->getAnimal();
        $totalN = 0;
        foreach ($buildings[$key] as $enclosureId => $n) {
          $totalN += $n;
        }
        if ($totalN == $animal->getSpecialEnclosure()['cubes']) {
          return [$key, $buildings[$key]];
        }
      }
    }
    // No enclosure => full auto
    elseif ($buildings->empty()) {
      return [null, []];
    }

    return false;
  }

  public function stRelease()
  {
    $choice = $this->getOnlyChoice();
    if ($choice !== false) {
      $this->actRelease($choice[0], $choice[1], true);
    }
  }

  public function actRelease($type, $selection, $automatic = false)
  {
    // Sanity checks
    self::checkAction('actRelease', $automatic);
    $player = Players::getActive();
    $animal = $this->getAnimal();
    $buildings = $this->getReleasableBuildings($player, $animal);

    // Sanity check
    if (!is_null($type)) {
      $enclosures = $buildings[$type] ?? null;
      if (is_null($enclosures) && !$buildings->empty()) {
        throw new \BgaVisibleSystemException('Invalid animal or enclosure type. Should not happen');
      }
      $totalN = 0;
      foreach ($selection as $enclosureId => $n) {
        if (!isset($enclosures[$enclosureId])) {
          throw new \BgaVisibleSystemException('Invalid enclosure. Should not happen');
        }
        $totalN += $n;
      }
      if ($type != REGULAR_ENCLOSURE_TYPE && $totalN != $animal->getSpecialEnclosure()['cubes']) {
        throw new \BgaVisibleSystemException('Wrong number of cubes. Should not happen');
      }
    }

    // Loose the appeal of the animal release
    $player->incAppeal($animal->getAppeal() * -1, false);
    Stats::incAnimalsReleased($player);

    $buildings = [];
    if (!empty($selection)) {
      // Unflip of building / removal of token
      foreach ($selection as $enclosureId => $n) {
        $building = $player->map()->emptyEnclosure($enclosureId, $animal, $n);
        $buildings[] = $building;
      }
    } else {
      $buildings = [['type' => 'empty']];
    }
    ZooCards::discard([$animal->getId()]);
    Notifications::releaseAnimal($player, $animal, $buildings, [APPEAL => $animal->getAppeal()]);

    $this->resolveAction(['selection' => $selection]);
  }
}
