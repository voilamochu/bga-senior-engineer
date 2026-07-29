<?php

namespace ARK\Models;

use ARK\Cards\Sponsors\S271_ExcavationSite;
use ARK\Managers\Meeples;
use ARK\Managers\Buildings;
use ARK\Managers\Players;
use ARK\Helpers\UserException;
use ARK\Helpers\Utils;
use ARK\Helpers\Collection;
use ARK\Core\Stats;
use ARK\Core\Globals;

/*
 * ZooMap: all utility functions concerning a Zoo Map
 */

const DIRECTIONS = [
  ['x' => -1, 'y' => -1],
  ['x' => 0, 'y' => -2],
  ['x' => 1, 'y' => -1],
  ['x' => 1, 'y' => 1],
  ['x' => 0, 'y' => 2],
  ['x' => -1, 'y' => 1],
];

class ZooMap
{
  // STATIC DATA
  protected $id = '';
  protected $asset = null;
  protected $name = '';
  protected $desc = '';
  protected $terrains = [];
  protected $bonuses = [];
  protected $upgradeNeeded = [];
  protected $workersBonuses = [];
  protected $lastWorkerBonus = null;
  protected $partnerZooBonuses = [];
  protected $facBonuses = [];
  protected $facPartnerZooLinkedBonuses = [];
  protected $bonusSpaces = [];

  protected $fullMapBonus = 7;

  // CONSTRUCT
  protected $player = null;
  protected $pId = null;
  public function __construct($player = null)
  {
    if (!is_null($player)) {
      $this->player = $player;
      $this->pId = $player->getId();
      $this->fetchDatas();
    }
  }

  public function canUseEffect()
  {
    return true;
  }

  public function isMapPower($enclosure)
  {
    return false;
  }

  public function getIncome()
  {
    return [];
  }

  public function getId()
  {
    return $this->id;
  }

  public function getName()
  {
    return $this->name;
  }

  public function getPartnerZooBonuses()
  {
    return $this->partnerZooBonuses;
  }

  public function getFacBonuses()
  {
    return $this->facBonuses;
  }

  public function getFacPartnerZooLinkedBonuses()
  {
    return $this->facPartnerZooLinkedBonuses;
  }

  public function getBonuses()
  {
    return $this->bonuses;
  }

  public function getBonusSpaces()
  {
    return $this->bonusSpaces;
  }

  public function getWorkersBonuses()
  {
    $bonuses = $this->workersBonuses;
    $b = $this->lastWorkerBonus;
    if (!is_null($b)) {
      $bonuses[3] = $b;
    }
    return $bonuses;
  }

  public function getIncomeBonuses()
  {
    return array_filter($this->bonusSpaces, function ($space) {
      return $space['type'] == INCOME;
    });
  }

