<?php
namespace AGR\Models;

use AGR\Managers\Meeples;
use AGR\Managers\Farmers;
use AGR\Core\Notifications;
use AGR\Core\Engine;
use AGR\Core\Globals;
use AGR\Helpers\Utils;
use AGR\Managers\ActionCards;
use AGR\Managers\PlayerCards;
use AGR\Managers\Players;

/*
 * Action cards for all
 */

class ActionCard extends \AGR\Models\AbstractCard
{
  /*
   * STATIC INFORMATIONS
   *  they are overwritten by children
   */
  protected $type = ACTION;
  protected $actionCardType = null; // Useful to declare Hollow4 as an Hollow action
  protected $stage = 0;
  protected $accumulation = []; // Array of resource => amount
  protected $desc = []; // UI
  protected $tooltipDesc = null; // UI
  protected $size = 'm'; // UI
  protected $container = 'central'; // UI
  protected $accumulate = ''; // UI
  protected $adjacent = [];

  // Constraints
  protected $players = null; // Players requirements => null if none, integer if only one, array otherwise
  protected $isAdditional = false;
  protected $isBeginner = false; // Will ONLY be there on the beginner variant
  protected $isNotBeginner = false; // Will NOT be there on the beginner variant

  /*
   * DYNAMIC INFORMATIONS
   */
  protected $visible = false;

  public function __construct($row)
  {
    parent::__construct($row);
    if ($row != null) {
      $this->visible = $row['location'] == 'board'; // TODO
    }
  }

  public function jsonSerialize()
  {
    $data = parent::jsonSerialize();
    $data['component'] = $this->isBoardComponent();
    $data['container'] = $this->container;
    $data['desc'] = $this->desc;
    $data['tooltipDesc'] = $this->tooltipDesc ?? $this->desc;
    $data['size'] = $this->size;
    $data['accumulate'] = $this->accumulate;

    return $data;
  }

  public function getActionCardType()
  {
    return $this->actionCardType ?? substr($this->id, 6);
  }

  public function isSupported($players, $options)
  {
    return ($this->players == null || in_array(count($players), $this->players)) &&
      (!$this->isAdditional || ($options[OPTION_ADDITIONAL_SPACES] ?? OPTION_ADDITIONAL_SPACES_DISABLED) == OPTION_ADDITIONAL_SPACES_ENABLED) &&
      (!$this->isBeginner || $options[OPTION_COMPETITIVE_LEVEL] == OPTION_COMPETITIVE_BEGINNER) &&
      (!$this->isNotBeginner || $options[OPTION_COMPETITIVE_LEVEL] != OPTION_COMPETITIVE_BEGINNER);
  }

  public function getInitialLocation()
  {
    return $this->stage == 0 ? 'board' : ['deck', $this->stage];
  }

  public function getTurn()
  {
    $loc = $this->getLocation();
    $t = explode('_', $loc);
    if ($t[0] != 'turn') {
      return 0;
    }

    return (int) $t[1];
  }
 
  public function isVisible()
  {
    return $this->getTurn() <= Globals::getTurn();
  }

  public function isBoardComponent()
  {
    return $this->stage == 0;
  }

  public function hasAccumulation()
  {
    if (count($this->accumulation) == 0) {
      return false;
    }
    return true;
  }

  public function getAccumulation()
  {
    return $this->accumulation;
  }

  public function accumulate()
  {
    $ids = [];
    if ($this->hasAccumulation()) {
      foreach ($this->accumulation as $resource => $amount) {
        $ids = array_merge($ids, Meeples::createResourceOnCard($resource, self::getId(), $amount));
      }
    }
    return $ids;
  }
  
