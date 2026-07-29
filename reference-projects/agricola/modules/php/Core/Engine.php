<?php
namespace AGR\Core;
use AGR\Managers\Players;
use AGR\Managers\Actions;
use AGR\Managers\Scores;
use AGR\Managers\PlayerCards;
use AGR\Helpers\Log;
use AGR\Helpers\QueryBuilder;

/*
 * Engine: a class that allows to handle complex flow
 */
class Engine
{
  public static $tree = null;

  public static function boot()
  {
    $t = Globals::getEngine();
    self::$tree = self::buildTree($t);
  }

  public static function save()
  {
    $t = self::$tree->toArray();
    Globals::setEngine($t);
  }

  /**
   * Setup the engine, given an array representing a tree
   * @param array $t
   */
  public static function setup($t, $callback)
  {
    self::$tree = self::buildTree($t);
    self::save();
    Globals::setCallbackEngineResolved($callback);
    Globals::setEngineChoices(0);
    Log::enable(); // Enable log
    Log::startEngine();
  }

  /**
   * Convert an array into a tree
   * @param array $t
   */
  public static function buildTree($t)
  {
    $t['childs'] = $t['childs'] ?? [];
    $type = $t['type'] ?? (empty($t['childs']) ? NODE_LEAF : NODE_SEQ);

    $childs = [];
    foreach ($t['childs'] as $child) {
      $childs[] = self::buildTree($child);
    }

    $className = '\AGR\Core\Engine\\' . ucfirst($type) . 'Node';
    unset($t['childs']);
    return new $className($t, $childs);
  }

  /**
   * Recursively compute the next unresolved node we are going to address
   */
  public static function getNextUnresolved()
  {
    return self::$tree->getNextUnresolved();
  }

