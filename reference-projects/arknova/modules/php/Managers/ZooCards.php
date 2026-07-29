<?php

namespace ARK\Managers;

use ARK\Core\Globals;
use ARK\Core\Game;
use ARK\Core\Notifications;
use ARK\Helpers\FlowConvertor;
use ARK\Helpers\Utils;
use ARK\Helpers\Log;
use ARK\Managers\Players;
use ARK\Actions\Animals;
use ARK\Actions\SearchCard;
use ARK\Core\Engine;
use ARK\Helpers\Collection;
use ARK\Models\ZooCard;

/* Class to manage all the cards for Ark nova */
// ZooCards in contrast to ActionCards that are the cards on the board

class ZooCards extends \ARK\Helpers\CachedPieces
{
  protected static string $table = 'cards';
  protected static string $prefix = 'card_';
  protected static array $customFields = ['player_id', 'extra_datas'];
  protected static bool $autoIncrement = false;
  protected static bool $autoremovePrefix = false;
  protected static bool $autoreshuffle = true;
  protected static array $autoreshuffleCustom = ['deck' => 'discard'];
  protected static ?Collection $datas = null;

  protected static function cast(array $card): ZooCard
  {
    return self::getCardInstance($card['card_id'], $card);
  }

  public static function getCardInstance(string $id, array $data = null): ZooCard
  {
    $t = explode('_', $id);
    // First part before _ specify the type and the numbering
    $prefixes = [
      'A' => 'Animals',
      'S' => 'Sponsors',
      'P' => 'Projects',
      'F' => 'FinalScoring',
    ];
    $prefix = $prefixes[$t[0][0]];
    $className = "\ARK\Cards\\$prefix\\$id";
    return new $className($data);
  }

  public static function getUiData(): array
  {
    return self::getPool()
      ->merge(self::getInLocationOrdered('inPlay'))
      ->merge(self::getInLocation('base_%'))
      ->merge(self::getInLocation('projects_%'))
      ->merge(self::getInLocation('rescueStation'))
      ->ui();
  }

  ///////////////////////////////////
  //  ____       _
  // / ___|  ___| |_ _   _ _ __
  // \___ \ / _ \ __| | | | '_ \
  //  ___) |  __/ |_| |_| | |_) |
  // |____/ \___|\__|\__,_| .__/
  //                      |_|
  ///////////////////////////////////

  /* Creation of the cards */
  public static function setupNewGame(array $players, array $options)
  {
    // Load list of cards
    include dirname(__FILE__) . '/../Cards/list.inc.php';

    $baseProjects = [];
    foreach ($cardIds as $cId) {
      $card = self::getCardInstance($cId);
      if (!$card->isSupported($players, $options)) {
        continue;
      }

      $type = $card->getType();
      $location = 'deck';
      if ($type == CARD_BASE_PROJECT) {
        $baseProjects[] = $cId;
        $location = 'projectDeck';
      } elseif ($type == CARD_SCORING) {
        $location = 'scoringDeck';
      }

      $cards[$cId] = [
        'id' => $cId,
        'location' => $location,
      ];
    }

    // Draw base projects on association board (projects_base_X)
    shuffle($baseProjects);
    $nbBaseProjects = count($players) == 4 ? 4 : 3;

    for ($i = 0; $i < $nbBaseProjects; $i++) {
      $value = array_pop($baseProjects);
      $cards[$value]['location'] = 'base_' . $i;
    }

    // Create the cards
    self::create($cards, null);
    self::shuffle('deck');
    self::shuffle('scoringDeck');
  }

  // Used once the players are done with choosing their zoo map
  public static function initialDraw()
  {
    foreach (Players::getAll() as $pId => $player) {
      $cards = self::draw($player, 8);
      $scoringCards = self::draw($player, 2, 'scoringDeck', 'scoringHand');
      Notifications::initialDraw($player, $cards, $scoringCards);

      // MAP 14
      if ($player->getMapId() == 14) {
        SearchCard::stSearchCardAux($player, SPONSOR_PERSON, clienttranslate('Map 14 effect'));
      }
    }
  }

