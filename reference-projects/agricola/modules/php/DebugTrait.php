<?php
namespace AGR;

use AGR\Core\Globals;
use AGR\Managers\Players;
use AGR\Managers\Meeples;
use AGR\Managers\Fences;
use AGR\Managers\Actions;
use AGR\Managers\PlayerCards;
use AGR\Managers\Campaign;
use AGR\Core\Engine;
use AGR\Core\Game;
use AGR\Models\PlayerBoard;
use AGR\Core\Notifications;
use AGR\Helpers\Utils;
use Bga\GameFramework\Actions\Debug;

trait DebugTrait
{
  /*
   * ============================
   * Studio-native debug actions
   * ============================
   */

/*  #[Debug(reload: true)]
  function debug_tp(): void
  {
    $player = Players::getCurrent();
    PlayerCards::setupNewGame(
      [
        $player->getId() => $player,
      ],
      [
        OPTION_COMPETITIVE_LEVEL => OPTION_COMPETITIVE_NORMAL,
        OPTION_ADDITIONAL_SPACES => OPTION_ADDITIONAL_SPACES_DISABLED,
        OPTION_DECK_CD => OPTION_DECK_ENABLED,
        OPTION_NEW_SET => OPTION_DECK_ENABLED,
        OPTION_EVEN_MORE_CARDS_SET => OPTION_DECK_DISABLED,
        OPTION_DECK_A => OPTION_DECK_ENABLED,
        OPTION_DECK_B => OPTION_DECK_ENABLED,
        OPTION_DECK_C => OPTION_DECK_ENABLED,
        OPTION_DECK_D => OPTION_DECK_ENABLED,
        OPTION_DRAFT => OPTION_DRAFT_FREE,
        OPTION_SCORING => OPTION_SCORING_ENABLED,
      ]
    );
  }
    */
  
  #[Debug(reload: false)]
  function debug_addResource(string $type, int $qty = 1): void
  {
    if (!\in_array($type, RESOURCES)) {
      throw new \BgaVisibleSystemException("Didn't recognise the resource: " . $type);
    }

    $player = Players::getCurrent();
    $meeples = $player->createResourceInReserve($type, $qty);
    Notifications::gainResources($player, $meeples);
    Engine::proceed();
  }

  #[Debug(reload: false)]
  function debug_addFResource(string $type, int $qty = 1): void
  {
    $player = Players::getCurrent();
    $turn = Globals::getTurn() + 1;
    $meepleIds = Meeples::createResourceInLocation($type, 'turn_' . $turn, $player->getId(), null, null, $qty);
    $meeples = Meeples::getMany($meepleIds);
    Notifications::placeMeeplesForFuture($player, [$type => $qty], [$turn], $meeples);
  }

  #[Debug(reload: false)]
  function debug_infResources(): void
  {
    $player = Players::getCurrent();
    $meeples = [];
    foreach ([WOOD, CLAY, REED, STONE, FOOD] as $res) {
      $meeples = array_merge($meeples, $player->createResourceInReserve($res, 8));
    }
    Notifications::gainResources($player, $meeples);
    Engine::proceed();
  }

  // Quick way to win a game for testing: play Soldier (if not already in play) + 100 wood + 100 stone
  #[Debug(reload: false)]
  function debug_winWithSoldier(): void
  {
    $player = Players::getCurrent();
    $pId = $player->getId();

    $soldier = 'C133_Soldier';
    $alreadyPlayed = self::getUniqueValueFromDB(
      "SELECT COUNT(*) FROM cards WHERE card_id = '$soldier' AND player_id = $pId AND card_location = 'inPlay'"
    );
    if ($alreadyPlayed == 0) {
      $this->debug_playCardByName($soldier);
    }

    $meeples = array_merge(
      $player->createResourceInReserve(WOOD, 100),
      $player->createResourceInReserve(STONE, 100)
    );
    Notifications::gainResources($player, $meeples);
    Engine::proceed();
  }

  #[Debug(reload: true)]
  function debug_allVisible(): void
  {
    self::DbQuery("UPDATE `cards` SET `card_state` = 1 WHERE `card_location` LIKE 'turn%'");
  }

