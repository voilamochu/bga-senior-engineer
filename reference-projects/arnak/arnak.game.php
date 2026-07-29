<?php
 /**
  *------
  * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
  * arnak implementation : © Adam Spanel <adam.spanel@seznam.cz>
  *
  * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
  * See http://en.boardgamearena.com/#!doc/Studio for more information.
  * -----
  *
  * arnak.game.php
  *
  * This is the main file for your game logic.
  *
  * In this PHP file, you are going to defines the rules of the game.
  *
  */
define ("NEXT_ROUND", 2);
define ("DECIDE_KEEP", 35);
define ("NEXT_ROUND_CONT", 36);
define ("NEXT_PLAYER", 3);
define ("END_ROUND", 4);
define ("SELECT_ACTION", 5);
define ("AFTER_MAIN", 6);
define ("TURN_END", 7);
define ("EVAL_LOCATION", 8);
define ("EVAL_CARD", 9);
define ("EVAL_PLANE", 10);
define ("MUST_DISCARD", 11);
define ("MUST_DISCARD_FREE", 12);
define ("IDOL_UPGRADE", 13);
define ("IDOL_ASSISTANT", 14);
define ("IDOL_EXILE", 15);
define ("IDOL_REFRESH", 16);
define ("EVAL_IDOL", 17);
define ("SITE_EFFECT", 18);
define ("RESEARCH_BONUS", 19);
define ("MUST_TRAVEL", 20);
define ("MAY_TRAVEL", 21);
define ("MAY_EXILE", 22);
define ("BUY_ART", 23);
define ("ART_WAIT_ARGS", 24);
define ("ART_DISCARD", 25);
define ("ART_DONE", 26);
define ("ART_EFFECT", 27);
define ("BUY_ITEM", 28);
define ("ART_SELECT_ASS", 29);
define ("ART_ACTIVATE_ASS", 37);
define ("ART_EARRING_SELECT_KEEP", 30);
define ("ART_EARRING_SELECT_TOPDECK", 31);
define ("ART_SELECT_EXILED", 32);
define ("MUST_DISCARD_SHELL", 33);
define ("MAY_DISCARD_SHELL", 34);

define ("BOOT", 1);
define ("SHIP", 2);
define ("CAR", 3);
define ("PLANE", 4);

define ("GLASS", 1);
define ("BOOK", 2);

require_once( APP_GAMEMODULE_PATH.'module/table/table.game.php' );
require_once( "modules/card_effects.php" );
require_once( "modules/gamedata.php" );
require_once( "modules/sql_wrappers.php" );

class arnak extends Table
{
  public array $material;

  function debugMode() {
    if ($this->getBgaEnvironment() == 'studio') {
      return 1;
    }
    return 0;
  }
  function __construct( )
  {
    // Your global variables labels:
    //  Here, you can assign labels to global variables you are using for this game.
    //  You can use any number of global variables with IDs between 10 and 99.
    //  If your game has options (variants), you also have to associate here a label to
    //  the corresponding ID in gameoptions.inc.php.
    // Note: afterwards, you can get/set the global variables with getGameStateValue/setGameStateInitialValue/setGameStateValue
    parent::__construct();

    $this->initGameStateLabels( array(
      "round" => 10,
      "start-player" => 11,
      "discount-coins" => 12,
      "discount-compass" => 13,
      "discount-tablet" => 14,
      "discount-arrowhead" => 15,
      "discount-jewel" => 16,
      "discount-boot" => 17,
      "discount-ship" => 18,
      "discount-car" => 19,
      "discount-plane" => 20,
      "guard-buffer" => 21,
      "discount-idol" => 22,
      "discount-idol_slot" => 23,
      "site-buffer" => 25,
      "special-research-done" => 26,
      "research-token-done" => 27,
      "research-curr-type" => 28,
      "art-active" => 29,
      "artifact-mainaction" => 30,
      "warmask-played" => 31,
      "ocarina-played" => 32,

      //  "my_second_global_variable" => 11,
      //    ...
      //  "my_first_game_variant" => 100,
      //  "my_second_game_variant" => 101,
      //    ...
    ) );

    $this->gameData = new GameData($this);
    $this->sqlWrapper = new SqlWrapper($this);
  }

  protected function getGameName( )
  {
    // Used for translations and stuff. Please do not modify.
    return "arnak";
  }

