<?php
namespace AGR\Models;

use AGR\Actions\Pay;
use AGR\Actions\Reorganize;
use AGR\Core\Engine;
use AGR\Core\Globals;
use AGR\Core\Notifications;
use AGR\Core\Preferences;
use AGR\Helpers\Utils;
use AGR\Managers\ActionCards;
use AGR\Managers\Actions;
use AGR\Managers\Farmers;
use AGR\Managers\Meeples;
use AGR\Managers\PlayerCards;
use AGR\Managers\Players;
use AGR\Managers\Scores;
use AGR\Managers\Stables;

/*
 * Player: all utility functions concerning a player
 */

class Player extends \AGR\Helpers\DB_Manager implements \JsonSerializable
{
  protected static $table = 'player';
  protected static $primary = 'player_id';

  protected $id;
  protected $no; // natural order
  protected $name; // player name
  protected $color;
  protected $eliminated = false;
  protected $score = 0;
  protected $zombie = false;
  protected $board = null;

  public function __construct($row)
  {
    if ($row != null) {
      $this->id = (int) $row['player_id'];
      $this->no = (int) $row['player_no'];
      $this->name = $row['player_name'];
      $this->color = $row['player_color'];
      $this->eliminated = $row['player_eliminated'] == 1;
      $this->score = $row['player_score'];
      $this->zombie = $row['player_zombie'] == 1;
      $this->placedFarmers = [];
    }
  }

  /*
   * Getters
   */
  public function getId()
  {
    return $this->id;
  }
  public function getNo()
  {
    return $this->no;
  }
  public function getName()
  {
    return $this->name;
  }
  public function getColor()
  {
    return $this->color;
  }
  public function isEliminated()
  {
    return $this->eliminated;
  }
  public function isZombie()
  {
    return $this->zombie;
  }

  public function getPref($prefId)
  {
    return Preferences::get($this->id, $prefId);
  }

  public function setPref($prefId, $value)
  {
    Preferences::set($this->id, $prefId, $value);
  }

  public function jsonSerialize($currentPlayerId = null)
  {
    $current = $this->id == $currentPlayerId;
    $data = [
      'id' => $this->id,
      'eliminated' => $this->eliminated,
      'no' => $this->no,
      'name' => $this->getName(),
      'color' => $this->color,
      'score' => $this->score,
      'resources' => [],
      'board' => $this->board()->getUiData(),
      'hand' => $current ? $this->getHand()->ui() : [],
      'harvestCost' => $this->getHarvestCost(),
      'personalOwned' => Meeples::getPersonalOwned($this->id),
    ];

    foreach (RESOURCES as $resource) {
      $data['resources'][$resource] = $this->countReserveResource($resource);
    }

    return $data;
  }

  public function board()
  {
    if ($this->board == null) {
      $this->board = new PlayerBoard($this);
    }
    return $this->board;
  }

  public function countRooms()
  {
    return Meeples::countRooms($this->id);
  }

