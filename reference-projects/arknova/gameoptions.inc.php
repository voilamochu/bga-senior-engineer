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
 * gameoptions.inc.php
 *
 * Ark Nova game options description
 *
 */

namespace ARK;

require_once 'modules/php/constants.inc.php';

$game_options = [
  OPTION_COMPETITIVE_LEVEL => [
    'name' => totranslate('Competitive level'),
    'values' => [
      OPTION_COMPETITIVE_FIRST_GAME => [
        'name' => totranslate('First game'),
        'tmdisplay' => totranslate('First game'),
        'description' => totranslate('All players will play with map A'),
      ],
      OPTION_COMPETITIVE_BEGINNER => [
        'name' => totranslate('Beginner'),
        'tmdisplay' => totranslate('Beginner'),
        'description' => totranslate('All players will play with map 0'),
      ],
      OPTION_COMPETITIVE_NORMAL => [
        'name' => totranslate('Normal'),
        'nobeginner' => true,
      ],
      OPTION_COMPETITIVE_CUSTOM_SETUP => [
        'name' => totranslate('Custom setup'),
        'tmdisplay' => totranslate('Custom'),
        'description' => totranslate('All players will pick whatever map they want'),
      ],
      OPTION_COMPETITIVE_CUSTOM_SETUP_NON_BEGINNER => [
        'name' => totranslate('Custom setup without A and 0'),
        'tmdisplay' => totranslate('Custom - A/0'),
        'description' => totranslate('All players will pick whatever map they want, except beginner maps A and 0'),
      ],
      OPTION_COMPETITIVE_ALL_SAME_SETUP => [
        'name' => totranslate('Same map for all players'),
        'description' => totranslate('All players will play the same map'),
      ],
    ],
    'default' => OPTION_COMPETITIVE_FIRST_GAME,
  ],
  OPTION_PEACEFUL_MODE => [
    'name' => totranslate('Peaceful mode'),
    'values' => [
      OPTION_PEACEFUL_MODE_DISABLED => [
        'name' => totranslate('Disabled'),
        'description' => totranslate('You will play with interactive abilities like Poison and Pilfering'),
      ],
      OPTION_PEACEFUL_MODE_ENABLED => [
        'name' => totranslate('Enabled'),
        'tmdisplay' => totranslate('[Peaceful]'),
        'description' => totranslate(
          'Interactive abilities like Poison and Pilfering will be replaced by the effects intended for the solo game'
        ),
      ],
    ],
    'default' => OPTION_PEACEFUL_MODE_DISABLED,
    'displaycondition' => [
      [
        'type' => 'minplayers',
        'value' => [2, 3, 4],
      ],
    ],
  ],
  OPTION_SOLO_DIFFICULTY => [
    'name' => totranslate('Difficulty level'),
    'values' => [
      OPTION_SOLO_DIFFICULTY_BEGINNER => [
        'name' => totranslate('Normal'),
        'description' => totranslate('You will start the game with 20 appeal'),
      ],
      OPTION_SOLO_DIFFICULTY_NORMAL => [
        'name' => totranslate('Experienced'),
        'description' => totranslate('You will start the game with 10 appeal'),
        'nobeginner' => true,
      ],
      OPTION_SOLO_DIFFICULTY_HARD => [
        'name' => totranslate('Expert'),
        'description' => totranslate('You will start the game with 0 appeal'),
        'nobeginner' => true,
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'maxplayers',
        'value' => [1],
      ],
    ],
  ],

  OPTION_SOLO_CHALLENGE => [
    'name' => totranslate('Solo Challenge?'),
    'values' => [
      OPTION_CHALLENGE_YES => [
        'name' => totranslate('Yes'),
        'description' => totranslate('You will play 3 games in row with new cards/maps/bonuses'),
        'nobeginner' => true,
      ],
      OPTION_CHALLENGE_NO => [
        'name' => totranslate('No'),
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'maxplayers',
        'value' => [1],
      ],
    ],
    'startcondition' => [
      \OPTION_CHALLENGE_YES => [
        [
          'type' => 'otheroption',
          'id' => OPTION_COMPETITIVE_LEVEL,
          'value' => OPTION_COMPETITIVE_NORMAL,
          'message' => totranslate('Solo challenge can only be played in Normal competitive level.'),
        ],
      ],
    ],

    'default' => OPTION_CHALLENGE_NO,
  ],

  OPTION_MAP_PACK => [
    'name' => totranslate('Map Pack 1 (Disabled for now)'),
    'values' => [
      // OPTION_MAP_PACK_YES => [
      //   'name' => totranslate('Enabled'),
      //   'description' => totranslate('The two additional maps 9 and 10 will be included'),
      //   'nobeginner' => true,
      //   'tmdisplay' => totranslate('[Map pack 1]'),
      // ],
      OPTION_MAP_PACK_NO => [
        'name' => totranslate('Disabled (publisher\'s choice)'),
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroptionisnot',
        'id' => \OPTION_COMPETITIVE_LEVEL,
        'value' => [OPTION_COMPETITIVE_FIRST_GAME, OPTION_COMPETITIVE_BEGINNER],
      ],
    ],

    'default' => OPTION_MAP_PACK_NO,
  ],

  OPTION_MAP_PACK2 => [
    'name' => totranslate('Map Pack 2'),
    'values' => [
      OPTION_MAP_PACK_YES => [
        'name' => totranslate('Enabled'),
        'description' => totranslate('The five additional maps 11, 12, 13, 14 and T1 will be included'),
        'nobeginner' => true,
        'tmdisplay' => totranslate('[Map pack 2]'),
      ],
      OPTION_MAP_PACK_NO => [
        'name' => totranslate('Disabled'),
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroptionisnot',
        'id' => \OPTION_COMPETITIVE_LEVEL,
        'value' => [OPTION_COMPETITIVE_FIRST_GAME, OPTION_COMPETITIVE_BEGINNER],
      ],
    ],

    'default' => OPTION_MAP_PACK_NO,
  ],

  OPTION_SAME_MAP_MODE => [
    'name' => totranslate('Players\' map'),
    'values' => [
      \OPTION_SAME_MAP_RANDOM => [
        'name' => totranslate('Random'),
        'tmdisplay' => totranslate('Same map: random'),
        'description' => totranslate('All players will play the same random map'),
      ],
      1 => [
        'name' => totranslate('Map 1 (Observation Tower)'),
        'tmdisplay' => totranslate('Same map: Map1 (Observation Tower)'),
        'description' => totranslate('All players will play the map 1 (Observation Tower)'),
      ],
      2 => [
        'name' => totranslate('Map 2 (Outdoor Areas)'),
        'tmdisplay' => totranslate('Same map: Map2 (Outdoor Areas)'),
        'description' => totranslate('All players will play the map 2 (Outdoor Areas)'),
      ],
      3 => [
        'name' => totranslate('Map 3 (Silver Lake)'),
        'tmdisplay' => totranslate('Same map: Map3 (Silver Lake)'),
        'description' => totranslate('All players will play the map 3 (Silver Lake)'),
      ],
      4 => [
        'name' => totranslate('Map 4 (Commercial Harbor)'),
        'tmdisplay' => totranslate('Same map: Map4 (Commercial Harbor)'),
        'description' => totranslate('All players will play the map 4 (Commercial Harbor)'),
      ],
      5 => [
        'name' => totranslate('Map 5 (Park Restaurant)'),
        'tmdisplay' => totranslate('Same map: Map5 (Park Restaurant)'),
        'description' => totranslate('All players will play the map 5 (Park Restaurant)'),
      ],
      6 => [
        'name' => totranslate('Map 6 (Research Institute)'),
        'tmdisplay' => totranslate('Same map: Map6 (Research Institute)'),
        'description' => totranslate('All players will play the map 6 (Research Institute)'),
      ],
      7 => [
        'name' => totranslate('Map 7 (Ice Cream Parlors)'),
        'tmdisplay' => totranslate('Same map: Map7 (Ice Cream Parlors)'),
        'description' => totranslate('All players will play the map 7 (Ice Cream Parlors)'),
      ],
      8 => [
        'name' => totranslate('Map 8 (Hollywood Hills)'),
        'tmdisplay' => totranslate('Same map: Map8 (Hollywood Hills)'),
        'description' => totranslate('All players will play the map 8 (Hollywood Hills)'),
      ],
      9 => [
        'name' => totranslate('Map 9 (Geographical Zoo)'),
        'tmdisplay' => totranslate('Same map: Map9 (Geographical Zoo)'),
        'description' => totranslate('All players will play the map 9 (Geographical Zoo)'),
      ],
      10 => [
        'name' => totranslate('Map 10 (Rescue Station)'),
        'tmdisplay' => totranslate('Same map: Map10 (Rescue Station)'),
        'description' => totranslate('All players will play the map 10 (Rescue Station)'),
      ],
      11 => [
        'name' => totranslate('Map 11 (Caves)'),
        'tmdisplay' => totranslate('Same map: Map11 (Caves)'),
        'description' => totranslate('All players will play the map 11 (Caves)'),
      ],
      12 => [
        'name' => totranslate('Map 12 (Artificial Intelligence)'),
        'tmdisplay' => totranslate('Same map: Map12 (AI)'),
        'description' => totranslate('All players will play the map 12 (Artificial Intelligence)'),
      ],
      13 => [
        'name' => totranslate('Map 13 (Drawing Board)'),
        'tmdisplay' => totranslate('Same map: Map13 (Drawing Board)'),
        'description' => totranslate('All players will play the map 13 (Drawing Board)'),
      ],
      14 => [
        'name' => totranslate('Map 14 (Lagoon)'),
        'tmdisplay' => totranslate('Same map: Map14 (Lagoon)'),
        'description' => totranslate('All players will play the map 14 (Lagoon)'),
      ],
      100 => [
        'name' => totranslate('Map T1 (Tournament 1)'),
        'tmdisplay' => totranslate('Same map: MapT1'),
        'description' => totranslate('All players will play the map T1'),
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroption',
        'id' => \OPTION_COMPETITIVE_LEVEL,
        'value' => [\OPTION_COMPETITIVE_ALL_SAME_SETUP],
      ],
    ],
  ],


  OPTION_ALTERNATIVE_MAPS => [
    'name' => totranslate('Alternative Maps (Standard maps for now)'),
    'values' => [
      OPTION_ALTERNATIVE_MAPS_YES => [
        'name' => totranslate('Enabled (publisher\'s choice)'),
        'description' => totranslate('Slight variations on maps 1-8'),
        'nobeginner' => true,
        'tmdisplay' => totranslate('[Alt maps]'),
      ],
      // OPTION_ALTERNATIVE_MAPS_NO => [
      //   'name' => totranslate('Disabled'),
      // ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroptionisnot',
        'id' => \OPTION_COMPETITIVE_LEVEL,
        'value' => [OPTION_COMPETITIVE_FIRST_GAME, OPTION_COMPETITIVE_BEGINNER],
      ],
    ],

    'default' => OPTION_ALTERNATIVE_MAPS_YES,
  ],

  OPTION_MARINE_WORLD => [
    'name' => totranslate('Expansion'),
    'level' => 'major',
    'values' => [
      OPTION_MARINE_WORLD_YES => [
        'name' => totranslate('Marine World'),
        'description' => totranslate('Expansion Marine World'),
        'nobeginner' => true,
        'tmdisplay' => totranslate('[Marine World]'),
      ],
      OPTION_MARINE_WORLD_NO => [
        'name' => totranslate('Base game only'),
      ],
    ],

    'default' => OPTION_MARINE_WORLD_NO,
  ]
];

