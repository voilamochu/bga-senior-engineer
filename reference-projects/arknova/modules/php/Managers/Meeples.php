<?php

namespace ARK\Managers;

use ARK\Core\Stats;
use ARK\Core\Globals;
use ARK\Helpers\UserException;
use ARK\Helpers\Collection;

/* Class to manage all the meeples for ArkNova */

class Meeples extends \ARK\Helpers\Pieces
{
  protected static string $table = 'meeples';
  protected static string $prefix = 'meeple_';
  protected static array $customFields = ['type', 'player_id', 'x', 'y'];

  protected static function cast(array $meeple): array
  {
    return [
      'id' => (int) $meeple['id'],
      'location' => $meeple['location'],
      'pId' => $meeple['player_id'],
      'type' => $meeple['type'],
      'x' => $meeple['x'],
      'y' => $meeple['y'],
      'state' => $meeple['state'],
    ];
  }
  public static function getUiData(): array
  {
    return self::getAll()->filter(fn($m) => $m['location'] != 'box')->toArray();
  }

  ////////////////////////////////////
  //  ____       _
  // / ___|  ___| |_ _   _ _ __
  // \___ \ / _ \ __| | | | '_ \
  //  ___) |  __/ |_| |_| | |_) |
  // |____/ \___|\__|\__,_| .__/
  //                      |_|
  ////////////////////////////////////

  /* Creation of various meeples */
  public static function setupNewGame(array $players, array $options)
  {
    $meeples = [];
    // Association board: continents
    foreach ([AFRICA, EUROPE, ASIA, AMERICAS, AUSTRALIA] as $continent) {
      $meeples[] = ['type' => "partner-$continent", 'location' => 'association_3'];
      $meeples[] = ['type' => "partner-$continent", 'location' => 'box', 'nbr' => 3];
    }
    // Association board: universities
    foreach (UNIVERSITIES as $university) {
      $meeples[] = ['type' => $university, 'location' => 'association_4'];
      $meeples[] = ['type' => $university, 'location' => 'box', 'nbr' => 3];
    }
    // Marine world: add the generic unversity and animal universities on the side
    if (Globals::isMarineWorld()) {
      $meeples[] = ['type' => UNIVERSITY_SCIENCE_ANIMAL_GEN, 'location' => 'association_4'];
      foreach (UNIVERSITIES_ANIMALS as $type) {
        $meeples[] = ['type' => $type, 'location' => 'association_side'];
      }
    }

    // Association board: block base project and donations
    if (count($players) == 2) {
      foreach (ZooCards::getBaseProjects() as $project) {
        $t = explode('_', $project->getLocation());
        $meeples[] = ['type' => TOKEN, 'player_id' => 0, 'location' => $project->getId() . '_' . $t[1]];
      }

      foreach ([1, 3, 5] as $donationSlot) {
        $meeples[] = ['type' => TOKEN, 'player_id' => 0, 'location' => "association_0_$donationSlot"];
      }
    }

    // Solo mode => place the 7 cubes on the solo tile
    if (Globals::isSolo()) {
      for ($i = 1; $i <= 7; $i++) {
        $meeples[] = ['type' => TOKEN, 'location' => "solo_left_$i", 'player_id' => 0];
      }
    }

    return self::getMany(self::create($meeples));
  }

  public static function setupNextGame()
  {
    // deletion of all
    self::DB()
      ->delete()
      ->run();
    self::setupNewGame([1], []);
  }

  /**
   * Finish the setup of a player once he is done with map selection by creating tokens on that map
   */
  public static function setupPlayer($pId)
  {
    $meeples = [];
    // Token on bonus/income
    for ($i = 0; $i < 7; $i++) {
      $meeples[] = ['type' => TOKEN, 'location' => "bonus_$i", 'player_id' => $pId];
    }
    // Workers
    for ($i = 1; $i <= 3; $i++) {
      $meeples[] = ['type' => WORKER, 'location' => "supply_$i", 'player_id' => $pId];
    }
    $meeples[] = ['type' => WORKER, 'location' => 'reserve', 'player_id' => $pId];

    // Geographical zoo
    if (Players::get($pId)->getMapId() == 9) {
      foreach (CONTINENTS as $continent) {
        $meeples[] = ['type' => TOKEN, 'location' => "continent-area_$continent", 'player_id' => $pId];
      }
    }

    return self::getMany(self::create($meeples));
  }