  //////////////////////////
  //  ____             _
  // |  _ \ ___   ___ | |
  // | |_) / _ \ / _ \| |
  // |  __/ (_) | (_) | |
  // |_|   \___/ \___/|_|
  //////////////////////////

  /**
   * Return the cards in the pool with a pool number < $poolNumberLimit
   */
  public static function getPool(int $poolNumberLimit = 6, string $type = null): Collection
  {
    return self::getInLocationOrdered(['pool', '%'])->filter(function ($card) use ($poolNumberLimit, $type) {
      return $card->getPoolNumber() <= $poolNumberLimit && (is_null($type) || $card->getType() == $type);
    });
  }

  /**
   * Get the cards in the pool given within reputation range
   */
  public static function getInReputationRange(int $reputation): Collection
  {
    $limitMap = [0, 1, 2, 3, 3, 3, 4, 4, 4, 5, 5, 5, 6];
    return self::getPool($limitMap[$reputation] ?? 6);
  }

  public static function discard(string|array $cardIds, string $discard = 'discard'): array
  {
    $deletedMeeples = [];
    $assignedCardIds = [];
    if (!is_array($cardIds)) {
      $cardIds = [$cardIds];
    }

    // Marine world: if the card discarded is from the display & has player token, it's not discarded but put in hand
    foreach (self::getMany($cardIds) as $cId => $card) {
      if ($card->isMarked()) {
        $token = $card->getMark();
        Meeples::removeFromCard($cId);
        $card->setPId((int) $token['pId']);
        $card->setLocation('hand');
        $assignedCardIds[] = $cId;
        $deletedMeeples[] = $token;
      }

      // Move pouched cards if needed
      $pouchedCards = self::getInLocation($cId)->where('state', POUCHED);
      self::move($pouchedCards->getIds(), $discard);
    }

    $discardedCards = self::getMany(array_diff($cardIds, $assignedCardIds));
    self::move($discardedCards->getIds(), $discard);

    return [$discardedCards, self::getMany($assignedCardIds), $deletedMeeples];
  }

  /**
   * fillPool: slide the cards on the pool to the left and draw additional cards to fill the pool
   */
  public static function fillPool($ignoreWave = false)
  {
    if (self::countInLocation(['pool', '%']) == 6) {
      return false;
    }

    // Moving cards to fill in hole on their left
    $lastHole = null;
    for ($i = 1; $i <= 6; $i++) {
      $card = self::getInLocation(['pool', $i])->first();
      if (is_null($card) && is_null($lastHole)) {
        $lastHole = $i;
      } elseif (!is_null($card) && !is_null($lastHole)) {
        self::move($card->getId(), ['pool', $lastHole]);
        $lastHole++;
      }
    }

    // Drawing cards to fill remaining holes
    $cards = [];
    for ($i = $lastHole ?? 7; $i <= 6; $i++) {
      $cards[] = self::pickOneForLocation('deck', ['pool', $i]);
    }

    if (!empty($cards)) {
      $pool = self::getInLocation(['pool', '%']);
      Notifications::fillPool($cards, $pool);
    }

    // Ignore wave during setup
    if ($ignoreWave) {
      return;
    }

    $toDelete = 0;
    foreach ($cards as $card) {
      if ($card->isWave()) {
        $toDelete++;
      }
    }
    if ($toDelete > 0) {
      $cards = self::getPool($toDelete);
      list($discarded, $assigned, $meeples) = self::discard($cards->getIds());
      Notifications::discardPoolCardsWave($discarded);
      if (!$assigned->empty()) {
        Notifications::markAssign($assigned, $meeples);
      }
      self::fillPool();
    }
  }

