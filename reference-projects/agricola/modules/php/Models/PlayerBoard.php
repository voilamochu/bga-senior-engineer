<?php
namespace AGR\Models;

use AGR\Core\Globals;
use AGR\Core\Notifications;
use AGR\Helpers\UserException;
use AGR\Helpers\Utils;
use AGR\Managers\Fences;
use AGR\Managers\Meeples;
use AGR\Managers\PlayerCards;
use AGR\Managers\Stables;

/*
 * PlayerBoard: all utility functions concerning a player board
 */

define('W', 0);
define('NW', 1);
define('N', 2);
define('NE', 3);
define('E', 4);
define('SE', 5);
define('S', 6);
define('SW', 7);

define('INSIDE', 1);
define('BORDER', 2);
define('CURRENT_WORK', 3);

class PlayerBoard
{
  protected $player = null;
  protected $pId = null;
  protected $grid = []; // 10x6 grid that holds 'nodes' (room/field/empty) + 'edges' (fences) + virtual intersections
  protected $fences = [];
  protected $rooms = [];
  protected $stables = [];
  protected $fields = [];
  protected $pastures = null; // Array of all current pastures
  protected $arePasturesUpToDate = false;
  protected $farmHandStable = null; // Special Farm Hand stable (B85) stored off-grid
  public function __construct($player)
  {
    $this->player = $player;
    $this->pId = $player->getId();
    $this->fetchDatas();
  }

  public function getUiData()
  {
    return [
      'pastures' => $this->getPastures(),
      'fences' => $this->fences,
      'dropZones' => $this->getAnimalsDropZones(),
      'farmHandStable' => $this->getFarmHandTopLeft(),
    ];
  }

  public function refresh()
  {
    $this->fetchDatas();
  }

  /**
   * Fetch DB for fences/rooms/fields and fill the grid
   */
  protected function fetchDatas()
  {
    $this->grid = self::createEmptyGrid();

    $this->fences = Fences::getOnBoard($this->pId)->toArray();
    foreach ($this->fences as $fence) {
      $this->grid[$fence['x']][$fence['y']] = $fence;
    }

    $this->rooms = Meeples::getRooms($this->pId)->toArray();
    foreach ($this->rooms as $room) {
      $this->grid[$room['x']][$room['y']] = $room;
    }

    $this->fields = Meeples::getFields($this->pId)->toArray();
    foreach ($this->fields as &$field) {
      $field['uid'] = $field['x'] . '_' . $field['y'];
      $this->grid[$field['x']][$field['y']] = $field;
    }
    foreach (PlayerCards::getFieldCards($this->pId) as $card) {
      $this->addFieldCard($card->getId(), $card->getFieldDetails());
    }

    $this->stables = Stables::getOnBoard($this->pId)->toArray();
    foreach ($this->stables as $stable) {
      $this->grid[$stable['x']][$stable['y']] = $stable;
    }

    // Special Farm Hand stable (stored separately)
    $this->farmHandStable = Stables::getFarmHandStable($this->pId);
  }

  public function getRooms()
  {
    return $this->rooms;
  }

  // returns 1 field for Rock Garden / Wood Field
  public function countFields()
  {
    return $this->countLogicalFields();
  }

  public function getFields()
  {
    return $this->fields;
  }

  public function getStables()
  {
    return $this->stables;
  }

  public function countFencedStables()
  {
    $fencedStables = 0;
    $zones = $this->getAnimalsDropZones();
    foreach ($zones as $zone) {
      if ($zone['type'] != 'pasture') {
        continue;
      }
      $fencedStables += count($zone['stables']);
    }

    return $fencedStables;
  }

  public function countWoodPalisades()
  {
    return \array_reduce(
      $this->fences,
      function ($carry, $fence) {
        return $carry + ((($fence['state'] ?? 0) == FENCE_STATE_WOOD_PALISADE) ? 1 : 0);
      },
      0
    );
  }

  /*************************
   ********* ADDERS *********
   *************************/

  /**
   * Add a fence at a given position
   * This only check that the player has a fence in reserve and that the spot is free
   */
  public function addFence(&$fence, $location = 'reserve')
  {
    self::checkFencePos($fence['x'], $fence['y']);

    if (!Fences::hasAvailable($this->pId, $location)) {
      throw new \BgaVisibleSystemException('You do not have any fence available');
    }

    // check fence is on a correct place
    if ($this->containsFence($fence)) {
      throw new \BgaVisibleSystemException('A fence already exist at this position');
    }

    // Move fence
    $id = Fences::moveNextAvailable($this->pId, 'board', $fence, $location);
    $fence = Meeples::get($id);
    $this->fences[] = $fence;
    $this->grid[$fence['x']][$fence['y']] = $fence;
    $this->arePasturesUpToDate = false;

    // Track fence timings
    $fencesTurnId = Globals::getFencesTurnId();
    $fencesTurnId[$this->pId] = Globals::getTurnId();
    Globals::setFencesTurnId($fencesTurnId);
  }

  /**
   * B030 Wood Palisades:
   * Add a fence marker made of 2 wood on a farm border edge.
   * This does not consume a fence piece from reserve.
   */
  public function addWoodPalisadeFence(&$fence)
  {
    self::checkFencePos($fence['x'], $fence['y']);

    if ($this->containsFence($fence)) {
      throw new \BgaVisibleSystemException('A fence already exist at this position');
    }

    $onBorder = $fence['x'] == 0 || $fence['x'] == 10 || $fence['y'] == 0 || $fence['y'] == 6;
    if (!$onBorder) {
      throw new UserException(clienttranslate('Wood Palisades can only be placed on farm border fence spaces'));
    }

    $ids = Meeples::createResourceInLocation(
      'fence',
      'board',
      $this->pId,
      $fence['x'],
      $fence['y'],
      1,
      FENCE_STATE_WOOD_PALISADE
    );

    $id = is_array($ids) ? $ids[0] : $ids;
    $fence = Meeples::get($id);
    $this->fences[] = $fence;
    $this->grid[$fence['x']][$fence['y']] = $fence;
    $this->arePasturesUpToDate = false;

    $fencesTurnId = Globals::getFencesTurnId();
    $fencesTurnId[$this->pId] = Globals::getTurnId();
    Globals::setFencesTurnId($fencesTurnId);
  }

  //for E149_MidnightFencer
  public function addFenceFromOpponentReserve(&$fence, $fromPid)
  {
    self::checkFencePos($fence['x'], $fence['y']);

    if (!Fences::hasAvailable($fromPid, 'reserve')) {
      throw new \BgaVisibleSystemException('That player does not have any fence available');
    }

    if ($this->containsFence($fence)) {
      throw new \BgaVisibleSystemException('A fence already exist at this position');
    }

    // Move fence from donor reserve to board coords
    $id = Fences::moveNextAvailable($fromPid, 'board', $fence, 'reserve');

    // Transfer ownership and ensure it's considered on this player's board, keep its original color
    Meeples::DB()->update(['player_id' => $this->pId], $id);
    $colors = Globals::getFenceColorPids();
    $colors[$id] = $fromPid;
    Globals::setFenceColorPids($colors);
    Notifications::updatePersonalOwned($fromPid, Meeples::getPersonalOwned($fromPid));
    Notifications::updatePersonalOwned($this->pId, Meeples::getPersonalOwned($this->pId));

    $fence = Meeples::get($id);
    $fence['colorPid'] = $fromPid;
    $this->fences[] = $fence;
    $this->grid[$fence['x']][$fence['y']] = $fence;
    $this->arePasturesUpToDate = false;

    // Track fence timings
    $fencesTurnId = Globals::getFencesTurnId();
    $fencesTurnId[$this->pId] = Globals::getTurnId();
    Globals::setFencesTurnId($fencesTurnId);
  } 

  /**
   * Add a stable at a given position
   * This only check that the spot is free
   */
  public function addStable(&$stable)
  {
    self::checkNodePos($stable['x'], $stable['y']);

    if (!Stables::hasAvailable($this->pId)) {
      throw new \BgaVisibleSystemException('You do not have any stable available');
    }

    if (!$this->isFree($stable)) {
      throw new \BgaVisibleSystemException('This node is not free');
    }

    $id = Stables::moveNextAvailable($this->pId, 'board', $stable);
    $stable = Meeples::get($id);
    $this->stables[] = $stable;
    $this->grid[$stable['x']][$stable['y']] = $stable;
    $this->arePasturesUpToDate = false;

    // Track stable timings
    $stablesTurnId = Globals::getStablesTurnId();
    if (!isset($stablesTurnId[$this->pId])) {
      $stablesTurnId[$this->pId] = [$id => Globals::getTurnId()];
    } else {
      $stablesTurnId[$this->pId][$id] = Globals::getTurnId();
    }
    Globals::setStablesTurnId($stablesTurnId);
  }