  /////////////////////////////////
  //  ____                 _
  // | __ ) _ __ ___  __ _| | __
  // |  _ \| '__/ _ \/ _` | |/ /
  // | |_) | | |  __/ (_| |   <
  // |____/|_|  \___|\__,_|_|\_\
  /////////////////////////////////

  /**
   * Break cleanup : remove "interactive" tokens
   */
  public static function breakCleanupTokens()
  {
    $tokens = self::getSelectQuery()
      ->whereIn('type', [VENOM, MULTIPLIER, CONSTRICTION])
      ->get();

    foreach ($tokens as $tokId => $tok) {
      self::DB()->delete($tokId);
    }

    return $tokens;
  }

  /**
   * BreakReturnWorkers : put back workers in reserve
   */
  public static function breakReturnWorkers()
  {
    $workers = self::getSelectQuery()
      ->where('type', 'worker')
      ->where('meeple_location', 'LIKE', 'association_%')
      ->get()
      ->getIds();
    self::move($workers, 'reserve');

    return self::getMany($workers);
  }

  /**
   * breakRefill : refill universities and partner zoos
   */
  public static function breakRefill()
  {
    $meepleIds = [];

    ////////////////////////////////////////
    // TODO: remove at some point, legacy code
    if (self::countInLocation('box') < 2) {
      $continents = array_diff(CONTINENTS, self::getAvailableZoosContinents());
      foreach ($continents as $continent) {
        $meeples[] = ['type' => "partner-$continent", 'location' => 'association_3'];
      }

      // Universities
      $universities = array_diff(UNIVERSITIES, self::getAvailableUniversitiesTypes());
      foreach ($universities as $university) {
        $meeples[] = ['type' => $university, 'location' => 'association_4'];
      }

      $created = empty($meeples) ? new Collection([]) : self::getMany(self::create($meeples));

      // MW: "refresh" generic university
      if (Globals::isMarineWorld()) {
        $univ = self::getSelectQuery()->where('type', UNIVERSITY_SCIENCE_ANIMAL_GEN)->get()->first();
        if ($univ['location'] == 'box') {
          self::move($univ['id'], 'association_4');
          $univ['location'] = 'association_4';
          $created[$univ['id']] = $univ;
        }
      }

      return $created;
    }
    //////////////////////////////////////

    // Partner zoo
    $continents = array_diff(CONTINENTS, self::getAvailableZoosContinents());
    foreach ($continents as $continent) {
      // Is anyone missing that continent ?
      $found = false;
      foreach (Players::getAll() as $player) {
        if (!$player->hasPartnerZoo($continent)) {
          $found = true;
          break;
        }
      }
      if (!$found) {
        continue;
      }

      $meeple = self::getInLocationQ('box')->where('type', "partner-$continent")->get()->first();
      if (!is_null($meeple)) {
        self::move($meeple['id'], 'association_3');
        $meepleIds[] = $meeple['id'];
      }
    }

    // Universities
    $universities = array_diff(UNIVERSITIES, self::getAvailableUniversitiesTypes());
    foreach ($universities as $university) {
      // Is anyone missing that university ?
      $found = false;
      foreach (Players::getAll() as $player) {
        if (!$player->hasUniversity($university)) {
          $found = true;
          break;
        }
      }
      if (!$found) {
        continue;
      }


      $meeple = self::getInLocationQ('box')->where('type', $university)->get()->first();
      if (!is_null($meeple)) {
        self::move($meeple['id'], 'association_4');
        $meepleIds[] = $meeple['id'];
      }
    }

    // MW: "refresh" generic university
    if (Globals::isMarineWorld()) {
      $univ = self::getSelectQuery()->where('type', UNIVERSITY_SCIENCE_ANIMAL_GEN)->get()->first();
      if ($univ['location'] == 'box') {
        self::move($univ['id'], 'association_4');
        $meepleIds[] = $univ['id'];
      }
    }

    return self::getMany($meepleIds);
  }

