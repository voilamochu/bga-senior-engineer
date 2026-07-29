<?php
namespace AGR\Actions;

use AGR\Core\Globals;
use AGR\Managers\PlayerCards;
use AGR\Managers\Players;
use AGR\Core\Notifications;
use AGR\Core\Engine;

class WishChildren extends \AGR\Models\Action
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->description = clienttranslate('Grow family');
  }

  public function getState()
  {
    return ST_WISHCHILDREN;
  }

  public function isAutomatic($player = null)
  {
    return true;
  }

  public function isDoable($player, $ignoreResources = false)
  {
    if (!$player->hasFarmerInReserve()) {
      return false;
    }

    // Check constraints associated with the action
    $constraints = $this->ctx->getArgs()['constraints'] ?? [];
    $numPlayedRoomCards = $player->countExtraRoom();

    if ($player->hasPlayedCard('E155_Visionary')) {
      $othersHaveGrown = true;

      foreach (Players::getAll() as $player2) {
        if ($player->getId() == $player2->getId()) {
          continue;
        }

        if ($player2->countFarmers() == 2) {
          $othersHaveGrown = false;
          break;
        }
      }

      if (Globals::getTurn() < 11 && !$othersHaveGrown) {
        return false;
      }
    }

    $checkE151Passed = false;
    if ($player->hasPlayedCard('E151_DeliveryNurse') && !PlayerCards::get('E151_DeliveryNurse')->isFlagged()) {
      $checkE151Passed = $player->checkHaveAllTypeAnimals();
    }

    $checkE92Passed = false;
    if ($player->hasPlayedCard('E92_FieldDoctor') && !PlayerCards::get('E92_FieldDoctor')->isFlagged()) {
      $checkE92Passed = PlayerCards::get('E92_FieldDoctor')->checkRoomsSurrondedByFields($player);
    }

    $fromActionSpace = $this->ctx->getArgs()['fromActionSpace'] ?? false;

    foreach ($constraints as $c) {
      if ($c == 'freeRoom') {
        if ($fromActionSpace) {
          if ($player->countFarmers() >= $player->countRooms() + $numPlayedRoomCards && !$checkE151Passed && !$checkE92Passed) {
            return false;
          }
        } else {
          if ($this->ctx->getArgs()['cardLocation'] ?? '' == 'D92_ChildOmbudsman') {
            return true;
          }
          if ($player->countFarmers() >= $player->countRooms() + $numPlayedRoomCards && !$checkE151Passed) {
            return false;
          }
        }
      }

      // Action space on additional board with an action blocked until turn5
      if ($c == 'turn5' && Globals::getTurn() < 5) {
        return false;
      }
    }

    return true;
  }

  public function checkConstraintsBeforeAct($player)
  {
    $constraints = $this->ctx->getArgs()['constraints'] ?? [];

    foreach ($constraints as $c) {
      if ($c == 'freeRoom') {
        if ($player->countFarmers() >= $player->countRooms() + $player->countExtraRoom()) {
          throw new \BgaUserException('You cannot grow your family now, check "enough room space" constraint failed. You have ' . $player->countFarmers() . ' farmers and ' . $player->countRooms() . ' rooms and ' . $player->countExtraRoom() . ' extra room spaces now.');
        }
      }
      if ($c == 'turn5' && Globals::getTurn() < 5) {
        throw new \BgaUserException('You cannot grow your family now, check "after turn 5" constraint failed.');
      }
    }
  }

  public function stWishChildren()
  {
    $args = $this->ctx->getArgs();

    $player = Players::getActive();
    $this->checkConstraintsBeforeAct($player);
    if ($args['insideHouse'] ?? false) {
      $room = $player->getFreeRoom(); // Get a free room
      $meep = $player->growFamily([$room['x'], $room['y']], 'board');
    } elseif ($args['cardLocation'] ?? false) {
      if ($args['cardLocation'] == 'D92_ChildOmbudsman') {
        if ($player->countFarmers() >= $player->countRooms() + $player->countExtraRoom()) {
          throw new \BgaUserException('Before using Child Ombudsman, you must prepare room space, for example, by some anytime action like Mason.');
        }
      }
      $meep = $player->growFamily($args['cardLocation']);
    } else {
      $cardId = $this->ctx->getCardId(); // CardId is tagged in the flow tree associated to the action
      $meep = $player->growFamily($cardId);
    }

    Notifications::growFamily($player, $meep);
    Notifications::updateHarvestCosts();

    $attributionCardId = $args['cardId'] ?? ($this->ctx != null ? $this->ctx->getCardId() : null);
    if (!is_null($attributionCardId)) {
      $attrCards = PlayerCards::getMany([$attributionCardId], false);
      if ($attrCards->count() === 1) {
        $attrCard = $attrCards->first();
        if ($attrCard->getPId() === $player->getId()) {
          $attrCard->incStats('gain', FARMER, 1);
        }
      }
    }

    // Listeners for cards
    $eventData = [
      'farmers' => $player->countFarmers(),
    ];
    $this->checkAfterListeners($player, $eventData);

    $this->resolveAction();
    Engine::proceed();
  }
}