  public function countExtraRoom($include_A127_Lodger = true)
  {
    $numPlayedRoomCards = (int) ($this->hasPlayedCard('A10_WoodenShed'))
      + (int) ($this->hasPlayedCard('B10_Caravan'));
    if ($this->hasPlayedCard('D85_Reader')) {
      if (Globals::getDraftMode() == OPTION_DRAFT_DISABLED && $this->countOccupations() >= 6) {
        $numPlayedRoomCards = $numPlayedRoomCards + 1;
      } else if ($this->countOccupations() >= 7) {
        $numPlayedRoomCards = $numPlayedRoomCards + 1;
      }
    }
    if ($this->hasPlayedCard('C10_BunkBeds')) {
      if ($this->countRooms() >= 4) {
        $numPlayedRoomCards = $numPlayedRoomCards + 1;
      }
    }

    if ($this->hasPlayedCard('A85_Homekeeper')) {
      foreach ($this->board()->getRooms() as $room) {
        if (($room['type'] == 'roomClay' or $room['type'] == 'roomStone') && (($this->board()->isAdjacentToType($room['x'], $room['y'], 'field') && $this->board()->isAdjacentToType($room['x'], $room['y'], 'pasture')) || $this->board()->isAdjacentToType($room['x'], $room['y'], 'pastureField'))) {
          $numPlayedRoomCards = $numPlayedRoomCards + 1;
          break;
        }
      }
    }

    if ($this->hasPlayedCard('C85_DenBuilder')) {
      if (Globals::getDenBuilderRoom() == 1) {
        $numPlayedRoomCards = $numPlayedRoomCards + 1;
      }
    }

    if ($this->hasPlayedCard('E85_MasterTanner')) {
      if (PlayerCards::get('E85_MasterTanner')->hasEnoughFood()) {
        $numPlayedRoomCards = $numPlayedRoomCards + 1;
      }
    }
    if ($this->hasPlayedCard('A127_Lodger') && $include_A127_Lodger) {
      if (PlayerCards::get('A127_Lodger')->hasRoom()) {
        $numPlayedRoomCards = $numPlayedRoomCards + 1;
      }
    }

    if ($this->hasPlayedCard('B85_FarmHand')) {
      if (Stables::hasFarmHandStable($this->getId())) {
        $numPlayedRoomCards = $numPlayedRoomCards + 1;
      }
    }

    return $numPlayedRoomCards;
  }

  public function canTakeAction($action, $ctx, $ignoreResources)
  {
    return Actions::isDoable($action, $ctx, $this, $ignoreResources);
  }

  public function countReserveResource($type)
  {
    return Meeples::countReserveResource($this->id, $type);
  }

  public function countReservedResourcesForScoring($type)
  {
    // Soldier/Drudgery Reeve only reserve at end-game, so the persistent Global is only consulted
    // then — mid-game a stale value can't reach live scoring.
    $persistent = 0;
    if (Globals::getGameFlowPhase() == 'endgame') {
      $persistent = Globals::getReservedResourcesForScoring()[$this->id][$type] ?? 0;
    }
    return $persistent + Scores::getLiveReservedForScoring($this->id, $type);
  }

  public function reserveResourcesForScoring($type, $amount)
  {
    Scores::reserveForScoring($this->id, $type, $amount);
  }

  public function countAllResource($type)
  {
    return Meeples::countAllResource($this->id, $type);
  }

  public function createResourceInReserve($type, $nbr = 1)
  {
    return Meeples::getMany(Meeples::createResourceInReserve($this->id, $type, $nbr))->toArray();
  }

  public function getAllReserveResources()
  {
    $reserve = [];
    foreach (RESOURCES as $res) {
      $reserve[$res] = 0;
    }

    foreach (Meeples::getReserveResource($this->id) as $meeple) {
      if (in_array($meeple['type'], RESOURCES)) {
        $reserve[$meeple['type']]++;
      }
    }

    return $reserve;
  }

  public function getReserveResource($type = null)
  {
    return Meeples::getReserveResource($this->id, $type);
  }

  public function getExchangeResources()
  {
    $reserve = $this->getAllReserveResources();
    $reserve = $this->countAnimalsOnBoard($reserve); // Add animals, even if they are not in reserve
    if ($this->hasPlayedCard('B155_ArtTeacher')) {
      if (Players::count() < 4 && PlayerCards::get('C39_StudioBoat')->isPlayed()) {
        $reserve[FOOD_TRAVEL] = Meeples::getResourcesOnCard('C39_StudioBoat', null, FOOD)->count();
      } else {
        $reserve[FOOD_TRAVEL] = Meeples::getResourcesOnCard('ActionTravelingPlayers', null, FOOD)->count();
      }
    }
    return $reserve;
  }

  public function getRoomType()
  {
    return Meeples::getRoomType($this->id);
  }

  public function hasRenoBlockingCards()
  {
    return $this->hasPlayedCard('A10_WoodenShed') ||
      $this->hasPlayedCard('B33_Mantlepiece');
  }

  public function countStablesInReserve()
  {
    return Meeples::getReserveResource($this->id, 'stable')->count();
  }

  public function getStablesInReserve()
  {
    return Meeples::getReserveResource($this->id, 'stable');
  }