  /**
   * getOccupiedIncomeSpaces : return the list of income/bonus spaces with a player's token on it (ie => no bonus)
   */
  public static function getOccupiedBonusesSpaces($pId)
  {
    $query = self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('type', TOKEN)
      ->where('meeple_location', 'LIKE', 'bonus_%');

    return $query
      ->get()
      ->map(function ($meeple) {
        $t = explode('_', $meeple['location']);
        return (int) $t[1];
      })
      ->toArray();
  }

  /**
   * endSoloTurn : move the first token on the left of solo card to the right
   *  -> return true if it triggers a break
   */
  public static function endSoloTurn()
  {
    for ($i = 1; $i <= 7; $i++) {
      $token = self::getInLocation('solo_left_' . $i)->first();
      if (is_null($token)) {
        continue;
      }

      $token['location'] = 'solo_right_' . $i;
      self::move($token['id'], 'solo_right_' . $i);
      return [$token, $i == 7];
    }

    return [null, false];
  }

  /**
   * breakClearSoloTokens : move tokens back on the left, and donate
   */
  public static function breakClearSoloTokens()
  {
    $tokenIds = [];

    // Move the topest one on first free donation
    $token = self::getInLocation('solo_right_%', null, ['location', 'ASC'])->first();
    $associationTokens = self::getTokensOnDonation();
    $position = min(7, count($associationTokens));
    self::move($token['id'], "association_0_$position");
    $tokenIds[] = $token['id'];

    // Move the other ones to the left
    $soloTokens = self::getInLocation('solo_right_%');
    foreach ($soloTokens as $tId => $token) {
      $position = \explode('_', $token['location'])[2];
      self::move($tId, "solo_left_$position");
      $tokenIds[] = $tId;
    }

    return self::getMany($tokenIds);
  }

  /////////////////////////////////////////////
  // __        __         _
  // \ \      / /__  _ __| | _____ _ __ ___
  //  \ \ /\ / / _ \| '__| |/ / _ \ '__/ __|
  //   \ V  V / (_) | |  |   <  __/ |  \__ \
  //    \_/\_/ \___/|_|  |_|\_\___|_|  |___/
  /////////////////////////////////////////////

  /**
   * AVAILABLE WORKERS => can be used on association board
   */
  public static function getAvailableWorkers($pId)
  {
    $query = self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('type', WORKER)
      ->where('meeple_location', 'reserve');

    return $query->get();
  }

  public static function getAvailableWorker($pId)
  {
    return self::getAvailableWorkers($pId)->first();
  }

  public static function hasAvailableWorkers($pId)
  {
    return self::getAvailableWorkers($pId)->count() > 0;
  }

  /**
   * WORKERS IN SUPPLY => can be unlocked by bonuses
   */
  public static function getWorkersInSupply($pId)
  {
    $query = self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('type', WORKER)
      ->where('meeple_location', 'LIKE', 'supply_%')
      ->orderBy('meeple_location', 'ASC');

    return $query->get();
  }

  /**
   * WORKERS ON ASSOCIATION BOARD
   */
  public static function countWorkersInSlot($pId, $slot)
  {
    return self::getWorkersInSlot($pId, $slot)
      ->count();
  }

  public static function getWorkersInSlot($pId, $slot): Collection
  {
    return self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('type', WORKER)
      ->where('meeple_location', 'association_' . $slot)->get();
  }

  public static function countUsedWorkers($pId)
  {
    return self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('type', WORKER)
      ->where('meeple_location', 'LIKE', 'association_%')
      ->count();
  }

  public static function moveBackWorker($id)
  {
    self::move($id, 'reserve');

    return self::getMany($id);
  }

  ///////////////////////////
  //  _____
  // |__  /___   ___  ___
  //   / // _ \ / _ \/ __|
  //  / /| (_) | (_) \__ \
  // /____\___/ \___/|___/
  ///////////////////////////
  public static function getPartnerZoo($pId, $continent)
  {
    if (is_null($continent)) {
      return null;
    }

    return self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('type', "partner-$continent")
      ->getSingle();
  }

