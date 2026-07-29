<?php
namespace AGR\Actions; 
use AGR\Core\Globals; 
use AGR\Managers\Meeples; 
use AGR\Managers\Players; 
use AGR\Core\Notifications; 
use AGR\Core\Engine;
use AGR\Core\Stats;
use AGR\Helpers\Utils;
use AGR\Managers\PlayerCards;

// Receive goods that were put on an action card for future round
// or move an existing meeple off a card or space and into the reserve
class Receive extends \AGR\Models\Action
{
  public function getState()
  {
    return ST_RECEIVE;
  }

  public function getDescription($ignoreResources = false)
  {
    $meeple = $this->getMeeple();
    $receiverPId = $meeple['pId'];
    $activePId = Players::getActiveId();
    $playerColor = $receiverPId ? Players::get($receiverPId)->getColor() : null;

    if ($receiverPId && $receiverPId != $activePId) {
      $receiver = Players::get($receiverPId);
      return [
        'log' => clienttranslate('Allow ${player_name} to receive ${resources_desc}'),
        'args' => [
          'player_name' => $receiver->getName(),
          'resources_desc' => Utils::resourcesToStr([$meeple['type'] => 1]),
          'playerColor' => $playerColor,
        ],
      ];
    }

    return [
      'log' => clienttranslate('Receive ${resources_desc}'),
      'args' => [
        'resources_desc' => Utils::resourcesToStr([$meeple['type'] => 1]),
        'playerColor' => $playerColor,
      ],
    ];
  }

  public function getMeeple()
  {
    $args = $this->getCtxArgs();
    return empty($args['meeples']) ? Meeples::get($args['meeple']) : Meeples::get($args['meeples'][0]);
  }

  public function getMeeples()
  {
    $args = $this->getCtxArgs();

    // batch
    if (!empty($args['meeples']) && is_array($args['meeples'])) {
      return Meeples::getMany($args['meeples'])->toArray();
    // single
    } else {
      return [Meeples::get($args['meeple'])];
    }

    throw new \feException('Receive: no meeple id found in args');
  }

  public function isAutomatic($player = null)
  {
    return true;
  }

  public function isIndependent($player = null)
  {
    $meeple = $this->getMeeple();
    return !in_array($meeple['type'], [SHEEP, PIG, CATTLE, FIELD, STABLE]);
  }

