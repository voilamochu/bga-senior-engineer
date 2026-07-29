<?php

namespace ARK\Managers;

use ARK\Core\Game;
use ARK\Core\Globals;
use ARK\Core\Stats;
use ARK\Helpers\Utils;
use ARK\Core\Notifications;
use ARK\Models\Player;
use ARK\Helpers\Collection;

/*
 * Players manager : allows to easily access players ...
 *  a player is an instance of Player class
 */

class Players extends \ARK\Helpers\CachedDB_Manager
{
  protected static string $table = 'player';
  protected static string $primary = 'player_id';
  protected static ?Collection $datas = null;
  protected static function cast($row): Player
  {
    return new \ARK\Models\Player($row);
  }

  public static function setupNewGame($players, $options)
  {
    // Map is not determined at first unless first game/beginner mode
    $map = '';
    if (Globals::isFirstGame()) {
      $map = 'A';
    } elseif (Globals::isBeginner()) {
      $map = '0';
    } elseif (Globals::getSameMap() != 0) {
      $map = Globals::getSameMap();
    }

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
      'money',
      'reputation',
      'appeal',
      'conservation',
      'xtoken',
      'map_id',
    ]);

    $values = [];

    $appeal = 0;
    if (Globals::isSolo()) {
      $appealMap = [
        OPTION_SOLO_DIFFICULTY_BEGINNER => 20,
        OPTION_SOLO_DIFFICULTY_NORMAL => 10,
        OPTION_SOLO_DIFFICULTY_HARD => 0,
      ];
      $appeal = $appealMap[$options[\OPTION_SOLO_DIFFICULTY] ?? OPTION_SOLO_DIFFICULTY_BEGINNER] ?? 20;
    }

    foreach ($players as $pId => $player) {
      $color = array_shift($colors);
      $values[] = [
        $pId,
        $color,
        $player['player_canal'],
        $player['player_name'],
        $player['player_avatar'],
        -114 + $appeal,
        25,
        1,
        $appeal++,
        0,
        0,
        $map,
      ];
    }
    $query->values($values);
    self::invalidate();
    Game::get()->reattributeColorsBasedOnPreferences($players, $gameInfos['player_colors']);
    Game::get()->reloadPlayersBasicInfos();
  }

  public static function setupNextGame()
  {
    $player = self::getActive();
    $player->setMapId('');
    $player->setScore(-114 + Globals::getSoloAppeal());
    $player->setMoney(25);
    $player->setReputation(1);
    $player->setAppeal(Globals::getSoloAppeal());
    $player->setConservation(0);
    $player->setXToken(0);
  }

  public static function getActiveId(): int
  {
    return (int) Game::get()->getActivePlayerId();
  }

  public static function getCurrentId(): int
  {
    return (int) Game::get()->getCurrentPId();
  }

  public static function getActive(): Player
  {
    return self::get(self::getActiveId());
  }

  public static function getCurrent(): Player
  {
    return self::get(self::getCurrentId());
  }

  public static function get($id = null): ?Player
  {
    return parent::get($id ?? self::getActiveId());
  }

  public static function getNextId($player): int
  {
    $pId = is_int($player) ? $player : $player->getId();
    $table = Game::get()->getNextPlayerTable();
    return $table[$pId];
  }

  public static function getNext($player): Player
  {
    return self::get(self::getNextId($player));
  }

  public static function getPrevious($player): Player
  {
    $table = Game::get()->getPrevPlayerTable();
    $pId = (int) $table[$player->getId()];
    return self::get($pId);
  }

  /*
   * Return the number of players
   */
  public static function count(): int
  {
    return self::getAll()->count();
  }

  /*
   * getUiData : get all ui data of all players
   */
  public static function getUiData($pId)
  {
    return self::getAll()
      ->map(function ($player) use ($pId) {
        return $player->getUiData($pId);
      })
      ->toAssoc();
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

  public static function checkEndOfGamePlayer($player)
  {
    if (Globals::getEndRemainingPlayers() != []) {
      return true;
    }
    if (Globals::isSolo()) {
      return false;
    }
    if (Players::getActiveId() != $player->getId()) {
      return false;
    }

    $score = $player->updateScore();
    if ($score >= 100 && !Globals::isEndTriggered()) {
      Notifications::endOfGame($player);
      Globals::setEndRemainingPlayers(self::getAll()->getIds());
      Globals::setEndTriggered(true);
      Stats::incEndGameTriggered($player);
      return true;
    }

    return false;
  }
}
