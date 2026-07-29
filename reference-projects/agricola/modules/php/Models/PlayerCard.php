<?php
namespace AGR\Models;

use AGR\Core\Engine;
use AGR\Core\Globals;
use AGR\Core\Notifications;
use AGR\Core\Stats;
use AGR\Helpers\Utils;
use AGR\Managers\ActionCards;
use AGR\Managers\Campaign;
use AGR\Managers\Meeples;
use AGR\Managers\PlayerCards;
use AGR\Managers\Players;
use AGR\Managers\Scores;
use AGR\States\TurnTrait;

/*
 * PlayerCard: parent of Major/minor improvements and Occupation
 */

class PlayerCard extends AbstractCard
{
  protected $implemented = true; // For DEV only
  protected $isArtifexOrBubulcus = false;
  protected $isCorbariusOrDulcinaria = false;

  protected $deck = null;
  protected $number = null;
  protected $players = null;
  protected $bannedStrong1or2p = false;
  protected $bannedStrong3or4p = false;
  protected $bannedWeak1or2p = false;
  protected $bannedWeak3or4p = false;
  protected $bannedLiving = false;

  protected $desc = []; // UI
  protected $costText = ''; // UI
  protected $prerequisite = ''; // UI
  protected $holder = false; // Is holding resources ?
  protected $animalHolder = false; // Is holding animals ?
  protected $field = false; // Is a field card ?

  protected $vp = 0;
  protected $extraVp = false;
  protected $cost = [];
  protected $conditionalCost = null;
  protected $fee = null;
  protected $exchanges = [];
  protected $category = null;
  protected $type = null;
  protected $stats = null;
  protected $infobox = null;
  protected $marked = null;
  protected $usedText = null; // Custom label for the "used" stat line (null = "Number of times used")

  protected $passing = false; // only for Minor
  protected $bonusStoneRoom = false; // for C30_HalfTimberedHouse

  protected $actionCard = false; // for C104_Collector
  protected $flow = null;

  protected $isCookery = false;
  protected $isBakingImprovement = false;
  protected $sharedScoring = false;

  public $replacesCostFor = []; //see PlayerCards::applyEffects

  public function jsonSerialize()
  {
    $data = parent::jsonSerialize();
    $data['deck'] = $this->deck;
    $data['players'] = $this->players;
    $data['desc'] = $this->desc;
    $data['vp'] = $this->vp;
    $data['extraVp'] = $this->extraVp;
    $data['bonusVp'] = $this->getBonusScore() > 0 ? $this->getBonusScore() : '';
    $data['prerequisite'] = $this->prerequisite;
    $data['costs'] = $this->costs ?? [$this->cost];
    $data['conditionalCost'] = $this->conditionalCost;
    $data['costText'] = $this->costText;
    $data['fee'] = $this->fee;
    $data['type'] = $this->type;
    $data['category'] = $this->category;
    $data['numbering'] = $this->deck . str_pad($this->number, 3, '0', STR_PAD_LEFT);
    $data['holder'] = $this->holder;
    $data['animalHolder'] = $this->animalHolder;
    $data['field'] = $this->field;
    $data['actionCard'] = $this->actionCard;
    $data['stats'] = $this->getStats();
    $data['infobox'] = $this->getInfobox();
    $data['marked'] = $this->isMarked();
    $data['usedText'] = $this->usedText;
    $data['sharedScoring'] = $this->sharedScoring;
    return $data;
  }