$game_preferences = [
  OPTION_CONFIRM => [
    'name' => totranslate('Turn confirmation'),
    'needReload' => false,
    'default' => OPTION_CONFIRM_ENABLED,
    'values' => [
      OPTION_CONFIRM_ENABLED => ['name' => totranslate('Enabled')],
      OPTION_CONFIRM_DISABLED => ['name' => totranslate('Disabled')],
      OPTION_CONFIRM_TIMER => ['name' => totranslate('Enabled with timer')],
    ],
  ],
  OPTION_CONFIRM_UNDOABLE => [
    'name' => totranslate('Undoable actions confirmation'),
    'needReload' => false,
    'values' => [
      OPTION_CONFIRM_ENABLED => ['name' => totranslate('Enabled')],
      OPTION_CONFIRM_DISABLED => ['name' => totranslate('Disabled')],
    ],
  ],
  OPTION_REMOVE_SNAKE_IMAGES => [
    'name' => totranslate('Remove snake images'),
    'attribute' => 'disable-snake',
    'needReload' => false,
    'values' => [
      OPTION_REMOVE_SNAKE_IMAGES_DISABLED => ['name' => totranslate('Disabled')],
      OPTION_REMOVE_SNAKE_IMAGES_ENABLED => ['name' => totranslate('Enabled')],
    ],
  ],
  OPTION_REDUCED_COSTS => [
    'name' => totranslate('Show reduced costs'),
    'attribute' => 'reduced-cost',
    'needReload' => false,
    'default' => 1,
    'values' => [
      0 => ['name' => totranslate('Disabled')],
      1 => ['name' => totranslate('Enabled')],
    ],
  ],
  OPTION_FOLDER_COSTS => [
    'name' => totranslate('Show folder costs'),
    'attribute' => 'folder-cost',
    'needReload' => false,
    'values' => [
      0 => ['name' => totranslate('Only when using an upgraded action card')],
      1 => ['name' => totranslate('Always')],
      2 => ['name' => totranslate('Never')],
    ],
  ],
  OPTION_ENCLOSURE_SIZE => [
    'name' => totranslate('Display empty enclosure sizes'),
    'attribute' => 'enclosure-size',
    'needReload' => false,
    'values' => [
      0 => ['name' => totranslate('Disabled')],
      1 => ['name' => totranslate('Enabled')],
    ],
  ],
  OPTION_BUILDING_BORDERS => [
    'name' => totranslate('Display buildings borders'),
    'attribute' => 'buildings-borders',
    'needReload' => false,
    'values' => [
      0 => ['name' => totranslate('Disabled')],
      1 => ['name' => totranslate('Enabled')],
    ],
  ],
  OPTION_HELPER_PLAYABLE => [
    'name' => totranslate('Show why a card is playable or not on hover'),
    'attribute' => 'helper-playable',
    'needReload' => false,
    'values' => [
      0 => ['name' => totranslate('Enabled')],
      1 => ['name' => totranslate('Disabled')],
    ],
  ],
  OPTION_ANIMATION => [
    'name' => totranslate('Animations speed'),
    'needReload' => false,
    'values' => [
      5 => ['name' => totranslate('Very slow')],
      4 => ['name' => totranslate('Slow')],
      3 => ['name' => totranslate('Regular')],
      2 => ['name' => totranslate('Fast')],
      1 => ['name' => totranslate('Very fast')],
      0 => ['name' => totranslate('Instant (no animation)')],
    ],
    'default' => 3,
  ],

];