  ////////////////////////////////////////////////////////
  //     _                       _       _   _
  //    / \   ___ ___  ___   ___(_) __ _| |_(_) ___  _ __
  //   / _ \ / __/ __|/ _ \ / __| |/ _` | __| |/ _ \| '_ \
  //  / ___ \\__ \__ \ (_) | (__| | (_| | |_| | (_) | | | |
  // /_/   \_\___/___/\___/ \___|_|\__,_|\__|_|\___/|_| |_|
  ////////////////////////////////////////////////////////

  /**
   * Conservation projects in the top row (= played by players)
   */
  public static function getAssociationProjects(): COllection
  {
    return self::getFiltered(null, 'projects_%');
  }

  /**
   * Conservation projects in the bottom row (= placed here at setup from base projects deck)
   */
  public static function getBaseProjects(): Collection
  {
    return self::getFiltered(null, 'base_%');
  }

  public static function insertProjectCard(string $cardId)
  {
    $nbPlayers = Players::count();
    $projectCards = ZooCards::getAssociationProjects();
    $maxProjects = max(2, $nbPlayers);

    // Too many projects played => remove the last one of the row
    if (count($projectCards) == $maxProjects) {
      $last = $maxProjects - 1; // Index start at 0
      $cardToDiscard = ZooCards::getFiltered(null, "projects_$last")->first();
      $cardToDiscard->setLocation('discard');
      $tokenIds = Meeples::removeFromCard($cardToDiscard->getId());
      Notifications::discardProject($cardToDiscard, $tokenIds);
    }

    // Shift them by one
    foreach (ZooCards::getAssociationProjects() as $cId => $card) {
      $nb = \explode('_', $card->getLocation())[1];
      $card->setLocation('projects_' . ++$nb);
    }
    // Insert the card
    $card = self::getSingle($cardId);
    $card->setLocation('projects_0');
    $card->setPId(null);
  }

  ///////////////////////////////////////////////
  //  ____                                 _
  // |  _ \ ___ _ __ ___  ___  _ __   __ _| |
  // | |_) / _ \ '__/ __|/ _ \| '_ \ / _` | |
  // |  __/  __/ |  \__ \ (_) | | | | (_| | |
  // |_|   \___|_|  |___/\___/|_| |_|\__,_|_|
  ///////////////////////////////////////////////

  /**
   * Add a card to a player hand
   */
  public static function addToHand(string $cId, int|object $pId): ZooCard
  {
    $card = self::getSingle($cId);
    $pId = is_int($pId) ? $pId : $pId->getId();

    // MARKED ?
    if ($card->isMarked()) {
      Engine::insertOrUpdateParallelChilds([$card->removeMarkForMoney($pId)]);
    }

    $card->setPId($pId);
    $card->setLocation('hand');

    return $card;
  }

  /**
   * Draw cards from the deck
   */
  public static function draw($player, $n = 1, $fromLocation = 'deck', $toLocation = 'hand')
  {
    $cards = self::pickForLocation($n, $fromLocation, $toLocation);
    foreach ($cards as $cId => &$c) {
      self::insertOnTop($cId, $toLocation);
      $c->setPId($player->getId());
    }
    return $cards;
  }

  /**
   * Draw card until we find one with the icone
   */
  public static function searchCard($player, $icon)
  {
    $checkSearchCard = function ($card, $icon) {
      // Skip projects
      if ($card->getType() == CARD_PROJECT) {
        return false;
      }

      // Search sponsor card
      if ($icon == CARD_SPONSOR) {
        return $card->getType() == CARD_SPONSOR;
      }
      // Search person sponsor card
      if ($icon == SPONSOR_PERSON) {
        return $card->getType() == CARD_SPONSOR && $card->isPerson();
      }

      return in_array($icon, array_keys($card->getIcons()));
    };

    $drawed = new Collection();
    $found = null;
    while (is_null($found)) {
      $card = self::draw($player)->first();
      if ($checkSearchCard($card, $icon)) {
        $found = $card;
      } else {
        $drawed[$card->getId()] = $card;
      }
    }

    if (!$drawed->empty()) {
      foreach ($drawed as $cId => $card) {
        self::insertAtBottom($cId, 'deck');
      }
    }

    return $found;
  }