  public function isSupported($players, $options)
  {
    // Check number of players
    $nPlayers = count($players);
    $checkPlayer =
      $this->players === null ||
      (is_array($this->players) && in_array($nPlayers, $this->players)) || // TODO : we can probably remove that since players criterion are always X+ on cards
      (is_string($this->players) && $nPlayers >= (int) explode('+', $this->players)[0]);

    // Check banlists (strong + weak), branching on player count
    $isLowPlayerCount = $nPlayers <= 2;

    $onStrongBanlist = $isLowPlayerCount ? $this->bannedStrong1or2p : $this->bannedStrong3or4p;
    $checkStrongBanlist = !$onStrongBanlist || $options[OPTION_COMPETITIVE_LEVEL] != OPTION_COMPETITIVE_BANLIST;

    $options[OPTION_WEAK_BANLIST] ??= OPTION_WEAK_BANLIST_DISABLED;
    $onWeakBanlist = $isLowPlayerCount ? $this->bannedWeak1or2p : $this->bannedWeak3or4p;
    $checkWeakBanlist = !$onWeakBanlist || $options[OPTION_WEAK_BANLIST] == OPTION_WEAK_BANLIST_DISABLED;

    // Check Living Hand banlist
    $checkLivingBanlist = !$this->bannedLiving || Globals::getDraftMode() != OPTION_DRAFT_LIVING_HAND;

    $checkDeck = false;

    // Check null deck
    if ($this->deck == null) {
      $checkDeck = true;
    }

    // Check All Cards Mode
    if ($options[OPTION_DECKS] == OPTION_DECKS_ALL) {
      $checkDeck = true;
    }

    // Check Base Only Mode
    if ($options[OPTION_DECKS] == OPTION_DECKS_BASE) {
      if (($this->deck == 'A' || $this->deck == 'B') && !$this->isArtifexOrBubulcus) {
        $checkDeck = true;
      }
    }

    // Check Base
    if ($options[OPTION_DECKS] == OPTION_DECKS_CUSTOM && $options[OPTION_DECK_BASE] == OPTION_DECK_ENABLED &&
        ($this->deck == 'A' || $this->deck == 'B') && !$this->isArtifexOrBubulcus
    ) {
      $checkDeck = true;
    }

    // Check A (Artifex)
    if ($options[OPTION_DECKS] == OPTION_DECKS_CUSTOM && $options[OPTION_DECK_A] == OPTION_DECK_ENABLED &&
        $this->deck == 'A' && $this->isArtifexOrBubulcus
    ) {
      $checkDeck = true;
    }

    // Check B (Bubulcus)
    if ($options[OPTION_DECKS] == OPTION_DECKS_CUSTOM && $options[OPTION_DECK_B] == OPTION_DECK_ENABLED &&
        $this->deck == 'B' && $this->isArtifexOrBubulcus
    ) {
      $checkDeck = true;
    }

    // Check C (Corbarius)
    if ($options[OPTION_DECKS] == OPTION_DECKS_CUSTOM && $options[OPTION_DECK_C] == OPTION_DECK_ENABLED &&
        $this->deck == 'C' && $this->isCorbariusOrDulcinaria
    ) {
      $checkDeck = true;
    }

    // Check D (Dulcinaria)
    if ($options[OPTION_DECKS] == OPTION_DECKS_CUSTOM && $options[OPTION_DECK_D] == OPTION_DECK_ENABLED &&
        $this->deck == 'D' && $this->isCorbariusOrDulcinaria
    ) {
      $checkDeck = true;
    }

    // Check E (Ephipparius)
    if ($options[OPTION_DECKS] == OPTION_DECKS_CUSTOM && $options[OPTION_DECK_E] == OPTION_DECK_ENABLED &&
        $this->deck == 'E'
    ) {
      $checkDeck = true;
    }

    // Check CD (Consul Dirigens)
    if ($options[OPTION_DECKS] == OPTION_DECKS_CUSTOM && $options[OPTION_DECK_CD] == OPTION_DECK_ENABLED &&
        ($this->deck == 'C' || $this->deck == 'D') && !$this->isCorbariusOrDulcinaria
    ) {
      $checkDeck = true;
    }

    return $this->implemented && $checkPlayer && $checkStrongBanlist && $checkWeakBanlist && $checkLivingBanlist && $checkDeck;
  }

  public function getType()
  {
    return $this->type;
  }

  public function getOtherCardTypes()
  {
    return [];
  }

  public function hasType($type)
  {
    if (is_null($type)) {
      return true;
    }

    return $this->getType() == $type || in_array($type, $this->getOtherCardTypes(), true);
  }

  public function getCode()
  {
    $map = [
      'A' => 1,
      'B' => 2,
      'C' => 3,
      'D' => 4,
      'E' => 5,
    ];
    return $this->number + 256 * $map[$this->deck];
  }

  /**
   * Useful for notifications
   */
  public function getTypeStr()
  {
    return '';
  }

  public function getScore()
  {
    return $this->vp;
  }

  // Solo campaign: expose the on-play flow so permanent occupations can re-fire their
  // immediate benefit at the start of each game (run via the engine, not actBuy).
  public function getOnBuyFlow($player)
  {
    return $this->onBuy($player);
  }

  /*
   * Solo campaign: bring this occupation into play as a free permanent (no cost/prerequisite
   * check). Driven by a SPECIAL_EFFECT node so it plays as part of a real turn — its onBuy and
   * triggered after-occupation listeners resolve together, and the whole phase is one Confirm/Undo.
   */
  public function campaignPlayPermanent()
  {
    Campaign::playPermanentEffect($this);
  }

  public function getCampaignPlayPermanentDescription()
  {
    return [
      'log' => clienttranslate('Play ${card_name}'),
      'args' => [
        'card_name' => $this->getName(),
      ],
    ];
  }

  public function getDeck()
  {
    return $this->deck;
  }
  public function getNumber()
  {
    return $this->number;
  }

  public function isPlayed()
  {
    return $this->location == 'inPlay';
  }

  public function isAnimalHolder()
  {
    return $this->animalHolder;
  }

  public function isField()
  {
    return $this->field;
  }

  public function getPlayer($checkPlayed = false)
  {
    if (!$this->isPlayed() && $checkPlayed) {
      throw new \feException("Trying to get the player for a non-played card : {$this->id}");
    }

    return Players::get($this->pId);
  }

  // Useful for PlayerActionCard
  public function getTurn()
  {
    return null;
  }

  /**
   * Cost/buy function
   */
  public function getBaseCosts()
  {
    return $this->costs ?? [$this->cost];
  }

  public function getBaseCostsWithFee($added = false)
  {
    $costs = $this->costs ?? [$this->cost];
    $fee = $this->fee ?? [];

    if ($added && $fee != []) {
      $combined = [];
      foreach ($costs as $cost) {
        $combined[] = array_merge($cost, $fee);
      }
      return $combined;
    }
    return array_merge($costs, [$fee]);
  }

