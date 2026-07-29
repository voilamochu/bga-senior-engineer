<?php

namespace ARK\Models;

use ARK\Actions\Bonuses\BuySponsor;
use ARK\Core\Stats;
use ARK\Core\Notifications;
use ARK\Core\Preferences;
use ARK\Managers\Actions;
use ARK\Managers\ZooCards;
use ARK\Managers\ActionCards;
use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Managers\Buildings;
use ARK\Core\Globals;
use ARK\Core\Engine;
use ARK\Core\Engine\AbstractNode;
use ARK\Helpers\Collection;
use ARK\Helpers\FlowConvertor;
use ARK\Helpers\Utils;

/*
 * Player: all utility functions concerning a player
 */

class Player extends \ARK\Helpers\DB_Model
{
  private ?ZooMap $map = null;
  protected string $table = 'player';
  protected string $primary = 'player_id';
  protected array $attributes = [
    'id' => ['player_id', 'int'],
    'no' => ['player_no', 'int'],
    'name' => 'player_name',
    'color' => 'player_color',
    'eliminated' => 'player_eliminated',
    'score' => ['player_score', 'int'],
    'scoreAux' => ['player_score_aux', 'int'],
    'zombie' => 'player_zombie',

    'money' => ['money', 'int'],
    'reputation' => ['reputation', 'int'],
    'appeal' => ['appeal', 'int'],
    'conservation' => ['conservation', 'int'],
    'xToken' => ['xtoken', 'int'],
    'mapId' => 'map_id',
  ];
  protected int $id;
  protected int $money;
  protected int $appeal;
  protected int $conservation;
  protected int $xToken;
  protected int $reputation;

  // Cached attribute
  public function map(): ?ZooMap
  {
    if ($this->map == null) {
      $mapId = $this->getMapId();

      if (is_null($mapId) || $mapId == '') {
        return null;
      }

      if ($mapId == '9a') $mapId = 9;
      if ($mapId == '10a') $mapId = 10;

      $className = '\ARK\Maps\Map' . $mapId;
      $this->map = new $className($this);
    }
    return $this->map;
  }

  public function hasAnytimeUsefulAction(): bool
  {
    // Map4 : sell one card for 3 money
    if ($this->canUseMap(4) && $this->getHand()->count() > 0) {
      return true;
    }
    // Pay for money grey bonus tile
    if ($this->hasKeptBonusTile(BONUS_SPONSOR_MONEY_MW) && BuySponsor::getPlayableSponsors($this, true) === true) {
      return true;
    }

    return false;
  }

  public function canUseMap(string|int $mapId): bool
  {
    if ($this->getMapId() != $mapId && $this->getMapId() != $mapId . "a") {
      return false;
    }
    return $this->map()->canUseEffect();
  }

  public function getMapStatus(): ?array
  {
    $map = $this->map();
    return is_null($map) ? null : $map->getStatus();
  }

  public function getUiData(?int $currentPlayerId = null): array
  {
    $data = parent::getUiData();
    $current = $this->id == $currentPlayerId;
    $data['hand'] = $current ? $this->getHand()->ui() : [];
    $data['handCount'] = $this->getHand()->count();
    $data['scoringHand'] = $current || Globals::isEnd() ? $this->getScoringHand()->ui() : [];
    $data['scoringHandCount'] = $this->getScoringHand()->count();
    $data['actionCards'] = $this->getActionCards()->ui();
    $data['icons'] = $this->countCardIcons();
    $data['sizes'] = $this->countAnimalsBySizes();
    $data['income'] = $this->getMoneyIncome();
    $data['newScore'] = $this->getNewScore();
    $data['mapStatus'] = $this->getMapStatus();
    $data['handStatus'] = $this->getHandStatus();
    $data['projectStrength'] = $this->getSupportProjectCost();

    // rename xToken into xtoken for ui
    $data['xtoken'] = $this->getXToken();
    unset($data['xToken']);

    // MAP 11
    if ($this->getMapId() == 11) {
      $data['stored'] = $current ? $this->getStoredCards()->ui() : [];
      $data['storedCount'] = $this->getStoredCards()->count();
    }

    return $data;
  }

  public function getPref($prefId)
  {
    return Preferences::get($this->id, $prefId);
  }

  public function getSupportProjectCost(): int
  {
    return $this->hasPlayedCard('S203_Veterinarian') ? 4 : 5;
  }

  public function getStat(string $name): mixed
  {
    $name = 'get' . \ucfirst($name);
    return Stats::$name($this->id);
  }

  public function canTakeAction(string $action, null|array|AbstractNode $ctx): bool
  {
    return Actions::isDoable($action, $ctx, $this);
  }


  public function countXTokens(): int
  {
    return $this->getXToken();
  }

