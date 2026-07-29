<?php

namespace ARK\Actions;

use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Managers\ZooCards;
use ARK\Core\Notifications;
use ARK\Core\Engine;
use ARK\Core\Globals;
use ARK\Helpers\Utils;
use ARK\Helpers\FlowConvertor;
use ARK\Models\Player;

class UseBonusTile extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_USE_KEPT_BONUS;
  }

  public function getBonus()
  {
    $meeple = Meeples::get($this->getCtxArg('meepleId'));
    return [$meeple['type'], $meeple['state'], clienttranslate('bonus tile')];
  }

  public function isIrreversible(?Player $player = null): bool
  {
    return false;
  }

  public function getFlow($player)
  {
    list($type, $n, $source) = $this->getBonus();
    if ($type == BONUS_RETURN_WORKER) $type = EXTRA_SHIFT;
    if ($type == BONUS_SPONSOR_MONEY_MW) $type = BUY_SPONSOR;

    return FlowConvertor::getFlowSingleBonus($type, $n, $source);
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

  public function doNotDisplayIfNotDoable(): bool
  {
    $player = Players::getActive();
    $flowTree = $this->getFlowTree($player);
    return is_null($flowTree) ? false : $flowTree->doNotDisplayIfNotDoable();
  }

  public function isIndependent(?Player $player = null): bool
  {
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
    list($type, $n) = $this->getBonus();
    return [
      'log' => clienttranslate('Use ${bonus_pentagon} : ${flowDesc}'),
      'args' => [
        'i18n' => ['flowDesc'],
        'flowDesc' => $flowDesc,
        'bonus_source_type' => 'bonus',
        'bonus_pentagon' => '',
        'bonus_type' => $type,
        'bonus_n' => $n,
      ],
    ];
  }

  public function stUseBonusTile()
  {
    $player = $this->getPlayer();
    list($type, $n, $source) = $this->getBonus();

    $meeple = Meeples::getSingle($this->getCtxArg('meepleId'));
    Meeples::move($meeple['id'], 'box');
    Notifications::useBonus($player, $type, $n, $source, $meeple);

    // Replace this node by the actual flow of the bonus
    $node = $this->ctx;
    $flow = $this->getFlow($player);
    if ($node->isMandatory()) {
      $flow['optional'] = false; // Remove optional to avoid double confirmation UX
    }

    $node->replace(Engine::buildTree($flow));
    Engine::save();
    Engine::proceed();
  }
}