  public function getCosts($player, $args = [])
  {
    $costs = [];
    foreach ($this->getBaseCosts() as $cost) {
      $costs['trades'][] = array_merge(['max' => 1], $cost);
    }

    if (!is_null($this->fee)) {
      $costs['fee'] = $this->fee;
    }

    // Apply card effects
    $args['card'] = $this;
    $args['costs'] = $costs;
    PlayerCards::applyEffects($player, 'ComputeCardCosts', $args);
    return $args['costs'];
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $cost = $this->getCosts($player, $args);
    return $ignoreResources || $player->canBuy($cost);
  }

  // Resources this card consumes/checks during the harvest BEFORE the feeding phase, i.e. goods
  // worth buying in the up-front harvest window. Default none; overridden by the few cards that care.
  public function preFeedingGoodsWanted()
  {
    return [];
  }

  /**
   * Exchanges functions
   */
  public function getExchanges()
  {
    $exchanges = $this->exchanges;
    foreach ($exchanges as &$exchange) {
      if (!isset($exchange['cardId']) && ($exchange['source'] ?? null) === $this->name) {
        $exchange['cardId'] = $this->id;
      }
    }
    unset($exchange);

    if (Globals::isHarvest()) {
      $flags = Globals::getExchangeFlags();
      Utils::filter($exchanges, function ($exchange) use ($flags) {
        return is_null($exchange['flag']) || !in_array($exchange['flag'], $flags);
      });
    }

    return $exchanges;
  }

  /**
   * Scores functions
   */
  protected function addScoringEntry($score, $desc, $isBonus = true, $player = null)
  {
    if (!$this->isPlayed()) {
      throw new \feException("Trying to addScoringEntry for a non-played card : {$this->id}");
    }

    if (is_string($desc)) {
      $desc = [
        'log' => $desc,
        'args' => [],
      ];
    }
    $player = $player ?? $this->getPlayer();
    $desc['args']['i18n'][] = 'card_name';
    $desc['args']['card_name'] = $this->getName();
    $desc['args']['player_name'] = $player ?? $player->getName();
    $desc['args']['score'] = $score;
    Scores::addEntry($player, $isBonus ? SCORING_CARDS_BONUS : SCORING_CARDS, $score, $desc, null, $this->getName());
  }

  protected function addBonusScoringEntry($score, $desc = null, $player = null)
  {
    $desc = $desc ?? $this->getBonusDescription();
    $this->addScoringEntry($score, $desc, true, $player);
  }

  protected function addQuantityScoringEntry($n, $scoresMap, $descSingular, $descPlural)
  {
    if (!$this->isPlayed()) {
      throw new \feException("Trying to addScoringEntry for a non-played card : {$this->id}");
    }

    Scores::addQuantityEntry(
      $this->getPlayer(),
      SCORING_CARDS_BONUS,
      $n,
      $scoresMap,
      $descSingular,
      $descPlural,
      $this->getName()
    );
  }

  // Reserves what it scored so a second card on the same pile can't recount it. Cards score in
  // card_id order, so the better card must sort first ('D60_LargePottery' < 'Major_Pottery').
  protected function addResourceScoringEntry($resource, $scoresMap, $descSingular, $descPlural)
  {
    $player = $this->getPlayer();
    $count = $player->countReserveResource($resource) - $player->countReservedResourcesForScoring($resource);
    $this->addQuantityScoringEntry($count, $scoresMap, $descSingular, $descPlural);
    $match = self::matchScoreBracket($count, $scoresMap);
    $player->reserveResourcesForScoring($resource, $match === null ? 0 : $match[0]);
  }

  // Returns [lowerBound, gain] for the bracket $count lands in, or null. Keys are 'X-Y', 'X+' or 'X'.
  protected static function matchScoreBracket($count, $map)
  {
    foreach ($map as $qty => $gain) {
      if (\strpos($qty, '-') !== false) {
        [$lower, $upper] = \explode('-', $qty);
      } elseif (\strpos($qty, '+') !== false) {
        $lower = \rtrim($qty, '+');
        $upper = null;
      } else {
        $lower = $upper = $qty;
      }
      if ($count >= $lower && ($upper === null || $count <= $upper)) {
        return [$lower, $gain];
      }
    }
    return null;
  }

  public function computeScore()
  {
    if ($this->vp != 0) {
      $this->addScoringEntry(
        $this->vp,
        clienttranslate('${player_name} earns ${score} for owning ${card_name}'),
        false
      );
    }

    $this->computeBonusScore();
  }

  public function getBonusDescription()
  {
    return clienttranslate('${player_name} earns ${score} for bonus of ${card_name}');
  }

  public function computeBonusScore()
  {
    $bonus = $this->getBonusScore();
    if ($bonus != 0) {
      $this->addBonusScoringEntry($bonus);
    }
  }

  public function getBonusScore()
  {
    return $this->getExtraDatas(BONUS_VP) ?? 0;
  }

  public function incBonusScore($amount)
  {
    $newScore = $this->getBonusScore() + $amount;
    $this->setExtraDatas(BONUS_VP, $newScore);
    return $newScore;
  }

  public function getStats()
  {
    return $this->getExtraDatas('stats') ?? [];
  }

  public function resetStats()
  {
    $this->setExtraDatas('stats', []);
    Notifications::updateCardStats($this->id, []);
  }

