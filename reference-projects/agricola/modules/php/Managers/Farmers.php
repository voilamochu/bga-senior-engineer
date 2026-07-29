<?php
namespace AGR\Managers;

use AGR\Core\Globals;
use AGR\Core\Notifications;

/* Class to manage all the famers for Agricola */

class Farmers extends Meeples
{
  /* Creation of various meeples */
  public static function setupNewGame($players, $options)
  {
    $meeples = [];
    foreach ($players as $pId => $player) {
      // Farmers in reserve
      $meeples[] = ['type' => 'farmer', 'player_id' => $pId, 'location' => 'reserve', 'state' => SUPPLY, 'nbr' => 3];
      // Farmers in position
      $meeples[] = [
        'type' => 'farmer',
        'player_id' => $pId,
        'location' => 'board',
        'x' => 1,
        'y' => 3,
        'nbr' => 1,
      ];
      $meeples[] = [
        'type' => 'farmer',
        'player_id' => $pId,
        'location' => 'board',
        'x' => 1,
        'y' => 5,
        'nbr' => 1,
      ];
    }

    self::create($meeples);
  }

  /* Partial query for a given player */
  public static function qPlayer($pId, $location = null)
  {
    $query = self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('type', 'farmer');

    if ($location != null) {
      $query = $query->where('meeple_location', $location);
    }

    return $query;
  }

  /**
   * Get all the farmers not in reserve
   * @return Collection of farmers
   * @param number $pId Id of player
   */
  public static function getAllOfPlayer($pId)
  {
    return self::qPlayer($pId, null)
      ->where('meeple_location', '<>', 'reserve')
      ->get();
  }

  /**
   * Do we have an available farmer for an action?
   * @return boolean true if has one available, else false
   * @param number $pId Id of player
   */
  public static function hasAvailable($pId)
  {
     return self::qPlayer($pId, 'board')->count()
      + (self::qPlayer($pId, 'A10_WoodenShed')->count() > 0)
      + (self::qPlayer($pId, 'A127_Lodger')->count() > 0)
      + (self::qPlayer($pId, 'B10_Caravan')->count() > 0)
      + (self::qPlayer($pId, 'C85_DenBuilder')->count() > 0)
      + (self::qPlayer($pId, 'D85_Reader')->count() > 0);
  }

  public static function getAllAvailable($pId = null, $fromSupply = false)
  {
    if ($fromSupply) {
      return self::qPlayer($pId, 'reserve')->get();
    }

    // Farmers living on cards come first so placement empties cards before house rooms,
    // keeping the remaining-people count easier to read at a glance
    return self::qPlayer($pId, 'A10_WoodenShed')->get()
      ->merge(self::qPlayer($pId, 'A127_Lodger')->get())
      ->merge(self::qPlayer($pId, 'B10_Caravan')->get())
      ->merge(self::qPlayer($pId, 'C85_DenBuilder')->get())
      ->merge(self::qPlayer($pId, 'D85_Reader')->get())
      ->merge(self::qPlayer($pId, 'E85_MasterTanner')->get())
      ->merge(self::qPlayer($pId, 'board')->get());
  }

  public static function getNextChildren($pId)
  {
    return self::qPlayer($pId, null)
      ->where('meeple_location', '<>', 'reserve')
      ->where('meeple_state', CHILD)
      ->get();
  }

  public static function getChildren($pId)
  {
    return self::qPlayer($pId, null)
      ->where('meeple_location', '<>', 'board')
      ->where('meeple_state', CHILD)
      ->get();
  }

  public static function hasChildren($pId)
  {
    return self::qPlayer($pId, null)
      ->where('meeple_location', '<>', 'reserve')
      ->where('meeple_state', CHILD)
      ->count() > 0;
  }

  public static function getTemporary($pId = null)
  {
    return self::qPlayer($pId, null)
      ->where('meeple_state', TEMP)
      ->get();
  }

  /**
   * Provide first available farmer
   * @param number $pId
   * @return array meeple
   */
  public static function getNextAvailable($pId, $fromSupply = false)
  {
    // return self::qPlayer($pId, 'board')->getSingle();
    return self::getAllAvailable($pId, $fromSupply)->first();
  }

