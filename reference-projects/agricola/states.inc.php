<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * agricola implementation : © Timothée Pecatte <tim.pecatte@gmail.com>, Vincent Toper <vincent.toper@gmail.com>
 *
 * This code has been vproduced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * states.inc.php
 *
 * agricola game states description
 *
 */

$machinestates = [
  // The initial state. Please do not modify.
  ST_GAME_SETUP => [
    'name' => 'gameSetup',
    'description' => '',
    'type' => 'manager',
    'action' => 'stGameSetup',
    'transitions' => ['' => ST_DRAFT],
  ],

  ST_GENERIC_NEXT_PLAYER => [
    'name' => 'genericNextPlayer',
    'action' => 'dummy',
    'type' => 'game',
  ],

  ST_DRAFT => [
    'name' => 'draftCards',
    'description' => '',
    'type' => 'game',
    'action' => 'stDraftGame',
    'transitions' => [
      'noDraft' => ST_BEFORE_START_OF_TURN,
      'startTurn' => ST_BEFORE_START_OF_TURN,
      'draft' => ST_DRAFT_PLAYER,
      'seed' => ST_LOAD_SEED,
    ],
  ],

  ST_LOAD_SEED => [
    'name' => 'loadSeed',
    'description' => clienttranslate('Please enter a valid seed to load the game'),
    'descriptionmyturn' => clienttranslate('Please enter a valid seed to load the game'),
    'type' => 'multipleactiveplayer',
    'possibleactions' => ['actLoadSeed'],
    'transitions' => ['start' => ST_BEFORE_START_OF_TURN],
  ],

  ST_DRAFT_PLAYER => [
    'name' => 'draftPlayers',
    'description' => clienttranslate('(${turn}/${total}) Draft: players are choosing their cards'),
    'descriptionmyturn' => clienttranslate('(${turn}/${total}) Draft: ${you} must choose ${draftChoice}'),
    'type' => 'multipleactiveplayer',
    'action' => 'stDraftPlayers',
    'args' => 'argsDraftPlayers',
    'possibleactions' => ['actDraftAdd', 'actDraftRemove', 'actDraftConfirm', 'actCancelDraft'],
    'transitions' => [
      'apply' => ST_DRAFT_APPLY,
      'done' => ST_BEFORE_START_OF_TURN,
    ],
  ],

  ST_DRAFT_APPLY => [
    'name' => 'applyDraft',
    'description' => '',
    'type' => 'game',
    'action' => 'stApplyDraft',
    'transitions' => [
      'draft' => ST_DRAFT,
      'asyncDraft' => ST_DRAFT_PLAYER,
      'done' => ST_BEFORE_START_OF_TURN,
    ],
  ],

  ST_LIVING_HAND_REFILL => [
    'name' => 'livingHandRefill',
    'description' => clienttranslate('Living Hand: ${actplayer} must refill hand'),
    'descriptionmyturn' => clienttranslate('Living Hand: refill hand up to at least 4 occupations and 4 minor improvements'),
    'type' => 'activeplayer',
    'args' => 'argsLivingHandRefill',
    'possibleactions' => ['actLivingHandPick'],
  ],

  ST_LIVING_HAND_PASSING_DECISION => [
    'name' => 'livingHandPassingDecision',
    'description' => clienttranslate('Living Hand: ${actplayer} must decide whether to keep ${card_name}'),
    'descriptionmyturn' => clienttranslate('Living Hand: ${you} must decide whether to keep ${card_name}'),
    'type' => 'activeplayer',
    'args' => 'argsLivingHandPassingDecision',
    'possibleactions' => ['actLivingHandPassDecision'],
  ],

  ST_SOLO_ACTION_CARD_MODE => [
    'name' => 'soloActionCardMode',
    'description' => clienttranslate('${actplayer} must choose how action cards are revealed'),
    'descriptionmyturn' => clienttranslate('${you} must choose how action cards are revealed each round'),
    'type' => 'activeplayer',
    'possibleactions' => ['actSoloActionCardMode'],
    'transitions' => ['' => ST_BEFORE_START_OF_TURN],
  ],

  ST_CHOOSE_ACTION_CARD => [
    'name' => 'chooseActionCard',
    'description' => clienttranslate('${actplayer} must choose the next action card'),
    'descriptionmyturn' => clienttranslate('${you} must choose which action card is revealed this round'),
    'type' => 'activeplayer',
    'args' => 'argsChooseActionCard',
    'possibleactions' => ['actChooseActionCard'],
  ],

  ST_BEFORE_START_OF_TURN => [
    'name' => 'beforeStartOfTurn',
    'description' => '',
    'type' => 'game',
    'action' => 'stBeforeStartOfTurn',
    'updateGameProgression' => true,
  ],

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
  ],

  ST_CONFIRM_PARTIAL_TURN => [
    'name' => 'confirmPartialTurn',
    'description' => clienttranslate('${actplayer} must switch to ${player_name} for ${card_name}'),
    'descriptionmyturn' => clienttranslate(
      '${you} must switch to ${player_name} for ${card_name}. You will not be able to restart turn'
    ),
    'descriptionnocard' => clienttranslate('${actplayer} must switch to ${player_name}'),
    'descriptionmyturnnocard' => clienttranslate(
      '${you} must switch to ${player_name}. You will not be able to restart turn'
    ),
    'type' => 'activeplayer',
    'args' => 'argsConfirmPartialTurn',
    // 'action' => 'stConfirmPartialTurn',
    'possibleactions' => ['actConfirmPartialTurn', 'actRestart'],
  ],

  ST_RESOLVE_CHOICE => [
    'name' => 'resolveChoice',
    'description' => clienttranslate('${actplayer} must choose an action'),
    'descriptionmyturn' => clienttranslate('${you} must choose an action'),
    'type' => 'activeplayer',
    'args' => 'argsResolveChoice',
    'action' => 'stResolveChoice',
    'possibleactions' => ['actChooseAction', 'actRestart'],
    'transitions' => [],
  ],

  ST_IMPOSSIBLE_MANDATORY_ACTION => [
    'name' => 'impossibleAction',
    'description' => clienttranslate(
      '${actplayer} can\'t take the mandatory action and must restart their turn or exchange/cook'
    ),
    'descriptionmyturn' => clienttranslate(
      '${you} can\'t take the mandatory action. Restart your turn or exchange/cook to make it possible'
    ),
    'type' => 'activeplayer',
    'action' => 'stImpossibleAction',
    'args' => 'argsImpossibleAction',
    'possibleactions' => ['actRestart', 'actAbandonStuckAction'],
  ],

  ST_PREPARATION => [
    'name' => 'preparation',
    'description' => '',
    'type' => 'game',
    'action' => 'stPreparation',
    'updateGameProgression' => true,
  ],

  ST_LABOR => [
    'name' => 'labor',
    'description' => '',
    'type' => 'game',
    'action' => 'stLabor',
    'transitions' => [
      'done' => ST_END_WORK_PHASE,
    ],
  ],

  ST_PLACE_FARMER => [
    'name' => 'placeFarmer',
    'description' => clienttranslate('${actplayer} must place a person'),
    'descriptionmyturn' => clienttranslate('${you} must place a person'),
    'descriptionskippable' => clienttranslate('${actplayer} may place a person'),
    'descriptionmyturnskippable' => clienttranslate('${you} may place a person'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'action' => 'stAtomicAction',
    'possibleactions' => ['actPlaceFarmer', 'actPassOptionalAction', 'actRestart'],
    'transitions' => [],
  ],

  ST_GAIN => [
    'name' => 'gainResources',
    'type' => 'game',
    'action' => 'stAtomicAction',
  ],

  ST_COLLECT => [
    'name' => 'collectResources',
    'type' => 'game',
    'action' => 'stAtomicAction',
  ],

  ST_RECEIVE => [
    'name' => 'receiveResources',
    'type' => 'game',
    'action' => 'stAtomicAction',
  ],

  ST_PLACE_FUTURE_MEEPLES => [
    'name' => 'placeFutureMeeples',
    'type' => 'game',
    'action' => 'stAtomicAction',
  ],

  ST_PLACE_MEEPLES_FROM_SUPPLY => [
    'name' => 'placeMeeplesFromSupply',
    'type' => 'game',
    'action' => 'stAtomicAction',
  ],

  ST_REAP => [
    'name' => 'reapCrops',
    'type' => 'game',
    'action' => 'stAtomicAction',
  ],

  ST_FENCING => [
    'name' => 'fencing',
    'description' => clienttranslate('${actplayer} must build fence(s)'),
    'descriptionmyturn' => clienttranslate('${you} may construct up to ${max} fence(s)'),
    'descriptionskippable' => clienttranslate('${actplayer} may build fence(s)'),
    'descriptionmyturnskippable' => clienttranslate('${you} may construct up to ${max} fence(s)'),

    'descriptionmyturnnomore' => clienttranslate(
      '${you} may construct up to ${max} fence(s) (quantity of fences in your reserve)'
    ),

    'descriptionminipasture' => clienttranslate('${actplayer} must fence a farmyard space'),
    'descriptionmyturnminipasture' => clienttranslate('${you} must fence a farmyard space'),
    'descriptionfieldfences' => clienttranslate('${actplayer} must build fence(s). Fences adjacent to fields are free'),
    'descriptionmyturnfieldfences' => clienttranslate('${you} must build fence(s). Fences adjacent to fields are free'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actFence', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_PAY => [
    'name' => 'payResources',
    'description' => clienttranslate('${actplayer} must choose how to pay for ${source}'),
    'descriptionmyturn' => clienttranslate('${you} must choose how to pay for ${source}'),
    'descriptionauto' => clienttranslate('${actplayer} pays for ${source}'),
    'descriptionmyturnauto' => clienttranslate('${you} pay for ${source}'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actPay', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_FIRSTPLAYER => [
    'name' => 'firstPlayer',
    'description' => '',
    'action' => 'stAtomicAction',
    'type' => 'game',
  ],

  ST_OCCUPATION => [
    'name' => 'occupation',
    'description' => clienttranslate('${actplayer} must play an occupation'),
    'descriptionmyturn' => clienttranslate('${you} must play an occupation'),
    'descriptionskippable' => clienttranslate('${actplayer} may play an occupation'),
    'descriptionmyturnskippable' => clienttranslate('${you} may play an occupation'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actOccupation', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_PLOW => [
    'name' => 'plow',
    'description' => clienttranslate('${actplayer} must plow a field'),
    'descriptionmyturn' => clienttranslate('${you} must plow a field'),
    'descriptionskippable' => clienttranslate('${actplayer} may plow a field'),
    'descriptionmyturnskippable' => clienttranslate('${you} may plow a field'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actPlow', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_CONSTRUCT => [
    'name' => 'construct',
    'description' => clienttranslate('${actplayer} must build room(s)'),
    'descriptionmyturn' => clienttranslate('${you} must build up to ${max} room(s)'),
    'descriptionskippable' => clienttranslate('${actplayer} may build room(s)'),
    'descriptionmyturnskippable' => clienttranslate('${you} may build up to ${max} room(s)'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actConstruct', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_SOW => [
    'name' => 'sow',
    'description' => clienttranslate('${actplayer} must sow their fields'),
    'descriptionmyturn' => clienttranslate('${you} must sow your field(s)'),
    'descriptionskippable' => clienttranslate('${actplayer} may sow their fields'),
    'descriptionmyturnskippable' => clienttranslate('${you} may sow your field(s)'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actSow', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_STABLE => [
    'name' => 'stables',
    'description' => clienttranslate('${actplayer} must build stable(s)'),
    'descriptionmyturn' => clienttranslate('${you} must build up to ${max} stable(s)'),
    'descriptionskippable' => clienttranslate('${actplayer} may build stable(s)'),
    'descriptionmyturnskippable' => clienttranslate('${you} may build up to ${max} stable(s)'),
    'descriptionmyturnnomore' => clienttranslate(
      '${you} may construct up to ${max} stable(s) (quantity of stables in your reserve)'
    ),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actStables', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_RENOVATION => [
    'name' => 'renovation',
    'description' => clienttranslate('${actplayer} must renovate'),
    'descriptionmyturn' => clienttranslate('${you} must renovate'),
    'descriptionskippable' => clienttranslate('${actplayer} may renovate'),
    'descriptionmyturnskippable' => clienttranslate('${you} may renovate'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actRenovation', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_IMPROVEMENT => [
    'name' => 'improvement',
    'description' => clienttranslate('${actplayer} must play a ${strTypes} improvement'),
    'descriptionmyturn' => clienttranslate('${you} must play a ${strTypes} improvement'),
    'descriptionskippable' => clienttranslate('${actplayer} may play a ${strTypes} improvement'),
    'descriptionmyturnskippable' => clienttranslate('${you} may play a ${strTypes} improvement'),
    'args' => 'argsAtomicAction',
    'action' => 'stAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actImprovement', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_REORGANIZE => [
    'name' => 'reorganize',
    'description' => clienttranslate(
      '${actplayer} must reorganize their animals inside their pastures, rooms and stables'
    ),
    'descriptionmyturn' => clienttranslate('You must reorganize your animals inside your pastures, rooms and stables'),
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actReorganize', 'actRestart'],
  ],

  ST_WISHCHILDREN => [
    'name' => 'wishForChildren',
    'description' => '',
    'descriptionmyturn' => '',
    'action' => 'stAtomicAction',
    'type' => 'game',
  ],

  ST_EXCHANGE => [
    'name' => 'exchange',
    'description' => clienttranslate('${actplayer} may exchange resources'),
    'descriptionmyturn' => clienttranslate('You may exchange resources'),
    'descriptionbread' => clienttranslate('${actplayer} must bake bread'),
    'descriptionmyturnbread' => clienttranslate('You must bake bread'),
    'descriptionbreadskippable' => clienttranslate('${actplayer} may bake bread'),
    'descriptionmyturnbreadskippable' => clienttranslate('You may bake bread'),
    'descriptionharvest' => clienttranslate('${actplayer} may exchange resources before feeding their family'),
    'descriptionmyturnharvest' => clienttranslate('You may exchange resources before feeding your family'),
    'descriptioncook' => clienttranslate('${actplayer} is cooking their animals'),
    'descriptionmyturncook' => clienttranslate('You are cooking your animals'),
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'action' => 'stAtomicAction',
    'possibleactions' => ['actExchange', 'actPassOptionalAction', 'actRestart'],
  ],

  ST_ACTIVATE_CARD => [
    'name' => 'activateCard',
    'description' => '',
    'type' => 'game',
    'action' => 'stAtomicAction',
    'transitions' => [],
  ],

  ST_SPECIAL_EFFECT => [
    'name' => 'specialEffect',
    'description' => '',
    'descriptionmyturn' => '',
    'action' => 'stAtomicAction',
    'args' => 'argsAtomicAction',
    'type' => 'activeplayer',
    'possibleactions' => ['actPassOptionalAction', 'actRestart', 
        'actA3', 'actA58', 'actA70', 'actA71', 'actA84', 'actA102', 'actA112', 'actA136',
        'actB83', 'actB85', 'actB115', 'actB146', 'actB157', 'actB165',
        'actC18', 'actC57', 'actC63', 'actC69','actC104', 'actC133', 'actC146',
        'actD51', 'actD70', 'actD71', 'actD72', 'actD93', 'actD102', 'actD132', 'actD137', 
        'actE4', 'actE5', 'actE10', 'actE22', 'actE71', 'actE73', 'actE76', 'actE148', 'actE74', 'actE78', 'actE106', 'actE112', 'actE85'
    ],
  ],

  ST_END_WORK_PHASE => [
    'name' => 'endWorkPhase',
    'description' => '',
    'type' => 'game',
    'action' => 'stEndWorkPhase',
  ],

  ST_START_HARVEST => [
    'name' => 'startHarvest',
    'description' => '',
    'type' => 'game',
    'action' => 'stStartHarvest',
  ],

  ST_HARVEST_FIELD => [
    'name' => 'harvestCrop',
    'description' => '',
    'type' => 'game',
    'action' => 'stHarvestFieldPhase',
  ],

  ST_HARVEST_FEED => [
    'name' => 'harvestFeed',
    'description' => '',
    'type' => 'game',
    'action' => 'stHarvestFeed',
  ],

  ST_HARVEST_BREED => [
    'name' => 'harvestBreed',
    'description' => '',
    'type' => 'game',
    'action' => 'stHarvestBreed',
  ],

  ST_PRE_END_OF_TURN => [
    'name' => 'preEndOfTurn',
    'description' => '',
    'type' => 'game',
    'action' => 'stPreEndOfTurn',
    'transitions' => [
      'harvest' => ST_START_HARVEST,
      'end' => ST_END_OF_TURN,
    ],
  ],

  // remove by end of year
  ST_PRE_END_OF_TURN2 => [
    'name' => 'preEndOfTurn',
    'description' => '',
    'type' => 'game',
    'action' => 'stPreEndOfTurn',
    'transitions' => [
      'harvest' => ST_START_HARVEST,
      'end' => ST_END_OF_TURN,
    ],
  ],


  ST_END_OF_TURN => [
    'name' => 'endOfTurn',
    'description' => '',
    'type' => 'game',
    'action' => 'stEndOfTurn',
    'transitions' => [
      'newTurn' => ST_BEFORE_START_OF_TURN,
      'end' => ST_PRE_END_OF_GAME,
    ],
  ],

  ST_PRE_END_OF_GAME => [
    'name' => 'preEndOfGame',
    'type' => 'game',
    'action' => 'stPreEndOfGame',
    'transitions' => ['' => ST_END_GAME],
  ],

    ST_CAMPAIGN_CHOOSE_PERMANENT => [
    'name' => 'campaignChoosePermanent',
    'description' => clienttranslate('Campaign: ${actplayer} is reviewing the game'),
    'descriptionmyturn' => clienttranslate('Campaign review'),
    'type' => 'activeplayer',
    'args' => 'argsChoosePermanent',
    'possibleactions' => ['actChoosePermanent', 'actProceed', 'actEndCampaign'],
    'transitions' => ['next' => ST_CAMPAIGN_NEXT_GAME, 'end' => ST_END_GAME],
  ],

  ST_CAMPAIGN_NEXT_GAME => [
    'name' => 'campaignNextGame',
    'description' => '',
    'type' => 'game',
    'action' => 'stCampaignNextGame',
    'transitions' => ['intro' => ST_CAMPAIGN_NEW_GAME_INTRO],
  ],

  ST_CAMPAIGN_NEW_GAME_INTRO => [
    'name' => 'campaignNewGameIntro',
    'description' => clienttranslate('Campaign: ${actplayer} is about to start the next game'),
    'descriptionmyturn' => clienttranslate('Campaign'),
    'type' => 'activeplayer',
    'args' => 'argsCampaignNewGameIntro',
    'possibleactions' => ['actStartCampaignGame'],
    'transitions' => ['start' => ST_BEFORE_START_OF_TURN],
  ],

  ST_CAMPAIGN_PLAY_PERMANENTS => [
    'name' => 'campaignPlayPermanents',
    'description' => '',
    'type' => 'game',
    'action' => 'stCampaignPlayPermanents',
    'transitions' => [
      'prep' => ST_PREPARATION,
    ],
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

  // DEBUG STATE
  ST_CHECK_COMBOS => [
    'name' => 'checkCombos',
    'type' => 'activeplayer',
    'args' => 'argsCheckCombos',
    'description' => 'Here are all the listening cards',
    'descriptionmyturn' => 'Here are all the listening cards',
  ],
];