  /**
   * Add a field at a given position
   * This only check that the spot is free
   */
  public function addField(&$field)
  {
    self::checkNodePos($field['x'], $field['y']);
    if (!$this->isFree($field)) {
      throw new \BgaVisibleSystemException('This node is not free');
    }

    // Create the field meeple and update the variable
    $id = Meeples::createResourceInLocation('field', 'board', $this->pId, $field['x'], $field['y']);
    $field = Meeples::get($id); // Update $field value
    $this->fields[] = $field;
    $this->grid[$field['x']][$field['y']] = $field;
  }

  /**
   * Add a room at a given position
   * This only check that the spot is free
   */
  public function addRoom($roomType, &$room)
  {
    self::checkNodePos($room['x'], $room['y']);
    if (!$this->isFree($room)) {
      throw new \BgaVisibleSystemException('This node is not free');
    }

    // Create the room meeple and update the variable
    $id = Meeples::createResourceInLocation($roomType, 'board', $this->pId, $room['x'], $room['y']);
    $room = Meeples::get($id);
    $this->rooms[] = $room;
    $this->grid[$room['x']][$room['y']] = $room;

    // Track room timings
    $roomsTurnId = Globals::getRoomsTurnId();
    $roomsTurnId[$this->pId] = ['roomType' => $roomType, 'turnId' => Globals::getTurnId()];
    Globals::setRoomsTurnId($roomsTurnId);
  }

  /**
   * Change all rooms type to $newRoomType
   */
  public function renovateRooms($newRoomType)
  {
    $rooms = $this->rooms;
    $this->rooms = [];

    $roomsTurnId = Globals::getRoomsTurnId();
    $hadRoomTracking = array_key_exists($this->pId, $roomsTurnId);
    $roomTracking = $hadRoomTracking ? $roomsTurnId[$this->pId] : null;

    foreach ($rooms as &$room) {
      $tmp = $room['id'];
      // Remove existing room
      Meeples::DB()->delete($room['id']);
      $this->grid[$room['x']][$room['y']] = null;
      // Add the next room in the same place
      $this->addRoom($newRoomType, $room);
      $room['oldId'] = $tmp;
    }

    if ($hadRoomTracking) {
      $roomsTurnId[$this->pId] = $roomTracking;
    } else {
      unset($roomsTurnId[$this->pId]);
    }
    Globals::setRoomsTurnId($roomsTurnId);

    return $rooms;
  }

  /****************************
   ******* SANITY CHECKS *******
   ****************************/

  /**
   * Check whether the current board is valid wrt to pastures
   */
  public function arePasturesValid($raiseException = false)
  {
    // Disallow fencing that encloses a field, even if computePastures() doesn't include it
    foreach (self::getAllNodes() as $pos) {
      if ($this->containsField($pos['x'], $pos['y']) && $this->isNodeRegionFenced($pos)) {
        if ($raiseException) {
          throw new UserException(clienttranslate('You cannot fence around a field'));
        }
        return false;
      }
    }

    $pastures = $this->getPastures();
    if (empty($pastures)) {
      return true;
    }

    // Checks that pasture contains nothing expect stable
    foreach ($pastures as $pasture) {
      foreach ($pasture['nodes'] as $pos) {
        if (!$this->isFreeOrStable($pos)) {
          if ($raiseException) {
            throw new UserException(clienttranslate('A pasture contains a room or a field'));
          }
          return false;
        }
      }
    }

    // Check adjacency of pastures
    $marks = $this->getPasturesMarks();
    if (!self::isConnex($marks)) {
      if ($raiseException) {
        throw new UserException(clienttranslate('Some pastures are not adjacent'));
      }
      return false;
    }

    return true;
  }

  /**
   * Check whether board crop stacks are still on valid sow locations:
   * - regular field tiles, or
   * - Love for Agriculture 1/2-space pastures (at most one stack per pasture).
   */
  public function areCropStacksValid($raiseException = false)
  {
    $boardStacks = [];
    foreach ($this->getGrowingCrops() as $crop) {
      if (($crop['location'] ?? null) !== 'board') {
        continue;
      }

      $x = $crop['x'] ?? 0;
      $y = $crop['y'] ?? 0;

      // Board crop stacks must always sit on board node coordinates.
      if (!self::isValid($x, $y) || $x <= 0 || $y <= 0 || $x % 2 != 1 || $y % 2 != 1) {
        if ($raiseException) {
          throw new UserException(clienttranslate('A crop stack is on an invalid board position'));
        }
        return false;
      }

      $boardStacks[$x . '_' . $y] = ['x' => $x, 'y' => $y];
    }

    if (empty($boardStacks)) {
      return true;
    }

    $lovePastureByNode = [];
    if ($this->player->hasPlayedCard('B72_LoveforAgriculture')) {
      foreach ($this->getPastures() as $pasture) {
        $size = count($pasture['nodes'] ?? []);
        if (!in_array($size, [1, 2], true)) {
          continue;
        }

        $pastureKey = $this->getNodesKey($pasture['nodes']);
        foreach ($pasture['nodes'] as $node) {
          $lovePastureByNode[$node['x'] . '_' . $node['y']] = $pastureKey;
        }
      }
    }

    $stacksPerLovePasture = [];
    foreach ($boardStacks as $stack) {
      if ($this->containsField($stack['x'], $stack['y'])) {
        continue;
      }

      $nodeKey = $stack['x'] . '_' . $stack['y'];
      if (!isset($lovePastureByNode[$nodeKey])) {
        if ($raiseException) {
          throw new UserException(clienttranslate('You must keep sown crops valid for Love for Agriculture'));
        }
        return false;
      }

      $pastureKey = $lovePastureByNode[$nodeKey];
      $stacksPerLovePasture[$pastureKey] = ($stacksPerLovePasture[$pastureKey] ?? 0) + 1;
      if ($stacksPerLovePasture[$pastureKey] > 1) {
        if ($raiseException) {
          throw new UserException(clienttranslate('A Love for Agriculture pasture can contain at most one crop stack'));
        }
        return false;
      }
    }

    return true;
  }

  /**
   * Check whether the current board is valid wrt to fences
   */
  public function areFencesValid($raiseException = false)
  {
    foreach (self::getAllEdges() as $pos) {
      if ($this->containsFence($pos)) {
        $endpoints = $this->getEndpointConnections($pos);
        if (empty($endpoints['start']) || empty($endpoints['end'])) {
          if ($raiseException) {
            throw new UserException(clienttranslate('One fence is not connected at one of its endpoints'));
          }

          return false;
        }
      }
    }

    return true;
  }

  /**
   * Check whether the current board is valid wrt to fields
   */
  public function areFieldsValid($raiseException = false)
  {
    // Check adjacency of fields
    $marks = self::getSubgraphMarks($this->getFieldTiles());
    if (!self::isConnex($marks)) {
      if ($raiseException) {
        throw new UserException(clienttranslate('Some fields are not adjacent'));
      }
      return false;
    }

    return true;
  }

  /**
   * Check whether the current board is valid wrt to rooms
   */
  public function areRoomsValid($raiseException = false)
  {
    // Check adjacency of fields
    $marks = self::getSubgraphMarks($this->rooms);
    if (!self::isConnex($marks)) {
      if ($raiseException) {
        throw new UserException(clienttranslate('Some rooms are not adjacent'));
      }
      return false;
    }

    return true;
  }

  /**
   * Check whether the current board is valid wrt to animals
   */
  public function areAnimalsValid($raiseException = true)
  {
    $extraAnimals = $this->getInvalidAnimals($raiseException);
    return empty($extraAnimals);
  }

