<?php
namespace AGR\Actions;
use AGR\Managers\Meeples;
use AGR\Managers\Players;
use AGR\Managers\PlayerCards;
use AGR\Core\Notifications;
use AGR\Core\Engine;
use AGR\Core\Globals;
use AGR\Helpers\Utils;

class ActivateCard extends \AGR\Models\Action
{
  public function getState()
  {
    return ST_ACTIVATE_CARD;
  }

  public function getCard()
  {
    return PlayerCards::get($this->getCtxArgs()['cardId']);
  }

  public function getFlow($player)
  {
    return $this->getCard()->isPlayed()
      ? PlayerCards::applyEffect(
        $this->getCard(),
        $player,
        $this->getCtxArgs()['event']['method'],
        $this->getCtxArgs()['event'],
        true // Throw error if no such listener
      )
      : null;
  }

  public function getFlowTree($player)
  {
    $flow = $this->getFlow($player);
    return is_null($flow) ? null : Engine::buildTree($flow);
  }

  public function isOptional()
  {
    $player = $this->getPlayer();
    if (is_null($this->getFlowTree($player))) {
      return true;
    }
    return $this->getFlowTree($player)->isOptional();
  }

  public function isAutomatic($player = null)
  {
    return true;
  }

  public function isDoable($player, $ignoreResources = false)
  {
    $flowTree = $this->getFlowTree($player);
    return is_null($flowTree) ? false : $flowTree->isDoable($player, $ignoreResources);
  }

  public function isIndependent($player = null)
  {
    $flowTree = $this->getFlowTree($player);
    return is_null($flowTree) ? false : $flowTree->isIndependent($player);
  }

  public function getDescription($ignoreResources = false)
  {
    $flowTree = $this->getFlowTree($this->getPlayer());
    if (is_null($flowTree)) {
      return '';
    }

    $flowDesc = $flowTree->getDescription($ignoreResources);
    return [
      'log' => '${flowDesc} (${source})',
      'args' => [
        'i18n' => ['flowDesc', 'source'],
        'flowDesc' => $flowDesc,
        'source' => $this->getCard()->getName(),
      ],
    ];
  }

  public function stActivateCard()
  {
    $player = $this->getPlayer();
    $node = $this->ctx;
    $flow = $this->getFlow($player);
    $cardId = $this->getCtxArgs()['cardId'];

    if ($node->isMandatory()) {
      $flow['optional'] = false; // Remove optional to avoid double confirmation UX
    }
    // Add tag about that card
    $flow = Utils::tagTree($flow, [
      'sourceId' => $cardId
    ]);

    if ($flow['countAsUse'] ?? false) {
      PlayerCards::get($cardId)->incStats('used');
    }

    $node->replace(Engine::buildTree($flow));
    Engine::save();
    Engine::proceed();
  }
}
