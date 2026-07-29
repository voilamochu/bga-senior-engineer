<?php
namespace AGR\Managers;

use agricola;
use AGR\Core\Globals;
use AGR\Core\Stats;
use AGR\Core\Game;
use AGR\Core\Notifications;
use AGR\Helpers\UserException;
use AGR\Helpers\QueryBuilder;

/* Class to manage all the meeples for Agricola */

class Meeples extends \AGR\Helpers\Pieces
{
  protected static $table = 'meeples';
  protected static $prefix = 'meeple_';
  protected static $customFields = ['type', 'player_id', 'x', 'y'];

  protected static function cast($meeple)
  {
    return [
      'id' => (int) $meeple['id'],
      'location' => $meeple['location'],
      'pId' => $meeple['player_id'],
      'type' => $meeple['type'],
      'x' => $meeple['x'],
      'y' => $meeple['y'],
      'state' => $meeple['state'],
    ];
  }

  public static $renovationCost = [
    'roomWood' => ['multiple' => CLAY, 'once' => REED, 'next' => 'roomClay'],
    'roomClay' => ['multiple' => STONE, 'once' => REED, 'next' => 'roomStone'],
  ];

  public static function getUiData()
  {
    // Base query as before
    $meeples = self::getSelectQuery()
      ->orderBy('meeple_state')
      ->orderBy('type') // Ensure fields are created before grain and vegetable that might be on them
      ->get()
      ->toArray();

    // B85 Farm Hand:
    // Hide special "farmhand" stables from the client.
    // They are exposed to the UI via PlayerBoard::getUiData() instead.
    $meeples = array_values(array_filter($meeples, function ($m) {
      return !(
        $m['type'] === 'stable'
        && $m['location'] === 'farmhand'
      );
    }));

    // E149_MidnightFencer: attach colorPid to stolen fence meeples
    $colors = Globals::getFenceColorPids();
    if (!empty($colors)) {
      foreach ($meeples as &$m) {
        if ($m['type'] === 'fence') {
          $id = $m['id'] ?? null;
          if ($id !== null && isset($colors[$id])) {
            $m['colorPid'] = $colors[$id];
          }
        }
      }
      unset($m);
    }

    return $meeples;
  }

  /* Creation of various meeples */
  public static function setupNewGame($players, $options)
  {
    $player_order = agricola::get()->getNextPlayerTable();

    // 1st player has 2 food
    // other players have 3 foods
    // Snake opening: start player also has 3 food (multiplayer only)
    $meeples = [];

    $startPlayerFood = 2;
    if (
      count($players) > 1
      && (($options[OPTION_SNAKE_OPENING] ?? OPTION_SNAKE_OPENING_DISABLED) == OPTION_SNAKE_OPENING_ENABLED)
    ) {
      $startPlayerFood = 3;
    }

    if (count($players) > 1) {
      $meeples[] = ['type' => FOOD, 'player_id' => $player_order[0], 'location' => 'reserve', 'nbr' => $startPlayerFood];
    }

    $meeples[] = ['type' => 'firstPlayer', 'player_id' => $player_order[0], 'location' => 'reserve', 'nbr' => 1];

    foreach ($players as $player_id => $player) {
      if ($player_id !== $player_order[0]) {
        $meeples[] = ['type' => FOOD, 'player_id' => $player_id, 'location' => 'reserve', 'nbr' => 3];
      }

      // rooms
      $meeples[] = [
        'type' => 'roomWood',
        'player_id' => $player_id,
        'location' => 'board',
        'x' => 1,
        'y' => 3,
        'nbr' => 1,
      ];
      $meeples[] = [
        'type' => 'roomWood',
        'player_id' => $player_id,
        'location' => 'board',
        'x' => 1,
        'y' => 5,
        'nbr' => 1,
      ];

      // fence
      $meeples[] = ['type' => 'fence', 'player_id' => $player_id, 'location' => 'reserve', 'nbr' => 15];
      // stables
      $meeples[] = ['type' => 'stable', 'player_id' => $player_id, 'location' => 'reserve', 'nbr' => 4];
    }

    self::create($meeples);

    Farmers::setupNewGame($players, $options);
  }