  /*
    setupNewGame:

    This method is called only once, when a new game is launched.
    In this method, you must setup the game according to the game rules, so that
    the game is ready to be played.
  */
  protected function setupNewGame( $players, $options = array() )
  {
    // Set the colors of the players with HTML color code
    // The default below is red/green/blue/orange/brown
    // The number of colors defined here must correspond to the maximum number of players allowed for the gams
    $gameinfos = $this->getGameinfos();
    $default_colors = $gameinfos['player_colors'];

    // Create players
    // Note: if you added some extra field on "player" table in the database (dbmodel.sql), you can initialize it there.
    $sql = "INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES ";
    $values = array();
    foreach( $players as $player_id => $player )
    {
      $color = array_shift( $default_colors );
      $values[] = "('".$player_id."','$color','".$player['player_canal']."','".addslashes( $player['player_name'] )."','".addslashes( $player['player_avatar'] )."')";
    }
    $sql .= implode( ',', $values );
    $this->DbQuery( $sql );
    $this->reattributeColorsBasedOnPreferences( $players, $gameinfos['player_colors'] );
    $this->reloadPlayersBasicInfos();

    /************ Start the game initialization *****/

    // Init global values with their initial values
    $this->setGameStateInitialValue('round', 0);

    $this->setGameStateInitialValue("discount-coins", 0);
    $this->setGameStateInitialValue("discount-compass", 0);
    $this->setGameStateInitialValue("discount-tablet", 0);
    $this->setGameStateInitialValue("discount-arrowhead", 0);
    $this->setGameStateInitialValue("discount-jewel", 0);
    $this->setGameStateInitialValue("discount-boot", 0);
    $this->setGameStateInitialValue("discount-ship", 0);
    $this->setGameStateInitialValue("discount-car", 0);
    $this->setGameStateInitialValue("discount-plane", 0);
    $this->setGameStateInitialValue("discount-idol", 0);
    $this->setGameStateInitialValue("discount-idol_slot", 0);
    $this->setGameStateInitialValue('guard-buffer', -1);
    $this->setGameStateInitialValue('special-research-done', 0);
    $this->setGameStateInitialValue('research-token-done', 0);
    $this->setGameStateInitialValue('research-curr-type', 0);

    $this->initStat("player", "gained-coins", 0);
    $this->initStat("player", "gained-compass", 0);
    $this->initStat("player", "gained-tablet", 0);
    $this->initStat("player", "gained-arrowhead", 0);
    $this->initStat("player", "gained-jewel", 0);
    $this->initStat("player", "gained-idol", 0);
    $this->initStat("player", "idol-bonus", 0);
    $this->initStat("player", "idol-jewel", 0);
    $this->initStat("player", "idol-arrowhead", 0);
    $this->initStat("player", "idol-tablet", 0);
    $this->initStat("player", "idol-coins", 0);
    $this->initStat("player", "idol-card", 0);
    $this->initStat("player", "gained-item", 0);
    $this->initStat("player", "gained-art", 0);
    $this->initStat("player", "gained-card", 0);
    $this->initStat("player", "gained-draw", 0);
    $this->initStat("player", "exiled", 0);
    $this->initStat("player", "gained-fear", 0);
    $this->initStat("player", "discarded", 0);
    $this->initStat("player", "played", 0);
    $this->initStat("player", "cost-art", 0);
    $this->initStat("player", "cost-item", 0);
    $this->initStat("player", "guardians", 0);
    $this->initStat("player", "guardians-1", 0);
    $this->initStat("player", "guardians-2", 0);
    $this->initStat("player", "guardians-3", 0);
    $this->initStat("player", "guardians-4", 0);
    $this->initStat("player", "guardians-5", 0);
    $this->initStat("player", "sites-activated", 0);
    $this->initStat("player", "sites-activated-basic", 0);
    $this->initStat("player", "sites-activated-small", 0);
    $this->initStat("player", "sites-activated-big", 0);
    $this->initStat("player", "sites-discovered", 0);
    $this->initStat("player", "sites-discovered-small", 0);
    $this->initStat("player", "sites-discovered-big", 0);
    $this->initStat("player", "tokens-used", 0);
    $this->initStat("player", "book-step", 0);
    $this->initStat("player", "glass-step", 0);
    $this->initStat("player", "temple", 0);
    $this->initStat("player", "temple-bronze", 0);
    $this->initStat("player", "temple-silver", 0);
    $this->initStat("player", "temple-gold", 0);
    $this->initStat("player", "assistant-activated", 0);
    $this->initStat("player", "assistant-activated-silver", 0);
    $this->initStat("player", "assistant-activated-gold", 0);


    $coinGain = [0, 2, 1, 2, 1];
    $compassGain = [0, 0, 1, 1, 2];

    foreach($players as $playerId => $player) {
      $startDeck = [
        Basic::Funding_Car,
        Basic::Funding_Ship,
        Basic::Explore_Car,
        Basic::Explore_Ship,
        Basic::Fear,
        Basic::Fear
      ];

      shuffle($startDeck);
      $this->sqlWrapper->createCards([], $startDeck, $playerId, 'deck');

      $playerOrder = $this->getNonEmptyObjectFromDB("SELECT * FROM player WHERE player_id = $playerId")["player_no"];
      if ($playerOrder == 1) {
        $this->setGameStateValue("start-player", $playerId);
      }
      $this->DbQuery("UPDATE player SET compass = ".$compassGain[$playerOrder].", coins = ".$coinGain[$playerOrder].", idol_slot = 4 WHERE player_id = $playerId");

      if ($this->debugMode()) {
        $this->DbQuery("UPDATE player SET tablet = 50, arrowhead = 50, jewel = 50, coins = 50, compass = 50, idol = 4, research_glass = 6, research_book = 0 WHERE player_id = $playerId");
      }

    }

    $idolBonus = [
    "coins", "coins", "coins",
    "compass", "compass", "compass",
    "tablet", "tablet", "tablet",
    "exile", "exile", "exile",
    "upgrade", "upgrade",
    "refresh", "refresh"
    ];
    shuffle($idolBonus);

    $positionIds = range(0, 16);
    $blocked = range(0, 4);
    shuffle($blocked);
    array_pop($blocked);
    array_pop($blocked);

    foreach($positionIds as $i) {
      $slot2 = -1;
      if ($i < 5) {
        $slot2 = "NULL";
        if (count($players) == 2) {
          $slot2 = -1;
        }
        if (count($players) == 3 && in_array($i, $blocked)) {
          $slot2 = -1;
        }
        $idol = "NULL";
      }
      else {
        $idolStr = array_pop($idolBonus);
        $idol = "'$idolStr'";
      }
      $this->DbQuery("INSERT INTO board_position (idboard_position, slot2, idol_bonus) VALUES ($i, $slot2, $idol)");
    }

    $researchBonus = [
      "coins", "coins", "coins",
      "compass", "compass", "compass",
      "tablet", "tablet", "tablet",
      "exile", "exile", "exile",
      "card", "card", "card",
      "upgrade", "upgrade", "upgrade"
    ];
    shuffle($researchBonus);
    if ($this->birdTemple()) {
      switch(count($players)) {
        case 2: $positionIds = [3, 4, 6, 7, 8, 10, 11, 14, 14]; break;
        case 3: $positionIds = [3, 4, 6, 7, 8, 10, 11, 12, 13, 14, 14, 14]; break;
        case 4: $positionIds = [3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 14, 14, 14]; break;

      }
    }
    else {
      switch(count($players)) {
        case 2: $positionIds = [3, 5, 6, 7, 11, 12, 13, 13, 14, 14]; break;
        case 3: $positionIds = [3, 4, 5, 6, 7, 11, 12, 13, 13, 13, 14, 14, 14]; break;
        case 4: $positionIds = [3, 4, 5, 6, 7, 9, 10, 11, 12, 13, 13, 13, 14, 14, 14, 14]; break;

      }
    }
    foreach($positionIds as $spaceId) {
      $bonus = array_pop($researchBonus);
      $this->DbQuery("INSERT INTO research_bonus (track_pos, bonus_type) VALUES ($spaceId, '$bonus')");
    }

    $amt = count($players);
    for ($tileId = 1; $tileId <= 6; ++$tileId) {
      $this->DbQuery("INSERT INTO temple_tile (idtemple_tile, amt) VALUES ($tileId, $amt)");
    }

    $bigLocationIds = range(1, 6);
    shuffle($bigLocationIds);
    foreach($bigLocationIds as $order => $id) {
      $this->DbQuery("INSERT INTO location (size, num, is_open, deck_order) VALUES ('big', $id, 0, $order)");
    }
    $smallLocationIds = range(1, 10);
    shuffle($smallLocationIds);
    if ($this->debugMode()) {
      $smallLocationIds = [5, 7, 8, 9, 1, 2, 3, 4];
    }
    foreach($smallLocationIds as $order => $id) {
      $this->DbQuery("INSERT INTO location (size, num, is_open, deck_order) VALUES ('small', $id, 0, $order)");
    }

    foreach(range(0, 4) as $i) {
      $this->DbQuery("INSERT INTO location (size, num, is_open, is_at_position) VALUES('basic', $i, 1, $i)");
    }

    $guardianIds = range(1, 15);
    shuffle($guardianIds);
    if ($this->debugMode()) {
      $guardianIds = [7, 8, 9, 1, 2, 3, 4];
    }
    foreach($guardianIds as $order => $id) {
      $this->DbQuery("INSERT INTO guardian (num, deckorder) VALUES ($id, $order)");
    }

    $arts = Artefact::cases();
    shuffle($arts);
    if ($this->debugMode()) {
      $arts = [
        Artefact::Guardians_Ocarina,
        Artefact::Decorated_Horn,
        Artefact::Sacred_Drum,
        Artefact::Coconut_Flask,
        Artefact::Ancient_Wine,
        Artefact::Crystal_Earring,
        Artefact::Obsidian_Earring,
        Artefact::Pathfinders_Sandals,
        Artefact::Ornate_Hammer,
        Artefact::War_Mask,
        Artefact::Idol_of_AraAnu,
        Artefact::Inscribed_Blade,
        Artefact::Cleansing_Cauldron,
        Artefact::Treasure_Chest,
        Artefact::Monkey_Medallion,
        Artefact::War_Club,
        Artefact::Ritual_Dagger,
        Artefact::War_Mask
      ];
    }

    $this->sqlWrapper->createCards([], $arts, NULL, 'deck');

    $items = Item::cases();
    shuffle($items);
    if ($this->debugMode()) {
      $items = [
          Item::Large_Backpack,
          Item::Fishing_Rod,
          Item::Precision_Compass,
          Item::Theodolite,
          Item::Hat,
          Item::Automobile,
          Item::Ostrich,
          Item::Hot_Air_Balloon,
          Item::Tent,
          Item::Revolver,
          Item::Bear_Trap,
          Item::Lantern,
          Item::Army_Knife,
          Item::Binoculars,
          Item::Dog,
          Item::Horse,
          Item::Pack_Donkey,
          Item::Aeroplane,
          Item::Machete
        ];
    }

    $this->sqlWrapper->createCards([], $items, NULL, 'deck');

    $assistantIds = range(1, 12);
    shuffle($assistantIds);
    if ($this->debugMode()) {
      $assistantIds = [1, 2, 10, 3, 4, 5, 6, 7, 8, 9, 11, 12];
    }
    $offer = [1, 1, 1, 1, 2, 2, 2, 2, 3, 3, 3, 3];
    if (!$this->birdTemple()) {
      $offer = [];
      for ($i = 0; $i < count($players); ++$i) {
        array_push($offer, 4);
      }
      $offer = array_merge($offer, [1, 1, 1, 2, 2, 2, 3, 3, 3, 3, 3]);
    }
    foreach ($assistantIds as $i => $num) {
      $offerId = $offer[$i];
      $this->sqlWrapper->createAssistant($offerId != 4, $num, $offerId);
    }

    // we need some player active for giveExtraTime()
    $this->activeNextPlayer();
    $this->activePrevPlayer();

  }
  protected function getAllDatas() {
    $result = array();

    $current_player_id = $this->getCurrentPlayerId();


    $sql = "SELECT player_id id, player_score score, coins coins, compass compass, tablet tablet, arrowhead arrowhead, jewel jewel, idol_slot idol_slot, idol idol, research_glass research_glass, research_book research_book, temple_bronze, temple_silver, temple_gold, passed passed, temple_rank temple_rank FROM player ";
    $result['players'] = $this->getCollectionFromDb( $sql );
    foreach ($this->loadPlayersBasicInfos() as $idPlayer => $infos) {
      if ($idPlayer == $current_player_id) {
        $result['players'][$idPlayer]["hand"] = $this->sqlWrapper->getPublicCards($idPlayer, 'hand');
        $result['players'][$idPlayer]["earring"] = $this->sqlWrapper->getPublicCards($idPlayer, 'earring');
        $result['players'][$idPlayer]["keep"] = $this->sqlWrapper->getPublicCards($idPlayer, 'keep');
      }
      if ($this->gamestate->state()["name"] == "gameEnd") {
        $result["players"][$idPlayer]["scoreBreakdown"] = array();
        foreach(["research", "temple", "idols", "guardians", "cards", "fear"] as $category) {
          $result["players"][$idPlayer]["scoreBreakdown"][$category] = $this->score($category, $idPlayer);
        }
      }
      $result['players'][$idPlayer]["play"] = $this->sqlWrapper->getPublicCards($idPlayer, 'play');
      $result['players'][$idPlayer]["deck_amt"] = count($this->sqlWrapper->getPublicCards($idPlayer, 'deck'));
      $result['players'][$idPlayer]["hand_amt"] = count($this->sqlWrapper->getPublicCards($idPlayer, 'hand')) + count($this->sqlWrapper->getPublicCards($idPlayer, 'keep'));
      $result['players'][$idPlayer]["assistants"] = $this->sqlWrapper->getPlayerAssistants($idPlayer);
      $result['players'][$idPlayer]["guardian"] = count($this->getObjectListFromDb("SELECT * FROM guardian WHERE in_hand = $idPlayer"));
      $result['players'][$idPlayer]["guardians_ready"] = $this->getObjectListFromDb("SELECT idguardian id, num num FROM guardian WHERE in_hand = $idPlayer AND ready = 1");
    }

    $result['artSupply'] = $this->sqlWrapper->getPublicCards(null, 'supply', 'art');
    $result['itemSupply'] = $this->sqlWrapper->getPublicCards(null, 'supply', 'item');
    $result['itemDeck'] = count($this->sqlWrapper->getPublicCards(null, 'deck', 'item'));
    $result['itemExile'] = count($this->sqlWrapper->getPublicCards(null, 'discard', 'item'));
    $result['artDeck'] = count($this->sqlWrapper->getPublicCards(null, 'deck', 'art'));
    $result['artExile'] = count($this->sqlWrapper->getPublicCards(null, 'discard', 'art'));

    $result['board_position'] = $this->getCollectionFromDb("SELECT * FROM board_position");
    $result['round'] = $this->staffPosition();
    $result['locations'] = $this->getCollectionFromDb("SELECT idlocation id, size size, num num, is_at_position position FROM location WHERE is_open");
    $result['guardians'] = $this->getCollectionFromDb("SELECT * FROM guardian WHERE at_location IS NOT NULL OR in_hand IS NOT NULL");
    $result['research_bonus'] = $this->getCollectionFromDb("SELECT * FROM research_bonus");
    $result['temple_tile'] = $this->getCollectionFromDb("SELECT idtemple_tile id, amt amt FROM temple_tile");
    foreach($result['research_bonus'] as $i => $bonus) {
      if ($bonus["track_pos"] == 14) {
        $result['research_bonus'][$i]["bonus_type"] = "hidden";
      }
    }
    $stackAmt = $this->birdTemple() ? 3 : 4;
    for ($stackId = 1; $stackId <= $stackAmt; ++$stackId) {
      $assistants = $this->sqlWrapper->getAssistantsStack($stackId);
      $result["assistants"][$stackId] = (count($assistants) > 0) ? $assistants[0] : [];
      $result["assistants"][$stackId]["deckHeight"] = count($assistants);
    }
    $result['bird_temple'] = $this->birdTemple();
    $result["turn_based"] = $this->isTurnBased();
    $result['start_player'] = $this->getGameStateValue("start-player");

    $result['material'] = [
      "cards" => $this->material["cards"],
      "assistants" => $this->material["assistants"],
      "research" => $this->birdTemple() ? $this->material["birdResearch"] : $this->material["snakeResearch"],
      "travelCost" => $this->birdTemple() ? $this->material["birdTravelCost"] : $this->material["snakeTravelCost"],
      "sites" => $this->material["sites"],
      "guardians" => $this->material["guardians"]
    ];

    return $result;
  }

  function birdTemple() {
    return $this->gamestate->table_globals[100] == 1;
  }

  function isTurnBased() {
    return $this->gamestate->table_globals[200] > 2;
  }

  function staffPosition() {
    return ($this->debugMode() ? 3 : $this->getGameStateValue("round")); 
  }

  function displayDeck($playerId) {
    if (!$this->isTurnBased()) {
      throw new BgaUserException("cannot show deck in real-time games");
    }
    $cards = $this->sqlWrapper->getPublicCards($playerId);
    shuffle($cards);
    $this->notifyPlayer($this->getCurrentPlayerId(), "deckDisplay", 'Displaying deck of ${player_name}', array(
      "player_name" => $this->loadPlayersBasicInfos()[$playerId]["player_name"],
      "player_id" => $this->loadPlayersBasicInfos()[$playerId]["player_id"],
      "cards" => $cards
    ));
    //throw new BgaUserException("display deck for $playerId");
  }

  function displayDiscard() {
    $cards = $this->sqlWrapper->getPublicCards(NULL, 'discard', 'item');
    shuffle($cards);
    $this->notifyPlayer($this->getCurrentPlayerId(), "deckDisplay", 'Displaying discarded items', array(
      "cards" => $cards
    ));
  }

