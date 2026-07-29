<?php
namespace AGR\Managers;

use AGR\Core\Game;

/* Class to manage all the cards for Agricola */

class Actions
{
  static $classes = [
    COLLECT => 'Collect',
    GAIN => 'Gain',
    CONSTRUCT => 'Construct',
    PLOW => 'Plow',
    FIRSTPLAYER => 'FirstPlayer',
    IMPROVEMENT => 'Improvement',
    SOW => 'Sow',
    STABLES => 'Stables',
    RENOVATION => 'Renovation',
    FENCING => 'Fencing',
    WISHCHILDREN => 'WishChildren',
    PAY => 'Pay',
    REORGANIZE => 'Reorganize',
    EXCHANGE => 'Exchange',
    PLACE_FARMER => 'PlaceFarmer',
    OCCUPATION => 'Occupation',
    ACTIVATE_CARD => 'ActivateCard',
    SPECIAL_EFFECT => 'SpecialEffect',
    RECEIVE => 'Receive',
    REAP => 'Reap',
    PLACE_FUTURE_MEEPLES => 'PlaceFutureMeeples',
    PLACE_MEEPLES_FROM_SUPPLY => 'PlaceMeeplesFromSupply',
  ];

  public static function get($actionId, $ctx = null)
  {
    if (!\array_key_exists($actionId, self::$classes)) {
      throw new \BgaVisibleSystemException('Trying to get an atomic action not defined in Actions.php : ' . $actionId);
    }
    $name = '\AGR\Actions\\' . self::$classes[$actionId];
    return new $name($ctx);
  }

  public static function getActionOfState($stateId, $throwErrorIfNone = true)
  {
    foreach (array_keys(self::$classes) as $actionId) {
      if (self::getState($actionId, null) == $stateId) {
        return $actionId;
      }
    }

    if ($throwErrorIfNone) {
      throw new \BgaVisibleSystemException('Trying to fetch args of a non-declared atomic action in state ' . $stateId);
    } else {
      return null;
    }
  }

  public static function isDoable($actionId, $ctx, $player, $ignoreResources = false)
  {
    $res = self::get($actionId, $ctx)->isDoable($player, $ignoreResources);
    // Cards that bypass isDoable (eg Paper Maker)
    $args = [
      'action' => $actionId,
      'ignoreResources' => $ignoreResources,
      'isDoable' => $res,
      'ctx' => $ctx,
    ];
    PlayerCards::applyEffects($player, 'isDoable', $args);
    return $args['isDoable'];
  }

  public static function getErrorMessage($actionId, $ctx = null)
  {
    // Potter Ceramics/Hand Truck force a Bake Bread action. If the player
    // has no way to bake we land here. (considered adding gates but it gets messy, clear error message better)
    if ($actionId == EXCHANGE && $ctx != null) {
      $ctxArgs = is_array($ctx) ? $ctx : $ctx->getArgs();
      if (($ctxArgs['trigger'] ?? null) == BREAD) {
        return clienttranslate(
          'You have no way to bake (no baking improvement), so the required Bake Bread action cannot be completed.'
        );
      }
    }

    $actionId = ucfirst(mb_strtolower($actionId));
    $msg = sprintf(
      clienttranslate(
        'Attempting to take an action (%s) that is not possible. Either another card erroneously flagged this action as possible, or this action was possible until another card interfered.'
      ),
      $actionId
    );
    return $msg;
  }

  public static function getState($actionId, $ctx)
  {
    return self::get($actionId, $ctx)->getState();
  }

  public static function getArgs($actionId, $ctx)
  {
    $action = self::get($actionId, $ctx);
    $methodName = 'args' . self::$classes[$actionId];
    return array_merge($action->$methodName(), ['optionalAction' => $ctx->isOptional()]);
  }

  public static function takeAction($actionId, $args, $ctx)
  {
    $player = Players::getActive();
    if (!self::isDoable($actionId, $ctx, $player)) {
      throw new \BgaUserException(self::getErrorMessage($actionId, $ctx));
    }

    $action = self::get($actionId, $ctx);
    $methodName = 'act' . self::$classes[$actionId];
    $action->$methodName(...$args);
  }

  public static function stAction($actionId, $ctx)
  {
    $player = Players::getActive();

    if (!self::isDoable($actionId, $ctx, $player)) {
      if (!$ctx->isOptional()) {
        if (self::isDoable($actionId, $ctx, $player, true)) {
          Game::get()->gamestate->jumpToState(ST_IMPOSSIBLE_MANDATORY_ACTION);
          return;
        } else {
          throw new \BgaUserException(self::getErrorMessage($actionId, $ctx));
        }
      } else {
        // Auto pass if optional and not doable
        Game::get()->actPassOptionalAction(true);
        return;
      }
    }

    $action = self::get($actionId, $ctx);
    $methodName = 'st' . self::$classes[$actionId];
    if (\method_exists($action, $methodName)) {
      $action->$methodName();
    }
  }
}