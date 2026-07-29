<?php
namespace AGR\Managers;

use AGR\Core\Globals;
use AGR\Core\Game;
use AGR\Core\Stats;
use AGR\Helpers\DB_Manager;
use AGR\Helpers\Utils;
use AGR\Managers\Players;

/* Class to manage all the cards for Agricola */
// PlayerCards in contrast to ActionCards that are the cards on the board

class PlayerCards extends \AGR\Helpers\Pieces
{
  protected static $table = 'cards';
  protected static $prefix = 'card_';
  protected static $customFields = ['player_id', 'extra_datas'];
  protected static $autoIncrement = false;

  protected static function cast($card)
  {
    return self::getCardInstance($card['id'], $card);
  }

  public static function getCardInstance($id, $data = null)
  {
    $t = explode('_', $id);
    // First part before _ specify the deck and the numbering
    // Eg: Major_Fireplace1,  A24_ThreshingBoard, ...
    $prefix = $t[0] == 'Major' ? 'Major' : $t[0][0];
    $className = "\AGR\Cards\\$prefix\\$id";
    return new $className($data);
  }

  /* Creation of the cards */
  public static function setupNewGame($players, $options)
  {
    // Load list of cards
    include dirname(__FILE__) . '/../Cards/list.inc.php';

    // Keep only the supported cards and group them by type
    $cards = [
      MAJOR => [],
      MINOR => [],
      OCCUPATION => [],
    ];
    foreach ($cardIds as $cId) {
      $card = self::getCardInstance($cId);
      if ($card->isSupported($players, $options)) {
        $cards[$card->getType()][$card->getId()] = [
          'id' => $card->getId(),
          'location' => 'box',
        ];
      }
    }

    // Put the Major Improvements on the board
    foreach ($cards[MAJOR] as &$card) {
      $card['location'] = 'board';
    }

    // If Draft mode is disabled
    if (!Globals::isBeginner() && Globals::getDraftMode() != OPTION_SEED_MODE) {
      if (Globals::getDraftMode() == OPTION_DRAFT_DISABLED) {
        foreach ($players as $pId => $player) {
          self::drawCards($cards[OCCUPATION], $pId, 7, 'hand', 0);
          self::drawCards($cards[MINOR], $pId, 7, 'hand', 10);
        }
      } elseif (Globals::getDraftMode() == OPTION_DRAFT_LIVING_HAND && Globals::isSolo()) {
        // Solo Living Hand: no one to draft against, deal the starting hand directly
        foreach ($players as $pId => $player) {
          self::drawCards($cards[OCCUPATION], $pId, 4, 'hand', 0);
          self::drawCards($cards[MINOR], $pId, 4, 'hand', 10);
        }
      } else {
        $n = Game::get()->getDraftStartingNumberOfCards();
        $nMinor = $n == -1 ? count($cards[MINOR]) : $n;
        $nOccupation = $n == -1 ? count($cards[OCCUPATION]) : $n;
        $protocol = Game::get()->getDraftProtocol();
        foreach ($players as $pId => $player) {
          if ($protocol == OCCUPATION_FIRST) {
            self::drawCards($cards[MINOR], $pId, $nMinor, 'phase2');
            self::drawCards($cards[OCCUPATION], $pId, $nOccupation, 'draft');
          } elseif ($protocol == MINOR_FIRST) {
            self::drawCards($cards[MINOR], $pId, $nMinor, 'draft');
            self::drawCards($cards[OCCUPATION], $pId, $nOccupation, 'phase2');
          } else {
            self::drawCards($cards[MINOR], $pId, $nMinor, 'draft');
            self::drawCards($cards[OCCUPATION], $pId, $nOccupation, 'draft');
          }
        }
      }
    }

    // Campaign forbids drafts, so the hand dealt here is final and can be recorded for retries
    if (Globals::isCampaign()) {
      self::recordCampaignHand($cards);
    }

    // Merge cards to be created
    $oCards = array_merge(array_values($cards[MAJOR]), array_values($cards[MINOR]), array_values($cards[OCCUPATION]));

    // Remove cards still in the box (except Living Hand when we need to draw more)
    if (Globals::getDraftMode() != OPTION_DRAFT_LIVING_HAND) {
      $oCards = array_filter($oCards, function ($card) {
        return $card['location'] != 'box';
      });
    }

    // Create the cards
    self::create($oCards, null);
  }