  function drawCard($playerId, $bottom = false, $position = "hand") {
    $cards = $this->sqlWrapper->getCards($playerId, 'deck');
    if (count($cards) == 0) {
      $this->notifyAllPlayers("cantDraw", clienttranslate('${player_name} cannot draw a card because their deck is empty'),
      array ('player_id' => $playerId,
        'player_name' => $this->loadPlayersBasicInfos()[$playerId]['player_name']
        )
      );
      return false;
    }

    $card = $bottom ? end($cards) : $cards[0];

    $message = "";
    if ($bottom) {
      $message = clienttranslate('${playerName} draws a card${bottom}.');
    }
    else if ($position == "earring") {
      $message = clienttranslate('${playerName} draws a card for the earring');
    }

    $notif = [
      ["msg" => clienttranslate('You draw ${cardName}'), "top" => !$bottom],
      ["msg" => $message, "bottom" => ($bottom?clienttranslate(" from the bottom of their deck"):""), "top" => !$bottom]
    ];
    $this->sqlWrapper->moveCard(["drawSelfCard", "drawCard"], $card, $playerId, $position, $notif);

    $this->undoSavePoint();
    $this->incStat(1, "gained-draw", $playerId);
    return true;
  }
  function selectCard($cardId) {
    $this->checkAction("selectCard");
    $playerId = $this->getActivePlayerId();
    switch($this->gamestate->state()["name"]) {
      case "artEarringSelectKeep":
        $card = $this->sqlWrapper->getCardFromId($cardId, $this->getActivePlayerId());
        if (!$card || $card["position"] != 'earring') {
          throw new BgaUserException(clienttranslate("You must discard one of the cards drawn with the earring"));
        }

        $notif = [["msg" => clienttranslate('You keep ${cardName} in your hand')], ["msg" => clienttranslate('${playerName} keeps 1 card in hand')]];
        $this->sqlWrapper->moveCard(["earringKeep", "earringKeepAll"], $card, $this->getActivePlayerId(), 'hand', $notif);
        $artActive = $this->getGameStateValue("art-active");
        if ($artActive >= 0 && Artefact::from($artActive) == Artefact::Crystal_Earring) {
          $this->gamestate->nextState("selectTopdeck");
        }
        else {
          $this->artDone();
        }
        break;
      case "artEarringSelectTopdeck":
        $cardToSelect = $this->sqlWrapper->getCardFromId($cardId, $this->getActivePlayerId());
        if (!$cardToSelect || $cardToSelect["position"] != 'earring') {
          throw new BgaUserException(clienttranslate("You must discard one of the cards drawn with the earring"));
        }
        $this->putCardToDeck($cardId, true, true);
        $this->artDone();
        break;
    }
  }
  function cancelEarring() {
    $this->checkAction("cancelEarring");
    $this->artDone();
  }
  function playCard($cardId, $arg = "") {

    $playerId = $this->getCurrentPlayerId();
    $this->checkAction("playCard");
    $cardToPlay = $this->sqlWrapper->getCardFromId($cardId, $playerId);
    if (!$cardToPlay) {
      throw new BgaUserException("Invalid use of this card");
    }
    $cardInfo = $cardToPlay["info"];
    $cardPosition = $cardToPlay["position"];
    if ($cardInfo->type() == "art" && $cardPosition == "hand") {
      $this->gainResource("tablet", $playerId, -1, array("component" => "card", "arg" => $cardId));
    }
    if ($this->gamestate->state()["name"] == "researchBonus") {

      $legal = $this->currentSpecialResearch() == "free-art" &&
      ($cardInfo->type() == "art" && $cardPosition == "supply");
      if (!$legal) {
        throw new BgaUserException(clienttranslate("Cannot play that artifact during this research"));
      }
    }
    $inhand = $cardPosition == "hand";
    $artActive = $this->getGameStateValue("art-active");
    $supplyArtWaited = $cardPosition == "play" && $cardInfo->type() == "art" && $artActive >=0 && $cardInfo == Artefact::from($artActive);
    if (!$inhand && !$supplyArtWaited) {
      throw new BgaUserException(clienttranslate("Invalid attempt to play a card"));
    }

    $notif = $inhand ? [["msg" => clienttranslate('${playerName} plays ${cardName}')]] : [];

    $this->sqlWrapper->moveCard(["cardPlayed"], $cardToPlay, $playerId, 'play', $notif);

    if (!$inhand) {
      $this->setGameStateValue("art-active", -1);
    }
    (new CardEffects($this, $this->getActivePlayerId())) -> cardEffect($cardInfo, $cardId, $arg);
    $this->incStat(1, "played", $playerId);
  }
  function artDone() {
    $toDiscard = $this->sqlWrapper->getCards($this->getActivePlayerId(), 'earring');
    foreach($toDiscard as $card) {
      $this->discardCard($card["id"]);
    }
    $this->setGameStateValue("art-active", -1);
    $this->gamestate->nextState("artDone");

    if ($this->researchDone()) {
      $this->gamestate->nextState("allDone");
    }
    else {
      $this->gamestate->nextState("researchRemains");
    }
  }
  function buyCard($cardId, $top = false, $mainAction = true, $force = true) {
    $cardBought = $this->sqlWrapper->getCardFromId($cardId);
    $cardInfo = $cardBought["info"];
    if ($cardBought["position"] != 'supply') {
      throw new BgaUserException(clienttranslate($cardInfo->type()." card cannot be bought"));
    }

    $player = $this->getActivePlayerId();
    if (!$force) {
      if ($cardInfo->type() == "art") {
        $this->checkAction("buyArt");
      }
      else if ($cardInfo->type() == "item") {
        $this->checkAction("buyItem");
      }
      else {
        throw new BgaUserException(clienttranslate($cardInfo->type()." card cannot be bought"));
      }
    }
    $amt = $this->gameData->cardCost($cardInfo);
    $resName = $cardInfo->type() == "item" ? "coins" : "compass";
    if ($this->gamestate->state()["name"] == "researchBonus") {
      if ($this->currentSpecialResearch() == "free-art") {
        $this->setGameStateValue("special-research-done", 1);
        $this->notifyAllPlayers("freeArt", clienttranslate('${player_name} gets the artifact for free'), array("player_name" => $this->getActivePlayerName()));
      }
      else {
        throw new BgaUserException("Cannot buy an artifact right now");
      }
    }
    else if ($this->gamestate->state()["name"] == "evalPlane") {
      $this->notifyAllPlayers("planeFree", clienttranslate('The card is free thanks to the location'), array());
    }
    else {
      $this->gainResource($resName, $player, -$amt, array("component" => "card", "arg" => $cardId));
    }
    if ($cardInfo->type() == "art") {
      $notif = [["msg" => clienttranslate('${playerName} plays ${cardName}')]];
      $this->sqlWrapper->moveCard(["cardPlayed"], $cardBought, $player, 'play', $notif);

      $this->setGameStateValue("artifact-mainaction", $mainAction ? 1 : 0);
      $clientArgs = true;
      if ($cardInfo == Artefact::Ceremonial_Rattle && count($this->sqlWrapper->getPlayerAssistants($player))) {
        $clientArgs = false;
      }

      if( $cardInfo == Artefact::War_Mask ||
          $cardInfo == Artefact::Treasure_Chest ||
          $cardInfo == Artefact::Serpents_Gold ||
          $cardInfo == Artefact::Serpent_Idol ||
          $cardInfo == Artefact::Hunting_Arrows ||
          $cardInfo == Artefact::Coconut_Flask ||
          $cardInfo == Artefact::Cleansing_Cauldron ||
          $cardInfo == Artefact::Ancient_Wine ||
          $cardInfo == Artefact::Ornate_Hammer ||
          $cardInfo == Artefact::Stone_Jar ||
          $cardInfo == Artefact::Passage_Shell ||
          $cardInfo == Artefact::Stone_Key ||
          $cardInfo == Artefact::Guiding_Stone ||
          $cardInfo == Artefact::Runes_of_the_Dead ) {
        $clientArgs = false;
      }
      if ($clientArgs) {
        $this->setGameStateValue("art-active", $cardInfo->value);
        $this->gamestate->nextState("artWaitArgs");

      }
      else {
        (new CardEffects($this, $this->getActivePlayerId())) -> cardEffect($cardInfo, $cardId);
      }
    }
    else {
      $this->putCardToDeck($cardId, $top, false);

      if ($mainAction) {
        $this->gamestate->nextState("main_action_done");
      }
    }

    //$this->refillCards();
    $this->incStat(1, "gained-card", $player);
    $this->incStat(1, "gained-".$cardInfo->type(), $player);
    $this->resetDiscount();
    $siteId = $this->getGameStateValue("guard-buffer");
    if ($siteId > -1) {
      $this->placeGuard($siteId);
    }
  }
  function cancelBuy()
  {
    $this->checkAction("cancelBuy");
    if( $this->gamestate->state()["name"] == "buyArt" ) {
      $type = "art";
    }
    else if( $this->gamestate->state()["name"] == "buyItem" ) {
      $type = "item";
    }
    $this->unRefillCards();
    $this->gamestate->nextState("main_action_done");
    $this->resetDiscount();
  }
  function putCardToDeck($cardId, $top, $secret) {
    $player = $this->getCurrentPlayerId();
    $card = $this->sqlWrapper->getCardFromId($cardId);

    $notif = [];
    $position_notif = $top ? clienttranslate("top") : clienttranslate("bottom");
    if ($secret) {
      array_push($notif, ["msg" => clienttranslate('You put ${cardName} to the ${position} of your deck'), "position" => $position_notif, "top" => $top]);
      array_push($notif, ["msg" => clienttranslate('${playerName} puts a card to the ${position} of their deck'), "position" => $position_notif, "top" => $top]);
    }
    else {
      array_push($notif, ["msg" => clienttranslate('${playerName} puts ${cardName} to the ${position} of their deck'), "position" => $position_notif, "top" => $top]);
    }
    $this->sqlWrapper->moveCard(["putToDeck","newInDeck"], $card, $player, 'deck', $notif, !$top);
  }
  function getFromExile($cardId) {
    $this->checkAction("selectExileCard");
    $artActive = $this->getGameStateValue("art-active");
    if ($artActive >= 0 && Artefact::from($artActive) != Artefact::Ornate_Hammer) {
      throw new BgaUserException("Weird, you are trying to get card from exile without the hammer");
    }
    $card = $this->sqlWrapper->getCardFromId($cardId);
    if ($card["position"] != 'discard' || $card["info"]->type() != 'item') {
      throw new BgaUserException("Invalid card, it must be picked from exile");
    }
    $this->putCardToDeck($cardId, false, false);
    $this->artDone();
  }
  function activeArt() {
    $num = $this->getGameStateValue("art-active");
    return array("num" => $num, "id" => $this->sqlWrapper->getCardId('art',$num));
  }
  function researchLeft() {
    $result = array();
    $playerId = $this->getActivePlayerId();
    if ($this->getGameStateValue("special-research-done") == 0) {
      $result["special"] = $this->currentSpecialResearch();
      if ($result["special"] == "assistant-special") {
        $result["_private"]["active"]["special_assistants"] = $this->sqlWrapper->getAssistantsStack(4);
      }
    }
    if ($this->getGameStateValue("research-token-done") == 0) {
      $space = $this->getObjectFromDB("SELECT * FROM player WHERE player_id = $playerId")["research_".$this->researchType()];
      $result["token"] = $space;
      if( $space == 14 ) {
        $result["_private"]["active"]["tokens_left"] = $this->getCollectionFromDb("SELECT * FROM research_bonus WHERE track_pos = $space");
      }

    }
    return $result;
  }
  function clientDiscardCard($cardId) {
    $this->checkAction("discard");
    $this->discardCard($cardId);
  }
  function discardCard($cardId) {
    $playerId = $this->getActivePlayerId();
    $card = $this->sqlWrapper->getCardFromId($cardId, $playerId);
    if (!$card || ($card['position'] != 'hand' && $card['position'] != 'earring')) {
      throw new BgaUserException("Invalid attempt to discard a card.");
    }
    $notif = [["msg" => clienttranslate('${playerName} discards ${cardName}')]];
    $this->sqlWrapper->moveCard(["cardPlayed"], $card, $playerId, 'play', $notif);

    $name = $this->gamestate->state()["name"];
    if ($name === "mustDiscard" || $name === "mustDiscardFree" || $name === "mayDiscard") {
      $this->gamestate->nextState("discard_done");
    }
    if ($name === "mayDiscard") {
      $this->setGameStateValue("art-active", -1);
    }
    $this->incStat(1, "discarded", $playerId);
  }

  function refillCards() {
    $round = $this->staffPosition();
    foreach(["art", "item"] as $i => $cardType) {
      $limit = $cardType == "art" ? $round : 6 - $round;
      while (count($this->sqlWrapper->getCards(null, 'supply', $cardType)) < $limit && $this->revealCard($cardType));
    }
  }
  function cardTypeText($type) {
    return array(
      "fear" => clienttranslate("fear"),
      "art" => clienttranslate("artifact"),
      "item" => clienttranslate("item"),
    )[$type];
  }
  function revealCard($type) {
    $cards = $this->sqlWrapper->getCards(null, 'deck', $type);
    if (count($cards) == 0) {
      $this->notifyAllPlayers("outOfCards", clienttranslate('There are no more cards of type ${type} to deal'), array("type" => $type, 'i18n' => ['type'] ));
      return false;
    }
    $newCard = $cards[0];

    $notif = [["msg" => clienttranslate('New ${cardTypeText} ${cardName} is revealed'), "cardTypeText" => $this->cardTypeText($newCard["info"]->type())]];
    $this->sqlWrapper->moveCard(["cardReveal"], $newCard, null, 'supply', $notif);

    if (count($this->getCollectionFromDb("SELECT * FROM player WHERE passed != 1")) > 0) {
      $this->undoSavePoint();
    }
    return true;
  }
  function unRefillCards() {
    $round = $this->staffPosition();
    foreach(["art", "item"] as $i => $cardType) {
      $limit = $cardType == "art" ? $round : 6 - $round;
      while (count($cards = $this->sqlWrapper->getCards(null, 'supply', $cardType)) > $limit) {
        $lastCard = end($cards);
        $notif = [["msg" => clienttranslate('Card ${cardName} is put back on top of ${cardTypeText} deck'), "cardTypeText" => $this->cardTypeText($lastCard["info"]->type())]];
        $this->sqlWrapper->moveCard(["drawnCardPutBack"], $lastCard, null, 'deck', $notif, false);
      }
    }
  }
  function exileStaffCards() {
    foreach(["art", "item"] as $i => $type) {
      $cards = $this->sqlWrapper->getCards(null, 'supply', $type);
      if (count($cards) == 0) {
        continue;
      }

      $notif = [["msg" => clienttranslate('Removing ${cardName} from the supply')]];
      $this->sqlWrapper->moveCard(["removeStaffCard"], $cards[0], null, 'discard', $notif);
    }
  }