  /**
   * Get animals in an incorrect locations
   */
  public function getInvalidAnimals($raiseException = true)
  {
    $zones = $this->getAnimalsDropZonesWithAnimals(true);
    $animals = [];

    foreach ($zones as $id => $zone) {
      // Add all the unaccomodated animals
      if ($id === 'unaccomodated') {
        foreach ($zone['meeples'] as $meeple) {
          $animals[] = $meeple;
        }
        continue;
      }

      // Check max capacity
      if ($zone['animals'] > $zone['capacity']) {
        if ($raiseException) {
          throw new UserException(clienttranslate('A room/pasture/stable contains too many animals'));
        }

        // Keep the first one, and return the extra ones
        $i = 0;
        foreach ($zone['meeples'] as $meeple) {
          $i++;
          if ($i > $zone['capacity']) {
            $animals[] = $meeple;
          }
        }
      }

      // Check potential constraints about animal type
      if (isset($zone['constraints'])) {
        foreach ($zone['meeples'] as $meeple) {
          if (!in_array($meeple['type'], $zone['constraints'])) {
            if ($raiseException) {
              throw new UserException(clienttranslate('This zone cannot contain this type of animal'));
            }

            $animals[] = $meeple;
          }
        }
      }

      // Check card constraint
      if ($zone['type'] == 'card' && method_exists(PlayerCards::get($zone['card_id']), 'getInvalidAnimals')) {
        $card = PlayerCards::get($zone['card_id']);
        $animals = array_merge($animals, $card->getInvalidAnimals($zone, $raiseException));
      }
      // Check only one type of animal
      else {
        $type = null;
        foreach ($zone['meeples'] as $meeple) {
          // Take the first meeple as the zone type
          if ($type == null) {
            $type = $meeple['type'];
            continue;
          }

          if ($meeple['type'] != $type) {
            if ($raiseException) {
              throw new UserException(clienttranslate('A room/pasture/stable contains more than one type of animal'));
            }

            $animals[] = $meeple;
          }
        }
      }
    }

    // Check E33 constraint
    if ($this->player->hasPlayedCard('E33_BeaverColony')) {
      $zones = $this->getAnimalsDropZonesWithAnimals(true);

      // Consider only pastures with stable
      $stabledPastures = [];
      foreach ($zones as $zone) {
        if (!isset($zone['type']) || $zone['type'] !== 'pasture') {
          continue;
        }
        if (empty($zone['stables'])) {
          continue;
        }
        $stabledPastures[] = $zone;
      }

      // If no pasture with stable, nothing to enforce
      if (!empty($stabledPastures)) {
        $conditionMet = false;
        $invalid = [];

        foreach ($stabledPastures as $zone) {
          if (isset($zone['animals']) && $zone['animals'] == 0) {
            $conditionMet = true;
            break;
          }

          if ($invalid == []) {
            foreach ($zone['meeples'] as $meeple) {
              $invalid[] = $meeple;
            }
          }
        }

        if (!$conditionMet) {
          if ($raiseException) {
            throw new UserException(clienttranslate('At least one of your pastures with stable must be empty (Beaver Colony)'));
          }
          $animals = array_merge($invalid, $animals);
        }
      }
    }

    // Check E36 constraint
    if ($this->player->hasPlayedCard('E36_HerbalGarden')) {
      $conditionMet = false;
      $zones = $this->getAnimalsDropZonesWithAnimals(true);
      $invalid = [];

      foreach ($zones as $zone) {
        if (isset($zone['type']) && $zone['type'] != 'pasture') {
          continue;
        }

        if (isset($zone['animals']) && $zone['animals'] == 0) {
          $conditionMet = true;
          break;
        }

        if ($invalid == []) {
          foreach ($zone['meeples'] as $meeple) {
            $invalid[] = $meeple;
          }
        }
      }

      if (!$conditionMet) {
        if ($raiseException) {
          throw new UserException(clienttranslate('At least one of your pastures must be empty (Herbal Garden)'));
        }
        $animals = array_merge($invalid, $animals);
      }
    }

    return $animals;
  }

  public function clearStables($stableIds)
  {
    foreach ($this->stables as $stable) {
      if (in_array($stable['id'], $stableIds)) {
        $this->grid[$stable['x']][$stable['y']] = null;
      }
    }
    Utils::filter($this->stables, function ($stable) use ($stableIds) {
      return !in_array($stable['id'], $stableIds);
    });
    $this->arePasturesUpToDate = false;
  }

  /**************************
   **************************
   ******* ARGS UTILS *******
   **************************
   *************************/

  /**
   * Return all free nodes
   */
  public function getFreeZones($bNotInsidePasture = true)
  {
    $nodes = self::getAllNodes();

    // Should be free and not in a pasture
    $marks = $this->getPasturesMarks();
    Utils::filter($nodes, function ($pos) use ($marks, $bNotInsidePasture) {
      return $this->isFree($pos) && (!$bNotInsidePasture || $marks[$pos['x']][$pos['y']] != INSIDE);
    });

    return $nodes;
  }

  /**
   * Return all nodes that could receive a specific type, ie free and adjacent to existing same type
   * Used for fields and rooms that share similar constraints
   */
  protected function getAdjacentZones($existingNodes)
  {
    $nodes = $this->getFreeZones();

    // Compute adjacent zones to existing fields
    $adjZones = [];
    foreach ($existingNodes as $pos) {
      if (isset($pos['ignore']) && $pos['ignore'] == true) {
        continue;
      }

      foreach (self::getNodesAround($pos) as $zone) {
        $adjZones[] = $zone;
      }
    }
    // If non empty, intersect
    if (!empty($adjZones)) {
      $nodes = Utils::intersectZones($nodes, $adjZones);
    }

    return $nodes;
  }

  /**
   * Return all nodes that could receive a field
   */
  public function getPlowableZones($unrestricted = false)
  {
    if ($unrestricted) {
      return $this->getFreeZones();
    }

    return $this->getAdjacentZones($this->getFieldTiles());
  }

  public function canPlow()
  {
    if($this->player->hasPlayedCard('C17_NewlyPlowedField')){
        $fields=$this->getFieldTiles();
        if(count($fields)==3){
          return !empty($this->getPlowableZones(true));
        }
    }
    return !empty($this->getPlowableZones());
  }

  /**
   * Return all nodes that could receive a room
   */
  public function getBuildableZones()
  {
    return $this->getAdjacentZones($this->rooms);
  }

  public function canConstruct()
  {
    return !empty($this->getBuildableZones());
  }

  /**
   * Return all edges that could receive a fence, ie free and not in-between two buildings
   */
  public function getFencableZones()
  {
    $edges = self::getAllEdges();
    Utils::filter($edges, function ($pos) {
      return !$this->containsFence($pos) && !$this->isSurrounded($pos);
    });
    return $edges;
  }

  /**
   * Return all fields that could receive a crop.
   * Notice: it will return all sowable places, for E80_RockGarden it will return 3 fields.
   */
  public function getSowableFields($reserve = null, $ignoreResources = false, $includeSowActionModifiers = false)
  {
    $reserve = $reserve ?? $this->player->getAllReserveResources();
    $fields = $this->getFieldsAndCrops();

    if ($includeSowActionModifiers) {
      $args = [
        'fields' => $fields,
        'reserve' => $reserve,
        'ignoreResources' => $ignoreResources,
      ];
      PlayerCards::applyEffects($this->player, 'ComputeSowableFields', $args);
      $fields = $args['fields'];
    }

    Utils::filter($fields, function ($field) use ($reserve, $ignoreResources) {
      return empty($field['crops']) &&
        ($ignoreResources || !isset($field['constraints']) || $reserve[$field['constraints']] > 0);
    });
    
    return $fields;
  }

  /**
   * Return all fields that contain a crop
   */
  public function getPlantedFields()
  {
    $fields = $this->getFieldsAndCrops();
    Utils::filter($fields, function ($field) {
      return !empty($field['crops']);
    });
    return $fields;
  }

  /**
   * Return all edges that could receive a fence and adjacent to a field
   */
  public function getAvailableFieldFences()
  {
    $availableEdges = [];
    $fencable = $this->getFencableZones();
    foreach ($this->fields as $field) {
      if ($field['type'] == 'fieldCard') {
        continue;
      }

      $edges = self::getEdgesAround($field['x'], $field['y']);
      foreach ($edges as $edge) {
        if (in_array($edge, $fencable)) {
          $availableEdges[] = $edge;
        }
      }
    }

    $availableEdges = array_unique($availableEdges, SORT_REGULAR);
    // throw new \feException(print_r($availableEdges));
    return $availableEdges;
  }

  /**
   * Return all edges on farm border that could receive a fence.
   */
  public function getAvailableBorderFences()
  {
    $availableEdges = [];
    $fencable = $this->getFencableZones();
    foreach ($fencable as $edge) {
      if ($edge['x'] == 0 || $edge['x'] == 10 || $edge['y'] == 0 || $edge['y'] == 6) {
        $availableEdges[] = $edge;
      }
    }

    $availableEdges = array_unique($availableEdges, SORT_REGULAR);
    // throw new \feException(print_r($availableEdges));
    return $availableEdges;
  }

  public function canSow($reserve = null, $ignoreResources = false, $includeSowActionModifiers = false)
  {
    return !empty($this->getSowableFields($reserve, $ignoreResources, $includeSowActionModifiers));
  }