  public function getNextCropToSow($type)
  {
    return Meeples::getReserveResource($this->id, $type)->first();
  }

  public function countOccupations()
  {
    return $this->getCards(OCCUPATION, true)->count();
  }

  public function countAllImprovements()
  {
    return $this->getCards(MAJOR, true)->count() + $this->getCards(MINOR, true)->count() - $this->getMajorMinor()->count();
  }

  public function getCards($type = null, $playedOnly = false)
  {
    return PlayerCards::getOfPlayer($this->id)->filter(function ($card) use ($type, $playedOnly) {
      return ($type == null || $card->hasType($type)) && (!$playedOnly || $card->isPlayed());
    });
  }

  public function getMajorMinor(){
    return PlayerCards::getOfPlayer($this->id)->filter(function ($card) {
      return $card->isPlayed() && $card->getType() == MINOR && $card->hasType(MAJOR);
    });
  }

  public function getDraftSelection()
  {
    return $this->getCards()->filter(function ($card) {
      return $card->getLocation() == 'selection';
    });
  }

  public function getHand($type = null)
  {
    return PlayerCards::getOfPlayer($this->id)->filter(function ($card) use ($type) {
      return ($type == null || $card->getType() == $type) && !$card->isPlayed() && $card->getLocation() != 'phase2';
    });
  }

  public function getPlayedCards($type = null)
  {
    return $this->getCards($type, true);
  }

  public function getActionCards()
  {
    return $this->getPlayedCards()->filter(function ($card) {
      return $card->isActionCard() &&
        (!$card->isPrivateSpace() || $this->getId() == $card->getPlayer()->getId());
    });
  }

  public function hasPlayedCard($cardId)
  {
    return $this->getPlayedCards()->reduce(function ($carry, $card) use ($cardId) {
      return $carry || $card->getId() == $cardId;
    }, false);
  }

  public function hasInHand($cardId)
  {
    $hand = $this->getHand();

    foreach ($hand as $card) {
      if ($card->getId() == $cardId) {
        return true;
      }
    }

    return false;
  }

  public function canCook()
  {
    return $this->getPlayedCards()->reduce(function ($carry, $card) {
      return $carry || $card->canCook();
    }, false);
  }

  public function canBake()
  {
    return $this->getPlayedCards()->reduce(function ($carry, $card) {
      return $carry || $card->canBake();
    }, false);
  }

  public function getPossibleExchanges($trigger = ANYTIME, $removeAnytime = false)
  {
    $exchanges = [Utils::formatExchange([GRAIN => [FOOD => 1]]), Utils::formatExchange([VEGETABLE => [FOOD => 1]])];

    foreach ($this->getPlayedCards() as $card) {
      $exchanges = array_merge($exchanges, $card->getExchanges());
    }

    // Filter according to trigger
    Utils::filterExchanges($exchanges, $trigger, $removeAnytime);

    return $exchanges;
  }

  // True iff this player can buy a non-food good (via an affordable once-per-harvest exchange) that one
  // of their cards wants before feeding, and that they don't already have and aren't about to reap.
  // Gates the up-front harvest exchange window (see HarvestTrait::harvestPrepPlayer).
  public function harvestPrepGoods()
  {
    $reserve = $this->getExchangeResources();
    $producible = [];
    foreach ($this->getPossibleExchanges(HARVEST, true) as $exchange) {
      if (is_null($exchange['flag'] ?? null)) {
        continue;
      }
      foreach ($exchange['from'] as $res => $amount) {
        if (($reserve[$res] ?? 0) < $amount) {
          continue 2;
        }
      }
      foreach ($exchange['to'] as $res => $amount) {
        if ($res != FOOD && $res != SCORE) {
          $producible[$res] = true;
        }
      }
    }
    if (empty($producible)) {
      return false;
    }

    foreach ($this->getPlayedCards() as $card) {
      foreach ($card->preFeedingGoodsWanted() as $res) {
        if (
          isset($producible[$res]) &&
          $this->countReserveResource($res) == 0 &&
          !$this->willReapHarvestGood($res)
        ) {
          return true;
        }
      }
    }
    return false;
  }

