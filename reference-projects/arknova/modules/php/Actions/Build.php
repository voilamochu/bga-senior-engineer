<?php

namespace ARK\Actions;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Managers\ActionCards;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Engine;
use ARK\Core\Globals;
use ARK\Core\Stats;
use ARK\Helpers\Utils;
use ARK\Helpers\FlowConvertor;
use ARK\Models\Player;

const PAVILION_BUILD1 = 'pavilion1';
const KIOSK_BUILD2 = 'kiosk2';
const UNIQUE_CARD_BUILDINGS = [PAVILION_BUILD1, KIOSK_BUILD2];

class Build extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_BUILD;
  }

  public function isOptional(): bool
  {
    return !empty($this->getPreviousBuildings()) || ($this->getCtxArg('canPass') ?? false);
  }

  public function isDoable(Player $player): bool
  {
    return $this->getPlayableBuildings($player, true) || (!empty($this->getPreviousBuildings()) && $player->hasAnytimeUsefulAction());
  }

  public function isFree()
  {
    return $this->getCtxArg('free') ?? false;
  }

  public function isUniqueBuilding()
  {
    return $this->getCtxArg('unique') ?? false;
  }

  public function getPreviousBuildings()
  {
    return $this->getCtxArg('previous') ?? [];
  }

  public function getComputedPrevious($previous)
  {
    $previous = $previous ?? $this->getPreviousBuildings();
    $player = Players::getActive();
    $engineerUsed = false;

    // Check duplicated
    $toRemove = UNIQUE_CARD_BUILDINGS;
    foreach (array_count_values($previous) as $type => $c) {
      if ($c <= 1) continue;

      // Duplicated special kiosk and pavilions must come from engineer
      if (in_array($type, UNIQUE_CARD_BUILDINGS)) {
        $engineerUsed = true;
      }
      // kiosk/pavilion because they must come from engineer 
      if (in_array($type, [KIOSK, PAVILION])) {
        $engineerUsed = true;
        $toRemove[] = $type;
      }
      // Regular enclosure must come from engineer unless build3 lvl 2
      if (in_array($type, REGULAR_ENCLOSURES)) {
        $canUseEngineer = !$engineerUsed && $player->hasPlayedCard('S217_Engineer');
        if ($canUseEngineer) {
          $engineerUsed = true;
          $toRemove[] = $type;
        }
      }
    }

    // Remove special buildings from unique action cards
    // => remove only first occurence because engineer can copy!
    foreach ($toRemove as $type) {
      $key = array_search($type, $previous);
      if ($key !== FALSE) {
        unset($previous[$key]);
      }
    }

    // Current total strength
    $totalStrength = array_reduce($previous, function ($total, $b) {
      return $total + count(BUILDINGS[$b]);
    }, 0);

    // Are we already beyond total strength?
    if ($totalStrength > $this->getStrength() && !$engineerUsed) {
      $engineerUsed = true;
      // Remove the biggest duplicated buildings
      $sizes = [];
      foreach (array_count_values($previous) as $type => $c) {
        if ($c >= 2) $sizes[$type] = count(BUILDINGS[$type]);
      }
      $maxType = array_keys($sizes, max($sizes))[0];
      $key = array_search($maxType, $previous);
      unset($previous[$key]);
      $totalStrength -= max($sizes);
    }

    $canUseEngineer = !$engineerUsed && $player->hasPlayedCard('S217_Engineer');

    return [$previous, $totalStrength, $canUseEngineer];
  }

  public function getPreviousTotalSize($previous = null)
  {
    $previous = $previous ?? $this->getPreviousBuildings();
    list(, $totalStrength,) = $this->getComputedPrevious($previous);
    return $totalStrength;
  }

  public function canUseEngineer($player, $previousBuildings = null)
  {
    $previousBuildings = $previousBuildings ?? $this->getPreviousBuildings();
    list(,, $canUseEngineer) = $this->getComputedPrevious($previousBuildings);
    return $canUseEngineer;
  }

  public function getPossibleBuildings($player, $forcedPrevious = null, $byPassCostCheck = false)
  {
    // Free building => can only build this one
    $forcedType = $this->getCtxArg('freeBuilding') ?? null;
    if (!is_null($forcedType)) {
      $buildings = is_array($forcedType) ? $forcedType : [$forcedType];
      // Keep the check about special buildings
      Utils::filter($buildings, function ($buildingType) use ($player) {
        return !in_array($buildingType, \SPECIAL_ENCLOSURES) || !$player->map()->hasBuilding($buildingType);
      });
      return $buildings;
    }

    // Set of all possibile buildings depending on card lvl
    $buildingsMap = [
      1 => ['pavilion', 'kiosk', 'size-1', 'size-2', 'size-3', 'size-4', 'size-5', PETTING_ZOO],
      2 => [LARGE_BIRD_AVIARY, REPTILE_HOUSE],
    ];

    if (Globals::isMarineWorld()) {
      $buildingsMap[1] =  ['pavilion', 'kiosk', 'size-1', 'size-2', 'size-3', 'size-4', 'size-5', PETTING_ZOO, SMALL_AQUARIUM, LARGE_AQUARIUM];
    }

    $buildings = array_merge($buildingsMap[1], $this->isUpgraded() ? $buildingsMap[2] : []);

    // Upgraded card => ensure not twice the same building and total size fit strength
    $realPreviousBuildings = $forcedPrevious ?? $this->getPreviousBuildings();
    list($previousBuildings, $previousTotalSize, $canUseEngineer) = $this->getComputedPrevious($realPreviousBuildings);

    // Engineer without upgraded card => restrict building to same one
    $previous = array_diff($realPreviousBuildings, UNIQUE_CARD_BUILDINGS);
    if (!$this->isUpgraded() && !empty($previous)) {
      $buildings = $previous;
    }
    if ($this->getNumber() == 1 && (!in_array(PAVILION_BUILD1, $realPreviousBuildings) || $canUseEngineer)) {
      array_unshift($buildings, PAVILION_BUILD1);
    }
    if ($this->getNumber() == 2 && (!in_array(KIOSK_BUILD2, $realPreviousBuildings) || $canUseEngineer)) {
      array_unshift($buildings, KIOSK_BUILD2);
    }

    Utils::filter($buildings, function ($buildingType) use ($player, $previousTotalSize, $previousBuildings, $canUseEngineer, $byPassCostCheck, $realPreviousBuildings) {
      // Not twice the same building (except for Engineer and BUILD3 LVL2)
      $checkNoDuplicate = !in_array($buildingType, $previousBuildings);

      // Special enclosure can only be built once
      $checkSpecialEnclosure = !in_array($buildingType, \SPECIAL_ENCLOSURES) || !$player->map()->hasBuilding($buildingType);
      // Upper bound on size given by strength
      if (in_array($buildingType, UNIQUE_CARD_BUILDINGS)) {
        $size = 1;
        $checkSize = true;
        $cost = $this->isUpgraded() ? 2 : 3;
        if (in_array($buildingType, $realPreviousBuildings) && $canUseEngineer) {
          $cost = 2; // VERY SPECIFIC INTERACTION WITH ENGINEER AND UNIQUE CARDS BUILDINGS
        }
      } else {
        $size = count(BUILDINGS[$buildingType]);
        $checkSize = $size + $previousTotalSize <= $this->getStrength();
        $cost = 2 * $size;
      }

      // Check the cost
      $checkMoney = $player->getMoney() >= $cost;

      // Engineer
      if ($canUseEngineer && !$checkNoDuplicate) {
        $checkNoDuplicate = true;
        $checkSize = true;

        // Using Engineer to copy Build1 & 2 bonus buildings uses the usual cost of 2, even at lvl1
        if (in_array($buildingType, UNIQUE_CARD_BUILDINGS)) {
          $cost = 2;
        }
      }
      // BUILD3 - SIDE 2
      if (!$checkNoDuplicate && $this->getNumber() == 3 && $this->isUpgraded() && in_array($buildingType, REGULAR_ENCLOSURES)) {
        $checkNoDuplicate = true;
      }

      return $checkNoDuplicate && $checkSpecialEnclosure && $checkSize && ($checkMoney || $byPassCostCheck);
    });

    return $buildings;
  }

  public static function getPlayableBuildingsAux(Player $player, $checkIsDoable, $forcedBuildings, $args = [])
  {
    $buildings = [];
    foreach ($forcedBuildings as $buildingType) {
      $args2 = $args;
      $type = $buildingType;
      if ($type == PAVILION_BUILD1) $type = PAVILION;
      if ($type == KIOSK_BUILD2) $type = KIOSK;

      // ASSOC 4 - LVL1 - can the player can afford these 2$ extra??
      if ($args['canPayToBuildOnSingleWaterRock'] ?? false) {
        $size = count(BUILDINGS[$buildingType]);
        $cost = 2 * $size;
        if ($player->getMoney() < $cost + 2) {
          $args2['canPayToBuildOnSingleWaterRock'] = false;
        }
      }
      // ASSOC 4 - LVL 2
      if ($args['canBuildOnSingleWaterRock'] ?? false) {
        $args2['canPayToBuildOnSingleWaterRock'] = true;
      }

      $placementOptions = $player->map()->getPlacementOptions($type, $checkIsDoable, $args2);
      if (!empty($placementOptions)) {
        if ($checkIsDoable) {
          return true;
        }
        $buildings[$buildingType] = $placementOptions;
      }
    }

    return $checkIsDoable ? false : $buildings;
  }

  public function getPlayableBuildings($player, $checkIsDoable = false, $forcedBuildings = null, $forcedPrevious = null)
  {
    // BUILD 4
    $args = [];
    if ($this->getNumber() == 4) {
      if (!$this->isUpgraded()) {
        // LVL1 - ensure it's used at most 1 due to engineer
        $args['canPayToBuildOnSingleWaterRock'] = $this->getCtxArg('canPayToBuildOnSingleWaterRock') ?? true;
      } else {
        // LVL2 - ensure it's used at most 1 per action
        $args['canBuildOnSingleWaterRock'] = $this->getCtxArg('canBuildOnSingleWaterRock') ?? true;
      }
    }

    return self::getPlayableBuildingsAux($player, $checkIsDoable, $forcedBuildings ?? $this->getPossibleBuildings($player, $forcedPrevious), $args);
  }

  public function getDescription(): string|array
  {
    if ($this->isFree()) {
      if ($this->isUniqueBuilding()) {
        return  clienttranslate('Place unique building');
      }
      if ($this->getCtxArg('freeBuilding') == KIOSK) {
        return clienttranslate('Build a <KIOSK> for free');
      }
      if ($this->getCtxArg('freeBuilding') == PAVILION) {
        return clienttranslate('Build a pavilion for free');
      }
      return clienttranslate('Build for free');
    } else {
      return '';
    }
  }

  public function argsBuild($buildingTypes = null)
  {
    $player = Players::getActive();

    return [
      'buildings' => $this->getPlayableBuildings($player, false, $buildingTypes),
      'allBuildings' => $this->getPossibleBuildings($player, null, true),
      'allAffordableBuildings' => $this->getPossibleBuildings($player),
      'maxSize' => $this->getStrength() - $this->getPreviousTotalSize(),
      'strength' => $this->getStrength(),
      'strength_icon' => '',
      'free' => $this->isFree(),
      'lvl' => $this->getLevel(),
      'canPass' => $this->getCtxArg('canPass') ?? false,
      'descSuffix' => $this->isUniqueBuilding()
        ? 'unique'
        : ($this->isFree()
          ? 'free'
          : ($this->canUseEngineer($player)
            ? 'engineer'
            : '')),
    ];
  }

  public function actBuild($buildingType, $pos, $rotation)
  {
    self::checkAction('actBuild');
    $args = $this->getArgs();
    $player = Players::getActive();

    // Check engineer
    $buildings = $this->getCtxArg('previous') ?? []; // Dont use getPreviousBuilding for unique action cards
    if (in_array($buildingType, $buildings)) {
      $canUseEngineer = $this->canUseEngineer($player);
      $canUseBuild3 = $this->getNumber() == 3 && $this->isUpgraded() && in_array($buildingType, REGULAR_ENCLOSURES);

      if ($canUseEngineer && !$canUseBuild3) {
        $msg = clienttranslate('${player_name} uses the Engineer effect');
      } else if (!$canUseEngineer && $canUseBuild3) {
        $msg = clienttranslate('${player_name} uses the Build3 effect');
      } else if ($canUseEngineer && $canUseBuild3) {
        $msg = clienttranslate('${player_name} uses the Engineer/Build3 effect');
      } else {
        throw new \BgaVisibleSystemException('Duplicated building without engineer nor Build3. Should not happen');
      }

      Notifications::message($msg, ['player' => Players::getActive()]);
    }
    if ($buildingType == PAVILION_BUILD1) {
      $args['cost'] = $this->isUpgraded() ? 2 : 3;
      Notifications::message(clienttranslate('${player_name} uses Build1 effect'), ['player' => Players::getActive()]);
    }
    if ($buildingType == KIOSK_BUILD2) {
      $args['cost'] = $this->isUpgraded() ? 2 : 3;
      Notifications::message(clienttranslate('${player_name} uses Build2 effect'), ['player' => Players::getActive()]);
    }
    $buildings[] = $buildingType;

    // BUILD4
    $covering = false;
    if ($this->getNumber() == 4) {
      $option = self::getCheckedOption($buildingType, $pos, $rotation, $args);
      if (isset($option['coveringRotations']) && in_array($rotation, $option['coveringRotations'])) {
        $covering = true;
        Notifications::message(clienttranslate('${player_name} uses Build4 effect to build over rock/water'), ['player' => $player]);
        if ($this->isUpgraded()) {
          $coveringBonus = [MONEY => 2, 'source' => clienttranslate("Build4")];
        } else if (!$player->hasPlayedCard('S219_DiversityResearcher')) {
          $args['extraCost'] = 2;
        }
      }
    }

    // Place the building
    $bonuses = $this->actBuildAux($buildingType, $pos, $rotation, $args);
    if (isset($coveringBonus)) $bonuses[] = $coveringBonus;
    $this->insertBonusesFlow($bonuses, clienttranslate('placement bonus'));

    // Are we done yet ?
    if (!$this->isFree()) {
      if (
        $this->isUpgraded()
        || $this->canUseEngineer($player, $buildings)
        || in_array($this->getNumber(), [1, 2])
      ) {
        $updatedArgs = ['previous' => $buildings];
        if ($covering) {
          $updatedArgs['canBuildOnSingleWaterRock'] = false;
          $updatedArgs['canPayToBuildOnSingleWaterRock'] = false;
        }
        $this->duplicateAction($updatedArgs);
      }
    }

    $this->resolveAction([$buildingType, $pos, $rotation]);
  }

  // Sanity checks
  public static function getCheckedOption($buildingType, $pos, $rotation, $args, $cardId = null)
  {
    $options = $args['buildings'][$buildingType] ?? null;
    if (is_null($options)) {
      throw new \BgaVisibleSystemException('You cannot build that type of building. Should not happen');
    }
    $optionIndex = Utils::search($options, function ($option) use ($pos) {
      return $option['pos']['x'] == $pos['x'] && $option['pos']['y'] == $pos['y'];
    });
    $option = $optionIndex === false ? null : $options[$optionIndex];
    if (is_null($option) || !in_array($rotation, $option['rotations'])) {
      throw new \BgaVisibleSystemException('You cannot build this building here. Should not happen');
    }
    return $option;
  }

  /**
   * Extract the main part of build to be able to call from other animal effects like Posturing
   */
  public static function actBuildAux($buildingType, $pos, $rotation, $args, $cardId = null)
  {
    $player = Players::getActive();
    self::getCheckedOption($buildingType, $pos, $rotation, $args, $cardId);

    // Pay for it
    if ($args['free'] ?? false) {
      $cost = null;
    } else {
      if (in_array($buildingType, UNIQUE_CARD_BUILDINGS)) {
        $cost = $args['cost'];
        $buildingType = $buildingType == PAVILION_BUILD1 ? PAVILION : KIOSK;
      } else {
        $cost = 2 * count(BUILDINGS[$buildingType]);
      }
      $cost += $args['extraCost'] ?? 0; // BUILD 4

      $player->pay($cost, false, null);
      Stats::incMoneyUsedBuild($player, $cost);
    }

    // Place it on the board
    list($building, $bonuses) = $player->map()->addBuilding($buildingType, $pos, $rotation);
    $card = is_null($cardId) ? null : ZooCards::getSingle($cardId);
    Notifications::buyBuilding($player, $cost, $building, $card);
    if ($buildingType == 'pavilion') {
      $player->incAppeal(1, true, clienttranslate('building a pavilion'));
    }

    // Special enclosure => MOVE ANIMAL
    if (in_array($buildingType, SPECIAL_ENCLOSURES)) {
      $canMoveAnimal = true;

      // No petting animal can be placed inside regular enclosure so no animal to move
      if ($buildingType == PETTING_ZOO) {
        $canMoveAnimal = false;
      }
      // Aquarium => check number of total aquarium built
      if (in_array($buildingType, ENCLOSURE_TYPES_MAP[AQUARIUM])) {
        $nBuilt = 0;
        foreach (ENCLOSURE_TYPES_MAP[AQUARIUM] as $type) {
          if ($player->map()->hasBuilding($type)) {
            $nBuilt++;
          }
        }
        $canMoveAnimal = $nBuilt == 1; // Can move animal only if it's first aquarium
      }

      if ($canMoveAnimal) {
        $bonuses[] = [MOVE_ANIMALS => $buildingType];
      }
    }

    if (in_array($buildingType, [SMALL_AQUARIUM, LARGE_AQUARIUM])) {
      $icons = [WATER => 1];
      list($immediateReaction, $afterReaction) = ZooCards::getIconsReaction($icons, $player, true);
      $bonuses = array_merge($bonuses, $immediateReaction);
      // Ignore afterReactions as the only differed reaction is expertonAfrica and no building has any Africa icons
    }

    // Handle bonuses
    return $bonuses;
  }
}
