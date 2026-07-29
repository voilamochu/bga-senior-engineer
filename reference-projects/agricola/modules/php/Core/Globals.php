<?php

namespace AGR\Core;

/*
 * Globals
 */

class Globals extends \AGR\Helpers\DB_Manager
{
  protected static $initialized = false;
  protected static $variables = [
    'engine' => 'obj',
    // DO NOT MODIFY, USED IN ENGINE MODULE
    'engineChoices' => 'int',
    // DO NOT MODIFY, USED IN ENGINE MODULE
    'callbackEngineResolved' => 'obj',
    // DO NOT MODIFY, USED IN ENGINE MODULE
    'anytimeRecursion' => 'int',
    // DO NOT MODIFY, USED IN ENGINE MODULE

    'customTurnOrders' => 'obj',
    // DO NOT MODIFY, USED FOR CUSTOM TURN ORDER FEATURE

    'harvest' => 'bool',
    'fieldPhase' => 'bool',
    'breedPhase' => 'bool',
    'skippedPlayers' => 'obj',
    'exchangeFlags' => 'obj',

    'obtainedResourcesDuringWork' => 'obj',
    'farmyardGoodsPlacedDuringWork' => 'obj',
    'placedFarmers' => 'obj',
    'lassoPlacedFarmers' => 'obj',

    'gameSeed' => 'str',

    // Game options
    'solo' => 'bool',
    'beginner' => 'bool',
    'banlist' => 'bool',
    'additional' => 'bool',
    'liveScoring' => 'bool',
    'draftMode' => 'int',
    'turn' => 'int',
    'completedFeedingPhases' => 'int',
    'scytheWorkerFields' => 'obj',
    'scytheField' => 'obj',
    'grainThiefFields' => 'obj',
    'denBuilderRoom' => 'int',
    'stableManureFields' => 'obj',
    'harvestedFields' => 'obj',
    'lastRevealed' => 'str',
    'draftTurn' => 'int',
    'draftPlayerTurns' => 'obj', // deprecated — kept for legacy game compat
    'draftPlayer1Turn' => 'int',
    'draftPlayer2Turn' => 'int',
    'draftPlayer3Turn' => 'int',
    'draftPlayer4Turn' => 'int',
    'draftLastPool' => 'obj',
    'livingHandPendingPassing' => 'obj',
    'livingHandRefillReturn' => 'str',
    'firstPlayer' => 'int',
    'deckA' => 'bool',  // deprecated
    'deckB' => 'bool',  // deprecated
    'oldDeckSelection' => 'bool',  // deprecated
    'adoptiveChild' => 'int',
    // deprecated
    'snakeOpening' => 'bool',
    'snakeOpeningFlipped' => 'bool',
    'soloActionCardMode' => 'int',
    'passHarvest' => 'obj',
    'passFieldAndBreed' => 'obj',
    'skipHarvest' => 'obj',
    'skipFieldAndBreed' => 'obj',
    'workPhase' => 'bool',
    'gameFlowPhase' => 'str',
    'numBredAnimals' => 'int',
    'numSilentKills' => 'int',
    'skipNext' => 'obj',

    'turnId' => 'str',
    'stablesTurnId' => 'obj',
    'roomsTurnId' => 'obj',
    'fencesTurnId' => 'obj',
    'lessonsTurnId' => 'obj',
    'cookedTurnId' => 'obj',
    'reservedResourcesForScoring' => 'obj',
    'bonusA136' => 'obj',
    'bonusC133' => 'int',
    'fenceColorPids' => 'obj',
    'bassinetFirstSpace' => 'str',
    'jobContractFake' => 'int',

    'd115' => 'obj',
    // deprecated
    'debugGlobal' => 'bool',

    // Solo campaign
    'campaign' => 'bool',
    'campaignGoal' => 'int',
    'permanentOccupations' => 'obj',
    'campaignResults' => 'obj',
    'campaignOptions' => 'obj',
    'campaignPermanentsPending' => 'bool',
    'campaignDealtHand' => 'obj',
  ];

  protected static $table = 'global_variables';
  protected static $primary = 'name';
  protected static function cast($row)
  {
    $val = json_decode(\stripslashes($row['value']), true);
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

    foreach (self::DB()->select(['value', 'name'])->get(false) as $name => $variable) {
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
    return null;
  }

  /*
   * Setup new game
   */
  public static function setupNewGame($players, $options)
  {
    self::setSolo(count($players) == 1);
    self::setBeginner($options[OPTION_COMPETITIVE_LEVEL] == OPTION_COMPETITIVE_BEGINNER);
    self::setBanlist($options[OPTION_COMPETITIVE_LEVEL] == OPTION_COMPETITIVE_BANLIST);
    self::setAdditional(($options[OPTION_ADDITIONAL_SPACES] ?? OPTION_ADDITIONAL_SPACES_DISABLED) == OPTION_ADDITIONAL_SPACES_ENABLED);
    self::setDraftMode($options[OPTION_DRAFT] ?? 0);
    self::setLiveScoring($options[OPTION_SCORING] == OPTION_SCORING_ENABLED);
    self::setSnakeOpening(
      (count($players) > 1) && (($options[OPTION_SNAKE_OPENING] ?? OPTION_SNAKE_OPENING_DISABLED) == OPTION_SNAKE_OPENING_ENABLED)
    );
    self::setSnakeOpeningFlipped(false);
    self::setCampaign(
      (count($players) == 1) && (($options[OPTION_CAMPAIGN] ?? OPTION_CAMPAIGN_DISABLED) == OPTION_CAMPAIGN_ENABLED)
    );
    self::setTurn(0);
    self::setDraftTurn(0);
    self::setFirstPlayer(Game::get()->getNextPlayerTable()[0]);
    // self::setFirstPlayer(\array_rand($players));
  }

  
  //Reset per-game state between two games of a solo campaign.
  public static function resetForNextCampaignGame()
  {
    $keep = [
      // Engine internals (managed by the engine module)
      'engine', 'engineChoices', 'callbackEngineResolved', 'anytimeRecursion',
      // Game configuration, chosen once when the table is created
      'solo', 'beginner', 'banlist', 'additional', 'liveScoring', 'draftMode', 'gameSeed',
      // Campaign progress
      'campaign', 'campaignGoal', 'permanentOccupations', 'campaignResults', 'campaignOptions', 'campaignPermanentsPending', 'campaignDealtHand',
      // Re-derived just below rather than defaulted
      'firstPlayer',
    ];

    $defaults = ['int' => 0, 'obj' => [], 'bool' => false, 'str' => ''];
    foreach (self::$variables as $name => $type) {
      if (in_array($name, $keep)) {
        continue;
      }
      $setter = 'set' . ucfirst($name);
      self::$setter($defaults[$type]);
    }

    self::setFirstPlayer(Game::get()->getNextPlayerTable()[0]);
  }
}