  public function getIncomeFromAppeal(): int
  {
    // prettier-ignore
    $appealIncome = [5, 6, 7, 8, 9, 10, 10, 11, 11, 12, 12, 13, 13, 14, 14, 15, 15, 16, 16, 16, 17, 17, 17, 18, 18, 18, 19, 19, 19, 20, 20, 20, 21, 21, 21, 21, 22, 22, 22, 22, 23, 23, 23, 23, 24, 24, 24, 24, 25, 25, 25, 25, 26, 26, 26, 26, 27, 27, 27, 27, 27, 28, 28, 28, 28, 28, 29, 29, 29, 29, 29, 30, 30, 30, 30, 30, 31, 31, 31, 31, 31, 32, 32, 32, 32, 32, 33, 33, 33, 33, 33, 34, 34, 34, 34, 34, 35, 35, 35, 35, 35, 35, 36, 36, 36, 36, 36, 36];
    $money = $appealIncome[$this->getAppeal()] ?? 37;
    return $money;
  }

  public function getIncome(bool $ui = false): array
  {
    $bonuses = [];

    if ($ui) {
      // MONEY FROM APPEAL
      $money = $this->getIncomeFromAppeal();
      if ($money != 0) {
        $bonuses[] = [MONEY => $money, 'source' => clienttranslate('appeal income'), 'income' => true];
      }

      if (!is_null($this->map())) {
        // MONEY FROM KIOSKS
        $money = $this->map()->getKioskIncome();
        if ($money != 0) {
          $bonuses[] = [MONEY => $money, 'source' => clienttranslate('kiosk income'), 'income' => true];
        }

        // MAP INCOME BONUSES
        $incomeBonuses = $this->map()->getIncomeBonuses();
        $bonusToGet = array_diff(array_keys($incomeBonuses), $this->getOccupiedBonusesSpaces());
        foreach ($bonusToGet as $bonus) {
          $bonuses[] = $incomeBonuses[$bonus]['bonus'];
        }

        // MAP INCOME (EFFECT)
        $incomeMap = $this->map()->getIncome(true);
        foreach ($incomeMap as $bonus) {
          $bonus['source'] = $this->map()->getName();
          $bonus['income'] = true;
          $bonuses[] = $bonus;
        }
      }

      // INCOME FROM SPONSORS
      $sponsors = $this->getPlayedCards(CARD_SPONSOR);
      foreach ($sponsors as $sId => $sponsor) {
        $income = $sponsor->getIncome() ?? [];
        foreach ($income as $bonus) {
          $bonus['sourceId'] = $sId;
          $bonus['income'] = true;
          $bonuses[] = $bonus;
        }
      }
    } else {
      // MONEY FROM APPEAL
      $bonuses[] = [
        'action' => MONEY_INCOME,
        'pId' => $this->id,
        'args' => ['type' => 'appeal'],
        'source' => clienttranslate('appeal income'),
      ];

      // MONEY FROM KIOSKS
      $bonuses[] = [
        'action' => MONEY_INCOME,
        'pId' => $this->id,
        'args' => ['type' => 'kiosk'],
        'source' => clienttranslate('kiosk income'),
      ];

      // MAP INCOME BONUSES
      $incomeBonuses = $this->map()->getIncomeBonuses();
      $bonusToGet = array_diff(array_keys($incomeBonuses), $this->getOccupiedBonusesSpaces());
      foreach ($bonusToGet as $bonus) {
        $gain = $incomeBonuses[$bonus]['bonus'];
        $gain['income'] = true;
        $bonuses[] = $gain;
      }

      // MAP INCOME (EFFECT)
      if ($this->map()->getIncome() != []) {
        // map 13 => weird income
        if ($this->getMapId() == 13) {
          $bonuses = array_merge($bonuses, $this->map()->getIncome());
        }
        // other maps => money income
        else {
          $bonuses[] = [
            'action' => MONEY_INCOME,
            'pId' => $this->id,
            'args' => ['type' => 'map'],
            'source' => clienttranslate('map income'),
          ];
        }
      }

      // INCOME FROM SPONSORS
      $sponsors = $this->getPlayedCards(CARD_SPONSOR);
      foreach ($sponsors as $sId => $sponsor) {
        if (!is_null($sponsor->getIncome())) {
          $bonuses[] = [
            'action' => ACTIVATE_CARD,
            'pId' => $this->id,
            'args' => [
              'cardId' => $sId,
              'event' => ['method' => 'getIncome'],
            ],
          ];
        }
      }
    }

    return $bonuses;
  }

  public function getMoneyIncome(): int
  {
    $bonuses = $this->getIncome(true);
    $money = 0;
    foreach ($bonuses as $bonus) {
      $money += $bonus[MONEY] ?? 0;
    }
    return $money;
  }

  ////////////////////////////////////////
  //  ____       _   _
  // / ___|  ___| |_| |_ ___ _ __ ___
  // \___ \ / _ \ __| __/ _ \ '__/ __|
  //  ___) |  __/ |_| ||  __/ |  \__ \
  // |____/ \___|\__|\__\___|_|  |___/
  ////////////////////////////////////////
  public function getNewScore(): int
  {
    $conservationScore = $this->getNewScoreConservation();
    return $this->getAppeal() + $conservationScore;
  }