  /**
   * Return all dropzones for animals
   *  a dropzone is described by a total capacity and an array of correspondings cells/pos/cards
   */
  public function getAnimalsDropZones()
  {
    $zones = [];

    // Add the pastures
    foreach ($this->getPastures() as $pasture) {
      $zones[] = [
        'type' => 'pasture',
        'capacity' => 2 ** (count($pasture['stables']) + 1) * count($pasture['nodes']),
        'locations' => $pasture['nodes'],
        'stables' => $pasture['stables'],
      ];
    }

    // Add the rooms as a single zone
    $zones[] = [
      'type' => 'room',
      'capacity' => 1,
      'locations' => array_map(['AGR\Models\PlayerBoard', 'extractPos'], $this->rooms),
    ];

    // Add the unfenced stables
    $marks = $this->getPasturesMarks();
    foreach ($this->stables as $stable) {
      if ($marks[$stable['x']][$stable['y']] == null) {
        $zones[] = [
          'type' => 'stable',
          'capacity' => 1,
          'locations' => [self::extractPos($stable)],
        ];
      }
    }

    // Apply card effects
    $args['zones'] = $zones;
    PlayerCards::applyEffects($this->player, 'ComputeDropZones', $args);
    return $args['zones'];
  }

  public function getAnimalsDropZonesWithAnimals($includeUnaccomodatedAnimals = false)
  {
    $zones = $this->getAnimalsDropZones();
    $player = $this->player;
    $animals = $player->getAnimalsOnBoard();

    foreach ($zones as &$zone) {
      $zone[SHEEP] = 0;
      $zone[PIG] = 0;
      $zone[CATTLE] = 0;
      $zone['animals'] = 0;
      $zone['meeples'] = [];

      // Find all animals inside that zone
      $meeples = $animals->toAssoc();
      foreach ($meeples as $i => $animal) {
        $pos = $this->extractPos($animal);
        if (
          ($zone['type'] == 'card' && $zone['card_id'] == $animal['location']) || // CARD HOLDER
          ($zone['type'] != 'card' && in_array($pos, $zone['locations']))
        ) {
          $zone[$animal['type']]++;
          $zone['animals']++;
          $zone['meeples'][] = $animal;
          unset($animals[$i]);
        }
      }
    }

    if ($includeUnaccomodatedAnimals) {
      $zones['unaccomodated']['meeples'] = $animals->toArray();
    }

    return $zones;
  }

  // True if every animal in $animals can be placed into this board's zones via some valid arrangement.
  public function canAccommodateAll($animals)
  {
    return self::canAccommodateAllInZones($this->getAnimalsDropZonesWithAnimals(), $animals);
  }

  // Maximum number of animals from $animals that can fit collectively into this board's zones. Used
  // to detect "shortage" cases where multiple babies compete for the same free slots.
  public function countAccommodatable($animals)
  {
    return self::countAccommodatableInZones($this->getAnimalsDropZonesWithAnimals(), $animals);
  }

  private static function canAccommodateAllInZones($zones, $animals)
  {
    if (empty($animals)) {
      return true;
    }
    $type = array_pop($animals);
    foreach ($zones as $i => $zone) {
      if (self::canZoneAcceptType($zone, $type)) {
        $modified = $zones;
        $modified[$i]['animals']++;
        $modified[$i][$type] = ($modified[$i][$type] ?? 0) + 1;
        if (self::canAccommodateAllInZones($modified, $animals)) {
          return true;
        }
      }
    }
    return false;
  }

  private static function countAccommodatableInZones($zones, $animals)
  {
    if (empty($animals)) {
      return 0;
    }
    $best = 0;
    $type = array_pop($animals);
    foreach ($zones as $i => $zone) {
      if (self::canZoneAcceptType($zone, $type)) {
        $modified = $zones;
        $modified[$i]['animals']++;
        $modified[$i][$type] = ($modified[$i][$type] ?? 0) + 1;
        $candidate = 1 + self::countAccommodatableInZones($modified, $animals);
        if ($candidate > $best) {
          $best = $candidate;
        }
      }
    }
    $candidate = self::countAccommodatableInZones($zones, $animals);
    if ($candidate > $best) {
      $best = $candidate;
    }
    return $best;
  }

  // Whether $zone (with its current animal counts) can take one more animal of $type, respecting
  // capacity, type constraints, and any card-specific canAcceptAnimal hook.
  private static function canZoneAcceptType($zone, $type)
  {
    if (($zone['animals'] ?? 0) >= ($zone['capacity'] ?? 0)) {
      return false;
    }

    if (($zone['type'] ?? null) == 'card' && method_exists(PlayerCards::get($zone['card_id']), 'canAcceptAnimal')) {
      return PlayerCards::get($zone['card_id'])->canAcceptAnimal($zone, ['type' => $type]);
    }

    if (isset($zone['constraints'])) {
      return in_array($type, $zone['constraints']);
    }

    // Default fallback for pastures / stables / rooms / Stockyard: one type per zone.
    return ($zone['animals'] ?? 0) == 0 || ($zone[$type] ?? 0) > 0;
  }

  public function countCoveredZonesByPastures()
  {
    $pastures = $this->getPastures();
    $coveredZones = array_reduce(
      $pastures,
      function ($carry, $pasture) {
        return $carry + count($pasture['nodes']);
      },
      0
    );
    return $coveredZones;
  }

  /******************************
   ******************************
   ***** FIELDS/CROPS UTILS *****
   ******************************
   *****************************/
  public function addFieldCard($id, $details)
  {
    $this->fields[$id] = array_merge($details, [
      'uid' => $id,
      'id' => $id,
      'pId' => $this->pId,
      'location' => $id,
      'type' => 'fieldCard',
      'x' => -1,
      'y' => -1,
    ]);

    // D75_WoodField
    if ($id == 'D75_WoodField') {
      $id .= '2';
      $this->fields[$id] = array_merge($details, [
        'uid' => $id,
        'id' => 'D75_WoodField',
        'pId' => $this->pId,
        'location' => 'D75_WoodField',
        'type' => 'fieldCard',
        'x' => 0,
        'y' => -1,
      ]);
    }

    // E80_RockGarden
    if ($id == 'E80_RockGarden') {
      $id .= '2';

      $this->fields[$id] = array_merge($details, [
        'uid' => $id,
        'id' => 'E80_RockGarden',
        'pId' => $this->pId,
        'location' => 'E80_RockGarden',
        'type' => 'fieldCard',
        'x' => 0,
        'y' => -1,
      ]);

      $id = rtrim($id, '2');
      $id .= '3';

      $this->fields[$id] = array_merge($details, [
        'uid' => $id,
        'id' => 'E80_RockGarden',
        'pId' => $this->pId,
        'location' => 'E80_RockGarden',
        'type' => 'fieldCard',
        'x' => -2,
        'y' => -1,
      ]);
    }
  }

  public function getFieldTiles()
  {
    $fields = $this->fields;
    Utils::filter($fields, function ($field) {
      return $field['type'] == 'field';
    });
    return $fields;
  }

  public function getFieldCards()
  {
    $fields = $this->fields;
    Utils::filter($fields, function ($field) {
      return $field['type'] == 'fieldCard';
    });
    return $fields;
  }

  public function getGrowingCrops($keepOnlyThisType = null)
  {
    $fieldCards = array_map(function ($field) {
      return $field['id'];
    }, $this->getFieldCards());
    $crops = Meeples::getGrowingCrops($this->pId, $fieldCards);

    if ($keepOnlyThisType != null) {
      $crops = $crops->filter(function ($crop) use ($keepOnlyThisType) {
        return $crop['type'] == $keepOnlyThisType;
      });
    }

    return $crops;
  }

  /*
   * Notice: it will return all fields not sowable place, for E80_RockGarden it will only return 1 field.
   */
  public function getEmptyFields()
  {
    $fields = [];
    foreach ($this->fields as $field) {
      if (substr($field['uid'], 0, strlen('D75_WoodField')) === 'D75_WoodField') {
      $field['fieldType'] = null;
      $fields['D75_WoodField'] = $field;
      } else if (substr($field['uid'], 0, strlen('E80_RockGarden')) === 'E80_RockGarden') {
        $field['fieldType'] = null;
        $fields['E80_RockGarden'] = $field;
      } else {
        $field['fieldType'] = null;
        $fields[$field['uid']] = $field;
      }
    }

    foreach ($this->getGrowingCrops() as $crop) {
      $uid = ($crop['x'] < 0 || $crop['y'] < 0) ? $crop['location'] : $crop['x'] . '_' . $crop['y'];
      if ($crop['location'] == 'D75_WoodField') {
        $uid = 'D75_WoodField';
      }
      if ($crop['location'] == 'E80_RockGarden') {
        $uid = 'E80_RockGarden';
      }
      $fields[$uid]['fieldType'] = $crop['type'];
    }
    
    Utils::filter($fields, function ($field) {
      return $field['fieldType'] == null;
    });

    // getEmptyFields is only used for counting (C99_GardenDesigner & C33_GreeningPlan),
    // so it is not important for exact fields to be returned. maybe refactor in future.
    if ($this->player->hasPlayedCard('C99_GardenDesigner')) {
      $foodFields = PlayerCards::get('C99_GardenDesigner')->getExtraDatas('foodFields');
      if (count($fields) > $foodFields) {
        $fields = array_slice($fields, 0, count($fields)-$foodFields);
      } else {
        return [];
      }
    }
    return $fields;
  }

