<?php

namespace AGR\Actions;

use AGR\Core\Engine;
use AGR\Core\Notifications;
use AGR\Helpers\UserException;
use AGR\Managers\PlayerCards;
use AGR\Managers\Players;

class Renovation extends \AGR\Models\Action
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->description = clienttranslate('Renovate');
  }

  public $renovationCosts = [
    'roomClay' => ['fees' => [[REED => 1]], 'trades' => [[CLAY => 1]]],
    'roomStone' => ['fees' => [[REED => 1]], 'trades' => [[STONE => 1]]],
  ];

  public $renovationLink = ['roomWood' => 'roomClay', 'roomClay' => 'roomStone', 'roomStone' => null];

  public function getState()
  {
    return ST_RENOVATION;
  }

  public function getCosts($player, $newRoomType = null, $overrideCosts = null, $actionCardId = null)
  {
    $roomType = $player->getRoomType();
    $actionCardId = $actionCardId ?? $this->getCtxArgs()['actionCardId'] ?? ($this->ctx != null ? $this->ctx->getCardId() : null);

    if ($newRoomType == null) {
      $newRoomType = $this->renovationLink[$roomType];
    }

    $costs = $overrideCosts ?? $this->renovationCosts[$newRoomType];

    $eventData = [
      'oldRoomType' => $roomType,
      'newRoomType' => $newRoomType,
      'actionCardId' => $actionCardId,
    ];

    $this->checkCostModifiers($costs, $player, $eventData);
    return $costs;
  }

  public function isDoable($player, $ignoreResources = false)
  {
    $roomType = $player->getRoomType();
    if ($roomType == 'roomStone' || $player->hasRenoBlockingCards()) {
      return false;
    }

    $nbRooms = $player->countRooms();
    $args = $this->argsRenovation($player);

    foreach ($args['renovationType'] as $renovationType) {
      $costs = $this->getCtxArgs()['costs'] ?? null;
      if ($ignoreResources || $player->canBuy($this->getCosts($player, $renovationType, $costs), $nbRooms)) {
        return true;
      }
    }
    return false;
  }

  public function isAutomatic($player = null)
  {
    return true;
  }

  public function getRenovationType($player = null, $args = [])
  {
    if ($args['toClay'] ?? false) {
      return ['roomClay'];
    }
    if ($args['toStone'] ?? false) {
      return ['roomStone'];
    }

    if ($player == null) {
      $player = Players::getActive();
    }
    $renovationType = [$this->renovationLink[$player->getRoomType()]];
    if (
      ($player->hasPlayedCard('A87_Conservator') && $player->getRoomType() == 'roomWood') ||
      ($player->hasPlayedCard('C13_WoodSlideHammer') && $player->getRoomType() == 'roomWood' && $player->countRooms() >= 5)
    ) {
      $renovationType[] = 'roomStone';
    }
    // if we have use D14_HammerCrusher's bonus, we cannot renovate to clay anymore.
    if ($player->hasPlayedCard('D14_HammerCrusher')) {
      if (PlayerCards::get('D14_HammerCrusher')->isFlagged()) {
        $renovationType = array_diff($renovationType, ['roomClay']);
      }
    }

    return $renovationType;
  }

  public function argsRenovation($player = null)
  {
    if ($player == null) {
      $player = Players::getActive();
    }

    $renovationType = $this->getRenovationType($player, $this->getCtxArgs());

    return [
      'renovationType' => $renovationType,
      'combinations' => $this->doableCombinations($player, $renovationType),
    ];
  }

  protected function doableCombinations($player, $renovationType)
  {
    $canDo = [];
    foreach ($renovationType as $type) {
      $costs = $this->getCtxArgs()['costs'] ?? null;
      if ($player->canBuy($this->getCosts($player, $type, $costs), $player->countRooms())) {
        $canDo[] = $type;
      }
    }
    return $canDo;
  }

  public function stRenovation()
  {
    $player = Players::getActive();
    $args = $this->argsRenovation();
    $canDo = $this->doableCombinations($player, $args['renovationType']);

    if (count($canDo) == 1) {
      $this->actRenovation(array_pop($canDo), true);
    } else if (empty($canDo)) {
      throw new UserException(clienttranslate('Renovation is not possible! To fix this, maybe exchange your resources first, or just restart your turn.'));
    }
  }

  public function actRenovation($renovationType, $isAuto = false)
  {
    self::checkAction('actRenovation', $isAuto);
    $player = Players::getActive();
    $actionCardId = $this->getCtxArgs()['actionCardId'] ?? ($this->ctx != null ? $this->ctx->getCardId() : null);
    $overrideCosts = $this->getCtxArgs()['costs'] ?? null;
    $costs = $this->getCosts($player, $renovationType, $overrideCosts); // Make sure to call it before DB update!
    $args = $this->argsRenovation();

    if (!in_array($renovationType, $args['renovationType'])) {
      throw new \feException('renovationType not authorized. Should not happen');
    }

    $roomType = $player->getRoomType();
    $newRoomType = $renovationType;
    // $newRoomType = $this->renovationLink[$roomType];

    // Renovation
    $rooms = $player->board()->renovateRooms($newRoomType);

    Notifications::renovate($player, $rooms, $newRoomType);

    // Pay and proceed
    $player->pay(count($rooms), $costs, clienttranslate('Renovation'));

    if ($newRoomType == 'roomClay' && $player->hasPlayedCard('D121_ClayPlasterer')) {
      PlayerCards::get('D121_ClayPlasterer')->incStats('saved', CLAY, count($rooms) - 1);
    }

    // Listeners for cards
    $eventData = [
      'oldRoomType' => $roomType,
      'newRoomType' => $newRoomType,
      'rooms' => $rooms,
      'actionCardId' => $actionCardId,
    ];
    $this->checkAfterListeners($player, $eventData);

    $this->resolveAction(['rooms' => $rooms]);
  }
}