  /**
   * move meeple token to coords
   * @param number $mId meeple id
   * @param varchar $location place on which we put the meeple
   * @param array $coord X & Y position
   **/
  public static function moveToCoords($mId, $location, $coord = null)
  {
    $meeple = self::get($mId);

    $x = null;
    $y = null;

    if (is_array($coord) && isset($coord['x']) && isset($coord['y'])) {
      $x = $coord['x'];
      $y = $coord['y'];
    } elseif (is_array($coord) && count($coord) == 2) {
      $x = $coord[0];
      $y = $coord[1];
    } elseif (is_array($coord)) {
      $x = $coord[0];
    } elseif ($coord != null) {
      $x = $coord;
    }

    self::DB()->update(
      [
        'meeple_location' => $location,
        'x' => $x,
        'y' => $y,
      ],
      $mId
    );

    if (self::shouldTrackFarmyardGoodPlacement($meeple['type'], $meeple['pId'], $location, $x, $y, $meeple['location'])) {
      self::markFarmyardGoodPlacement($meeple['pId']);
    }
  }

  protected static function isFarmyardWorkerGoodType($type)
  {
    return in_array($type, [FOOD, WOOD, CLAY, REED, STONE, GRAIN, VEGETABLE, SHEEP, PIG, CATTLE], true);
  }

  protected static function isFarmyardNode($location, $x, $y)
  {
    if ($location !== 'board' || is_null($x) || is_null($y)) {
      return false;
    }

    return $x > 0 && $x <= 10 && $x % 2 == 1 && $y > 0 && $y <= 6 && $y % 2 == 1;
  }

  protected static function hasAnimalTypeOnBoard($pId, $type)
  {
    if (!in_array($type, ANIMALS, true)) {
      return false;
    }

    return self::getFilteredQuery($pId, 'board', $type)->count() > 0;
  }

  protected static function canPlaceAnimalOnBoardNow($pId, $type)
  {
    if (!in_array($type, ANIMALS, true)) {
      return false;
    }

    $player = Players::get($pId);
    $zones = $player->board()->getAnimalsDropZonesWithAnimals();

    foreach ($zones as $zone) {
      if (!is_array($zone) || !isset($zone['type']) || $zone['type'] === 'card') {
        continue;
      }

      $constraints = $zone['constraints'] ?? ANIMALS;
      if (!in_array($type, $constraints, true)) {
        continue;
      }

      $animals = $zone['animals'] ?? 0;
      $capacity = $zone['capacity'] ?? 0;
      if ($animals >= $capacity) {
        continue;
      }

      $occupiedTypes = 0;
      foreach (ANIMALS as $aType) {
        if (($zone[$aType] ?? 0) > 0) {
          $occupiedTypes++;
        }
      }

      if ($occupiedTypes <= 1 && ($animals == 0 || ($zone[$type] ?? 0) > 0)) {
        return true;
      }
    }

    return false;
  }

  protected static function shouldTrackFarmyardGoodPlacement($type, $pId, $location, $x, $y, $fromLocation = null)
  {
    if (!Globals::isWorkPhase()) {
      return false;
    }

    if ($pId <= 0 || !self::isFarmyardWorkerGoodType($type)) {
      return false;
    }

    if (self::isFarmyardNode($location, $x, $y)) {
      // "Moving an existing good" should not count.
      return $fromLocation !== 'board';
    }

    // Animal gained to reserve should still count if it could have been placed on the farmyard,
    // including the case where the same animal type is already on the board (newborn/cooking logic).
    if (
      in_array($type, ANIMALS, true) &&
      $location === 'reserve' &&
      $fromLocation !== 'board' &&
      (self::canPlaceAnimalOnBoardNow($pId, $type) || self::hasAnimalTypeOnBoard($pId, $type))
    ) {
      return true;
    }

    return false;
  }

  public static function markFarmyardGoodPlacement($pId)
  {
    $flags = Globals::getFarmyardGoodsPlacedDuringWork() ?? [];
    $flags[$pId] = true;
    Globals::setFarmyardGoodsPlacedDuringWork($flags);
  }

  /**
   * Generic base query
   */
  public static function getFilteredQuery($pId, $location, $type)
  {
    $query = self::getSelectQuery()->wherePlayer($pId);
    if ($location != null) {
      $query = $query->where('meeple_location', $location);
    }
    if ($type != null) {
      if (is_array($type)) {
        $query = $query->whereIn('type', $type);
      } else {
        $query = $query->where('type', strpos($type, '%') === false ? '=' : 'LIKE', $type);
      }
    }
    return $query;
  }