  public function canBePlayed($player, $onlyCheckSpecificPlayer = null, $ignoreResources = false, $ignoreDoable = false)
  {
    // What cards should we check ?
    $actionList = [$this->id];
    if ($this->isAdditional) {
      $actionList = array_merge($actionList, [
        'ActionResourceMarketAdd',
        'ActionCopseAdd',
        'ActionAnimalMarketAdd',
        'ActionWishChildrenAdd',
      ]);
    }

    $exclusiveUse = $this->getExtraDatas('exclusiveUse') ?? [];
    if ($exclusiveUse != []) {
      if ($player->getId() != $exclusiveUse) {
        return false;
      }
    }

    $ignoreOccupacy = false;
    if (
      $player->hasPlayedCard('B151_LittlePeasant') && PlayerCards::get('B151_LittlePeasant')->canIgnoreOccupacy($player) &&
      $this->getActionCardType() != 'MeetingPlace'
    ) {
      $ignoreOccupacy = true;
    }

    if (!$ignoreOccupacy) {
      // Is there a farmer here ?
      foreach ($actionList as $action) {
        $farmers = Farmers::getOnCard($action);
        if ($farmers->count() > 0 && $onlyCheckSpecificPlayer == null) {
          return false;
        }

        $pIds = $farmers
          ->map(function ($farmer) {
            return $farmer['pId'];
          })
          ->toArray();
        if (in_array($onlyCheckSpecificPlayer, $pIds)) {
          return false;
        }
      }
    }

    // Check that the action is doable
    $flow = $this->getFlow($player);
    $flowTree = Engine::buildTree($flow);
    return $flowTree->isDoable($player, $ignoreResources) || $ignoreDoable;
  }


  public function getFlow($player)
  {
    // Add card context for listeners
    return Utils::tagTree($this->flow, [
      'pId' => $player->getId(),
      'cardId' => $this->id,
    ]);
  }