  public function willReapHarvestGood($res)
  {
    if ($res == GRAIN) {
      return !empty($this->board()->getGrainFields());
    }
    if ($res == VEGETABLE) {
      return !empty($this->board()->getVegetableFields());
    }
    return false;
  }

  public function useResource($resource, $amount, $preferIds = null)
  {
    if ($resource == FOOD_TRAVEL) {
      $foodOnTravelling = [];
      if (Players::count() < 4 && PlayerCards::get('C39_StudioBoat')->isPlayed()) {
        $foodOnTravelling = Meeples::getResourcesOnCard('C39_StudioBoat', null, FOOD);
      } else {
        $foodOnTravelling = Meeples::getResourcesOnCard('ActionTravelingPlayers', null, FOOD);
      }
      if ($foodOnTravelling->count() < $amount) {
        throw new UserException(clienttranslate('Not enough food on Traveling Players'));
      }
      foreach ($foodOnTravelling as $meeple) {
        $meeples[] = $meeple;
        $amount--;
        if ($amount == 0) {
          break;
        }
      }
      return Meeples::deleteResources($meeples);
    }
    return Meeples::useResource($this->id, $resource, $amount, $preferIds);
  }

  public function payResourceTo($pId, $resource, $amount)
  {
    return Meeples::payResourceTo($this->id, $resource, $amount, $pId);
  }

  /************************
   ******** ANIMALS ********
   ************************/
  public function checkAutoReorganize(&$meeples)
  {
    return Reorganize::checkAutoReorganize($this, $meeples);
  }

  public function getAnimals($location = null)
  {
    return Meeples::getAnimals($this->id, $location);
  }

  /**
   * Get all animals on board, including the ones on card holders
   */
  public function getAnimalsOnBoard()
  {
    $animals = $this->getAnimals('board');
    foreach ($this->getPlayedCards() as $card) {
      if ($card->isAnimalHolder()) {
        $animals = $animals->merge($this->getAnimals($card->getId()));
      }
    }
    return $animals;
  }

  /**
   * Count the number of animals in a given location
   */
  public function countAnimalsInLocation($location, $res = null)
  {
    $reserve = $res ?? [SHEEP => 0, PIG => 0, CATTLE => 0];
    foreach (Meeples::getAnimals($this->id, $location) as $meeple) {
      $reserve[$meeple['type']]++;
    }
    return $reserve;
  }

  /**
   * Count the number of animals of each type on board, including on card holders
   */
  public function countAnimalsOnBoard($res = null)
  {
    $res = $this->countAnimalsInLocation('board', $res);
    foreach ($this->getPlayedCards() as $card) {
      if ($card->isAnimalHolder()) {
        $res = $this->countAnimalsInLocation($card->getId(), $res);
      }
    }
    return $res;
  }
  public function checkHaveAllTypeAnimals()
  {
    if ($this->countAnimalsOnBoard()[SHEEP] < 1 || $this->countAnimalsOnBoard()[PIG] < 1 || $this->countAnimalsOnBoard()[CATTLE] < 1) {
      return false;
    }
    return true;
  }

  public function countAnimalsInReserve($res = null)
  {
    return $this->countAnimalsInLocation('reserve', $res);
  }

  /**
   * Which animals can be converted by a card?
   */
  public function getExchangeableAnimalTypes($trigger = ANYTIME)
  {
    $types = [
      SHEEP => false,
      PIG => false,
      CATTLE => false,
    ];

    // Iterate through all possible exchanges and check "from" field
    foreach ($this->getPossibleExchanges($trigger) as $exchange) {
      foreach ($exchange['from'] as $resType => $amount) {
        if (\array_key_exists($resType, $types)) {
          $types[$resType] = true;
        }
      }
    }

    return $types;
  }

  /**
   * Check whether there are un-accomodated animals in reserve or not, and trigger REORGANIZE if needed
   */
  public function checkAnimalsInReserve($needConfirm = false)
  {
    if ($needConfirm || $this->getAnimals('reserve')->count() > 0) {
      Engine::insertAsChild([
        'action' => REORGANIZE,
      ]);
    }
  }