  /**************************** Animals *****************************************/
  public static function getAnimals($pId, $location = null)
  {
    return self::getFilteredQuery($pId, $location, [SHEEP, PIG, CATTLE])->get();
  }

  public static function countAnimalsInZoneLocation($pId, $location = null)
  {
    return self::getFilteredQuery($pId, 'board', [SHEEP, PIG, CATTLE])
      ->where('x', $location['x'])
      ->where('y', $location['y'])
      ->count();
  }

  public static function countAnimalsInZoneCard($pId, $location = null)
  {
    return self::getFilteredQuery($pId, $location['card_id'], [SHEEP, PIG, CATTLE])->count();
  }

  /**************************** Field *****************************************/
  public static function getFields($pId)
  {
    return self::getFilteredQuery($pId, 'board', 'field')->get();
  }

  /**************************** Rooms *****************************************/
  protected static function getRoomsQ($pId)
  {
    return self::getFilteredQuery($pId, 'board', 'room%');
  }

  public static function getRooms($pId)
  {
    return self::getRoomsQ($pId)->get();
  }

  // countRooms
  public static function countRooms($pId)
  {
    return self::getRoomsQ($pId)->count();
  }

  /**
   *
   * Provides the type of room constructed
   * @param number $player_id
   * @return string rommType (roomWood, roomClay, roomStone)
   */
  public static function getRoomType($pId)
  {
    $roomsType = array_unique(
      self::getRooms($pId)
        ->map(function ($token) {
          return $token['type'];
        })
        ->toArray()
    );

    if (count($roomsType) != 1) {
      throw new \feException('multiple Room type, should not happen');
    }
    return $roomsType[0];
  }

  /*************************** Resource management ***********************/
  protected static function getResourcesInUsePriority($resources, $resourceType, $preferIds = null)
  {
    $ordered = $resources instanceof \AGR\Helpers\Collection ? $resources->toAssoc() : $resources;

    if ($resourceType == PIG) {
      // Mud Wallower pigs are non-fungible and kept for last when spending generically.
      $regular = [];
      $mudWallower = [];
      foreach ($ordered as $id => $resource) {
        if (($resource['location'] ?? null) == 'C148_MudWallower') {
          $mudWallower[$id] = $resource;
        } else {
          $regular[$id] = $resource;
        }
      }
      $ordered = $regular + $mudWallower;
    }

    // A caller may pin specific meeples to be spent first — e.g. Boar Spear converts the exact
    // pig it just gained, even when that pig sits on Mud Wallower (which is otherwise last).
    if (!empty($preferIds)) {
      $preferred = [];
      foreach ($preferIds as $pid) {
        if (isset($ordered[$pid])) {
          $preferred[$pid] = $ordered[$pid];
          unset($ordered[$pid]);
        }
      }
      $ordered = $preferred + $ordered;
    }

    return $ordered;
  }

  public static function useResource($player_id, $resourceType, $amount, $preferIds = null)
  {
    $deleted = [];
    if ($amount == 0) {
      return [];
    }

    // $resource = self::getReserveResource($player_id, $resourceType);
    $resource = self::getResourceOfType($player_id, $resourceType);

    if (count($resource) < $amount) {
      throw new UserException(sprintf(clienttranslate('You do not have enough %s'), $resourceType));
    }

    foreach (self::getResourcesInUsePriority($resource, $resourceType, $preferIds) as $res) {
      $deleted[] = $res;
      self::DB()->delete($res['id']);
      $amount--;
      if ($amount == 0) {
        break;
      }
    }

    if (in_array($resourceType, ['farmer', 'fence', 'stable'], true)) {
      Notifications::updatePersonalOwned($player_id, self::getPersonalOwned($player_id));
    }

    return $deleted;
  }

  public static function useReserveResource($player_id, $resourceType, $amount)
  {
    $deleted = [];
    if ($amount == 0) {
      return [];
    }

    $resource = self::getReserveResource($player_id, $resourceType);

    if (count($resource) < $amount) {
      throw new UserException(sprintf(clienttranslate('You do not have enough %s'), $resourceType));
    }

    foreach ($resource as $id => $res) {
      $deleted[] = $res;
      self::DB()->delete($id);
      $amount--;
      if ($amount == 0) {
        break;
      }
    }

    return $deleted;
  }

