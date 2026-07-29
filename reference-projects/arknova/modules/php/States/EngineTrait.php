<?php

namespace ARK\States;

use ARK\Core\Globals;
use ARK\Core\Engine;
use ARK\Core\Game;
use ARK\Core\Notifications;
use ARK\Managers\Players;
use ARK\Managers\Meeples;
use ARK\Managers\Fences;
use ARK\Managers\Actions;
use ARK\Managers\ZooCards;
use ARK\Models\PlayerBoard;
use ARK\Actions\Effects\VenomPay;
use ARK\Actions\Animals;
use ARK\Helpers\Log;

trait EngineTrait
{
  function addCommonArgs(&$args)
  {
    $args['previousEngineChoices'] = Globals::getEngineChoices();
    $args['previousSteps'] = Log::getUndoableSteps();
    if (!($args['automaticAction'] ?? false)) {
      if (isset($args['_private']['active'])) {
        $args['_private'][Players::getActiveId()] = $args['_private']['active'];
        unset($args['_private']['active']);
      }

      foreach (Players::getAll() as $pId => $player) {
        $args['_private'][$pId]['statuses'] = ZooCards::getStatuses($player);
      }
    }
  }

  /**
   * Trying to get the atomic action corresponding to the state where the game is
   */
  function getCurrentAtomicAction()
  {
    $stateId = $this->gamestate->state_id();
    return Actions::getActionOfState($stateId);
  }

  /**
   * Ask the corresponding atomic action for its args
   */
  function argsAtomicAction()
  {
    $player = Players::getActive();
    $action = $this->getCurrentAtomicAction();
    $node = Engine::getNextUnresolved();
    $args = Actions::getArgs($action, $node);
    $args['automaticAction'] = Actions::get($action, $node)->isAutomatic($player);
    if ($args['automaticAction']) {
      $args['_no_notify'] = true;
      return $args;
    }
    $this->addArgsAnytimeAction($args, $action);

    $sourceId = $node->getSourceId() ?? null;
    if (!isset($args['source']) && !is_null($sourceId)) {
      $args['sourceId'] = $sourceId;
      $args['source'] = ZooCards::get($sourceId)->getName();
    }
    $source = $node->getSource() ?? null;
    if (!isset($args['source']) && !is_null($source)) {
      $args['source'] = $source;
    }

    return $args;
  }

  /**
   * Add anytime actions
   */
  function addArgsAnytimeAction(&$args, $action)
  {
    $this->addCommonArgs($args);

    // If the action is auto => don't display anytime buttons
    if ($args['automaticAction'] ?? false) {
      return;
    }
    $player = Players::getActive();
    $actions = [];

    // Player may pay venom upfront
    if (Globals::isVenomTriggered() && VenomPay::needToPay($player) && !in_array($this->gamestate->state_id(), [ST_DISCARD_SCORING, ST_PILFERING_EXECUTE, ST_VENOM_PAY])) {
      $actions[] = [
        'action' => \VENOM_PAY,
        'pId' => $player->getId(),
      ];
    }

    // Map 4 anytime action : discard 1 card for 3 money
    if ($player->canUseMap(4) && !in_array($this->gamestate->state_id(), MAP4_FORBIDDEN) && !Globals::isBreak()) {
      $actions[] = [
        'action' => \MAP4,
        'pId' => $player->getId(),
      ];
    }

    // Map 11 anytime action : take 1 card back into your hand
    if ($player->canUseMap(11) && !in_array($this->gamestate->state_id(), MAP11_FORBIDDEN)) {
      $actions[] = [
        'action' => UNSTORE,
        'pId' => $player->getId(),
      ];
    }

    // Kept bonus tiles
    foreach ($player->getKeptBonusTiles() as $bonus) {
      if (in_array($bonus['type'], array_merge([BONUS_RETURN_WORKER, BONUS_SPONSOR_MONEY_MW])) && !in_array($this->gamestate->state_id(), MAP4_FORBIDDEN)) {
        $actions[] = [
          'action' => USE_KEPT_BONUS,
          'args' => ['meepleId' => $bonus['id']]
        ];
      }
    }

    // Keep only doable actions
    $anytimeActions = [];
    foreach ($actions as $flow) {
      $tree = Engine::buildTree($flow);
      if ($tree->isDoable($player)) {
        $anytimeActions[] = [
          'flow' => $flow,
          'desc' => $flow['desc'] ?? $tree->getDescription(true),
          'optionalAction' => $tree->isOptional(),
          'independentAction' => $tree->isIndependent($player),
        ];
      }
    }

    // TAKE ALL INCOME MONEY
    if (Globals::isBreak() && $action == 'resolveChoice') {
      $choice = Engine::getNextUnresolved();
      if ($choice->getFlag() == 'ChooseIncomeNode') {
        $atLeastOneMoney = false;
        foreach ($choice->getChilds() as $i => $node) {
          if ($node->isActionResolved()) {
            continue;
          }
          if ($this->isMoneyNode($node)) {
            $atLeastOneMoney = true;
            break;
          }
        }

        if ($atLeastOneMoney) {
          $anytimeActions[] = [
            'flow' => 'ChooseIncomeNode',
            'desc' => clienttranslate('Take all <MONEY> income'),
            'optionalAction' => true,
            'independentAction' => false,
          ];
        }
      }
    }

    $args['anytimeActions'] = $anytimeActions;
  }