  /**
   * Check whether some animals are invalid and need reorganize or not
   */
  public function forceReorganizeIfNeeded()
  {
    $animals = $this->board()->getInvalidAnimals(false);
    if (empty($animals)) {
      return;
    }

    // Put all invalid animals in the reserve
    $ids = [];
    foreach ($animals as $meeple) {
      Meeples::moveToCoords($meeple['id'], 'reserve');
      $ids[] = $meeple['id'];
    }

    // Try to accomodate them somewhere else
    $meeples = Meeples::getMany($ids)->toArray();
    $reorganize = $this->checkAutoReorganize($meeples);

    // Notify the deplacements
    Notifications::moveAnimalsAround($this, $meeples);

    // Reorganize if needed
    $this->checkAnimalsInReserve($reorganize);
  }

  /*************************
   ******* PAY SUGAR ********
   *************************/
  public function canPayCost($cost)
  {
    return $this->canPayFee(['fee' => $cost]);
  }

  public function canPayFee($costs)
  {
    return Pay::canPayFee($this, $costs);
  }

  public function canBuy($costs, $n = 1)
  {
    return Pay::canBuy($this, $costs, $n);
  }

  public function maxBuyableAmount($costs)
  {
    return Pay::maxBuyableAmount($this, $costs);
  }

  public function pay($nb, $costs, $source = '', $insertInsideEngine = true, $sourceInfo = null)
  {
    $node = [
      'action' => PAY,
      'args' => [
        'nb' => $nb,
        'costs' => $costs,
        'source' => $source,
        'sourceInfo' => $sourceInfo
      ],
    ];
    if ($insertInsideEngine) {
      Engine::insertAsChild($node);
    }
    return $node;
  }

  /**************************
   ****** FARMERS SUGAR ******
   **************************/
  public function getAllFarmers()
  {
    return Farmers::getAllOfPlayer($this->id);
  }

  public function hasFarmerAvailable()
  {
    return Farmers::hasAvailable($this->id);
  }

  public function hasAdoptiveAvailable()
  {
    return $this->hasPlayedCard('A92_AdoptiveParents') &&
      Farmers::hasChildren($this->id) &&
      !in_array($this->id, Globals::getSkippedPlayers());
  }

  public function hasGuestRoomAvailable()
  {
    return $this->hasPlayedCard('E22_GuestRoom') && PlayerCards::get('E22_GuestRoom')->canBeActivated() &&
      !in_array($this->id, Globals::getSkippedPlayers());
  }

  public function hasWorkPermitAvailable()
  {
    return $this->hasPlayedCard('D22_WorkPermit') &&
      PlayerCards::get('D22_WorkPermit')->isFlagged() &&
      Globals::getTurn() == PlayerCards::get('D22_WorkPermit')->getExtraDatas('turn') &&
      $this->hasFarmerInReserve();
  }

  public function hasTelegramAvailable()
  {
    return $this->hasPlayedCard('A22_Telegram') &&
      PlayerCards::get('A22_Telegram')->isFlagged() &&
      Globals::getTurn() == PlayerCards::get('A22_Telegram')->getExtraDatas('triggerRound') &&
      $this->hasFarmerInReserve();
  }

  // TODO : useful ??
  public function getNextFarmerAvailable()
  {
    return Farmers::getNextAvailable($this->id);
  }

  public function moveNextFarmerAvailable($location, $coords = null, $fromSupply = false)
  {
    return Farmers::moveNextAvailable($this->id, $location, $coords, $fromSupply);
  }

  public function countFarmers($type = null)
  {
    return Farmers::count($this->id, $type);
  }

  public function getFarmers($type = null)
  {
    return Farmers::getFarmers($this->id, $type);
  }

  public function hasFarmerInReserve()
  {
    return Farmers::hasInReserve($this->id);
  }

  public function getNextFarmerInReserve()
  {
    return Farmers::getNextInReserve($this->id);
  }