  public static function deleteResources($meeples)
  {
    $deleted = [];

    foreach ($meeples as $meeple) {
      $deleted[] = $meeple;
      self::DB()->delete($meeple['id']);
    }

    $affected = [];
    foreach ($meeples as $meeple) {
      if (in_array($meeple['type'], ['farmer', 'fence', 'stable'], true)) {
        $affected[$meeple['pId']] = true;
      }
    }

    foreach (array_keys($affected) as $pId) {
      Notifications::updatePersonalOwned($pId, self::getPersonalOwned($pId));
    }

    return $deleted;
  }

  public static function payResourceTo($player_id, $resourceType, $amount, $otherPlayer)
  {
    $moved = [];
    if ($amount == 0) {
      return [];
    }

    // $resource = self::getReserveResource($player_id, $resourceType);
    $resource = self::getResourceOfType($player_id, $resourceType);

    if (count($resource) < $amount) {
      throw new UserException(sprintf(clienttranslate('You do not have enough %s'), $resourceType));
    }

    foreach (self::getResourcesInUsePriority($resource, $resourceType) as $res) {
      self::DB()->update(
        [
          'player_id' => $otherPlayer,
          'meeple_location' => 'reserve',
        ],
        $res['id']
      );
      $res['pId'] = $otherPlayer;
      $moved[] = $res;
      // self::DB()->delete($id);
      $amount--;
      if ($amount == 0) {
        break;
      }
    }
    return $moved;
  }

  public static function createResourceInLocation($type, $location, $player_id, $x, $y, $nbr = 1, $state = null)
  {
    $meeples = [
      [
        'type' => $type,
        'player_id' => $player_id,
        'location' => $location,
        'x' => $x,
        'y' => $y,
        'nbr' => $nbr,
        'state' => $state,
      ],
    ];

    $ids = self::create($meeples);
    self::updateMaxima();

    if (self::shouldTrackFarmyardGoodPlacement($type, $player_id, $location, $x, $y)) {
      self::markFarmyardGoodPlacement($player_id);
    }

    return $ids;
  }

  public static function collectClayFromSpace($player, $x, $y)
  {
    $meeples = self::getSelectQuery()
      ->wherePlayer($player->getId())
      ->where([['type', CLAY], ['x', $x],['y', $y]])
      ->get();

    $meeplesToCollect = [];
    foreach ($meeples as $meeple) {
      self::DB()->update(
        [
          'player_id' => $player->getId(),
          'meeple_location' => 'reserve',
        ],
        $meeple['id']
      );

      // Update for possible upcoming notifications
      $meeple['location'] = 'reserve';
      $meeple['pId'] = $player->getId();

      $meeplesToCollect[] = $meeple;
    }
    return $meeplesToCollect;
  }

  public static function createResourceOnCard($type, $location, $nbr = 1, $state = null)
  {
    return self::createResourceInLocation($type, $location, 0, null, null, $nbr, $state);
  }

  // Default function to create a resource in reserve
  public static function createResourceInReserve($pId, $type, $nbr = 1)
  {
    return self::createResourceInLocation($type, 'reserve', $pId, null, null, $nbr);
  }

  public static function getOnCardQ($cId, $pId = null, $type = null)
  {
    return self::getFilteredQuery($pId, $cId, $type);
  }

  public static function getResourcesOnCard($cId, $pId = null, $type = null)
  {
    return self::getOnCardQ($cId, $pId, $type)
      ->where('type', '<>', 'farmer')
      ->orderBy('state', 'asc')
      ->get();
  }

  public static function collectResourcesOnCard($player, $cId, $pId = null)
  {
    // collect all resources on the card
    $resources = self::getResourcesOnCard($cId, $pId);
    foreach ($resources as $id => &$res) {
      self::DB()->update(
        [
          'player_id' => $player->getId(),
          'meeple_location' => 'reserve',
        ],
        $id
      );

      // Update for possible upcoming notifications
      $res['location'] = 'reserve';
      $res['pId'] = $player->getId();
    }

    return $resources->toArray();
  }

  public static function receiveResource($player, &$meeple)
  {
    self::DB()->update(
      [
        'player_id' => $player->getId(),
        'meeple_location' => 'reserve',
      ],
      $meeple['id']
    );
    $meeple = self::get($meeple['id']);

    return $meeple;
  }

