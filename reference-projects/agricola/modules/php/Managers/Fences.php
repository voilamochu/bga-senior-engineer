<?php
namespace AGR\Managers;

/* Class to manage all the famers for Agricola */

class Fences extends Meeples
{
  /* Creation of various meeples */
  public static function setupNewGame($players, $options)
  {
  }

  /* Partial query for a given player */
  public static function qPlayer($pId, $location = null)
  {
    $query = self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('type', 'fence');

    if ($location != null) {
      $query = $query->where('meeple_location', $location);
    }

    return $query;
  }

  protected static function isWoodPalisadeState($state)
  {
    return $state == FENCE_STATE_WOOD_PALISADE;
  }

  public static function isWoodPalisade($fence)
  {
    return self::isWoodPalisadeState($fence['state'] ?? 0);
  }

  protected static function countWoodPalisades($pId, $location = null)
  {
    return self::qPlayer($pId, $location)
      ->where('meeple_state', FENCE_STATE_WOOD_PALISADE)
      ->count();
  }

  /**
   * Do we have an available fence for an action
   * @return boolean true if has one available, else false
   * @param number $pId Id of player
   */
  public static function hasAvailable($pId, $location = 'reserve')
  {
    return self::countAvailable($pId, $location) > 0;
  }

  /**
   * Provide first available fence
   * @param number $pId
   * @return array meeple
   */
  public static function getNextAvailable($pId, $location = 'reserve')
  {
    foreach (self::qPlayer($pId, $location)->get() as $fence) {
      if (!self::isWoodPalisade($fence)) {
        return $fence;
      }
    }

    return null;
  }

  /**
   * move Fence token. Take the next available fence
   * @param number $pId
   * @param varchar $location place on which we put the card
   * @param array $coord X & Y position
   * CAUTION : don't use 'move' as it's already taken by parent
   **/
  public static function moveNextAvailable($pId, $location, $coords = null, $fromLocation = 'reserve')
  {
    $fence = self::getNextAvailable($pId, $fromLocation);
    if ($fence == null) {
      throw new \feException('No more available fence from ' . $fromLocation);
    }

    parent::moveToCoords($fence['id'], $location, $coords);
    return $fence['id'];
  }

  /**
   * @param number $pId
   * @return int number of farmers
   **/
  public static function count($pId)
  {
    return self::qPlayer($pId)
      ->where('meeple_location', '<>', 'reserve')
      ->count();
  }

  public static function countAvailable($pId, $location = 'reserve')
  {
    $total = self::qPlayer($pId)
      ->where('meeple_location', $location)
      ->count();

    return max(0, $total - self::countWoodPalisades($pId, $location));
  }

  public static function calculateMinimumPastureFences($pid)
  {
    //TODO: Timothée, parcours de graphe? :)
    return 4;
  }

  /**
   * Provide placed fences
   * @param number $pId
   * @return array meeples
   */
  public static function getOnBoard($pId)
  {
    return self::qPlayer($pId, 'board')->get();
  }
}