  function idolBonus($bonusName) {
    $this->checkAction("useIdol");
    $playerId = $this->getActivePlayerId();
    $this->gainResource("idol_slot", $playerId, -1);
    $this->gainResource("idol", $playerId, -1);
    $resArg = array("component" => "idol", "type" => $bonusName);
    switch($bonusName) {
      case "jewel":
        $this->gainResource("coins", $playerId, -1, $resArg);
        $this->gainResource("jewel", $playerId, 1, $resArg);
        break;
      case "arrowhead":
        $this->gainResource("arrowhead", $playerId, 1, $resArg);
        break;
      case "tablet":
        $this->gainResource("tablet", $playerId, 2, $resArg);
        break;
      case "coins":
        $this->gainResource("coins", $playerId, 1, $resArg);
        $this->gainResource("compass", $playerId, 1, $resArg);
        break;
      case "card":
        $this->gainResource("card", $playerId, 1);
        break;
      default:
        throw new BgaUserException("Unknown bonus $bonusName");
    }
    $this->incStat(1, "idol-bonus", $playerId);
    $this->incStat(1, "idol-".$bonusName, $playerId);
  }

  function freeWorkerAmt($player) {
    return 2 - count($this->getCollectionFromDb("SELECT * FROM board_position WHERE slot1 = $player")) -
    count($this->getCollectionFromDb("SELECT * FROM board_position WHERE slot2 = $player"));
  }

  function travelUseful($travelReqs, $icon) {
    if ($this->ocarinaActive()) {
      $icon = PLANE;
    }
    if ($icon == PLANE) {
      return $travelReqs[PLANE] > 0 || $travelReqs[CAR] > 0 || $travelReqs[SHIP] > 0 || $travelReqs[BOOT] > 0;
    }
    if ($icon == BOOT) {
      return $travelReqs[BOOT] > 0;
    }
    if ($icon == CAR) {
      return $travelReqs[CAR] > 0 || $travelReqs[BOOT] > 0;
    }
    if ($icon == SHIP) {
      return $travelReqs[SHIP] > 0 || $travelReqs[BOOT] > 0;
    }
  }

