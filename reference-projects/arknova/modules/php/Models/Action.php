<?php

namespace ARK\Models;

use ARK\Core\Engine;
use ARK\Core\Game;
use ARK\Core\Globals;
use ARK\Core\Notifications;
use ARK\Managers\ZooCards;
use ARK\Managers\Players;
use ARK\Helpers\Log;
use ARK\Helpers\FlowConvertor;
use ARK\Models\Player;

/*
 * Action: base class to handle atomic action
 */

class Action
{
  protected $ctx = null; // Contain ctx information : current node of flow tree
  protected $description = '';
  public function __construct($ctx)
  {
    $this->ctx = $ctx;
  }

  public function isDoable(Player $player): bool
  {
    return true;
  }
  public function doNotDisplayIfNotDoable(): bool
  {
    return false;
  }

  public function isOptional(): bool
  {
    return false;
  }

  public function isIndependent(Player $player = null): bool
  {
    return false;
  }

  public function isAutomatic(Player $player = null): bool
  {
    return false;
  }

  public function isIrreversible(Player $player = null): bool
  {
    return false;
  }

  public function getDescription(): string|array
  {
    return $this->description;
  }

  public function getPlayer(): Player
  {
    $pId = $this->ctx->getPId() ?? Players::getActiveId();
    return Players::get($pId);
  }

  public function getState(): int
  {
    return 0;
  }

  protected $args = null;
  public function getArgs(): array
  {
    if (is_null($this->args)) {
      $methodName = 'args' . $this->getClassName();
      $this->args = \method_exists($this, $methodName) ? $this->$methodName() : [];
    }
    return $this->args;
  }


  /**
   * Syntaxic sugar
   */
  public function getCtxArgs(): array
  {
    if ($this->ctx == null) {
      return [];
    } elseif (is_array($this->ctx)) {
      return $this->ctx;
    } else {
      return $this->ctx->getArgs() ?? [];
    }
  }
  public function getCtxArg($v)
  {
    return $this->getCtxArgs()[$v] ?? null;
  }
  // Useful for the 5 actions corresponding to action cards
  public function getStrength()
  {
    return $this->getCtxArg('strength');
  }
  public function getLevel()
  {
    return $this->getCtxArg('lvl');
  }
  public function isUpgraded()
  {
    return $this->getLevel() == 2;
  }
  public function getNumber()
  {
    return $this->getCtxArg('number');
  }

  // Useful for the animals effects
  public function getN()
  {
    return $this->getCtxArg('n');
  }

  public function resolveAction($args = [], $checkpoint = false)
  {
    $player = Players::getActive();
    $args['automatic'] = $this->isAutomatic($player);
    Engine::resolveAction($args, $checkpoint, $this->ctx);
    Engine::proceed();
  }

  /**
   * Insert flow as child of current node
   */
  public function insertAsChild($flow)
  {
    Engine::insertAsChild($flow, $this->ctx);
  }

  /**
   * Insert childs on the next upcoming afterFinishingAction node
   */
  public function pushAfterFinishingChilds($childs)
  {
    Engine::pushAfterFinishingChilds($childs);
  }

  public function checkCanTakeIrreversible()
  {
    Engine::checkCanTakeIrreversible();
  }

  /**
   * Insert childs as parallel node childs
   */
  public function pushParallelChild($node)
  {
    $this->pushParallelChilds([$node]);
  }

  public function pushParallelChilds($childs)
  {
    Engine::insertOrUpdateParallelChilds($childs, $this->ctx);
  }

  /**
   * Given bonuses, compute the flow and insert them as childs (or on insertAfterFinishing node)
   */
  public function insertBonusesFlow($bonuses, $source = '', $sourceType = null, $sourceId = null)
  {
    if (empty($bonuses)) {
      return;
    }

    //    if (isset($bonuses[0]['type']) || isset($bonuses['type'])) {
    if (isset($bonuses['type'])) {
      // we already are receiving a node
      $immediate = $bonuses;
      $after = [];
    } else {
      list($immediate, $after) = FlowConvertor::getFlow($bonuses, $source, $sourceType, $sourceId);
    }
    $this->pushParallelChilds($immediate);
    $this->pushAfterFinishingChilds($after);
  }