  public function getUsedText()
  {
    return $this->usedText;
  }

  public function getInfobox()
  {
    return $this->getExtraDatas('infobox') ?? null;
  }

  public function isMarked()
  {
    return $this->getExtraDatas('marked') ?? false;
  }

  public function incStats($type, $resource = null, $amount = null)
  {
    $stats = $this->getStats();

    if ($this->passing) {
      return;
    }

    if ($type == 'used') {
      if (array_key_exists($type, $stats)) {
        $stats[$type]++;
      } else {
        $stats[$type] = 1;
      }
    }

    if (in_array($type, ['gain', 'receivedPayment', 'paidToOthers', 'saved', 'paid'])) {
      $notTracked = [SCORE, BEGGING, FOOD_TRAVEL, FOODPLUS, WOODPLUS, VEGETABLEPLUS, FIELDPLUS];
      if (in_array($resource, $notTracked)) {
        return;
      }

      if (array_key_exists($type . $resource, $stats)) {
        $stats[$type . $resource] += $amount;
      } else {
        $stats[$type . $resource] = $amount;
      }
    }

    $this->setExtraDatas('stats', $stats);
    $stats = $this->getStats();
    Notifications::updateCardStats($this->id, $stats);
    return $stats;
  }

  public function setInfobox($string)
  {
    $this->setExtraDatas('infobox', $string);
    Notifications::placeInfobox($this->id, $string);
  }

  public function updateInfobox($string)
  {
    $this->setExtraDatas('infobox', $string);
    Notifications::updateInfobox($this->id, $string);
  }

  public function setMarked($marked)
  {
    $this->setExtraDatas('marked', $marked);
    if ($marked) {
      Notifications::markUsableExchange($this->id);
    } else {
      Notifications::unmarkUsableExchange($this->id);
    }
  }

  public function endOfGameCleanup($resource, $map)
  {
    if (!$this->isPlayed()) {
      return;
    }
    $count = $this->getPlayer()->countReserveResource($resource) - $this->getPlayer()->countReservedResourcesForScoring($resource);
    $match = self::matchScoreBracket($count, $map);
    if ($match !== null) {
      [$lower, $gain] = $match;
      $this->setExtraDatas(BONUS_VP, $gain);
      return $this->getPlayer()->useResource($resource, $lower);
    }
  }

  /**
   * Only keep exchanges corresponding to a trigger.
   * optional parameter $removeAnytime will allow to remove ANYTIME exchange if ask for a specific trigger
   *  -> useful to determine a card canCook and canBake booleans
   */
  public function getFilteredExchanges($trigger = ANYTIME, $removeAnytime = false)
  {
    $exchanges = $this->getExchanges();
    Utils::filterExchanges($exchanges, $trigger, $removeAnytime);
    return $exchanges;
  }

  public function canExchange($trigger = ANYTIME, $removeAnytime = false)
  {
    return count($this->getFilteredExchanges($trigger, $removeAnytime)) > 0;
  }

  public function canCook()
  {
    return $this->isCookery;

    //    return $this->canExchange(ANYTIME, true);
  }

  public function canBake()
  {
    return $this->isBakingImprovement;

    //    return $this->canExchange(BREAD, true);

  }

  public function actBuy($player, $args = [])
  {
    // Check $cost
    if (!$this->isBuyable($player, false, $args)) {
      throw new \BgaVisibleSystemException('Cannot be bought. Should not happen');
    }

    // Update stat
    Stats::setCardPlayed($player->getId(), $this->getCode(), Globals::getTurn());

    // Update playerid if major
    $next = null;
    if ($this->passing) {
      if (Globals::isSolo()) {
        PlayerCards::moveToBox($this->id);
      } else {
        $next = Players::get(Players::getNextId($player));
        self::DB()->update(['player_id' => $next->getId()], $this->id);

        // Living Hand only: receiving player can later keep/decline this passed card.
        if (Globals::getDraftMode() == OPTION_DRAFT_LIVING_HAND) {
          $pending = Globals::getLivingHandPendingPassing();
          if (!is_array($pending)) {
            $pending = [];
          }

          $receiverId = $next->getId();
          if (!isset($pending[$receiverId]) || !is_array($pending[$receiverId])) {
            $pending[$receiverId] = [];
          }
          if (!in_array($this->id, $pending[$receiverId], true)) {
            $pending[$receiverId][] = $this->id;
          }

          Globals::setLivingHandPendingPassing($pending);
        }
      }
    } else {
      $maxPos = self::DB()
        ->where('card_location', 'inPlay')
        ->wherePlayer($player->getId())
        ->max('card_state');
      self::DB()->update(
        [
          'player_id' => $player->getId(),
          'card_location' => 'inPlay',
          'card_state' => $maxPos + 1,
        ],
        $this->id
      );
      $this->location = 'inPlay';
      $this->state = $maxPos + 1;
    }
    $this->pId = $player->getId();

    // Notify
    if ($this->passing) {
      if (Globals::isSolo()) {
        Notifications::buyAndDestroyCard($this, $player);
      } else {
        Notifications::buyAndPassCard($this, $player, $next);
      }
    } else {
      Notifications::buyCard($this, $player);
    }

    // Trigger of Pay if needed
    $cost = $this->getCosts($player, $args);
    if ($cost != NO_COST) {
      $player->pay(1, $cost, $this->name, true, [
        'sourceAction' => 'BuyCard',
        'cardId' => $this->id,
        'cardType' => $this->type,
      ]);
    }

    // additionnal effects of buying
    if ($this->isField()) {
      $player->board()->addFieldCard($this->id, $this->getFieldDetails());
    }

    $flow = $this->onBuy($player);
    if (!is_null($flow)) {
      Engine::insertAsChild($flow);
    }

    $flow = $this->onBuyWithData($player, $args);
    if (!is_null($flow)) {
      Engine::insertAsChild($flow);
    }
  }