  /**
   * move Farmer token. Take the next available farmer
   * @param number $pId
   * @param varchar $location place on which we put the card
   * @param array $coord X & Y position
   * CAUTION : don't use 'move' as it's already taken by parent
   **/
  public static function moveNextAvailable($pId, $location, $coords = null, $fromSupply = false)
  {
    // if (Globals::getAdoptiveChild() != 0) {
    //   $farmer = self::get(Globals::getAdoptiveChild());
    //   Globals::setAdoptiveChild(0);
    // } else {
    $farmer = self::getNextAvailable($pId, $fromSupply);
    // }

    if ($farmer == null) {
      throw new \feException('No more available person');
    }

    parent::moveToCoords($farmer['id'], $location, $coords);
    return $farmer['id'];
  }

  /**
   * @param number $pId
   * @return int number of farmers
   **/
  public static function count($pId, $type = null, $excludeTemp = true)
  {
    $query = self::qPlayer($pId)
      ->where('meeple_location', '<>', 'reserve')
      ->where('meeple_location', 'NOT LIKE', 'turn%')
      ->where('meeple_state', '<>', REMOVE);

    if ($excludeTemp) {
      $query = $query->where('meeple_state', '<>', TEMP);
    }

    if ($type !== null) {
      $query = $query->where('meeple_state', $type);
    }

    return $query->count();
  }

  public static function getFarmers($pId, $type = null, $excludeTemp = true)
  {
    $query = self::qPlayer($pId)
      ->where('meeple_location', '<>', 'reserve')
      ->where('meeple_location', 'NOT LIKE', 'turn%')
      ->where('meeple_state', '<>', REMOVE);

    if ($excludeTemp) {
      $query = $query->where('meeple_state', '<>', TEMP);
    }

    if ($type !== null) {
      $query = $query->where('meeple_state', $type);
    }

    return $query->get();
  }

  /**
   *
   * @param number $pId
   * @return boolean true if it's possible
   */
  public static function hasInReserve($pId)
  {
    return self::qPlayer($pId)
      ->where('meeple_location', 'reserve')
      ->count() > 0;
  }

  public static function hasMultipleInReserve($pId)
  {
    return self::qPlayer($pId)
      ->where('meeple_location', 'reserve')
      ->count() > 1;
  }

  /**
   *
   * @param number $pId
   * @return a meeple
   */
  public static function getNextInReserve($pId)
  {
    return self::qPlayer($pId, 'reserve')->getSingle();
  }

  /**
   * Return all farmers on a card
   * @param number $cId card Id
   * @param number $pId (opt)
   */
  public static function getOnCard($cId, $pId = null)
  {
    return parent::getOnCardQ($cId, $pId)
      ->where('type', 'farmer')
      ->get();
  }

  public static function getNonChildrenOnCard($cId, $pId = null)
  {
    return parent::getOnCardQ($cId, $pId)
      ->where('type', 'farmer')
      ->where('meeple_state', '<>', CHILD)
      ->get();
  }

  /**
   * Get all children and make them grown-up
   */
  public static function growChildren()
  {
    $children = self::qPlayer(null)
      ->where('meeple_state', CHILD)
      ->get()
      ->getIds();
    foreach ($children as $cId) {
      self::setState($cId, ADULT);
    }
    return $children;
  }

  /**
   * Grow one child
   */
  public static function growOneChild($pId)
  {
    $children = self::qPlayer($pId)
      ->where('meeple_state', CHILD)
      ->limit(1)
      ->get()
      ->getIds();
    foreach ($children as $cId) {
      self::setState($cId, ADULT);
    }

    return $children;
  }

  public static function setFarmerState($farmer, $state)
  {
    $farmer['state'] = $state;

    self::DB()->update(
      [
        'meeple_state' => $state,
      ],
      $farmer['id'],
    );
  }

  public static function cleanupJobContractFake()
  {
    $fakeId = Globals::getJobContractFake();
    if (empty($fakeId)) {
      return;
    }

    // Fake is a meeple row; fetch without throwing if already gone
    $meeples = Meeples::getMany($fakeId, false);
    if ($meeples->count() > 0) {
      $deleted = Meeples::deleteResources($meeples);
      Notifications::silentDestroy($deleted);
    }

    Globals::setJobContractFake(null);
  }
}