  /*
   * Deal a solo-campaign game: like setupNewGame (draft disabled) but the permanent
   * occupations stay in play, are kept out of the pool, and shrink the new hand so the
   * player always starts with 7 occupations total (permanents + dealt).
   */
  public static function setupCampaignGame($players, $options, $permanentIds)
  {
    self::setupCampaignMajors($players, $options);

    include dirname(__FILE__) . '/../Cards/list.inc.php';

    $cards = [
      MINOR => [],
      OCCUPATION => [],
    ];
    foreach ($cardIds as $cId) {
      if (in_array($cId, $permanentIds)) {
        continue;
      }
      $card = self::getCardInstance($cId);
      $type = $card->getType();
      if (($type == OCCUPATION || $type == MINOR) && $card->isSupported($players, $options)) {
        $cards[$type][$card->getId()] = [
          'id' => $card->getId(),
          'location' => 'box',
        ];
      }
    }

    $nbOccupations = 7 - count($permanentIds);
    foreach ($players as $pId => $player) {
      if ($nbOccupations > 0) {
        self::drawCards($cards[OCCUPATION], $pId, $nbOccupations, 'hand', 0);
      }
      self::drawCards($cards[MINOR], $pId, 7, 'hand', 10);
    }

    self::recordCampaignHand($cards);

    $oCards = array_merge(array_values($cards[MINOR]), array_values($cards[OCCUPATION]));
    $oCards = array_filter($oCards, function ($card) {
      return $card['location'] != 'box';
    });

    self::create($oCards, null);
  }

  /* Record the dealt campaign hand so a failed game can be retried with exactly the same cards */
  protected static function recordCampaignHand($cards)
  {
    $hand = [OCCUPATION => [], MINOR => []];
    foreach ([OCCUPATION, MINOR] as $type) {
      foreach ($cards[$type] as $card) {
        if ($card['location'] == 'hand') {
          $hand[$type][] = $card['id'];
        }
      }
    }
    Globals::setCampaignDealtHand($hand);
  }

  /* Put the Major Improvements on the board for a campaign game */
  public static function setupCampaignMajors($players, $options)
  {
    include dirname(__FILE__) . '/../Cards/list.inc.php';

    $majors = [];
    foreach ($cardIds as $cId) {
      $card = self::getCardInstance($cId);
      if ($card->getType() == MAJOR && $card->isSupported($players, $options)) {
        $majors[] = ['id' => $card->getId(), 'location' => 'board'];
      }
    }
    self::create($majors, null);
  }

  /* Re-create a fixed set of occupations + minors directly into a player's hand (retry: same cards) */
  public static function dealSpecificHand($pId, $occIds, $minIds)
  {
    Globals::setCampaignDealtHand([OCCUPATION => array_values($occIds), MINOR => array_values($minIds)]);

    $cards = [];
    $state = 1;
    foreach ($occIds as $cId) {
      $cards[] = ['id' => $cId, 'location' => 'hand', 'player_id' => $pId, 'state' => $state++];
    }
    $state = 11;
    foreach ($minIds as $cId) {
      $cards[] = ['id' => $cId, 'location' => 'hand', 'player_id' => $pId, 'state' => $state++];
    }
    self::create($cards, null);
  }

  public static function drawCards(&$cards, $pId, $n, $location = 'hand', $stateBase = null)
  {
    $pool = array_filter($cards, function ($card) {
      return $card['location'] == 'box';
    });
    // array_rand returns a single key (not an array) when $n == 1
    $hand = (array) array_rand($pool, $n);
    $i = 1;
    foreach ($hand as $cId) {
      $cards[$cId]['location'] = $location;
      $cards[$cId]['player_id'] = $pId;
      if ($stateBase !== null) {
        $cards[$cId]['state'] = $stateBase + $i++;
      }
    }
  }