  public function getFieldsAndCrops($keepOnlyThisType = null)
  {
    $fields = [];
    foreach ($this->fields as $field) {
      $field['crops'] = [];
      $field['fieldType'] = null;
      $fields[$field['uid']] = $field;
    }

    foreach ($this->getGrowingCrops($keepOnlyThisType) as $crop) {
      $uid = ($crop['x'] < 0 || $crop['y'] < 0) ? $crop['location'] : $crop['x'] . '_' . $crop['y'];

      if ($crop['location'] == 'D75_WoodField' && $crop['x'] == 0) {
        $uid = 'D75_WoodField2';
      }

      if ($crop['location'] == 'E80_RockGarden' && $crop['x'] == 0) {
        $uid = 'E80_RockGarden2';
      }

      if ($crop['location'] == 'E80_RockGarden' && $crop['x'] == -2) {
        $uid = 'E80_RockGarden3';
      }

      if (!isset($fields[$uid])) {
        continue;
      }

      $fields[$uid]['crops'][] = $crop;
      $fields[$uid]['fieldType'] = $crop['type'];
    }

    $args = [
      'fields' => $fields,
      'keepOnlyThisType' => $keepOnlyThisType,
    ];
    PlayerCards::applyEffects($this->player, 'ComputeFieldsAndCrops', $args);
    $fields = $args['fields'];

    if ($keepOnlyThisType != null) {
      Utils::filter($fields, function ($field) use ($keepOnlyThisType) {
        return $field['fieldType'] == $keepOnlyThisType;
      });
    }

    return $fields;
  }

  public function getHarvestCrops($zonesToHarvest = null)
  {
    $extraReap = $this->player->hasPlayedCard('A112_ScytheWorker') ? Globals::getScytheWorkerFields() : [];
    $extraGood = $this->player->hasPlayedCard('D72_StableManure') ? Globals::getStableManureFields() : [];
    $fullReap = $this->player->hasPlayedCard('E73_Scythe') ? Globals::getScytheField() : [];
    $fullReapUids = array_column($fullReap, 'uid');
    $stealReap = [];
    if ($this->player->hasPlayedCard('E112_GrainThief')) {
      foreach (Globals::getGrainThiefFields() as $field) {
        if (in_array($field['uid'], $fullReapUids)) {
          continue;
        }
        $stealReap[] = $field;
      }
      Globals::setGrainThiefFields($stealReap);
    }
    $stealReapUids = array_column($stealReap, 'uid');
    $extraReapUids = array_column($extraReap, 'uid');
    $extraGoodUids = array_column($extraGood, 'uid');

    if ($zonesToHarvest !== null) {
      $fields = array_filter($this->getFieldsAndCrops(), function ($field) use ($zonesToHarvest) {
        return in_array($field['uid'], array_column($zonesToHarvest, 'uid'));
      });
    } else {
      $fields = $this->getFieldsAndCrops();
    }

    $crops = [];
    $zones = [];
    foreach ($fields as $field) {
      $fcrops = $field['crops'];
      $harvestCount = 1;
      if (in_array($field['uid'], $stealReapUids)) {
        $harvestCount--;
      }
      if (in_array($field['uid'], $extraReapUids)) {
        $harvestCount++;
      }
      if (in_array($field['uid'], $extraGoodUids)) {
        $harvestCount++;
      }
      $harvestCount = min($harvestCount, count($fcrops));
      if (in_array($field['uid'], $fullReapUids)) {
        $harvestCount = count($fcrops);
      }
      if (count($fcrops) - $harvestCount == 1) {
        $zones[] = $field['uid'];
      }
      if ($harvestCount > 0) {
        for ($i = 1; $i <= $harvestCount; $i++) {
          $crops[] = $fcrops[count($fcrops) - $i];
          if ($i == count($fcrops)) {
            $crops[count($crops) - 1]['isLastCrop'] = true;
          }
        }
      }
    }
    $harvestedFields = Globals::getHarvestedFields();
    $harvestedFields[$this->pId] = $zones;
    Globals::setHarvestedFields($harvestedFields);
    return $crops;
  }

  /**
   * Return unique board field-tile positions from a harvested crops list.
   * Field cards and temporary non-tile fields are excluded.
   */
  public function getHarvestedFieldTilePositions($crops)
  {
    $fields = [];

    foreach (($crops ?? []) as $crop) {
      $x = $crop['x'] ?? 0;
      $y = $crop['y'] ?? 0;

      if ($x <= 0 || $y <= 0) {
        continue;
      }

      if (!$this->containsField($x, $y)) {
        continue;
      }

      $fields[$x . '_' . $y] = ['x' => $x, 'y' => $y];
    }

    return array_values($fields);
  }

  public function getGrainFields()
  {
    return $this->getFieldsAndCrops(GRAIN);
  }

  public function getVegetableFields()
  {
    return $this->getFieldsAndCrops(VEGETABLE);
  }


  /**
   * Return a grouping key so that multi-slot field cards (Wood Field / Rock Garden)
   * are treated as a single logical field.
   */
  public function getLogicalFieldGroupId(array $field): string
  {
    if (isset($field['uid'])) {
      $uid = $field['uid'];
    } elseif (($field['type'] ?? null) === 'fieldCard') {
      $uid = $field['location'] ?? $field['id'];
    } else {
      $uid = $field['x'] . '_' . $field['y'];
    }

    // Collapse multi-slot field cards to a single group id
    if (strpos($uid, 'D75_WoodField') === 0) {
      return 'D75_WoodField';
    }
    if (strpos($uid, 'E80_RockGarden') === 0) {
      return 'E80_RockGarden';
    }

    return $uid;
  }

  /**
   * Count logical fields (multi-slot field cards count as 1).
   * Includes temporary card-defined fields returned by getFieldsAndCrops()
   * (e.g. Love for Agriculture pastures while sown).
   */
  public function countLogicalFields(): int
  {
    return count($this->groupFields($this->getFieldsAndCrops()));
  }

  /**
   * Group a list of fields (useful for cards like Plant Fertilizer).
   * @return array<string, array> groups keyed by logical field group id
   */
  public function groupFields(array $fields): array
  {
    $groups = [];
    foreach ($fields as $field) {
      $k = $this->getLogicalFieldGroupId($field);
      $groups[$k][] = $field;
    }
    return $groups;
  }

  public function countPlantedLogicalFields(): int
  {
    $groups = $this->groupFields($this->getFieldsAndCrops());
    $count = 0;

    foreach ($groups as $groupSlots) {
      foreach ($groupSlots as $slot) {
        if (!empty($slot['crops'])) {
          $count++;
          break;
        }
      }
    }

    return $count;
  }

  public function countEmptyLogicalFields(): int
  {
    $groups = $this->groupFields($this->getFieldsAndCrops());
    $count = 0;

    foreach ($groups as $groupSlots) {
      $hasCrop = false;
      foreach ($groupSlots as $slot) {
        if (!empty($slot['crops'])) {
          $hasCrop = true;
          break;
        }
      }
      if (!$hasCrop) {
        $count++;
      }
    }

    return $count;
  }


  /******************************
   ******************************
   ******* PASTURES UTILS *******
   ******************************
   *****************************/

  /**
   * Public function to get the list of all pastures
   * @param $forceRecomputation : boolean value to avoid caching
   */
  public function getPastures($forceRecomputation = false)
  {
    if ($this->pastures == null || $forceRecomputation || !$this->arePasturesUpToDate) {
      $this->computePastures();
      $this->arePasturesUpToDate = true;
    }
    return $this->pastures;
  }

