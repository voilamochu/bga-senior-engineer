<?php
namespace AGR\Actions;
use AGR\Core\Globals;
use AGR\Core\Notifications;
use AGR\Core\Stats;
use AGR\Core\Engine;
use AGR\Helpers\Utils;
use AGR\Managers\Meeples;
use AGR\Managers\PlayerCards;
use AGR\Managers\Players;

class Collect extends \AGR\Models\Action
{
  public function getState()
  {
    return ST_COLLECT;
  }

  public function getDescription($ignoreResources = false)
  {
    $cardId = $this->ctx->getCardId();
    $player = Players::getActive();
    $meeples = Meeples::getResourcesOnCard($cardId)->toArray();
    $res = Utils::reduceResources($meeples);
    return [
      'log' => clienttranslate('Collect ${resources_desc}'),
      'args' => [
        'resources_desc' => Utils::resourcesToStr($res),
      ],
    ];
  }

  public function stCollect()
  {
    $cardId = $this->ctx->getCardId();
    $player = Players::getActive();

    $eventData = [
      'actionCardId' => $cardId,
    ];

    $meeples = Meeples::collectResourcesOnCard($player, $cardId);
    $eventData['meeples'] = $meeples;
    $cardGains = [];

    foreach ($meeples as $meeple) {
      $statName = 'incBoard' . ucfirst($meeple['type']);
      Stats::$statName($player);

      $sourceCardId = $meeple['x'] ?? ($meeple['state'] ?? null);
      if (is_string($sourceCardId) && $sourceCardId !== '') {
        if (!array_key_exists($sourceCardId, $cardGains)) {
          $cardGains[$sourceCardId] = [];
        }
        if (!array_key_exists($meeple['type'], $cardGains[$sourceCardId])) {
          $cardGains[$sourceCardId][$meeple['type']] = 0;
        }
        $cardGains[$sourceCardId][$meeple['type']]++;
      }
    }

    foreach ($cardGains as $sourceCardId => $resources) {
      $cards = PlayerCards::getMany([$sourceCardId], false);
      if ($cards->count() !== 1) {
        continue;
      }
      $card = $cards->first();
      if ($card->getPId() !== $player->getId()) {
        continue;
      }
      foreach ($resources as $resource => $amount) {
        $card->incStats('gain', $resource, $amount);
      }
    }

    $this->checkListeners('Collect', $player, $eventData);
    $reorganize = $player->checkAutoReorganize($meeples);
    Notifications::collectResources($player, $meeples);
    Notifications::updateDropZones($player);
    if ($player->hasInHand('A53_Claypipe') && Globals::isWorkPhase()) {
      $player->updateObtainedResources($meeples);
    }

    $this->checkListeners('ImmediatelyAfterCollect', $player, $eventData);
    $player->checkAnimalsInReserve($reorganize);
    $reaction = $this->checkListeners('AfterCollect', $player, $eventData, false);

    // try to find parent with placeFarmer action
    $node = $this->ctx;
    while ($node->getAction() != PLACE_FARMER && !is_null($node->getParent())) {
       $node = $node->getParent();
    }
    $afterPlaceFarmerNode = null;

    // Found it => now we are going to look at the childs
    if ($node->getAction() == PLACE_FARMER) {
      foreach ($node->getChilds() as &$child) {
        if ($child->getEventMethod() == 'AfterPlaceFarmer') {
          $afterPlaceFarmerNode = $child;
        }
      }
    }

    // Insert as child if the afterPlaceFarmerNode was not found
    if (is_null($afterPlaceFarmerNode)) {
      if (!is_null($reaction)) {
        Engine::insertAsChild($reaction);
      }
    }
    // Otherwise, push the childs
    else {
      foreach (($reaction['childs'] ?? []) as $child) {
        $afterPlaceFarmerNode->pushChild(Engine::buildTree($child));
      }
    }
    $this->resolveAction();
  }

  public function isAutomatic($player = null)
  {
    return true;
  }
}