  /**************************
   * Draft related functions *
   ***************************/
  public static function addToSelection($card)
  {
    // Compute position to ensure occupations and minors are apart
    $cards = self::getInLocationQ('hand')
      ->wherePlayer($card->getPId())
      ->get();
    $pos = $cards->reduce(
      function ($carry, $c) use ($card) {
        return $card->getType() == $c->getType() ? max($carry, $c->getState()) : $carry;
      },
      $card->getType() == OCCUPATION ? 0 : 10
    );

    //    $pos = self::getExtremePosition(true, 'hand');
    self::move($card->getId(), 'selection', $pos + 1);
    return $pos + 1;
  }

  public static function removeFromSelection($card)
  {
    self::move($card->getId(), 'draft');
  }

  // Legacy: confirm all players at once (used by stApplyDraft)
  public static function confirmDraftSelections()
  {
    $cards = self::getInLocation('selection');
    self::moveAllInLocationKeepState('selection', 'hand');
    self::moveAllInLocation('draft', 'passing');
    return $cards;
  }

  // Async: confirm a single player's selection, returning the cards moved to hand
  public static function confirmDraftSelectionForPlayer($pId)
  {
    $cards = self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('card_location', 'selection')
      ->get();
    foreach ($cards as $card) {
      self::move($card->getId(), 'hand', $card->getState());
    }
    return $cards;
  }

  // Async: move a player's remaining draft cards to the next player's passing queue,
  // stamping card_state with $generation so piles can be promoted in FIFO order
  public static function queueDraftCardsForNextPlayer($pId, $generation)
  {
    $nextPId = Players::getNextId($pId);
    foreach (self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('card_location', 'draft')
      ->get() as $card) {
      self::DB()->update(
        [
          'card_location' => 'passing',
          'player_id' => $nextPId,
          'card_state' => $generation,
        ],
        $card->getId()
      );
    }
  }

  // Async: promote the oldest queued passing pile for a player into their active draft
  // Returns true if cards were promoted, false if the queue was empty
  public static function promoteOldestPassingPile($pId)
  {
    // Safety: refuse to promote if the player already has cards in draft (prevents pile merging)
    if (self::hasDraftCards($pId)) {
      return false;
    }

    $passing = self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('card_location', 'passing')
      ->get();

    if ($passing->empty()) {
      return false;
    }

    $minGen = $passing->reduce(fn($carry, $c) => min($carry, $c->getState()), PHP_INT_MAX);

    foreach ($passing as $card) {
      if ($card->getState() == $minGen) {
        self::DB()->update(
          [
            'card_location' => 'draft',
            'card_state' => 0,
          ],
          $card->getId()
        );
      }
    }

    return true;
  }

  // Async: does a player have an active draft pile right now?
  public static function hasDraftCards($pId)
  {
    return !self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('card_location', 'draft')
      ->get()
      ->empty();
  }

  // Async: count all cards still in play during the draft (used to detect completion)
  public static function countCardsInDraftLocations()
  {
    return self::getInLocation('draft')->count()
      + self::getInLocation('passing')->count()
      + self::getInLocation('selection')->count()
      + self::getInLocation('phase2')->count();
  }

  // Async: move a player's phase2 cards (second-phase type) into their active draft
  public static function activatePhase2Cards($pId)
  {
    foreach (self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('card_location', 'phase2')
      ->get() as $card) {
      self::move($card->getId(), 'draft');
    }
  }

  // Async: discard a player's remaining draft cards (used on the final turn instead of queuing)
  public static function discardPlayerRemainingDraftCards($pId)
  {
    foreach (self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('card_location', 'draft')
      ->get() as $card) {
      Stats::setNextDiscardedCard($pId, $card->getCode());
      self::moveToDiscardpile($card->getId());
    }
  }