  public function getNewScoreConservation(): int
  {
    $conservation = $this->getConservation();
    // 2 slots for up to 10 conservation
    $targetAppeal = 114 - min(10, $conservation) * 2;
    if ($conservation > 10) {
      $targetAppeal -= ($conservation - 10) * 3;
    }
    return 100 - $targetAppeal;
  }

  public function updateScore(bool $endOfGame = false)
  {
    $newScore = $this->getNewScore();
    $score = Globals::isSolo() ? $newScore - 100 : $newScore;
    $this->setScore($score);
    Stats::setAppeal($this, $this->getAppeal());
    Stats::setConservation($this, $this->getConservation());
    Stats::setScore($this, $score);

    // End of game ?
    if ($endOfGame) {
      $this->setScoreAux($this->countSupportedProjects());
      // For solo mode, we need to increase the score by 1, as for BGA 0 is a loss

      // This game was part of the solo challenge
      if (Globals::isSolo() && (Globals::getSoloChallenge() > 0 || Globals::getSoloScore() != -999)) {
        // new setup for solo challenge
        $oldScore = Globals::getSoloScore();

        if ($oldScore == -999) {
          $oldScore = $score;
        } else {
          $oldScore += $score;
        }
        Globals::setSoloScore($oldScore);
      }

      // Solo game that is not a solo challenge
      if (Globals::isSolo() && Globals::getSoloChallenge() == -1) {
        Notifications::message(
          \clienttranslate(
            'Game result notice: In spring 2023, the game scoring has been changed in general and the solo mode scoring in particular was changed from a 0-point success to a 100-point success system. Due to technical restrictions, BGA still uses the old 0-point success system for now. Due to this fact 100 points are subtracted from your score at the end of the game and you achieve a victory if you score 0 or more points.'
          )
        );
        if ($score == 0) {
          $score++;
          $newScore++;
          $this->setScore($score);
          Notifications::message(clienttranslate('Score was increased by 1 to comply with BGA win policy'));
        }
      }

      Notifications::finalScoring(
        $this,
        $score,
        $newScore,
        $this->getAppeal(),
        $this->getConservation(),
        $this->getNewScoreConservation()
      );

      // Last game of a solo challenge
      if (Globals::getSoloChallenge() == 0 && Globals::getSoloScore() != -999) {
        // last challenge so we need to display the total score
        if ($oldScore == 0) {
          $oldScore++;
          Notifications::message(clienttranslate('Score was increased by 1 to comply with BGA win policy'));
        }

        $this->setScore($oldScore);
        if ($oldScore >= 0) {
          Notifications::message('Solo challenge: after 3 games, you won with a total score of ${score}', ['score' => $oldScore]);
        } else {
          Notifications::message('Solo challenge: after 3 games, you lost with a total score of ${score}', [
            'score' => $oldScore,
          ]);
        }
      }
    }
    return $newScore;
  }

  public function pay(int $n, bool $notif = true, null|string|ZooCard $source = null): int
  {
    if ($this->money < $n) {
      throw new \BgaVisibleSystemException('You don\'t have enough money to pay. Should not happen');
    }

    parent::incMoney(-$n);
    if ($notif) {
      Notifications::payMoney($this, $n, $this->money, $source);
    }

    return $this->money;
  }

  public function payXToken(int $n, bool $notif = true, null|string|ZooCard $source = null): int
  {
    if ($this->xToken < $n) {
      throw new \BgaVisibleSystemException('You don\'t have enough xtoken to pay. Should not happen');
    }

    parent::incXToken(-$n);
    if ($notif) {
      Notifications::payXToken($this, $n, $this->xToken, $source);
    }
    Stats::incXTokenUsed($this->id, $n);

    return $this->xToken;
  }

  public function incMoney(int $n, bool $notif = true, null|string|ZooCard $source = null, bool $byPassSponsors2 = false): array
  {
    if ($n == 0) {
      return [];
    }

    parent::incMoney($n);
    if ($notif) {
      Notifications::incMoney($this, $n, $this->money, $source);
    }
    Stats::incMoneyGained($this->id, $n);

    // SPONSORS2
    $activeCard = Globals::getActiveActionCard();
    if (
      !$byPassSponsors2
      && !Globals::isSponsors2Gained()
      && !empty($activeCard)
      && $activeCard['type'] == 'Sponsors2'
      && $activeCard['pId'] == $this->getActionCardOfType('Sponsors')->getPId() // Only works when I am the one gaining money
    ) {
      Globals::setSponsors2Gained(true);
      return [[MONEY => $activeCard['lvl'] == 2 ? 5 : 3, 'source' => clienttranslate("Sponsors2 effect")]];
    }

    return [];
  }

