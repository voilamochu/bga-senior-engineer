<?php

class SqlWrapper {

  public function __construct($game) {
    $this->game = $game;
    $this->cardFeildMapping = array(
      "idcard" => "id",
      "card_type" => "type",
      "num" => "num",
      "card_position" => "position",
      "player" => "playerId"
    );
  }

  public function getPublicCards($player_id = NULL, $position = NULL, $type = NULL) {
    $fields = ["idcard", "card_type", "num"];
    $cards = $this->getCardsFromFields($player_id, $position, $type, $fields);
    foreach ($cards as $idx => $card) {
      if ($card["type"] != "art" && $card["type"] != "item") {
        $cards[$idx]["num"] = $card["type"];
        $cards[$idx]["type"] = "basic";
      }
    }
    return $cards;
  }

  public function getCards($player_id = NULL, $position = NULL, $type = NULL) {
    $fields = ["idcard", "card_type", "num", "card_position", "player"];
    $cards = $this->getCardsFromFields($player_id, $position, $type, $fields);
    foreach ($cards as $idx => $card) {
      $cards[$idx] = $this->cardWithInfo($cards[$idx]);
    }
    return $cards;
  }

  private function getCardsFromFields($player_id = NULL, $position = NULL, $type = NULL, $fields = []) {
    $filters = [];
    if ($position) {
      array_push($filters, "card_position = '$position'" );
    }
    array_push($filters, is_null($player_id) ? "player IS NULL" : "player = $player_id");
    if ($type) {
      array_push($filters, "card_type = '$type'");
    }
    $filters_str = implode(' AND ', $filters);
    $field_str = $this->cardFeildStr($fields);
    return $this->game->getObjectListFromDb("SELECT $field_str FROM card WHERE $filters_str ORDER BY deck_order");
  }

  public function getCardFromId($id, $extra_fields = [], $player_id = NULL) {
    $field_str = $this->cardFeildStr(["idcard", "card_type", "num", "card_position", "player"]);

    $filters = "idcard = $id";
    if ($player_id) {
      $filters .= " AND player = $player_id";
    }
    $card = $this->game->getObjectFromDB("SELECT $field_str FROM card WHERE $filters");
    return $this->cardWithInfo($card);
  }

  public function getCardId($type, $num) {
    $card = $this->game->getObjectFromDB("SELECT idcard FROM card WHERE card_type = '$type' AND num = '$num'");
    return $card["idcard"];
  }

  private function getCardOrders($player_id, $position) {
    $filters = [];
    if ($position) {
      array_push($filters, "card_position = '$position'" );
    }
    array_push($filters, is_null($player_id) ? "player IS NULL" : "player = $player_id");
    $filters_str = implode(' AND ', $filters);
    $cards = $this->game->getObjectListFromDb("SELECT deck_order FROM card WHERE $filters_str ORDER BY deck_order");

    $orders = [];
    foreach ($cards as $card) {
      array_push($orders, $card["deck_order"]);
    }
    return $orders;
  }

  public function moveCard($debug, $card, $playerId, $destination, $notifs = [], $high = true) {
    $destinationOrders = $this->getCardOrders($playerId, $destination);
    $nextOrder = 0;
    if (count($destinationOrders) > 0) {
      $nextOrder = $high ? (end($destinationOrders) + 1) : ($destinationOrders[0] - 1);
    }

    $id = $card['id'];
    $cardInfo = $card["info"];
    $player_str = $playerId ? $playerId : "NULL";
    $this->game->DbQuery("UPDATE card SET player = $player_str, card_position = '$destination', deck_order = $nextOrder WHERE idcard = $id");

    if (count($notifs) > 0) {
      $notif_card = array(
        "i18n" => ["cardName"],
        "cardName" => $this->game->gameData->cardName($cardInfo),
        "cardType" => $cardInfo->type(),
        "cardNum" => $cardInfo->value,
        "cardId" => $id,
        "source" => $card['position'],
        "srcPlayerId" => $card['playerId'],
        "destination" => $destination,
        "dstPlayerId" => $playerId,
        "preserve" => ['cardType', 'cardNum'],
        "debug" => $debug
      );

      if ($playerId) {
        $notif_card["playerName"] = $this->game->loadPlayersBasicInfos()[$playerId]["player_name"];
      }
      else {
        $notif_card["playerName"] = $this->game->getActivePlayerName();
      }

      foreach($notifs[0] as $var => $value) {
        if ($var != "msg") {
          array_push($notif_card["i18n"], $var);
          $notif_card[$var] = $value;
        }
      }

      if (count($notifs) == 1) {
        if (!is_null($notifs[0])) {
          $this->game->notifyAllPlayers("moveCard", $notifs[0]["msg"], $notif_card);
        }
      }
      else if (count($notifs) == 2) {
        if (!is_null($notifs[0])) {
          $this->game->notifyPlayer($playerId, "moveCard", $notifs[0]["msg"], $notif_card);
        }

        if (!is_null($notifs[1])) {
          $notif_players = array (
            "i18n" => [],
            "playerName" => $this->game->getActivePlayerName(),
            "source" => $card['position'],
            "srcPlayerId" => $card['playerId'],
            "destination" => $destination,
            "dstPlayerId" => $playerId,
            "debug" => $debug
          );
          foreach($notifs[1] as $var => $value) {
            if ($var != "msg") {
              array_push($notif_players["i18n"], $var);
              $notif_players[$var] = $value;
            }
          }

          $this->game->notifyAllPlayers("playerMoveCard", $notifs[1]["msg"], $notif_players);
        }
      }
    }
  }