  /**
   * Get all cards in hand of player matching the given type
   */
  public static function getHand($pId, $type = null)
  {
    return self::getFiltered($pId, 'hand')
      ->orderBy('state', 'ASC')
      ->filter(function ($card) use ($type) {
        return $type == null || $card->getType() == $type;
      });
  }

  /**
   * Get all scoring cards in hand of player
   */
  public static function getScoringHand($pId)
  {
    return self::getFiltered($pId, 'scoringHand');
  }

  /**
   * Get all cards played by player matching the given type
   */
  public static function getPlayedCards($pId, $type = null)
  {
    return self::getFiltered($pId, 'inPlay')
      ->merge(self::getRescuedCards($pId))
      ->filter(function ($card) use ($type) {
        return $type == null || $card->getType() == $type;
      });
  }

  public static function getRescuedCards($pId)
  {
    return self::getFiltered($pId, 'rescueStation');
  }

  /**
   * Check whether a player played a specific card
   */
  public static function hasPlayedCard($pId, $id)
  {
    $card = ZooCards::getSingle($id, false);
    return !is_null($card) && $card->isPlayed() && $card->getPId() == $pId;
  }

  ///////////////////////////////////////
  //  _____                 _
  // | ____|_   _____ _ __ | |_ ___
  // |  _| \ \ / / _ \ '_ \| __/ __|
  // | |___ \ V /  __/ | | | |_\__ \
  // |_____| \_/ \___|_| |_|\__|___/
  ///////////////////////////////////////

  /**
   * Get all the cards triggered by an event
   */
  public static function getListeningCards($event)
  {
    return self::getInLocation('inPlay')
      //->merge(self::getInLocation('hand')) Removing hand cards as shouldn't have listeners on hand
      ->filter(function ($card) use ($event) {
        return $card->isListeningTo($event);
      })
      ->getIds();
  }

  /**
   * Get reaction in form of an ARRAY of node that can be used to activate a card
   */
  public static function getReaction($event, $returnNullIfEmpty = true)
  {
    $listeningCards = self::getListeningCards($event);
    if (empty($listeningCards) && $returnNullIfEmpty) {
      return null;
    }

    $childs = [];
    foreach ($listeningCards as $cardId) {
      $childs[] = [
        'action' => ACTIVATE_CARD,
        'pId' => $event['pId'],
        'args' => [
          'cardId' => $cardId,
          'event' => $event,
        ],
      ];
    }

    if (empty($childs) && $returnNullIfEmpty) {
      return null;
    }

    return $childs;
  }

  /**
   * Get reaction to icons
   */
  public static function getIconsReaction($icons, $player, $splitImmediateAndAfter = false)
  {
    $cards = self::getInLocation('inPlay')->filter(function ($card) {
      return \method_exists($card, 'getIconsReaction');
    });
    $immediateChilds = [];
    $afterChilds = [];
    foreach ($cards as $card) {
      $bonuses = $card->getIconsReaction($icons, $player->getId() == $card->getPId());
      if (empty($bonuses)) {
        continue;
      }

      $child = [
        'action' => ACTIVATE_CARD,
        'pId' => $player->getId(),
        'args' => [
          'cardId' => $card->getId(),
          'event' => [
            'icons' => $icons,
            'method' => 'playIcons',
          ],
        ],
      ];

      if (in_array($card->getId(), ['S214_ExpertOnAfrica', 'S268_ConferenceOnEurope'])) {
        $child['afterFinishing'] = true;
        $afterChilds[] = $child;
      } else {
        $immediateChilds[] = $child;
      }
    }

    return $splitImmediateAndAfter ? [$immediateChilds, $afterChilds] : array_merge($immediateChilds, $afterChilds);
  }

  /**
   * Go trough all played cards to apply effects
   */
  public static function getAllCardsWithMethod($methodName)
  {
    return self::getInLocation('inPlay')->filter(function ($card) use ($methodName) {
      return \method_exists($card, 'on' . $methodName) ||
        \method_exists($card, 'onPlayer' . $methodName) ||
        \method_exists($card, 'onOpponent' . $methodName);
    });
  }

