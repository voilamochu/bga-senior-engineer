<?php

namespace ARK\Managers;

use ARK\Core\Game;
use ARK\Core\Engine;
use ARK\Core\Engine\AbstractNode;
use ARK\Managers\Players;
use ARK\Core\Globals;
use ARK\Models\Action;
use ARK\Models\ActionCard;
use ARK\Models\Player;

/* Class to manage all the cards for Arknova */

class Actions
{
  static $classes = [
    GAIN => 'Gain',
    PAY => 'Pay',
    ACTIVATE_CARD => 'ActivateCard',
    SPECIAL_EFFECT => 'SpecialEffect',
    CHOOSE_ACTION_CARD => 'ChooseActionCard',

    // Action cards
    ANIMALS => 'Animals',
    ASSOCIATION => 'Association',
    BUILD => 'Build',
    CARDS => 'Cards',
    SPONSORS => 'Sponsors',

    'Animals1' => 'Animals',
    'Association1' => 'Association',
    'Build1' => 'Build',
    'Cards1' => 'Cards',
    'Sponsors1' => 'Sponsors',
    'Animals2' => 'Animals',
    'Association2' => 'Association',
    'Build2' => 'Build',
    'Cards2' => 'Cards',
    'Sponsors2' => 'Sponsors',
    'Animals3' => 'Animals',
    'Association3' => 'Association',
    'Build3' => 'Build',
    'Cards3' => 'Cards',
    'Sponsors3' => 'Sponsors',
    'Animals4' => 'Animals',
    'Association4' => 'Association',
    'Build4' => 'Build',
    'Cards4' => 'Cards',
    'Sponsors4' => 'Sponsors',

    // Animals powers
    SPRINT => 'Effects\Sprint',
    HUNTER => 'Effects\Hunter',
    INVENTIVE => 'Effects\Inventive',
    JUMPING => 'Effects\Jumping',
    SUNBATHING => 'Effects\Sunbathing',
    POUCH => 'Effects\Pouch',
    DIGGING => 'Effects\Digging',
    VENOM => 'Effects\Venom',
    VENOM_PAY => 'Effects\VenomPay',
    PILFERING => 'Effects\Pilfering',
    PILFERING_EXECUTE => 'Effects\PilferingExecute',
    SNAPPING => 'Effects\Snapping',
    HYPNOSIS => 'Effects\Hypnosis',
    SCAVENGING => 'Effects\Scavenging',
    POSTURING => 'Effects\Posturing',
    PERCEPTION => 'Effects\Perception',
    PACK => 'Effects\Pack',
    CLEVER => 'Effects\Clever',
    BOOST => 'Effects\Boost',
    ACTION => 'Effects\Action',
    MULTIPLIER => 'Effects\Multiplier',
    FULL_THROATED => 'Effects\FullThroated',
    ICONIC_ANIMAL => 'Effects\IconicAnimal',
    RESISTANCE => 'Effects\Resistance',
    ASSERTION => 'Effects\Assertion',
    SPONSOR_MAGNET => 'Effects\SponsorMagnet',
    CONSTRICTION => 'Effects\Constriction',
    DETERMINATION => 'Effects\Determination',
    PEACOCKING => 'Effects\Peacocking',
    PETTING_ZOO_ANIMAL => 'Effects\PettingZooAnimal',
    DOMINANCE => 'Effects\Dominance',
    MAP4 => 'Effects\Map4Effect',
    MAP8 => 'Effects\Map8Effect',
    MAP9 => 'Effects\Map9Effect',
    MAP10 => 'Effects\Map10Effect',

    // Bonuses
    GAIN_UNIVERSITY => 'Bonuses\GainUniversity',
    GAIN_PARTNER_ZOO => 'Bonuses\GainPartnerZoo',
    BUY_SPONSOR => 'Bonuses\BuySponsor',
    MARKETING => 'Bonuses\BuySponsor', // New name in MW
    WAZA_SPECIAL => 'Bonuses\WazaSpecial',
    ARCHEOLOGIST_BONUS => 'Bonuses\ArcheologistBonus',

    // Other
    ADVANCE_BREAK => 'AdvanceBreak',
    CLEANUP => 'Cleanup',
    DISCARD => 'Discard',
    UPGRADE_CARD => 'UpgradeCard',
    RELEASE => 'Release',
    TAKE_BONUS => 'TakeBonus',
    MOVE_ANIMALS => 'MoveAnimals',
    TAKE_IN_RANGE_OR_DECK => 'TakeInRange',
    'take-in-range' => 'TakeInRange', // TODO : remove in a while
    DISCARD_SCORING => 'DiscardScoring',
    MONEY_INCOME => 'MoneyIncome',

    // MW
    USE_KEPT_BONUS => 'UseBonusTile',
    SEARCH_CARD => 'SearchCard',
    GAIN_MARKED => 'GainMarked',
    SYMBIOSIS => 'Effects\Symbiosis',
    GLIDE => 'Effects\Glide',
    SHARK_ATTACK => 'Effects\SharkAttack',
    CUT_DOWN => 'Effects\CutDown',
    CAMOUFLAGE => 'Effects\Camouflage',
    SCUBA_DIVE => 'Effects\ScubaDive',
    ADAPT => 'Effects\Adapt',
    MONKEY_GANG => 'Effects\MonkeyGang',
    MARK => 'Effects\Mark',
    TRADE => 'Effects\Trade',
    SEA_ANIMAL_MAGNET => 'Effects\SeaAnimalMagnet',
    SYMBIOSIS => 'Effects\Symbiosis',
    EXTRA_SHIFT => 'Effects\ExtraShift',

    DONATE => 'Bonuses\Donate',
    EXPEDITION => 'Bonuses\Expedition',
    SEARCH_PET_DISCARD => 'Bonuses\SearchPetDiscard',
    RECONSTRUCTION_REMOVE => 'Bonuses\ReconstructionRemove',
    RECONSTRUCTION_PLACE_BACK => 'Bonuses\ReconstructionPlaceBack',
    INCREASE_SIZE => 'Bonuses\IncreaseSize',

    ANIMALS2_HUNTER => 'Animals2Hunter',
    ANIMALS3_PAYGAIN => 'Animals3PayGain',
    SPONSORS_DISCARD_CARD_GET_BONUS => 'SponsorsDiscardCardGetBonus',

    // MAP PACK 2
    ANIMAL_MAGNET => 'Effects\AnimalMagnet',
    BONUS_WAVE => 'Bonuses\Wave',
    BONUS_FREE_SPONSOR_PERSON => 'Bonuses\FreePersonSponsor',
    MAP_13_INCOME => 'Effects\Map13Income',
    STORE => 'Effects\Map11EffectStore',
    UNSTORE => 'Effects\Map11EffectUnstore',
  ];

