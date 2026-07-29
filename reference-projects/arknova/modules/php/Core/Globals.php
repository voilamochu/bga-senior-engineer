<?php

namespace ARK\Core;

use ARK\Core\Game;
use ARK\Helpers\Utils;

/*
 * Globals
 */

class Globals extends \ARK\Helpers\DB_Manager
{
  protected static $initialized = false;
  protected static $variables = [
    'engine' => 'obj', // DO NOT MODIFY, USED IN ENGINE MODULE
    'engineChoices' => 'int', // DO NOT MODIFY, USED IN ENGINE MODULE
    'callbackEngineResolved' => 'obj', // DO NOT MODIFY, USED IN ENGINE MODULE
    'anytimeRecursion' => 'int', // DO NOT MODIFY, USED IN ENGINE MODULE
    'customTurnOrders' => 'obj', // DO NOT MODIFY, USED FOR CUSTOM TURN ORDER FEATURE

    'firstPlayer' => 'int',

    // Setup
    'possibleMaps' => 'obj', // For each player, the list of possible map to play
    'initialMapSelection' => 'obj',
    'initialActionCardsSelection' => 'obj',
    'initialSelection' => 'obj',
    'bonusTiles' => 'obj',
    'soloChallenge' => 'int',
    'soloChallengeData' => 'obj',
    'soloScore' => 'int',
    'actionCardsDraft' => 'obj',
    'actionCardsDraftRound' => 'int',

    // Break related
    'break' => 'int',
    'maxBreak' => 'int',
    'mustBreak' => 'bool',
    'breakDiscardSelection' => 'obj',
    'breakPlayer' => 'int',

    'bonusTiles' => 'obj', // Bonus tiles on scoreboard

    // Abilities related
    'venomTriggered' => 'bool',
    'usedVenom' => 'bool',
    'venomPaid' => 'bool',
    'effectPerception' => 'obj',
    'effectHunter' => 'obj',
    'effectScavenging' => 'obj',
    'effectResistance' => 'obj',
    'effectHypnosis' => 'int',
    'effectMap4' => 'bool',
    'effectAssertion' => 'obj',
    'effectCamouflage' => 'int',
    'effectScubaDive' => 'obj',
    'landscapeGardener' => 'bool',
    'mapT1Used' => 'bool',
    'activeT1Effect' => 'bool',
    'activeActionCard' => 'obj',
    'sponsors2Gained' => 'bool',

    // Game options
    'solo' => 'bool',
    'peaceful' => 'bool',
    'firstGame' => 'bool',
    'beginner' => 'bool',
    'freeSetup' => 'bool',
    'noBeginner' => 'bool',
    'soloAppeal' => 'int',
    'sameMap' => 'str',
    'mapPack' => 'bool',
    'mapPack2' => 'bool',
    'marineWorld' => 'bool',
    'alternativeMaps' => 'bool',

    // end of game
    'endRemainingPlayers' => 'obj',
    'endFinalScoringDone' => 'bool',
    'endTriggered' => 'bool',
    'end' => 'bool',
  ];

  protected static string $table = 'global_variables';
  protected static string $primary = 'name';
  protected static function cast(array $row): mixed
  {
    $val = json_decode(\stripslashes($row['value']), true);
    if (!isset(self::$variables[$row['name']])) return $val;
    return self::$variables[$row['name']] == 'int' ? ((int) $val) : $val;
  }

  /*
   * Fetch all existings variables from DB
   */
  protected static $data = [];
  public static function fetch()
  {
    // Turn of LOG to avoid infinite loop (Globals::isLogging() calling itself for fetching)
    $tmp = self::$log;
    self::$log = false;

    foreach (
      self::DB()
        ->select(['value', 'name'])
        ->get(false)
      as $name => $variable
    ) {
      if (\array_key_exists($name, self::$variables)) {
        self::$data[$name] = $variable;
      }
    }
    self::$initialized = true;
    self::$log = $tmp;
  }

  /*
   * Create and store a global variable declared in this file but not present in DB yet
   *  (only happens when adding globals while a game is running)
   */
  public static function create($name)
  {
    if (!\array_key_exists($name, self::$variables)) {
      return;
    }

    $default = [
      'int' => 0,
      'obj' => [],
      'bool' => false,
      'str' => '',
    ];
    $val = $default[self::$variables[$name]];
    self::DB()->insert(
      [
        'name' => $name,
        'value' => \json_encode($val),
      ],
      true
    );
    self::$data[$name] = $val;
  }

  public static function isBreak()
  {
    return !is_null(self::getBreakPlayer()) && self::getBreakPlayer() != -1;
  }