  /**
   * Compute and store the set of all pastures
   * WARNING this function presuppose areFencesValid is true
   */
  protected function computePastures()
  {
    $this->pastures = [];
    $visited = [];
    foreach (self::getAllNodes() as $pos) {
      if (isset($visited[$pos['x']][$pos['y']]) || !$this->isFreeOrStable($pos)) {
        continue;
      }
      $fences = $this->getFencesAround($pos);
      if (!isset($fences[W]) || !isset($fences[N])) {
        // The top-left corner of a pasture should have fence on TOP and LEFT
        continue;
      }

      // Run graph exploration
      $queue = [$pos];
      $visited[$pos['x']][$pos['y']] = true;
      $pasture = [
        'nodes' => [],
        'stables' => [],
      ];
      while (!empty($queue)) {
        $pos = array_pop($queue);
        array_push($pasture['nodes'], $pos);
        if ($this->containsStable($pos)) {
          array_push($pasture['stables'], $pos);
        }

        foreach ([W, N, E, S] as $i) {
          if ($this->hasFenceInDirection($pos, $i)) {
            continue;
          }

          $npos = $this->nextNodeInDirection($pos, $i);
          if (!self::isValid($npos) || isset($visited[$npos['x']][$npos['y']])) {
            continue;
          }

          $visited[$npos['x']][$npos['y']] = true;
          array_push($queue, $npos);
        }
      }

      // Check if the pasture is not the outside
      $fenced = true;
      foreach ($pasture['nodes'] as $node) {
        foreach ([W, N, E, S] as $i) {
          if (!$this->hasFenceInDirection($node, $i)) {
            $npos = $this->nextNodeInDirection($node, $i);
            if (!in_array($npos, $pasture['nodes'])) {
              $fenced = false;
              break;
            }
          }
        }
      }

      if ($fenced) {
        array_push($this->pastures, $pasture);
      }
    }
  }

  /**
   * Get nodes for all pastures with $pastureSize = number of farmyard spaces
   */
  public function getPastureZonesBySize($pastureSize)
  {
    $nodes = [];
    $pastures = $this->getPastures();

    foreach ($pastures as $pasture)
      if (count($pasture['nodes']) == $pastureSize) {
        foreach ($pasture['nodes'] as $pos)
          if ($this->isFree($pos)) {
            $nodes[] = $pos;
          }
      }

    return $nodes;
  }

  /**
   * Check wether a player can create a pasture with at most X fences
   * WARNING : this does not ensure the player has enough fences in reserve
   * it's also supposing that current pastures and fences are valid
   * @param int $n : the number of available fences to build a new pasture
   */
  public function canCreateNewPasture($n)
  {
    if ($n == 0) {
      return false;
    }

    $marks = $this->getPasturesMarks();
    $bMustTouchAnotherPasture = !empty($this->getPastures());
    foreach (self::getAllNodes() as $pos) {
      if (!$this->isFreeOrStable($pos)) {
        continue;
      }

      // Is the starting node inside a pasture ?
      $splitPasture = $marks[$pos['x']][$pos['y']] == INSIDE;
      if (($splitPasture || $bMustTouchAnotherPasture) && $this->countFencesAround($pos) == 0) {
        continue; // We cannot create a new pasture in the middle of an existing one, or create a non-adjacent pasture if one already exists
      }

      // Start creating a new pasture starting from $pos
      if ($this->canCreateNewPastureAux($marks, $pos, 0, $n, $splitPasture)) {
        return true;
      }
    }
    return false;
  }

  // Recursive auxiliary function
  protected function canCreateNewPastureAux(&$marks, $pos, $borderSize, $n, $splitPasture)
  {
    // Compute how many fences you need to add the current $pos to work in progress (can be negative !)
    $extraFences = 4 - 2 * $this->countNeighboursAux($pos, $marks) - $this->countFencesAround($pos);
    $borderSize += $extraFences;

    // Do we have anough fences are not in the middle of a pasture ? We happy!
    if ($borderSize <= $n && (!$splitPasture || $this->countFencesAround($pos) != 0)) {
      return true;
    }

    $tmp = $marks[$pos['x']][$pos['y']];
    $marks[$pos['x']][$pos['y']] = CURRENT_WORK;
    foreach ([W, N, E, S] as $dir) {
      if ($this->hasFenceInDirection($pos, $dir)) {
        continue;
      }

      $npos = $this->nextNodeInDirection($pos, $dir);
      if (!self::isValid($npos) || $marks[$npos['x']][$npos['y']] == CURRENT_WORK) {
        continue;
      }

      if ($this->canCreateNewPastureAux($marks, $npos, $borderSize, $n, $splitPasture)) {
        return true;
      }
    }
    $marks[$pos['x']][$pos['y']] = $tmp;
    return false;
  }

  // Auxiliary function to count neighbours in current work recursive function above
  protected function countNeighboursAux(&$pos, &$marks)
  {
    $n = 0;
    foreach ([W, N, E, S] as $dir) {
      $npos = $this->nextNodeInDirection($pos, $dir);
      if (self::isValid($npos) && $marks[$npos['x']][$npos['y']] == CURRENT_WORK) {
        $n++;
      }
    }
    return $n;
  }

  /**
   * Return true if the connected region of nodes containing $start is fully enclosed by fences.
   * Ignores node contents; uses fences as walls.
   */
  protected function isNodeRegionFenced($start)
  {
    // Avoid checkPos/checkNodePos here because they mutate array args by reference.
    if (!is_array($start) || !isset($start['x']) || !isset($start['y'])) {
      throw new \feException('isNodeRegionFenced: invalid start pos');
    }

    $sx = $start['x'];
    $sy = $start['y'];

    // Basic validation: must be a valid node coordinate (odd, odd) inside board
    if (!self::isValid($sx, $sy) || $sx % 2 != 1 || $sy % 2 != 1) {
      throw new \feException('isNodeRegionFenced: start is not a valid node');
    }

    $marks = self::createEmptyGrid(); // null everywhere
    $startPos = ['x' => $sx, 'y' => $sy];

    $queue = [$startPos];
    $marks[$sx][$sy] = INSIDE;

    // Flood fill: cross only unfenced adjacencies
    while (!empty($queue)) {
      $pos = array_pop($queue);

      foreach ([W, N, E, S] as $dir) {
        if ($this->hasFenceInDirection($pos, $dir)) {
          continue;
        }

        $npos = self::nextNodeInDirection($pos, $dir);

        // Open to outside of board
        if (!self::isValid($npos)) {
          return false;
        }

        if ($marks[$npos['x']][$npos['y']] !== null) {
          continue;
        }

        $marks[$npos['x']][$npos['y']] = INSIDE;
        $queue[] = $npos;
      }
    }

    // Leakage check: any unfenced edge from a region node to outside region => not enclosed
    foreach (self::getAllNodes() as $node) {
      if ($marks[$node['x']][$node['y']] !== INSIDE) {
        continue;
      }

      foreach ([W, N, E, S] as $dir) {
        if ($this->hasFenceInDirection($node, $dir)) {
          continue;
        }

        $npos = self::nextNodeInDirection($node, $dir);

        if (!self::isValid($npos)) {
          return false;
        }

        if ($marks[$npos['x']][$npos['y']] !== INSIDE) {
          return false;
        }
      }
    }

    return true;
  }

  /***********************************
   ***********************************
   *********** GRID UTILS ************
   ***********************************
   ***********************************/
  protected static function createEmptyGrid()
  {
    $t = [];
    for ($x = 0; $x <= 10; $x++) {
      for ($y = 0; $y <= 6; $y++) {
        $t[$x][$y] = null;
      }
    }
    return $t;
  }

  protected static function getAllIntersections()
  {
    $result = [];
    for ($x = 0; $x <= 10; $x += 2) {
      for ($y = 0; $y <= 6; $y += 2) {
        $result[] = ['x' => $x, 'y' => $y];
      }
    }
    return $result;
  }

  protected static function getAllNodes()
  {
    $result = [];
    for ($x = 1; $x <= 10; $x += 2) {
      for ($y = 1; $y <= 6; $y += 2) {
        $result[] = ['x' => $x, 'y' => $y];
      }
    }
    return $result;
  }

  protected static function getAllEdges()
  {
    $result = [];
    for ($x = 0; $x <= 10; $x++) {
      $startingY = $x % 2 == 0 ? 1 : 0;
      for ($y = $startingY; $y <= 6; $y += 2) {
        $result[] = ['x' => $x, 'y' => $y];
      }
    }
    return $result;
  }

  /**
   * Return an associative array of 4 intersections around a node
   * @param $x,$y  coordinate of 'node'
   */
  protected static function getIntersections($x, $y = null)
  {
    self::checkPos($x, $y);
    self::checkNodePos($x, $y);

    $result = [];
    foreach ([NW, NE, SW, SE] as $i) {
      $nx = $x + self::$dirs[$i]['x'];
      $ny = $y + self::$dirs[$i]['y'];
      $result[$i] = ['x' => $nx, 'y' => $ny];
    }

    return $result;
  }

