<?php

namespace ARK\Actions;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Engine;
use ARK\Core\Globals;
use ARK\Helpers\Utils;
use ARK\Models\Player;

class ActivateCard extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_ACTIVATE_CARD;
  }

  public function getCard()
  {
    return ZooCards::get($this->getCtxArg('cardId'));
  }

  public function getFlow($player)
  {
    return $this->getCard()->isPlayed()
      ? ZooCards::applyEffect(
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

  public function isOptional(): bool
  {
    $player = $this->getPlayer();
    if (is_null($this->getFlowTree($player))) {
      return true;
    }
    return $this->getFlowTree($player)->isOptional();
  }

  public function isAutomatic(?Player $player = null): bool
  {
    return true;
  }

  public function isDoable(Player $player): bool
  {
    $flowTree = $this->getFlowTree($player);
    return is_null($flowTree) ? false : $flowTree->isDoable($player);
  }

  public function isIrreversible(?Player $player = null): bool
  {
    $flowTree = $this->getFlowTree($player);
    return is_null($flowTree) ? false : $flowTree->isIrreversible();
  }

  public function isIndependent(?Player $player = null): bool
  {
    if ($this->getCtxArgs()['event']['method'] == 'getIncome') {
      return false;
    }

    $flowTree = $this->getFlowTree($player);
    return is_null($flowTree) ? false : $flowTree->isIndependent($player);
  }

  public function getDescription(): string|array
  {
    $flowTree = $this->getFlowTree($this->getPlayer());
    if (is_null($flowTree)) {
      return '';
    }

    $flowDesc = $flowTree->getDescription();
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
    if ($node->isMandatory()) {
      $flow['optional'] = false; // Remove optional to avoid double confirmation UX
    }
    // Add tag about that card
    $flow = Utils::tagTree($flow, [
      'sourceId' => $this->getCtxArg('cardId'),
    ]);

    $node->replace(Engine::buildTree($flow));
    Engine::save();
    Engine::proceed();
  }
}