  public function trackPlacedFarmers($fId)
  {
    $pId = $this->id;
    $placed = Globals::getPlacedFarmers();

    if (!isset($placed[$pId])) {
      $placed[$pId] = [];
    }

    if (!in_array($fId, $placed[$pId])) {
      $placed[$pId][] = $fId;
      Globals::setPlacedFarmers($placed);
    }
  }

  public function countPlacedFarmers()
  {
    return array_key_exists($this->id, Globals::getPlacedFarmers()) ? count(Globals::getPlacedFarmers()[$this->id]) : 0;
  }

  public function resetPlacedFarmers()
  {
    Globals::setPlacedFarmers([]);
    Globals::setLassoPlacedFarmers([]);
  }

  public function getFarmersOnAccumulationSpaces()
  {
    $pId = $this->id;
    $spaces = ActionCards::getAccumulationSpaces();
    $n = 0;

    foreach ($spaces as $space) {
      if (!Farmers::getOnCard($space->getId(), $pId)->empty()) {
        $n++;
      }
    }

    return $n;
  }

  /**************************
   ********* ACTIONS ********
   **************************/

  public function growFamily($action, $location = 'card')
  {
    $mId = Farmers::getNextInReserve($this->id)['id'];
    if ($location == 'card') {
      Meeples::moveToCoords($mId, $action);
    } else {
      Meeples::moveToCoords($mId, 'board', $action);
    }
    Meeples::setState($mId, CHILD); // Tag him as a CHILD
    return Meeples::get($mId);
  }

  public function returnHomeOne($fId)
  {
    $rooms = Meeples::getRooms($this->id);
    $room = $rooms->first();
    return Meeples::moveToCoords($fId, 'board', [$room['x'], $room['y']]);
  }

  public function getFreeRoom()
  {
    $rooms = Meeples::getRooms($this->id);
    foreach ($rooms as $room) {
      if ($this->countFarmerAtPos(['x' => $room['x'], 'y' => $room['y']]) == 0) {
        return $room;
      }
    }
    return null;
  }

  public function countFarmerAtPos($coord)
  {
    return Meeples::getOnCardQ('board', $this->id)
      ->where('type', 'farmer')
      ->where('x', $coord['x'])
      ->where('y', $coord['y'])
      ->count();
  }

  public function returnHomeFarmers()
  {
    $farmers = self::getAllFarmers();
    $rooms = Meeples::getRooms($this->id);

    // Cards providing room
    $woodenShed = $this->hasPlayedCard('A10_WoodenShed');
    $lodger = $this->hasPlayedCard('A127_Lodger');
    $caravan = $this->hasPlayedCard('B10_Caravan');
    $denBuilder = $this->hasPlayedCard('C85_DenBuilder');
    $reader = $this->hasPlayedCard('D85_Reader');

    foreach ($farmers as $farmer) {
      if (
        $farmer['location'] == 'board'
        || $farmer['location'] == 'A10_WoodenShed'
        || $farmer['location'] == 'A127_Lodger'
        || $farmer['location'] == 'B10_Caravan'
        || $farmer['location'] == 'C85_DenBuilder'
        || $farmer['location'] == 'D85_Reader'
        || str_contains($farmer['location'], 'turn')
      ) {
        continue;
      }

      if ($farmer['state'] == REMOVE) {
        $silentKill = Meeples::deleteResources([$farmer]);
        Notifications::silentKill($silentKill);
        continue;
      }

      if ($farmer['state'] == TEMP) {
        Meeples::returnToReserve($this, $farmer);
        continue;
      }

      foreach ($rooms as $room) {
        if ($this->countFarmerAtPos(['x' => $room['x'], 'y' => $room['y']]) == 0) {
          Meeples::moveToCoords($farmer['id'], 'board', [$room['x'], $room['y']]);
          continue;
        }
      }
    }

    $this->resetPlacedFarmers();

    // check if all farmers have been allocated. else we put in an existing room as they may have been born from urgent wish for children
    foreach (self::getAllFarmers() as $farmer) {
      if (
        $farmer['location'] == 'board'
        || $farmer['location'] == 'A10_WoodenShed'
        || $farmer['location'] == 'A127_Lodger'
        || $farmer['location'] == 'B10_Caravan'
        || $farmer['location'] == 'C85_DenBuilder'
        || $farmer['location'] == 'D85_Reader'
        || str_contains($farmer['location'], 'turn')
      ) {
        continue;
      }

      if ($woodenShed) {
        Meeples::moveToCoords($farmer['id'], 'A10_WoodenShed');
        $woodenShed = false;
        continue;
      }

      if ($caravan) {
        Meeples::moveToCoords($farmer['id'], 'B10_Caravan');
        $caravan = false;
        continue;
      }

      if ($denBuilder) {
        Meeples::moveToCoords($farmer['id'], 'C85_DenBuilder');
        $caravan = false;
        continue;
      }

      if ($reader) {
        Meeples::moveToCoords($farmer['id'], 'D85_Reader');
        $caravan = false;
        continue;
      }

      if ($lodger) {
        Meeples::moveToCoords($farmer['id'], 'A127_Lodger');
        $caravan = false;
        continue;
      }

      foreach ($rooms as $room) {
        if ($this->countFarmerAtPos(['x' => $room['x'], 'y' => $room['y']]) == 1) {
          Meeples::moveToCoords($farmer['id'], 'board', [$room['x'], $room['y']]);
          continue 2;
        }
      }

      foreach ($rooms as $room) {
        if ($this->countFarmerAtPos(['x' => $room['x'], 'y' => $room['y']]) == 2) {
          Meeples::moveToCoords($farmer['id'], 'board', [$room['x'], $room['y']]);
          continue;
        }
      }
    }
  }