  function payTravel($iconReqs, $payment) {
    $paymentAvailable = [
    BOOT => max(0, $this->getGameStateValue("discount-boot")),
    CAR => max(0, $this->getGameStateValue("discount-car")),
    SHIP => max(0, $this->getGameStateValue("discount-ship")),
    PLANE => max(0, $this->getGameStateValue("discount-plane"))
    ];

    $travelReqs = [
      BOOT => 0,
      SHIP => 0,
      CAR => 0,
      PLANE => 0
    ];

    foreach ($iconReqs as $icon) {
      $travelReqs[$icon]++;
    }

    $playerId = $this->getActivePlayerId();
    //throw new BgaUserException(JSON_ENCODE($paymentAvailable));
    if (!is_null($payment)) {
      foreach($payment as $i => $pay) {
        switch($pay["type"]) {
        case "card":
          $id = $pay["id"];
          $cardTravel = $this->sqlWrapper->getCardFromId($id, $playerId);
          if ($cardTravel && $cardTravel['position'] == 'hand') {
            $cardInfo = $cardTravel["info"];
            $useful = false;
            foreach($this->gameData->cardTravel($cardInfo) as $i => $travelType) {
              $paymentAvailable[$travelType] += 1;
              if ($this->travelUseful($travelReqs, $travelType)) {
                $useful = true;
              }
            }
            if (!$useful) {
              break;
            }
            $notif = [["msg" => clienttranslate('${playerName} discards ${cardName} for travel symbols')]];
            $this->sqlWrapper->moveCard(["cardPlayed"], $cardTravel, $playerId, 'play', $notif);
          }
          break;
        case "buyplane":
          if (!$this->travelUseful($travelReqs, PLANE)) {
            break;
          }
          $paymentAvailable[PLANE] += 1;
          $this->notifyAllPlayers("buyPlane", clienttranslate('${player_name} buys a plane symbol for 2 coins'),
          array(
            "player_name" => $this->getActivePlayerName(),
            "player_id" => $this->getActivePlayerId()
          ));
          $this->gainResource("coins", $this->getActivePlayerId(), -2);
          break;
        case "assistant":
          $assNum = $pay["num"];
          $assistant = $this->sqlWrapper->getAssistantFromNum($assNum);
          if ($assistant["ready"] == 1 && $assistant["in_hand"] == $playerId) {
            $effect = $this->gameData->assistantPower($assNum, $assistant["gold"]);
            if (!$effect["travel"]) {
              throw new BgaUserException("Cannot pay with that assistant");
            }
            $travelUseful = false;
            foreach ($effect["travel"] as $icon) {
              $travelUseful |= $this->travelUseful($travelReqs, $icon);
              $paymentAvailable[$icon]++;
            }
            if (!$travelUseful) {
              break;
            }
            $this->sqlWrapper->changeAssistantUsed($assNum, true, clienttranslate('${player_name} uses his assistant for travel icons'));
          }
          break;
        case "guardian":
          $num = $pay["num"];
          $reward = $this->gameData->guardianReward($num);
          if(!isset($reward["travel"])) {
            throw new BgaUserException(clienttranslate("Cannot pay travel with guardian $num"));
          }
          $useful = false;
          foreach ($reward["travel"] as $travelType) {
            if ($this->travelUseful($travelReqs, $travelType)) {
              $useful = true;
            }
            $paymentAvailable[$travelType]++;
          }
          if (!$useful) {
            break;
          }
          $this->getNonEmptyObjectFromDB("SELECT * FROM guardian WHERE ready = 1 AND num = $num");
          $this->dbQuery("UPDATE guardian SET ready = 0 WHERE num = $num");
          $this->notifyAllPlayers("useGuard", clienttranslate('${player_name} uses his guardian for travel symbol'), array(
            "player_name" => $this->getActivePlayerName(),
            "player_id" => $this->getActivePlayerId(),
            "guardNum" => $num
          ));
          break;
        }
      }
    }

    if ($this->ocarinaActive()) {
      $this->notifyAllPlayers("freePlanes", clienttranslate("All travel symbols count as planes (Guardian's Ocarina)"), array());
      $paymentAvailable = [PLANE => $paymentAvailable[PLANE] + $paymentAvailable[SHIP] + $paymentAvailable[CAR] + $paymentAvailable[BOOT], SHIP => 0, CAR => 0, BOOT => 0];
    }

    $paymentAvailable[PLANE] -= $travelReqs[PLANE];
    $travelReqs[PLANE] = 0;

    $planeModif = max($paymentAvailable[PLANE], 0);
    $travelReqs[BOOT] -= $paymentAvailable[BOOT];
    $shipsNeeded = $travelReqs[SHIP] - $paymentAvailable[SHIP] - $planeModif;
    $carsNeeded = $travelReqs[CAR] - $paymentAvailable[CAR] - $planeModif;
    $bootsNeeded = $travelReqs[BOOT] - ($paymentAvailable[SHIP] + $paymentAvailable[CAR] + $planeModif);

    $enough = (($travelReqs[CAR] + $travelReqs[SHIP] <= $paymentAvailable[CAR] + $paymentAvailable[SHIP] + $paymentAvailable[PLANE]) &&
      ($carsNeeded <= 0) &&
      ($shipsNeeded <= 0) &&
      ($travelReqs[BOOT] + $travelReqs[CAR] + $travelReqs[SHIP] - ($paymentAvailable[SHIP] + $paymentAvailable[CAR] + $paymentAvailable[PLANE]) <= 0 &&
      $paymentAvailable[PLANE] >= 0)
    );

    if (!$enough) {
      throw new BgaUserException(JSON_ENCODE(array("car" => $carsNeeded, "ship" => $shipsNeeded, "plane" => -$paymentAvailable[PLANE], "boot" => $bootsNeeded)));
    }

    $this->setGameStateValue("discount-boot", -max($bootsNeeded, $paymentAvailable[BOOT]));
    $this->setGameStateValue("discount-car", -max($carsNeeded, $paymentAvailable[CAR]));
    $this->setGameStateValue("discount-ship", -max($shipsNeeded, $paymentAvailable[SHIP]));
    //$this->setGameStateValue("discount-plane", max(0, $paymentAvailable[PLANE] - $);
  }
  function ocarinaActive() {
    $playerId = $this->getActivePlayerId();
    if ($this->getGameStateValue("ocarina-played") == "1" ) {
      $cards = $this->sqlWrapper->getCards($playerId, 'play', 'art');
      foreach ($cards as $card) {
        if ($card["info"] == Artefact::Guardians_Ocarina) {
          return true;
        }
      }
    }
    return false;
  }
  function availableGuardians($targetLocation = -1, $notOccupiedbyAnyOtherPlayer = false)
  {
    $playerId = $this->getActivePlayerId();
    $guards = $this->getObjectListFromDb(
    "SELECT *
    FROM guardian g
    INNER JOIN board_position p ON g.at_location = p.idboard_position
    ");
    $available_guards = array();
    foreach ($guards as $guard) {
      if ($targetLocation >= 0 && $guard["at_location"] != $targetLocation) {
        continue;
      }
      if ($notOccupiedbyAnyOtherPlayer) {
        if (($guard["slot1"] == $playerId || is_null($guard["slot1"]) ) && ($guard["slot2"] == $playerId || is_null($guard["slot2"]) || $guard["slot2"] == -1)) {
          $available_guards[count($available_guards)] = $guard;
        }
      }
      else {
        if ($guard["slot1"] == $playerId || $guard["slot2"] == $playerId) {
          $available_guards[count($available_guards)] = $guard;
        }
      }
    }
    return $available_guards;
  }
  function freeOvercome($locationId = -1) {
    $playerId = $this->getActivePlayerId();
    $guards = $this->availableGuardians($locationId);
    switch (count($guards)) {
      case 0: throw new BgaUserException(clienttranslate("Select a valid guardian")); break;
      case 1: $this->overcomeGuard($guards[0]["num"], "", true); break;
      default: throw new BgaUserException(clienttranslate("Incorrect number of guards found"));
    }
  }
  function overcomeGuard($guardNum, $movePayment, $free = false) {
    $cost = $this->gameData->guardianCost($guardNum);
    $playerId = $this->getActivePlayerId();
    if (!$free) {
      foreach ($cost as $type => $amt) {
        if ($type == "travel") {
          $this->payTravel($amt, $movePayment);
        }
        else if ($type == "discard") {
          if (array_key_exists(0, $movePayment)) {
            if ($movePayment[0]["type"] !== "card") {
              throw new BgaUserException("Trying to discard something else than a card");
            }
            $this->discardCard($movePayment[0]["id"]);
          }
          else {
            throw new BgaUserException('{"card": 1}');
          }
        }
        else {
          $this->gainResource($type, $playerId, -$amt, array("component" => "guardian", "num" => $guardNum));
        }
      }
    }
    $this->dbQuery("UPDATE guardian SET in_hand = $playerId, ready = 1, at_location = NULL WHERE num = $guardNum");
    $this->incStat(1, "guardians", $playerId);
    $this->incStat(1, "guardians-".$this->getGameStateValue("round"), $playerId);
    $this->notifyAllPlayers("overcomeGuard", clienttranslate('${player_name} overcame the guardian'),
    array(
    "player_name" => $this->getCurrentPlayerName(),
    "playerId" => $this->getCurrentPlayerId(),
    "guardNum" => $guardNum
    ));
  }
  function moveToSite($siteId, $movePayment, $relocateFrom = null) {
    $playerId = $this->getActivePlayerId();
    $guards = array();
    if ($siteId !== "home") {
      $guards = $this->availableGuardians($siteId);
    }
    if (is_null($relocateFrom)) {
      if ($this->gamestate->state()["name"] == "researchBonus" && $this->currentSpecialResearch() == "guard" ) {
        if( $this->getActivePlayerId() != $this->getCurrentPlayerId())
          throw new BgaUserException(clienttranslate("It is not your turn"));
        if (count($guards) == 0) {
          throw new BgaUserException(clienttranslate("Select a valid guardian"));
        }
        $this->overcomeGuard($guards[0]["num"], $movePayment, true);
        $this->setGameStateValue("special-research-done", 1);
        $this->didResearch();
        return;
      }


      $moveString = base64_decode($movePayment);
      $movePayment = json_decode($moveString, true);

      $this->checkAction("digSite");
    }



    if ($siteId != "home") {
      if (count($guards) > 0 ) {
        $this->overcomeGuard($guards[0]["num"], $movePayment);
        $this->gamestate->nextState("main_action_done");
        return;
      }
      $site = $this->getObjectFromDB("SELECT * FROM board_position WHERE idboard_position = $siteId");
      $targetSlot = "slot1";
      if ($site["slot1"]) {
        $targetSlot = "slot2";
        if ($site["slot2"]) {
          throw new BgaUserException(clienttranslate("There is no free spot on that site"));
        }
      }

      if ($this->freeWorkerAmt($playerId) <= 0 && is_null($relocateFrom)) {
        throw new BgaUserException(clienttranslate("You have no free archaeologist"));
      }

      $targetSlotNo = $targetSlot === "slot1" ? 0 : 1;
    }

    $artActive = $this->getGameStateValue("art-active");
    if ($artActive >= 0 && Artefact::from($artActive) == Artefact::Passage_Shell && $siteId > 4) {
      throw new BgaUserException(clienttranslate("You must travel to a camp site"));
    }

    $fromSlot = null;
    if (!is_null($relocateFrom)) {
      $position = $this->getObjectFromDB("SELECT * FROM board_position WHERE (slot1 = $playerId OR slot2 = $playerId) AND idboard_position = $relocateFrom");
      if( $position ) {
        $fromSlot = ( $position['slot2'] == $playerId ) ? 2 : 1;
      }
      else {
        throw new BgaUserException(clienttranslate("You don't have an archaeologist at the location you are trying to move from"));
      }
      $slotField = 'slot'.$fromSlot;
      $this->dbQuery("UPDATE board_position SET $slotField = NULL WHERE idboard_position = $relocateFrom");

      if ($siteId == "home") {
        $this->notifyAllPlayers("moveWorker", clienttranslate('${player_name} moves his archaeologist back to the camp'),
          array(
          "player_name" => $this->getCurrentPlayerName(),
          "playerId" => $this->getCurrentPlayerId(),
          "siteId" => "home",
          "from" => $relocateFrom,
          "fromSlot" => $fromSlot
          ));
        return;
      }
    }
    else {
      $this->payTravel($this->gameData->siteTravelCost($siteId, $targetSlotNo), $movePayment);
    }


    $this->dbQuery("UPDATE board_position SET $targetSlot = $playerId WHERE idboard_position = $siteId");
    $this->notifyAllPlayers("moveWorker", clienttranslate('${player_name} moves his archaeologist to a site'),
    array(
    "player_name" => $this->getCurrentPlayerName(),
    "playerId" => $this->getCurrentPlayerId(),
    "siteId" => $siteId,
    "slot" => $targetSlot === "slot1" ? 1 : 2,
    "from" => $relocateFrom,
    "fromSlot" => $fromSlot
    ));

    $siteTile = $this->getObjectFromDB("SELECT * FROM location WHERE is_at_position = $siteId");
    if ($siteTile) {
      $this->siteEffect($siteTile["size"], $siteTile["num"]);

    }
    else {
      if ($siteId < 5) {
        throw new BgaUserException("Weird, you are trying to discover base tile");
      }
      if ($siteId < 13) {
        $compassAmt = -3;
      }
      else {
        $compassAmt = -6;
      }
      $this->gainResource("compass", $playerId, $compassAmt, array("component" => "sitebox", "id" => $siteId));
      if ($compassAmt == -6) {
        $this->gainResource("idol", $playerId, 1);
      }

      $this->notifyAllPlayers("idolEffect", clienttranslate('${player_name} evaluates the effect of the idol'),
        array(
        "player_name" => $this->getCurrentPlayerName(),
        "playerId" => $this->getCurrentPlayerId()
      ));
      $idolEffect = $this->getNonEmptyObjectFromDB("SELECT * FROM board_position WHERE idboard_position = $siteId")["idol_bonus"];
      $this->setGameStateValue("site-buffer", $siteId);
      $this->gamestate->nextState("discover");
      switch($idolEffect) {
        case "exile":
          $this->gamestate->nextState("idolExile");
          break;
        case "upgrade":
          $this->gamestate->nextState("idolUpgrade");

          break;
        case "refresh":
          if (count($this->sqlWrapper->getPlayerAssistants($playerId)) > 0) {
            $this->gamestate->nextState("idolRefresh");
          }
          else {
            $this->notifyAllPlayers("noRefresh", clienttranslate("No assistants to refresh"), array());
            $this->revealLocation();
          }
          //TODO
          break;
        default:
          $this->gainResource($idolEffect, $playerId, 1, array("component" => "sitebox", "id" => $siteId));
          $this->revealLocation();
      }
      $this->notifyAllPlayers("idolGain", '',
        array(
        "player_name" => $this->getCurrentPlayerName(),
        "playerId" => $this->getCurrentPlayerId(),
        "position" => $siteId
      ));

    }
  }
  function cancelTravel() {
    $this->gamestate->nextState("cancel");
    $this->notifyAllPlayers(
      "cancelTravel",
      clienttranslate('${player_name} chooses not to travel'),
      array(
        "player_name" => $this->getActivePlayerName()
      )
    );
  }
  function revealLocation() {
    $siteId = $this->getGameStateValue("site-buffer");
    if ($siteId < 5) {
      throw new BgaUserException("Weird, you are trying to discover base tile");
    }

    if ($siteId < 13) {
      $size = 'small';
    }
    else {
      $size = 'big';
    }
    $amt = 1;
    /*if ($size == "big") {
      $amt = 2;
    }*/
    $playerId = $this->getActivePlayerId();
    $this->gainResource("idol", $playerId, $amt);
    $this->dbQuery("UPDATE board_position SET idol_bonus = NULL WHERE idboard_position = $siteId");

    $newSite = $this->getObjectFromDB("SELECT * FROM location WHERE size = '$size' AND is_open = 0  ORDER BY deck_order LIMIT 1");
    $id = $newSite['idlocation'];

    $this->dbQuery("UPDATE location SET is_open = 1, is_at_position = $siteId WHERE idlocation = $id");
    $this->notifyAllPlayers(
      "discoverLocation",
      clienttranslate('${player_name} discovers a new location'),
      array(
        "player_name" => $this->getCurrentPlayerName(),
        "player_id" => $this->getCurrentPlayerId(),
        "locationSize" => $size,
        "locationNum" => $newSite["num"],
        "locationId" => $id,
        "boardPosition" => $siteId
      )
    );
    $this->incStat(1, "sites-discovered", $playerId);
    $this->incStat(1, "sites-discovered-".$size, $playerId);
    $this->undoSavePoint();
    $this->setGameStateValue("site-buffer", -1);
    $this->setGameStateValue("guard-buffer", $siteId);
    $this->siteEffect($newSite["size"], $newSite["num"]);
  }

  function placeGuard($siteId) {
    $guard = $this->getObjectFromDB("SELECT * FROM guardian WHERE at_location IS NULL AND in_hand IS NULL ORDER BY deckorder LIMIT 1");
    $id = $guard["idguardian"];
    $this->dbQuery("UPDATE guardian SET at_location = $siteId WHERE idguardian = $id");
    $this->notifyAllPlayers(
      "newGuardian",
      clienttranslate('A wild guardian appears'),
      array(
        "guardId" => $id,
        "guardNum" => $guard["num"],
        "boardPosition" => $siteId
      )
    );
    $this->undoSavePoint();
    $this->setGameStateValue("guard-buffer", -1);
  }

  function siteEffect($size, $num, $free = false) {
    if (!$free) {
      $this->gamestate->nextState("siteEffect");
    }
    $playerId = $this->getActivePlayerId();
    $gains = $this->gameData->siteEffects($size, $num);
    $artActive = $this->getGameStateValue("art-active");
    $double = $size == "basic" && $artActive >= 0 && Artefact::from($artActive) == Artefact::Passage_Shell;
    $iters = 1;
    if ($double) {
      $iters = 2;
    }
    $mustDiscard = false;
    $buyFreeItem = false;
    foreach($gains as $type => $amt) {
      if ($type == "discardforjewel") {
        $mustDiscard = true;
        $this->gainResource("jewel", $playerId, 1, array("component" => "site", "size" => $size, "num" => $num));
      }
      else if ($type == "buyfreeitem") {
        $buyFreeItem = true;
      }
      else {
        for ($i = 0; $i < $iters; $i++) {
          $this->gainResource($type, $playerId, $amt, array("component" => "site", "size" => $size, "num" => $num));
        }
      }
    }
    $this->incStat($double ? 2 : 1, "sites-activated", $playerId);
    $this->incStat($double ? 2 : 1, "sites-activated-".$size, $playerId);
    if ($buyFreeItem) {
      $this->setGameStateValue("discount-coins", 9999);
      $this->gamestate->nextState("plane");
    }
    else if ($mustDiscard) {  // is discard jewel
      if ($double) {
        $this->gamestate->nextState("jewelDiscardShell");
      }
      else {
        $this->gamestate->nextState("jewelDiscard");
      }
    }
    else {
      $this->setGameStateValue("art-active", -1);
      $siteId = $this->getGameStateValue("guard-buffer");
      if ($siteId > -1) {
        $this->placeGuard($siteId);
      }
      if (!$free) {
        $this->gamestate->nextState("evalDone");
      }
    }
  }

  function planeCompass() {
    $this->checkAction("planeCompass");
    $this->gainResource("compass", $this->getActivePlayerId(), 2);
    $this->gamestate->nextState("main_action_done");
    $siteId = $this->getGameStateValue("guard-buffer");
    if ($siteId > -1) {
      $this->placeGuard($siteId);
    }
  }

  function idolExile($cardId) {
    $this->checkAction("exile");
    $this->exile($cardId);
    //$this->gamestate->nextState("exileDone");
  }
  function clientExile($cardId) {
    $this->checkAction("exile");
    $name = $this->gamestate->state()['name'];
    if ($name == "idolExile" || $name == "mayExile") {
      $this->exile($cardId);
      return;
    }
    if ($name == "researchBonus") {
      if ($this->currentSpecialResearch() == "exile" && $this->getGameStateValue("special-research-done") != 1) {
        $this->exile($cardId);

        $this->setGameStateValue("special-research-done", 1);
        $this->didResearch();
        return;
      }
    }
    throw new BgaUserException("Trying to exile a card which is not possible right now");
  }
  function didResearch() {
    if ($this->researchDone()) {
      $this->gamestate->nextState("research_done");
    }
    else {
      $this->gamestate->nextState("researchRemains");
    }
  }
  function exile($cardId, $fromSupply = false) {
    if ($cardId == "cancel") {
      $this->notifyAllPlayers('exileCancel', clienttranslate('${player_name} chooses not to exile anything'),
      array("player_name" => self::getActivePlayerName())
      );
      /*if ($this->gamestate->state()['name'] == "researchBonus") {
        $this->setGameStateValue("special-research-done", 1);
        $this->didResearch();
      }*/
    }
    else {
      $card = $this->sqlWrapper->getCardFromId($cardId);
      if ($fromSupply) {
        if (!$card || $card['position'] != 'supply') {
          throw new BgaUserException("Invalid attempt to exile card");
        }
      }
      else {
        if (!$card || ($card['position'] != 'hand' && $card['position'] != 'play')) {
          throw new BgaUserException("Invalid attempt to exile card");
        }
      }

      $notif = [["msg" => clienttranslate('${playerName} exiles ${cardName}')]];
      $this->sqlWrapper->moveCard(["exileCard"], $card, null, 'discard', $notif);
      $this->incStat(1, "exiled", $this->getActivePlayerId());
    }

    switch($this->gamestate->state()['name']) {
      case "idolExile":
        $this->revealLocation();
        break;
      case "mayExile":
        if ($this->researchDone()) {
          $this->gamestate->nextState("exileDone");
        }
        else {
          //$this->gamestate->nextState("exileDone");
          $this->gamestate->nextState("researchRemains");
        }
    }
  }

  function useGuard($guardNum, $arg = "") {
    $this->checkAction("useGuardPower");
    $arg = base64_decode($arg);
    $playerId = $this->getCurrentPlayerId();
    if (!$this->getObjectFromDB("SELECT * FROM guardian WHERE ready = 1 AND num = $guardNum AND in_hand = $playerId")) {
      throw new BgaUserException("That is not your guardian boon");
    }
    $this->dbQuery("UPDATE guardian SET ready = 0 WHERE num = $guardNum");
    $this->notifyAllPlayers("useGuard", clienttranslate('${player_name} uses his guardian boon'), array(
      "player_name" => $this->getActivePlayerName(),
      "player_id" => $this->getActivePlayerId(),
      "guardNum" => $guardNum
    ));

    $reward = $this->gameData->guardianReward($guardNum);
    if (isset($reward["card"])) {
      $this->gainResource("card", $playerId, 1);
    }
    else if (isset($reward["exile"])) {
      $this->exile($arg);
    }
    else if (isset($reward["upgrade"])) {
      $this->upgrade($arg, true);
    }
    else {
      throw new BgaUserException("Cannot use guard $guardNum");
    }
  }

  function useAssistant($assNum, $assArg = "") {
    $assistant = $this->sqlWrapper->getAssistantFromNum($assNum);
    $playerId = $this->getActivePlayerId();
    if ($this->getCurrentPlayerId() != $playerId) {
      throw new BgaUserException("It is not your turn");
    }
    if (is_null($assistant["in_hand"]) &&
    ($this->currentSpecialResearch() == "assistant-silver" || $this->currentSpecialResearch() == "assistant-special") && $this->getGameStateValue("special-research-done") == 0) {
      $this->getNewAssistant($assNum);
      return;
    }
    if ($assistant["in_hand"] == $playerId) {
      if ($this->currentSpecialResearch() == "assistant-gold" && $this->getGameStateValue("special-research-done") == 0) {
        $this->upgradeAssistant($assNum);
        $this->refreshAssistant($assNum);
      }
      else if ($this->currentSpecialResearch() == "assistant-refresh") {
        $this->refreshAssistant($assNum);
        $this->setGameStateValue("special-research-done", 1);
        $this->didResearch();
      }
      else if ($this->gamestate->state()["name"] == "idolRefresh") {
        $this->refreshAssistant($assNum);
        $this->revealLocation();
      }
      else if ($assistant["ready"] == 1) {
        $effect = $this->gameData->assistantPower($assNum, $assistant["gold"]);
        if (isset($effect["discount"])) {
          $this->checkAction("useActionAssistant");
        }
        $this->assistantEffect($assNum, $assArg, $assistant["gold"]);
        return;
      }

      else {
        throw new BgaUserException(clienttranslate("Cannot do anything with that assistant right now"));
      }
      return;
    }
    if ($this->gamestate->state()["name"] == "artActivateAss") {
      $artActive = $this->getGameStateValue("art-active");
      if ($assistant["in_offer"] == 4) {
        throw new BgaUserException(clienttranslate("You cannot use artifacts with survivors from the first expedition (this is not a bug, this rule is confirmed with the game designers)"));
      }
      if ($artActive >=0 && is_null($assistant["in_hand"])) {
        if (Artefact::from($artActive) == Artefact::Ancient_Wine) {
          $effect = $this->gameData->assistantPower($assNum, true);
          $this->assistantEffect($assNum, $assArg, true);
          if (!isset($effect["discount"])) {
            $this->artDone();
          }
          return;
        }
        if (Artefact::from($artActive) == Artefact::Coconut_Flask) {
          $effect = $this->gameData->assistantPower($assNum, false);
          $this->assistantEffect($assNum, $assArg, false);
          if (!isset($effect["discount"]) && !isset($effect["discard"])) {
            $this->artDone();
          }
          return;
        }
      }
    }
    throw new BgaUserException(clienttranslate("Nothing to do with that assistant right now"));
  }
  function getNewAssistant($assNum) {
    $playerId = $this->getActivePlayerId();
    $assistant = $this->sqlWrapper->getAssistantFromNum($assNum);
    if ($assistant["in_hand"]) {
      throw new BgaUserException(clienttranslate("Trying to get assistant not in a stack"));
    }
    $stackId = $assistant["in_offer"];
    $assistants = $this->sqlWrapper->getAssistantsStack($stackId);
    $numAssistants = count($assistants);
    if ($numAssistants == 0) {
      throw new BgaUserException(clienttranslate("No assistant available in this stack"));
    }
    $topAssistantNum = $assistants[0]["num"];
    if ($this->currentSpecialResearch() == "assistant-special") {
      if ($assistant["in_offer"] != 4) {
        throw new BgaUserException(clienttranslate("You must select an assistant from the current research space"));
      }
    }
    else {
      if ($topAssistantNum != $assNum) {
        throw new BgaUserException(clienttranslate("trying to get assistant that is not at the top of the deck"));
      }
      if ($assistant["in_offer"] == 4) {
        throw new BgaUserException(clienttranslate("You must select an assistant from one of the 3 stacks at the bottom right of the board"));
      }
    }
    $revealedAss = ($numAssistants > 1 && $topAssistantNum == $assNum)?$assistants[1]:NULL;
    $this->sqlWrapper->moveAssistantFromStack($assNum, $playerId, clienttranslate('${player_name} got an assistant'), $revealedAss, ($numAssistants - 1), $stackId);
    if ($numAssistants > 1) {
      $this->undoSavePoint();
    }

    $this->setGameStateValue("special-research-done", 1);
    $this->didResearch();
  }
  function assistantEffect($assNum, $assArg, $gold) {
    $assistant = $this->sqlWrapper->getAssistantFromNum($assNum);
    if ($assistant["in_hand"]) {
      $this->sqlWrapper->changeAssistantUsed($assNum, true, clienttranslate('${player_name} uses an assistant'));
    }

    $assArg = base64_decode($assArg);
    $playerId = $this->getActivePlayerId();
    $this->incStat(1, "assistant-activated", $playerId);
    $this->incStat(1, "assistant-activated-".($gold ? "gold" : "silver"), $playerId);
    $resArg = array("component" => "assistant", "num" => $assNum);
    $effect = $this->gameData->assistantPower($assNum, $gold);
    $ressources = [];
    if (isset($effect["ressourcesChoice"])) {
      $choice = ($assArg == "jewel") ? 1 : 0;
      $ressources = $effect["ressourcesChoice"][$choice];
    }
    else if (isset($effect["ressources"])) {
      $ressources = $effect["ressources"];
    }
    if (isset($effect["payboot"])) {
      $this->payTravel([BOOT], json_decode($assArg, true));
    }
    if (isset($effect["exile"])) {
      $this->exile($assArg);
    }
    if (isset($effect["discard"])) {
      $this->gamestate->nextState("assistantDiscard");
    }
    if (isset($effect["upgrade"])) {
      $this->upgrade($assArg, true);
    }
    if (isset($effect["discount"])) {
      $this->setGameStateValue("discount-coins", $effect["discount"]);
      $this->setGameStateValue("discount-compass", $effect["discount"]);
      $artActive = $this->getGameStateValue("art-active");
      $freeAction = false;
      $card = $this->sqlWrapper->getCardFromId($assArg);
      if ($artActive >=0 && (Artefact::from($artActive) == Artefact::Coconut_Flask || Artefact::from($artActive) == Artefact::Ancient_Wine) && $card["info"]->type() == "art") {
        $freeAction = true;
      }
      $this->buyCard($assArg, false, !$freeAction);
      // not main action if on board
    }

    foreach ($ressources as $ressource => $amt) {
      $this->gainResource($ressource, $playerId, $amt, $resArg);
    }
  }
  function upgradeAssistant($assNum) {
    $playerId = $this->getActivePlayerId();
    $assistant = $this->sqlWrapper->getAssistantFromNum($assNum);
    if (!$assistant["in_hand"] || $assistant["gold"] == 1) {
      throw new BgaUserException(clienttranslate("Cannot upgrade this assistant"));
    }
    $this->sqlWrapper->changeAssistantUpgarded($assNum, true, clienttranslate('${player_name} upgrades his assistant to gold'));
    $this->setGameStateValue("special-research-done", 1);
    $this->didResearch();
  }
  function refreshAssistant($assNum) {
    $this->sqlWrapper->changeAssistantUsed($assNum, false, clienttranslate('${player_name} refreshes his assistant'));
  }
  function clickResearch($researchId) {
    $this->checkAction("research");
    $this->research($researchId);
  }
  function research($researchId, $free = false, $forceType = null) {
    $playerId = $this->getActivePlayerId();
    $playerResearch = $this->getNonEmptyObjectFromDB("SELECT research_glass glass, research_book book FROM player WHERE player_id = $playerId");
    $glassOptions = $this->gameData->researchPossibilities($playerResearch["glass"]);
    $bookOptions = $this->gameData->researchPossibilities($playerResearch["book"]);

    if (in_array($researchId, $glassOptions)) {
      $researchType = "glass";
    }
    else if (in_array($researchId, $bookOptions)) {
      if ($this->gameData->researchStep($researchId) > $this->gameData->researchStep($playerResearch["glass"])) {
        throw new BgaUserException(clienttranslate("Book can never be higher than glass"));
      }
      $researchType = "book";
      if ($researchId == 14) {
        throw new BgaUserException(clienttranslate("Book cannot go to the highest research spot"));
      }
    }
    else {
      throw new BgaUserException(clienttranslate("None of your tokens can go to that research space"));
    }
    $this->setGameStateValue("research-curr-type", $researchType == "book" ? 1 : 0);
    if (!is_null($forceType) && $forceType != $researchType) {
      throw new BgaUserException(clienttranslate("You must research with your $forceType"));
    }
    $toPay = $this->gameData->researchCost($researchId);
    $resArg = array("component" => "research", "id" => $researchId);
    if (!$free) {
      foreach($toPay as $resName => $amt) {
        $this->gainResource($resName, $playerId, -$amt, $resArg);
      }
    }

    $this->dbQuery("UPDATE player SET research_$researchType = $researchId WHERE player_id = $playerId");
    $researchDone = true;

    $step = $this->gameData->researchStep($researchId);
    $rank = null;
    $stepBonus = $this->gameData->researchBonus($step, $researchType === "book");
    $researchBonus = $this->getCollectionFromDb("SELECT * FROM research_bonus WHERE track_pos = $researchId");
    if ($step == 8) {
      $this->undoSavePoint();
      $rank = count($this->getCollectionFromDb("SELECT * FROM player WHERE research_glass = 14"));
      $this->dbQuery("UPDATE player SET temple_rank = $rank WHERE player_id = $playerId");
    }
    $this->notifyAllPlayers('research', clienttranslate('${player_name} researches with his ${type}'), array("player_name" => $this->getActivePlayerName(),
      "player_id" => $this->getActivePlayerId(),
      "researchId" => $researchId,
      "type" => $researchType,
      "rank" => $rank,
      "i18n" => ['type'],
    ));

    $this->setGameStateValue("special-research-done", 1);
    switch($stepBonus) {
      case "3compass":
        $this->gainResource("compass", $playerId, 3, $resArg);
        break;
      case "2coins":
        $this->gainResource("coins", $playerId, 2, $resArg);
        break;
      case "assistant-special":
        $this->undoSavePoint();
        // cascade to next
      case "assistant-silver":
      case "assistant-gold":
      case "assistant-refresh":
      case "exile":
      case "free-art":
        $this->setGameStateValue("special-research-done", 0);
        break;
      case "guard":
        $guards = $this->availableGuardians();
        if (count($guards) == 0) {
          break;
        }
        if (count($guards) == 1) {
          $this->overcomeGuard($guards[0]["num"], array(), true);
          break;
        }
        if (count($guards) == 2) {
          $this->setGameStateValue("special-research-done", 0);
          break;
        }
        throw new BgaUserException("More than 2 guards found");
        break;
      case "":
        break;
      default:
        $this->gainResource($stepBonus, $playerId, 1, $resArg);
        break;
    }
    $this->setGameStateValue("research-token-done", 1);
    $instaUse = false;
    if (count($researchBonus) === 1) {
      $this->setGameStateValue("research-token-done", 0);
      $b = array_values($researchBonus)[0];
      $instaUse = true;
      //throw new BgaUserException(JSON_ENCODE($b));
      switch($b["bonus_type"]) {
        case "upgrade":  case "exile":
          $instaUse = false;
          break;
        case "card":
          if ($stepBonus == "free-art") {
            $instaUse = false;
          }
          break;
      }
      $id = $b["idresearch_bonus"];
    }
    else if (count($researchBonus) > 1) {
      $this->setGameStateValue("research-token-done", 0);
    }
    if ($instaUse) {
      $this->setGameStateValue("research-token-done", 1);
      $this->gamestate->nextState("research_bonus");
      $this->useToken($b["idresearch_bonus"], "", true);
      $this->dbQuery("DELETE FROM research_bonus WHERE idresearch_bonus = $id");
      $this->notifyAllPlayers("removeResearchToken", "", array("tokenId" => $id));
    }
    else if (count($researchBonus) > 0) {
      $this->setGameStateValue("research-token-done", 0);
      $this->gamestate->nextState("research_bonus");
    }
    else {
      $this->gamestate->nextState("research_bonus");
    }

    if ($this->researchDone() && $this->gamestate->state()["name"] == "researchBonus") {
      $this->gamestate->nextState("research_done");
    }
  }
  function clickTempleTile($num) {
    $this->checkAction("getTempleTile");
    $this->getTempleTile($num);
    $this->gamestate->nextState("main_action_done");
  }
  function getTempleTile($num) {
    $cost = $this->gameData->templeTileCost($num);
    $playerId = $this->getActivePlayerId();
    $glassResearch = $this->getNonEmptyObjectFromDB("SELECT * FROM player WHERE player_id = $playerId")["research_glass"];
    if ($glassResearch < 14) {
      throw new BgaUserException(clienttranslate("You must reach the top of research track with your magnifying glass before getting temple tiles"));
    }
    foreach ($cost as $resName => $amt) {
      $this->gainResource($resName, $playerId, -$amt, array("component" => "temple", "num" => $num));
    }
    $color = $this->gameData->templeTileColor($num);
    $this->dbQuery("UPDATE player SET temple_$color = temple_$color + 1 WHERE player_id = $playerId");
    $this->dbQuery("UPDATE temple_tile SET amt = amt - 1 WHERE idtemple_tile = $num");
    $this->notifyAllPlayers("getTempleTile", clienttranslate('${player_name} gets a ${colorText} temple tile'),
    array(
      "player_name" => $this->getActivePlayerName(),
      "player_id" => $playerId,
      "color" => $color,
      "colorText" => array("bronze" => clienttranslate("bronze"), "silver" => clienttranslate("silver"), "gold" => clienttranslate("gold"))[$color],
      "num" => $num,
      "i18n" => ['colorText'],
    )
    );
    $this->incStat(1, "temple", $playerId);
    $this->incStat(1, "temple-".$color, $playerId);

  }
  function useToken($id, $arg = "", $noCheck = false) {
    if (!$noCheck) {
      $this->checkAction("useResearchToken");
    }
    $playerId = $this->getActivePlayerId();
    $trackPos = $this->getNonEmptyObjectFromDB("SELECT * FROM player WHERE player_id = $playerId")["research_".$this->researchType()];
    $b = $this->getNonEmptyObjectFromDB("SELECT * FROM research_bonus WHERE idresearch_bonus = $id AND track_pos = $trackPos");

    switch($b["bonus_type"]) {
      case "upgrade":
        $this->upgrade($arg, true);
        break;
      case "exile":
        $this->exile($arg);
        break;
      default:
        $this->gainResource($b["bonus_type"], $playerId, 1, array("component" => "research", "id" => $trackPos));
        break;
    }

    $this->incStat(1, "tokens-used", $playerId);

    $this->dbQuery("DELETE FROM research_bonus WHERE idresearch_bonus = $id");
    $this->setGameStateValue("research-token-done", 1);
    $this->notifyAllPlayers("removeResearchToken", "", array("tokenId" => $id));
    if ($this->researchDone()) {
      $this->gamestate->nextState("research_done");
    }
  }
  function researchType() {
    return $this->getGameStateValue("research-curr-type") == "1" ? "book" : "glass";
  }
  function currentSpecialResearch() {
    if ($this->gamestate->state()['name'] != "researchBonus") {
      return "";
    }
    $playerId = $this->getActivePlayerId();
    $step = $this->gameData->researchStep($this->getNonEmptyObjectFromDB(
        "SELECT * FROM player WHERE player_id = $playerId"
      )["research_".$this->researchType()]
    );

    return $this->gameData->researchBonus($step, $this->researchType() == "book");
  }
  function researchDone() {
    return $this->getGameStateValue("special-research-done") == "1" && $this->getGameStateValue("research-token-done") == "1";
  }
  function clientUpgrade($type) {
    $this->checkAction("upgrade");
    $this->upgrade($type);
  }
  function upgrade($type, $paid = false) {
    $playerId = $this->getActivePlayerId();
    switch($type) {
      case 1:
        $this->gainResource("tablet", $playerId, -1);
        $this->gainResource("arrowhead", $playerId, 1);
        break;
      case 2:
        $this->gainResource("arrowhead", $playerId, -1);
        $this->gainResource("jewel", $playerId, 1);
        break;
      case 0:
        break;
      default:
        throw new BgaUserException("Invalid upgrade argument $type");
    }
    if (!$paid) {
      switch($this->gamestate->state()['name']) {
        case "idolUpgrade":
          $this->revealLocation();
          break;
        case "selectAction":
          break;
        default:
          throw new BgaUserException("Cannot use effect ".$this->gamestate->state()['name']);
      }
    }

  }



  function cancelIdolExile() {
    $this->idolExile("cancel");
  }
  function cancelShellDiscard() {
    $this->checkAction("cancelShell");
    $this->gainResource("jewel", $this->getActivePlayerId(), -1);
    $this->setGameStateValue("art-active", -1);
    $this->gamestate->nextState("discard_done");
  }

  function cancelExile() {
    $this->checkAction("exile");
    $this->exile("cancel");
  }
  function skipArt() {
    $this->checkAction("skipArt");
    $this->gamestate->nextState("skipArt");
  }

  function exiledItems() {
    return array("cards" => $this->sqlWrapper->getPublicCards(null, 'discard', 'item'));
  }

  function stNextRound() {


    $this->notifyAllPlayers("nextRound", clienttranslate("Setting up next round"), array());
    //$this->activeNextPlayer();

    $guardedMeeple = $this->getObjectListFromDb("SELECT p.idboard_position pos, p.slot1 player FROM guardian g INNER JOIN board_position p ON p.idboard_position = g.at_location WHERE slot1 IS NOT NULL");
    $guardedMeeple = array_merge($guardedMeeple, $this->getObjectListFromDb("SELECT p.idboard_position pos, p.slot2 player FROM guardian g INNER JOIN board_position p ON p.idboard_position = g.at_location WHERE slot2 IS NOT NULL AND slot2 != -1"));
    foreach($guardedMeeple as $g) {
      $playerId = $g["player"];
      
      $safe = false;
      if( $this->getGameStateValue("warmask-played") == 1 ) {
        $cards = $this->sqlWrapper->getCards($playerId, 'play', 'art');
        foreach ($cards as $card) {
          if ($card["info"] == Artefact::War_Mask) {
            $safe = true;
            break;
          }
        }
      }

      if ($safe) {
        $this->notifyAllPlayers("noFear", clienttranslate('${player_name} does not get fear thanks to the War Mask'), array("player_name" =>$this->loadPlayersBasicInfos()[$playerId]["player_name"]));
      }
      else {
        $this->gainResource("fear", $playerId, 1);
      }
    }
    $this->setGameStateValue("warmask-played", 0);
    $this->setGameStateValue("ocarina-played", 0);

    if (intval($this->getGameStateValue("round")) >= "5") {
      $this->gameEnd();
      return;

    }
    $this->DbQuery("UPDATE board_position SET slot1 = NULL WHERE slot1 != -1");
    $this->DbQuery("UPDATE board_position SET slot2 = NULL WHERE slot2 != -1");
    $this->notifyAllPlayers("returnWorkers", clienttranslate("Returning all archaeologists home"), array());
    $firstRound = $this->getGameStateValue("round") == "0";

    if ($firstRound) {
      $this->gamestate->nextState("decideKeep");
      $this->gamestate->nextState("allDiscarded");
    }
    else {
      $playersToActivate = $this->getObjectListFromDb("SELECT DISTINCT p.player_id id FROM player p INNER JOIN card c ON p.player_id = c.player WHERE c.card_position = 'hand' AND c.card_type != 'fear'");
      $playerArray = array_map(function($a) {return $a["id"];}, $playersToActivate);
      $this->gamestate->nextState("decideKeep");
      $this->gamestate->setPlayersMultiactive($playerArray, "allDiscarded");
    }
  }

  function confirmKeep($cardsToKeep) {
    $this->checkAction("confirmKeep");
    $playerId = $this->getCurrentPlayerId();  // !! not active, cuz multiactive state
    //throw new BgaUserException(json_encode($cardsToKeep));
    $cards = $this->sqlWrapper->getCards($playerId, 'hand');
    $keepCards = [];
    foreach ($cards as $card) {
      if (in_array($card['id'], $cardsToKeep)) {
        array_push($keepCards, $card);
      }
    }

    foreach ($keepCards as $card) {
      $notif = [["msg" => ''], null];
      $this->sqlWrapper->moveCard(['keepCard'], $card, $playerId, 'keep', $notif);
    }

    $this->gamestate->setPlayerNonMultiactive($playerId, "allDiscarded");
  }
  function stNextRoundCont() {
    $firstRound = $this->getGameStateValue("round") == "0";
    foreach ($this->loadPlayersBasicInfos() as $playerId => $player) {
      $cardsKept = $this->sqlWrapper->getCards($playerId, 'keep');
      $cardsKeptCount = count($cardsKept);

      $cards = $this->sqlWrapper->getCards($playerId, 'hand');
      foreach ($cards as $card) {
        $notif =[["msg" => clienttranslate('${playerName} discards ${cardName}')]];
        $this->sqlWrapper->moveCard(["cardPlayed"], $card, $playerId, 'play', $notif);
      }

      if ($cardsKeptCount > 0) {
        $notif = [
          ["msg" => ""],
          ["msg" => clienttranslate('${playerName} decides to keep ${cardAmt} cards to the next round'), "cardAmt" => $cardsKeptCount, "playerName" => $player["player_name"]]
        ];
        $this->sqlWrapper->moveCards(['keptCardsBackInHand', "playerKeptCards"], $playerId, 'keep', 'hand', $notif);
      }
    }

    if (!$firstRound) {
      $this->notifyAllPlayers("shufflePlay", clienttranslate("Shuffling cards from play area to the bottom of the deck"), array());

      // put play to bottom of deck
      foreach($this->loadPlayersBasicInfos() as $playerId => $player) {
        $playCards = $this->sqlWrapper->getCards($playerId, 'play');
        shuffle($playCards);
        foreach ($playCards as $card) {
          $this->sqlWrapper->moveCard([], $card, $playerId, 'deck');
        }
      }
    }

    $assistants = $this->sqlWrapper->getPlayerAssistants($playerId);
    foreach ($assistants as $assistant) {
      $this->sqlWrapper->changeAssistantUsed($assistant["num"], false);
    }
    $this->notifyAllPlayers("refreshAll", clienttranslate("Everyone refreshes their assistants"), array());

    if (!$firstRound) {
      $this->exileStaffCards();
    }

    $this->incGameStateValue("round", 1);

    $this->notifyAllPlayers("moveStaff", clienttranslate('Moving the moon staff to round ${roundNo}'), array("roundNo" => $this->staffPosition()));

    $this->refillCards();

    if (!$firstRound) {
      $this->gamestate->changeActivePlayer($this->getGameStateValue("start-player"));
      $this->activeNextPlayer();
      $this->setGameStateValue("start-player", $this->getActivePlayerId());
      $this->notifyAllPlayers("passStartMarker", clienttranslate('${player_name} receives the start player marker'), array(
        "player_name" => $this->getActivePlayerName(),
        "player_id" => $this->getActivePlayerId()
      ));

      $this->activePrevPlayer();  // activeNextPlayer on nextPlayer state
    }


    foreach($this->loadPlayersBasicInfos() as $playerId => $player) {
      $cardAmt = 5 - count($this->sqlWrapper->getCards($playerId, 'hand'));
      $this->gainResource("card", $playerId, $cardAmt);
      $this->DbQuery("UPDATE player SET passed = 0");
    }

    $this->gamestate->nextState('nextPlayer');
  }
  function categoryText($category) {
    return array(
    "research" => clienttranslate("research"),
    "temple" => clienttranslate("temple tiles"),
    "idols" => clienttranslate("idols and empty slots"),
    "guardians" => clienttranslate("overcame guardians"),
    "cards" => clienttranslate("items and artifacts"),
    "fear" => clienttranslate("fear"),
    )[$category];
  }
  function finalScoring() {
    $this->notifyAllPlayers("startScoring", "", array());
    foreach(["research", "temple", "idols", "guardians", "cards", "fear"] as $category) {
      foreach ($this->loadPlayersBasicInfos() as $playerId => $player) {
        $score = $this->score($category, $playerId);
        $this->DbQuery("UPDATE player SET player_score=player_score + $score WHERE player_id=$playerId");
        if ($score != 0) {
          $this->notifyAllPlayers("score", clienttranslate('${player_name} scores ${score} for ${categoryText}'),
          array(
            "i18n" => ["categoryText"],
            "player_name" => $this->loadPlayersBasicInfos()[$playerId]["player_name"],
            "player_id" => $playerId,
            "score" => $score,
            "category" => $category,
            "categoryText" => $this->categoryText($category)
          )
          );
        }
        $this->setStat($score, "score-".$category, $playerId);
      }
    }
    foreach ($this->loadPlayersBasicInfos() as $playerId => $player) {
      $player = $this->getNonEmptyObjectFromDB("SELECT * FROM player WHERE player_id = $playerId");
      $auxScore = $this->score("research", $playerId);
      $this->setStat($this->score("art", $playerId), "score-art", $playerId);
      $this->setStat($this->score("item", $playerId), "score-item", $playerId);

      $cost = 0;
      foreach ($this->sqlWrapper->getCards($playerId, NULL, 'item') as $card) {
          $cost += $this->gameData->cardCost($card["info"]);
        }
      $this->setStat($cost, "cost-item", $playerId);
      $cost = 0;
      foreach ($this->sqlWrapper->getCards($playerId, NULL, 'art') as $card) {
          $cost += $this->gameData->cardCost($card["info"]);
        }
      $this->setStat($cost, "cost-art", $playerId);

      $this->setStat($this->gameData->researchStep($player["research_book"]), "book-step", $playerId);
      $this->setStat($this->gameData->researchStep($player["research_glass"]), "glass-step", $playerId);

      if ($this->gameData->researchStep($player["research_glass"]) == 8) {
        $auxScore += 100 * (5 - $player["temple_rank"]);
      }
      $this->DbQuery("UPDATE player SET player_score_aux = $auxScore WHERE player_id = $playerId");
    }

  }

  function score($category, $playerId) {
    $score = 0;
    $player = $this->getNonEmptyObjectFromDB("SELECT * FROM player WHERE player_id = $playerId");
    switch($category) {
      case "research":
        $score =
          $this->gameData->stepPoints(true, $this->gameData->researchStep($player["research_book"])) +
          $this->gameData->stepPoints(false, $this->gameData->researchStep($player["research_glass"]), $player["temple_rank"]);
        break;
      case "temple":
        $score = $player["temple_bronze"] * 2 + $player["temple_silver"] * 6 + $player["temple_gold"] * 11;
        break;
      case "idols":
        $score = [12, 13, 13, 12, 10][$player["idol_slot"]] + 3 * $player["idol"];
        break;
      case "guardians":
        $score = count($this->getCollectionFromDb("SELECT * FROM guardian WHERE in_hand = $playerId")) * 5;
        break;
      case "cards":
        foreach ($this->sqlWrapper->getCards($playerId) as $card) {
          $cardInfo = $card["info"];
          if ($cardInfo->type() != 'basic') {
            $score += $this->gameData->cardPoints($cardInfo);
          }
        }
        break;
      case "fear":
        foreach ($this->sqlWrapper->getCards($playerId, NULL, 'fear') as $card) {
          $score += $this->gameData->cardPoints($card["info"]);
        }
        break;
      case "item":
        foreach ($this->sqlWrapper->getCards($playerId, NULL, 'item') as $card) {
          $score += $this->gameData->cardPoints($card["info"]);
        }
        break;
      case "art":
        foreach ($this->sqlWrapper->getCards($playerId, NULL, 'art') as $card) {
          $score += $this->gameData->cardPoints($card["info"]);
        }
        break;
    }
    return $score;
  }

  function stCheckLastPlayer() {
    if (!$this->researchDone()) {
      $this->gamestate->nextState("researchRemains");
      return;
    }
    $passedNum = count($this->getCollectionFromDb("SELECT * FROM player WHERE passed != 1"));
    if ($passedNum == 1) {
      $this->notifyAllPlayers("endTurn", "");
      $this->gamestate->nextState("turn_end");
    }
  }

  function stNextPlayer() {
    $this->setGameStateValue("art-active", -1);
    $this->refillCards();
    $this->resetDiscount(true);
    $this->giveExtraTime($this->getActivePlayerId());
    $this->setGameStateValue("special-research-done", 1);
    $this->setGameStateValue("research-token-done", 1);
    $ingameNum = count($this->getCollectionFromDb("SELECT * FROM player WHERE passed != 1"));
    //throw new BgaUserException($passedNum);
    if ($ingameNum == 0) {
      $this->gamestate->nextState('allPassed');

      return;
    }

    do {
      $this->activeNextPlayer();

    } while ($this->getNonEmptyObjectFromDB("SELECT * FROM player WHERE player_id = ".$this->getActivePlayerId())["passed"] == 1);
    if ($ingameNum > 1) {

      $this->undoSavePoint();
    }
    $this->gamestate->nextState('playerActivated');
  }

  function getGameProgression()
  {
    $progress = 20 * ($this->getGameStateValue("round") - 1);
    $players = $this->loadPlayersBasicInfos();
    $totalMeeple = count($players) * 2;
    $freeMeeple = 0;
    foreach ($players as $playerId => $player) {
      $freeMeeple += $this->freeWorkerAmt($playerId);
    }
    $progress += 20 * (1 - ($freeMeeple / $totalMeeple));
    return $progress;
  }





  function pass($force = false) {
    if (!$force) {
      $this->checkAction("pass");
    }
    $playerId = $this->getActivePlayerId();
    $this->dbQuery("UPDATE player SET passed = 1 WHERE player_id = $playerId");

    $this->notifyAllPlayers("pass", clienttranslate('${player_name} passes'),
    array("player_name" => $this->loadPlayersBasicInfos()[$playerId]["player_name"], "player_id" => $playerId)
    );
    $this->gamestate->nextState("turn_end");
    //throw new BgaUserException("here");
    if ($this->gamestate->state()["name"] == "selectAction" && count($this->getCollectionFromDb("SELECT * FROM player WHERE passed != 1")) > 0) {
      $this->undoSavePoint();
    }

  }
  function undo() {
    $this->checkAction("undo");
    $this->undoRestorePoint();
  }
  function endTurn() {
    $playerId = $this->getActivePlayerId();
    $this->checkAction("endTurn");
    //throw new BgaUserException("here");
    $this->notifyAllPlayers("endTurn", clienttranslate('${player_name} ends his turn'),
    array("player_name" => $this->loadPlayersBasicInfos()[$playerId]["player_name"]));
    $this->gamestate->nextState("turn_end");

  }

  function gameEnd() {
    $this->gamestate->nextState('gameEnd');
    foreach ($this->loadPlayersBasicInfos() as $playerId => $player) {
      $notif = [["msg" => ""]];
      $this->sqlWrapper->moveCards(["showAllCards"], $playerId, 'hand', 'play', $notif);
      $this->sqlWrapper->moveCards(["showAllCards"], $playerId, 'deck', 'play', $notif);
    }
    $this->finalScoring();
    $this->gamestate->nextState('scoringDone');
  }

  function resetDiscount($hard = false) {
    $toReset = ["coins", "compass", "tablet", "arrowhead", "jewel", "idol", "idol_slot"];
    if ($hard) {
      $toReset = array_merge($toReset, ["ship", "car", "boot", "plane"]);
    }
    foreach($toReset as $res) {
      $this->setGameStateValue("discount-$res", 0);
    }
  }

  function resText($resName) {
    return array(
      "coins" => clienttranslate("coin"),
      "idol" => clienttranslate("idol"),
      "compass" => clienttranslate("compass"),
      "tablet" => clienttranslate("tablet"),
      "arrowhead" => clienttranslate("arrowhead"),
      "jewel" => clienttranslate("jewel"),
      "idol_slot" => clienttranslate("idol slot"),
    )[$resName];
  }

  function gainResource($resName, $player, $amt, $source = NULL) {
    if ($player == -1) {
      $player = $this->getActivePlayerId();
    }
    if ($resName === "fear") {
      $notifs =[["msg" => clienttranslate('${player_name} gains a fear')]];
      $this->sqlWrapper->createCards(["gainFear"], [Basic::Fear], $player, 'play', $notifs);
      $this->incStat(1, "gained-fear", $player);
      return;
    }
    if ($resName === "card") {
      for ($i = 0; $i < $amt; ++$i) {
        $this->drawCard($player);
      }
      $this->notifyAllPlayers("drawCards", clienttranslate('${player_name} draws ${amt} cards'), array(
        "player_name" => $this->loadPlayersBasicInfos()[$player]["player_name"],
        "amt" => $amt,
        "player_id" => $player
      ));
      return;
    }
    $discounted = false;
    if ($amt < 0) {
      $discount = $this->getGameStateValue("discount-$resName");
      if ($discount) {
        $effectiveDiscount = min(abs($amt), $discount);
        $this->notifyAllPlayers("discount", clienttranslate('${amtText} ${resName} is covered by discount'),
        array(
        "i18n" => ["resText"],
        "amtText" => $effectiveDiscount,
        "resName" => $resName,
        "resText" => $this->resText($resName)
        ));
        $amt += $effectiveDiscount;

        $this->incGameStateValue("discount-$resName", -$effectiveDiscount);
      }
    }

    $current = $this->getNonEmptyObjectFromDB("SELECT * FROM player WHERE player_id = $player")[$resName];
    if ($current + $amt < 0) {
      throw new BgaUserException(clienttranslate("Not enough ").$this->resText($resName));
    }
    $this->dbQuery("UPDATE player SET $resName = $resName + $amt WHERE player_id = $player");
    if ($amt > 0 && $resName != "idol_slot") {
      $this->incStat($amt, "gained-".$resName, $player);
    }
    if ($amt != 0) {
      $this->notifyAllPlayers("gainRes", '${player_name} ${verb} ${amtText} ${resText}',
      array("amt" => $amt,
      "i18n" => ["resText", "verb"],
      "amtText" => abs($amt),
      "player_id" => $player,
      "player_name" => $this->loadPlayersBasicInfos()[$player]["player_name"],
      "resName" => $resName,
      "resText" => $this->resText($resName),
      "verb" => $amt < 0 ? clienttranslate("pays") : clienttranslate("gains"),
      "source" => JSON_ENCODE($source)
      )
      );
    }
  }


//////////////////////////////////////////////////////////////////////////////
//////////// Zombie
////////////

  /*
    zombieTurn:

    This method is called each time it is the turn of a player who has quit the game (= "zombie" player).
    You can do whatever you want in order to make sure the turn of this player ends appropriately
    (ex: pass).

    Important: your zombie code will be called when the player leaves the game. This action is triggered
    from the main site and propagated to the gameserver from a server, not from a browser.
    As a consequence, there is no current player associated to this action. In your zombieTurn function,
    you must _never_ use getCurrentPlayerId() or getCurrentPlayerName(), otherwise it will fail with a "Not logged" error message.
  */

  function zombieTurn( $state, $active_player )
  {
    $statename = $state['name'];

    if ($state['type'] === "activeplayer") {
      switch ($statename) {
        default:
          $this->pass(true);
          break;
      }

      return;
    }

    if ($state['type'] === "multipleactiveplayer") {
      // Make sure player is in a non blocking status for role turn
      $this->gamestate->setPlayerNonMultiactive( $active_player, '' );

      return;
    }

    throw new feException( "Zombie mode not supported at this game state: ".$statename );
  }

///////////////////////////////////////////////////////////////////////////////////:
////////// DB upgrade
//////////

  /*
    upgradeTableDb:

    You don't have to care about this until your game has been published on BGA.
    Once your game is on BGA, this method is called everytime the system detects a game running with your old
    Database scheme.
    In this case, if you change your Database scheme, you just have to apply the needed changes in order to
    update the game database and allow the game to continue to run with your new version.

  */

  function upgradeTableDb( $from_version )
  {
    // $from_version is the current version of this game database, in numerical form.
    // For example, if the game was running with a release of your game named "140430-1345",
    // $from_version is equal to 1404301345

    // Example:
//    if( $from_version <= 1404301345 )
//    {
//      // ! important ! Use DBPREFIX_<table_name> for all tables
//
//      $sql = "ALTER TABLE DBPREFIX_xxxxxxx ....";
//      $this->applyDbUpgradeToAllDB( $sql );
//    }
//    if( $from_version <= 1405061421 )
//    {
//      // ! important ! Use DBPREFIX_<table_name> for all tables
//
//      $sql = "CREATE TABLE DBPREFIX_xxxxxxx ....";
//      $this->applyDbUpgradeToAllDB( $sql );
//    }
//    // Please add your future database scheme changes here
//
//


  }
}