  public static function returnToReserve($player, &$meeple)
  {
    self::DB()->update(
      [
        'player_id' => $player->getId(),
        'meeple_location' => 'reserve',
      ],
      $meeple['id']
    );
    $meeple = self::get($meeple['id']);
    return $meeple;
  }

  /**
   * Return seeds on fields
   */
  public static function getGrowingCrops($pId, $fieldCards = [])
  {
    $type = [VEGETABLE, GRAIN, STONE, WOOD];
    $locations = array_merge($fieldCards, ['board']);
    return self::getSelectQuery()
      ->wherePlayer($pId)
      ->whereIn('type', $type)
      ->whereIn('meeple_location', $locations)
      ->get();
  }

  public static function collectFirstPlayerToken($pId)
  {
    $tokenId = self::getSelectQuery()
      ->where('type', 'firstPlayer')
      ->getSingle()['id'];

    self::DB()->update(['player_id' => $pId], $tokenId);
    return $tokenId;
  }

  /************************ Utility functions **********************/

  /**
   * Check if cell is adjacent
   * @param $x X coordinate of the new block
   * @param $y Y coordinate of the new block
   * @param $posX X coordinate existing block
   * @param $posY Y coordinate existing block
   * @return true if adjacent
   *
   **/
  public static function isAdjacent($x, $y, $posX, $posY)
  {
    if (abs($x - $posX) == 1 && abs($y - $poxY) == 0) {
      return true;
    } elseif (abs($x - $posX) == 0 && abs($y - $posY) == 1) {
      return true;
    }

    return false;
  }

  public static function getReserveResource($pId, $type = null)
  {
    $query = self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('meeple_location', 'reserve');

    if ($type != null) {
      $query = $query->where('type', $type);
    }
    return $query->get();
  }

  public static function getResourceOfType($pId, $type)
  {
    $query = self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('type', $type)
      ->where('meeple_location', 'NOT LIKE', 'turn_%')
      ->orderBy('meeple_location', 'DESC');

    return $query->get();

    /*
TODO : smart choice for animals
public static function getNextSheep()
{
  // Try to find a sheep on the player board
  $zones = $this->getPlayer()->board()->getAnimalsDropZonesWithAnimals();

  // Sort rooms first, then stable, then pasture
  usort($zones, function ($a, $b) {
    $map = [
      'D148_special' => 3,
      'room' => 2,
      'stable' => 1,
      'pasture' => 0,
    ];
    return $map[$b['type']] - $map[$a['type']];
  });

  foreach ($zones as &$zone) {
    if ($zone[SHEEP] > 0) {
      foreach ($zone['meeples'] as $meeple) {
        if ($meeple['type'] == SHEEP) {
          return $meeple;
        }
      }
    }
  }

  return null;
}
*/
  }

  public static function countReserveResource($pId, $type = null)
  {
    return self::getReserveResource($pId, $type)->count();
  }

  public static function countAllResource($pId, $type)
  {
    $query = self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('type', $type);

    return $query->get()->count();
  }


  public static function updateMaxima()
  {
    $types = [WOOD, CLAY, REED, STONE, GRAIN, VEGETABLE, SHEEP, PIG, CATTLE];
    foreach($types as $type){
      $name = "get".ucfirst($type).'Max';
      $v = Stats::$name();
      $c = self::getFilteredQuery(null, null, $type)->count();
      if($c > $v){
        $name = "set".ucfirst($type).'Max';
        Stats::$name($c);
      }
    }
  }

  public static function fixMeepleType($meeple)
  {
    $type = $meeple['type'];
    if (substr($type, -4) == 'plus') {
      $type = str_replace('plus', '', $type);
    }

    self::DB()->update(
      [
        'type' => $type,
      ],
      $meeple['id'],
    );
  }

  public static function getPersonalOwned($pId)
  {
    $fenceTotal = self::countAllResource($pId, 'fence');
    $woodPalisades = self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('type', 'fence')
      ->where('meeple_state', FENCE_STATE_WOOD_PALISADE)
      ->count();

    // Exclude REMOVE-state farmers (e.g. Job Contract's fake on Lessons): they
    // are slated for deletion at returnHome and shouldn't count toward the cap.
    $farmers = self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('type', 'farmer')
      ->where('meeple_state', '<>', REMOVE)
      ->count();

    return [
      'farmer' => $farmers,
      'fence'  => max(0, $fenceTotal - $woodPalisades),
      'stable' => self::countAllResource($pId, 'stable'),
    ];
  }

}
