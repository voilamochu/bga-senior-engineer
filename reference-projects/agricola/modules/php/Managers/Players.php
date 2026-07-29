<?php
namespace AGR\Managers;
use AGR\Core\Game;
use AGR\Core\Globals;
use AGR\Core\Stats;
use AGR\Helpers\Utils;

/*
 * Players manager : allows to easily access players ...
 *  a player is an instance of Player class
 */
class Players extends \AGR\Helpers\DB_Manager
{
  protected static $table = 'player';
  protected static $primary = 'player_id';
  protected static function cast($row)
  {
    return new \AGR\Models\Player($row);
  }

  public static function setupNewGame($players, $options)
  {
    // Create players
    $gameInfos = Game::get()->getGameinfos();
    $colors = $gameInfos['player_colors'];
    $query = self::DB()->multipleInsert([
      'player_id',
      'player_color',
      'player_canal',
      'player_name',
      'player_avatar',
      'player_score',
    ]);

    $values = [];
    $score = $options[OPTION_SCORING] == OPTION_SCORING_ENABLED ? -14 : 0;
    foreach ($players as $pId => $player) {
      $color = array_shift($colors);
      $values[] = [$pId, $color, $player['player_canal'], $player['player_name'], $player['player_avatar'], $score];
    }
    $query->values($values);
    Game::get()->reattributeColorsBasedOnPreferences($players, $gameInfos['player_colors']);
    Game::get()->reloadPlayersBasicInfos();
  }

  public static function getActiveId()
  {
    return Game::get()->getActivePlayerId();
  }

  public static function getCurrentId()
  {
    return Game::get()->getCurrentPId();
  }

  public static function getAll()
  {
    return self::DB()->get(false);
  }

  /*
   * get : returns the Player object for the given player ID
   */
  public static function get($pId = null)
  {
    $pId = $pId ?: self::getActiveId();
    return self::DB()
      ->where($pId)
      ->getSingle();
  }

  public static function getActive()
  {
    return self::get();
  }

  public static function getCurrent()
  {
    return self::get(self::getCurrentId());
  }

  public static function getNextId($player)
  {
    $pId = is_int($player) ? $player : $player->getId();
    $table = Game::get()->getNextPlayerTable();
    return $table[$pId];
  }

  /*
   * Return the number of players
   */
  public static function count()
  {
    return self::DB()->count();
  }

  public static function countUnallocatedFarmers()
  {
    // Get zombie players ids
    $zombies = self::getAll()
      ->filter(function ($player) {
        return $player->isZombie();
      })
      ->getIds();

    // Filter out farmers of zombies
    return Farmers::getAllAvailable()
      ->filter(function ($meeple) use ($zombies) {
        return !in_array($meeple['pId'], $zombies);
      })
      ->count();
  }

  public static function returnHome()
  {
    foreach (self::getAll() as $player) {
      $player->returnHomeFarmers();
    }
  }

  /*
   * getUiData : get all ui data of all players
   */
  public static function getUiData($pId)
  {
    return self::getAll()->map(function ($player) use ($pId) {
      return $player->jsonSerialize($pId);
    });
  }

  /*
   * Get current turn order according to first player variable
   */
  public static function getTurnOrder($firstPlayer = null)
  {
    $firstPlayer = $firstPlayer ?? Globals::getFirstPlayer();
    $order = [];
    $p = $firstPlayer;
    do {
      $order[] = $p;
      $p = self::getNextId($p);
    } while ($p != $firstPlayer);
    return $order;
  }

  /**
   * Get current turn order for harvest by removing skipped players
   */
  public static function getHarvestTurnOrder($firstPlayer = null, $key = null)
  {
    $order = self::getTurnOrder($firstPlayer);
    $skipped = Globals::getSkipHarvest() ?? [];
    if ($key == 'harvestField' || $key == 'harvestBreed') {
      $skipFieldandBreed = Globals::getSkipFieldAndBreed() ?? [];
      $skipped = array_values(array_unique(array_merge($skipped, $skipFieldandBreed)));
    }

    Utils::filter($order, function ($pId) use ($skipped) {
      return !in_array($pId, $skipped);
    });
    return $order;
  }
}