  public function returnToBoard()
  {
    self::DB()->update(['player_id' => null, 'card_location' => 'board'], $this->id);
  }

  public function giveToPlayer($playerId)
  {
    $maxPos = self::DB()
      ->where('card_location', 'inPlay')
      ->wherePlayer($playerId)
      ->max('card_state');
    self::DB()->update(
      [
        'player_id' => $playerId,
        'card_location' => 'inPlay',
        'card_state' => $maxPos + 1,
      ],
      $this->id
    );
    $this->location = 'inPlay';
    $this->state = $maxPos + 1;
    $this->pId = $playerId;
  }

  /**
   * Generic way to handle resources placed for future with cost
   */
  protected function getReceiveFlowWithCost($meeple, $cost, $source)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => PAY,
          'pId' => $this->pId,
          'args' => [
            'nb' => 1,
            'costs' => $cost,
            'source' => $source,
            'cardId' => $this->id,
          ],
        ],
        [
          'action' => RECEIVE,
          'args' => [
            'meeple' => $meeple['id'],
          ],
        ],
      ],
    ];
  }

  protected function onBuy($player)
  {
    return null;
  }

  protected function onBuyWithData($player, $eventData)
  {
    return null;
  }

  /**
   * Event modifiers template
   **/
  public function isListeningTo($event)
  {
    return false;
  }

  public function isAnytime($event, $action = null)
  {
    $node = Engine::getNextUnresolved();
    $ctxArgs = $node == null ? [] : $node->getArgs();
    return ($event['type'] ?? null) == 'anytime' &&
      $this->getPlayer()->getId() == Players::getActiveId() &&
      ($action == null || $event['action'] == $action) &&
      ($ctxArgs['cardId'] ?? null) != $this->id;
  }

  protected function isActionCardEvent($event, $actionCardType, $playerConstraint = 'player', $immediately = false)
  {
    return $event['type'] == 'action' &&
      $event['action'] == 'PlaceFarmer' &&
      ($actionCardType == null || ($event['actionCardType'] ?? null) == $actionCardType) &&
      (is_null($playerConstraint) ||
        ($playerConstraint == 'player' && $this->pId == $event['pId']) ||
        ($playerConstraint == 'opponent' && $this->pId != $event['pId'])) &&
      ((!$immediately && $event['method'] == 'PlaceFarmer') ||
        ($immediately && $event['method'] == 'ImmediatelyAfterPlaceFarmer'));
  }

  protected function isActionCardTurnEvent($event, $turns, $playerConstraint = 'player', $immediately = false)
  {
    $cardId = $event['actionCardId'] ?? null;
    if ($cardId != null) {
      $card = Utils::getActionCard($cardId);
      $turn = $card->getTurn();
      if (in_array($turn, $turns)) {
        $type = $card->getActionCardType();
        return $this->isActionCardEvent($event, $type);
      }
    }
  }

  protected function isActionEvent($event, $action, $playerConstraint = 'player', $immediately = false)
  {
    return $event['type'] == 'action' &&
      $event['action'] == $action &&
      (is_null($playerConstraint) ||
        ($playerConstraint == 'player' && $this->pId == $event['pId']) ||
        ($playerConstraint == 'opponent' && $this->pId != $event['pId'])) &&
      (($immediately && $event['method'] == 'ImmediatelyAfter' . $action) ||
        (!$immediately && $event['method'] == 'After' . $action));
  }

  protected function isBeforeEvent($event, $action, $playerConstraint = 'player')
  {
    return $event['type'] == 'action' &&
      $event['action'] == $action &&
      $event['method'] == 'before' . $action &&
      (is_null($playerConstraint) ||
        ($playerConstraint == 'player' && $this->pId == $event['pId']) ||
        ($playerConstraint == 'opponent' && $this->pId != $event['pId']));
  }

  protected function isDuringActionEvent($event, $action, $playerConstraint = 'player')
  {
    return $event['type'] == 'action' &&
      $event['action'] == $action &&
      (is_null($playerConstraint) ||
        ($playerConstraint == 'player' && $this->pId == $event['pId']) ||
        ($playerConstraint == 'opponent' && $this->pId != $event['pId'])) &&
      $event['method'] == $action;
  }

  protected function isPlayerEvent($event, $playerConstraint = 'player')
  {
    return is_null($playerConstraint) ||
        ($playerConstraint == 'player' && $this->pId == $event['pId']) ||
        ($playerConstraint == 'opponent' && $this->pId != $event['pId']);
  }

  protected function isBeforeCollectEvent($event, $res = null, $playerConstraint = 'player')
  {
    $cardType = $event['actionCardType'] ?? null;

    if (!$this->isActionCardEvent($event, $cardType, $playerConstraint)) {
      return false;
    }

    return $this->isCollectionSpace($event, $res);
  }

  protected function isCollectEvent($event, $res = null, $immediately = false, $playerConstraint = 'player')
  {
    if (!$this->isActionEvent($event, 'Collect', $playerConstraint, $immediately)) {
      return false;
    }

    return $this->isCollectionSpace($event, $res);
  }

  protected function isCollectionSpace($event, $res = null)
  {
    $actionCard = Utils::getActionCard($event['actionCardId']);
    if (!$actionCard->hasAccumulation()) {
      return false;
    }

    if ($res == null) {
      return true;
    }

    foreach ($actionCard->getAccumulation() as $resource => $amount) {
      if ($resource == $res) {
        return true;
      }
    }
    return false;
  }

  // Any way of getting goods off an action space: Collect, a space's Gain effect, or a Receive
  // of meeples taken from a space without using its action (e.g. Riverine Shepherd, Handcart)
  protected function isGetFromActionSpaceEvent($event, $types = null)
  {
    $matches = $this->isCollectEvent($event) ||
      (($this->isActionEvent($event, 'Gain') || $this->isActionEvent($event, 'Receive')) &&
        ($event['fromActionSpace'] ?? false));
    if (!$matches) {
      return false;
    }

    if ($types == null) {
      return true;
    }

    foreach ($event['meeples'] as $meeple) {
      if (in_array($meeple['type'], $types)) {
        return true;
      }
    }
    return false;
  }

  public function enforceReorganizeOnLastHarvest()
  {
    return false;
  }

  public function isBonusStoneRoom()
  {
    return $this->bonusStoneRoom;
  }

  public function isActionCard()
  {
    return $this->actionCard;
  }

  public function getMaxBonus($bonusAttribute)
  {
    $player = $this->getPlayer();
    $playedCards = $player->getPlayedCards();
    $score = -1;
    $id = '';
    $f = 'is' . $bonusAttribute;

    foreach ($playedCards as $card) {
      if (!$card->$f()) {
        continue;
      }

      $tmp = $card->computeBonus();
      if ($tmp > $score) {
        $score = $tmp;
        $id = $card->getId();
      }
    }
    return $id;
  }

  public function isFlagged($data = 'used')
  {
    // check legacy values
    if ($data == 'used') {
      $used = $this->getExtraDatas('used') ?? 0;
      $done = $this->getExtraDatas('done') ?? 0;
      $flag = $this->getExtraDatas('flag') ?? 0;

      return $used || $done || $flag;
    }

    return $this->getExtraDatas($data) ?? 0;
  }

  /****************************
   ****** SYNTAXIC SUGAR *******
   ****************************/
  public function refreshDropZones()
  {
    Notifications::updateDropZones($this->getPlayer());
  }

  public function gainNode($gain, $pId = null)
  {
    $gain['pId'] = $pId ?? $this->pId;
    $node =  [
      'action' => GAIN,
      'args' => $gain,
      'source' => $this->name,
      'cardId' => $this->getId(),
    ];
    return $node;
  }

  public function payNode($cost, $sourceName = null, $nb = 1, $to = null, $pId = null, $meeples = null)
  {
    return [
      'action' => PAY,
      'args' => [
        'pId' => $pId ?? $this->pId,
        'nb' => $nb,
        'costs' => Utils::formatCost($cost),
        'source' => $sourceName ?? $this->name,
        'cId' => $this->id,
        'to' => $to,
        'meeples' => $meeples,
      ],
    ];
  }

  public function payGainNode($cost, $gain, $sourceName = null, $optional = true, $pId = null, $meeples = null)
  {
    $pId = $pId ?? $this->pId;

    return [
      'type' => NODE_SEQ,
      'optional' => $optional,
      'pId' => $pId,
      'childs' => [$this->payNode($cost, $sourceName, 1, null, $pId, $meeples), $this->gainNode($gain, $pId)],
    ];
  }

  public function receiveNode($mId, $updateObtained = false, $player = null)
  {
    return [
      'action' => RECEIVE,
      'args' => [
        'meeple' => $mId,
        'player' => $player,
        'cardId' => $this->id,
      ],
    ];
  }

  public function moveResourcesToSpaceNode($resources, $location, $optional = false, $pId = null)
  {
    return [
      'action' => PLACE_MEEPLES_FROM_SUPPLY,
      'optional' => $optional,
      'args' => [
        'pId' => $pId ?? $this->pId,
        'resources' => $resources,
        'location' => $location,
        'cardId' => $this->id,
      ]
    ];
  }

  public function futureMeeplesNode(
    $resources,
    $turns,
    $flagCard = null,
    $cardId = null,
    $pId = null,
    $optional = false
  ) {
    // allow single integer argument for "next N rounds" cases
    if (!is_array($turns)) {
      $n = $turns;
      $turns = [];
      for ($i = 1; $i <= $n; $i++) {
        $turns[] = '+' . $i;
      }
    }

    return [
      'action' => PLACE_FUTURE_MEEPLES,
      'optional' => $optional,
      'args' => [
        'pId' => $pId ?? $this->pId,
        'resources' => $resources,
        'turns' => $turns,
        'flagCard' => is_null($flagCard) ? true : $flagCard,
        'cardId' => is_null($cardId) ? $this->id : $cardId,
      ],
    ];
  }

  public function bakeBreadNode()
  {
    return [
      'action' => EXCHANGE,
      'optional' => true,
      'pId' => $this->pId,
      'args' => [
        'trigger' => BREAD,
      ],
    ];
  }

  public function useActionSpaceNode($cId, $farmer = null)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'moveMeepleToActionSpace',
            'args' => [$cId, $farmer],
          ],
        ],
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'getActionSpaceBonusFlow',
            'args' => [$cId, '', $farmer],
          ],
        ],
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'useActionSpace',
            'args' => [$cId, $farmer],
          ],
        ],
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'getActionSpaceBonusFlow',
            'args' => [$cId, 'ImmediatelyAfter', $farmer],
          ],
        ],
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'getActionSpaceBonusFlow',
            'args' => [$cId, 'After', $farmer],
          ],
        ],
      ],
    ];
  }

  public function moveMeepleToActionSpace($cId, $farmer)
  {
    if (is_null($farmer)) {
      return;
    }

    Meeples::moveToCoords($farmer['id'], $cId);
    Notifications::placeFarmer(Players::get($farmer['pId']), $farmer['id'], ActionCards::get($cId));
  }

  public function getUseActionSpaceDescription($cId)
  {
    $position = null;
    if (Players::count() == 4) {
      if ($cId == 'ActionLessons') {
        $position = 'Right';
      }
      if ($cId == 'ActionLessons4') {
        $position = 'Left';
      }
    }

    if ($position != null) {
      $msg = [
        'log' => clienttranslate('Use ${action_space} (${position})'),
        'args' => [
          'i18n' => ['action_space'],
          ['position'],
          'position' => clienttranslate($position),
          'action_space' => Utils::getActionCard($cId)->getName(),
        ],
      ];
    } else {
      $msg = [
        'log' => clienttranslate('Use ${action_space}'),
        'args' => [
          'i18n' => ['action_space'],
          'action_space' => Utils::getActionCard($cId)->getName(),
        ],
      ];
    }

    return $msg;
  }

  // Re-checked at choice time, so fees paid earlier in the same reaction count against the target space
  public function isUseActionSpaceDoable($player, $ignoreResources, $cId, $farmer = null)
  {
    $player = $farmer == null ? $this->getPlayer() : Players::get($farmer['pId']);
    return Utils::getActionCard($cId)->canBePlayed($player, 'dummy', $ignoreResources);
  }

  public function useActionSpace($cId, $farmer = null)
  {
    $flow = $this->getActionSpaceFlow($cId, $farmer);

    if ($flow != []) {
      Engine::insertAsChild($flow);
    }
  }
  public function checkModifiers($method, &$data, $name, $player, $args = [])
  {
    $args[$name] = $data;
    PlayerCards::applyEffects($player, $method, $args);
    $data = $args[$name];
  }

  public function getActionSpaceFlow($cId, $farmer = null)
  {
    $flow = [];

    $player = $farmer == null ? $this->getPlayer() : Players::get($farmer['pId']);
    $card = Utils::getActionCard($cId);

    // Unaffordable actions keep their flow so the engine routes to impossible-mandatory instead of silently skipping
    if ($card->canBePlayed($player, 'dummy', true)) {
      $flow = $card->getFlow($player);
      // the code below is same as in the actPlaceFarmer, sadly not follow the DRY principle. maybe refactor later.
      $eventData = [
        'actionCardId' => $card->getId(),
        'actionCardType' => $card->getActionCardType(),
      ];
      if ($farmer != null){
        $eventData['fId'] = $farmer['id'];
      }
        $this->checkModifiers('computePlaceFarmerFlow', $flow, 'flow', $player, $eventData);
      // D101 side effect
      if (!$card->hasAccumulation() && !isset($card->skipGains) && Meeples::getResourcesOnCard($cId)->count() > 0) {
        $flow = [
          'type' => NODE_SEQ,
          'childs' => [
            [
              'action' => COLLECT,
              'pId' => $player->getId(),
              'cardId' => $cId,
            ],
            $flow,
          ],
        ];
      }
    }

    return $flow;
  }

  public function getActionSpaceBonusFlow($cId, $timing = '', $farmer)
  {
    $type = Utils::getActionCard($cId)->getActionCardType();
    $farmerPlaced = is_null($farmer) ? false : true;
    $pId = $farmer == null ? $this->getPlayer()->getId() : $farmer['pId'];

    $event = [
      'pId' => $pId,
      'type' => 'action',
      'action' => 'PlaceFarmer',
      'method' => $timing . 'PlaceFarmer',
      'actionCardId' => $cId,
      'actionCardType' => $type,
      'farmerPlaced' => $farmerPlaced,
    ];

    // Ensure downstream reactions can reliably identify the same farmer, useful for Large-Scale Farmer and Swagman when Basket Chair in the game
    if (!is_null($farmer) && isset($farmer['id'])) {
      $event['fId'] = $farmer['id'];
    }

    $reaction = PlayerCards::getReaction($event);
    if (!is_null($reaction)) {
      Engine::insertAsChild($reaction);
    }
  }

  public function flagCardNode($data = 'used')
  {
    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'flagCard',
        'args' => [$data],
      ],
    ];
  }

  public function unflagCardNode($data = 'used')
  {
    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'unflagCard',
        'args' => [$data],
      ],
    ];
  }

  public function isIndependentFlagCard()
  {
    return true;
  }

  public function flagCard($data = 'used')
  {
    $this->setExtraDatas($data, 1);
  }

  public function isIndependentUnflagCard()
  {
    return true;
  }

  public function unflagCard($data = 'used')
  {
    // unflag legacy values
    if ($data == 'used') {
      $this->setExtraDatas('done', 0);
      $this->setExtraDatas('flag', 0);
    }

    $this->setExtraDatas($data, 0);
  }

  public function getNextResource($type = null)
  {
    $meeples = Meeples::getResourcesOnCard($this->id, null, $type);
    return $meeples->empty() ? null : $meeples->last();
  }

  public function setUsedOnTurnIdNode($value = null)
  {
    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'setUsedOnTurnId',
        'args' => [$value],
      ],
    ];
  }

  public function setUsedOnTurnId($value = null)
  {
    $this->setExtraDatas('turnId', $value ?? Globals::getTurnId());
  }

  public function usableThisTurn(): bool
  {
    return !in_array(Globals::getTurnId(), ['', $this->getExtraDatas('turnId')]);
  }

  public function isIndependentSetUsedOnTurnId()
  {
    return true;
  }

  public function setTurnIdNode()
  {
    return [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'cardId' => $this->id,
        'method' => 'setTurnIdMethod',
      ],
    ];
  }

  public function setTurnIdMethod()
  {
    TurnTrait::setTurnId();
  }

  public function numStablesBuiltThisTurn($player): int
  {
    $turnId = Globals::getTurnId();
    if ($turnId != '' && array_key_exists($player->getId(), Globals::getStablesTurnId())) {
      return array_reduce(
        Globals::getStablesTurnId()[$player->getId()],
        function ($count, $turnBuilt) use ($turnId) {
          return $count + ($turnBuilt == $turnId ? 1 : 0);
        },
        0
      );
    } else {
      return 0;
    }
  }

  // Returns an array structured: ['roomType' => $roomType, 'turnId' => Globals::getTurnId()] (PlayerBoard.php->addRoom)
  public function roomTypeBuiltThisTurn($player)
  {
    $turnId = Globals::getTurnId();
    if ($turnId != '' && array_key_exists($player->getId(), Globals::getRoomsTurnId())) {
      $roomInfo = Globals::getRoomsTurnId()[$player->getId()];
      return $turnId == $roomInfo['turnId'] ? $roomInfo['roomType'] : null;
    }
    return null;
  }

  public function hasBuiltFencesThisTurn($player): bool
  {
    $turnId = Globals::getTurnId();
    return $turnId != '' &&
      array_key_exists($player->getId(), Globals::getFencesTurnId()) &&
      Globals::getFencesTurnId()[$player->getId()] == $turnId;
  }

  public function hasCookedThisTurn($player): bool
  {
    $turnId = Globals::getTurnId();
    return $turnId != '' &&
      array_key_exists($player->getId(), Globals::getCookedTurnId()) &&
      Globals::getCookedTurnId()[$player->getId()] == $turnId;
  }

  public function hasUsedLessonsThisTurn($player): bool
  {
    $turnId = Globals::getTurnId();
    return $turnId != '' &&
      array_key_exists($player->getId(), Globals::getLessonsTurnId()) &&
      Globals::getLessonsTurnId()[$player->getId()] == $turnId;
  }

  /**********************
   **** FALLBACK LEGACY ******
   **** TODO: REMOVE ********/

  protected function placeMeeplesForFuture($player, $placements, $flagCard = false)
  {
    $currentTurn = Globals::getTurn();

    foreach ($placements as $placement) {
      // Compute the turns where we are going to add stuff, if any
      $turns = [];
      foreach ($placement['turns'] as $turn) {
        if (!\is_int($turn)) {
          // If $x is not int, it's of the form +X
          $turn = (int) substr($turn, 1);
          $turn += $currentTurn;
        }

        if ($turn > $currentTurn && $turn <= 14) {
          $turns[] = $turn;
        }
      }

      if (empty($turns)) {
        continue;
      }

      // Create meeples and place them
      $meepleIds = [];
      foreach ($turns as $turn) {
        foreach ($placement['resources'] as $resType => $amount) {
          array_push(
            $meepleIds,
            ...Meeples::createResourceInLocation(
              $resType,
              'turn_' . $turn,
              $player->getId(),
              $flagCard ? $this->id : null,
              null,
              $amount
            )
          );
        }
      }

      $meeples = Meeples::getMany($meepleIds);
      Notifications::placeMeeplesForFuture($player, $placement['resources'], $turns, $meeples);
    }
  }

  public function onPlayerHarvestEndPhase($player)
  {
    return $this->onPlayerEndHarvest($player);
  }

  public function onPlayerHarvestFeedPhase($player)
  {
    return $this->onPlayerHarvestFeedingPhase($player);
  }

  public function onPlayerHarvestEndOfFeed($player)
  {
    return $this->onPlayerEndHarvestFeedingPhase($player);
  }
}