  function actAnytimeAction($choiceId, $auto = false)
  {
    $args = $this->gamestate->state()['args'];
    if (!isset($args['anytimeActions'][$choiceId])) {
      throw new \BgaVisibleSystemException('You can\'t take this anytime action');
    }

    Log::step();
    $flow = $args['anytimeActions'][$choiceId]['flow'];
    if (!$auto) {
      Globals::incEngineChoices();
    }

    // TAKE ALL INCOME MONEY
    if ($flow == 'ChooseIncomeNode') {
      $choice = Engine::getNextUnresolved();
      foreach ($choice->getChilds() as $i => $node) {
        if ($node->isActionResolved()) {
          continue;
        }
        if ($this->isMoneyNode($node)) {
          $choice->choose($i, true);
          Engine::proceed();
        }
      }
      return;
    }

    Engine::insertAtRoot($flow, false);
    Engine::proceed();
  }

  function isMoneyNode($node)
  {
    $action = $node->getAction();
    if (is_null($action)) {
      return false; // If not an action, return false
    }
    $args = $node->getArgs();
    if ($action == MONEY_INCOME) {
      return true;
    }
    if ($action == ACTIVATE_CARD && $args['event']['method'] == 'getIncome') {
      $card = ZooCards::get($args['cardId']);
      foreach ($card->getIncome() as $bonus) {
        if (array_keys($bonus)[0] != MONEY) {
          return false;
        }
      }
      return true;
    }
    if ($action == MAP_13_INCOME) {
      $quarter = $args['n'];
      $bonus = Players::get($node->getPId())->map()->getQuarterIncome($quarter);
      if ((array_keys($bonus)[0] ?? MONEY) != MONEY) {
        return false;
      }
      return true;
    }

    return false;
  }

  /**
   * Pass the argument of the action to the atomic action
   */
  function actTakeAtomicAction($actionName, $args)
  {
    self::checkAction($actionName);
    $action = $this->getCurrentAtomicAction();
    Actions::takeAction($action, $actionName, $args, Engine::getNextUnresolved());
  }

  /**
   * To pass if the action is an optional one
   */
  function actPassOptionalAction($auto = false)
  {
    if ($auto) {
      $this->gamestate->checkPossibleAction('actPassOptionalAction');
    } else {
      Log::step();
      Globals::incEngineChoices();
      self::checkAction('actPassOptionalAction');
    }

    $action = $this->getCurrentAtomicAction();
    Actions::pass($action, Engine::getNextUnresolved());
  }

  /**
   * Pass the argument of the action to the atomic action
   */
  function stAtomicAction()
  {
    $action = $this->getCurrentAtomicAction();
    $node = Engine::getNextUnresolved();
    Actions::stAction($action, $node);
  }

  /********************************
   ********************************
   ********** FLOW CHOICE *********
   ********************************
   ********************************/
  function argsResolveChoice()
  {
    $player = Players::getActive();
    $node = Engine::getNextUnresolved();
    if (is_null($node)) {
      return [];
    }

    $args = array_merge($node->getArgs() ?? [], [
      'choices' => Engine::getNextChoice($player),
      'allChoices' => Engine::getNextChoice($player, true),
    ]);

    if ($node->getStateDescription() != "") {
      $desc = $node->getStateDescription();
      $args['description'] = $desc['description'];
      $args['descriptionmyturn'] = $desc['descriptionmyturn'];
      $args = array_merge($args, $desc['args']);
    } else if ($node instanceof \ARK\Core\Engine\XorNode) {
      $args['descSuffix'] = 'xor';
    }

    $sourceId = $node->getSourceId() ?? null;
    if (!isset($args['source']) && !is_null($sourceId)) {
      $args['sourceId'] = $sourceId;
      $args['source'] = ZooCards::get($sourceId)->getName();
    }
    $this->addArgsAnytimeAction($args, 'resolveChoice');
    return $args;
  }

  function actChooseAction($choiceId)
  {
    $player = Players::getActive();
    Engine::chooseNode($player, $choiceId);
  }

  public function stResolveStack() {}

  public function stResolveChoice() {}

  function argsImpossibleAction()
  {
    $player = Players::getActive();
    $node = Engine::getNextUnresolved();

    $args = [
      'desc' => $node->getDescription(),
    ];
    $this->addArgsAnytimeAction($args, 'impossibleAction');
    return $args;
  }

  /*******************************
   ******* CONFIRM / RESTART ******
   ********************************/
  public function argsConfirmTurn()
  {
    $data = [
      'previousEngineChoices' => Globals::getEngineChoices(),
      'previousSteps' => Log::getUndoableSteps(),
      'automaticAction' => false,
    ];
    $this->addArgsAnytimeAction($data, 'confirmTurn');
    return $data;
  }

  public function stConfirmTurn()
  {
    $player = Players::getActive();
    // Check user preference to bypass if DISABLED is picked
    $pref = $player->getPref(OPTION_CONFIRM);
    if ((Globals::getEngineChoices() == 0 || $pref == OPTION_CONFIRM_DISABLED)
      && !$player->hasAnytimeUsefulAction()
    ) {
      $this->actConfirmTurn(true);
    }
  }

  public function actConfirmTurn($auto = false)
  {
    if (!$auto) {
      self::checkAction('actConfirmTurn');
    }
    Engine::confirm();
  }

  public function actConfirmPartialTurn($auto = false)
  {
    if (!$auto) {
      self::checkAction('actConfirmPartialTurn');
    }
    Engine::confirmPartialTurn();
  }

  public function actRestart()
  {
    self::checkAction('actRestart');
    if (Globals::getEngineChoices() < 1) {
      throw new \BgaVisibleSystemException('No choice to undo');
    }
    Engine::restart();
  }

  public function actUndoToStep($stepId)
  {
    self::checkAction('actRestart');
    $steps = Log::getUndoableSteps();
    if (!in_array($stepId, $steps)) {
      throw new \BgaVisibleSystemException('You cant undo here');
    }
    Engine::undoToStep($stepId);
  }
}