  // Jump straight to the end-of-campaign review with 8 dummy wins, to test the "Congratulations"
  // screen and the campaign-complete summary modal without playing all 8 games. Click "End
  // campaign" on the review to see the summary modal.
  #[Debug(reload: true)]
  function debug_endCampaign(): void
  {
    $goals = Campaign::GOALS;
    $scores = [52, 60, 68, 75, 78, 82, 85, 86]; // each beats its goal; best score 86

    $results = [];
    foreach ($goals as $i => $goal) {
      $results[] = ['game' => $i + 1, 'goal' => $goal, 'score' => $scores[$i], 'hit' => true];
    }

    Globals::setCampaign(true);
    Globals::setCampaignGoal(67); // last fixed target → game 8 just won
    Globals::setCampaignResults($results);
    Globals::setPermanentOccupations(['d1', 'd2', 'd3', 'd4', 'd5', 'd6', 'd7']); // 7 → no permanent left to pick

    // Activate the solo player before the activeplayer review state (neutral-state jump first)
    $this->gamestate->jumpToState(\ST_GENERIC_NEXT_PLAYER);
    $this->gamestate->changeActivePlayer(Players::getAll()->first()->getId());
    $this->gamestate->jumpToState(\ST_CAMPAIGN_CHOOSE_PERMANENT);
  }


  #[Debug(reload: true)]
  function debug_goToRound(int $round): void
  {
    Globals::setTurn($round);
    self::DbQuery("UPDATE `cards` SET `card_state` = 1 WHERE `card_location` LIKE 'turn%'");
  }

  // Discard the current player's hand to the box, optionally only 'occupation' or 'minor' cards
  #[Debug(reload: true)]
  function debug_discardHand(string $type = ''): void
  {
    $pId = (int) Players::getCurrent()->getId();

    $n = 0;
    foreach (PlayerCards::getInLocation('hand') as $card) {
      if ($card->getPId() != $pId || ($type != '' && strcasecmp($card->getType(), $type) != 0)) {
        continue;
      }
      self::DbQuery("UPDATE cards SET player_id = NULL, card_location = 'box' WHERE card_id = '{$card->getId()}'");
      $n++;
    }

    Notifications::message("debug_discardHand: discarded $n card(s)");
  }

