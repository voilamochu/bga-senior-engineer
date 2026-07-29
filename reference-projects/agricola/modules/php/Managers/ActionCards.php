<?php
namespace AGR\Managers;

use AGR\Core\Globals;
use AGR\Core\Meeples;
// use agricola;

/* Class to manage all the cards for Agricola */

class ActionCards extends \AGR\Helpers\Pieces
{
  protected static $table = 'cards';
  protected static $prefix = 'card_';
  protected static $customFields = ['player_id', 'extra_datas'];
  protected static $autoIncrement = false;

  protected static function cast($card)
  {
    $className = '\AGR\Cards\Actions\\' . $card['id'];
    if (isset($card['player_id'])) {
      $className = '\AGR\Cards\\' . $card['id'][0] . '\\' . $card['id'];
    }
    
    return new $className($card);
  }

  protected static $actionCards = [
    'CattleMarket',
    'ClayPit',
    'Copse',
    'Cultivation',
    'DayLaborer',
    'EasternQuarry',
    'FarmExpansion',
    'Farmland',
    'FarmRedevelopment',
    'Fencing',
    'Fishing',
    'Forest',
    'ForestSolo',
    'GrainSeeds',
    'GrainUtilization',
    'Grove',
    'Hollow4',
    'Hollow',
    'HouseRedevelopment',
    'HouseRedevelopmentBeginner',
    'Lessons3',
    'Lessons4',
    'Lessons',
    'MajorImprovement',
    'MajorImprovementBeginner',
    'MeetingPlace',
    'MeetingPlaceBeginner',
    'MeetingPlaceSolo',
    'MeetingPlaceSoloBeginner',
    'PigMarket',
    'ReedBank',
    'ResourceMarket4',
    'ResourceMarket',
    'SheepMarket',
    'SideJob',
    'TravelingPlayers',
    'UrgentWishChildren',
    'VegetableSeeds',
    'WesternQuarry',
    'WishChildren',
    'WishChildrenBeginner',
    // Additional tile
    'ResourceMarketAdd',
    'CopseAdd',
    'AnimalMarketAdd',
    'WishChildrenAdd',
  ];

  /* Creation of the cards */
  public static function setupNewGame($players, $options)
  {
    $cards = [];
    $turn = 1;
    foreach (self::$actionCards as $class) {
      $className = '\AGR\Cards\Actions\Action' . $class;
      $card = new $className(null);

      // Check number of players and options constraints
      if (!$card->isSupported($players, $options)) {
        continue;
      }

      $cards[] = [
        'id' => $card->getId(),
        'location' => $card->getInitialLocation(),
        'state' => $card->getInitialLocation() == 'board' ? 1 : 0,
      ];
    }

    self::create($cards, null);

    for ($i = 1; $i <= 6; $i++) {
      self::shuffle('deck_' . $i);

      foreach (self::getInLocation('deck_' . $i, null, 'state') as $id => $card) {
        self::move($card->getId(), 'turn_' . $turn);
        $turn++;
      }
    }
  }

  public static function getInLocation($location, $state = null, $orderBy = null)
  {
    return parent::getInLocationQ($location, $state, $orderBy)
      ->where('card_id', 'LIKE', 'Action%')
      ->get();
  }

  public static function getVisible($player = null)
  {
    $cards = self::getInLocation('board')->merge(self::getInLocation(['turn', '%'], VISIBLE));
    foreach (Players::getAll() as $player2) {
      $cards = $cards->merge($player2->getActionCards());
    }
    return $cards;
  }

  public static function getAccumulationSpaces($type = null, $player = null)
  {
    $cards = self::getInLocation('board')->merge(self::getInLocation(['turn', '%'], VISIBLE));
    foreach (Players::getAll() as $player2) {
      $cards = $cards->merge($player2->getActionCards());
    }

    $cards = $cards->filter(function ($space) {
      return ($space->getAccumulation() != null);
    });

    if (isset($type)) {
      $cards = $cards->filter(function ($space) use ($type) {
        return isset($space->getAccumulation()[$type]);
      });
   }

    return $cards;
  }

  public static function getUiData()
  {
    return [
      'visible' => self::getVisible()->ui(),
      'help' => self::getHelp(),
    ];
  }

  // First turn of the stage each turn belongs to (index = turn)
  const TURN_STAGE_START = [0, 1, 1, 1, 1, 5, 5, 5, 8, 8, 10, 10, 12, 12, 14];

  public static function getHelp()
  {
    $cards = self::getInLocation(['turn', '%'])->ui();
    foreach ($cards as &$card) {
      $turn = \explode('_', $card['location'])[1];
      $card['location'] = 'turn_' . self::TURN_STAGE_START[$turn];
    }
    return $cards;
  }

  /**
   * All still-hidden action cards of the stage the given turn belongs to
   */
  public static function getHiddenStageCards($turn)
  {
    $cards = [];
    for ($t = $turn; $t <= 14 && self::TURN_STAGE_START[$t] == self::TURN_STAGE_START[$turn]; $t++) {
      foreach (self::getInLocation(['turn', $t], HIDDEN) as $card) {
        $cards[] = $card;
      }
    }
    return $cards;
  }

  public static function draw()
  {
    $turn = Globals::getTurn();
    $location = ['turn', $turn];
    if (self::countInLocation($location, HIDDEN) == 0) {
      return self::getInLocation($location);
      // throw new \feException('Card is alreay visible');
    }

    self::moveAllInLocation($location, $location, HIDDEN, VISIBLE);
    return self::getInLocation($location);
  }

  public static function accumulate()
  {
    $ids = [];
    foreach (self::getVisible() as $id => $card) {
      $ids = array_merge($ids, $card->accumulate());
    }
    return $ids;
  }

  /**
   * Generate/load seed
   */
  public static function getSeed()
  {
    $res = '';
    foreach (self::$actionCards as $class) {
      $card = self::getSingle('Action' . $class, false);
      if ($card != null && $card->getTurn() != 0) {
        $res .= dechex($card->getTurn());
      }
    }
    return $res;
  }

  public static function setSeed($seed)
  {
    $i = 0;
    foreach (self::$actionCards as $class) {
      $card = self::getSingle('Action' . $class, false);
      if ($card != null && $card->getTurn() != 0) {
        $turn = hexdec($seed[$i++]);
        self::move($card->getId(), 'turn_' . $turn);
      }
    }
  }

  // Return the live piece id of the round card on turn N (visible on the board)
  public static function getIdByTurn($turn)
  {
    $cards = self::getInLocation(['turn', $turn], VISIBLE);
    foreach ($cards as $id => $card) {
      return $card->getId();
    }
    return null;
  }
}
