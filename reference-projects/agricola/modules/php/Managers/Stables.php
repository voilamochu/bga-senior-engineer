<?php
namespace AGR\Managers;

use AGR\Core\Notifications;
use AGR\Managers\Meeples;
use AGR\Managers\Players;

/* Class to manage all the stables for Agricola */

class Stables extends Meeples
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
      ->where('type', 'stable');

    if ($location != null) {
      $query = $query->where('meeple_location', $location);
    }

    return $query;
  }

  /**
   * Do we have an available stable for an action
   * @return boolean true if has one available, else false
   * @param number $pId Id of player
   */
  public static function hasAvailable($pId)
  {
    return self::qPlayer($pId, 'reserve')->count() > 0;
  }

  /**
   * Provide first available stable
   * @param number $pId
   * @return array meeple
   */
  public static function getNextAvailable($pId)
  {
    return self::qPlayer($pId, 'reserve')->getSingle();
  }

  /**
   * move stable token. Take the next available stable
   * @param number $pId
   * @param varchar $location place on which we put the card
   * @param array $coord X & Y position
   * CAUTION : don't use 'move' as it's already taken by parent
   **/
  public static function moveNextAvailable($pId, $location, $coords = null)
  {
    $stable = self::getNextAvailable($pId);
    if ($stable == null) {
      throw new \feException('No more available stable');
    }

    parent::moveToCoords($stable['id'], $location, $coords);
    return $stable['id'];
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

  public static function countAvailable($pId)
  {
    return self::qPlayer($pId)
      ->where('meeple_location', 'reserve')
      ->count();
  }

  /**
   * Provide placed stables
   * @param number $pId
   * @return array meeples
   */
  public static function getOnBoard($pId)
  {
    return self::qPlayer($pId, 'board')->get();
  }

  /*************************
   ****** B85 Farm Hand ****
   *************************/

  public static function getFarmHandStable($pId)
  {
    return self::qPlayer($pId, 'farmhand')->getSingle();
  }

  public static function hasFarmHandStable($pId)
  {
    return self::qPlayer($pId, 'farmhand')->count() > 0;
  }

  /**
   * Create a Farm Hand stable by moving the next available stable
   * from reserve to the special "farmhand" location at (x, y).
   */
  public static function createFarmHandStable($pId, int $x, int $y): void
  {
    $stable = self::qPlayer($pId, 'reserve')->getSingle();

    if ($stable === null) {
      throw new \BgaUserException(
        clienttranslate('You do not have any stables left in reserve for Farm Hand.')
      );
    }

    $stableId = (int) $stable['id'];

    self::moveToCoords($stableId, 'farmhand', ['x' => $x, 'y' => $y]);
    $player = Players::get($pId);
    Notifications::farmHandStable($player, ['x' => $x, 'y' => $y], $stableId);
  }

  public static function removeFarmHandStable($player)
  {
    $stable = self::getFarmHandStable($player->getId());
    if ($stable === null) {
      return null;
    }

    $topLeft = $player->board()->getFarmHandTopLeft();

    // Move in DB
    $stable = Meeples::returnToReserve($player, $stable);

    // Animate the Farm Hand overlay (board -> reserve)
    if ($topLeft !== null) {
      Notifications::farmHandStableReturned($player, $topLeft, $stable);
    }

    // Standard message + counter update, but without any sliding animation in the panel
    $stable['type'] = 'stable';
    $stable['location'] = 'reserve';

    // Prevent addMeeple() spawning on the board
    unset($stable['x'], $stable['y']);

    // tell client "create/update counters, but don't slide" as we handle animation separately
    $stable['noPanelSlide'] = true;

    Notifications::returnToReserve($player, [$stable]);

    return $stable;
  }
}