  public function incReputation(int $n, bool $notif = true, null|string|ZooCard $source = null): array
  {
    $previousRep = $this->reputation;

    // Max rep of 9 is not upgraded
    if (!$this->isCardUpgraded(CARDS) && $this->reputation + $n > 9) {
      $n = 9 - $this->reputation;
      Notifications::message(clienttranslate('${player_name} cannot exceed 9 reputation'), ['player' => $this]);
    }
    if ($n == 0) {
      return [];
    }

    $bonuses = [];

    // Check max rep of 15
    if ($this->reputation + $n > 15) {
      $extra = $n + $this->reputation - 15;
      $n = 15 - $this->reputation;

      $bonusMap = Globals::getBonusTiles();
      $slot = $bonusMap[MAX_REP_BONUS_SLOT] ?? null;
      // No bonus tile for max rep => just gain the appeal
      if (is_null($slot) || empty($slot)) {
        $bonuses[] = [APPEAL => $extra, 'source' => clienttranslate('maxing out reputation')];
      }
      // Bonus tile => player can choose to take it or not
      else {
        $bonus = $slot[0]['bonus'];
        $type = array_keys($bonus)[0];
        $takeBonusNode = [
          'action' => TAKE_BONUS,
          'args' => [
            'type' => $type,
            'n' => $bonus[$type],
            'remove' => MAX_REP_BONUS_SLOT . "-0",
            'income' => false,
            'source' => clienttranslate('maxing out reputation')
          ],
        ];


        // If gaining more than 1 rep, he also gains appeal once the tile is taken
        if ($extra > 1) {
          $takeBonusNode = [
            //            'type' => NODE_PARALLEL,
            'type' => NODE_SEQ,
            'childs' => [
              [
                'action' => GAIN,
                'args' => [APPEAL => $extra - 1],
                'source' => clienttranslate('maxing out reputation')
              ],
              $takeBonusNode
            ]
          ];
        }

        // XOR node to decide whether the player takes the bonus tile or not
        $bonuses[] = [
          'type' => NODE_XOR,
          'childs' => [
            [
              'action' => GAIN,
              'args' => [APPEAL => $extra],
              'source' => clienttranslate('maxing out reputation')
            ],
            $takeBonusNode
          ],
          'stateDescription' => [
            'description' => clienttranslate('${actplayer} must choose their bonus for maxing out reputation'),
            'descriptionmyturn' => clienttranslate('${you} must choose your bonus for maxing out reputation'),
            'args' => [],
          ],
        ];
      }
    }

    parent::incReputation($n);
    Stats::incReputation($this->id, $n);
    if ($notif) {
      Notifications::incReputation($this, $n, $this->reputation, $source = null);
    }

    // bonus if you reach specific places
    $bonusMap = [
      5 => [BONUS_UPGRADE_CARD => 1],
      8 => [BONUS_WORKER => 1],
      10 => [TAKE_IN_RANGE_OR_DECK => 1],
      11 => [CONSERVATION => 1],
      12 => [XTOKEN => 1],
      13 => [TAKE_IN_RANGE_OR_DECK => 1],
      14 => [CONSERVATION => 1],
      15 => [XTOKEN => 1],
    ];
    for ($i = $previousRep + 1; $i <= $this->reputation; $i++) {
      if (isset($bonusMap[$i])) {
        $bonuses[] = $bonusMap[$i];
      }
    }
    return $bonuses;
  }

  public function incAppeal(int $n, bool $notif = true, null|string|ZooCard $source = null): array
  {
    // Check max appeal of 113
    if ($this->appeal + $n > 113) {
      $n = 113 - $this->appeal;
      Notifications::message(clienttranslate('${player_name} cannot have more than 113 appeal'), ['player' => $this]);
    }
    if ($n == 0) {
      return [];
    }

    parent::incAppeal($n);
    if ($notif) {
      Notifications::incAppeal($this, $n, $this->appeal, $source);
    }
    Players::checkEndOfGamePlayer($this);

    return [];
  }

  public function incConservation(int $n, bool $notif = true, null|string|ZooCard $source = null): array
  {
    $previousConservation = $this->conservation;
    // Check max conservation of 41
    if ($this->conservation + $n > 41) {
      $n = 41 - $this->conservation;
      Notifications::message(clienttranslate('${player_name} cannot have more than 41 conservation'), ['player' => $this]);
    }
    if ($n == 0) {
      return [];
    }

    parent::incConservation($n);
    if ($notif) {
      Notifications::incConservation($this, $n, $this->conservation, $source);
    }
    Players::checkEndOfGamePlayer($this);

    // BONUSES
    // No need of this if already more than 10
    $childs = [];
    if ($previousConservation < 10) {
      $bonusMap = Globals::getBonusTiles();
      for ($i = $previousConservation + 1; $i <= $this->conservation; $i++) {
        $node = FlowConvertor::getConservationBonusesXORNode($i);
        if (!is_null($node)) {
          $childs[] = $node;
        }
      }
    }

    return $childs;
  }

  public function incXToken(int $n, bool $notif = true, null|string|ZooCard $source = null): array
  {
    if ($this->xToken + $n > 5) {
      $n = 5 - $this->xToken;
      Notifications::message(clienttranslate('${player_name} cannot have more than 5 x-tokens'), ['player' => $this]);
    }
    if ($n == 0) {
      return [];
    }

    parent::incXToken($n);
    if ($notif) {
      Notifications::incXToken($this, $n, $this->xToken, $source);
    }
    Stats::incXTokenGained($this->id, $n);

    return [];
  }