  public static function applyEffects($player, $methodName, &$args)
  {
    // Compute a specific ordering if needed
    $cards = self::getAllCardsWithMethod($methodName)->toAssoc();
    $nodes = array_keys($cards);
    $edges = [];
    $orderName = 'order' . $methodName;
    foreach ($cards as $cId => $card) {
      if (\method_exists($card, $orderName)) {
        foreach ($card->$orderName() as $constraint) {
          $cId2 = $constraint[1];
          if (!in_array($cId2, $nodes)) {
            continue;
          }
          $op = $constraint[0];

          // Add the edge
          $edge = [$op == '<' ? $cId : $cId2, $op == '<' ? $cId2 : $cId];
          if (!in_array($edge, $edges)) {
            $edges[] = $edge;
          }
        }
      }
    }
    $topoOrder = Utils::topological_sort($nodes, $edges);
    $orderedCards = [];
    foreach ($topoOrder as $cId) {
      $orderedCards[] = $cards[$cId];
    }

    // Apply effects
    $result = false;
    foreach ($orderedCards as $card) {
      $res = self::applyEffect($card, $player, $methodName, $args, false);
      $result = $result || $res;
    }
    return $result;
  }

  public static function applyEffect($card, $player, $methodName, &$args, $throwErrorIfNone = false)
  {
    $card = $card instanceof \ARK\Models\ZooCard ? $card : self::get($card);
    $res = null;
    $listened = true;
    $isPlayerEvent = $player->getId() == $card->getPId();

    if ($methodName == 'playIcons') {
      list($immediate, $after) = FlowConvertor::getFlow($card->getIconsReaction($args['icons'], $isPlayerEvent));
      $res =
        count($immediate) > 1
        ? [
          'type' => \NODE_PARALLEL,
          'childs' => $immediate,
        ]
        : (empty($immediate)
          ? $after[0] ?? null
          : $immediate[0]);
    } elseif ($methodName == 'getIncome') {
      $income = $card->getIncome();
      foreach ($income as &$bonus) {
        $bonus['income'] = true;
      }
      list($immediate, $after) = FlowConvertor::getFlow($income);
      $res =
        count($immediate) > 1
        ? [
          'type' => \NODE_PARALLEL,
          'childs' => $immediate,
        ]
        : (empty($immediate)
          ? $after[0] ?? null
          : $immediate[0]);
    } elseif ($player != null && $isPlayerEvent && \method_exists($card, 'onPlayer' . $methodName)) {
      $n = 'onPlayer' . $methodName;
      $res = $card->$n($player, $args);
    } elseif ($player != null && !$isPlayerEvent && \method_exists($card, 'onOpponent' . $methodName)) {
      $n = 'onOpponent' . $methodName;
      $res = $card->$n($player, $args);
    } elseif (\method_exists($card, 'on' . $methodName)) {
      $n = 'on' . $methodName;
      $res = $card->$n($player, $args);
    } else {
      $listened = false;
    }

    if ($throwErrorIfNone && !$listened) {
      throw new \BgaVisibleSystemException(
        'Trying to apply effect of a card without corresponding listener : ' . $methodName . ' ' . $card->getId()
      );
    }
    if (!is_null($res)) {
      Utils::tagTree($res, ['sourceId' => $card->getId()]);
    }

    return $res;
  }

  public static function getStatuses($player)
  {
    // Animal statuses
    $statuses = Animals::getPlayableStatuses($player);
    // Project statuses
    foreach ($player->getScoringHand() as $cId => $card) {
      $b = $card->getScoreBonus();
      $statuses[$cId] = [
        'qty' => $card->getQuantity(),
        'bonus' => is_null($b) ? 0 : $b[CONSERVATION],
      ];
    }
    // TODO : SPONSORS

    return $statuses;
  }
}