  /**
   * Sanity checks
   */
  protected function posToStr($x, $y)
  {
    return '(' . $x . ',' . $y . ')';
  }
  protected static function checkPos(&$x, &$y, $checkValidity = true)
  {
    if ($y === null) {
      $y = $x['y'];
      $x = $x['x'];
    }
    if ($checkValidity && !self::isValid($x, $y)) {
      throw new \feException('Trying to access a position out of bounds :' . self::posToStr($x, $y));
    }
  }

  protected static function isValid($x, $y = null)
  {
    if ($y === null) {
      $y = $x['y'];
      $x = $x['x'];
    }
    return $x >= 0 && $x <= 10 && $y >= 0 && $y <= 6;
  }

  protected static function checkFencePos(&$x, &$y = null)
  {
    self::checkPos($x, $y, false);
    if ($x % 2 == 1 && $y % 2 == 1) {
      throw new \feException('Trying to ask fence of a node :' . self::posToStr($x, $y));
    }

    if ($x % 2 == 0 && $y % 2 == 0) {
      throw new \feException('Trying to ask fence of a virtual intersection :' . self::posToStr($x, $y));
    }
  }

  protected static function checkNodePos(&$x, &$y = null)
  {
    self::checkPos($x, $y);
    if ($x % 2 != 1 || $y % 2 != 1) {
      throw new \feException('Trying to ask node of an edge or a virtual intersection :' . self::posToStr($x, $y));
    }
  }

  public static function extractPos($mixed)
  {
    $r = [
      'x' => $mixed['x'],
      'y' => $mixed['y'],
    ];
    return $r;
  }

  /*****************
   *** NON-STATIC ***
   *****************/
  protected function isFree($x, $y = null)
  {
    self::checkPos($x, $y);
    return is_null($this->grid[$x][$y]);
  }

  protected function isFreeOrStable($x, $y = null)
  {
    self::checkPos($x, $y);
    return is_null($this->grid[$x][$y]) || $this->containsStable($x, $y);
  }

  /**
   * Util function to test if a fence is present at given position
   */
  protected function containsFence($x, $y = null)
  {
    self::checkFencePos($x, $y);
    return $this->grid[$x][$y] != null;
  }

  protected function getNodesKey(array $nodes): string
  {
    $coords = array_map(function ($node) {
      return $node['x'] . '_' . $node['y'];
    }, $nodes);
    sort($coords, SORT_STRING);
    return implode('-', $coords);
  }

  /**
   * Util function to test if a stable is present at given position
   */
  public function containsStable($x, $y = null)
  {
    self::checkNodePos($x, $y);
    return $this->grid[$x][$y] != null && $this->grid[$x][$y]['type'] == 'stable';
  }

  /**
   * Util function to test if a field is present at given position
   */
  public function containsField($x, $y = null)
  {
    self::checkNodePos($x, $y);
    return $this->grid[$x][$y] != null && $this->grid[$x][$y]['type'] == 'field';
  }

  /***********************************
   ***********************************
   *********** DIRS UTILS ************
   ***********************************
   ***********************************/

  // The 8 directions
  protected static $dirs = [
    W => ['x' => -1, 'y' => 0],
    NW => ['x' => -1, 'y' => -1],
    N => ['x' => 0, 'y' => -1],
    NE => ['x' => 1, 'y' => -1],
    E => ['x' => 1, 'y' => 0],
    SE => ['x' => 1, 'y' => 1],
    S => ['x' => 0, 'y' => 1],
    SW => ['x' => -1, 'y' => 1],
  ];

  // The two intersections corresponding to a direction
  protected static $intersectionDirs = [
    N => [NW, NE],
    E => [NE, SE],
    S => [SE, SW],
    W => [NW, SW],
  ];

  /**
   * Return the next cell starting from one cell
   */
  protected static function nextCellInDirection($pos, $dir, $steps = 1)
  {
    return [
      'x' => $pos['x'] + $steps * self::$dirs[$dir]['x'],
      'y' => $pos['y'] + $steps * self::$dirs[$dir]['y'],
    ];
  }

  /**
   * Return the next 'node' starting from one node and going into a direction
   */
  protected static function nextNodeInDirection($pos, $dir)
  {
    // Going two step into the direction to go over the intersection
    //  since we only care about nodes here
    return self::nextCellInDirection($pos, $dir, 2);
  }

  /**
   * Return an associative array with 4 nodes around a node
   * @param $x,$y  coordinate of 'node'
   */
  protected static function getNodesAround($x, $y = null)
  {
    self::checkNodePos($x, $y);
    $result = [];
    foreach ([W, N, E, S] as $dir) {
      $pos = self::nextNodeInDirection(['x' => $x, 'y' => $y], $dir);
      if (self::isValid($pos)) {
        $result[$dir] = $pos;
      }
    }
    return $result;
  }

  /**
   * Return an associative array with 4 edges around a node
   * @param $x,$y  coordinate of 'node'
   */
  protected static function getEdgesAround($x, $y = null)
  {
    self::checkNodePos($x, $y);
    $result = [];
    foreach ([W, N, E, S] as $dir) {
      $result[$dir] = self::nextCellInDirection(['x' => $x, 'y' => $y], $dir);
    }
    return $result;
  }

  /*****************
   *** NON-STATIC ***
   *****************/

  /**
   * Return an associative array with key set only if a fence exists in corresponding direction
   * @param $x,$y  coordinate of 'node'
   */
  protected function getFencesAround($x, $y = null)
  {
    $edges = $this->getEdgesAround($x, $y);
    foreach ([W, N, E, S] as $i) {
      $edge = $edges[$i];
      if ($this->grid[$edge['x']][$edge['y']] == null) {
        unset($edges[$i]);
      }
    }

    return $edges;
  }

  protected function countFencesAround($x, $y = null)
  {
    self::checkNodePos($x, $y);
    return count($this->getFencesAround($x, $y));
  }

  /**
   * Test if a node has a fence in given direction
   * @param $pos : assoc x,y array corresponding to pos
   * @param $dir : direction index (cf self::$dirs)
   */
  protected function hasFenceInDirection($pos, $dir)
  {
    $fences = $this->getFencesAround($pos['x'], $pos['y']);
    return isset($fences[$dir]) && $fences[$dir] != null;
  }

  /**
   * Given a fence position, return true if another fence is connected in given direction
   */
  protected function isConnectedInDirection($fpos, $dir)
  {
    $coeff = in_array($dir, [W, N, E, S]) ? 2 : 1;
    $npos = self::nextCellInDirection($fpos, $dir, $coeff);
    return self::isValid($npos) && $this->containsFence($npos);
  }

  /**
   * Return the set of other fence positions connected (by endpoint) to given fence position
   * @param $fpos : assoc array x,y
   * @return $result : assoc array with start/end which contains the dirs in which there is a fence
   *   start correspond to the start endpoint of a fence (top for a vertical, left for an horizontal)
   * and end correspond to the other endpoint
   */
  protected function getEndpointConnections($x, $y = null)
  {
    self::checkFencePos($x, $y);
    if ($x % 2 == 1) {
      // HORIZONTAL
      $tStart = [W, NW, SW];
      $tEnd = [E, NE, SE];
    } else {
      // VERTICAL
      $tStart = [NW, N, NE];
      $tEnd = [SW, S, SE];
    }

    $fpos = ['x' => $x, 'y' => $y];
    $result = ['start' => [], 'end' => []];
    foreach ($tStart as $dir) {
      if ($this->isConnectedInDirection($fpos, $dir)) {
        $result['start'][] = $dir;
      }
    }
    foreach ($tEnd as $dir) {
      if ($this->isConnectedInDirection($fpos, $dir)) {
        $result['end'][] = $dir;
      }
    }

    return $result;
  }

  /**
   * Return true if an edge is between two non-empty nodes
   * @param $fpos : assoc array x,y
   */
  protected function isSurrounded($x, $y = null)
  {
    self::checkFencePos($x, $y);

    $dirs = $x % 2 == 1 ? [N, S] : [W, E];
    $fpos = ['x' => $x, 'y' => $y];
    foreach ($dirs as $dir) {
      $npos = self::nextCellInDirection($fpos, $dir);
      if (self::isValid($npos) && $this->isFreeOrStable($npos)) {
        return false;
      }
    }

    return true;
  }

  /***********************************
   ***********************************
   ****** GENERIC GRAPH UTILS ********
   ***********************************
   ***********************************/

  /**
   * Create a subgraph of the grids with specified nodes
   */
  protected function getSubgraphMarks($nodes)
  {
    $marks = self::createEmptyGrid();
    foreach ($nodes as $pos) {
      $marks[$pos['x']][$pos['y']] = INSIDE;
    }

    return $marks;
  }