  /**
   * Proceed to next unresolved part of tree
   */
  public static function proceed($confirmedPartial = false)
  {
    $node = self::$tree->getNextUnresolved();
    // Are we done ?
    if ($node == null) {
      if (Globals::getEngineChoices() == 0) {
        self::confirm(); // No choices were made => auto confirm
      } else {
        // Confirm/restart
        Game::get()->gamestate->jumpToState(ST_CONFIRM_TURN);
      }
      return;
    }

    $oldPId = Game::get()->getActivePlayerId();
    $pId = $node->getPId();

    //failure case: if an empty reaction fires, control can pass to the wrong player. eg Forest Inn + Claypipe in play
    if (
      $pId != null &&
      $oldPId != $pId &&
      $node->getType() == NODE_PARALLEL
    ) {
      $reactionPlayer = Players::get($pId);
      $choicesPreview = $node->getChoices($reactionPlayer);
      $allChoicesPreview = $node->getChoices($reactionPlayer, true);
      $onlyPass =
        count($choicesPreview) == 1 && isset($choicesPreview[PASS]) &&
        count($allChoicesPreview) == 1 && isset($allChoicesPreview[PASS]);

      if ($onlyPass) {
        self::resolve(PASS);
        self::proceed($confirmedPartial);
        return;
      }
    }

    if (
      $pId != null &&
      $oldPId != $pId &&
      (!$node->isIndependent(Players::get($pId)) && Globals::getEngineChoices() != 0) &&
      !$confirmedPartial
    ) {
      Game::get()->gamestate->jumpToState(ST_CONFIRM_PARTIAL_TURN);
      return;
    }

    $player = Players::get($pId);
    // Jump to resolveStack state to ensure we can change active pId
    if ($pId != null && $oldPId != $pId) {
      Game::get()->gamestate->jumpToState(ST_RESOLVE_STACK);
      Game::get()->gamestate->changeActivePlayer($pId);
    }

    if ($confirmedPartial) {
      Log::enable();
      Log::checkpoint();
      Globals::setEngineChoices(0);
    }

    // If node with choice, switch to choice state
    $choices = $node->getChoices($player);
    $allChoices = $node->getChoices($player, true);
    if (!empty($allChoices) && $node->getType() != NODE_LEAF) {
      // Only one choice : auto choose (unless flagged forceChoice)
      if (count($choices) == 1 && count($allChoices) == 1 && array_keys($allChoices) == array_keys($choices) && !$node->forceChoice()) {
        $id = array_keys($choices)[0];
        self::chooseNode($player, $id, true);
      } else {
        // Otherwise, go in the RESOLVE_CHOICE state
        Game::get()->gamestate->jumpToState(ST_RESOLVE_CHOICE);
      }
    } else {
      // No choice => proceed to do the action
      $state = $node->getState();
      $args = $node->getArgs() ?? [];
      $actionId = Actions::getActionOfState($state, false);
      // Are there any replacement of the action we got? eg: Freshman
      if ($actionId != null && !($args['checkedReplaceAction'] ?? false)) {
        $action = Actions::get($actionId);
        $reaction = PlayerCards::getReaction([
          'type' => 'action',
          'method' => 'computeReplace' . $action->getClassName(),
          'action' => $action->getClassName(),
          'args' => $args,
          'cardId' => $node->getCardId(),
          'pId' => $pId ?? $oldPId,
        ], true, NODE_XOR);
        if ($reaction != null) {
          $actionFlow = $node->toArray();
          $actionFlow['args']['checkedReplaceAction'] = true; // Make sure to flag the flow to avoid infinite loop
          $flow = [
            'type' => NODE_XOR,
            'optional' => $node->isOptional(),
            'childs' => [$reaction, $actionFlow],
          ];
          $node->replace(Engine::buildTree($flow));
          self::save();
          self::proceed();
          return;
        }
        $args['checkedReplaceAction'] = true;
      }
      // Are there any "before" listener ? eg: Paper Maker
      if ($actionId != null && !($args['checkedBeforeAction'] ?? false)) {
        $action = Actions::get($actionId);
        $reaction = PlayerCards::getReaction([
          'type' => 'action',
          'method' => 'before' . $action->getClassName(),
          'action' => $action->getClassName(),
          'args' => $args,
          'cardId' => $node->getCardId(),
          'pId' => $pId ?? $oldPId,
        ]);

        // If there is at least one such listener, insert it in a SEQ node before the actual "real flow"
        if ($reaction != null) {
          $actionFlow = $node->toArray();
          $actionFlow['args']['checkedBeforeAction'] = true; // Make sure to flag the flow to avoid infinite loop
          $flow = [
            'type' => NODE_SEQ,
            'childs' => [$reaction, $actionFlow],
          ];
          $node->replace(Engine::buildTree($flow));
          self::save();
          self::proceed();
          return;
        }
      }

      Game::get()->gamestate->jumpToState($state);
    }
  }

  /**
   * Get the list of choices of current node
   */
  public static function getNextChoice($player = null, $ignoreResources = false)
  {
    return self::$tree->getNextUnresolved()->getChoices($player, $ignoreResources);
  }

  /**
   * Choose one option
   */
  public static function chooseNode($player, $nodeId, $auto = false)
  {
    $node = self::$tree->getNextUnresolved();
    if ($node == null) {
      throw new \BgaVisibleSystemException('This choice is not possible anymore');
    }
    $args = $node->getChoices($player);
    if (!isset($args[$nodeId])) {
      throw new \BgaVisibleSystemException('This choice is not possible');
    }

    if (!$auto) {
      Globals::incEngineChoices();
      Log::step();
    }

    if ($nodeId == PASS) {
      self::resolve(PASS);
      self::proceed();
      return;
    }

    if ($node->getChilds()[$nodeId]->isResolved()) {
      throw new \BgaVisibleSystemException('Node is already resolved');
    }
    $node->choose($nodeId, $auto);
    self::save();
    self::proceed();
  }

  /**
   * Resolve the current unresolved node
   * @param array $args : store informations about the resolution (choices made by players)
   */
  public static function resolve($args = [])
  {
    $node = self::$tree->getNextUnresolved();
    $node->resolve($args);
    self::save();
  }