  ////////////////////////////////////////////////////////////////
  //     _        _   _                ____              _
  //    / \   ___| |_(_) ___  _ __    / ___|__ _ _ __ __| |___
  //   / _ \ / __| __| |/ _ \| '_ \  | |   / _` | '__/ _` / __|
  //  / ___ \ (__| |_| | (_) | | | | | |__| (_| | | | (_| \__ \
  // /_/   \_\___|\__|_|\___/|_| |_|  \____\__,_|_|  \__,_|___/
  ////////////////////////////////////////////////////////////////
  public function getActionCards(): Collection
  {
    return ActionCards::getOfPlayer($this->id);
  }

  public function getActionCardInPosition($position): ActionCard
  {
    return ActionCards::getInPosition($this->id, $position);
  }

  public function getActionCardOfType($type, bool $ignoreHypnosis = false): ?ActionCard
  {
    $card = $this->getActionCards()
      ->filter(function ($card) use ($type) {
        return $card->getActionType() == $type;
      })
      ->first();

    if (Globals::getEffectHypnosis() != 0 && !$ignoreHypnosis) {
      $hypnoCard = ActionCards::get(Globals::getEffectHypnosis());
      if ($hypnoCard->getActionType() == $type) {
        $card = $hypnoCard;
      }
    }

    return $card;
  }

  public function isCardUpgraded($cardType): bool
  {
    $card = $this->getActionCardOfType($cardType);
    return is_null($card) ? false : $card->getLevel() == 2;
  }


  public function getActionCardInUse(): ?ActionCard
  {
    foreach (self::getActionCards() as $cId => $card) {
      if ($card->getStatus() === 1) {
        return $card;
      }
    }
    return null;
  }

  public function countTokensOnCards(string $type): int
  {
    return Meeples::countTokensOnCards($this->id, $type);
  }

  public function moveActionCard(string $type, int $position = 1): Collection
  {
    $oCard = $this->getActionCardOfType($type);
    $initialPosition = $oCard->getStrength();
    // move all others cards on the right
    foreach ($this->getActionCards() as $cId => $card) {
      $loc = $card->getStrength();
      if ($position == 1 && $loc < $initialPosition) {
        $card->setStrength($loc + 1);
      } elseif ($position == 5 && $loc > $initialPosition) {
        $card->setStrength($loc - 1);
      }
    }
    $oCard->setStrength($position);
    return $this->getActionCards();
  }

  ///////////////////////////////////////////////////
  //  _____              ____              _
  // |__  /___   ___    / ___|__ _ _ __ __| |___
  //   / // _ \ / _ \  | |   / _` | '__/ _` / __|
  //  / /| (_) | (_) | | |__| (_| | | | (_| \__ \
  // /____\___/ \___/   \____\__,_|_|  \__,_|___/
  ///////////////////////////////////////////////////
  public function getHand(?string $type = null): Collection
  {
    return ZooCards::getHand($this->id)->filter(function ($card) use ($type) {
      return is_null($type) || $card->getType() == $type;
    });
  }

  public function getStoredCards(?string $type = null): Collection
  {
    return ZooCards::getFiltered($this->id, 'stored')->filter(function ($card) use ($type) {
      return is_null($type) || $card->getType() == $type;
    });
  }

  public function getHandLimit(): int
  {
    $baseLimit = 3;
    if ($this->hasUniversity(\UNIVERSITY_REP_HAND)) {
      $baseLimit += 2;
    }

    if ($this->hasKeptBonusTile(BONUS_SNAP_CARDLIMIT)) {
      $baseLimit += 1;
    }

    return $baseLimit;
  }

  public function getHandStatus(): array
  {
    $limit = $this->getHandLimit();
    $tooMuch = $this->getHand()->count() > $limit;
    return [$limit, $tooMuch];
  }

  public function getScoringHand(): Collection
  {
    return ZooCards::getScoringHand($this->id);
  }

  public function getPlayedCards(?string $type = null): Collection
  {
    return ZooCards::getPlayedCards($this->id, $type);
  }

  public function getNextRescueSlot(): ?int
  {
    $count = ZooCards::getRescuedCards($this->id)->count();
    return $count == 3 ? null : $count;
  }

  public function getPlayedAnimal(?string $icon = null): Collection
  {
    $animals = $this->getPlayedCards(\CARD_ANIMAL);
    if (!is_null($icon)) {
      $animals = $animals->filter(function ($animal) use ($icon) {
        return ($animal->getIcons()[$icon] ?? 0) > 0;
      });
    }
    return $animals;
  }

  // Useful for flocking
  public function getBiggestHerbivore(): int
  {
    $n = 0;
    foreach ($this->getPlayedAnimal(\HERBIVORE) as $animal) {
      $n = max($n, $animal->getEnclosureSize());
    }
    return $n;
  }