  public function computeForbiddenCards()
  {
    $method = 'onPlayerComputeArgsPlaceFarmerRemoval';
    $cards = PlayerCards::getInLocation('inPlay')->filter(function ($card) use ($method) {
      return \method_exists($card, $method) && $card->getPlayer()->getId() == $this->id;
    });

    $forbidden = [];
    foreach ($cards as $card) {
      $forbidden = array_merge($forbidden, $card->$method($this));
    }

    return $forbidden;
  }

  public function computeModifiedPlacementConstraints()
  {
    $method = 'onPlayerComputeArgsPlaceFarmer';
    $cards = PlayerCards::getInLocation('inPlay')->filter(function ($card) use ($method) {
      return \method_exists($card, $method) && $card->getPlayer()->getId() == $this->id;
    });

    $customConstraints = [];
    foreach ($cards as $card) {
      $card = $card instanceof \AGR\Models\PlayerCard ? $card : PlayerCards::get($card);
      $cardModifiers = $card->$method($this);
      if ($cardModifiers != null && $cardModifiers != []) {
        $customConstraints = $this->mergeConstraints($customConstraints, $cardModifiers);
      }
    }

    $added = [];
    foreach ($customConstraints as $constraint) {
      $cId = $constraint['actionCardId'];
      $playerConstraint = $constraint['playerConstraint'] ?? null;
      $ignoreResources = $constraint['ignoreResources'] ?? false;
      $ignoreDoable = $constraint['ignoreDoable'] ?? false;

      $card = ActionCards::get($cId);
      if ($card->canBePlayed($this, $playerConstraint, $ignoreResources, $ignoreDoable)) {
        $added[] = $cId;
      }
    }

    return $added;
  }

  public function mergeConstraints($list, $newEntries)
  {
    foreach ($newEntries as $newEntry) {
      $found = false;
      $cId = $newEntry['actionCardId'];
      $ignoreResources = false;
      $playerConstraint = null;
      $ignoreDoable = false;

      foreach ($list as $entry) {
        if ($entry['actionCardId'] == $newEntry['actionCardId']) {
          $found = true;

          $ignoreResources = ($entry['ignoreResources'] ?? false) || ($newEntry['ignoreResources'] ?? false);
          $ignoreDoable = ($entry['ignoreDoable'] ?? false) || ($newEntry['ignoreDoable'] ?? false);
          $playerConstraint1 = $entry['playerConstraint'] ?? null;
          $playerConstraint2 = $newEntry['playerConstraint'] ?? null;
          if ($playerConstraint1 == 'dummy' || $playerConstraint2 == 'dummy') {
            $playerConstraint = 'dummy';
          } elseif ($playerConstraint1 != null) {
            $playerConstraint = $playerConstraint1;
          } elseif ($playerConstraint2 != null) {
            $playerConstraint = $playerConstraint2;
          }
        }
      }

      if (!$found) {
        $list[] = $newEntry;
      } else {
        $list[] = ['actionCardId' => $cId, 'ignoreResources' => $ignoreResources, 'playerConstraint' => $playerConstraint, 'ignoreDoable' => $ignoreDoable];
      }
    }

    return $list;
  }

