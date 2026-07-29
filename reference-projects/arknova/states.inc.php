<?php

/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * Ark Nova implementation : © Timothée Pecatte <tim.pecatte@gmail.com>, Vincent Toper <vincent.toper@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * states.inc.php
 *
 * Ark Nova game states description
 *
 */

$machinestates = [
  // The initial state. Please do not modify.
  ST_GAME_SETUP => [
    'name' => 'gameSetup',
    'description' => '',
    'type' => 'manager',
    'action' => 'stGameSetup',
    'transitions' => ['' => ST_SETUP_BRANCH],
  ],

  ST_GENERIC_NEXT_PLAYER => [
    'name' => 'genericNextPlayer',
    'type' => 'game',
    'args' => 'argsGenericNoNotify',
  ],

  //////////////////////////////////
  //  ____       _
  // / ___|  ___| |_ _   _ _ __
  // \___ \ / _ \ __| | | | '_ \
  //  ___) |  __/ |_| |_| | |_) |
  // |____/ \___|\__|\__,_| .__/
  //                      |_|
  //////////////////////////////////
  ST_SETUP_BRANCH => [
    'name' => 'setupBranch',
    'description' => '',
    'type' => 'game',
    'action' => 'stSetupBranch',
    'transitions' => ['selection' => ST_INITIAL_SELECTION, 'mapSelection' => ST_INITIAL_MAP_SELECTION, 'cardSelection' => ST_INITIAL_ACTION_CARD_DRAFT],
  ],

  ////////////////////
  //// MAP
  ////
  ST_INITIAL_MAP_SELECTION => [
    'name' => 'initialMapSelection',
    'description' => clienttranslate('Waiting for others to choose the zoo map they want to play'),
    'descriptionmyturn' => clienttranslate('${you} must select the zoo map you want to play'),
    'type' => 'multipleactiveplayer',
    'args' => 'argsInitialMapSelection',
    'possibleactions' => ['actSelectMap'],
    'transitions' => ['done' => ST_INITIAL_SELECTION, 'zombiePass' => ST_INITIAL_SELECTION, 'cardSelection' => ST_INITIAL_ACTION_CARD_DRAFT],
  ],

  ////////////////////
  //// ACTION CARDS
  ////
  ST_INITIAL_ACTION_CARD_DRAFT => [
    'name' => 'initialActionCardsSelection',
    'description' => clienttranslate('Waiting for others to choose the action card they want to keep'),
    'descriptionmyturn' => clienttranslate('${you} must select the action card you want to keep'),
    'type' => 'multipleactiveplayer',
    'args' => 'argsInitialActionCardsSelection',
    'possibleactions' => ['actSelectActionCard', 'actCancelActionCardSelection'],
    'transitions' => ['done' => ST_INITIAL_ACTION_CARD_DRAFT, 'finish' => ST_INITIAL_ACTION_CARD_KEEP,  'zombiePass' => ST_INITIAL_SELECTION],
  ],

  ST_INITIAL_ACTION_CARD_KEEP => [
    'name' => 'initialActionCardsKeep',
    'description' => clienttranslate('Waiting for others to choose the action cards they want to keep'),
    'descriptionmyturn' => clienttranslate('${you} must select the two unique action cards you want to keep'),
    'type' => 'multipleactiveplayer',
    'args' => 'argsInitialActionCardsKeep',
    'possibleactions' => ['actKeepActionCards', 'actCancelActionCardsKeep'],
    'transitions' => ['done' => ST_INITIAL_SELECTION,  'zombiePass' => ST_INITIAL_SELECTION],
  ],

  ////////////////////
  //// CARDS
  ////
  ST_INITIAL_SELECTION => [
    'name' => 'initialSelection',
    'description' => clienttranslate('Waiting for others to choose the cards they want to keep'),
    'descriptionmyturn' => clienttranslate('${you} must select the ${_private.n} cards you want to keep'),
    'type' => 'multipleactiveplayer',
    'args' => 'argsInitialSelection',
    'possibleactions' => ['actSelect', 'actCancelSelection'],
    'transitions' => ['done' => ST_BEFORE_START_OF_TURN],
  ],

  //////////////////////////////
  //  _____
  // |_   _|   _ _ __ _ __
  //   | || | | | '__| '_ \
  //   | || |_| | |  | | | |
  //   |_| \__,_|_|  |_| |_|
  //////////////////////////////

  ST_BEFORE_START_OF_TURN => [
    'name' => 'beforeStartOfTurn',
    'description' => '',
    'type' => 'game',
    'action' => 'stBeforeStartOfTurn',
    'updateGameProgression' => true,
  ],

  ST_TURNACTION => [
    'name' => 'turnAction',
    'description' => '',
    'type' => 'game',
    'action' => 'stTurnAction',
    'updateGameProgression' => true,
  ],

  ////////////////////////////////////
  //  _____             _
  // | ____|_ __   __ _(_)_ __   ___
  // |  _| | '_ \ / _` | | '_ \ / _ \
  // | |___| | | | (_| | | | | |  __/
  // |_____|_| |_|\__, |_|_| |_|\___|
  //              |___/
  ////////////////////////////////////
  ST_RESOLVE_STACK => [
    'name' => 'resolveStack',
    'type' => 'game',
    'action' => 'stResolveStack',
    'transitions' => [],
  ],

  ST_CONFIRM_TURN => [
    'name' => 'confirmTurn',
    'description' => clienttranslate('${actplayer} must confirm or restart their turn'),
    'descriptionmyturn' => clienttranslate('${you} must confirm or restart your turn'),
    'type' => 'activeplayer',
    'args' => 'argsConfirmTurn',
    'action' => 'stConfirmTurn',
    'possibleactions' => ['actConfirmTurn', 'actRestart'],
    'transitions' => ['breakStart' => ST_BREAK_MULTIACTIVE],
  ],

  ST_CONFIRM_PARTIAL_TURN => [
    'name' => 'confirmPartialTurn',
    'description' => clienttranslate('${actplayer} must confirm the switch of player'),
    'descriptionmyturn' => clienttranslate('${you} must confirm the switch of player. You will not be able to restart turn'),
    'type' => 'activeplayer',
    'args' => 'argsConfirmTurn',
    // 'action' => 'stConfirmPartialTurn',
    'possibleactions' => ['actConfirmPartialTurn', 'actRestart'],
  ],

  ST_RESOLVE_CHOICE => [
    'name' => 'resolveChoice',
    'description' => clienttranslate('${actplayer} must choose which effect to resolve'),
    'descriptionmyturn' => clienttranslate('${you} must choose which effect to resolve'),
    'descriptionxor' => clienttranslate('${actplayer} must choose exactly one effect'),
    'descriptionmyturnxor' => clienttranslate('${you} must choose exactly one effect'),
    'type' => 'activeplayer',
    'args' => 'argsResolveChoice',
    'action' => 'stResolveChoice',
    'possibleactions' => ['actChooseAction', 'actRestart'],
    'transitions' => [],
  ],

  ST_IMPOSSIBLE_MANDATORY_ACTION => [
    'name' => 'impossibleAction',
    'description' => clienttranslate('${actplayer} can\'t take the mandatory action and must restart his turn or exchange/cook'),
    'descriptionmyturn' => clienttranslate(
      '${you} can\'t take the mandatory action. Restart your turn or exchange/cook to make it possible'
    ),
    'type' => 'activeplayer',
    'args' => 'argsImpossibleAction',
    'possibleactions' => ['actRestart'],
  ],

  ////////////////////////////////////////////////////////////////////////////
  //     _   _                  _         _        _   _
  //    / \ | |_ ___  _ __ ___ (_) ___   / \   ___| |_(_) ___  _ __  ___
  //   / _ \| __/ _ \| '_ ` _ \| |/ __| / _ \ / __| __| |/ _ \| '_ \/ __|
  //  / ___ \ || (_) | | | | | | | (__ / ___ \ (__| |_| | (_) | | | \__ \
  // /_/   \_\__\___/|_| |_| |_|_|\___/_/   \_\___|\__|_|\___/|_| |_|___/
  //
  ////////////////////////////////////////////////////////////////////////////
  ST_GAIN => [
    'name' => 'gainResources',
    'type' => 'game',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
  ],

  ST_MONEY_INCOME => [
    'name' => 'moneyIncome',
    'type' => 'game',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
  ],

  ST_PAY => [
    'name' => 'pay',
    'type' => 'game',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
  ],

  ST_ACTIVATE_CARD => [
    'name' => 'activateCard',
    'args' => 'argsAtomicAction',
    'type' => 'game',
    'action' => 'stAtomicAction',
  ],

  ST_TAKE_BONUS => [
    'name' => 'takeBonus',
    'type' => 'game',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
  ],

  ST_SPECIAL_EFFECT => [
    'name' => 'specialEffect',
    'description' => '',
    'descriptionmyturn' => '',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actPassOptionalAction', 'actRestart'],
  ],

  ST_UPGRADE_CARD => [
    'name' => 'upgradeCard',
    'description' => clienttranslate('${actplayer} must upgrade an action card'),
    'descriptionmyturn' => clienttranslate('${you} must upgrade an action card'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actUpgradeCard', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_CHOOSE_ACTION_CARD => [
    'name' => 'chooseActionCard',
    'description' => clienttranslate('${actplayer} must choose an action card'),
    'descriptionmyturn' => clienttranslate('${you} must choose an action card'),
    'descriptionaction' => clienttranslate('${actplayer} may use the action card ${type}'),
    'descriptionmyturnaction' => clienttranslate('${you} may use the action card ${type}'),
    'descriptionhypnosis' => clienttranslate('Hypnosis: ${actplayer} must choose an action card'),
    'descriptionmyturnhypnosis' => clienttranslate('Hypnosis: ${you} must choose an action card'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actChooseActionCard', 'actT1Effect', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_CLEANUP => [
    'name' => 'cleanup',
    'description' => '',
    'descriptionmyturn' => '',
    'action' => 'stAtomicAction',
    'type' => 'game',
  ],

  ST_DISCARD_SCORING => [
    'name' => 'discardScoring',
    'type' => 'multipleactiveplayer',
    'description' => clienttranslate('All players must discard 1 final scoring card'),
    'descriptionmyturn' => clienttranslate('${you} must discard 1 final scoring card'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'possibleactions' => ['actDiscardScoring'],
    'transitions' => ['next' => ST_RESOLVE_STACK],
  ],

  ST_USE_KEPT_BONUS => [
    'name' => 'useKeptBonus',
    'action' => 'stAtomicAction',
    'type' => 'game',
  ],

  //////////////////////////////////////////////////////////////////
  //     _        _   _                ____              _
  //    / \   ___| |_(_) ___  _ __    / ___|__ _ _ __ __| |___
  //   / _ \ / __| __| |/ _ \| '_ \  | |   / _` | '__/ _` / __|
  //  / ___ \ (__| |_| | (_) | | | | | |__| (_| | | | (_| \__ \
  // /_/   \_\___|\__|_|\___/|_| |_|  \____\__,_|_|  \__,_|___/
  //////////////////////////////////////////////////////////////////
  ST_CARDS => [
    'name' => 'cards',
    'description' => clienttranslate(
      '<ACTION-CARDS>${strength_icon} ${actplayer} must take ${n} cards from ${source} ${discard}'
    ),
    'descriptionmyturn' => clienttranslate(
      '<ACTION-CARDS>${strength_icon} ${you} must take ${n} cards from ${source} ${discard}'
    ),
    'descriptionsnap' => clienttranslate(
      '<ACTION-CARDS>${strength_icon} ${actplayer} must take ${n} cards from ${source} ${discard} or snap ${nSnap} card(s)'
    ),
    'descriptionmyturnsnap' => clienttranslate(
      '<ACTION-CARDS>${strength_icon} ${you} must take ${n} cards from ${source} ${discard} or snap ${nSnap} card(s)'
    ),
    'descriptionsnaponly' => clienttranslate(
      '<ACTION-CARDS>${strength_icon} ${actplayer} may snap one card'
    ),
    'descriptionmyturnsnaponly' => clienttranslate(
      '<ACTION-CARDS>${strength_icon} ${you} may snap one card'
    ),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actDrawCards', 'actTakeCard', 'actSnapCard', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_DISCARD => [
    'name' => 'discard',
    'description' => clienttranslate('${actplayer} must discard ${n} card(s)'),
    'descriptionmyturn' => clienttranslate('${you} must discard ${n} card(s)'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actDiscard', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_BUILD => [
    'name' => 'build',
    'description' => clienttranslate(
      '<ACTION-BUILD>${strength_icon} ${actplayer} must build a building of size at most ${maxSize}'
    ),
    'descriptionmyturn' => clienttranslate(
      '<ACTION-BUILD>${strength_icon} ${you} must build a building of size at most ${maxSize}'
    ),
    'descriptionskippable' => clienttranslate(
      '<ACTION-BUILD>${strength_icon} ${actplayer} may build a building of size at most ${maxSize}'
    ),
    'descriptionmyturnskippable' => clienttranslate(
      '<ACTION-BUILD>${strength_icon} ${you} may build a building of size at most ${maxSize}'
    ),
    'descriptionfree' => clienttranslate('<ACTION-BUILD>${actplayer} may build a building for free'),
    'descriptionmyturnfree' => clienttranslate('<ACTION-BUILD>${you} may build a building for free'),
    'descriptionunique' => clienttranslate('<ACTION-BUILD>${actplayer} must build the unique building'),
    'descriptionmyturnunique' => clienttranslate('<ACTION-BUILD>${you} must build the unique building'),
    'descriptionengineer' => clienttranslate(
      '<ACTION-BUILD>${strength_icon} ${actplayer} may build a building of size at most ${maxSize} and/or use engineer\'s power'
    ),
    'descriptionmyturnengineer' => clienttranslate(
      '<ACTION-BUILD>${strength_icon} ${actplayer} may build a building of size at most ${maxSize} and/or use engineer\'s power'
    ),

    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actBuild', 'actPassBuild', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_MOVE_ANIMALS => [
    'name' => 'moveAnimals',
    'description' => clienttranslate('${actplayer} can move animals to the ${specialEnclosure}'),
    'descriptionmyturn' => clienttranslate('${you} can move animals to the ${specialEnclosure}'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actMoveAnimals', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_ANIMALS => [
    'name' => 'animals',
    'description' => clienttranslate('<ACTION-ANIMALS>${strength_icon} ${actplayer} must play one card from ${source} ${count}'),
    'descriptionmyturn' => clienttranslate('<ACTION-ANIMALS>${strength_icon} ${you} must play one card from ${source} ${count}'),
    'descriptionskippable' => clienttranslate(
      '<ACTION-ANIMALS>${strength_icon} ${actplayer} may play one card from ${source} ${count}'
    ),
    'descriptionmyturnskippable' => clienttranslate(
      '<ACTION-ANIMALS>${strength_icon} ${you} may play one card from ${source} ${count}'
    ),
    'descriptionwazaSmallskippable' => clienttranslate(
      '<ACTION-ANIMALS>${strength_icon} ${actplayer} may play one small animal from ${source} (Waza Small Animal Program)'
    ),
    'descriptionmyturnwazaSmallskippable' => clienttranslate(
      '<ACTION-ANIMALS>${strength_icon} ${you} may play one small animal from ${source} (Waza Small Animal Program)'
    ),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actAnimals', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_ASSOCIATION => [
    'name' => 'association',
    'description' => clienttranslate('<ACTION-ASSOCIATION>${strength_icon} ${actplayer} must perform an association task'),
    'descriptionmyturn' => clienttranslate('<ACTION-ASSOCIATION>${strength_icon} ${you} must perform an association task'),
    'descriptionskippable' => clienttranslate(
      '<ACTION-ASSOCIATION>${strength_icon} ${actplayer} may perform an association task (<STRENGTH:${strengthLeft}> left)'
    ),
    'descriptionmyturnskippable' => clienttranslate(
      '<ACTION-ASSOCIATION>${strength_icon} ${you} may perform an association task (<STRENGTH:${strengthLeft}> left)'
    ),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actAssociation', 'actConservationProject', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_RELEASE => [
    'name' => 'release',
    'description' => clienttranslate('${actplayer} must select an enclosure to release ${card_name}'),
    'descriptionmyturn' => clienttranslate('${you} must select an enclosure to release ${card_name}'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'action' => 'stAtomicAction',
    'possibleactions' => ['actRelease', 'actRestart'],
  ],

  ST_SPONSORS => [
    'name' => 'sponsors',
    'description' => clienttranslate('<ACTION-SPONSORS>${strength_icon} ${actplayer} must play one sponsor card from ${source}'),
    'descriptionmyturn' => clienttranslate('<ACTION-SPONSORS>${strength_icon} ${you} must play one sponsor card from ${source}'),
    'descriptioncanBreakForMoney' => clienttranslate(
      '<ACTION-SPONSORS>${strength_icon} ${actplayer} must play one sponsor card from ${source} or break for money'
    ),
    'descriptionmyturncanBreakForMoney' => clienttranslate(
      '<ACTION-SPONSORS>${strength_icon} ${you} must play one sponsor card from ${source} or break for money'
    ),
    'descriptionskippable' => clienttranslate(
      '<ACTION-SPONSORS>${strength_icon} ${actplayer} may play one card from ${source} (<STRENGTH:${strengthLeft}> left)'
    ),
    'descriptionmyturnskippable' => clienttranslate(
      '<ACTION-SPONSORS>${strength_icon} ${you} may play one card from ${source} (<STRENGTH:${strengthLeft}> left)'
    ),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actSponsors', 'actBreakForMoney', 'actPassOptionalAction', 'actRestart'],
  ],

  //////////////////////////////////////////////////////////////////////////////
  //     _          _                 _       _____  __  __           _
  //    / \   _ __ (_)_ __ ___   __ _| |___  | ____|/ _|/ _| ___  ___| |_ ___
  //   / _ \ | '_ \| | '_ ` _ \ / _` | / __| |  _| | |_| |_ / _ \/ __| __/ __|
  //  / ___ \| | | | | | | | | | (_| | \__ \ | |___|  _|  _|  __/ (__| |_\__ \
  // /_/   \_\_| |_|_|_| |_| |_|\__,_|_|___/ |_____|_| |_|  \___|\___|\__|___/
  //////////////////////////////////////////////////////////////////////////////

  ST_TAKE_IN_RANGE_OR_DECK => [
    'name' => 'takeInRange',
    'description' => clienttranslate('${actplayer} must take 1 card from display in reputation range ${count}'),
    'descriptionmyturn' => clienttranslate('${you} must take 1 card from display in reputation range ${count}'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actTakeInRange', 'actDrawCard', 'actRestart'],
  ],

  ST_SPRINT => [
    'name' => 'effectSprint',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'game',
  ],

  ST_SPONSOR_MAGNET => [
    'name' => 'effectSponsorMagnet',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'game',
  ],

  ST_SEA_ANIMAL_MAGNET => [
    'name' => 'effectSeaAnimalMagnet',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'game',
  ],

  ST_ANIMAL_MAGNET => [
    'name' => 'effectAnimalMagnet',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'game',
  ],

  ST_HUNTER => [
    'name' => 'effectHunter',
    'description' => clienttranslate('Hunter ${n}: ${actplayer} must keep 1 Animal card from the ${n} drawn cards'),
    'descriptionmyturn' => clienttranslate('Hunter ${n}: ${you} must keep 1 Animal card from the ${n} drawn cards'),
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actHunter', 'actRestart'],
  ],

  ST_JUMPING => [
    'name' => 'effectJumping',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'game',
  ],

  ST_PERCEPTION => [
    'name' => 'effectPerception',
    'description' => clienttranslate('Perception ${n}: ${actplayer} must keep ${m} cards from the ${n} drawn cards'),
    'descriptionmyturn' => clienttranslate('Perception ${n}: ${you} must keep ${m} cards from the ${n} drawn cards'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actPerception', 'actRestart'],
  ],

  ST_PACK => [
    'name' => 'effectPack',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'game',
  ],

  ST_INVENTIVE => [
    'name' => 'effectInventive',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'game',
  ],

  ST_PETTING_ZOO_ANIMAL => [
    'name' => 'effectPettingZooAnimal',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'game',
  ],

  ST_FULL_THROATED => [
    'name' => 'effectFullThroated',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'game',
  ],

  ST_SUNBATHING => [
    'name' => 'effectSunbathing',
    'description' => clienttranslate('Sunbathing: ${actplayer} can sell up to ${n} card(s) for <MONEY:4> each'),
    'descriptionmyturn' => clienttranslate('Sunbathing: ${you} can sell up to ${n} card(s) for <MONEY:4> each'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actSunbathing', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_MAP4 => [
    'name' => 'map4Effect',
    'description' => clienttranslate('Map 4 effect: ${actplayer} can sell 1 card for <MONEY:3>'),
    'descriptionmyturn' => clienttranslate('Map 4 effect: ${you} can sell 1 card for <MONEY:3>'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actMap4', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_POUCH => [
    'name' => 'effectPouch',
    'description' => clienttranslate('Pouch: ${actplayer} may place up to ${n} card(s) under ${source} for <APPEAL:2> each'),
    'descriptionmyturn' => clienttranslate('Pouch: ${you} may place up to ${n} card(s) under ${source} for <APPEAL:2> each'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actPouch', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_DIGGING => [
    'name' => 'effectDigging',
    'description' => clienttranslate(
      'Digging: ${actplayer} can discard 1 card from the display and replenish OR discard 1 card from hand to draw 1 card. (${n} remaining)'
    ),
    'descriptionmyturn' => clienttranslate(
      'Digging: ${you} can discard 1 card from the display and replenish OR discard 1 card from hand to draw 1 card. (${n} remaining)'
    ),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actDigging', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_SNAPPING => [
    'name' => 'effectSnapping',
    'description' => clienttranslate('Snapping: ${actplayer} must snap one card (${n} remaining)'),
    'descriptionmyturn' => clienttranslate('Snapping: ${you} must snap one card (${n} remaining)'),
    'descriptionswazaSmall' => clienttranslate('${actplayer} must snap one small animal card'),
    'descriptionmyturnwazaSmall' => clienttranslate('${you} must snap one small animal card'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actSnapCard', 'actPassOptionalAction', 'actReplenish', 'actRestart'],
  ],

  ST_SCAVENGING => [
    'name' => 'effectScavenging',
    'description' => clienttranslate('Scavenging: ${actplayer} must pick one card to keep'),
    'descriptionmyturn' => clienttranslate('Scavenging: ${you} must pick one card to keep'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actScavenging', 'actRestart'],
  ],

  ST_POSTURING => [
    'name' => 'effectPosturing',
    'description' => clienttranslate('Posturing: ${actplayer} may place a pavilion or a kiosk for free (${n} remaining)'),
    'descriptionmyturn' => clienttranslate('Posturing: ${you} may place a pavilion or a kiosk for free (${n} remaining)'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actBuild', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_PEACOCKING => [
    'name' => 'effectPeacocking',
    'description' => clienttranslate('Peacocking: ${actplayer} may place a Large Bird Aviairy for free'),
    'descriptionmyturn' => clienttranslate('Peacocking: ${you} may place a Large Bird Aviairy for free'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actBuild', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_ICONIC_ANIMAL => [
    'name' => 'effectIconicAnimal',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'game',
  ],

  ST_RESISTANCE => [
    'name' => 'effectResistance',
    'description' => clienttranslate('Resistance: ${actplayer} must keep one final scoring card'),
    'descriptionmyturn' => clienttranslate('Resistance: ${you} must keep one final scoring card'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actResistance', 'actRestart'],
  ],

  ST_ASSERTION => [
    'name' => 'effectAssertion',
    'description' => clienttranslate('Assertion: ${actplayer} must keep one base project card'),
    'descriptionmyturn' => clienttranslate('Assertion: ${you} must keep one base project card'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actAssertion', 'actRestart'],
  ],

  ST_DOMINANCE => [
    'name' => 'effectDominance',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'game',
  ],

  ST_CLEVER => [
    'name' => 'effectClever',
    'description' => clienttranslate('Clever: ${actplayer} may move 1 action card to <STRENGTH:1>'),
    'descriptionmyturn' => clienttranslate('Clever: ${you} may move 1 action card to <STRENGTH:1>'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actClever', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_BOOST => [
    'name' => 'effectBoost',
    'description' => clienttranslate(
      'Boost: ${actplayer} may place the ${card_type} action card to <STRENGTH:1> or <STRENGTH:5>'
    ),
    'descriptionmyturn' => clienttranslate(
      'Boost: ${you} may place the ${card_type} action card  to <STRENGTH:1> or <STRENGTH:5>'
    ),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actBoost', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_DETERMINATION => [
    'name' => 'effectDetermination',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'game',
  ],

  ST_CONSTRICTION => [
    'name' => 'effectConstriction',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'game',
  ],

  ST_VENOM => [
    'name' => 'effectVenom',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'game',
  ],
  ST_VENOM_PAY => [
    'name' => 'effectVenomPay',
    'description' => clienttranslate('${actplayer} must pay for venom'),
    'descriptionmyturn' => clienttranslate('${you} must pay for venom'),
    'possibleactions' => ['actRestart'],
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
  ],

  ST_MULTIPLIER => [
    'name' => 'effectMultiplier',
    'description' => clienttranslate('Multiplier: ${actplayer} must choose a card to put multiplier token'),
    'descriptionmyturn' => clienttranslate('Multiplier: ${you} must choose a card to put multiplier token'),
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actMultiplier', 'actRestart'],
  ],

  ST_ACTION => [
    'name' => 'effectAction',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'game',
  ],

  ST_PILFERING => [
    'name' => 'effectPilfering',
    'description' => clienttranslate('Pilfering: ${actplayer} must choose the player to pilfer'),
    'descriptionmyturn' => clienttranslate('Pilfering: ${you} must choose the player to pilfer'),
    'descriptionmultiple' => clienttranslate('Pilfering: ${actplayer} must choose the player(s) to pilfer'),
    'descriptionmyturnmultiple' => clienttranslate('Pilfering: ${you} must choose the player(s) to pilfer'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'action' => 'stAtomicAction',
    'possibleactions' => ['actPilfering', 'actRestart'],
  ],

  ST_PILFERING_EXECUTE => [
    'name' => 'effectPilferingExecute',
    'description' => clienttranslate(
      'Pilfering: ${actplayer} must choose to give a random card or <MONEY:${n}> to ${player_name}'
    ),
    'descriptionmyturn' => clienttranslate(
      'Pilfering: ${you} must choose to give a random card or <MONEY:${n}> to ${player_name}'
    ),
    'descriptionnomoney' => clienttranslate('Pilfering: ${actplayer} must give a random card to ${player_name}'),
    'descriptionmyturnnomoney' => clienttranslate('Pilfering: ${you} must give a random card to ${player_name}'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'action' => 'stAtomicAction',
    'possibleactions' => ['actPilferingExecute', 'actRestart'],
  ],

  ST_HYPNOSIS => [
    'name' => 'effectHypnosis',
    'description' => clienttranslate('Hypnosis: ${actplayer} must choose the player to hypnotize'),
    'descriptionmyturn' => clienttranslate('Hypnosis: ${you} must choose the player to hypnotize'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'action' => 'stAtomicAction',
    'possibleactions' => ['actHypnosis', 'actRestart'],
  ],

  ST_MAP8 => [
    'name' => 'effectMap8',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'game',
  ],

  ST_MAP9 => [
    'name' => 'map9Effect',
    'description' => clienttranslate('${actplayer} may choose which continent marker to remove to gain 1 bonus'),
    'descriptionmyturn' => clienttranslate('${you} may choose which continent marker to remove to gain 1 bonus'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actMap9', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_MAP10 => [
    'name' => 'map10Effect',
    'description' => clienttranslate(
      'Map 10 Digging: ${actplayer} can discard 1 card from the display and replenish OR discard 1 card from hand to draw 1 card.'
    ),
    'descriptionmyturn' => clienttranslate(
      'Map 10 Digging: ${you} can discard 1 card from the display and replenish OR discard 1 card from hand to draw 1 card.'
    ),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actMap10', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_MAP13_INCOME => [
    'name' => 'effectMap13',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'game',
  ],

  ST_MAP11_STORE => [
    'name' => 'effectMap11Store',
    'description' => clienttranslate('Map 11 Effect: ${actplayer} must choose a card to store'),
    'descriptionmyturn' => clienttranslate('Map 11 Effect: ${you} must choose a card to store'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actMap11EffectStore', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_MAP11_UNSTORE => [
    'name' => 'effectMap11Unstore',
    'description' => clienttranslate('Map 11 Effect: ${actplayer} must choose a stored card to put back into their hand'),
    'descriptionmyturn' => clienttranslate('Map 11 Effect: ${you} must choose a stored card to put back into your hand'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actMap11EffectUnstore', 'actPassOptionalAction', 'actRestart'],
  ],


  //////////////////////////////////////
  //// MW

  ST_SEARCH_CARD => [
    'name' => 'searchCard',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'game',
  ],

  ST_MARK => [
    'name' => 'effectMark',
    'description' => clienttranslate('Mark: ${actplayer} must place ${n} token(s) on animal card(s) on the display'),
    'descriptionmyturn' => clienttranslate('Mark: ${you} must place ${n} token(s) on animal card(s) on the display'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'action' => 'stAtomicAction',
    'possibleactions' => ['actMark', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_EXTRA_SHIFT => [
    'name' => 'effectExtraShift',
    'description' => clienttranslate('Extra Shift: ${actplayer} must remove 1 <WORKER> from the Association Board'),
    'descriptionmyturn' => clienttranslate('Extra Shift: ${you} must must remove 1 <WORKER> from the Association Board'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actExtraShift', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_TRADE => [
    'name' => 'effectTrade',
    'description' => clienttranslate('Trade: ${actplayer} may trade 1 <XTOKEN> for <MONEY:5> or vice-versa'),
    'descriptionmyturn' => clienttranslate('Trade: ${you} may trade 1 <XTOKEN> for <MONEY:5> or vice-versa'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actTrade', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_SYMBIOSIS => [
    'name' => 'effectSymbiosis',
    'description' => clienttranslate('Symbiosis: ${actplayer} must select a sea animal to gain 1 effect'),
    'descriptionmyturn' => clienttranslate('Symbiosis: ${you} must select a sea animal to gain 1 effect'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actSymbiosis', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_GLIDE => [
    'name' => 'effectGlide',
    'description' => clienttranslate('Glide: ${actplayer} may discard up to ${n} card(s) from their hand to gain effects for each <SEAANIMAL>'),
    'descriptionmyturn' => clienttranslate('Glide: ${you} may discard up to ${n} card(s) from your hand to gain effects for each <SEAANIMAL>'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actGlide', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_SHARK_ATTACK => [
    'name' => 'effectSharkAttack',
    'description' => clienttranslate('Shark Attack: ${actplayer} may discard up to ${n} card(s) in reputation range to gain <APPEAL>'),
    'descriptionmyturn' => clienttranslate('Shark Attack: ${you} may discard up to ${n} card(s) in reputation range to gain <APPEAL>'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actSharkAttack', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_CUT_DOWN => [
    'name' => 'effectCutDown',
    'description' => clienttranslate('Cut Down: ${actplayer} may remove 1 empty standard enclosure and gain its cost'),
    'descriptionmyturn' => clienttranslate('Cut Down: ${you} may remove 1 empty standard enclosure and gain its cost'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actCutDown', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_CAMOUFLAGE => [
    'name' => 'effectCamouflage',
    'type' => 'game',
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
  ],

  ST_GAIN_MARKED => [
    'name' => 'gainResources',
    'type' => 'game',
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
  ],

  ST_SCUBA_DIVE => [
    'name' => 'effectScubaDive',
    'description' => clienttranslate('Scuba Dive ${n}: ${actplayer} must keep 1 Sponsor card from the ${n} drawn cards'),
    'descriptionmyturn' => clienttranslate('Scuba Dive ${n}: ${you} must keep 1 Sponsor card from the ${n} drawn cards'),
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actScubaDive', 'actRestart'],
  ],

  ST_ADAPT => [
    'name' => 'effectAdapt',
    'description' => clienttranslate('Adapt: ${actplayer} must discard ${n} final scoring card(s)'),
    'descriptionmyturn' => clienttranslate('Adapt: ${you} must discard ${n} final scoring card(s)'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actAdapt', 'actRestart'],
  ],

  ST_MONKEY_GANG => [
    'name' => 'monkeyGang',
    'type' => 'game',
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
  ],

  ST_ANIMALS2_HUNTER => [
    'name' => 'animals2Hunter',
    'type' => 'game',
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
  ],

  ST_ANIMALS3_PAYGAIN => [
    'name' => 'animals3PayGain',
    'type' => 'game',
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
  ],

  ST_SPONSORS_DISCARD_CARD_GET_BONUS => [
    'name' => 'sponsorsDiscardCardGetBonus',
    'description' => '',
    'descriptionmyturn' => '',
    'description3-1' => clienttranslate('Sponsors 3: ${actplayer} can discard 1 Sponsor card to gain <MONEY:4>'),
    'descriptionmyturn3-1' => clienttranslate('Sponsors 3: ${you} can discard 1 Sponsor card to gain <MONEY:4>'),
    'description3-2' => clienttranslate('Sponsors 3: ${actplayer} can discard 1 card to gain <MONEY:4> or to <STRENGTH:+2>'),
    'descriptionmyturn3-2' => clienttranslate('Sponsors 3: ${you} can discard 1 card to gain <MONEY:4> or to <STRENGTH:+2>'),
    'description3-2bis' => clienttranslate('Sponsors 3: ${actplayer} can discard 1 card to gain <MONEY:4>'),
    'descriptionmyturn3-2bis' => clienttranslate('Sponsors 3: ${you} can discard 1 card to gain <MONEY:4>'),
    'description4-1' => clienttranslate('Sponsors 4: ${actplayer} can discard 1 Sponsor card to snap one Sponsor card'),
    'descriptionmyturn4-1' => clienttranslate('Sponsors 3: ${you} can discard 1 Sponsor card to snap one Sponsor card'),
    'description4-2' => clienttranslate('Sponsors 3: ${actplayer} can discard 1 card to play a Sponsor card for money'),
    'descriptionmyturn4-2' => clienttranslate('Sponsors 3: ${you} can discard 1 card to play a Sponsor card for money'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actSponsorsDiscardCardGetBonus', 'actPassOptionalAction', 'actRestart'],
  ],


  //////////////////////////////////////////////
  //  ____
  // | __ )  ___  _ __  _   _ ___  ___  ___
  // |  _ \ / _ \| '_ \| | | / __|/ _ \/ __|
  // | |_) | (_) | | | | |_| \__ \  __/\__ \
  // |____/ \___/|_| |_|\__,_|___/\___||___/
  //////////////////////////////////////////////
  ST_GAIN_UNIVERSITY => [
    'name' => 'gainUniversity',
    'description' => clienttranslate('${actplayer} must choose the university they want to take'),
    'descriptionmyturn' => clienttranslate('${you} must choose the university you want to take'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'action' => 'stAtomicAction',
    'possibleactions' => ['actGainUniversity', 'actRestart', 'actPassOptionalAction'],
  ],

  ST_GAIN_PARTNER_ZOO => [
    'name' => 'gainPartnerZoo',
    'description' => clienttranslate('${actplayer} must choose the partner zoo they want to take'),
    'descriptionmyturn' => clienttranslate('${you} must choose the partner zoo you want to take'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'action' => 'stAtomicAction',
    'possibleactions' => ['actGainPartnerZoo', 'actRestart', 'actPassOptionalAction'],
  ],

  ST_BUY_SPONSOR => [
    'name' => 'buySponsor',
    'description' => clienttranslate('${actplayer} may pay money to play one Sponsor from their hand'),
    'descriptionmyturn' => clienttranslate('${you} may pay money to play one Sponsor from your hand'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actBuySponsor', 'actRestart', 'actPassOptionalAction'],
  ],

  ST_WAZA_SPECIAL => [
    'name' => 'wazaSpecial',
    'description' => clienttranslate('${actplayer} must chose to focus on small or large animals'),
    'descriptionmyturn' => clienttranslate('${you} must chose to focus on small or large animals'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actWazaSpecial', 'actRestart'],
  ],

  ST_ARCHEOLOGIST_BONUS => [
    'name' => 'archeologistBonus',
    'type' => 'game',
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
  ],


  ///////////////////////////////
  // MARINE WORLD
  ST_DONATE => [
    'name' => 'donate',
    'type' => 'game',
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
  ],

  ST_EXPEDITION => [
    'name' => 'expedition',
    'description' => clienttranslate('${actplayer} must chose one person sponsor to discard'),
    'descriptionmyturn' => clienttranslate('${you} must chose one person sponsor to discard'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actExpedition', 'actRestart'],
  ],

  ST_SEARCH_PET_DISCARD => [
    'name' => 'searchPetDiscard',
    'description' => clienttranslate('${actplayer} must chose one Petting Zoo Animal in the discard'),
    'descriptionmyturn' => clienttranslate('${you} must chose one Petting Zoo Animal in the discard'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actSearchPetDiscard', 'actPassSearchPetDiscard', 'actRestart'],
  ],

  ST_RECONSTRUCTION_REMOVE => [
    'name' => 'reconstructionRemove',
    'description' => clienttranslate('${actplayer} may choose up to 3 buildings to reposition'),
    'descriptionmyturn' => clienttranslate('${you} may choose up to 3 buildings to reposition'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actReconstructionRemove', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_RECONSTRUCTION_PLACE_BACK => [
    'name' => 'reconstructionPlaceBack',
    'description' => clienttranslate('${actplayer} must reposition the building(s) they removed'),
    'descriptionmyturn' => clienttranslate('${you} must reposition the building(s) you removed'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actReconstructionPlaceBack', 'actRestart'],
  ],

  ST_INCREASE_SIZE => [
    'name' => 'increaseSize',
    'description' => clienttranslate('${actplayer} may increase the size of one regular enclosure by building over it'),
    'descriptionmyturn' => clienttranslate('${you} may increase the size of one regular enclosure by building over it'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actIncreaseSize', 'actPassOptionalAction', 'actRestart'],
  ],


  //////////////////////////
  //// MAP PACK 2

  ST_WAVE => [
    'name' => 'wave',
    'type' => 'game',
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
  ],

  ST_FREE_PERSON_SPONSOR => [
    'name' => 'freePersonSponsor',
    'description' => clienttranslate('${actplayer} may play one person Sponsor from their hand for free'),
    'descriptionmyturn' => clienttranslate('${you} may play one person Sponsor from your hand for free'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actFreePersonSponsor', 'actRestart', 'actPassOptionalAction'],
  ],


  ////////////////////////////////
  //  ____                 _
  // | __ ) _ __ ___  __ _| | __
  // |  _ \| '__/ _ \/ _` | |/ /
  // | |_) | | |  __/ (_| |   <
  // |____/|_|  \___|\__,_|_|\_\
  ////////////////////////////////

  ST_ADVANCE_BREAK => [
    'name' => 'advanceBreak',
    'description' => '',
    'descriptionmyturn' => '',
    'action' => 'stAtomicAction',
    'type' => 'game',
  ],

  ST_BREAK_MULTIACTIVE => [
    'name' => 'multiactiveBreak',
    'type' => 'game',
    'action' => 'stBreakPreCards',
    'transitions' => ['discard' => ST_BREAK_CARDS, 'refill' => ST_BREAK_REFILL],
  ],

  ST_BREAK_CARDS => [
    'name' => 'breakDiscard',
    'type' => 'multipleactiveplayer',
    'description' => clienttranslate('<BREAK> Break: waiting for other players to discard some cards'),
    'descriptionmyturn' => '',
    'args' => 'argsBreakDiscard',
    'possibleactions' => ['actBreakDiscardSelectCards', 'actCancelBreakDiscardSelection'],
    'transitions' => ['done' => ST_BREAK_REFILL, 'zombiePass' => ST_BREAK_REFILL],
  ],

  ST_BREAK_REFILL => [
    'name' => 'breakRefill',
    'type' => 'game',
    'action' => 'stBreakRefill',
    'transitions' => ['' => ST_BREAK_INCOME],
  ],

  ST_BREAK_INCOME => [
    'name' => 'breakIncome',
    'description' => '',
    'type' => 'game',
    'action' => 'stBreakIncome',
    'transitions' => [
      'next' => ST_BREAK_FINISH,
    ],
  ],

  ST_BREAK_FINISH => [
    'name' => 'breakFinish',
    'description' => '',
    'type' => 'game',
    'action' => 'stBreakFinish',
    'transitions' => [
      'next' => ST_BEFORE_START_OF_TURN,
    ],
  ],

  //////////////////////////////////////////////////////////////////
  //  _____           _    ___   __    ____
  // | ____|_ __   __| |  / _ \ / _|  / ___| __ _ _ __ ___   ___
  // |  _| | '_ \ / _` | | | | | |_  | |  _ / _` | '_ ` _ \ / _ \
  // | |___| | | | (_| | | |_| |  _| | |_| | (_| | | | | | |  __/
  // |_____|_| |_|\__,_|  \___/|_|    \____|\__,_|_| |_| |_|\___|
  //////////////////////////////////////////////////////////////////

  ST_PRE_END_OF_GAME => [
    'name' => 'preEndOfGame',
    'type' => 'game',
    'action' => 'stPreEndOfGame',
    'transitions' => ['' => ST_END_GAME],
  ],

  // Final state.
  // Please do not modify (and do not overload action/args methods).
  ST_END_GAME => [
    'name' => 'gameEnd',
    'description' => clienttranslate('End of game'),
    'type' => 'manager',
    'action' => 'stGameEnd',
    'args' => 'argGameEnd',
  ],
];