  public function getAdjacentSpaces()
  {
    $playerCount = Players::count();
    $forest = ($playerCount == 1) ? 'ActionForestSolo' : 'ActionForest';
    $lessons = ($playerCount == 3) ? 'ActionLessons3' : 'ActionLessons4';
    $meetingPlace = ($playerCount == 1) ? 'ActionMeetingPlaceSolo' : 'ActionMeetingPlace';
    $resourceMarket = ($playerCount == 3) ? 'ActionResourceMarket' : 'ActionResourceMarket4';
    $hollow = ($playerCount == 3) ? 'ActionHollow' : 'ActionHollow4';

    $map = [
      $forest => [1, 5, 'ActionGrainSeeds', 'ActionClayPit'],
      'ActionClayPit' => [8, $forest, 'ActionFarmland', 'ActionReedBank'],
      'ActionReedBank' => [8, 10, 'ActionClayPit', 'ActionFishing', 'ActionLessons'],
      'ActionFishing' => [10, 13, 'ActionReedBank', 'ActionDayLaborer'],
      'ActionFarmExpansion' => [1, $meetingPlace, ($playerCount == 4) ? 'ActionCopse' : 'ActionGrove'],
      $meetingPlace => ['ActionFarmExpansion', 'ActionGrove', 'ActionGrainSeeds'],
      'ActionGrainSeeds' => [$meetingPlace, $resourceMarket, $forest, 'ActionFarmland'],
      'ActionFarmland' => ['ActionGrainSeeds', 'ActionLessons', 'ActionClayPit', $hollow],
      'ActionLessons' => ['ActionFarmland', $lessons, 'ActionReedBank', 'ActionDayLaborer'],
      'ActionDayLaborer' => [12, 'ActionLessons', 'ActionFishing', 'ActionTravelingPlayers'],
      'ActionCopse' => ['ActionGrove', 'ActionFarmExpansion'],
      'ActionGrove' => ['ActionCopse', $resourceMarket, 'ActionMeetingPlace', ($playerCount == 3) ? 'ActionFarmExpansion' : ''],
      $resourceMarket => ['ActionGrove', $hollow, 'ActionGrainSeeds'],
      $hollow => [$resourceMarket, 'ActionFarmland', $lessons],
      $lessons => [$hollow, 'ActionLessons', 'ActionTravelingPlayers'],
      'ActionTravelingPlayers' => [$lessons, 'ActionDayLaborer', 'ActionWishChildrenAdd'],
      'ActionWishChildrenAdd' => ['ActionTravelingPlayers'],
    ];
    $map_turn = [
      1 => [2, 'ActionFarmExpansion', $forest],
      2 => [1, 3, 5],
      3 => [2, 4, 6],
      4 => [3, 7],
      5 => [2, 6, 8, $forest],
      6 => [3, 5, 7, 9],
      7 => [4, 6],
      8 => [5, 9, 10, 'ActionClayPit', 'ActionReedBank'],
      9 => [6, 8, 11],
      10 => [8, 11, 'ActionReedBank', 'ActionFishing'],
      11 => [9, 10, 14],
      12 => [13, 'ActionDayLaborer'],
      13 => [12, 14, 'ActionFishing'],
      14 => [11, 13],
    ];

    if (Globals::isAdditional()) {
      if ($playerCount == 4) {
        $map_turn[12][] = 'ActionWishChildrenAdd';
        $map['ActionWishChildrenAdd'][] = 12;
        $map['ActionWishChildrenAdd'][] = 'ActionAnimalMarketAdd';
        $map['ActionAnimalMarketAdd'][] = 'ActionWishChildrenAdd';
      }
      elseif ($playerCount == 3) {
        $map_turn[12][] = 'ActionAnimalMarketAdd';
        $map['ActionAnimalMarketAdd'][] = 12;
        $map['ActionWishChildrenAdd'][] = 'ActionAnimalMarketAdd';
        $map['ActionAnimalMarketAdd'][] = 'ActionWishChildrenAdd';
        $map['ActionWishChildrenAdd'][] = 'ActionDayLaborer';
        $map['ActionDayLaborer'][] = 'ActionWishChildrenAdd';
        $map['ActionWishChildrenAdd'][] = 'ActionLessons3';
        $map['ActionLessons3'][] = 'ActionWishChildrenAdd';
      }
      elseif ($playerCount == 2) {
        $map['ActionFarmExpansion'][] = 'ActionCopseAdd';
        $map['ActionMeetingPlace'][] = 'ActionWishChildrenAdd';
        $map['ActionGrainSeeds'][] = 'ActionWishChildrenAdd';
        $map['ActionGrainSeeds'][] = 'ActionResourceMarketAdd';
        $map['ActionFarmland'][] = 'ActionResourceMarketAdd';
        $map['ActionLessons'][] = 'ActionAnimalMarketAdd';
        $map['ActionDayLaborer'][] = 'ActionAnimalMarketAdd';

        $map['ActionCopseAdd'] = ['ActionFarmExpansion', 'ActionWishChildrenAdd'];
        $map['ActionWishChildrenAdd'] = ['ActionCopseAdd', 'ActionMeetingPlace', 'ActionResourceMarketAdd', 'ActionGrainSeeds'];
        $map['ActionResourceMarketAdd'] = ['ActionWishChildrenAdd', 'ActionAnimalMarketAdd', 'ActionGrainSeeds', 'ActionFarmland'];
        $map['ActionAnimalMarketAdd'] = ['ActionResourceMarketAdd', 'ActionLessons', 'ActionDayLaborer'];
      }
    }

    $turn = $this->getTurn();
    if ($turn > 0) {
      return $map_turn[$turn];
    }
    else {
      return $map[$this->id];
    }
  }

  public static function getAboveSpaceForRound($round)
  {
    // Map from the most recent round space to the action space above it
    $above = [
      5 => 2,
      6 => 3,
      7 => 4,
      8 => 5,
      9 => 6,
      10 => 8,
      11 => 9,
      12 => 'ActionDayLaborer',
      13 => 'ActionFishing',
      14 => 11,
    ];

    if (!isset($above[$round])) {
      return null;
    }

    $target = $above[$round];

    // If numeric, resolve "turn -> piece id"; if string, use as is
    if (is_int($target)) {
      return ActionCards::getIdByTurn($target);
    }

    return $target;
  }
}