  public function moveCards($debug, $playerId, $from, $to, $notifs) {

    $fromCards = $this->getPublicCards($playerId, $from);

    $this->game->DbQuery("UPDATE card SET player = $playerId, card_position = '$to' WHERE player = $playerId  AND card_position = '$from'");

    if (count($notifs) > 0) {
      $notif_cards = array (
        "i18n" => [],
        "playerId" => $playerId,
        "playerName" => $this->game->loadPlayersBasicInfos()[$playerId]["player_name"],
        "source" => $from,
        "destination" => $to,
        "cards" => JSON_ENCODE($fromCards),
        "debug" => $debug
      );
      foreach($notifs[0] as $var => $value) {
        if ($var != "msg") {
          array_push($notif_cards["i18n"], $var);
          $notif_cards[$var] = $value;
        }
      }

      if (count($notifs) == 1) {
        if (!is_null($notifs[0])) {
          $this->game->notifyAllPlayers("moveCards", $notifs[0]["msg"], $notif_cards);
        }
      }
      else {
        if (!is_null($notifs[0])) {
          $this->game->notifyPlayer($playerId, "moveCards", $notifs[0]["msg"], $notif_cards);
        }

        if (!is_null($notifs[1])) {
          $notif_players = array (
            "i18n" => [],
            "debug" => $debug
          );
          foreach($notifs[1] as $var => $value) {
            if ($var != "msg") {
              array_push($notif_players["i18n"], $var);
              $notif_players[$var] = $value;
            }
          }

          $this->game->notifyAllPlayers("movePlayerCards", $notifs[1]["msg"], $notif_players);
        }
      }
    }
  }

  public function createCards($debug, $cards, $playerId, $position, $notifs = []) {
    $orders = $this->getCardOrders($playerId, $position);
    $deckOrder = 0;
    if (count($orders) > 0) {
      end($orders) + 1;
    }

    foreach($cards as $card) {
      $cardType = $card->type();
      $cardTypeDb = ($cardType == "basic") ? $card->value : $cardType;
      $cardNum = ($cardType == "basic") ? 'NULL' : $card->value;
      $player_str = $playerId ? $playerId : "NULL";
      $this->game->DbQuery("INSERT INTO card (player, card_position, card_type, num, deck_order) VALUES ($player_str, '$position', '$cardTypeDb', $cardNum, $deckOrder)");
      $deckOrder++;
      if (count($notifs) == 1) {
        $id = $this->game->getObjectFromDB("SELECT LAST_INSERT_ID() id")["id"];
        $notif_cards = array(
          "i18n" => ["cardName"],
          "player_name" => $this->game->loadPlayersBasicInfos()[$playerId]["player_name"],
          "cardName" => $this->game->gameData->cardName($card),
          "cardType" => $card->type(),
          "cardNum" => $card->value,
          "cardId" => $id,
          "source" => 'discard',
          "srcPlayerId" => NULL,
          "destination" => $position,
          "dstPlayerId" => $playerId,
          "debug" => $debug
        );
        $this->game->notifyAllPlayers("moveCard", $notifs[0]["msg"], $notif_cards);
      }
    }
  }

  private function cardFeildStr($feilds) {
    $strFields = array_map(function ($feild) { return "$feild ".$this->cardFeildMapping[$feild];}, $feilds);
    return implode(', ', $strFields);
  }

  private function cardWithInfo($cardData)
  {
    $card = $cardData;
    unset($card["type"]);
    unset($card["num"]);
    $type = $cardData["type"];
    if ($type == "art") {
      $card["info"] = Artefact::from($cardData["num"]);
    }
    else if ($type == "item") {
      $card["info"] = Item::from($cardData["num"]);
    }
    else {
      $card["info"] = Basic::from($type);
    }
    return $card;
  }

  public function getAssistantFromNum($num) {
    $assistants = $this->getAssistants(["num" => $num], ["in_hand", "in_offer", "gold", "ready"]);
    if (count($assistants) > 0) {
      $assistants[0]["gold"] = ($assistants[0]["gold"] == 1);
      $assistants[0]["ready"] = ($assistants[0]["ready"] == 1);
      $assistants[0]["in_hand"] = is_null($assistants[0]["in_hand"])?NULL:intval($assistants[0]["in_hand"]);
      $assistants[0]["in_offer"] = intval($assistants[0]["in_offer"]);
    }
    return (count($assistants) > 0)?$assistants[0]:NULL;
  }

