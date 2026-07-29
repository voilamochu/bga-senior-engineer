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

class TakeBonus extends \ARK\Models\Action
{
  public function getState(): int
  {
    return ST_TAKE_BONUS;
  }

  public function getBonus()
  {
    return [$this->getCtxArg('type'), $this->getCtxArg('n'), $this->getCtxArg('source') ?? null];
  }

  public function isIrreversible(?Player $player = null): bool
  {
    list($type, $n, $source) = $this->getBonus();
    return ($type == DISCARD_SCORING && !Globals::isSolo()) || $type == BONUS_FINAL_SCORING;
  }

  public function getFlow($player)
  {
    list($type, $n, $source) = $this->getBonus();
    if ($type == BONUS_KIOSK_PAVILION) $type = KIOSK_OR_PAVILION;
    if ($type == BONUS_FINAL_SCORING) $type = ADAPT;

    // Grey bonus tiles => keep them
    if (in_array($type, KEEPER_BONUS_TILES)) {
      // Only this tile also has an immediate effect
      if ($type == BONUS_SNAP_CARDLIMIT) $type = SNAPPING;
      // Otherwise return a fake gain node
      else {
        $type = APPEAL;
        $n = 0;
      }
    }

    return FlowConvertor::getFlowSingleBonus($type, $n, $source);
  }

  public function getFlowTree($player)
  {
    list($type, $n) = $this->getBonus();
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
    if ($type == DISCARD_SCORING || ($this->getCtxArg('noIcon') ?? false)) {
      return $flowDesc; // No icon for discard scoring cards
    }

    if ($type == MAP_13_INCOME) {
      $type = 'quarters';
    }


    // Keeping tile => do not display flowDesc
    if (in_array($type, KEEPER_BONUS_TILES)) {
      return [
        'log' => clienttranslate('Take ${bonus_pentagon}'),
        'args' => [
          'bonus_source_type' => $this->getCtxArg('sourceType') ?? 'bonus',
          'bonus_pentagon' => '',
          'bonus_type' => $type,
          'bonus_n' => $n,
        ],
      ];
    }
    // Standard case => display pentagon + desc
    else {
      return [
        'log' => '${bonus_pentagon} : ${flowDesc}',
        'args' => [
          'i18n' => ['flowDesc'],
          'flowDesc' => $flowDesc,
          'bonus_source_type' => $this->getCtxArg('sourceType') ?? 'bonus',
          'bonus_pentagon' => '',
          'bonus_type' => $type,
          'bonus_n' => $n,
        ],
      ];
    }
  }

  public function stTakeBonus()
  {
    $player = $this->getPlayer();
    $args = $this->getCtxArgs();
    list($type, $n, $source) = $this->getBonus();
    $sourceType = $this->getCtxArg('sourceType') ?? 'bonus';


    // Remove the bonus from scoreboard if needed
    $bonusTiles = Globals::getBonusTiles();
    $remove = $args['remove'] ?? '';
    if (!empty($remove)) {
      list($conservation, $i) = explode('-', $remove);
      unset($bonusTiles[$conservation][$i]);
      Globals::setBonusTiles($bonusTiles);
    }

    // Create a meeple if needed (keeper tiles)
    $meeple = null;
    if (in_array($type, KEEPER_BONUS_TILES)) {
      $meeple = Meeples::addBonusToPlayer($player, $type, $n);
    }

    // Notify
    Notifications::takeBonus($player, $type, $n, $source, $sourceType, $remove, $bonusTiles, $meeple);

    // Replace this node by the actual flow of the bonus
    $node = $this->ctx;
    $flow = $this->getFlow($player);
    if ($node->isMandatory()) {
      $flow['optional'] = false; // Remove optional to avoid double confirmation UX
    }

    if (in_array($type, [CLEVER, DETERMINATION]) && !Globals::isBreak()) {
      $this->pushAfterFinishingChilds([$flow]);
      $this->resolveAction([CLEVER]);
    } else {
      $node->replace(Engine::buildTree($flow));
      if ($type == \DISCARD_SCORING) {
        Engine::checkpoint();
      }

      Engine::save();
      Engine::proceed();
    }
  }
}