  public static function get(string $actionId, null|array|AbstractNode $ctx = null): Action
  {
    if (!\array_key_exists($actionId, self::$classes)) {
      // throw new \feException(print_r(debug_print_backtrace()));
      // throw new \feException(print_r(Globals::getEngine()));
      throw new \BgaVisibleSystemException('Trying to get an atomic action not defined in Actions.php : ' . $actionId);
    }
    $name = '\ARK\Actions\\' . self::$classes[$actionId];
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

  public static function isDoable(string $actionId, null|array|AbstractNode $ctx, Player $player)
  {
    $res = self::get($actionId, $ctx)->isDoable($player);
    return $res;
  }

  public static function getErrorMessage($actionId)
  {
    if ($actionId == VENOM_PAY) {
      return Game::get()::translate(clienttranslate('You no longer have enough money to pay for Venom. You must undo or restart your turn.'));
    }

    $actionId = ucfirst(mb_strtolower($actionId));
    $msg = sprintf(
      Game::get()::translate(clienttranslate(
        'Attempting to take an action (%s) that is not possible. Either another card erroneously flagged this action as possible, or this action was possible until another card interfered.'
      )),
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
    $args = $action->getArgs();
    return array_merge($args, ['optionalAction' => $ctx->isOptional()]);
  }

  public static function takeAction($actionId, $actionName, $args, $ctx)
  {
    $player = Players::getActive();
    if (!self::isDoable($actionId, $ctx, $player)) {
      throw new \BgaUserException(self::getErrorMessage($actionId));
    }

    $action = self::get($actionId, $ctx);
    $methodName = $actionName; //'act' . self::$classes[$actionId];
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
          throw new \BgaUserException(self::getErrorMessage($actionId));
        }
      } else {
        // Auto pass if optional and not doable
        Game::get()->actPassOptionalAction(true);
        return;
      }
    }

    $action = self::get($actionId, $ctx);
    $methodName = 'st' . $action->getClassName();
    if (\method_exists($action, $methodName)) {
      $action->$methodName();
    }
  }

  public static function stPreAction($actionId, &$ctx)
  {
    $action = self::get($actionId, $ctx);
    $methodName = 'stPre' . $action->getClassName();
    if (\method_exists($action, $methodName)) {
      $action->$methodName();
      if ($ctx->isIrreversible(Players::get($ctx->getPId()))) {
        Engine::checkpoint();
      }
    }
  }

  public static function pass($actionId, $ctx)
  {
    if (!$ctx->isOptional()) {
      // self::error($ctx->toArray());
      throw new \BgaVisibleSystemException('This action is not optional');
    }

    $action = self::get($actionId, $ctx);
    $methodName = 'actPass' . $action->getClassName();
    if (\method_exists($action, $methodName)) {
      $action->$methodName();
    } else {
      Engine::resolve(PASS);
    }

    Engine::proceed();
  }
}
