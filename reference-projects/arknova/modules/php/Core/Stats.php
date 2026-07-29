<?php

namespace ARK\Core;

use ARK\Managers\Players;
use ARK\Helpers\Collection;

/*
 * Statistics
 */

class Stats extends \ARK\Helpers\CachedDB_Manager
{
  protected static string $table = 'stats';
  protected static string $primary = 'stats_id';
  protected static ?Collection $datas = null;
  protected static function cast(array $row): array
  {
    return [
      'id' => $row['stats_id'],
      'type' => $row['stats_type'],
      'pId' => $row['stats_player_id'],
      'value' => $row['stats_value'],
    ];
  }

  /*
   * Create and store a stat declared but not present in DB yet
   *  (only happens when adding stats while a game is running)
   */
  public static function checkExistence()
  {
    $default = [
      'int' => 0,
      'float' => 0,
      'bool' => false,
      'str' => '',
    ];

    // Fetch existing stats, all stats
    $stats = Game::get()->getStatTypes();
    $existingStats = self::getAll()
      ->map(function ($stat) {
        return $stat['type'] . ',' . ($stat['pId'] == null ? 'table' : 'player');
      })
      ->toArray();

    $values = [];
    // Deal with table stats first
    foreach ($stats['table'] as $stat) {
      if ($stat['id'] < 10) {
        continue;
      }
      if (!in_array($stat['id'] . ',table', $existingStats)) {
        $values[] = [
          'stats_type' => $stat['id'],
          'stats_player_id' => null,
          'stats_value' => $default[$stat['type']],
        ];
      }
    }

    // Deal with player stats
    $playerIds = Players::getAll()->getIds();
    foreach ($stats['player'] as $stat) {
      if ($stat['id'] < 10) {
        continue;
      }
      if (!in_array($stat['id'] . ',player', $existingStats)) {
        foreach ($playerIds as $i => $pId) {
          $value = $default[$stat['type']];
          if ($stat['id'] == STAT_POSITION) {
            $value = $i + 1;
          }
          if ($stat['id'] == STAT_ASSOCIATION_WORKERS) {
            $value = 1;
          }
          if ($stat['id'] == STAT_REPUTATION) {
            $value = 1;
          }

          $values[] = [
            'stats_type' => $stat['id'],
            'stats_player_id' => $pId,
            'stats_value' => $value,
          ];
        }
      }
    }

    // Insert if needed
    if (!empty($values)) {
      self::DB()
        ->multipleInsert(['stats_type', 'stats_player_id', 'stats_value'])
        ->values($values);
      self::invalidate();
    }
  }

  protected static function getValue($id, $pId)
  {
    $entry = self::getAll()
      ->filter(function ($stat) use ($id, $pId) {
        return $stat['type'] == $id &&
          ((is_null($pId) && is_null($stat['pId'])) || (!is_null($pId) && $stat['pId'] == (is_int($pId) ? $pId : $pId->getId())));
      })
      ->first();

    if (is_null($entry)) {
      $fPId = is_null($pId) ? "" : (is_int($pId) ? $pId : $pId->getId());
      throw new \InvalidArgumentException("Unexistent stat {$id}:{$fPId}, please report");
    }

    return $entry['value'];
  }

  protected static function getFilteredQuery($id, $pId)
  {
    $query = self::DB()->where('stats_type', $id);
    if (is_null($pId)) {
      $query = $query->whereNull('stats_player_id');
    } else {
      $query = $query->where('stats_player_id', is_int($pId) ? $pId : $pId->getId());
    }
    return $query;
  }