  public function hasPlayedCard($id): bool
  {
    return Zoocards::hasPlayedCard($this->id, $id);
  }

  public function getMaxFolderInRange(): int
  {
    $reputationMap = [1 => 1, 2 => 2, 3 => 2, 4 => 3, 5 => 3, 6 => 3, 7 => 4, 8 => 4, 9 => 4, 10 => 5, 11 => 5, 12 => 5];
    $maxFolder = $reputationMap[$this->getReputation()] ?? 6;
    return $maxFolder;
  }

  public function getCardsInReputationRange(?string $type = null): Collection
  {
    $maxFolder = $this->getMaxFolderInRange();
    return ZooCards::getPool($maxFolder)->filter(function ($card) use ($type) {
      return is_null($type) || $card->getType() == $type;
    });
  }

  public function countCardIcon(string $icon): int
  {
    $icons = $this->countCardIcons();
    return $icons[$icon] ?? 0;
  }

  public function countCardIcons(bool $onlyNonZero = false, ?array $toKeep = null): array
  {
    $icons = [];

    foreach (ALL_PREREQUISITES as $type) {
      $icons[$type] = 0;
    }

    $cards = $this->getPlayedCards();
    foreach ($cards as $aId => $card) {
      foreach ($card->getIcons() as $type => $n) {
        $icons[$type] += $n;
      }
    }

    // Universities
    foreach ($this->getUniversities() as $mId => $university) {
      foreach (UNIVERSITIES_ICONS[$university['type']] as $type => $n) {
        $icons[$type] += $n;
      }
    }

    // Partner zoos
    foreach ($this->getPartnerZoos() as $mId => $partner) {
      $continent = explode('-', $partner['type'])[1];
      $icons[$continent]++;
    }

    // MW : Aquarium
    if (!is_null($this->map())) {
      if ($this->map()->hasBuilding(SMALL_AQUARIUM)) {
        $icons[WATER]++;
      }
      if ($this->map()->hasBuilding(LARGE_AQUARIUM)) {
        $icons[WATER]++;
      }
    }


    if (!is_null($toKeep)) {
      foreach (array_keys($icons) as $type) {
        if (!in_array($type, $toKeep)) {
          unset($icons[$type]);
        }
      }
    }

    if ($onlyNonZero) {
      foreach (array_keys($icons) as $type) {
        if ($icons[$type] == 0) {
          unset($icons[$type]);
        }
      }
    }

    // Update stats
    if (!$onlyNonZero && is_null($toKeep)) {
      foreach (ALL_PREREQUISITES as $type) {
        if (!in_array($type, CONTINENTS_AND_TYPES) && !in_array($type, [WATER, ROCK, SCIENCE])) {
          continue;
        }

        $val = $icons[$type];
        $statName = 'getIcon' . $type;
        if (Stats::$statName($this) != $val) {
          $statName = 'setIcon' . $type;
          Stats::$statName($this, $val);
        }
      }
    }

    if (!Globals::isMarineWorld()) {
      unset($icons[SEA_ANIMAL]);
    }
    return $icons;
  }

  public function countAnimalsBySizes(): array
  {
    $counts = ['small' => 0, 'medium' => 0, 'large' => 0];
    foreach ($this->getPlayedAnimal() as $card) {
      if ($card->getType() != CARD_ANIMAL) continue;

      if ($card->isSmall()) $counts['small']++;
      else if ($card->isLarge()) $counts['large']++;
      else $counts['medium']++;
    }

    return $counts;
  }

  public function getReefAbilities(): array
  {
    $cards = $this->getPlayedAnimal()
      ->filter(fn($card) => $card->getLocation() != 'rescueStation'); // MAP10

    $abilities = [];

    foreach ($cards as $cId => $card) {
      if ($card->getType() == CARD_ANIMAL) {
        $bonus = $card->getReefAbility();
        if (!empty($bonus)) {
          $bonus['sourceId'] = $cId;
          $abilities[$cId] = $bonus;
        }
      }
    }
    return $abilities;
  }


  ////////////////////////////////////
  //  ____                        
  // | __ )  ___  _ __  _   _ ___ 
  // |  _ \ / _ \| '_ \| | | / __|
  // | |_) | (_) | | | | |_| \__ \
  // |____/ \___/|_| |_|\__,_|___/
  ////////////////////////////////////

  public function getKeptBonusTiles(): Collection
  {
    return Meeples::getKeptBonusTiles($this->id);
  }

  public function getKeptBonusTile(string $type): ?array
  {
    return $this->getKeptBonusTiles()->filter(fn($m) => $m['type'] == $type)->first();
  }

  public function hasKeptBonusTile(string $type): bool
  {
    return !is_null($this->getKeptBonusTile($type));
  }


  ////////////////////////////////////////////////////////////
  //     _                       _       _   _
  //    / \   ___ ___  ___   ___(_) __ _| |_(_) ___  _ __
  //   / _ \ / __/ __|/ _ \ / __| |/ _` | __| |/ _ \| '_ \
  //  / ___ \\__ \__ \ (_) | (__| | (_| | |_| | (_) | | | |
  // /_/   \_\___/___/\___/ \___|_|\__,_|\__|_|\___/|_| |_|
  ////////////////////////////////////////////////////////////