  /*
   * Magic method that intercept not defined static method and do the appropriate stuff
   */
  public static function __callStatic($method, $args)
  {
    if (!self::$initialized) {
      self::fetch();
    }

    if (preg_match('/^([gs]et|inc|is)([A-Z])(.*)$/', $method, $match)) {
      // Sanity check : does the name correspond to a declared variable ?
      $name = mb_strtolower($match[2]) . $match[3];
      if (!\array_key_exists($name, self::$variables)) {
        throw new \InvalidArgumentException("Property {$name} doesn't exist");
      }

      // Create in DB if don't exist yet
      if (!\array_key_exists($name, self::$data)) {
        self::create($name);
      }

      if ($match[1] == 'get') {
        // Basic getters
        return self::$data[$name];
      } elseif ($match[1] == 'is') {
        // Boolean getter
        if (self::$variables[$name] != 'bool') {
          throw new \InvalidArgumentException("Property {$name} is not of type bool");
        }
        return (bool) self::$data[$name];
      } elseif ($match[1] == 'set') {
        // Setters in DB and update cache
        $value = $args[0];
        if (self::$variables[$name] == 'int') {
          $value = (int) $value;
        }
        if (self::$variables[$name] == 'bool') {
          $value = (bool) $value;
        }

        self::$data[$name] = $value;
        self::DB()->update(['value' => \addslashes(\json_encode($value))], $name);
        return $value;
      } elseif ($match[1] == 'inc') {
        if (self::$variables[$name] != 'int') {
          throw new \InvalidArgumentException("Trying to increase {$name} which is not an int");
        }

        $getter = 'get' . $match[2] . $match[3];
        $setter = 'set' . $match[2] . $match[3];
        return self::$setter(self::$getter() + (empty($args) ? 1 : $args[0]));
      }
    }
    throw new \feException(print_r(debug_print_backtrace()));
    return null;
  }

  public static function getAvailableMapsIds(): array
  {
    if (self::isFirstGame()) {
      return ['A'];
    }
    if (self::isBeginner()) {
      return [0];
    }

    $mapPack1 = self::isMapPack();
    $mapPack2 = self::isMapPack2();
    $removeBeginner = self::isNoBeginner();
    $alternativeMaps = self::isAlternativeMaps();

    $maps = $removeBeginner ? [1, 2, 3, 4, 5, 6, 7, 8] : ['A', 0, 1, 2, 3, 4, 5, 6, 7, 8];

    // MAP PACK 1
    if ($mapPack1) {
      $maps[] = 9;
      $maps[] = 10;
    }

    // MAP PACK 2
    if ($mapPack2) {
      $maps[] = 11;
      $maps[] = 12;
      $maps[] = 13;
      $maps[] = 14;
      $maps[] = 'T1';
    }

    // ALT MAPS
    if ($alternativeMaps) {
      foreach ($maps as &$map) {
        if (in_array($map, ADVANCED_MAPS)) {
          $map = $map . 'a';
        }
      }
    }

    return $maps;
  }

