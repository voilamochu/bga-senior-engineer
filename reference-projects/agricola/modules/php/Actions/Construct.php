<?php
namespace AGR\Actions;

use AGR\Managers\PlayerCards;
use AGR\Managers\Players;
use AGR\Core\Notifications;
use AGR\Core\Engine;
use AGR\Helpers\Utils;
use AGR\Core\Stats;

class Construct extends \AGR\Models\Action
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->description = clienttranslate('Build rooms');
  }

  public function getState()
  {
    return ST_CONSTRUCT;
  }

  public function getCosts($player)
  {
    $roomType = $player->getRoomType();
    $constructCost = [
      'roomWood' => Utils::formatCost([REED => 2, WOOD => 5]),
      'roomClay' => Utils::formatCost([REED => 2, CLAY => 5]),
      'roomStone' => Utils::formatCost([REED => 2, STONE => 5]),
    ];
    $costs = $constructCost[$roomType];
    $costs = $this->getCtxArgs()['costs'] ?? $costs;
    $this->checkCostModifiers($costs, $player, ['type' => $roomType, 'actionCardId' => $this->getCtxArgs()['actionCardId'] ?? null,]);
    return $costs;
  }

  public function isDoable($player, $ignoreResources = false)
  {
    // The player must be able to buy at least one room and have an empty spot
    return ($ignoreResources || $player->canBuy($this->getCosts($player)) || $player->hasPlayedCard('A14_CarpentersHammer')) && $player->board()->canConstruct();
  }

  public function isTrueAction()
  {
    return $this->getCtxArgs()['trueAction'] ?? true;
  }

  public function getMaxBuildableRooms($player)
  {
    $argMax = $this->ctx->getArgs()['max'] ?? 99; // Are there any upper bound linked to the action itself (in case the action is triggered by a player card)
    $maxBuyable = $player->maxBuyableAmount($this->getCosts($player));
    return min($argMax, $maxBuyable);
  }

  public function argsConstruct()
  {
    $player = Players::getActive();
    $roomType = $player->getRoomType();

    return [
      'roomType' => $roomType,
      'max' => self::getMaxBuildableRooms($player),
      'zones' => $player->board()->getFreeZones(),
    ];
  }

  public function actConstruct($rooms)
  {
    self::checkAction('actConstruct');

    $player = Players::getActive();
    $roomType = $player->getRoomType();
    $args = $this->getCtxArgs();
    $source = $args['source'] ?? null;

    if (count($rooms) > $this->getMaxBuildableRooms($player)) {
      throw new \BgaVisibleSystemException('You can\'t build that many rooms with your resources');
    }

    // Record amount of rooms prior to building (A21)
    $oldRoomCount = $player->countRooms();

    // Add them to board
    $playerBoard = $player->board();
    foreach ($rooms as &$room) {
      $playerBoard->addRoom($roomType, $room);
    }

    // Then run sanity checks with $raiseException = true to auto rollback in case of invalid choice
    $playerBoard->areRoomsValid(true);

    // If everything is fine, notify the new rooms
    Notifications::construct($player, $rooms, $source);

    // Insert PAY action and proceed
    $player->pay(count($rooms), $this->getCosts($player), clienttranslate('Construction'), true, [
      'sourceAction' => 'Construct',
      'actionCardId' => $this->ctx != null ? $this->ctx->getCardId() : null,
    ]);
    Stats::incTotalRoomsBuilt($player, count($rooms));

    $cardId = $args['cardId'] ?? ($this->ctx != null ? $this->ctx->getCardId() : null);
    if (!is_null($cardId)) {
      $cards = PlayerCards::getMany([$cardId], false);
      if ($cards->count() === 1) {
        $card = $cards->first();
        if ($card->getPId() === $player->getId()) {
          $card->incStats('gain', $roomType, count($rooms));
        }
      }
    }

    // Listeners for cards
    $eventData = [
      'roomType' => $roomType,
      'rooms' => $rooms,
      'oldRoomCount' => $oldRoomCount,
      'trueAction' => $this->isTrueAction(),
    ];
    if (isset($args['actionCardId']) && $args['actionCardId'] == 'D14_HammerCrusher') {
      // for now we do not have During or ImmediatelyAfter listener for CONSTRUCT, so we only check After here for simplicity.
      // otherwise, we need to do something as stCollect do.
      $reaction = $this->checkListeners('After' . $this->getClassName(), $player, $eventData, false);
      if (!is_null($reaction)) {
        if (!is_null($reaction['childs'])) {
          $triggeredByOpponent = false;
          foreach ($reaction['childs'] as $child) {
            if (!is_null($child['args']) && !is_null($child['args']['event'])) {
              $triggeredByOpponent = $child['args']['event']['triggeredByOpponent'] ?? false;
            }
          }
          if ($triggeredByOpponent) {
            $wrapped = PlayerCards::get('D14_HammerCrusher')->wrapRenovationCheckNode($reaction, $player);
            Engine::insertAsChild($wrapped);
          } else{
            Engine::insertAsChild($reaction);
          }
        }
      }
    } else {
      $this->checkAfterListeners($player, $eventData);
    }

    $this->resolveAction();
  }
}