  // Async: discard a player's queued passing cards (orphaned piles that arrive after last turn)
  public static function discardPlayerPassingCards($pId)
  {
    foreach (self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('card_location', 'passing')
      ->get() as $card) {
      Stats::setNextDiscardedCard($pId, $card->getCode());
      self::moveToDiscardpile($card->getId());
    }
  }

  public static function passCards()
  {
    // Pass non-selected cards to next player
    foreach (self::getInLocation('passing') as $card) {
      self::DB()->update(
        [
          'card_location' => 'draft',
          'player_id' => Players::getNextId($card->getPId()),
        ],
        $card->getId()
      );
    }
  }

  /* Remove a card from the game; under Living Hand the box is the live refill pool, so discard instead */
  public static function moveToBox($cardId)
  {
    if (Globals::getDraftMode() == OPTION_DRAFT_LIVING_HAND) {
      self::moveToDiscardpile($cardId);
      return;
    }
    self::DB()->update(['player_id' => null, 'card_location' => 'box'], $cardId);
  }

  public static function moveToDiscardpile($cardId)
  {
    self::DB()->update(
      [
        'player_id' => null,
        'card_location' => 'discardpile',
        'card_state' => 0,
      ],
      $cardId
    );
  }

  public static function discardRemainingDraftPools()
  {
    // Discard any cards still in draft/passing after the initial draft
    foreach (['draft', 'passing'] as $loc) {
      foreach (self::getInLocation($loc) as $card) {
        self::moveToDiscardpile($card->getId());
      }
    }
  }

  /*********************************
   * Living Hand refill draft pool *
   *********************************/

  public static function getLivingHandOffer($pId)
  {
    return self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('card_location', 'livingHandOffer')
      ->get();
  }

  public static function clearLivingHandOffer($pId)
  {
    // Discard previously offered cards (unowned)
    foreach (self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('card_location', 'livingHandOffer')
      ->get() as $card) {

      self::DB()->update(
        [
          'player_id' => null,
          'card_location' => 'discardpile',
          'card_state' => 0,
        ],
        $card->getId()
      );
    }
  }

  public static function createLivingHandOffer($pId, $type)
  {
    self::clearLivingHandOffer($pId);

    $cards = self::getSelectQuery()
      ->where('card_location', 'box')
      ->get();

    $cards = $cards->toArray();
    shuffle($cards);

    $picked = [];
    foreach ($cards as $card) {
      if ($card->getType() != $type) {
        continue;
      }
      $picked[] = $card;
      if (count($picked) == 2) {
        break;
      }
    }

    if (count($picked) < 2) {
      throw new \BgaVisibleSystemException('Not enough cards left in the box for Living Hand refill');
    }

    foreach ($picked as $i => $card) {
      self::DB()->update(
        [
          'player_id' => $pId,
          'card_location' => 'livingHandOffer',
          'card_state' => $i,
        ],
        $card->getId()
      );
    }

    return $picked;
  }

  public static function acceptLivingHandOffer($pId, $chosenId)
  {
    $offer = self::getLivingHandOffer($pId);
    if ($offer->empty()) {
      throw new \BgaVisibleSystemException('No Living Hand offer available');
    }

    foreach ($offer as $card) {
      if ($card->getId() == $chosenId) {
        // Compute position to keep occupations and minors grouped (same logic as addToSelection)
        $handCards = self::getInLocationQ('hand')->wherePlayer($pId)->get();
        $pos = $handCards->reduce(
          function ($carry, $c) use ($card) {
            return $card->getType() == $c->getType() ? max($carry, $c->getState()) : $carry;
          },
          $card->getType() == OCCUPATION ? 0 : 10
        );
        self::move($card->getId(), 'hand', $pos + 1);
      } else {
        self::moveToDiscardpile($card->getId());
      }
    }
  }

