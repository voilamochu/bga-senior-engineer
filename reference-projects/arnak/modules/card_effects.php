<?php

class CardEffects {
  public function __construct($game, $playerId) {
    $this->game = $game;
    $this->playerId = $playerId;
  }
  public function cardEffect($card, $cardId, $arg = "") {
    $this->activeCard = $cardId;
    if ($card->type() == "art") {
      $this->artEffect($card, $arg);
    }
    else if ($card->type() == "item") {
      $this->itemEffect($card, $arg);
    }
    else if ($card->type() == "basic") {
      $this->basicEffect($card);
    }
  }

  function gainCardResource($resName, $amt) {
    $this->game->gainResource($resName, $this->playerId, $amt, array("component" => "card", "arg" => $this->activeCard));
  }
  function artEffect($artefact, $arg) {
    $game = $this->game;
    $game->gamestate->nextState("playArt");

    switch($artefact) {
      case Artefact::Pathfinders_Sandals:
      case Artefact::Pathfinders_Staff:
        $movement = JSON_DECODE($arg);
        $to = $movement->to;
        $from = $movement->from;
        if ($artefact == Artefact::Pathfinders_Sandals) {
          $site = $game->getObjectFromDb("SELECT * FROM location WHERE size='basic' AND is_at_position = $to");
        }
        else {
          $site = $game->getObjectFromDb("SELECT * FROM location WHERE (size='basic' OR size='small') AND is_at_position = $to");
        }
        if (!$site) {
          throw new BgaUserException(clienttranslate("The site you are trying to move to is not valid"));
        }
        $game->moveToSite($to, [], $from);
        break;
      case Artefact::War_Mask:
        $this->gainCardResource("arrowhead", 1);
        $game->setGameStateValue("warmask-played", 1);
        break;
      case Artefact::Treasure_Chest:
        $this->gainCardResource("card", 1);
        $this->gainCardResource("coins", 1);
        break;
      case Artefact::Ritual_Dagger:
        $game->exile($arg);
        $this->gainCardResource("arrowhead", 1);
        break;
      case Artefact::Crystal_Earring:
        if (count($game->sqlWrapper->getCards($this->playerId, 'deck')) == 0) {
          $game->artDone();
        }
        for ($i = 0; $i < $arg; ++$i) {
          $game->drawCard($this->playerId, false, "earring");
        }
        $game->gamestate->nextState("earring");
        break;
      case Artefact::Mortar:
        $game->exile($arg);
        $this->gainCardResource("coins", 2);
        break;
      case Artefact::Serpents_Gold:
        $this->gainCardResource("fear", 1);
        $this->gainCardResource("coins", 4);
        break;
      case Artefact::Serpent_Idol:
        $this->gainCardResource("fear", 1);
        $this->gainCardResource("jewel", 1);
        break;
      case Artefact::Monkey_Medallion:
        $game->setGameStateValue("discount-coins", 9999);
        $game->buyCard($arg, true, false, true);
        $game->resetDiscount();
        break;
      case Artefact::Idol_of_AraAnu:
        $game->setGameStateValue("discount-jewel", 1);
        $arg = JSON_DECODE($arg);
        if (isset($arg->temple)) {
          $game->getTempleTile($arg->temple);
          $game->artDone();
        }
        else {
          $game->research($arg);
        }
        $game->resetDiscount();
        break;
      case Artefact::Inscribed_Blade:
        $args = JSON_DECODE($arg);
        if ($args->discount == "arrowhead") {
          $game->setGameStateValue("discount-arrowhead", 1);
        }
        else {
          $game->setGameStateValue("discount-tablet", 2);
        }
        if (isset($args->temple)) {
          $game->getTempleTile($args->temple);
          $game->artDone();
        } 
        else {
          
          $game->research($args->research);
        }
        $game->resetDiscount();
        break;
      case Artefact::Guardians_Ocarina:
        $game->moveToSite("home", "", $arg);
        $game->setGameStateValue("ocarina-played", 1);
        break;
      case Artefact::Tigerclaw_Hairpin:
        $arg = JSON_DECODE($arg);
        $siteId = $arg->site;
        $exile = $arg->exile;
        $game->exile($exile);
        $site = $game->getObjectFromDb("SELECT * FROM board_position WHERE slot1 IS NULL AND (slot2 IS NULL OR slot2 = -1) AND idboard_position = $siteId");
        $siteTile = $game->getObjectFromDb("SELECT * FROM location WHERE is_at_position = $siteId AND size = 'basic'");
        if (!$site) {
          throw new BgaUserException(clienttranslate("That is not an unoccupied camp site"));
        }
        if (!$siteTile) {
          throw new BgaUserException(clienttranslate("That is not a camp site"));
        }
        $siteTile = $game->getNonEmptyObjectFromDb("SELECT * FROM location WHERE is_at_position = $siteId AND size = 'basic'");
        $game->siteEffect("basic", $siteTile["num"]);
        break;
      case Artefact::War_Club:
        $game->freeOvercome($arg);
        break;
      case Artefact::Sundial:
        if ($arg == "pass") {
          $this->gainCardResource("jewel", 1);
          $game->pass(true);
          return;
        }
        else {
          $this->gainCardResource("tablet", 2);
          $game->artDone();
        }
        break;
      case Artefact::Traders_Scales:
        $game->upgrade($arg, true);
        $this->gainCardResource("coins", 3);
        break;
      case Artefact::Hunting_Arrows:
        $this->gainCardResource("fear", 1);
        $this->gainCardResource("arrowhead", 2);
        break;
      case Artefact::Coconut_Flask:
        $this->gainCardResource("coins", 2);
        $game->setGameStateValue("art-active", $artefact->value);
        $game->gamestate->nextstate("activateAss");
        break;
      case Artefact::Cleansing_Cauldron:
        $this->gainCardResource("card", 1);
        $game->gamestate->nextstate("artExile");
        break;
      case Artefact::Ancient_Wine:
        $this->gainCardResource("coins", 1);
        $game->setGameStateValue("art-active", $artefact->value);
        $game->gamestate->nextstate("activateAss");
        break;
      case Artefact::Decorated_Horn:
        $args = JSON_DECODE($arg);
        $old = $args->oldAss;
        $new = $args->newAss;
        $oldAss = $game->sqlWrapper->getAssistantFromNum($old);
        if ($oldAss["in_hand"] != $this->playerId) {
          throw new BgaUserException(clienttranslate("You must select an assistant from your board"));
        }
        $newAss = $game->sqlWrapper->getAssistantFromNum($new);
        if ($newAss["in_hand"]) {
          throw new BgaUserException(clienttranslate("You must select an assistant from a stack"));
        }
        $slot = $newAss["in_offer"];
        $gold = $oldAss["gold"];

        $stackId = $newAss["in_offer"];
        $assistants = $game->sqlWrapper->getAssistantsStack($stackId);
        $numAssistants = count($assistants);
        if ($numAssistants == 0) {
          throw new BgaUserException(clienttranslate("No assistant available in this stack"));
        }
        if ($assistants[0]["num"] != $args->newAss) {
          throw new BgaUserException(clienttranslate("trying to get assistant that is not at the top of the deck"));
        }
        if ($newAss["in_offer"] == 4) {
          throw new BgaUserException(clienttranslate("You must select an assistant from one of the 3 stacks at the bottom right of the board"));
        }

        $game->sqlWrapper->changeAssistantUpgarded($old, false);
        $game->sqlWrapper->changeAssistantUsed($old, false);
        $game->sqlWrapper->swapAssistants($old, $new, $this->playerId, '${player_name} returns his assistant to the supply', clienttranslate('${player_name} got an assistant'), $numAssistants, $stackId);
        $game->sqlWrapper->changeAssistantUpgarded($args->newAss, $gold);
        break;
      case Artefact::Ornate_Hammer:
        $cards = $game->sqlWrapper->getCards(null, 'supply', 'item');
        if(count($cards) > 0) {
          $toExile = end($cards);
          $game->exile($toExile["id"], true);
        }
        $game->gamestate->nextState("discardSelect");
        break;
      case Artefact::Star_Charts:
        $this->gainCardResource("coins", -1);
        $siteIds = JSON_DECODE($arg);
        if ($siteIds[0] == $siteIds[1]) {
          throw new BgaUserException(clienttranslate("You must select 2 different sites"));
        }
        if ($siteIds[0] > 4 || $siteIds[1] > 4) {
          throw new BgaUserException(clienttranslate("You must select 2 basic sites"));
        }
        if ($siteIds[0] == 4) {
          $game->siteEffect("basic", $siteIds[1], true);
          $game->siteEffect("basic", $siteIds[0]);
        }
        else {
          $game->siteEffect("basic", $siteIds[0], true);
          $game->siteEffect("basic", $siteIds[1]);
        }
        break;
      case Artefact::Stone_Jar:
        $this->gainCardResource("card", 1);
        break;
      case Artefact::Passage_Shell:
        $game->setGameStateValue("discount-boot", 2);
        $game->gamestate->nextState("mayTravel");
        break;
      case Artefact::Ceremonial_Rattle:
        if (!$arg) {
          break;
        }

        $assistant = $game->sqlWrapper->getAssistantFromNum($arg);
        if($assistant['in_hand'] != $this->playerId) {
          throw new BgaUserException(clienttranslate("Nothing to do with that assistant right now"));
        }
        $game->refreshAssistant($arg);
        break;
      case Artefact::Sacred_Drum:
        $game->discardCard($arg);
        $assistants = $game->sqlWrapper->getPlayerAssistants($this->playerId);
        foreach($assistants as $assId => $ass) {
          if ($ass["ready"] == 0) {
            $game->refreshAssistant($ass["num"]);
          }
        }
        break;
      case Artefact::Traders_Coins:
        $game->upgrade($arg, true);
        $this->gainCardResource("coins", 2);
        break;
      case Artefact::Stone_Key:
        if ($game->getNonEmptyObjectFromDb("SELECT * FROM player WHERE player_id = $this->playerId")["idol_slot"] >= 4) {
          $game->notifyAllPlayers("cantIdol", "No idols in slots", array());
        }
        else {
          $this->gainCardResource("idol_slot", 1);
          $this->gainCardResource("idol", 1);
        }
        break;
      case Artefact::Obsidian_Earring:
        if (count($game->sqlWrapper->getCards($this->playerId, 'deck')) == 0) {
          $game->artDone();
        }
        for ($i = 0; $i < $arg; ++$i) {
          $game->drawCard($this->playerId, true, "earring");
        }
        $game->gamestate->nextState("earring");
        break;
      case Artefact::Guiding_Stone:
      case Artefact::Guiding_Skull:
        $size = "small";
        if ($artefact == Artefact::Guiding_Skull) {
          $size = "big";
          $this->gainCardResource("compass", -1);
        }
        $newSite = $game->getObjectFromDB("SELECT * FROM location WHERE size = '$size' AND is_open = 0  ORDER BY deck_order LIMIT 1");
        $game->notifyAllPlayers("siteReveal", clienttranslate('${player_name} reveals a ${size} location from the deck'), array(
          "size" => $size,
          "player_name" => $game->getActivePlayerName(),
          "player_id" => $this->playerId,
          "num" => $newSite["num"],
          "cardNum" => $artefact
        ));
        $deckOrder = $game->getObjectFromDB("SELECT * FROM location WHERE size = '$size' ORDER BY deck_order DESC LIMIT 1")["deck_order"] + 1;
        $siteId = $newSite["idlocation"];
        $game->dbQuery("UPDATE location SET deck_order = $deckOrder WHERE size = '$size' AND idlocation = $siteId");
        $game->undoSavePoint();
        $game->siteEffect($size, $newSite["num"]);
        break;
      case Artefact::Runes_of_the_Dead:
        $this->gainCardResource("fear", 1);
        $this->gainCardResource("coins", 1);
        $this->gainCardResource("tablet", 3);
        break;
      case Artefact::Guardians_Crown:
        $movement = JSON_DECODE($arg);
        $to = $movement->to;
        $from = $movement->from;
        $siteFrom = $game->getObjectFromDb("
        SELECT * FROM location loc INNER JOIN board_position pos ON loc.is_at_position = pos.idboard_position INNER JOIN guardian g ON g.at_location = pos.idboard_position WHERE g.at_location = $from AND (slot1 = $this->playerId OR slot2 = $this->playerId)");

        if (!$siteFrom) {
          throw new BgaUserException(clienttranslate("You did not select a valid guardian"));
        }
        $siteTo = $game->getObjectFromDb("
        SELECT loc.num num , loc.size size FROM location loc INNER JOIN board_position pos ON loc.is_at_position = pos.idboard_position WHERE (loc.size='basic' OR loc.size='small') AND pos.idboard_position = $to AND (slot1 IS NULL AND (slot2 IS NULL OR slot2 = -1))");
        if (!$siteTo) {
          throw new BgaUserException(clienttranslate("You did not select a valid destination"));
        }
        $newGuard = $game->getObjectFromDB("SELECT * FROM guardian WHERE at_location = $to");
        if ($newGuard) {
          throw new BgaUserException(clienttranslate("There is already a guardian there"));
        }
        $game->dbQuery("UPDATE guardian SET at_location = $to WHERE at_location = $from");
        $game->notifyAllPlayers("guardMove", '${player_name} moves a guardian to another site', array(
          "player_name" => $game->getActivePlayerName(),
          "player_id" => $game->getActivePlayerId(),
          "from" => $from,
          "to" => $to
        ));
        $game->siteEffect($siteTo["size"], $siteTo["num"]);
        break;
    }
    if( $artefact == Artefact::War_Mask ||
        $artefact == Artefact::Treasure_Chest ||
        $artefact == Artefact::Ritual_Dagger ||
        $artefact == Artefact::Mortar ||
        $artefact == Artefact::Serpents_Gold ||
        $artefact == Artefact::Serpent_Idol ||
        $artefact == Artefact::Monkey_Medallion ||
        $artefact == Artefact::Guardians_Ocarina ||
        $artefact == Artefact::War_Club ||
        $artefact == Artefact::Traders_Scales ||
        $artefact == Artefact::Hunting_Arrows ||
        $artefact == Artefact::Decorated_Horn ||
        $artefact == Artefact::Stone_Jar ||
        $artefact == Artefact::Ceremonial_Rattle ||
        $artefact == Artefact::Sacred_Drum ||
        $artefact == Artefact::Traders_Coins ||
        $artefact == Artefact::Stone_Key ||
        $artefact == Artefact::Runes_of_the_Dead ) {
      $game->artDone();
    }
    else {
      $game->setGameStateValue("art-active", $artefact->value);
    }
  }
  function itemEffect($item, $arg) {
    $game = $this->game;
    if ($game->gameData->cardExileItself($item)) {
      $game->exile($this->activeCard);
    }
    switch($item) {
      case Item::Sea_Turtle:
        $this->gainCardResource("card", 1);
        $game->setGameStateValue("discount-ship", 1);
        $game->gamestate->nextState("mayTravel");
        break;
      case Item::Ostrich:
        $this->gainCardResource("card", 1);
        $game->setGameStateValue("discount-car", 1);
        $game->gamestate->nextState("mayTravel");
        break;
      case Item::Pack_Donkey:
        $this->gainCardResource("card", 2);
        break;
      case Item::Horse:
        $this->gainCardResource("card", 1);
        $this->gainCardResource("coins", 1);
        $this->gainCardResource("compass", 1);
        break;
      case Item::Steam_Boat:
        $this->gainCardResource("compass", 2);
        break;
      case Item::Automobile:
        $this->gainCardResource("compass", 2);
        break;
      case Item::Sturdy_Boots:
        $this->gainCardResource("compass", 1);
        $game->setGameStateValue("discount-boot", 2);
        $game->gamestate->nextState("mayTravel");
        break;
      case Item::Gold_Pan:
        $this->gainCardResource("coins", 2);
        break;
      case Item::Trowel:
        $this->gainCardResource("compass", -1);
        $this->gainCardResource("jewel", 1);
        break;
      case Item::Pickaxe:
        $this->gainCardResource("compass", -1);
        $this->gainCardResource("tablet", 1);
        $this->gainCardResource("arrowhead", 1);
        break;
      case Item::Hot_Air_Balloon:
        $game->setGameStateValue("discount-compass", 3);
        $game->setGameStateValue("discount-plane", 1);
        $game->gamestate->nextState("mayTravel");
        // reset dis
        break;
      case Item::Aeroplane:
        $game->setGameStateValue("discount-compass", 2);
        $game->setGameStateValue("discount-plane", 1);
        $game->gamestate->nextState("mayTravel");
        // reset dis
        break;
      case Item::Journal:
        $game->research($arg, true, "book");
        break;
      case Item::Parrot:
        $game->discardCard($arg);
        $this->gainCardResource("jewel", 1);
        break;
      case Item::Watch:
        if ($arg == "pass") {
          $this->gainCardResource("coins", 3);
          $game->pass();
          return;
        }
        else {
          $this->gainCardResource("coins", 2);
        }
        break;
      case Item::Army_Knife:
        $options = JSON_DECODE($arg);
        if(count($options) != 2 ) {
          throw new BgaUserException(clienttranslate("You must select 2 options"));
        }
        if ($options[0] == $options[1] || (intval($options[0]) && intval($options[1]))) {
          throw new BgaUserException(clienttranslate("The options must be different"));
        }
        foreach ($options as $option) {
          switch($option) {
            case "compass": case "coins": case "tablet":
              $this->gainCardResource($option, 1);
              break;
            default:
              $game->exile($option);
          }
        }
        break;
      case Item::Binoculars:
        $siteTile = $game->getObjectFromDb("SELECT * FROM location WHERE is_at_position = $arg AND size = 'small'");
        if (!$siteTile) {
          throw new BgaUserException(clienttranslate("That is not a small discovered site"));
        }
        $game->siteEffect("small", $siteTile["num"]);
        break;
      case Item::Tent:
        $siteTile = $game->getObjectFromDb("SELECT * FROM location WHERE is_at_position = $arg");
        if (!$siteTile || !$game->getObjectFromDb("SELECT * FROM board_position WHERE (slot1 = $this->playerId OR slot2 = $this->playerId) AND idboard_position = $arg")) {
          throw new BgaUserException(clienttranslate("You must have an archeologist on the site"));
        }
        if ($siteTile["size"] == "big") {
          $this->gainCardResource("compass", -2);
        }
        $game->siteEffect($siteTile["size"], $siteTile["num"]);
        break;
      case Item::Fishing_Rod:
        $newCard = $game->revealCard("item");
        $game->setGameStateValue("discount-coins", 3);
        $game->gamestate->nextState("buyItem");
        break;
      case Item::Precision_Compass:
        $newCard = $game->revealCard("art");
        $game->setGameStateValue("discount-compass", 3);
        $game->gamestate->nextState("buyArt");
        break;
      case Item::Bow_and_Arrows:
        $guards = $game->getCollectionFromDb(
        "SELECT * FROM guardian g
        LEFT JOIN board_position p ON g.at_location = p.idboard_position 
        WHERE g.in_hand = $this->playerId OR p.slot1 = $this->playerId OR p.slot2 = $this->playerId");

        $this->gainCardResource("compass", min(3, count($guards)));
        break;
      case Item::Carrier_Pigeon:
        $this->gainCardResource("tablet", 2);
        break;
      case Item::Whip:
        $game->setGameStateValue("discount-compass", 4);
        $game->buyCard($arg, false, false, true);
        break;
      case Item::Rough_Map:
        $this->gainCardResource("compass", 3);
        break;
      case Item::Airdrop:
        $game->setGameStateValue("discount-coins", 999);
        $game->buyCard($arg, true, false, true);
        $this->gainCardResource("card", 1);
        break;
      case Item::Flask:
        $this->gainCardResource("card", 3);
        break;
      case Item::Machete:
        $game->exile($arg);
        $this->gainCardResource("compass", 2);
        break;
      case Item::Torch:
        $game->exile($arg);
        $this->gainCardResource("tablet", 1);
        break;
      case Item::Large_Backpack:
        $this->gainCardResource("coins", 1);
        $game->drawCard($this->playerId, true);
        break;
      case Item::Rope:
        $game->discardCard($arg);
        $this->gainCardResource("card", 2);
        break;
      case Item::Revolver:
        $this->gainCardResource("compass", -1);
        $game->freeOvercome($arg);
        break;
      case Item::Hat:
        $this->gainCardResource("compass", 1);
        $this->gainCardResource("coins", 1);
        break;
      case Item::Bear_Trap:
        $guards = $game->availableGuardians($arg, true);
        switch (count($guards)) {
          case 0: throw new BgaUserException(clienttranslate("Select a valid guardian"));
          case 1: $game->overcomeGuard($guards[0]["num"], "", true); break;
          default: throw new BgaUserException(clienttranslate("Incorrect number of guards found"));
          break;
        }
        break;
      case Item::Grappling_Hook:
        $game->discardCard($arg);
        $this->gainCardResource("card", 1);
        $game->gamestate->nextState("cardExile");
        break;
      case Item::Lantern:
        $siteTile = $game->getObjectFromDb("SELECT * FROM location WHERE is_at_position = $arg AND size = 'basic'");
        if (!$siteTile) {
          throw new BgaUserException(clienttranslate("That is not a camp site"));
        }
        $game->siteEffect("basic", $siteTile["num"]);
        break;
      case Item::Dog:
        $site = $game->getObjectFromDb("SELECT * FROM board_position WHERE slot1 IS NULL AND (slot2 IS NULL OR slot2 = -1) AND idboard_position = $arg");
        $siteTile = $game->getObjectFromDb("SELECT * FROM location WHERE is_at_position = $arg AND size = 'basic'");
        if (!$site) {
          throw new BgaUserException(clienttranslate("That is not an unoccupied camp site"));
        }
        if (!$siteTile) {
          throw new BgaUserException(clienttranslate("That is not a camp site"));
        }
        $siteTile = $game->getNonEmptyObjectFromDb("SELECT * FROM location WHERE is_at_position = $arg AND size = 'basic'");
        $this->gainCardResource("compass", 1);
        $game->siteEffect("basic", $siteTile["num"]);
        break;
      case Item::Brush:
        $player =  $game->getNonEmptyObjectFromDb("SELECT * FROM player WHERE player_id = $this->playerId");
        $idols = $player["idol"] + 4 - $player["idol_slot"];
        $this->gainCardResource("compass", min($idols, 3));
        break;
      case Item::Axe:
        $game->exile($arg);
        $this->gainCardResource("compass", 1);
        break;
      case Item::Chronometer:
        if ($arg == "pass") {
          $this->gainCardResource("compass", 3);
          $game->pass();
        }
        else {
          $this->gainCardResource("coins", 1);
          $this->gainCardResource("compass", 1);
        }
        break;
      case Item::Theodolite:
        $this->gainCardResource("coins", 1);
        $this->gainCardResource("compass", 2 - $game->freeWorkerAmt($this->playerId)); 
        break;
      default:
        throw new BgaUserException("cannot use item ".$item->value());
    }
    if ($game->gameData->cardAction($item) == "main") {
      $game->gamestate->nextState("main_action_done");
    }
  }
  function basicEffect($basic) {
    switch($basic) {
      case Basic::Funding_Car:
      case Basic::Funding_Ship:
        $this->gainCardResource("coins", 1);
        break;
      case Basic::Explore_Car:
      case Basic::Explore_Ship:
        $this->gainCardResource("compass", 1);
        break;
      case Basic::Fear:
        throw new BgaUserException(clienttranslate("Fear cannot be played"));
        break;
      default:
        throw new BgaUserException("Invalid Basic typr");
    }
  }
}
?>