  public function getUiData()
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'desc' => $this->desc,
      'asset' => $this->asset ?? $this->id,
      'terrains' => $this->terrains,
      'upgradeNeeded' => $this->upgradeNeeded,
      'bonuses' => $this->bonuses,
      'bonusSpaces' => $this->bonusSpaces,
      'workersBonuses' => $this->getWorkersBonuses(),
      'partnerZooBonuses' => $this->partnerZooBonuses,
      'facBonuses' => $this->facBonuses,
      'facPartnerZooLinkedBonuses' => $this->facPartnerZooLinkedBonuses,
    ];
  }

  public function getStatus()
  {
    return null;
  }

  public function refresh()
  {
    $this->fetchDatas();
  }

  /**
   * Fetch DB for tiles and fill the grid
   */
  protected $grid = [];
  protected $buildings = null;
  protected function fetchDatas()
  {
    if ($this->player == null) {
      return;
    }

    $this->grid = self::createGrid();
    foreach ($this->grid as $x => $col) {
      foreach ($col as $y => $cell) {
        $this->grid[$x][$y] = [
          'building' => null,
        ];
      }
    }

    // SPONSOR: EXPANSION AREA
    $hasPlayedExpansionArea = $this->player->hasPlayedCard('S272_ExpansionArea');
    $borders = $hasPlayedExpansionArea ? $this->getBorderCells() : [];

    $this->buildings = Buildings::getOfPlayer($this->pId);
    foreach ($this->buildings as &$building) {
      $building['size'] = count(BUILDINGS[$building['type']]);

      $onBorder = false;
      foreach ($this->getBuildingCoveredHexes($building, false) as $hex) {
        $this->grid[$hex['x']][$hex['y']]['building'] = &$building;
        if (in_array($hex, $borders)) {
          $onBorder = true;
        }
      }

      if ($building['type'] == 'size-3' && $onBorder) {
        $building['size'] = 5;
      }
    }
  }

  ///////////////////////////////////////////////
  //  ____        _ _     _ _
  // | __ ) _   _(_) | __| (_)_ __   __ _ ___
  // |  _ \| | | | | |/ _` | | '_ \ / _` / __|
  // | |_) | |_| | | | (_| | | | | | (_| \__ \
  // |____/ \__,_|_|_|\__,_|_|_| |_|\__, |___/
  //                                |___/
  ///////////////////////////////////////////////
  public function getBuildings()
  {
    return $this->buildings;
  }

  public function removeBuilding($buildingId)
  {
    $building = Buildings::getSingle($buildingId);
    // Remove from cached datas
    foreach ($this->getBuildingCoveredHexes($building, false) as $hex) {
      $this->grid[$hex['x']][$hex['y']]['building'] = null;
    }
    unset($this->buildings[$buildingId]);
    Buildings::remove($buildingId);
    $this->invalidateCachedDatas();
    $this->refresh();
  }

  public function addBuilding($buildingType, $pos, $rotation)
  {
    if (in_array($buildingType, \ENCLOSURES)) {
      Stats::incBuiltEnclosures($this->pId);
    } elseif ($buildingType == KIOSK) {
      Stats::incBuiltKiosks($this->pId);
    } elseif ($buildingType == PAVILION) {
      Stats::incBuiltPavilions($this->pId);
    } else {
      Stats::incBuiltUniqueStructures($this->pId);
    }
    Stats::incCoveredHexes($this->pId, count(BUILDINGS[$buildingType]));

    $building = Buildings::add($this->pId, $buildingType, $pos, $rotation);
    return $this->addBuildingAux($building);
  }

  public function placeBackBuilding($buildingId, $pos, $rotation, $previousBonuses)
  {
    $building = Buildings::placeBack($buildingId, $this->pId, $pos, $rotation);
    return $this->addBuildingAux($building, true, $previousBonuses);
  }

  protected function addBuildingAux($building, $isRepositioning = false, $previousBonuses = [])
  {
    $buildingType = $building['type'];
    $building['size'] = count(BUILDINGS[$buildingType]);
    $this->buildings[$building['id']] = &$building;
    $bonuses = [];
    $bonusHydrologist = 0;
    $bonusGeologist = 0;
    $isAlreadyFull = $this->countEmptySpaces() == 0 ? true : false;
    $this->invalidateCachedDatas();

    // useful for archeologist
    $border = $this->getBorderCells();
    $nBonusesOnBorder = 0;
    // Useful for expansion area
    $onBorder = false;

    foreach ($this->getBuildingCoveredHexes($building, false) as $hex) {
      // Space already covered? ignore it (Conference on Australia)
      if (!is_null($this->grid[$hex['x']][$hex['y']]['building'])) {
        continue;
      }

      $uid = self::getCellId($hex);
      // Hydrologist
      if ($this->player->hasPlayedCard('S241_Hydrologist')) {
        $neighbours = $this->getNeighbours($hex);
        list($water, $rock) = $this->countWaterAndRock($neighbours);
        $bonusHydrologist += $water > 0 ? 1 : 0;
      }
      // Geologist
      if ($this->player->hasPlayedCard('S242_Geologist')) {
        $neighbours = $this->getNeighbours($hex);
        list($water, $rock) = $this->countWaterAndRock($neighbours);
        $bonusGeologist += $rock > 0 ? 1 : 0;
      }
      // Expansion Area
      if ($this->player->hasPlayedCard('S272_ExpansionArea') && in_array($hex, $border)) {
        $onBorder = true;
      }
    }

    // Expansion Area
    if ($this->player->hasPlayedCard('S272_ExpansionArea') && $onBorder) {
      $building['size'] = 5;
    }

    foreach ($this->getBuildingCoveredHexes($building, false) as $hex) {
      if (is_null($this->grid[$hex['x']][$hex['y']]['building'])) {
        $this->grid[$hex['x']][$hex['y']]['building'] = $building;
        $uid = self::getCellId($hex);
        foreach ($this->bonuses[$uid] ?? [] as $bonus => $n) {
          // BONUS_STRENGTH OF MAP 12 IS NOT AN ACTUAL PLACEMENT BONUS
          if ($bonus == BONUS_STRENGTH) continue;

          $bonuses[] = [$bonus => $n];

          // S271_ExcavationSite => double placement bonus
          if ($buildingType == 'excavation') {
            $bonuses[] = [$bonus => $n];
          }

          if (in_array($hex, $border)) {
            $nBonusesOnBorder++;
          }
        }
      }
      // Already a building here => no new bonus (CONFERENCE ON AUSTRALIA)
      else {
        $this->grid[$hex['x']][$hex['y']]['building'] = $building;
      }
    }

    // No placement bonuses for reconstruction
    if ($this->player->hasPlayedCard('S280_Reconstruction')) {
      $bonuses = [];
      $nBonusesOnBorder = 0;
    }


    if ($bonusHydrologist > 0) {
      $bonuses[] = [MONEY => $bonusHydrologist, 'sourceId' => 'S241_Hydrologist'];
    }
    if ($bonusGeologist > 0) {
      $bonuses[] = [MONEY => $bonusGeologist, 'sourceId' => 'S242_Geologist'];
    }

    if (!$isAlreadyFull && $this->countEmptySpaces() == 0 && !in_array('full', $previousBonuses)) {
      // Handle map 13
      if ($this->fullMapBonus > 0) {
        $bonuses[] = [APPEAL => $this->fullMapBonus, 'source' => clienttranslate('filling the map')];
      }
    }

    // LANDSCAPE GARDENER
    if (!$isRepositioning && $buildingType == PAVILION && $this->player->hasPlayedCard('S276_LandscapeGardener') && !Globals::isLandscapeGardener()) {
      $bonuses[] = [XTOKEN => 1, 'sourceId' => 'S276_LandscapeGardener'];
    }

    // ARCHEOLOGIST
    if ($nBonusesOnBorder > 0 && $this->player->hasPlayedCard('S221_Archeologist')) {
      for ($i = 0; $i < $nBonusesOnBorder; $i++) {
        $bonuses[] = [ARCHEOLOGIST_BONUS => 1, 'sourceId' => 'S221_Archeologist'];
      }
    }

    if (!$isAlreadyFull) {
      Stats::setEmptyHexes($this->pId, $this->countEmptySpaces());
    }

    return [$building, $bonuses];
  }

  public function getBuildingAtPos($hex)
  {
    return $this->grid[$hex['x']][$hex['y']]['building'] ?? null;
  }

  public function hasBuildingAtPos($hex)
  {
    return !is_null($this->getBuildingAtPos($hex));
  }

  public function getBuildingsOfType($buildingType)
  {
    return $this->buildings->filter(function ($b) use ($buildingType) {
      return $b['type'] == $buildingType;
    });
  }

  public function getBuildingOfType($buildingType)
  {
    return $this->getBuildingsOfType($buildingType)->first();
  }

  public function hasBuilding($buildingType)
  {
    return $this->getBuildingOfType($buildingType) !== null;
  }

  protected function getBuildingsNeighbourCells()
  {
    $cells = [];
    foreach (self::getListOfCells() as $cell) {
      if (!is_null($this->getBuildingAtPos($cell))) {
        $cells = array_merge($cells, $this->getNeighbours($cell));
      }
    }
    return Utils::uniqueZones($cells);
  }

  public function isBuildingAdjacentTo($building, $cell)
  {
    $neighbours = [];
    foreach ($this->getBuildingCoveredHexes($building, false) as $hex) {
      $neighbours = array_merge($neighbours, $this->getNeighbours($hex));
    }
    return !empty(Utils::intersectZones([$cell], $neighbours));
  }

  protected $checkingCells = null;
  protected $freeCells = null;
  public function getPlacementOptionsCachedDatas()
  {
    if (is_null($this->checkingCells)) {
      $this->checkingCells = $this->buildings->empty() ? $this->getBorderCells() : $this->getConnectedCells();
    }
    if (is_null($this->freeCells)) {
      $cells = self::getListOfCells();
      Utils::filter($cells, function ($cell) {
        return !$this->hasBuildingAtPos($cell);
      });
      $this->freeCells = $cells;
    }

    return [$this->checkingCells, $this->freeCells];
  }
  public function invalidateCachedDatas()
  {
    $this->checkingCells = null;
    $this->freeCells = null;
  }

  public function getPlacementOptions($buildingType, $checkIsDoable = false, $args = [])
  {
    list($checkingCells, $freeCells) = $this->getPlacementOptionsCachedDatas();
    $byPassCheck = $this->player->hasPlayedCard('S219_DiversityResearcher');
    $size1 = count(BUILDINGS[$buildingType]) == 1;

    // COnference on Australia
    $mustCoverOneBuilding = $args['mustCoverOneBuilding'] ?? false;
    if ($mustCoverOneBuilding) {
      $freeCells = self::getListOfCells();
    }

    $result = [];
    // For each possible cell to place the reference hex of the building
    foreach ($freeCells as $pos) {
      if ($buildingType == 'kiosk' && !$this->isFarEnoughFromOtherKiosk($pos)) {
        continue;
      }

      $rotations = [];
      $coveringRotations = [];
      // Compute which rotations are valid
      for ($rotation = 0; $rotation < ($size1 ? 1 : 6); $rotation++) {
        // BUILD 4
        if ($args['canPayToBuildOnSingleWaterRock'] ?? false) {
          $hexes = self::getCoveredHexes($buildingType, $pos, $rotation, true, [WATER => true, ROCK => true]);
          if ($hexes === false) continue;

          // Count water/rock spaces
          $forbiddenSpaces = 0;
          foreach ($hexes as $hex) {
            $uid = self::getCellId($hex);
            if (in_array($uid, $this->terrains[WATER]) || in_array($uid, $this->terrains[ROCK])) {
              $forbiddenSpaces++;
            }
          }
          if ($forbiddenSpaces > 1 && !$byPassCheck) {
            continue;
          }
          if ($forbiddenSpaces != 0) {
            $coveringRotations[] = $rotation;
          }
        }
        // STARNDARD CASE : check whether covered hexes are valid
        else {
          $ignore = [];
          if ($mustCoverOneBuilding) $ignore['building'] = true;

          $hexes = self::getCoveredHexes($buildingType, $pos, $rotation, true, $ignore);
          // Are all the hexes valid to build upon ?
          if ($hexes === false) {
            continue;
          }

          // CONFERENCE ON AUSTRALIA
          if ($mustCoverOneBuilding) {
            $buildingIds = [];
            foreach ($hexes as $hex) {
              $building = $this->getBuildingAtPos($hex);
              if (!is_null($building)) {
                $buildingIds[] = $building['id'];
              }
            }

            $uniqueBuildingIds = array_unique($buildingIds);
            if (count($uniqueBuildingIds) != 1) {
              continue;
            }
            $building = $this->buildings[$buildingIds[0]];
            if (!in_array($building['type'], REGULAR_ENCLOSURES)) {
              continue; // can only cover regular enclosures
            }
            $smallSize = count(BUILDINGS[$building['type']]);
            $bigSize = count(BUILDINGS[$buildingType]);
            if (count($buildingIds) != $smallSize || $bigSize != $smallSize + 1) {
              continue; // must cover exactly the building + 1 hex
            }
          }
        }

        // Constraints for water/rock adjacency
        $constraints = BUILDINGS_CONSTRAINTS[$buildingType] ?? [];
        if ($buildingType == 'sea-turtle' && Globals::isMarineWorld()) {
          $constraints[WATER] = 2;
        }

        if (!$byPassCheck && !empty($constraints)) {
          $enclosure = [
            'type' => $buildingType,
            'rotation' => $rotation,
            'x' => $pos['x'],
            'y' => $pos['y'],
          ];
          $this->addSurroundingsToEnclosure($enclosure);
          $satisfied = true;
          foreach ($constraints as $constraint => $n) {
            if ($n > $enclosure[$constraint]) {
              $satisfied = false;
              break;
            }
          }

          if (!$satisfied) {
            continue;
          }
        }

        // Adjacency check: either adjacent to existing buildings, or on the border otherwise
        if ($buildingType == SIDE_ENTRANCE) {
          $rotations[] = $rotation;
        } elseif ($this->isIntersectionNonEmpty($hexes, $checkingCells)) {
          $rotations[] = $rotation;
        }
      }
      if (!empty($rotations)) {
        $result[] = [
          'pos' => $pos,
          'rotations' => $rotations,
        ];
        if (!empty($coveringRotations)) {
          $result[count($result) - 1]['coveringRotations'] = $coveringRotations;
        }
        if ($checkIsDoable) {
          return $result;
        }
      }
    }
    return $result;
  }

  /**
   * getCoveredHexes: given a building type, a position and rotation, return the list of hexes that would be covered by the building placed that way
   */
  public function getCoveredHexes($buildingType, $pos, $rotation, $checkAvailableToBuild = true, $ignore = null)
  {
    $ignore = $ignore ?? [];
    $hexes = [];
    if ($this->player->hasPlayedCard('S219_DiversityResearcher')) {
      $ignore[WATER] = true;
      $ignore[ROCK] = true;
    }
    if ($buildingType == UNDERWATER_TUNNEL) {
      $ignore[WATER] = true;
      $ignore[UNDERWATER_TUNNEL] = true;
    }

    foreach (BUILDINGS[$buildingType] as $delta) {
      $hexOffset = self::getRotatedHex(['x' => $delta[0], 'y' => $delta[1]], $rotation);
      $hex = [
        'x' => $pos['x'] + $hexOffset['x'],
        'y' => $pos['y'] + $hexOffset['y'],
      ];

      if (!$this->isCellAvailableToBuild($hex, $ignore ?? []) && $checkAvailableToBuild) {
        return false;
      } else {
        $hexes[] = $hex;
      }
    }

    // Check constraints, if any
    $constraints = [];
    if (in_array($buildingType, ['zoo-school', \SIDE_ENTRANCE])) {
      $constraints = ['border' => 2];
    }

    foreach ($constraints as $type => $value) {
      if ($type == 'border') {
        $borders = $this->getBorderCells();
        $check = 0;
        foreach ($hexes as $hex) {
          if (in_array($hex, $borders)) {
            $check++;
          }
        }
        if ($check < $value) {
          return false;
        }
      }
    }

    return $hexes;
  }

  // Same thing for a given DB result representing a building
  public function getBuildingCoveredHexes($building, $checkAvailableToBuild = true)
  {
    return $this->getCoveredHexes($building['type'], self::extractPos($building), $building['rotation'], $checkAvailableToBuild);
  }

  /**
   * isCellAvailableToBuild: given an hex, can we build here ?
   */
  public function isCellAvailableToBuild($hex, $ignore = [])
  {
    $uid = self::getCellId($hex);
    // Can't build outside of the grid
    if (!$this->isCellValid($hex)) {
      return false;
    }
    // Can't build on an already built cell (except Conference on Australia)
    if (!is_null($this->getBuildingAtPos($hex))) {
      return $ignore['building'] ?? false;
    }
    // Can't build on water
    if (!($ignore[WATER] ?? false) && in_array($uid, $this->terrains[WATER])) {
      return false;
    }
    // Can't build on rock
    if (!($ignore[ROCK] ?? false) && in_array($uid, $this->terrains[ROCK])) {
      return false;
    }
    // Can't build on upgraded spaces
    if (!($ignore[UPGRADED_BUILD_CARD] ?? $this->player->isCardUpgraded(BUILD)) && in_array($uid, $this->upgradeNeeded)) {
      return false;
    }


    // UNDERWATER TUNNEL
    if (($ignore[UNDERWATER_TUNNEL] ?? false) && !in_array($uid, $this->terrains[WATER])) {
      return false;
    }

    return true;
  }

  /////////////////////////////
  //  _  ___           _
  // | |/ (_) ___  ___| | __
  // | ' /| |/ _ \/ __| |/ /
  // | . \| | (_) \__ \   <
  // |_|\_\_|\___/|___/_|\_\
  /////////////////////////////

  /**
   * isFarEnoughFromOtherKiosk : check whether we can build a kiosk on a given cell
   */
  protected function isFarEnoughFromOtherKiosk($hex)
  {
    foreach ($this->buildings as $building) {
      if ($building['type'] == 'kiosk' && self::getDistance($hex, self::extractPos($building)) < 3) {
        return false;
      }
    }
    return true;
  }

  /**
   * getKioskIncome : compute the income yield by the kiosks on the map
   */
  public function getKioskIncome()
  {
    $money = 0;
    foreach ($this->getBuildingsOfType(KIOSK) as $building) {
      // 1 money per neighbours
      $nbNeighbours = $this->countBuildingNeighbours($building);
      $money += $nbNeighbours;
    }

    return $money;
  }

  /**
   * countBuildingNeighbours : count the number of neighbours around a building
   *  (auxiliary function to compute kiosk income + side entrance)
   */
  public function countBuildingNeighbours($building)
  {
    $neighbours = [];
    foreach ($this->getCoveredHexes($building['type'], $building, $building['rotation'], false) as $hex) {
      foreach ($this->getNeighbours($hex) as $cell) {
        $building2 = $this->getBuildingAtPos($cell);
        // Only count each building once as a neighbourd of current building
        if (is_null($building2) || in_array($building2['id'], $neighbours) || $building2['id'] == $building['id']) {
          continue;
        }
        // Empty regular enclosure dont count
        if (in_array($building2['type'], \REGULAR_ENCLOSURES) && $building2['state'] == 0) {
          continue;
        }

        $neighbours[] = $building2['id'];
      }
    }

    return count($neighbours);
  }

  //////////////////////////////////////////////////////
  //  _____            _
  // | ____|_ __   ___| | ___  ___ _   _ _ __ ___  ___
  // |  _| | '_ \ / __| |/ _ \/ __| | | | '__/ _ \/ __|
  // | |___| | | | (__| | (_) \__ \ |_| | | |  __/\__ \
  // |_____|_| |_|\___|_|\___/|___/\__,_|_|  \___||___/
  //////////////////////////////////////////////////////

  // Given an enclosure (building), return the list of hexes around that enclosure
  public function getEnclosureNeighbourHexes($enclosure)
  {
    $cells = [];
    foreach ($this->getBuildingCoveredHexes($enclosure, false) as $cell) {
      $cells = array_merge($cells, $this->getNeighbours($cell));
    }
    return Utils::uniqueZones($cells);
  }

  // Add the number of water/rock to an enclosure
  public function addSurroundingsToEnclosure(&$enclosure)
  {
    $neighbours = $this->getEnclosureNeighbourHexes($enclosure);
    list($water, $rock) = $this->countWaterAndRock($neighbours);
    $enclosure[WATER] = $water;
    $enclosure[ROCK] = $rock;
  }

  // Return the list of enclosures with number of water/rock surrounding them
  public function getEnclosuresWithSurroundings()
  {
    $enclosures = $this->buildings->filter(function ($building) {
      return in_array($building['type'], ENCLOSURES);
    });

    foreach ($enclosures as &$enclosure) {
      $this->addSurroundingsToEnclosure($enclosure);
    }

    return $enclosures;
  }

  public function getEmptyRegularEnclosures()
  {
    return $this->buildings->filter(function ($building) {
      return in_array($building['type'], REGULAR_ENCLOSURES) && $building['state'] == 0;
    });
  }

  public function getRegularEnclosures()
  {
    return $this->buildings->filter(function ($building) {
      return in_array($building['type'], REGULAR_ENCLOSURES);
    });
  }


  /**
   * isAnimalFittingEnclosure:
   *  - $animal : object
   *  - enclosure : array
   *  - isAnimalAdded : allow to distinguish whether we try to find an empty enclosure to add an animal, or a filled enclosure to free an animal
   *  - ignoreRequirements : allow to bypass requirements check in case of release and no other option
   * => return true/false or max number of cubes
   */
  public function isAnimalFittingEnclosure($animal, $enclosure, $isAnimalAdded = true, $ignoreRequirements = false): int|bool
  {
    // Check enclosure requirements
    $requirements = $animal->getEnclosureRequirements();
    if ($this->player->hasPlayedCard('S219_DiversityResearcher')) {
      $requirements[WATER] = 0;
      $requirements[ROCK] = 0;
    }

    if (
      !$ignoreRequirements &&
      ($enclosure[WATER] < ($requirements[WATER] ?? 0) || $enclosure[ROCK] < ($requirements[ROCK] ?? 0))
    ) {
      return false;
    }

    $type = $enclosure['type'];
    $enclosureSize = $enclosure['size']; // ?? count(BUILDINGS[$type]);

    // Regular enclosure
    if (in_array($type, \REGULAR_ENCLOSURES)) {
      $size = $animal->getEnclosureSize();
      // Animal must be ok with regular enclosure (all except domestic animals & some MW)
      if ($size == 0 || $animal->isNoRegularEnclosure()) {
        return false;
      }
      // + size big enough + enclosure is free
      if ($enclosureSize < $size || $enclosure['state'] == ($isAnimalAdded ? 1 : 0)) {
        return false;
      }
      return true;
    }
    // Special enclosure
    else {
      $special = $animal->getSpecialEnclosure();
      if (empty($special['types'] ?? [])) {
        return false;
      }

      foreach ($special['types'] as $specialType) {
        // Check that this kind of special enclosure is allowed 
        if (!in_array($type, ENCLOSURE_TYPES_MAP[$specialType])) {
          continue;
        }

        // Return how many cubes can be fitted
        return $isAnimalAdded ? ($enclosureSize - $enclosure['state']) : $enclosure['state'];
      }
      return false;
    }
  }

  public function getAvailableEnclosures(
    $animal,
    $isAnimalAdded = true,
    $ignoreRequirements = false,
    $checkIsDoable = false,
    $constraint = null,
    $excludedType = null // Useful for release
  ) {
    // ENCLOSURE TYPES
    $special = $animal->getSpecialEnclosure();
    $nCubes = $special['cubes'] ?? null;
    $types = [];
    // Regular enclosures
    if ((is_null($constraint) || $constraint == REGULAR_ENCLOSURE_TYPE) && !$animal->isNoRegularEnclosure()) {
      $types[] = REGULAR_ENCLOSURE_TYPE;
    }
    // Special enclosures
    if ((is_null($constraint) || $constraint == SPECIAL_ENCLOSURE_TYPE)) {
      if (!empty($special['types'] ?? [])) {
        $types = array_merge($types, $special['types']);
      }
    }

    // LOOP ON TYPES
    $fittingEnclosuresByType = new Collection([]);
    foreach ($types as $type) {
      $enclosureTypes = ENCLOSURE_TYPES_MAP[$type];
      if (!is_null($excludedType)) {
        $enclosureTypes = array_values(array_diff($enclosureTypes, [$excludedType]));
      }
      $enclosures = $this->getEnclosuresWithSurroundings();
      $fittingEnclosures = new Collection([]);
      $totalN = 0;
      foreach ($enclosures as $enclosure) {
        if (!in_array($enclosure['type'], $enclosureTypes)) {
          continue;
        }

        // How much can we fit ?
        $n = $this->isAnimalFittingEnclosure($animal, $enclosure, $isAnimalAdded, $ignoreRequirements);
        if ($n !== false && ($n !== 0 || $nCubes === 0)) {
          if ($n !== true) {
            $n = min($n, $nCubes);
            $totalN += $n;
          }
          $fittingEnclosures[$enclosure['id']] = $n;

          // Early abort for isDoable to prevent extra computation
          if ($checkIsDoable && $type == REGULAR_ENCLOSURE_TYPE) {
            break;
          }
        }
      }

      // Do we have enough places for all the cubes ?
      if ($type != REGULAR_ENCLOSURE_TYPE && $totalN < $nCubes) {
        continue;
      }

      // Have we found enough enclosures of this type ?
      if ($fittingEnclosures->count() > 0) {
        $fittingEnclosuresByType[$type] = $fittingEnclosures;
        if ($checkIsDoable) {
          return $fittingEnclosuresByType;
        }
      }
    }

    return $fittingEnclosuresByType;
  }

  public function getReleasableEnclosures($animal, $removeSpecialEnclosure = false, $ignoredType = null)
  {
    // 1. Do you have a matching special enclosure? (animal card has the icon, enough player tokens, water/rock if needed) If so, remove the tokens.
    // 2. Otherwise, do you have a matching standard enclosure? (occupied, large enough, water/rock if needed) If so, unflip the smallest such tile.
    // 3. Otherwise, do you have a matching special enclosure, ignoring water/rock? (animal card has the icon, enough player tokens) If so, remove the tokens.
    // 4. Otherwise, do you have a matching standard enclosure, ignoring water/rock? (occupied, large enough) If so, unflip the smallest such tile.

    // First get all the available enclosure that match water/rock requirements
    $enclosuresByTypes = $this->getAvailableEnclosures($animal, false, false, false, null, $ignoredType)->filter(
      fn($enclosures, $type) =>
      !$removeSpecialEnclosure || !in_array($type, \SPECIAL_ENCLOSURES)
    );
    // If none of them, just ignore the water/rock requirements
    if ($enclosuresByTypes->empty()) {
      $enclosuresByTypes = $this->getAvailableEnclosures($animal, false, true, false, null, $ignoredType);
    }

    // Now check the special enclosure, if any
    $types = $animal->getSpecialEnclosure()['types'] ?? [];
    $filteredEnclosures = $enclosuresByTypes->filter(
      fn($enclosures, $type) =>
      // Keep only special enclosure if $removeSpecialEnclosure if false
      //  or keep all but special enclosure if $removeSpecialEnclosure is true
      in_array($type, $types) xor $removeSpecialEnclosure
    );

    if ($removeSpecialEnclosure || !$filteredEnclosures->empty()) {
      $enclosuresByTypes = $filteredEnclosures;
    }

    // Keep the smallest ones
    $regularEnclosures = $enclosuresByTypes[REGULAR_ENCLOSURE_TYPE] ?? null;
    if (!is_null($regularEnclosures)) {
      $sizes = [];
      foreach ($regularEnclosures as $enclosureId => $n) {
        $enclosure = $this->buildings[$enclosureId];
        $size = $enclosure['size']; // ?? count(BUILDINGS[$enclosure['type']]);
        $sizes[$size][$enclosure['id']] = $n;
      }

      $enclosuresByTypes[REGULAR_ENCLOSURE_TYPE] = $sizes[min(array_keys($sizes))];
    }

    return $enclosuresByTypes;
  }

  /**
   * Fill enclosure with a new animal
   */
  public function fillEnclosure($enclosureId, $animal, $n = null)
  {
    $n = $n ?? $animal->getSpecialEnclosure()['cubes'];
    $enclosure = &$this->buildings[$enclosureId];
    $newState = 1;
    if (in_array($enclosure['type'], \SPECIAL_ENCLOSURES)) {
      $newState = $enclosure['state'] + $n;
    }
    Buildings::setState($enclosureId, $newState);
    $enclosure['state'] = $newState;
    return [$enclosure, null]; // Overwritten by map9
  }

  /**
   * Free an enclosure of an animal
   */
  public function emptyEnclosure($enclosureId, $animal, $n)
  {
    $enclosure = &$this->buildings[$enclosureId];
    $newState = 0;
    if (in_array($enclosure['type'], \SPECIAL_ENCLOSURES)) {
      $newState = $enclosure['state'] - $n;
    }
    Buildings::setState($enclosureId, $newState);
    $enclosure['state'] = $newState;
    return $enclosure;
  }

  //////////////////////////////////////
  //    ____      _   _
  //   / ___| ___| |_| |_ ___ _ __ ___
  //  | |  _ / _ \ __| __/ _ \ '__/ __|
  //  | |_| |  __/ |_| ||  __/ |  \__ \
  //   \____|\___|\__|\__\___|_|  |___/
  //////////////////////////////////////

  public function getPlacementBonusHexes()
  {
    $cells = [];
    foreach ($this->bonuses as $uid => $bonus) {
      $cells[] = $this->getHexFromId($uid);
    }
    return $cells;
  }

  public function getRockHexes()
  {
    $cells = [];
    foreach ($this->terrains[ROCK] as $uid) {
      $cells[] = $this->getHexFromId($uid);
    }
    return $cells;
  }

  public function getWaterHexes()
  {
    $cells = [];
    foreach ($this->terrains[WATER] as $uid) {
      $cells[] = $this->getHexFromId($uid);
    }
    return $cells;
  }

  // Count the number of empty spaces (excluding water/rock)
  public function countEmptySpaces()
  {
    $hexes = [];
    foreach ($this->getListOfCells() as $cell) {
      if (!$this->hasBuildingAtPos($cell)) {
        $hexes[] = $cell;
      }
    }
    list($water, $rock) = $this->countWaterAndRock($hexes);

    return count($hexes) - $water - $rock;
  }

  // Count the number of water/rock space on a given list of hexes
  protected function countWaterAndRock($hexes)
  {
    $water = 0;
    $rock = 0;
    foreach ($hexes as $hex) {
      // If a building is over a water/rock space (due to special card), the space is no longer water/rock
      //  => EXCEPT if it's the underwater tunnel
      $building = $this->getBuildingAtPos($hex);
      if (!is_null($building) && $building['type'] != UNDERWATER_TUNNEL) {
        continue;
      }

      $uid = self::getCellId($hex);
      if (in_array($uid, $this->terrains[WATER])) {
        $water++;
      }
      if (in_array($uid, $this->terrains[ROCK])) {
        $rock++;
      }
    }
    return [$water, $rock];
  }

  /* check if water or rock hex are connected */
  public function areAllTerrainHexConnected($type)
  {
    foreach ($this->terrains[$type] as $uId) {
      $hex = self::getHexFromId($uId);
      if (!is_null($this->getBuildingAtPos($hex))) {
        continue;
      }

      $found = false;
      foreach ($this->getNeighbours($hex) as $cell) {
        if ($this->hasBuildingAtPos($cell)) {
          $found = true;
        }
      }
      if ($found === false) {
        return false;
      }
    }
    return true;
  }

  public function areBorderCellsCovered()
  {
    foreach ($this->getBorderCells() as $hex) {
      $uid = self::getCellId($hex);
      if (!is_null($this->getBuildingAtPos($hex))) {
        continue;
      }

      if (in_array($uid, $this->terrains[WATER])) {
        continue;
      }

      if (in_array($uid, $this->terrains[ROCK])) {
        continue;
      }

      return false;
    }
    return true;
  }

  /**
   * getNonBuildingCells: return the list of cells that are not considered as buildings cells
   */
  public function getNonBuildingCells()
  {
    $cells = [];
    foreach (array_merge($this->terrains[WATER], $this->terrains[ROCK]) as $uid) {
      $cells[] = self::getHexFromId($uid);
    }
    return $cells;
  }

  /**
   * isBuildingCell: return true if the cell is considered as building cells
   */
  public function isBuildingCell($cell)
  {
    $uid = self::getCellId($cell);
    return !in_array($uid, $this->terrains[WATER]) && !in_array($uid, $this->terrains[ROCK]);
  }

  /**
   * getConnectedCells: return list of cells adjacent to at least one building
   *  => useful for some sponsors
   */
  public function getConnectedCells($withoutBuildings = true)
  {
    $cells = $this->getBuildingsNeighbourCells();
    if ($withoutBuildings) {
      Utils::filter($cells, function ($cell) {
        $building = $this->getBuildingAtPos($cell);
        return is_null($building) || $building['type'] == UNDERWATER_TUNNEL;
      });
    }
    return $cells;
  }

  /**
   * getIsolatedCells: return list of cells not adjacent to any building
   *  => useful for some sponsors
   */
  public function getIsolatedCells()
  {
    return Utils::diffZones(self::getListOfCells(), $this->getBuildingsNeighbourCells());
  }

  /////////////////////////////////////////////
  //   ____      _     _   _   _ _   _ _
  //  / ___|_ __(_) __| | | | | | |_(_) |___
  // | |  _| '__| |/ _` | | | | | __| | / __|
  // | |_| | |  | | (_| | | |_| | |_| | \__ \
  //  \____|_|  |_|\__,_|  \___/ \__|_|_|___/
  ////////////////////////////////////////////

  public static function getCellId($hex)
  {
    return $hex['x'] . '_' . $hex['y'];
  }

  public static function getHexFromId($uid)
  {
    $coord = explode('_', $uid);
    return ['x' => $coord[0], 'y' => $coord[1]];
  }

  public static function extractPos($building)
  {
    return [
      'x' => $building['x'],
      'y' => $building['y'],
    ];
  }

  public static function createGrid($defaultValue = null)
  {
    $dim = ['x' => 9, 'y' => 7];
    $g = [];
    for ($x = 0; $x < $dim['x']; $x++) {
      $size = $dim['y'] - ($x % 2 == 0 ? 1 : 0);
      for ($y = 0; $y < $size; $y++) {
        $row = 2 * $y + ($x % 2 == 0 ? 1 : 0);
        $g[$x][$row] = $defaultValue;
      }
    }
    return $g;
  }

  public static function getListOfCells()
  {
    $grid = self::createGrid(0);
    $cells = [];
    foreach ($grid as $x => $col) {
      foreach ($col as $y => $t) {
        $cells[] = ['x' => $x, 'y' => $y];
      }
    }
    return $cells;
  }

  protected $_borderCells = null;
  public function getBorderCells()
  {
    if (!isset($this->_borderCells)) {
      $grid = self::createGrid(0);
      $cells = [];
      foreach ($grid as $x => $col) {
        foreach ($col as $y => $t) {
          if ($y <= 1 || $x <= 0 || $y >= 11 || $x >= 8) {
            $cells[] = ['x' => $x, 'y' => $y];
          }
        }
      }
      $this->_borderCells = $cells;
    }

    return $this->_borderCells;
  }

  protected function isCellValid($cell)
  {
    return isset($this->grid[$cell['x']][$cell['y']]);
  }

  protected function areSameCell($cell1, $cell2)
  {
    return $cell1['x'] == $cell2['x'] && $cell1['y'] == $cell2['y'];
  }

  public function getNeighbours($cell)
  {
    $cells = [];
    foreach (DIRECTIONS as $dir) {
      $newCell = [
        'x' => $cell['x'] + $dir['x'],
        'y' => $cell['y'] + $dir['y'],
      ];
      if ($this->isCellValid($newCell)) {
        $cells[] = $newCell;
      }
    }
    return $cells;
  }

  protected function isIntersectionNonEmpty($cells1, $cells2)
  {
    foreach ($cells1 as $cell1) {
      foreach ($cells2 as $cell2) {
        if (self::areSameCell($cell1, $cell2)) {
          return true;
        }
      }
    }
    return false;
  }

  protected function getRotatedHex($hex, $rotation)
  {
    if ($rotation == 0 || ($hex['x'] == 0 && $hex['y'] == 0)) {
      return $hex;
    }

    $q = $hex['x'];
    $r = ($hex['y'] - $hex['x']) / 2;
    $cube = [$q, $r, -$q - $r];
    for ($i = 0; $i < $rotation; $i++) {
      $cube = [-$cube[1], -$cube[2], -$cube[0]];
    }
    return [
      'x' => $cube[0],
      'y' => 2 * $cube[1] + $cube[0],
    ];
  }

  protected function getDistance($hex1, $hex2)
  {
    $deltaX = abs($hex1['x'] - $hex2['x']);
    $deltaY = abs($hex1['y'] - $hex2['y']);
    return $deltaX + max(0, ($deltaY - $deltaX) / 2);
  }
}