  public static function resolveAction($args = [], $checkpoint = false)
  {
    $node = self::$tree->getNextUnresolved();

    if(!$node->isReUsable()){
      $node->resolveAction($args);
      if($node->isResolvingParent()){
        $node->getParent()->resolve([]);
      }
    } else {
      // TODO : remove
      $node->resolveAction($args);
      if(!$node->getParent()->isResolved()){
        $node->unresolveAction();
        $node->getParent()->unchoose(); // TODO : add sanity checks ??
      }
    }
    self::save();

    if (!isset($args['automatic']) || $args['automatic'] === false) {
      Globals::incEngineChoices();
    }
    if ($checkpoint) {
      self::checkpoint();
    }

    Scores::update();
  }

  public static function checkpoint()
  {
    Globals::setEngineChoices(0);
    Log::checkpoint();
  }

  /**
   * Insert a new node at root level at the end of seq node
   */
  public static function insertAtRoot($t, $last = true)
  {
    self::ensureSeqRootNode();
    if ($last) {
      self::$tree->pushChild(self::buildTree($t));
    } else {
      self::$tree->unshiftChild(self::buildTree($t));
    }
    self::save();
  }

  /**
   * Ensure the root is a SEQ node to be able to insert easily in the current flow
   */
  protected static function ensureSeqRootNode()
  {
    if (!self::$tree instanceof \AGR\Core\Engine\SeqNode) {
      self::$tree = new \AGR\Core\Engine\SeqNode([], [self::$tree]);
    }
  }

  public static function insertAsChild($t)
  {
    if ($t === null) {
      return;
    }
    self::ensureSeqRootNode();
    $node = self::$tree->getNextUnresolved();

    // If the node is an action leaf, turn it into a SEQ node first
    if($node == null){
      self::insertAtRoot($t);
      return;
    }
    if ($node->getType() == NODE_LEAF) {
      $newNode = $node->toArray();
      $newNode['type'] = NODE_SEQ;
      $node = $node->replace(self::buildTree($newNode));
    }

    // Push child
    $node->pushChild(self::buildTree($t));
    self::save();
  }

  /**
   * Confirm the full resolution of current flow
   */
  public static function confirm()
  {
    $node = self::$tree->getNextUnresolved();
    // Are we done ?
    if ($node != null) {
      throw new \feException("You can't confirm an ongoing turn");
    }

    // Callback
    $callback = Globals::getCallbackEngineResolved();
    if (isset($callback['state'])) {
      Game::get()->gamestate->jumpToState($callback['state']);
    } elseif (isset($callback['order'])) {
      Game::get()->nextPlayerCustomOrder($callback['order']);
    } elseif (isset($callback['method'])) {
      $name = $callback['method'];
      Game::get()->$name();
    }
  }

  public static function confirmPartialTurn()
  {
    $node = self::$tree->getNextUnresolved();

    // Are we done ?
    if ($node == null) {
      throw new \feException("You can't partial confirm an ended turn");
    }

    $oldPId = Game::get()->getActivePlayerId();
    $pId = $node->getPId();

    if ($oldPId == $pId) {
      throw new \feException("You can't partial confirm for the same player");
    }

    // Clear log
    self::checkpoint();
    Engine::proceed(true);
  }

  /**
   * Restart the whole flow
   */
  public static function restart()
  {
    Log::undoTurn();

    // Force to clear cached informations
    Globals::fetch();
    self::boot();
    self::proceed(false, true);
  }

  /**
   * Restart at a given step
   */
  public static function undoToStep($stepId)
  {
    Log::undoToStep($stepId);

    // Force to clear cached informations
    Globals::fetch();
    self::boot();
    self::proceed(false, true);
  }

  /**
   * Clear all nodes related to the current active zombie player
   */
  public static function clearZombieNodes($pId)
  {
    self::$tree->clearZombieNodes($pId);
  }

  /**
   * Get all resolved actions of given type
   */
  public static function getResolvedActions($types)
  {
    return self::$tree->getResolvedActions($types);
  }

  public static function getLastResolvedAction($types)
  {
    $actions = self::getResolvedActions($types);
    return empty($actions) ? null : $actions[count($actions) - 1];
  }
}