  /***********
   * WORKERS *
   ***********/

  public function hasAvailableWorkers(): bool
  {
    return Meeples::hasAvailableWorkers($this->id);
  }

  public function getAvailableWorker(): array
  {
    return Meeples::getAvailableWorker($this->id);
  }

  public function countAvailableWorkers(): int
  {
    return count(Meeples::getAvailableWorkers($this->id));
  }

  public function getWorkersInSupply(): Collection
  {
    return Meeples::getWorkersInSupply($this->id);
  }

  public function getNextWorkerInSupply(): ?array
  {
    return $this->getWorkersInSupply()->first();
  }

  public function countWorkersInSlot($slot): int
  {
    return Meeples::countWorkersInSlot($this->id, $slot);
  }

  public function getWorkersInSlot($slot): Collection
  {
    return Meeples::getWorkersInSlot($this->id, $slot);
  }

  public function countUsedWorkers(): int
  {
    return Meeples::countUsedWorkers($this->id);
  }

  public function countWorkersOnBoard(): int
  {
    return 3 - $this->getWorkersInSupply()->count();
  }

  /**
   * useWorkers: move the given number of worker into $location
   */
  public function useWorkers(int $nb, string $location): Collection
  {
    $moved = [];
    for ($i = 0; $i < $nb; $i++) {
      $id = $this->getAvailableWorker()['id'];
      Meeples::move($id, $location);
      $moved[] = $id;
    }
    return $nb == 0 ? new Collection([]) : Meeples::getMany($moved);
  }

  /**
   * gainWorker : take 1 worker from the supply and move it to the reserve
   */
  public function gainWorker(bool $notif = true): array
  {
    $new = self::getNextWorkerInSupply();
    if (is_null($new)) {
      return [];
    }

    // Notify new worker
    Meeples::move($new['id'], 'reserve');
    if ($notif) {
      Notifications::gainWorker($this, Meeples::get($new['id']));
    }
    Stats::incAssociationWorkers($this->id);

    // Map bonus for last worker
    $bonuses = $this->map()->getWorkersBonuses();
    $workersGained = $this->countWorkersOnBoard();
    $bonus = $bonuses[$workersGained] ?? null;
    return is_null($bonus) ? [] : ($bonus['multiple'] ?? [$bonus]);
  }

  /***************
   * PARTNER ZOO *
   ***************/

  public function hasPartnerZoo(?string $continent = null): bool
  {
    return Meeples::hasPartnerZoo($this->id, $continent);
  }

  public function countPartnerZoo(): int
  {
    return Meeples::countPartnerZoo($this->id);
  }

  public function getPartnerZoos(?string $continent = null): Collection
  {
    return Meeples::getPartnerZoos($this->id, $continent);
  }

  public function addPartnerZoo(int $meepleId): array
  {
    $index = $this->countPartnerZoo() + 1;
    $meeple = Meeples::moveZoo($meepleId, $this->id, $index);
    Notifications::addPartnerZoo($this, $meeple);

    $bonuses = [];
    $possibleBonuses = $this->map()->getPartnerZooBonuses();
    if (isset($possibleBonuses[$index])) {
      $bonuses[] = $possibleBonuses[$index];
    }

    // Linked bonuses
    $possibleBonuses = $this->map()->getFacPartnerZooLinkedBonuses();
    if (isset($possibleBonuses[$index]) && $this->countUniversities() >= $index) {
      $bonuses[] = $possibleBonuses[$index];
    }

    $continent = explode('-', $meeple['type'])[1];
    $icons = [$continent => 1];
    $bonuses = array_merge($bonuses, ZooCards::getIconsReaction($icons, $this));

    return $bonuses;
  }

  /****************
   * UNIVERSITIES *
   ****************/

  public function getUniversities(): Collection
  {
    return Meeples::getUniversities($this->id);
  }

  public function hasUniversity(string $type): bool
  {
    return Meeples::hasUniversity($this->id, $type);
  }
  public function hasSpecializedUniversity(): bool
  {
    foreach (UNIVERSITIES_ANIMALS as $univ) {
      if ($this->hasUniversity($univ)) {
        return true;
      }
    }
    return false;
  }

  public function countUniversities(): int
  {
    return Meeples::countUniversities($this->id);
  }