  public static function getPartnerZoos($pId, $continent = null)
  {
    return self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('type', 'LIKE', is_null($continent) ? 'partner-%' : "partner-$continent")
      ->get();
  }

  public static function hasPartnerZoo($pId, $continent)
  {
    return !is_null(self::getPartnerZoo($pId, $continent));
  }

  public static function countPartnerZoo($pId)
  {
    $query = self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('type', 'LIKE', 'partner-%');
    return $query->get()->count();
  }

  public static function getAvailableZoos()
  {
    return self::getSelectQuery()
      ->where('type', 'LIKE', 'partner-%')
      ->where('meeple_location', 'LIKE', 'association_%')
      ->get();
  }

  public static function getAvailableZoosContinents()
  {
    return self::getAvailableZoos()
      ->map(function ($meeple) {
        return explode('-', $meeple['type'])[1];
      })
      ->toArray();
  }

  public static function moveZoo($id, $pId, $index)
  {
    self::DB()->update(
      [
        'player_id' => $pId,
        'meeple_location' => "partner_$index",
      ],
      $id
    );
    return self::getSingle($id);
  }

  public static function getZoosInBox()
  {
    return self::getSelectQuery()
      ->where('type', 'LIKE', 'partner-%')
      ->where('meeple_location', 'box')
      ->get();
  }


  //////////////////////////////////////////////////////////////
  //  _   _       _                    _ _   _
  // | | | |_ __ (_)_   _____ _ __ ___(_) |_(_) ___  ___
  // | | | | '_ \| \ \ / / _ \ '__/ __| | __| |/ _ \/ __|
  // | |_| | | | | |\ V /  __/ |  \__ \ | |_| |  __/\__ \
  //  \___/|_| |_|_| \_/ \___|_|  |___/_|\__|_|\___||___/
  //////////////////////////////////////////////////////////////

  public static function getUniversities($pId): Collection
  {
    return self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('type', 'LIKE', 'fac-%')
      ->get();
  }

  public static function hasUniversity($pId, $type)
  {
    return !is_null(
      self::getSelectQuery()
        ->wherePlayer($pId)
        ->where('type', $type)
        ->getSingle()
    );
  }

  public static function countUniversities($pId)
  {
    return self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('type', 'LIKE', 'fac-%')
      ->count();
  }

  public static function getAvailableUniversities($location = "4")
  {
    return self::getSelectQuery()
      ->where('type', 'LIKE', 'fac-%')
      ->where('meeple_location', 'association_' . $location)
      ->get();
  }

  public static function getAvailableUniversitiesTypes()
  {
    return self::getAvailableUniversities()
      ->map(function ($meeple) {
        return $meeple['type'];
      })
      ->toArray();
  }

  public static function moveUniversity($id, $pId, $index)
  {
    self::DB()->update(
      [
        'player_id' => $pId,
        'meeple_location' => "university_$index",
      ],
      $id
    );
    return self::getSingle($id);
  }

  public static function geUniversitiesInBox()
  {
    return self::getSelectQuery()
      ->where('type', 'LIKE', 'fac-%')
      ->whereIn('meeple_location', ['box', 'side'])
      ->get();
  }


  ///////////////////////////////////
  //  _____     _
  // |_   _|__ | | _____ _ __  ___
  //   | |/ _ \| |/ / _ \ '_ \/ __|
  //   | | (_) |   <  __/ | | \__ \
  //   |_|\___/|_|\_\___|_| |_|___/
  ///////////////////////////////////

  /**
   * addOnCard: add a token on an card
   */
  public static function addTokenOnCard($pId, $cardId, $state = 0)
  {
    $meeple = [
      'type' => TOKEN,
      'player_id' => $pId,
      'location' => $cardId,
      'state' => $state,
    ];

    return self::singleCreate($meeple);
  }

  /**
   * getTokens on a card : useful for project cards to know which slot are available
   */
  public static function getTokensOnCard($pId, $cardId)
  {
    return self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('meeple_location', 'LIKE', $cardId . '%')
      ->get();
  }