  public static function countHandCardsOfType($playerOrId, $type)
  {
    $player = is_numeric($playerOrId) ? Players::get((int) $playerOrId) : $playerOrId;

    return $player->getHand($type)->count();
  }

  /*
   * Add base filter to remove all action cards
   */
  protected static function addBaseFilter(&$query)
  {
    $query = $query->where('card_id', 'NOT LIKE', 'Action%');
  }

  public static function getUiData()
  {
    return self::getInLocationOrdered('board')
      ->merge(self::getInLocationOrdered('inPlay'))
      ->ui();
  }

  public static function getOfPlayer($pId)
  {
    return self::getSelectQuery()
      ->wherePlayer($pId)
      ->where('card_location', '<>', 'passing')
      ->where('card_location', '<>', 'discardpile')
      ->get();
  }

  public static function getAvailables($type = null)
  {
    $location = 'hand';
    if ($type == MAJOR) {
      $location = 'board';
    }
    $singleTypeCards =  self::getInLocation($location)->filter(function ($card) use ($type) {
      return !$card->isPlayed() && ($type == null || $card->getType() == $type);
    });
    if ($type == MAJOR) {
      $doubleTypeCards = self::getInLocation('hand')->filter(function ($card) use ($type) {
        return !$card->isPlayed() && $card->getType() != MAJOR && $card->hasType($type);
      });
      $allCards = $singleTypeCards->merge($doubleTypeCards);
      //print_r($allCards);
      return $allCards;
    }
    return $singleTypeCards;
  }

  /**
   * Get all the cards triggered by an event
   */
  public static function getListeningCards($event)
  {
    return self::getInLocation('inPlay')
      ->merge(self::getInLocation('hand'))
      ->filter(function ($card) use ($event) {
        return $card->isListeningTo($event);
      })
      ->getIds();
  }

  /**
   * Get all the cards have a given method
   */
  public static function getCardsHasMethod($method, $pId)
  {
    return self::getInLocation('inPlay')
      ->filter(function ($card) use ($method, $pId) {
        return \method_exists($card, $method) && $card->getPlayer()->getId() == $pId;
      })
      ->getIds();
  }

