<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * arnak implementation : © Adam Spanel <adam.spanel@seznam.cz>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 * 
 * states.inc.php
 *
 * arnak game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: self::checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

// !! It is not a good idea to modify this file when a game is running !!

 
$machinestates = array(

  // The initial state. Please do not modify.
  1 => array(
    "name" => "gameSetup",
    "description" => "",
    "type" => "manager",
    "action" => "stGameSetup",
    "transitions" => array( "" => 2 )
  ),
  // Note: ID=2 => your first state
  NEXT_ROUND => array(
      "name" => "nextRound",
      "type" => "game",
      "action" => "stNextRound",
      "transitions" => array("decideKeep" => DECIDE_KEEP, "gameEnd" => 98)
  ),
  DECIDE_KEEP => array(
    "name" => "decideKeep",
    "type" => "multipleactiveplayer",
    "description" => clienttranslate('Everyone else may select cards to keep'),
    "descriptionmyturn" => clienttranslate('You may select cards to keep to the next round: Keep <span id="keep-num">0</span> cards'),
    "possibleactions" => array("confirmKeep"),
    "transitions" => array("allDiscarded" => NEXT_ROUND_CONT)
  ),
  NEXT_ROUND_CONT => array(
      "name" => "nextRound",
      "type" => "game",
      "action" => "stNextRoundCont",
      "description" => clienttranslate('Preparing for next round, please wait...'),
      "transitions" => array("nextPlayer" => NEXT_PLAYER, "gameEnd" => 98)
  ),
  NEXT_PLAYER => array(
      "name" => "nextPlayer",
      "action" => "stNextPlayer",
      "type" => "game",
      "transitions" => array( "playerActivated" => SELECT_ACTION, "allPassed" => NEXT_ROUND )
  ),
  SELECT_ACTION => array(
      "name" => "selectAction",
      "description" => clienttranslate('${actplayer} must select an action'),
      "descriptionmyturn" => clienttranslate('${you} must select an action'),
      "type" => "activeplayer",
      "updateGameProgression" => true,
      "possibleactions" => array( "playCard", "buyItem", "buyArt", "research",  "digSite", "discoverSite", "overcome", "useAssistant", "useActionAssistant", "useIdol", "useGuardPower", "getTempleTile", "pass", "undo"),
      "transitions" => array("siteEffect" => SITE_EFFECT, "discover" => EVAL_IDOL, "main_action_done" => AFTER_MAIN, "pass" => TURN_END, "turn_end" => NEXT_PLAYER, "research_bonus" => RESEARCH_BONUS, "assistantDiscard" => MUST_DISCARD_FREE, "mustTravel" => MUST_TRAVEL, "mayTravel" => MAY_TRAVEL, "cardExile" => MAY_EXILE, "artWaitArgs" => ART_WAIT_ARGS, "playArt" => ART_EFFECT, "buyItem" => BUY_ITEM, "buyArt" => BUY_ART, "jewelDiscardShell" => MUST_DISCARD_SHELL)
      /* TODO: update remove turn end */ 
  ),
  MUST_TRAVEL => array(
      "name" => "mustTravel",
      "type" => "activeplayer",
      "description" => clienttranslate('${actplayer} must travel to a site'),
      "descriptionmyturn" => clienttranslate('${you} must travel to a site'),
      "possibleactions" => array( "playCard", "digSite", "discoverSite", "useAssistant", "useIdol", "useGuardPower", "pass", "undo"),
      "transitions" => array("siteEffect" => SITE_EFFECT, "discover" => EVAL_IDOL, "cancel" => AFTER_MAIN),
  ),
  MAY_TRAVEL => array(
      "name" => "mayTravel",
      "type" => "activeplayer",
      "description" => clienttranslate('${actplayer} may travel to a site'),
      "descriptionmyturn" => clienttranslate('${you} may travel to a site'),
      "possibleactions" => array( "playCard", "digSite", "discoverSite", "useAssistant", "useIdol", "useGuardPower", "pass", "undo"),
      "transitions" => array("siteEffect" => SITE_EFFECT, "discover" => EVAL_IDOL, "cancel" => AFTER_MAIN),
  ),
  MAY_EXILE => array(
      "name" => "mayExile",
      "type" => "activeplayer",
      "description" => clienttranslate('${actplayer} may exile a card'),
      "descriptionmyturn" => clienttranslate('${you} may exile a card'),
      "possibleactions" => array("exile", "playCard", "useAssistant", "useIdol", "useGuardPower", "undo"),
      "transitions" => array("exileDone" => AFTER_MAIN, "researchRemains" => RESEARCH_BONUS),
  ),
  SITE_EFFECT => array(
      "name" => "evalLocation",
      "type" => "game",
      "description" => clienttranslate('Receiving the site\'s bonus...'),
      "transitions" => array("plane" => EVAL_PLANE, "evalDone" => AFTER_MAIN, "jewelDiscard" => MUST_DISCARD, "jewelDiscardShell" => MUST_DISCARD_SHELL)
  ),
  EVAL_IDOL => array(
      "name" => "evalIdol",
      "type" => "game",
    "description" => clienttranslate('Digging at the site...'),
      "transitions" => array("siteEffect" => SITE_EFFECT, "idolAssistant" => IDOL_ASSISTANT, "idolUpgrade" => IDOL_UPGRADE, "idolExile" => IDOL_EXILE, "idolRefresh" => IDOL_REFRESH) 
  ),
  MUST_DISCARD => array(
      "name" => "mustDiscard",
      "description" => clienttranslate('${actplayer} must discard a card'),
      "descriptionmyturn" => clienttranslate('${you} must discard a card'),
      "type" => "activeplayer",
      "possibleactions" => array("discard", "playCard", "useAssistant", "useIdol", "useGuardPower", "undo"),
      "transitions" => array("discard_done" => AFTER_MAIN)
  ),
  MUST_DISCARD_SHELL => array(
      "name" => "mustDiscard",
      "description" => clienttranslate('${actplayer} must discard a card'),
      "descriptionmyturn" => clienttranslate('${you} must discard a card'),
      "type" => "activeplayer",
      "possibleactions" => array("discard", "playCard", "useAssistant", "useIdol", "useGuardPower", "undo"),
      "transitions" => array("discard_done" => MAY_DISCARD_SHELL)
  ),
  MAY_DISCARD_SHELL => array(
      "name" => "mayDiscard",
      "description" => clienttranslate('${actplayer} may discard a card'),
      "descriptionmyturn" => clienttranslate('${you} may discard a card'),
      "type" => "activeplayer",
      "possibleactions" => array("discard", "cancelShell", "playCard", "useAssistant", "useIdol", "useGuardPower", "undo"),
      "transitions" => array("discard_done" => AFTER_MAIN)
  ),
  MUST_DISCARD_FREE => array(
      "name" => "mustDiscardFree",
      "description" => clienttranslate('${actplayer} must discard a card'),
      "descriptionmyturn" => clienttranslate('${you} must discard a card'),
      "type" => "activeplayer",
      "possibleactions" => array("discard", "playCard", "useAssistant", "useIdol", "useGuardPower", "undo"),
      "transitions" => array("discard_done" => SELECT_ACTION)
  ),
  EVAL_PLANE => array(
      "name" => "evalPlane",
      "description" => clienttranslate('${actplayer} may get an item for free'),
      "descriptionmyturn" => clienttranslate('${you} may get an item for free'),
      "type" => "activeplayer",
      "possibleactions" => array("buyItem", "playCard", "useAssistant", "useIdol", "useGuardPower", "planeCompass", "undo"),
      "transitions" => array("main_action_done" => AFTER_MAIN)
  ),
  IDOL_ASSISTANT => array(
      "name" => "idolUpgrade",
      "description" => clienttranslate('${actplayer} must refresh an assistant'),
      "descriptionmyturn" => clienttranslate('${you} must refresh an assistant'),
      "type" => "activeplayer",
      "possibleactions" => array("refresh", "playCard", "useAssistant", "useIdol", "useGuardPower", "undo"),
      "transitions" => array("siteEffect" => SITE_EFFECT)
  ),
  IDOL_UPGRADE => array(
      "name" => "idolUpgrade",
      "description" => clienttranslate('${actplayer} may upgrade'),
      "descriptionmyturn" => clienttranslate('${you} may upgrade'),
      "type" => "activeplayer",
      "possibleactions" => array("upgrade", "cancelUpgrade", "playCard", "useAssistant", "useIdol", "useGuardPower", "undo"),
      "transitions" => array("siteEffect" => SITE_EFFECT)
  ),
  IDOL_EXILE => array(
      "name" => "idolExile",
      "description" => clienttranslate('${actplayer} may exile a card'),
      "descriptionmyturn" => clienttranslate('${you} may exile a card'),
      "type" => "activeplayer",
      "possibleactions" => array("exile", "cancelIdolExile", "playCard", "useAssistant", "useIdol", "useGuardPower", "undo"),
      "transitions" => array("siteEffect" => SITE_EFFECT)
  ),
  IDOL_REFRESH => array(
      "name" => "idolRefresh",
      "description" => clienttranslate('${actplayer} may refresh an assistant'),
      "descriptionmyturn" => clienttranslate('${you} may refresh an assistant'),
      "type" => "activeplayer",
      "possibleactions" => array("idolRefresh", "cancelIdolRefresh", "playCard", "useAssistant", "useIdol", "useGuardPower", "undo"),
      "transitions" => array("siteEffect" => SITE_EFFECT)
  ),
  BUY_ART => array(
      "name" => "buyArt",
      "description" => clienttranslate('${actplayer} must buy an artifact'),
      "descriptionmyturn" => clienttranslate('${you} must buy an artifact'),
      "type" => "activeplayer",
      "possibleactions" => array("", "playCard", "useAssistant", "useIdol", "useGuardPower", "buyArt", "cancelBuy", "undo"),
      "transitions" => array("artWaitArgs" => ART_WAIT_ARGS, "playArt" => ART_EFFECT, "main_action_done" => AFTER_MAIN)
  ),
  BUY_ITEM => array(
      "name" => "buyItem",
      "description" => clienttranslate('${actplayer} must buy an item'),
      "descriptionmyturn" => clienttranslate('${you} must buy an item'),
      "type" => "activeplayer",
      "possibleactions" => array("", "playCard", "useAssistant", "useIdol", "useGuardPower", "buyItem", "cancelBuy", "undo"),
      "transitions" => array("main_action_done" => AFTER_MAIN)
  ),
  ART_WAIT_ARGS => array(
      "name" => "artWaitArgs",
      "type" => "activeplayer",
      "description" => clienttranslate('${actplayer} must evaluate artifact effect'),
      "args" => "activeArt",
      "descriptionmyturn" => clienttranslate('${you} must evaluate artifact effect'),
      "possibleactions" => array("playCard", "useAssistant", "useIdol", "useGuardPower", "pass", "undo", "skipArt"),
      "transitions" => array("playArt" => ART_EFFECT, "skipArt" => AFTER_MAIN)
  ),
  ART_EFFECT => array(
      "name" => "artEffect",
      "type" => "game",
      "description" => clienttranslate('Applying artifact\'s effect...'),
      "transitions" => array("artDone" => ART_DONE, "artExile" => MAY_EXILE, "assistantDiscard" => MUST_DISCARD_FREE, "selectAss" => ART_SELECT_ASS, "activateAss" => ART_ACTIVATE_ASS, "research_bonus" => RESEARCH_BONUS, "earring" => ART_EARRING_SELECT_KEEP, "siteEffect" => SITE_EFFECT, "discardSelect" => ART_SELECT_EXILED, "turn_end" => NEXT_PLAYER, "mustTravel" => MUST_TRAVEL, "mayTravel" => MAY_TRAVEL)
  ),
  ART_SELECT_ASS => array(
      "name" => "artSelectAss",
      "type" => "activeplayer",
      "description" => clienttranslate('${actplayer} must select an assistant'),
      "args" => "activeArt",
      "descriptionmyturn" => clienttranslate('${you} must select an assistant'),
      "possibleactions" => array("playCard", "useAssistant", "useIdol", "useGuardPower", "undo"),
      "transitions" => array("artDone" => ART_DONE, "assistantDiscard" => MUST_DISCARD_FREE, )
  ),
  ART_ACTIVATE_ASS => array(
      "name" => "artActivateAss",
      "type" => "activeplayer",
      "description" => clienttranslate('${actplayer} must activate an assistant'),
      "args" => "activeArt",
      "descriptionmyturn" => clienttranslate('${you} must activate an assistant'),
      "possibleactions" => array("playCard", "useAssistant", "useIdol", "useGuardPower", "undo"),
      "transitions" => array("artDone" => ART_DONE, "assistantDiscard" => MUST_DISCARD, "artWaitArgs" => ART_WAIT_ARGS, "playArt" => ART_EFFECT, "main_action_done" => AFTER_MAIN)
  ),
  ART_EARRING_SELECT_KEEP => array(
      "name" => "artEarringSelectKeep",
      "type" => "activeplayer",
      "description" => clienttranslate('${actplayer} must keep one of the drawn cards'),
      "descriptionmyturn" => clienttranslate('${you} must keep one of the drawn cards'),
      "possibleactions" => array("selectCard", "useAssistant", "useIdol", "useGuardPower", "undo", "selectCard"),
      "transitions" => array("artDone" => ART_DONE, "selectTopdeck" => ART_EARRING_SELECT_TOPDECK)
  ),
  ART_EARRING_SELECT_TOPDECK => array(
      "name" => "artEarringSelectTopdeck",
      "type" => "activeplayer",
      "description" => clienttranslate('${actplayer} may return a drawn card to the top of the deck'),
      "descriptionmyturn" => clienttranslate('${you} may return a drawn card to the top of the deck'),
      "possibleactions" => array("selectCard", "useAssistant", "useIdol", "useGuardPower", "undo", "selectCard", "cancelEarring"),
      "transitions" => array("artDone" => ART_DONE)
  ),
  ART_SELECT_EXILED => array(
      "name" => "artSelectDiscard",
      "type" => "activeplayer",
      "description" => clienttranslate('${actplayer} must select an item from the exile'),
      "descriptionmyturn" => clienttranslate('${you} must select an item from the exile'),
      "args" => "exiledItems",
      "possibleactions" => array("selectExileCard", "useAssistant", "useIdol", "useGuardPower", "undo"),
      "transitions" => array("artDone" => ART_DONE)
  ),
  ART_DONE => array(
      "name" => "artDone",
      "type" => "game",
      "transitions" => array("allDone" => AFTER_MAIN, "researchRemains" => RESEARCH_BONUS)
  ),
  RESEARCH_BONUS => array(
      "name" => "researchBonus",
      "description" => clienttranslate('${actplayer} must evaluate research bonuses'),
      "descriptionmyturn" => clienttranslate('${you} must evaluate research bonuses'),
      "type" => "activeplayer",
      "args" => "researchLeft",
      "possibleactions" => array("exile", "gainAssistant", "ugpradeAssistant", "buyArt", "playCard", "useAssistant", "useIdol", "useGuardPower", "useResearchToken",  "undo"),
      "transitions" => array("research_done" => AFTER_MAIN, "artWaitArgs" => ART_WAIT_ARGS, "playArt" => ART_EFFECT, "researchRemains" => RESEARCH_BONUS)
  ),
  AFTER_MAIN => array(
      "name" => "afterMain",
      "description" => clienttranslate('${actplayer} may perform free actions'),
      "descriptionmyturn" => clienttranslate('${you} may perform free actions'),
      "type" => "activeplayer",
      "action" => "stCheckLastPlayer",
      "possibleactions" => array( "playCard", "useAssistant", "useIdol", "useGuardPower", "endTurn", "undo"),
      "transitions" => array("turn_end" => NEXT_PLAYER, "assistantDiscard" => MUST_DISCARD, "researchRemains" => RESEARCH_BONUS) 
      ),

  /*TURN_END => array(
      "name" => "turnEnd",
      "type" => "game",
      "transitions" => array("nextPlayer" => NEXT_PLAYER, "allPassed" => NEXT_ROUND)
  ),*/

  // Final state.
  // Please do not modify (and do not overload action/args methods).
  98 => array(
    "name" => "scoring",
    "type" => "game",
    "transitions" => array("scoringDone" => 99)
  ),
  99 => array(
    "name" => "gameEnd",
    "description" => clienttranslate("End of game"),
    "type" => "manager",
    "action" => "stGameEnd",
    "args" => "argGameEnd"
  )
);