  /**
   * remove all the tokens on a card : useful when a project card is falling off the top row of association board
   */
  public static function removeFromCard($cardId)
  {
    $tokenIds = Meeples::getSelectQuery()
      ->where('meeple_location', 'LIKE', $cardId . '_%')
      ->orWhere('meeple_location', $cardId)
      ->get()
      ->getIds();

    Meeples::DB()
      ->delete()
      ->whereIn('meeple_id', $tokenIds)
      ->run();
    return $tokenIds;
  }

  /**
   * Move a token from a player board to the project card
   */
  public static function moveToProject($player, $bonusSpace, $cardId, $sId)
  {
    $token = self::getInLocationQ("bonus_$bonusSpace")
      ->wherePlayer($player->getId())
      ->get()
      ->first();

    $location = $cardId . '_' . $sId;
    self::move($token['id'], $location);
    $token['location'] = $location;
    return $token;
  }

  public static function countTokensOnCards($pId, $type)
  {
    return self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('type', $type)
      ->count();
  }

  /**
   * DONATION
   */

  public static function getTokensOnDonation()
  {
    $query = self::getSelectQuery()
      ->where('type', TOKEN)
      ->where('meeple_location', 'LIKE', 'association_0_%')
      ->orderBy('meeple_location', 'DESC');

    return $query->get();
  }

  public static function addTokenOnDonationSlot($player, $slot)
  {
    return Meeples::singleCreate([
      'location' => "association_0_$slot",
      'player_id' => $player->getId(),
      'type' => TOKEN,
    ]);
  }

  /**
   * MAP 9
   */
  public static function getTokenOnContinentArea($pId, $continent)
  {
    return self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('meeple_location', 'LIKE', "continent-area_$continent")
      ->get()
      ->first();
  }

  /************* UNCHECKED ******************/
  public static function getAssociationMeeple($type)
  {
    $query = self::getSelectQuery()
      ->where('type', $type)
      ->where('meeple_location', 'LIKE', 'association_%');
    return $query->get(true);
  }

  /////////////////////////////////////////////////////////////////////
  //     _        _   _                ____              _
  //    / \   ___| |_(_) ___  _ __    / ___|__ _ _ __ __| |___
  //   / _ \ / __| __| |/ _ \| '_ \  | |   / _` | '__/ _` / __|
  //  / ___ \ (__| |_| | (_) | | | | | |__| (_| | | | (_| \__ \
  // /_/   \_\___|\__|_|\___/|_| |_|  \____\__,_|_|  \__,_|___/
  /////////////////////////////////////////////////////////////////////

  /**
   * addOnActionCard: add a meeple on an action card
   */
  public static function addOnActionCard($type, $cardId, $pId, $state = 0)
  {
    $meeple = [
      'type' => $type,
      'player_id' => $pId,
      'location' => "actionCard_$cardId",
      'state' => $state,
    ];

    return self::singleCreate($meeple);
  }

  /**
   * getMeeplesOnActionCard on a card : useful for venom, multiplier
   */
  public static function getMeeplesOnActionCard($type, $cardId, $state = null)
  {
    $query = self::getSelectQuery()
      ->where('meeple_location', "actionCard_$cardId")
      ->where('type', $type);

    if (!is_null($state)) {
      $query = $query->where('meeple_state', $state);
    }

    return $query->get();
  }

  public static function destroy($meepleId)
  {
    $meeple = self::getSingle($meepleId);
    self::DB()->delete($meepleId);
    return $meeple;
  }


  ///////////////////////////////////
  //  ____                        
  // | __ )  ___  _ __  _   _ ___ 
  // |  _ \ / _ \| '_ \| | | / __|
  // | |_) | (_) | | | | |_| \__ \
  // |____/ \___/|_| |_|\__,_|___/
  ///////////////////////////////////

  public static function addBonusToPlayer($player, $type, $n)
  {
    $meeple = [
      'type' => $type,
      'player_id' => $player->getId(),
      'location' => "notepad",
      'state' => $n,
    ];

    return self::singleCreate($meeple);
  }

  public static function getKeptBonusTiles($pId)
  {
    return self::getFiltered($pId, 'notepad');
  }
}