  /**
   * Create a subgraph of the grids with nodes inside pastures
   * @param $pastures : array of pasture => ['nodes' => array of node, 'stables' => array of stables]
   */
  public function getPasturesMarks($pastures = null)
  {
    if ($pastures === null) {
      $pastures = $this->getPastures();
    }

    $nodes = [];
    foreach ($pastures as $pasture) {
      foreach ($pasture['nodes'] as $pos) {
        $nodes[] = $pos;
      }
    }

    return self::getSubgraphMarks($nodes);
  }

  /**
   * Test whether a set of nodes is connex or not
   * @param $marks is a grid with null everywhere expect on nodes of the graph we want to test
   */
  protected function isConnex($marks)
  {
    // Search a starting node
    $pos = null;
    foreach (self::getAllNodes() as $node) {
      if (!is_null($marks[$node['x']][$node['y']])) {
        $pos = $node;
        break;
      }
    }

    if ($pos == null) {
      // If no node, we consider it's connex
      return true;
    }

    // Run a graph exploration from this node
    $queue = [$pos];
    $marks[$pos['x']][$pos['y']] = null;
    while (!empty($queue)) {
      $pos = array_pop($queue);
      foreach ([W, N, E, S] as $i) {
        $npos = $this->nextNodeInDirection($pos, $i);
        if (!self::isValid($npos) || is_null($marks[$npos['x']][$npos['y']])) {
          continue;
        }

        $marks[$npos['x']][$npos['y']] = null;
        array_push($queue, $npos);
      }
    }

    // Now check whether some pasture nodes were still untouched by the graph exploration
    foreach (self::getAllNodes() as $pos) {
      if ($marks[$pos['x']][$pos['y']] == INSIDE) {
        return false;
      }
    }
    return true;
  }

  /*************************
   ****** CARD D148 ********
   ************************/

  /**
   * Return true if an edge is between two rooms
   * @param $fpos : assoc array x,y
   */
  protected function isSurroundedByRooms($x, $y = null)
  {
    self::checkFencePos($x, $y);

    $dirs = $x % 2 == 1 ? [N, S] : [W, E];
    $fpos = ['x' => $x, 'y' => $y];
    $n = 0;
    foreach ($dirs as $dir) {
      $npos = self::nextCellInDirection($fpos, $dir);
      if (self::isValid($npos)) {
        if (is_null($this->grid[$npos['x']][$npos['y']])) {
          return false;
        }
        if (!in_array($this->grid[$npos['x']][$npos['y']]['type'], ['roomWood', 'roomClay', 'roomStone'])) {
          return false;
        }
        $n++;
      }
    }

    return $n == 2;
  }

  /**
   * Return all edges that could receive a fence, ie free and not in-between two buildings
   */
  public function getSurroundedByRoomsEdges()
  {
    $edges = self::getAllEdges();
    Utils::filter($edges, function ($pos) {
      return $this->isSurroundedByRooms($pos);
    });
    return $edges;
  }

  /*************************
   ****** CARD B159 ********
   ************************/

  /**
   * Return true if a node is connected to a field
   * @param $fpos : assoc array x,y
   */
  public function isConnectedToField($x, $y = null)
  {
    foreach (self::getNodesAround($x, $y) as $zone) {
      if (is_null($this->grid[$zone['x']][$zone['y']])) {
        continue;
      }
      if ($this->grid[$zone['x']][$zone['y']]['type'] == 'field') {
        return true;
      }
    }
    return false;
  }

  /**
   * Return true if a node is connected to a certain type of tile
   */
  public function isAdjacentToType($x, $y, $type): bool
  {
    $marks = $this->getPasturesMarks();
    foreach (self::getNodesAround($x, $y) as $zone) {
      if ($type == 'free') {

        if (is_null($this->grid[$zone['x']][$zone['y']]) && $marks[$zone['x']][$zone['y']] != INSIDE) {
          return true;
        }
        continue;
      }
      if ($type == 'pasture') {
        if ($marks[$zone['x']][$zone['y']] == INSIDE) {
          return true;
        }
        continue;
      }
      if ($type == 'pastureField') {
        if ($marks[$zone['x']][$zone['y']] == INSIDE) {
          foreach ($this->getFieldsAndCrops() as $field) {
            if (($field['type'] ?? null) != 'pastureField') {
              continue;
            }
            foreach ($field['locations'] as $loc) {
              if ($loc['x'] == $zone['x'] && $loc['y'] == $zone['y']) {
                return true;
              }
            }
          }
        }
        continue;
      }
      if (is_null($this->grid[$zone['x']][$zone['y']])) {
        continue;
      }
      if ($this->grid[$zone['x']][$zone['y']]['type'] == $type) {
        return true;
      }
    }
    return false;
  }

  /*************************
   ****** CARD D1 ********
   ************************/

  /**
   * Return the locations that D1_ZigzagHarrow can plow
   */

  public function zigzag()
  {
    $zigzag = [];
    foreach (self::getFieldTiles() as $field) {
      $aroundFields = 0;
      $dirs = [];
      $zones = self::getNodesAround($field['x'], $field['y']);
      foreach ([W, N, E, S, W] as $dir) {
        if (!\array_key_exists($dir, $zones)) {
          $aroundFields = 0;
          continue;
        }
        $zone = $zones[$dir];
        if (is_null($this->grid[$zone['x']][$zone['y']])) {
          $aroundFields = 0;
          continue;
        }
        if ($this->grid[$zone['x']][$zone['y']]['type'] == 'field') {
          if ($aroundFields == 1) {
            if (in_array($dir, [N, S])) {
              if (!in_array(['x' => $field['x'] - 2, 'y' => $field['y'] + 2], $zigzag)) {
                $zigzag[] = ['x' => $field['x'] - 2, 'y' => $field['y'] + 2];
              }
              if (!in_array(['x' => $field['x'] + 2, 'y' => $field['y'] - 2], $zigzag)) {
                $zigzag[] = ['x' => $field['x'] + 2, 'y' => $field['y'] - 2];
              }
            } else {

              if (!in_array(['x' => $field['x'] + 2, 'y' => $field['y'] + 2], $zigzag)) {
                $zigzag[] = ['x' => $field['x'] + 2, 'y' => $field['y'] + 2];
              }
              if (!in_array(['x' => $field['x'] - 2, 'y' => $field['y'] - 2], $zigzag)) {
                $zigzag[] = ['x' => $field['x'] - 2, 'y' => $field['y'] - 2];
              }
            }
          }
          $dirs[] = $dir;
          $aroundFields = 1;
          continue;
        }
        $aroundFields = 0;
      }
    }
    return $zigzag;
  }

  /*************************
   ****** B85 Farm Hand ****
   *************************/

  public function hasFarmHandStable()
  {
    return $this->farmHandStable !== null;
  }

    /**
   * Stable count used for cards that say "stables you have".
   * Includes Farm Hand.
   */
  public function countStablesForCards(): int
  {
    return count($this->getStables()) + ($this->hasFarmHandStable() ? 1 : 0);
  }

  public function countUnfencedStablesForCards(): int
  {
    $zones = $this->getAnimalsDropZones();
    Utils::filter($zones, function ($zone) {
      return $zone['type'] == 'stable';
    });

    $count = count($zones);

    // Farm Hand stable is always unfenced by definition
    if ($this->hasFarmHandStable()) {
      $count += 1;
    }

    return $count;
  }

  public function getFarmHandStable()
  {
    return $this->farmHandStable;
  }

  /**
   * Return the top left coordinate of the Farm Hand 2x2 block,
   * or null if there is no Farm Hand stable.
   */
  public function getFarmHandTopLeft()
  {
    if ($this->farmHandStable === null) {
      return null;
    }

    $x = $this->farmHandStable['x'] ?? null;
    $y = $this->farmHandStable['y'] ?? null;

    if ($x === null || $y === null) {
      return null;
    }

    return ['x' => (int) $x, 'y' => (int) $y];
  }

  /**
   * Find all top left coordinates of 2x2 areas of field tiles.
   * Each candidate is ['x' => int, 'y' => int] for the top left field.
   */
  public function getFarmHandCandidates()
  {
    $fields = $this->getFieldTiles();
    $fieldMap = [];

    foreach ($fields as $field) {
      $key = $field['x'] . '_' . $field['y'];
      $fieldMap[$key] = true;
    }

    $candidates = [];

    foreach ($fields as $field) {
      $x = (int) $field['x'];
      $y = (int) $field['y'];

      // check that all four tiles of the 2x2 are fields
      if (
        isset($fieldMap[($x + 2) . '_' . $y]) &&
        isset($fieldMap[$x . '_' . ($y + 2)]) &&
        isset($fieldMap[($x + 2) . '_' . ($y + 2)])
      ) {
        $candidates[] = ['x' => $x, 'y' => $y];
      }
    }

    return $candidates;
  }
}