  /**
   * Update the args of current node
   * @param array $args : the keys/values that needs to get updated
   * Warning: resolve action must be call on the side
   */
  public function duplicateAction($args = [], $checkpoint = false)
  {
    // Duplicate the node and update the args
    $node = $this->ctx->toArray();
    $node['type'] = \NODE_LEAF;
    $node['childs'] = [];
    $node['args'] = array_merge($node['args'] ?? [], $args);
    $node['duplicate'] = true;
    unset($node['mandatory']); // Weird edge case
    $node = Engine::buildTree($node);
    // Insert it as a brother of current node and proceed
    $this->ctx->insertAsBrother($node);
    Engine::save();

    if ($checkpoint) {
      Engine::checkpoint();
    }
    // Engine::proceed();
  }

  public static function checkAction($action, $byPassActiveCheck = false)
  {
    if ($byPassActiveCheck) {
      Game::get()->gamestate->checkPossibleAction($action);
    } else {
      Game::get()->checkAction($action);
      $stepId = Log::step();
      // var_dump($stepId);
      // die('test');
      Notifications::newUndoableStep(Players::getCurrent(), $stepId);
    }
  }

  public function getClassName()
  {
    $classname = get_class($this);
    if ($pos = strrpos($classname, '\\')) {
      return substr($classname, $pos + 1);
    }
    return $classname;
  }

  protected function checkListeners($method, $player, $args = [])
  {
    $event = array_merge(
      [
        'pId' => $player->getId(),
        'type' => 'action',
        'action' => $this->getClassName(),
        'method' => $method,
      ],
      $args
    );

    $reaction = ZooCards::getReaction($event);
    $this->pushParallelChilds($reaction);
  }

  protected function checkIconsListeners($icons, $player)
  {
    list($immediateReaction, $afterReaction) = ZooCards::getIconsReaction($icons, $player, true);
    $this->pushParallelChilds($immediateReaction);
    $this->pushAfterFinishingChilds($afterReaction);
  }

  public function checkAfterListeners($player, $args = [], $duringActionListener = true)
  {
    if ($duringActionListener) {
      $this->checkListeners($this->getClassName(), $player, $args);
    }
    $this->checkListeners('ImmediatelyAfter' . $this->getClassName(), $player, $args);
    $this->checkListeners('After' . $this->getClassName(), $player, $args);
  }

  public function checkModifiers($method, &$data, $name, $player, $args = [])
  {
    $args[$name] = $data;
    if (!isset($args['actionCardId'])) {
      $args['actionCardId'] = $this->ctx != null ? $this->ctx->getCardId() : null;
    }
    ZooCards::applyEffects($player, $method, $args);
    $data = $args[$name];
  }

  public function checkCostModifiers(&$costs, $player, $args = [])
  {
    $this->checkModifiers('computeCosts' . $this->getClassName(), $costs, 'costs', $player, $args);
  }

  public function checkArgsModifiers(&$actionArgs, $player, $args = [])
  {
    $this->checkModifiers('computeArgs' . $this->getClassName(), $actionArgs, 'actionArgs', $player, $args);
  }

  public function checkAllSiblingsAreGainMoney()
  {
    $parent = $this->ctx->getParent();
    if (is_null($parent)) {
      return false;
    }
    foreach ($parent->getChilds() as $node) {
      if ($node->isActionResolved()) {
        continue;
      }

      $action = $node->getAction();
      if (is_null($action)) {
        return false; // If not an action, return false
      }
      $args = $node->getArgs();

      if ($action == MONEY_INCOME) {
        continue;
      }
      if ($action == ACTIVATE_CARD && $args['event']['method'] == 'getIncome') {
        $card = ZooCards::get($args['cardId']);
        foreach ($card->getIncome() as $bonus) {
          if (array_keys($bonus)[0] != MONEY) {
            return false;
          }
        }
        continue;
      }

      return false;
    }

    return true;
  }
}