  /**
   * Get reaction in form of a PARALLEL node with all the activated card
   */
  public static function getReaction($event, $returnNullIfEmpty = true, $nodeType = NODE_PARALLEL)
  {
    $listeningCards = self::getListeningCards($event);
    if (empty($listeningCards) && $returnNullIfEmpty) {
      return null;
    }

    $childs = [];
    $passHarvest = Globals::isHarvest() ? Globals::getSkipHarvest() ?? [] : [];
    $passField = Globals::isFieldPhase() ? Globals::getSkipFieldAndBreed() ?? [] : [];
    $passBreed = Globals::isBreedPhase() ? Globals::getSkipFieldAndBreed() ?? [] : [];
    foreach ($listeningCards as $cardId) {
      if (
        in_array(
          self::get($cardId)
            ->getPlayer()
            ->getId(),
          $passHarvest
        )
      ) {
        continue;
      }

      if (
        in_array(
          self::get($cardId)
            ->getPlayer()
            ->getId(),
          $passField
        )
      ) {
        continue;
      }

      if (
        in_array(
          self::get($cardId)
            ->getPlayer()
            ->getId(),
          $passBreed
        )
      ) {
        continue;
      }

      $event['triggeredByOpponent'] = self::get($cardId)->getPlayer()->getId() != $event['pId'];
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

    return [
      'type' => $nodeType,
      'pId' => $event['pId'],
      'childs' => $childs,
      'eventMethod' => $event['method']
    ];
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

    // Auto-derived ordering: a card declares itself a "replacer" for a hook
    // by listing the method name in $replacesCostFor. Replacers produce
    // alternative cost trades/fees that subsequent modifiers must be able to
    // operate on, so add an edge from every replacer to every non-replacer
    // for this method. Case-insensitive match because the calling code mixes
    // 'ComputeCardCosts' (PlayerCard) with 'computeCostsConstruct' (Action).
    $replacers = [];
    $nonReplacers = [];
    $methodNameLower = strtolower($methodName);
    foreach ($cards as $cId => $card) {
      $hooks = array_map('strtolower', $card->replacesCostFor ?? []);
      if (in_array($methodNameLower, $hooks)) {
        $replacers[] = $cId;
      } else {
        $nonReplacers[] = $cId;
      }
    }
    foreach ($replacers as $rId) {
      foreach ($nonReplacers as $mId) {
        $edge = [$rId, $mId];
        if (!in_array($edge, $edges)) {
          $edges[] = $edge;
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
    $card = $card instanceof \AGR\Models\PlayerCard ? $card : self::get($card);
    $triggeredByOpponent = $args['triggeredByOpponent'] ?? false;
    $res = null;
    $listened = false;
    if ($player != null && $player->getId() == $card->getPId() && \method_exists($card, 'onPlayer' . $methodName)) {
      $n = 'onPlayer' . $methodName;
      $res = $card->$n($player, $args);
      $listened = true;
    } elseif (
      $player != null &&
      $player->getId() != $card->getPId() &&
      \method_exists($card, 'onOpponent' . $methodName) && $triggeredByOpponent
    ) {
      $n = 'onOpponent' . $methodName;
      $res = $card->$n($player, $args);
      $listened = true;
    } elseif (\method_exists($card, 'on' . $methodName)) {
      $n = 'on' . $methodName;
      $res = $card->$n($player, $args);
      $listened = true;
    } elseif ($card->isAnytime($args) && \method_exists($card, 'atAnytime')) {
      $res = $card->atAnytime($player, $args);
      $listened = true;
    }

    if ($throwErrorIfNone && !$listened) {
      if ($player != null && $player->getId() != $card->getPId() && !$triggeredByOpponent) {
        // in some special case this can happen, just ignore this. detail see #116
      } else {
        throw new \BgaVisibleSystemException(
          'Trying to apply effect of a card without corresponding listener : ' . $methodName . ' card ' . $card->getId() . ' player ' . $player->getId()
          //print_r(\debug_print_backtrace())
        );
      }
    }

    return $res;
  }

  public static function getFieldCards($pId)
  {
    return self::getInLocationQ('inPlay')
      ->wherePlayer($pId)
      ->get()
      ->filter(function ($card) {
        return $card->isField();
      });
  }

  /**
   * Generate/load seed
   */
  public static function getSeed()
  {
    $res = '';
    foreach (Players::getAll() as $player) {
      $ids = $player
        ->getHand()
        ->map(function ($card) {
          return $card->getDeck() . dechex($card->getNumber());
        })
        ->toArray();
      $res .= ($res != '' ? '|' : '') . implode('', $ids);
    }
    return $res;
  }

  public static function preSeedClear()
  {
    self::DB()
      ->delete()
      ->whereNotNull('player_id')
      ->run();
  }

  public static function setSeed($player, $seed)
  {
    // Extract the list of (deck, number) identifiers
    preg_match_all('/([A-E][0-9a-f]+)/', $seed, $out, PREG_PATTERN_ORDER);
    $cards = [];
    foreach ($out[1] as $card) {
      $deck = $card[0];
      $number = hexdec(\substr($card, 1));
      $cards[] = $deck . $number;
    }

    // Create the cards
    $values = [];
    $occCount = 0;
    $minorCount = 0;
    include dirname(__FILE__) . '/../Cards/list.inc.php';
    foreach ($cardIds as $cId) {
      $card = self::getCardInstance($cId);
      if (in_array($card->getDeck() . $card->getNumber(), $cards)) {
        $state = $card->getType() == OCCUPATION ? ++$occCount : 10 + ++$minorCount;
        $values[] = [
          'id' => $card->getId(),
          'location' => 'hand',
          'player_id' => $player->getId(),
          'state' => $state,
        ];
      }
    }
    self::create($values, null);
  }
}