  public function getAssistantsStack($stack) {
    $assistants = $this->getAssistants(["in_hand" => NULL, "in_offer" => $stack], ["num", "gold", "ready"]);
    foreach ($assistants as $idx => $ass) {
      $assistants[$idx]["num"] = (intval($ass["num"]));
      $assistants[$idx]["gold"] = ($ass["gold"] == 1);
      $assistants[$idx]["ready"] = ($ass["ready"] == 1);
    }
    return $assistants;
  }

  public function getPlayerAssistants($playerId) {
    $assistants = $this->getAssistants(["in_hand" => $playerId], ["num", "gold", "ready"]);
    foreach ($assistants as $idx => $ass) {
      $assistants[$idx]["num"] = (intval($ass["num"]));
      $assistants[$idx]["gold"] = ($ass["gold"] == 1);
      $assistants[$idx]["ready"] = ($ass["ready"] == 1);
    }
    return $assistants;
  }

  private function getAssistants($conditions, $fields) {
    $conds = [];
    foreach ($conditions as $key => $value) {
      array_push($conds, ($value === NULL)?"$key IS NULL":"$key = $value");
    }
    $conds_str = implode(' AND ', $conds);
    $fields_str = implode(', ', $fields);
    return $this->game->getObjectListFromDb("SELECT $fields_str FROM assistant WHERE $conds_str ORDER BY offer_order");
  }
  
  public function changeAssistantUpgarded($num, $upgraded, $notif = "") {
    $gold = $upgraded ? 1 : 0;
    $this->game->DbQuery("UPDATE assistant SET gold = $gold WHERE num = $num");
    $this->game->notifyAllPlayers("upgradeAss", $notif, array(
      "player_name" => $this->game->getActivePlayerName(),
      "player_id" => $this->game->getActivePlayerId(),
      "gold" => $upgraded,
      "assNum" => $num
    ));
  }

  public function changeAssistantUsed($num, $used, $msg = "") {
    $ready = $used ? 0 : 1;
    $this->game->DbQuery("UPDATE assistant SET ready = $ready WHERE num = $num");
    $this->game->notifyAllPlayers("useAssistant", $msg, array(
      "player_name" => $this->game->getActivePlayerName(),
      "player_id" => $this->game->getActivePlayerId(),
      "used" => $used,
      "assNum" => $num
    ));
  }

  public function moveAssistantFromStack($num, $playerId, $msg, $revealedAss, $deckHeight, $stack) {
    $assistants = $this->getAssistants(["in_hand" => $playerId], ["offer_order"]);
    $order = 0;
    if (count($assistants) > 0 && $assistants[0]["offer_order"] == 0) {
      $order = 1;
    }
    $this->game->DbQuery("UPDATE assistant SET in_offer = NULL, offer_order = $order, in_hand = $playerId WHERE num = $num");
    $this->game->notifyAllPlayers("getAssistant", $msg, array(
      "player_name" => $this->game->getActivePlayerName(),
      "player_id" => $this->game->getActivePlayerId(),
      "playerSlot" => $order,
      "revealedAss" => $revealedAss,
      "revealedStack" => $stack,
      "newHeight" => $deckHeight,
      "assNum" => $num
    ));
  }

  public function swapAssistants($oldNum, $newNum, $playerId, $notifOld, $notifNew, $deckHeight, $stack) {
    $playerSlot = $this->getAssistants(["num" => $oldNum], ["offer_order"])[0]["offer_order"];
    $stackOrder = $this->getAssistants(["num" => $newNum], ["offer_order"])[0]["offer_order"];

    $this->game->DbQuery("UPDATE assistant SET in_offer = $stack, offer_order = $stackOrder, in_hand = NULL WHERE num = $oldNum");
    $this->game->DbQuery("UPDATE assistant SET in_offer = NULL, offer_order = $playerSlot, in_hand = $playerId WHERE num = $newNum");

    $this->game->notifyAllPlayers('returnAss', $notifOld, array(
        "player_name" => $this->game->getActivePlayerName(),
        "player_id" => $this->game->getActivePlayerId(),
        "num" => $oldNum,
        "slot" => $stack
    ));
    $this->game->notifyAllPlayers("getAssistant", $notifNew, array(
      "player_name" => $this->game->getActivePlayerName(),
      "player_id" => $this->game->getActivePlayerId(),
      "playerSlot" => $playerSlot,
      "revealedAss" => NULL,
      "revealedStack" => $stack,
      "newHeight" => $deckHeight,
      "assNum" => $newNum
    ));
  }

  public function createAssistant($ready, $num, $stack) {
    $assistants = $this->getAssistants(["in_offer" => $stack], ["offer_order"]);
    $order = (count($assistants) == 0) ? 0 : (end($assistants)["offer_order"] + 1);
    $readyValue = $ready ? 1 : 0;
    $this->game->DbQuery("INSERT INTO assistant (gold, ready, num, in_offer, offer_order) VALUES(0, $readyValue, $num, $stack, $order)");
  }

}
?>