<?php
/**
 *------
 * BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
 * agricola implementation : © Timothée Pecatte <tim.pecatte@gmail.com>, Vincent Toper <vincent.toper@gmail.com>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * stats.inc.php
 *
 * agricola game statistics description
 *
 */

require_once 'modules/php/constants.inc.php';

$stats_type = [
  'table' => [
    'woodMax' => [
      'id' => STAT_MAX_WOOD,
      'name' => 'Maximum number of wood meeples out of the box during the game',
      'type' => 'int',
      'display' => 'limited',
    ],
    'clayMax' => [
      'id' => STAT_MAX_CLAY,
      'name' => 'Maximum number of clay meeples out of the box during the game',
      'type' => 'int',
      'display' => 'limited',
    ],
    'stoneMax' => [
      'id' => STAT_MAX_STONE,
      'name' => 'Maximum number of stone meeples out of the box during the game',
      'type' => 'int',
      'display' => 'limited',
    ],
    'reedMax' => [
      'id' => STAT_MAX_REED,
      'name' => 'Maximum number of reed meeples out of the box during the game',
      'type' => 'int',
      'display' => 'limited',
    ],
    'grainMax' => [
      'id' => STAT_MAX_GRAIN,
      'name' => 'Maximum number of grain meeples out of the box during the game',
      'type' => 'int',
      'display' => 'limited',
    ],
    'vegetableMax' => [
      'id' => STAT_MAX_VEGETABLE,
      'name' => 'Maximum number of vegetable meeples out of the box during the game',
      'type' => 'int',
      'display' => 'limited',
    ],
    'sheepMax' => [
      'id' => STAT_MAX_SHEEP,
      'name' => 'Maximum number of sheep meeples out of the box during the game',
      'type' => 'int',
      'display' => 'limited',
    ],
    'pigMax' => [
      'id' => STAT_MAX_PIG,
      'name' => 'Maximum number of wild boar meeples out of the box during the game',
      'type' => 'int',
      'display' => 'limited',
    ],
    'cattleMax' => [
      'id' => STAT_MAX_CATTLE,
      'name' => 'Maximum number of cattle meeples out of the box during the game',
      'type' => 'int',
      'display' => 'limited',
    ],
  ],

  'value_labels' => [
    STAT_POSITION => [
      1 => totranslate('First player'),
      2 => totranslate('Second player'),
      3 => totranslate('Third player'),
      4 => totranslate('Fourth player'),
    ],
  ],

  'player' => [
    'position' => [
      'id' => STAT_POSITION,
      'name' => totranslate('Starting position in first round'),
      'type' => 'int',
    ],

    'scoreFields' => [
      'id' => STAT_SCORE_FIELDS,
      'name' => totranslate('Number of points earned by fields'),
      'type' => 'int',
    ],
    'scorePastures' => [
      'id' => STAT_SCORE_PASTURES,
      'name' => totranslate('Number of points earned by pastures'),
      'type' => 'int',
    ],
    'scoreGrains' => [
      'id' => STAT_SCORE_GRAINS,
      'name' => totranslate('Number of points earned by grain'),
      'type' => 'int',
    ],
    'scoreVegetables' => [
      'id' => STAT_SCORE_VEGETABLES,
      'name' => totranslate('Number of points earned by vegetables'),
      'type' => 'int',
    ],
    'scoreSheeps' => [
      'id' => STAT_SCORE_SHEEPS,
      'name' => totranslate('Number of points earned by sheep'),
      'type' => 'int',
    ],
    'scorePigs' => [
      'id' => STAT_SCORE_PIGS,
      'name' => totranslate('Number of points earned by wild boar'),
      'type' => 'int',
    ],
    'scoreCattles' => [
      'id' => STAT_SCORE_CATTLES,
      'name' => totranslate('Number of points earned by cattle'),
      'type' => 'int',
    ],
    'scoreUnused' => [
      'id' => STAT_SCORE_UNUSED,
      'name' => totranslate('Number of points lost by unused space'),
      'type' => 'int',
    ],
    'scoreStables' => [
      'id' => STAT_SCORE_STABLES,
      'name' => totranslate('Number of points earned by fenced stables'),
      'type' => 'int',
    ],
    'scoreClayRooms' => [
      'id' => STAT_SCORE_CLAY_ROOMS,
      'name' => totranslate('Number of points earned by clay hut rooms'),
      'type' => 'int',
    ],
    'scoreStoneRooms' => [
      'id' => STAT_SCORE_STONE_ROOMS,
      'name' => totranslate('Number of points earned by stone house rooms'),
      'type' => 'int',
    ],
    'scoreFarmers' => [
      'id' => STAT_SCORE_FARMERS,
      'name' => totranslate('Number of points earned by family size'),
      'type' => 'int',
    ],
    'scoreBeggings' => [
      'id' => STAT_SCORE_BEGGINGS,
      'name' => totranslate('Number of points lost by begging'),
      'type' => 'int',
    ],
    'scoreCards' => [
      'id' => STAT_SCORE_CARDS,
      'name' => totranslate('Number of points earned by cards'),
      'type' => 'int',
    ],
    'scoreCardsBonus' => [
      'id' => STAT_SCORE_CARDS_BONUS,
      'name' => totranslate('Number of bonus points earned by cards'),
      'type' => 'int',
    ],

    'firstPlayer' => [
      'id' => STAT_FIRST_PLAYER,
      'name' => totranslate('Number of times being first player of a round'),
      'type' => 'int',
    ],
    'placedFarmers' => [
      'id' => STAT_PLACED_FARMER,
      'name' => totranslate('Number of people placed on an action during the game'),
      'type' => 'int',
    ],

    'boardWood' => [
      'id' => STAT_WOOD_FROM_BOARD,
      'name' => totranslate('Amount of wood earned from the board'),
      'type' => 'int',
    ],
    'boardClay' => [
      'id' => STAT_CLAY_FROM_BOARD,
      'name' => totranslate('Amount of clay earned from the board'),
      'type' => 'int',
    ],
    'boardStone' => [
      'id' => STAT_STONE_FROM_BOARD,
      'name' => totranslate('Amount of stone earned from the board'),
      'type' => 'int',
    ],
    'boardReed' => [
      'id' => STAT_REED_FROM_BOARD,
      'name' => totranslate('Amount of reed earned from the board'),
      'type' => 'int',
    ],
    'boardGrain' => [
      'id' => STAT_GRAIN_FROM_BOARD,
      'name' => totranslate('Amount of grains earned from the board'),
      'type' => 'int',
    ],
    'boardVegetable' => [
      'id' => STAT_VEGETABLE_FROM_BOARD,
      'name' => totranslate('Amount of vegetables earned from the board'),
      'type' => 'int',
    ],
    'boardFood' => [
      'id' => STAT_FOOD_FROM_BOARD,
      'name' => totranslate('Amount of food earned from the board'),
      'type' => 'int',
    ],
    'boardSheep' => [
      'id' => STAT_SHEEP_FROM_BOARD,
      'name' => totranslate('Amount of sheep earned from the board'),
      'type' => 'int',
    ],
    'boardPig' => [
      'id' => STAT_PIG_FROM_BOARD,
      'name' => totranslate('Amount of wild boar earned from the board'),
      'type' => 'int',
    ],
    'boardCattle' => [
      'id' => STAT_CATTLE_FROM_BOARD,
      'name' => totranslate('Amount of cattle earned from the board'),
      'type' => 'int',
    ],

    'cardsWood' => [
      'id' => STAT_WOOD_FROM_CARDS,
      'name' => totranslate('Amount of wood earned from cards'),
      'type' => 'int',
    ],
    'cardsClay' => [
      'id' => STAT_CLAY_FROM_CARDS,
      'name' => totranslate('Amount of clay earned from cards'),
      'type' => 'int',
    ],
    'cardsStone' => [
      'id' => STAT_STONE_FROM_CARDS,
      'name' => totranslate('Amount of stone earned from cards'),
      'type' => 'int',
    ],
    'cardsReed' => [
      'id' => STAT_REED_FROM_CARDS,
      'name' => totranslate('Amount of reed earned from cards'),
      'type' => 'int',
    ],
    'cardsGrain' => [
      'id' => STAT_GRAIN_FROM_CARDS,
      'name' => totranslate('Amount of grains earned from cards'),
      'type' => 'int',
    ],
    'cardsVegetable' => [
      'id' => STAT_VEGETABLE_FROM_CARDS,
      'name' => totranslate('Amount of vegetables earned from cards'),
      'type' => 'int',
    ],
    'cardsFood' => [
      'id' => STAT_FOOD_FROM_CARDS,
      'name' => totranslate('Amount of food earned from cards'),
      'type' => 'int',
    ],
    'cardsSheep' => [
      'id' => STAT_SHEEP_FROM_CARDS,
      'name' => totranslate('Amount of sheep earned from cards'),
      'type' => 'int',
    ],
    'cardsPig' => [
      'id' => STAT_PIG_FROM_CARDS,
      'name' => totranslate('Amount of wild boar earned from cards'),
      'type' => 'int',
    ],
    'cardsCattle' => [
      'id' => STAT_CATTLE_FROM_CARDS,
      'name' => totranslate('Amount of cattle earned from cards'),
      'type' => 'int',
    ],
    'cardsBegging' => [
      'id' => STAT_BEGGING_FROM_CARDS,
      'name' => totranslate('Number of begging cards from cards'),
      'type' => 'int',
    ],
    'totalMajorBuilt' => [
      'id' => STAT_MAJOR_BUILT,
      'name' => totranslate('Number of Major improvements built'),
      'type' => 'int', // done
    ],
    'totalMinorBuilt' => [
      'id' => STAT_MINOR_BUILT,
      'name' => totranslate('Number of Minor improvements built'),
      'type' => 'int', // done
    ],
    'totalOccupationBuilt' => [
      'id' => STAT_OCCUPATION_BUILT,
      'name' => totranslate('Number of Occupations played'),
      'type' => 'int', // done
    ],
    'totalRoomsBuilt' => [
      'id' => STAT_ROOMS_BUILT,
      'name' => totranslate('Number of rooms built'),
      'type' => 'int', // done
    ],
    'convertedGrain' => [
      'id' => STAT_GRAIN_CONVERTED,
      'name' => totranslate('Number of grain converted to food'),
      'type' => 'int',
    ],
    'convertedVegetable' => [
      'id' => STAT_VEGETABLE_CONVERTED,
      'name' => totranslate('Number of vegetables converted to food'),
      'type' => 'int',
    ],
    'convertedSheep' => [
      'id' => STAT_SHEEP_CONVERTED,
      'name' => totranslate('Number of sheep converted to food'),
      'type' => 'int',
    ],
    'convertedPig' => [
      'id' => STAT_PIG_CONVERTED,
      'name' => totranslate('Number of pigs converted to food'),
      'type' => 'int',
    ],
    'convertedCattle' => [
      'id' => STAT_CATTLE_CONVERTED,
      'name' => totranslate('Number of cattle converted to food'),
      'type' => 'int',
    ],
    'convertedStone' => [
      'id' => STAT_STONE_CONVERTED,
      'name' => totranslate('Number of stone converted to food'),
      'type' => 'int',
    ],
    'convertedClay' => [
      'id' => STAT_CLAY_CONVERTED,
      'name' => totranslate('Number of clay converted to food'),
      'type' => 'int',
    ],
    'convertedReed' => [
      'id' => STAT_REED_CONVERTED,
      'name' => totranslate('Number of reed converted to food'),
      'type' => 'int',
    ],
    'convertedWood' => [
      'id' => STAT_WOOD_CONVERTED,
      'name' => totranslate('Number of wood converted to food'),
      'type' => 'int',
    ],
    //
    'grainToFood' => [
      'id' => STAT_FOOD_FROM_GRAIN,
      'name' => totranslate('Number of food from grain'),
      'type' => 'int',
    ],
    'vegetableToFood' => [
      'id' => STAT_FOOD_FROM_VEGETABLE,
      'name' => totranslate('Number of food from vegetable'),
      'type' => 'int',
    ],
    'sheepToFood' => [
      'id' => STAT_FOOD_FROM_SHEEP,
      'name' => totranslate('Number of food from sheep'),
      'type' => 'int',
    ],
    'pigToFood' => [
      'id' => STAT_FOOD_FROM_PIG,
      'name' => totranslate('Number of food from pig'),
      'type' => 'int',
    ],
    'cattleToFood' => [
      'id' => STAT_FOOD_FROM_CATTLE,
      'name' => totranslate('Number of food from cattle'),
      'type' => 'int',
    ],
    'stoneToFood' => [
      'id' => STAT_FOOD_FROM_STONE,
      'name' => totranslate('Number of food from stone'),
      'type' => 'int',
    ],
    'clayToFood' => [
      'id' => STAT_FOOD_FROM_CLAY,
      'name' => totranslate('Number of food from clay'),
      'type' => 'int',
    ],
    'reedToFood' => [
      'id' => STAT_FOOD_FROM_REED,
      'name' => totranslate('Number of food from reed'),
      'type' => 'int',
    ],
    'woodToFood' => [
      'id' => STAT_FOOD_FROM_WOOD,
      'name' => totranslate('Number of food from wood'),
      'type' => 'int',
    ],

    'harvestedGrain' => [
      'id' => STAT_HARVESTED_GRAINS,
      'name' => totranslate('Number of harvested grains'),
      'type' => 'int',
    ],
    'harvestedVegetable' => [
      'id' => STAT_HARVESTED_VEGETABLES,
      'name' => totranslate('Number of harvested vegetables'),
      'type' => 'int',
    ],

    /***********************
     *** TRACK CARD DATAS ***
     ***********************/

    'card1' => [
      'id' => STAT_CARD_1,
      'name' => 'Tracked card n°1',
      'type' => 'int',
      'display' => 'limited',
    ],
    'card2' => [
      'id' => STAT_CARD_2,
      'name' => 'Tracked card n°2',
      'type' => 'int',
      'display' => 'limited',
    ],
    'card3' => [
      'id' => STAT_CARD_3,
      'name' => 'Tracked card n°3',
      'type' => 'int',
      'display' => 'limited',
    ],
    'card4' => [
      'id' => STAT_CARD_4,
      'name' => 'Tracked card n°4',
      'type' => 'int',
      'display' => 'limited',
    ],
    'card5' => [
      'id' => STAT_CARD_5,
      'name' => 'Tracked card n°5',
      'type' => 'int',
      'display' => 'limited',
    ],
    'card6' => [
      'id' => STAT_CARD_6,
      'name' => 'Tracked card n°6',
      'type' => 'int',
      'display' => 'limited',
    ],
    'card7' => [
      'id' => STAT_CARD_7,
      'name' => 'Tracked card n°7',
      'type' => 'int',
      'display' => 'limited',
    ],
    'card8' => [
      'id' => STAT_CARD_8,
      'name' => 'Tracked card n°8',
      'type' => 'int',
      'display' => 'limited',
    ],
    'card9' => [
      'id' => STAT_CARD_9,
      'name' => 'Tracked card n°9',
      'type' => 'int',
      'display' => 'limited',
    ],
    'card10' => [
      'id' => STAT_CARD_10,
      'name' => 'Tracked card n°10',
      'type' => 'int',
      'display' => 'limited',
    ],
    'card11' => [
      'id' => STAT_CARD_11,
      'name' => 'Tracked card n°11',
      'type' => 'int',
      'display' => 'limited',
    ],
    'card12' => [
      'id' => STAT_CARD_12,
      'name' => 'Tracked card n°12',
      'type' => 'int',
      'display' => 'limited',
    ],
    'card13' => [
      'id' => STAT_CARD_13,
      'name' => 'Tracked card n°13',
      'type' => 'int',
      'display' => 'limited',
    ],
    'card14' => [
      'id' => STAT_CARD_14,
      'name' => 'Tracked card n°14',
      'type' => 'int',
      'display' => 'limited',
    ],

    'cardDiscarded1' => [
      'id' => STAT_CARD_DISCARDED_1,
      'name' => 'Rejected card n°1',
      'type' => 'int',
      'display' => 'limited',
    ],
    'cardDiscarded2' => [
      'id' => STAT_CARD_DISCARDED_2,
      'name' => 'Rejected card n°2',
      'type' => 'int',
      'display' => 'limited',
    ],
    'cardDiscarded3' => [
      'id' => STAT_CARD_DISCARDED_3,
      'name' => 'Rejected card n°3',
      'type' => 'int',
      'display' => 'limited',
    ],
    'cardDiscarded4' => [
      'id' => STAT_CARD_DISCARDED_4,
      'name' => 'Rejected card n°4',
      'type' => 'int',
      'display' => 'limited',
    ],
    'cardDiscarded5' => [
      'id' => STAT_CARD_DISCARDED_5,
      'name' => 'Rejected card n°5',
      'type' => 'int',
      'display' => 'limited',
    ],
    'cardDiscarded6' => [
      'id' => STAT_CARD_DISCARDED_6,
      'name' => 'Rejected card n°6',
      'type' => 'int',
      'display' => 'limited',
    ],
  ],
];