  public function addUniversity(int $meepleId): array
  {
    $index = $this->countUniversities() + 1;
    $meeple = Meeples::moveUniversity($meepleId, $this->id, $index);
    Notifications::addUniversity($this, $meeple);

    $bonuses = [];
    $possibleBonuses = $this->map()->getFacBonuses();
    if (isset($possibleBonuses[$index])) {
      $bonuses[] = $possibleBonuses[$index];
    }

    // Linked bonuses
    $possibleBonuses = $this->map()->getFacPartnerZooLinkedBonuses();
    if (isset($possibleBonuses[$index]) && $this->countPartnerZoo() >= $index) {
      $bonuses[] = $possibleBonuses[$index];
    }

    // Reputation gain from university
    $repGains = [
      \UNIVERSITY_REP_HAND => 1,
      \UNIVERSITY_SCIENCE_REP => 2,
    ];
    $repGain = $repGains[$meeple['type']] ?? 0;
    if ($repGain > 0) {
      $bonuses[] = [REPUTATION => $repGain, 'source' => clienttranslate(' from university')];
    }

    // Science icons
    $scienceIcons = [
      UNIVERSITY_SCIENCE_REP        => [SCIENCE => 1],
      UNIVERSITY_SCIENCE_SCIENCE    => [SCIENCE => 2],
      UNIVERSITY_SCIENCE_BIRD       => [SCIENCE => 1],
      UNIVERSITY_SCIENCE_PRIMATE    => [SCIENCE => 1],
      UNIVERSITY_SCIENCE_REPTILE    => [SCIENCE => 1],
      UNIVERSITY_SCIENCE_PREDATOR   => [SCIENCE => 1],
      UNIVERSITY_SCIENCE_HERBIVORE  => [SCIENCE => 1],
      UNIVERSITY_SCIENCE_SEA_ANIMAL => [SCIENCE => 1],
    ];
    $icons = $scienceIcons[$meeple['type']] ?? [];

    if (in_array($meeple['type'], UNIVERSITIES_ANIMALS)) {
      $map = [
        UNIVERSITY_SCIENCE_BIRD => BIRD,
        UNIVERSITY_SCIENCE_PRIMATE => PRIMATE,
        UNIVERSITY_SCIENCE_REPTILE => REPTILE,
        UNIVERSITY_SCIENCE_PREDATOR => PREDATOR,
        UNIVERSITY_SCIENCE_HERBIVORE => HERBIVORE,
        UNIVERSITY_SCIENCE_SEA_ANIMAL => SEA_ANIMAL
      ];
      $icon = $map[$meeple['type']];
      $icons[$icon] = 1;
      $bonuses[] = [SEARCH_CARD => $icon, 'optional' => true, 'source' => clienttranslate('University')];
    }

    $bonuses = array_merge($bonuses, ZooCards::getIconsReaction($icons, $this));
    return $bonuses;
  }

  /************
   * PROJECTS *
   ************/

  /**
   * getOccupiedBonusesSpaces : return the list of zoo map bonus spaces with meeples on it
   *  => these are the ones available when suppporting a new conservation project
   */
  public function getOccupiedBonusesSpaces()
  {
    return Meeples::getOccupiedBonusesSpaces($this->id);
  }

  /**
   * countCardTokens : return the number of tokens/cubes on a card
   *  => make sure a player cant support a project twice
   */
  public function countCardTokens(string $cardId): int
  {
    return count(Meeples::getTokensOnCard($this->id, $cardId));
  }

  /**
   * countSupportedProjects: return how many conservation project the player supported
   */
  public function countSupportedProjects(): int
  {
    $incomeBonuses = $this->map()->getBonusSpaces();
    $alreadySupported = array_diff(array_keys($incomeBonuses), $this->getOccupiedBonusesSpaces());
    return count($alreadySupported);
  }

  /**
   * getIconBonusForBaseProjects: return an array of tokens on sponsor card that can be used to reduce base project "cost"
   */
  public function getIconBonusForBaseProjects(): int
  {
    $bonus = 0;
    foreach (SPONSOR_CARD_WITH_ICON_BONUS as $cId) {
      if (!$this->hasPlayedCard($cId)) {
        continue;
      }

      $card = ZooCards::getSingle($cId);
      if (!$card->getTokensOnIt()->empty()) {
        $bonus++;
      }
    }

    // MW : gray bonus
    if ($this->hasKeptBonusTile(BONUS_ICON_SUPPORT_PROJECT)) {
      $bonus++;
    }

    return $bonus;
  }

  /**
   * useReductionToken: find a token and use it (take the card with most token on it)
   */
  public function useReductionToken(array $previousCIds = []): array
  {
    $tokens = [];
    foreach (array_diff(SPONSOR_CARD_WITH_ICON_BONUS, $previousCIds) as $cId) {
      if (!$this->hasPlayedCard($cId)) {
        continue;
      }

      $card = ZooCards::getSingle($cId);
      $meeples = $card->getTokensOnIt();
      if (count($meeples) > count($tokens)) {
        $tokens = $meeples;
      }
    }

    if (empty($tokens)) {
      // MW : GRAY BONUS
      $bonusTile = $this->getKeptBonusTile(BONUS_ICON_SUPPORT_PROJECT);
      if (!is_null($bonusTile)) {
        Meeples::move($bonusTile['id'], 'box');
        return $bonusTile;
      }

      throw new \BgaVisibleSystemException('Dont have any token left for base project icon bonus. Should not happen');
    }

    $token = $tokens->first();
    Meeples::destroy($token['id']);
    return $token;
  }
}