  public function stReceive()
  {
    $player = $this->getCtxArgs()['player'] ?? Players::getActive();
    if (isset($this->getCtxArgs()['player']) && is_array($this->getCtxArgs()['player'])) {
      $ap = $this->getCtxArgs()['player'];
      if(isset($ap['id'])) {
        $player = Players::get($ap['id']);
      }
    }

    $args = $this->getCtxArgs();
    $meeples = $this->getMeeples();
    $usedCard = null;
    $usedCardId = isset($args['cardId']) && is_string($args['cardId']) && $args['cardId'] !== '' ? $args['cardId'] : null;
    if (!is_null($usedCardId)) {
      $cards = PlayerCards::getMany([$usedCardId], false);
      if ($cards->count() === 1) {
        $card = $cards->first();
        if ($card->getPId() === $player->getId()) {
          $usedCard = $card;
        }
      }
    }

    if (empty($meeples)) {
      return;
    }

    // Goods taken off an action space count as "gotten from an action space" for trigger
    // cards (Mattock, Beaver Colony...) even when no action was used there (bug 225149)
    $actionSpaceId = null;
    foreach ($meeples as $meeple) {
      if (Utils::isActionSpace($meeple['location'])) {
        $actionSpaceId = $meeple['location'];
        break;
      }
    }

    // 1) Split plain goods vs tokens needing individual flows
    //    Plain goods are ONLY these base types and MUST have no triggers or card/source markers.
    $plainGoodsTypes = [FOOD, WOOD, CLAY, REED, STONE, GRAIN, VEGETABLE];

    $goods = [];
    $special = [];

    foreach ($meeples as $meeple) {
      $type = $meeple['type'] ?? null;

      // x now carries the source card id (for stat attribution) and no longer forces
      // special per-meeple handling; the triggered/special routing happens in TurnTrait
      // for cards that define a custom getReceiveFlow. Plain-type meeples still batch.
      $hasTriggersOrSource = !empty($meeple['card']) || !empty($meeple['source']);
      $isPlainType = is_string($type) && in_array($type, $plainGoodsTypes, true);

      if ($isPlainType && !$hasTriggersOrSource) {
        $goods[] = $meeple;   // safe to batch
      } else {
        $special[] = $meeple; // Confidant (FOODPLUS), fields, rooms, stables, any card/triggered items
      }
    }

    // 2) Receive all plain goods in one go (DB + ONE notify)
    $eventMeeples = [];
    $receivedGoods = [];

    foreach ($goods as $meeple) {
      $receivedGoods[] = Meeples::receiveResource($player, $meeple);
    }

    if (!empty($receivedGoods)) {
      Notifications::receiveResources($player, $receivedGoods);

      // Claypipe tracking for goods
      if ($player->hasInHand('A53_Claypipe') && Globals::isWorkPhase()) {
        $player->updateObtainedResources($receivedGoods);
      }

      $eventMeeples = array_merge($eventMeeples, $receivedGoods);
    }

    // 3) Handle specials individually (may have follow-ups; cards like Confidant inject their own flows)
    foreach ($special as $meeple) {
      $updated = Meeples::receiveResource($player, $meeple);
      $eventMeeples[] = $updated ?? $meeple;

      // Claypipe tracking
      if ($player->hasInHand('A53_Claypipe') && Globals::isWorkPhase()) {
        $player->updateObtainedResources([$meeple]);
      }

      // Inline follow-ups for board tokens
      $type = $meeple['type'] ?? null;

      if ($type === FIELD) {
        if ($player->board()->canPlow()) {
          Engine::insertAsChild([
            'action' => PLOW,
            'optional' => true,
            'pId' => $player->getId(),
          ]);
        } else {
          Notifications::message(clienttranslate('${player_name} can\'t plow the received field'), [
            'player_name' => $player->getName(),
          ]);
        }
      } elseif ($type === 'roomStone') { // room types are string literals in ROOMS
        if ($player->board()->canConstruct() && $player->getRoomType() == 'roomStone') {
          Engine::insertAsChild([
            'action' => CONSTRUCT,
            'optional' => true,
            'pId' => $player->getId(),
            'args' => ['costs' => Utils::formatCost(['max' => 1]), 'max' => 1],
          ]);
        } else {
          Notifications::message(clienttranslate('${player_name} can\'t build the received room'), [
            'player_name' => $player->getName(),
          ]);
        }
      } elseif ($type === STABLE) {
        Engine::insertAsChild([
          'action' => STABLES,
          'optional' => true,
          'pId' => $player->getId(),
          'args' => ['costs' => Utils::formatCost(['max' => 1]), 'max' => 1, 'checkpoint' => $args['checkpoint'] ?? false],
        ]);
      }
    }

    $cardGains = [];
    foreach ($eventMeeples as $meeple) {
      $sourceCardId = null;
      if (isset($meeple['x']) && is_string($meeple['x']) && $meeple['x'] !== '' && !is_numeric($meeple['x'])) {
        $sourceCardId = $meeple['x'];
      } elseif (isset($meeple['state']) && is_string($meeple['state']) && $meeple['state'] !== '' && !is_numeric($meeple['state'])) {
        $sourceCardId = $meeple['state'];
      } elseif (!is_null($usedCard)) {
        $sourceCardId = $usedCard->getId();
      }

      if (!is_string($sourceCardId) || $sourceCardId === '') {
        continue;
      }

      if (!array_key_exists($sourceCardId, $cardGains)) {
        $cardGains[$sourceCardId] = [];
      }

      if (!array_key_exists($meeple['type'], $cardGains[$sourceCardId])) {
        $cardGains[$sourceCardId][$meeple['type']] = 0;
      }

      $cardGains[$sourceCardId][$meeple['type']]++;
    }

    foreach ($cardGains as $sourceCardId => $resources) {
      $card = !is_null($usedCard) && $usedCard->getId() === $sourceCardId ? $usedCard : null;
      if (is_null($card)) {
        $cards = PlayerCards::getMany([$sourceCardId], false);
        if ($cards->count() !== 1) {
          continue;
        }
        $card = $cards->first();
        if ($card->getPId() !== $player->getId()) {
          continue;
        }
      }

      foreach ($resources as $resource => $amount) {
        $card->incStats('gain', $resource, $amount);
      }
    }

    // 4) Listeners / auto-reorganize (before notifications so meeples have final locations)
    $eventData = [
      'meeples' => $eventMeeples,
      'fromActionSpace' => !is_null($actionSpaceId),
      'actionCardId' => $actionSpaceId,
    ];

    $this->checkListeners('Receive', $player, $eventData);

    $reorganize = $player->checkAutoReorganize($eventMeeples);

    // 5) Notify specials with updated locations
    $isRound = isset($args['type']) && $args['type'] == 'round';
    $goodsCount = count($receivedGoods);
    foreach (array_slice($eventMeeples, $goodsCount) as $m) {
      if ($isRound) {
        Notifications::receiveRoundResource($player, $m);
      } else {
        Notifications::receiveResource($player, $m);
      }
    }

    Notifications::updateDropZones($player);
    $player->checkAnimalsInReserve($reorganize);

    $this->checkAfterListeners($player, $eventData);
    $this->resolveAction();
  }
}
