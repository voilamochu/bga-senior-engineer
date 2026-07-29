<?php

namespace ARK\Actions;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Core\Notifications;
use ARK\Managers\ActionCards;
use ARK\Core\Engine;
use ARK\Core\Stats;
use ARK\Helpers\Utils;
use ARK\Helpers\FlowConvertor;
use ARK\Managers\ZooCards;
use ARK\Models\Player;

class MoveAnimals extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_MOVE_ANIMALS;
  }

  public function getDescription(): string|array
  {
    return clienttranslate('Move animals to the new building');
  }

  /**
   * Get the special enclosure just played
   */
  public function getBuildingType()
  {
    return $this->getCtxArg('buildingType');
  }
  public function getSpecialEnclosure($player)
  {
    $buildingType = $this->getBuildingType();
    $enclosure = $player->map()->getBuildingOfType($buildingType);
    $player->map()->addSurroundingsToEnclosure($enclosure);
    return $enclosure;
  }

  /**
   * Get the animals that might be moved into that new special enclosure
   */
  public function getAnimalsForSpecialEnclosure($player)
  {
    $buildingType = $this->getBuildingType();
    $animals = $player->getPlayedAnimal()->filter(function ($card) {
      return $card->getLocation() != 'rescueStation'; // MAP10
    });
    $validAnimals = [];
    foreach ($animals as $id => $animal) {
      $special = $animal->getSpecialEnclosure();
      // Check that this kind of special enclosure is allowed for the played animal
      $found = false;
      foreach (($special['types'] ?? []) as $enclosureType) {
        if (in_array($buildingType, ENCLOSURE_TYPES_MAP[$enclosureType])) {
          $found = true;
        }
      }
      if ($found) {
        $validAnimals[$id] = $animal;
      }
    }
    return $validAnimals;
  }

  /**
   * Get the animals that can be moved, along with the choice of enclosures that can be freed
   */
  public function getMovableAnimals($player, $processed = null)
  {
    $processed = $processed ?? ($this->getCtxArg('processed') ?? []);
    $buildingType = $this->getBuildingType();
    $enclosure = $this->getSpecialEnclosure($player);
    $animals = [];
    foreach ($this->getAnimalsForSpecialEnclosure($player) as $aId => $animal) {
      // Already move in the special enclosure ?
      if (in_array($aId, $processed)) {
        continue;
      }

      // Enough space and meet requirement to move them ?
      $n = $player->map()->isAnimalFittingEnclosure($animal, $enclosure);
      if ($n !== false && $n >= $animal->getSpecialEnclosure()['cubes']) {
        $animals[$aId] = $player
          ->map()
          ->getReleasableEnclosures($animal, false, $buildingType);
      }
    }

    return $animals;
  }

  public function isDoable(Player $player): bool
  {
    return count($this->getMovableAnimals($player)) > 0;
  }

  public function argsMoveAnimals()
  {
    $player = Players::getActive();
    $names = [
      LARGE_BIRD_AVIARY => clienttranslate('Large Bird Aviary'),
      REPTILE_HOUSE => clienttranslate('Reptile House'),
      SMALL_AQUARIUM => clienttranslate('Small Aquarium'),
      LARGE_AQUARIUM => clienttranslate('Large Aquarium'),
      UNDERWATER_TUNNEL => clienttranslate('Underwater Tunnel'),
    ];

    return [
      'i18n' => ['specialEnclosure'],
      'specialEnclosure' => $names[$this->getBuildingType()],
      'animals' => $this->getMovableAnimals($player),
    ];
  }

  public function actMoveAnimals($cardId, $type, $selection)
  {
    self::checkAction('actMoveAnimals');
    $args = $this->argsMoveAnimals();
    $enclosuresByTypes = $args['animals'][$cardId] ?? null;
    // Sanity check
    if (is_null($enclosuresByTypes)) {
      throw new \BgaVisibleSystemException('You cannot move that animal. Should not happen');
    }
    $animal = ZooCards::get($cardId);
    $enclosures = $enclosuresByTypes[$type] ?? null;
    if (is_null($enclosures)) {
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

    // fill new enclosure
    $player = Players::getActive();
    $specialEnclosure = $this->getSpecialEnclosure($player);
    list($enclosure, $map9Continent) = $player->map()->fillEnclosure($specialEnclosure['id'], $animal);
    $freeEnclosures = [];
    // Unflip of building / removal of token
    foreach ($selection as $enclosureId => $n) {
      $building = $player->map()->emptyEnclosure($enclosureId, $animal, $n);
      $freeEnclosures[] = $building;
    }
    Notifications::moveAnimal($player, $animal, $enclosure, $freeEnclosures);

    // Map 9 effect
    if (isset($map9Continent)) {
      $this->pushParallelChild([
        'action' => MAP9,
        'args' => ['continent' => $map9Continent],
      ]);
    }

    $processed = $this->getCtxArg('processed') ?? [];
    $processed[] = $cardId;

    // Any animal left to move ?
    $animalsLeft = $this->getMovableAnimals($player, $processed);
    if (!empty($animalsLeft)) {
      // Loop on same node with updated args
      $this->duplicateAction(['processed' => $processed]);
    }

    $this->resolveAction(['cardId' => $cardId, 'enclosureId' => $enclosureId]);
  }
}