  /*
   * Setup new game
   */
  public static function setupNewGame($players, $options, $newChallenge = false)
  {
    // All this is only executed during table creation, not when starting a new game within the challenge
    if ($newChallenge === false) {
      self::setMapPack(($options[\OPTION_MAP_PACK] ?? OPTION_MAP_PACK_NO) == \OPTION_MAP_PACK_YES);
      self::setMapPack2(($options[\OPTION_MAP_PACK2] ?? OPTION_MAP_PACK_NO) == \OPTION_MAP_PACK_YES);
      self::setAlternativeMaps(($options[\OPTION_ALTERNATIVE_MAPS] ?? OPTION_ALTERNATIVE_MAPS_NO) == \OPTION_ALTERNATIVE_MAPS_YES);
      self::setSolo(count($players) == 1);
      self::setPeaceful(count($players) == 1 || ($options[\OPTION_PEACEFUL_MODE] ?? null) == \OPTION_PEACEFUL_MODE_ENABLED);
      self::setBeginner($options[OPTION_COMPETITIVE_LEVEL] == OPTION_COMPETITIVE_BEGINNER);
      self::setFirstGame($options[OPTION_COMPETITIVE_LEVEL] == \OPTION_COMPETITIVE_FIRST_GAME);
      self::setFreeSetup(
        ($options[OPTION_COMPETITIVE_LEVEL] == \OPTION_COMPETITIVE_CUSTOM_SETUP)
          || ($options[OPTION_COMPETITIVE_LEVEL] == \OPTION_COMPETITIVE_CUSTOM_SETUP_NON_BEGINNER)
      );

      $map = $options[\OPTION_SAME_MAP_MODE] ?? OPTION_SAME_MAP_RANDOM;
      // NO Beginner maps
      self::setNoBeginner(
        ($options[OPTION_COMPETITIVE_LEVEL] == \OPTION_COMPETITIVE_CUSTOM_SETUP_NON_BEGINNER)
          || ($options[OPTION_COMPETITIVE_LEVEL] == OPTION_COMPETITIVE_NORMAL)
          || ($options[\OPTION_COMPETITIVE_LEVEL] == \OPTION_COMPETITIVE_ALL_SAME_SETUP && $map == OPTION_SAME_MAP_RANDOM)
      );

      // Same map option
      if ($options[\OPTION_COMPETITIVE_LEVEL] == \OPTION_COMPETITIVE_ALL_SAME_SETUP) {
        if ($map == OPTION_SAME_MAP_RANDOM) {
          $maps = self::getAvailableMapsIds();
          $i = array_rand($maps, 1);
          $map = $maps[$i];
        } else {
          if ($map == 100) $map = 'T1';

          if (self::isAlternativeMaps() && in_array($map, ADVANCED_MAPS)) {
            $map = $map . 'a';
          }
        }
      }
      self::setSameMap($map);
      self::setMarineWorld(($options[OPTION_MARINE_WORLD] ?? OPTION_MARINE_WORLD_NO) == OPTION_MARINE_WORLD_YES);

      // Solo starting appeal
      self::setSoloAppeal(0);
      if (count($players) == 1) {
        $appealMap = [
          OPTION_SOLO_DIFFICULTY_BEGINNER => 20,
          OPTION_SOLO_DIFFICULTY_NORMAL => 10,
          OPTION_SOLO_DIFFICULTY_HARD => 0,
        ];
        self::setSoloAppeal($appealMap[$options[\OPTION_SOLO_DIFFICULTY] ?? OPTION_SOLO_DIFFICULTY_BEGINNER]);

        if (($options[OPTION_SOLO_CHALLENGE] ?? OPTION_CHALLENGE_NO) == OPTION_CHALLENGE_YES) {
          self::setSoloChallenge(3);
        }
      }
      Globals::setSoloScore(-999);
    }

    // Break
    $maxBreaks = [1 => 0, 2 => 9, 3 => 12, 4 => 15];
    self::setBreak(0);
    self::setMaxBreak($maxBreaks[count($players)]);
    self::setMustBreak(false);
    self::setBreakPlayer(-1);

    self::setEndRemainingPlayers([]);
    self::setEndTriggered(false);
    self::setEndFinalScoringDone(false);

    // Abilities
    self::setEffectHypnosis(0);
    self::setUsedVenom(false);
    self::setEffectMap4(false);

    // BONUSES
    $bonuses = [];
    // Constant Bonus Tiles on score board
    $bonuses[2][] = ['permanent' => true, 'bonus' => [BONUS_UPGRADE_CARD => 1]];
    $bonuses[2][] = ['permanent' => true, 'bonus' => [BONUS_WORKER => 1]];
    $bonuses[5][] = ['permanent' => true, 'bonus' => [MONEY => 5]];
    $bonuses[8][] = ['permanent' => true, 'bonus' => [MONEY => 5]];
    $bonuses[10][] = ['permanent' => false, 'bonus' => [DISCARD_SCORING => 0]];
    // Random Bonus tiles on score board
    $spots = [5, 5, 8, 8];
    $bonTiles = BONUS_TILES;
    // If marine world, add new bonus tiles
    if (self::isMarineWorld()) {
      $bonTiles = BONUS_TILES_MARINE;
      $spots[] = MAX_REP_BONUS_SLOT; // And add a bonus tile at the end of the appeal track
    }

    // Make sure to not reuse the same bonuses as in previous solo game
    $soloChallengeData = self::getSoloChallengeData();
    if (!isset($soloChallengeData['bonuses'])) {
      $soloChallengeData['bonuses'] = [];
    }
    $bonuseTiles = Utils::bonus_diff($bonTiles, $soloChallengeData['bonuses']);

    // Do we have enough bonuses left ? Otherwise reuse some
    $delta = count($spots) - count($bonuseTiles);
    if ($delta > 0) {
      $keys = Utils::rand(array_keys($soloChallengeData['bonuses']), $delta);
      for ($i = 0; $i < $delta; $i++) {
        $bonuseTiles[] = $soloChallengeData['bonuses'][$keys[$i]];
      }
    }

    $bonusKeys = Utils::rand(array_keys($bonuseTiles), count($spots));
    foreach ($spots as $i => $spot) {
      $bonuses[$spot][] = [
        'permanent' => false,
        'bonus' => $bonuseTiles[$bonusKeys[$i]],
      ];
      $soloChallengeData['bonuses'][] = $bonuseTiles[$bonusKeys[$i]];
    }
    self::setBonusTiles($bonuses);
    self::setSoloChallengeData($soloChallengeData);
  }
}