  /*
   * Magic method that intercept not defined static method and do the appropriate stuff
   */
  public static function __callStatic($method, $args)
  {
    if (preg_match('/^([gs]et|inc)([A-Z])(.*)$/', $method, $match)) {
      $stats = Game::get()->getStatTypes();

      // Sanity check : does the name correspond to a declared variable ?
      $name = mb_strtolower($match[2]) . $match[3];
      $isTableStat = \array_key_exists($name, $stats['table']);
      $isPlayerStat = \array_key_exists($name, $stats['player']);
      if (!$isTableStat && !$isPlayerStat) {
        throw new \InvalidArgumentException("Statistic {$name} doesn't exist");
      }

      if ($match[1] == 'get') {
        // Basic getters
        $id = null;
        $pId = null;
        if ($isTableStat) {
          $id = $stats['table'][$name]['id'];
        } else {
          if (empty($args)) {
            throw new \InvalidArgumentException("You need to specify the player for the stat {$name}");
          }
          $id = $stats['player'][$name]['id'];
          $pId = $args[0];
        }

        return self::getValue($id, $pId);
      } elseif ($match[1] == 'set') {
        // Setters in DB and update cache
        $id = null;
        $pId = null;
        $value = null;

        if ($isTableStat) {
          $id = $stats['table'][$name]['id'];
          $value = $args[0];
        } else {
          if (count($args) < 2) {
            throw new \InvalidArgumentException("You need to specify the player for the stat {$name}");
          }
          $id = $stats['player'][$name]['id'];
          $pId = $args[0];
          $value = $args[1];
        }

        self::getFilteredQuery($id, $pId)
          ->update(['stats_value' => $value])
          ->run();
        self::invalidate();
        return $value;
      } elseif ($match[1] == 'inc') {
        $id = null;
        $pId = null;
        $value = null;

        if ($isTableStat) {
          $id = $stats['table'][$name]['id'];
          $value = $args[0] ?? 1;
        } else {
          if (count($args) < 1) {
            throw new \InvalidArgumentException("You need to specify the player for the stat {$name}");
          }
          $id = $stats['player'][$name]['id'];
          $pId = $args[0];
          $value = $args[1] ?? 1;
        }

        self::getFilteredQuery($id, $pId)
          ->inc(['stats_value' => $value])
          ->run();
        self::invalidate();
        return $value;
      }
    }
    return null;
  }

  protected static function getLabels()
  {
    $labels = [
      clienttranslate('Number of breaks'),

      clienttranslate('First player'),
      clienttranslate('Second player'),
      clienttranslate('Third player'),
      clienttranslate('Fourth player'),

      clienttranslate('Map A'),
      clienttranslate('Map 0'),
      clienttranslate('Map 1: Observation Tower'),
      clienttranslate('Map 2: Outdoor Areas'),
      clienttranslate('Map 3: Silver Lake'),
      clienttranslate('Map 4: Commercial Harbor'),
      clienttranslate('Map 5: Park Restaurant'),
      clienttranslate('Map 6: Research Institute'),
      clienttranslate('Map 7: Ice Cream Parlors'),
      clienttranslate('Map 8: Hollywood Hills'),
      clienttranslate('Map 9: Geographical Zoo'),
      clienttranslate('Map 10: Rescue Station'),
      clienttranslate('Map 11: Caves'),
      clienttranslate('Map 12: Artificial Intelligence'),
      clienttranslate('Map 13: Drawing Board'),
      clienttranslate('Map 14: Lagoon'),
      clienttranslate('Map T1: Tournament 1'),

      clienttranslate('Map 1a: Observation Tower'),
      clienttranslate('Map 2a: Outdoor Areas'),
      clienttranslate('Map 3a: Silver Lake'),
      clienttranslate('Map 4a: Commercial Harbor'),
      clienttranslate('Map 5a: Park Restaurant'),
      clienttranslate('Map 6a: Research Institute'),
      clienttranslate('Map 7a: Ice Cream Parlors'),
      clienttranslate('Map 8a: Hollywood Hills'),

      clienttranslate('No'),
      clienttranslate('Yes'),

      clienttranslate('Animals 1'),
      clienttranslate('Animals 2'),
      clienttranslate('Animals 3'),
      clienttranslate('Animals 4'),
      clienttranslate('Build 1'),
      clienttranslate('Build 2'),
      clienttranslate('Build 3'),
      clienttranslate('Build 4'),
      clienttranslate('Sponsors 1'),
      clienttranslate('Sponsors 2'),
      clienttranslate('Sponsors 3'),
      clienttranslate('Sponsors 4'),
      clienttranslate('Association 1'),
      clienttranslate('Association 2'),
      clienttranslate('Association 3'),
      clienttranslate('Association 4'),
      clienttranslate('Cards 1'),
      clienttranslate('Cards 2'),
      clienttranslate('Cards 3'),
      clienttranslate('Cards 4'),
    ];
  }

  public static function getCardUid($actionCardType)
  {
    $map = [
      'Animals1' => 10,
      'Animals2' => 11,
      'Animals3' => 12,
      'Animals4' => 13,

      'Build1' => 20,
      'Build2' => 21,
      'Build3' => 22,
      'Build4' => 23,

      'Association1' => 30,
      'Association2' => 31,
      'Association3' => 32,
      'Association4' => 33,

      'Cards1' => 40,
      'Cards2' => 41,
      'Cards3' => 42,
      'Cards4' => 43,

      'Sponsors1' => 50,
      'Sponsors2' => 51,
      'Sponsors3' => 52,
      'Sponsors4' => 53,
    ];

    return $map[$actionCardType];
  }
}