  public function getHarvestCost()
  {
    $mult = Globals::isSolo() ? 3 : 2;
    $E30tax = $this->countFarmers(CHILD) * $this->hasPlayedCard('E30_ChildsToy');
    $E159discount = ($this->countFarmers(ADULT) + $this->countFarmers(CHILD)) * $this->hasPlayedCard('E159_OldMiser');

    return $this->countFarmers(ADULT) * $mult + $this->countFarmers(CHILD) + $E30tax - $E159discount;
  }

  public function breed($animalType = null, $source = null)
  {
    $meeples = [];
    $animals = $this->breedTypes();
    $created = [];
    Globals::setNumBredAnimals(0);

    foreach ($animals as $animal => $value) {
      if ($value == false) {
        continue;
      }
      if ($animalType != null && $animal != $animalType) {
        continue;
      }

      $created[] = $animalType;
      array_push($meeples, ...$this->createResourceInReserve($animal, 1));
    }

    if (count($meeples) > 0) {
      $reorganize = $this->checkAutoReorganize($meeples);
      if ($source != 'card' && (
        $this->hasPlayedCard('E90_DungCollector') || $this->hasPlayedCard('D115_FodderPlanter') || $this->hasPlayedCard('E134_Omnifarmer') ||
        $this->hasPlayedCard('E133_ChampionBreeder') || $this->hasPlayedCard('C71_Slurry')
      )) {
        Globals::setNumBredAnimals(count($created));
        $reorganize = true;
      }
      if ($this->hasPlayedCard('E53_BoarSpear') && $animalType == PIG) {
        $child = [
                 'action' => SPECIAL_EFFECT,
                 'args' => [
                   'cardId' => 'E53_BoarSpear',
                   'method' => 'increasePigConverted',
                   'args' => [1],
                 ],
               ];
        Engine::insertAsChild($child);
      }
      Notifications::breed($this, $meeples, $source);
      return $reorganize || $this->getAnimals('reserve')->count() > 0;
    }
    return false;
  }

  public function breedTypes()
  {
    $animals = $this->countAnimalsOnBoard();
    $canBreed = [];

    foreach ($animals as $animal => $value) {
      if ($value < 2) {
        if ($this->hasPlayedCard('E84_DollysMother') && $animal == SHEEP && $value == 1) {
          $canBreed[SHEEP] = true;
          continue;
        } else {
          $canBreed[$animal] = false;
          continue;
        }
      }
      $canBreed[$animal] = true;
    }

    return $canBreed;
  }

  /***************************
   ******* CARD SPECIFIC ******
   ***************************/

  public function updateObtainedResources($meeples)
  {
    $g = Globals::getObtainedResourcesDuringWork();
    if (!isset($g[$this->id])) {
      $g[$this->id] = [];
    }

    foreach ($meeples as $meeple) {
      $g[$this->id][$meeple['type']] = ($g[$this->id][$meeple['type']] ?? 0) + 1;
    }

    Globals::setObtainedResourcesDuringWork($g);
  }

  /** 
   * For B85 Farm Hand
   * Return true if this player can currently *use* the Farm Hand effect
   * The "once per game" rule is enforced by the card itself via its
   * hasBuiltFarmHandStable() flag, so this method stays generic.
   */
  public function canUseFarmHandStable()
  {
    if (!$this->hasPlayedCard('B85_FarmHand')) {
      return false;
    }

    if ($this->countStablesInReserve() <= 0) {
      return false;
    }

    if (empty($this->board()->getFarmHandCandidates())) {
      return false;
    }

    return true;
  }

}