  #[Debug(reload: false)]
  function debug_playCardByName(string $query): void
  {
    $cid = $this->resolveCardIdFromQuery($query);
    if ($cid === null) {
      return;
    }

    if (strpos($cid, 'Action') === 0) {
      Notifications::message("debug_playCardByName: refusing action card id $cid");
      return;
    }

    $player = Players::getCurrent();
    $pId = (int) $player->getId();

    $exists = (int) self::getUniqueValueFromDB("SELECT COUNT(*) FROM cards WHERE card_id = '$cid'");

    if ($exists === 0) {
      self::DbQuery("
        UPDATE cards
        SET card_id = '$cid', player_id = $pId, card_location = 'inPlay'
        WHERE card_location NOT IN ('hand', 'inPlay', 'board')
          AND card_id NOT LIKE 'Action%'
        LIMIT 1
      ");
      $n = (int) self::getUniqueValueFromDB("SELECT ROW_COUNT()");
      if ($n === 0) {
        self::DbQuery("INSERT INTO cards (card_id, card_location, player_id) VALUES ('$cid', 'inPlay', $pId)");
      }
    } else {
      self::DbQuery("UPDATE cards SET player_id = $pId, card_location = 'inPlay' WHERE card_id = '$cid'");
    }

    $this->notifDebugCardInPlay($cid);
  }

  private function notifDebugCardInPlay(string $cardId): void
  {
    $player = Players::getCurrent();
    $pId = (int) $player->getId();

    $card = PlayerCards::get($cardId);
    $data = $card->jsonSerialize();
    $data['id'] = $data['id'] ?? $cardId;
    $data['pId'] = $pId;
    $data['location'] = 'inPlay';

    self::notifyPlayer($pId, 'debugCardInPlay', '', [
      'card' => $data,
    ]);
  }

  #[Debug(reload: false)]
  function debug_drawCardByName(string $query): void
  {
    $cid = $this->resolveCardIdFromQuery($query);
    if ($cid === null) {
      return;
    }

    if (strpos($cid, 'Action') === 0) {
      Notifications::message("debug_drawCardByName: refusing action card id $cid");
      return;
    }

    $this->drawCard($cid);
    $this->notifDebugCardToHand($cid);
  }

  /*
   * ============================
   * Bug repros
   * ============================
   */

  // Bug 142468: building a room via Hammer Crusher before a Plumber-discounted renovation
  // is blocked — stoneRenovationCheck prices the renovation without the discount because
  // its ctx-less Renovation has no actionCardId, so B128/D13 cost modifiers bail out.
  // Setup: clay house with 4 rooms; 3 stone + 3 clay + 2 reed; Plumber + Hammer Crusher
  // in play; an opponent has Building Tycoon (opponent AfterConstruct reaction, which is
  // what routes the flow through the check).
  // Trigger in browser: place a farmer on the Major Improvement space, buy Fireplace
  // (2 clay), then accept Plumber's renovation, take Hammer Crusher's room build
  // (5th room), place it and confirm.
  // Buggy: UserException "You must make sure you have enough resources to renovate..."
  // Expected: build resolves, opponent may react, then renovation for 3 stone + 1 reed.
  #[Debug(reload: true)]
  function debug_repro142468(): void
  {
    $player = Players::getCurrent();
    $pId = (int) $player->getId();

    $opponent = null;
    foreach (Players::getAll() as $p) {
      if ($p->getId() != $pId) {
        $opponent = $p;
        break;
      }
    }
    if ($opponent === null) {
      throw new \BgaVisibleSystemException('debug_repro142468 needs at least 2 players');
    }

    $this->debug_playCardByName('B128_Plumber');
    $this->debug_playCardByName('D14_HammerCrusher');
    $this->ensureCardInPlayForPlayer('D128_BuildingTycoon', (int) $opponent->getId());

    // Clay house with 4 rooms (renovation link clay -> stone)
    self::DbQuery("UPDATE meeples SET type = 'roomClay' WHERE player_id = $pId AND type = 'roomWood'");
    $board = $player->board();
    foreach ([['x' => 3, 'y' => 3], ['x' => 3, 'y' => 5]] as $room) {
      $board->addRoom('roomClay', $room);
    }

    // 3 stone: 5-room stone renovation costs 5 stone + 1 reed undiscounted, 3 stone + 1 reed with Plumber.
    // 5 clay + 2 reed: 2 clay buys Fireplace (the space's mandatory improvement); the rest plus
    // Hammer Crusher's gift of 2 clay + 1 reed exactly pays the 5th clay room (5 clay + 2 reed),
    // leaving 1 reed for the renovation fee.
    $meeples = array_merge(
      $player->createResourceInReserve(STONE, 3),
      $player->createResourceInReserve(CLAY, 5),
      $player->createResourceInReserve(REED, 2)
    );
    Notifications::gainResources($player, $meeples);
  }

  /*
   * ============================
   * Private helpers
   * ============================
   */

  private function ensureCardInPlayForPlayer(string $cardId, int $pId): void
  {
    $exists = (int) self::getUniqueValueFromDB("SELECT COUNT(*) FROM cards WHERE card_id = '$cardId'");

    if ($exists === 0) {
      self::DbQuery("
        UPDATE cards
        SET card_id = '$cardId', player_id = $pId, card_location = 'inPlay'
        WHERE card_location NOT IN ('hand', 'inPlay', 'board')
          AND card_id NOT LIKE 'Action%'
        LIMIT 1
      ");
      $n = (int) self::getUniqueValueFromDB("SELECT ROW_COUNT()");
      if ($n === 0) {
        self::DbQuery("INSERT INTO cards (card_id, card_location, player_id) VALUES ('$cardId', 'inPlay', $pId)");
      }
    } else {
      self::DbQuery("UPDATE cards SET player_id = $pId, card_location = 'inPlay' WHERE card_id = '$cardId'");
    }
  }


  private function notifDebugCardToHand(string $cardId): void
  {
    $player = Players::getCurrent();
    $pId = (int) $player->getId();

    $card = PlayerCards::get($cardId);
    $data = $card->jsonSerialize();

    $data['id'] = $data['id'] ?? $cardId;
    $data['pId'] = $pId;
    $data['location'] = 'hand';

    self::notifyPlayer($pId, 'debugCardToHand', '', [
      'card' => $data,
    ]);
  }
  
  private function ensureCardOwnedByCurrentPlayer(string $cardId): void
  {
    $player = Players::getCurrent();
    $pId = (int) $player->getId();

    $exists = (int) self::getUniqueValueFromDB("SELECT COUNT(*) FROM cards WHERE card_id = '$cardId'");

    if ($exists === 0) {
      self::DbQuery("
        UPDATE cards
        SET card_id = '$cardId', player_id = $pId
        WHERE card_location NOT IN ('hand', 'inPlay', 'board')
          AND card_id NOT LIKE 'Action%'
        LIMIT 1
      ");
      $n = (int) self::getUniqueValueFromDB("SELECT ROW_COUNT()");
      if ($n === 0) {
        self::DbQuery("INSERT INTO cards (card_id, card_location, player_id) VALUES ('$cardId', 'hand', $pId)");
      }
    } else {
      self::DbQuery("UPDATE cards SET player_id = $pId WHERE card_id = '$cardId'");
    }
  }

  private function debug_drawCardToHandDbOnly(string $id, bool $verbose = false): void
  {
    $player = Players::getCurrent();
    $pId = (int) $player->getId();

    if (strpos($id, 'Action') === 0) {
      throw new \BgaVisibleSystemException("debug_drawCardToHandDbOnly: refusing to draw action card id $id");
    }

    $exists = (int) self::getUniqueValueFromDB("SELECT COUNT(*) FROM cards WHERE card_id = '$id'");

    if ($exists === 0) {
      self::DbQuery("
        UPDATE cards
        SET card_id = '$id', player_id = $pId, card_location = 'hand'
        WHERE card_location NOT IN ('hand', 'inPlay', 'board')
          AND card_id NOT LIKE 'Action%'
        LIMIT 1
      ");
      $n = (int) self::getUniqueValueFromDB("SELECT ROW_COUNT()");
      if ($n === 0) {
        self::DbQuery("INSERT INTO cards (card_id, card_location, player_id) VALUES ('$id', 'hand', $pId)");
      }
    } else {
      self::DbQuery("UPDATE cards SET player_id = $pId, card_location = 'hand' WHERE card_id = '$id'");
      $n = (int) self::getUniqueValueFromDB("SELECT ROW_COUNT()");
    }

    if ($verbose) {
      $row = self::getObjectFromDB("SELECT card_id, player_id, card_location FROM cards WHERE card_id = '$id'");
    }
  }

  private function normaliseCardQuery(string $s): string
  {
    $s = strtolower($s);
    return preg_replace('/[^a-z0-9]+/', '', $s);
  }

  private function labelFromCardId(string $cardId): string
  {
    $parts = explode('_', $cardId, 2);
    $name = $parts[1] ?? $parts[0];

    $name = preg_replace('/([a-z])([A-Z])/', '$1 $2', $name);
    $name = preg_replace('/([A-Za-z])([0-9])/', '$1 $2', $name);

    return trim($name);
  }

  private ?array $debugCardLookupCache = null;

  private function buildCardLookup(): array
  {
    if ($this->debugCardLookupCache !== null) {
      return $this->debugCardLookupCache;
    }

    include dirname(__FILE__) . '/Cards/list.inc.php'; // defines $cardIds

    $lookup = [];
    $labels = [];

    foreach ($cardIds as $cid) {
      if (strpos($cid, 'Action') === 0) {
        continue;
      }

      $label = $this->labelFromCardId($cid);
      $labels[$cid] = $label;

      $keys = [
        $this->normaliseCardQuery($cid),
        $this->normaliseCardQuery($label),
      ];

      foreach ($keys as $k) {
        if ($k === '') {
          continue;
        }
        if (!isset($lookup[$k])) {
          $lookup[$k] = $cid;
        }
      }
    }

    $this->debugCardLookupCache = [$lookup, $labels];
    return $this->debugCardLookupCache;
  }

  private function resolveCardIdFromQuery(string $query): ?string
  {
    [$lookup, $labels] = $this->buildCardLookup();
    $k = $this->normaliseCardQuery($query);

    if ($k === '') {
      Notifications::message("resolveCardIdFromQuery: empty query");
      return null;
    }

    if (isset($lookup[$k])) {
      $cid = $lookup[$k];
      Notifications::message("resolveCardIdFromQuery: exact '$query' -> $cid (" . ($labels[$cid] ?? '') . ")");
      return $cid;
    }

    $best = [];
    foreach ($lookup as $key => $cid) {
      $d = levenshtein($k, $key);
      $best[] = [$d, $cid, $labels[$cid] ?? ''];
    }
    usort($best, fn($a, $b) => $a[0] <=> $b[0]);
    $best = array_slice($best, 0, 5);

    $lines = [];
    foreach ($best as [$d, $cid, $label]) {
      $lines[] = "$cid ($label) d=$d";
    }
    Notifications::message("resolveCardIdFromQuery: no exact match for '$query'. Best: " . implode(" | ", $lines));

    if (count($best) > 0 && $best[0][0] <= 3) {
      $cid = $best[0][1];
      Notifications::message("resolveCardIdFromQuery: accepted closest -> $cid");
      return $cid;
    }

    Notifications::message("resolveCardIdFromQuery: not close enough, no card drawn.");
    return null;
  }

  public function noopEngineResolved(): void
  {
    // No-op
  }

  /*
   * ============================
   * legacy
   * ============================
   * Old entrypoints and superseded tooling. Keep them for reference,
   * but prefer the debug_* actions above in Studio.
   */

 // function tp() { $this->debug_tp(); }
  function addResource($type, $qty = 1) { $this->debug_addResource((string) $type, (int) $qty); }
  function addFResource($type, $qty = 1) { $this->debug_addFResource((string) $type, (int) $qty); }
  function infResources() { $this->debug_infResources(); }
  function allVisible() { $this->debug_allVisible(); }
  function gotoRound($round) { $this->debug_gotoRound((int) $round); }

  // superseded by debug_drawCardByName
  function drawCard($cardId) { $this->debug_drawCardToHandDbOnly((string) $cardId, true); }
  function playCard($cardId) { $this->debug_playCardByName((string) $cardId); }

  // Combo checker legacy
  public function checkCombos()
  {
    $this->gamestate->jumpToState(\ST_CHECK_COMBOS);
  }

  public function getArgsCheckCombos($methodName)
  {
    include dirname(__FILE__) . '/Cards/list.inc.php';
    $cards = [];
    foreach ($cardIds as $cId) {
      $card = PlayerCards::getCardInstance($cId);
      if (\method_exists($card, 'onPlayer' . $methodName)) {
        $cards[$cId] = $card;
      }
    }

    $order = [];
    $edges = [];
    $orderName = 'order' . $methodName;

    foreach ($cards as $cId => $card) {
      if (\method_exists($card, $orderName)) {
        foreach ($card->$orderName() as $constraint) {
          $cId2 = $constraint[1];
          $op = $constraint[0];

          if (isset($order[$cId][$cId2]) && $order[$cId][$cId2] != $op) {
            throw new \feException('Incompatible ordering on following cards :' . $cId . ' ' . $cId2);
          }
          $order[$cId][$cId2] = $op;

          $symOp = $op == '<' ? '>' : '<';
          if (isset($order[$cId2][$cId]) && $order[$cId2][$cId] != $symOp) {
            throw new \feException('Incompatible ordering on following cards :' . $cId . ' ' . $cId2);
          }
          $order[$cId2][$cId] = $symOp;

          $edges[] = [$op == '<' ? $cId : $cId2, $op == '<' ? $cId2 : $cId];
        }
      }
    }

    $nodes = array_keys($cards);
    $topoOrder = Utils::topological_sort($nodes, $edges);

    for ($i = 0; $i < count($cards); $i++) {
      for ($j = $i + 1; $j < count($cards); $j++) {
        $cId = $topoOrder[$i];
        $cId2 = $topoOrder[$j];
        if (isset($order[$cId][$cId2]) && $order[$cId][$cId2] != '<') {
          throw new \feException('Incompatible ordering after closure on following cards :' . $cId . ' ' . $cId2);
        }
      }
    }

    $orderedCards = [];
    foreach ($topoOrder as $cId) {
      $orderedCards[] = $cards[$cId];
    }

    return [
      'cards' => $orderedCards,
      'order' => $order,
    ];
  }

  public function argsCheckCombos()
  {
    return [
      'construct' => $this->getArgsCheckCombos('ComputeCostsConstruct'),
      'renovate' => $this->getArgsCheckCombos('ComputeCostsRenovation'),
      'improvement' => $this->getArgsCheckCombos('ComputeCardCosts'),
    ];
  }
}
