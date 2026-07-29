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
 * gameoptions.inc.php
 *
 * agricola game options description
 *
 */

namespace AGR;

require_once 'modules/php/constants.inc.php';

$startAction = totranslate('Can only be used in a multiplayer game');
$begAction = totranslate('Draft cannot be enabled in beginner mode');
$livingHandNeedsAllCards = totranslate('Living Hand requires "Card Set: All Cards".');

$game_options = [
  OPTION_COMPETITIVE_LEVEL => [
    'name' => totranslate('Competitive level'),
    'values' => [
      OPTION_COMPETITIVE_BEGINNER => [
        'name' => totranslate('Beginner'),
        'tmdisplay' => totranslate('Beginner'),
        'description' => totranslate('No hand cards'),
      ],
      OPTION_COMPETITIVE_NORMAL => [
        'name' => totranslate('Normal'),
        'nobeginner' => true,
      ],
      OPTION_COMPETITIVE_BANLIST => [
        'name' => totranslate('Competitive'),
        'tmdisplay' => totranslate('Banlist'),
        'description' => totranslate('Remove overpowered or game-warping cards. The list varies by player count, see en.doc.boardgamearena.com/Gamehelpagricola#Banlist'),
        'nobeginner' => true,
      ],
    ],
    'default' => OPTION_COMPETITIVE_BEGINNER,
  ],

  OPTION_ADDITIONAL_SPACES => [
    'name' => totranslate('Additional Action Spaces'),
    'values' => [
      OPTION_ADDITIONAL_SPACES_ENABLED => [
        'name' => totranslate('Enabled (variant)'),
        'tmdisplay' => totranslate('Additional Spaces'),
        'description' => totranslate('Additional action space tile'),
      ],
      OPTION_ADDITIONAL_SPACES_DISABLED => [
        'name' => totranslate('Disabled (standard)'),
        'nobeginner' => true,
      ],
    ],
    'default' => OPTION_ADDITIONAL_SPACES_DISABLED,
    'startcondition' => [
      OPTION_ADDITIONAL_SPACES_ENABLED => [
        [
          'type' => 'minplayers',
          'value' => 2,
          'message' => totranslate('Additional action spaces needs at least 2 players'),
        ],
      ],
    ],
  ],

  OPTION_DRAFT => [
    'name' => totranslate('Draft mode'),
    'values' => [
      OPTION_DRAFT_DISABLED => [
        'name' => totranslate('Disabled'),
      ],

      OPTION_SEED_MODE => [
        'name' => totranslate('Seed mode'),
        'tmdisplay' => totranslate('[Custom seed]'),
        'description' => totranslate(
          'Allows you to replay a previous game with the same cards and action card order. Do NOT select this if you don\'t have a seed from a previous game'
        ),
      ],

      OPTION_DRAFT_LIVING_HAND => [
        'name' => totranslate('Living Hand'),
        'tmdisplay' => totranslate('[Living Hand]'),
        'description' => totranslate('Draft from 7 cards to keep 4 occupations and 4 minor improvements (solo: dealt directly). At the start and end of your turns, if you have fewer than 4 occupations or 4 minor improvements in hand, draw 2 of the missing type and keep 1 until you are back to 4. You may decline passing minor improvements at the start of your turn.'),
      ],

      OPTION_PICK_7_OUT_OF_10 => [
        'name' => totranslate('Keep 7 cards out of 10 dealt'),
        'tmdisplay' => totranslate('[Keep 7 out of 10]'),
      ],

      OPTION_DRAFT_7_SIMULTANEOUS => [
        'name' => totranslate('Draft 7 cards, Occupations with Minors'),
        'tmdisplay' => totranslate('[Draft 7, Occ + MI]'),
      ],
      OPTION_DRAFT_8_SIMULTANEOUS => [
        'name' => totranslate('Draft 7 out of 8 cards, Occupations with Minors'),
        'tmdisplay' => totranslate('[Draft 8 -> 7, Occ + MI]'),
      ],
      OPTION_DRAFT_9_SIMULTANEOUS => [
        'name' => totranslate('Draft 7 out of 9 cards, Occupations with Minors'),
        'tmdisplay' => totranslate('[Draft 9 -> 7, Occ + MI]'),
      ],
      OPTION_DRAFT_10_SIMULTANEOUS => [
        'name' => totranslate('Draft 7 out of 10 cards, Occupations with Minors'),
        'tmdisplay' => totranslate('[Draft 10 -> 7, Occ + MI]'),
      ],

      OPTION_DRAFT_7_OCCUPATIONS => [
        'name' => totranslate('Draft 7 cards, Occupations then Minors'),
        'tmdisplay' => totranslate('[Draft 7, Occ first]'),
      ],
      OPTION_DRAFT_8_OCCUPATIONS => [
        'name' => totranslate('Draft 7 out of 8 cards, Occupations then Minors'),
        'tmdisplay' => totranslate('[Draft 8 -> 7, Occ first]'),
      ],
      OPTION_DRAFT_9_OCCUPATIONS => [
        'name' => totranslate('Draft 7 out of 9 cards, Occupations then Minors'),
        'tmdisplay' => totranslate('[Draft 9 -> 7, Occ first]'),
      ],
      OPTION_DRAFT_10_OCCUPATIONS => [
        'name' => totranslate('Draft 7 out of 10 cards, Occupations then Minors'),
        'tmdisplay' => totranslate('[Draft 10 -> 7, Occ first]'),
      ],

      OPTION_DRAFT_7_MINORS => [
        'name' => totranslate('Draft 7 cards, Minors then Occupations'),
        'tmdisplay' => totranslate('[Draft 7, MI first]'),
      ],
      OPTION_DRAFT_8_MINORS => [
        'name' => totranslate('Draft 7 out of 8 cards, Minors then Occupations'),
        'tmdisplay' => totranslate('[Draft 8 -> 7, MI first]'),
      ],
      OPTION_DRAFT_9_MINORS => [
        'name' => totranslate('Draft 7 out of 9 cards, Minors then Occupations'),
        'tmdisplay' => totranslate('[Draft 9 -> 7, MI first]'),
      ],
      OPTION_DRAFT_10_MINORS => [
        'name' => totranslate('Draft 7 out of 10 cards, Minors then Occupations'),
        'tmdisplay' => totranslate('[Draft 10 -> 7, MI first]'),
      ],
      OPTION_DRAFT_FREE => [
        'name' => totranslate('Draft 7 out of all the cards (solo mode)'),
      ],
    ],
    'default' => OPTION_DRAFT_DISABLED,
    'startcondition' => [
      OPTION_DRAFT_FREE => [
        ['type' => 'maxplayers', 'value' => 1, 'message' => totranslate('Can only be used with one player')],
      ],
      OPTION_DRAFT_LIVING_HAND => [
        [
          'type' => 'otheroption', 'id' => OPTION_DECKS, 'value' => OPTION_DECKS_ALL, 'message' => $livingHandNeedsAllCards,
        ],
      ],
      OPTION_DRAFT_7_SIMULTANEOUS => [['type' => 'minplayers', 'value' => 2, 'message' => $startAction]],
      OPTION_DRAFT_8_SIMULTANEOUS => [['type' => 'minplayers', 'value' => 2, 'message' => $startAction]],
      OPTION_DRAFT_9_SIMULTANEOUS => [['type' => 'minplayers', 'value' => 2, 'message' => $startAction]],
      OPTION_DRAFT_10_SIMULTANEOUS => [['type' => 'minplayers', 'value' => 2, 'message' => $startAction]],
      OPTION_DRAFT_7_OCCUPATIONS => [['type' => 'minplayers', 'value' => 2, 'message' => $startAction]],
      OPTION_DRAFT_8_OCCUPATIONS => [['type' => 'minplayers', 'value' => 2, 'message' => $startAction]],
      OPTION_DRAFT_9_OCCUPATIONS => [['type' => 'minplayers', 'value' => 2, 'message' => $startAction]],
      OPTION_DRAFT_10_OCCUPATIONS => [['type' => 'minplayers', 'value' => 2, 'message' => $startAction]],
      OPTION_DRAFT_7_MINORS => [['type' => 'minplayers', 'value' => 2, 'message' => $startAction]],
      OPTION_DRAFT_8_MINORS => [['type' => 'minplayers', 'value' => 2, 'message' => $startAction]],
      OPTION_DRAFT_9_MINORS => [['type' => 'minplayers', 'value' => 2, 'message' => $startAction]],
      OPTION_DRAFT_10_MINORS => [['type' => 'minplayers', 'value' => 2, 'message' => $startAction]],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroptionisnot',
        'id' => OPTION_COMPETITIVE_LEVEL,
        'value' => OPTION_COMPETITIVE_BEGINNER,
      ],
    ],
  ],

  OPTION_SCORING => [
    'name' => totranslate('Live scoring'),
    'values' => [
      OPTION_SCORING_ENABLED => [
        'name' => totranslate('Enabled'),
      ],
      OPTION_SCORING_DISABLED => [
        'name' => totranslate('Disabled'),
        'tmdisplay' => totranslate('No live scoring'),
      ],
    ],
    'default' => OPTION_SCORING_ENABLED,
  ],

  OPTION_WEAK_BANLIST => [
    'name' => totranslate('Ban weak cards'),
    'values' => [
      OPTION_WEAK_BANLIST_DISABLED => [
        'name' => totranslate('Disabled'),
      ],
      OPTION_WEAK_BANLIST_ENABLED => [
        'name' => totranslate('Enabled'),
        'tmdisplay' => totranslate('Disable Weakest Cards'),
        'description' => totranslate('Remove underpowered cards. The list varies by player count, see en.doc.boardgamearena.com/Gamehelpagricola#Banlist'),
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroptionisnot',
        'id' => OPTION_COMPETITIVE_LEVEL,
        'value' => OPTION_COMPETITIVE_BEGINNER,
      ],
    ],
    'default' => OPTION_WEAK_BANLIST_DISABLED,
  ],

  OPTION_SNAKE_OPENING => [
    'name' => totranslate('Snake opening'),
    'values' => [
      OPTION_SNAKE_OPENING_DISABLED => [
        'name' => totranslate('Disabled (standard)'),
      ],
      OPTION_SNAKE_OPENING_ENABLED => [
        'name' => totranslate('Enabled (variant)'),
        'tmdisplay' => totranslate('Snake opening'),
        'description' => totranslate(
          'Each player starts with 3 food. In round 1, each player’s second person is played in reverse turn order.'
        ),
      ],
    ],
    'default' => OPTION_SNAKE_OPENING_DISABLED,
    'startcondition' => [
      OPTION_SNAKE_OPENING_ENABLED => [
        ['type' => 'minplayers', 'value' => 2, 'message' => $startAction],
      ],
    ],
  ],

  OPTION_CAMPAIGN => [
    'name' => totranslate('Solo campaign'),
    'values' => [
      OPTION_CAMPAIGN_DISABLED => [
        'name' => totranslate('Disabled'),
      ],
      OPTION_CAMPAIGN_ENABLED => [
        'name' => totranslate('Enabled'),
        'tmdisplay' => totranslate('Solo campaign'),
        'description' => totranslate(
          'Hit the target score for 8 games in a row. Each game you can choose to make one played occupation permanent (played for free at the start of all future games). Gain 1 starting food for every 2 points by which you beat the previous goal.'
        ),
      ],
    ],
    'default' => OPTION_CAMPAIGN_DISABLED,
    'startcondition' => [
      OPTION_CAMPAIGN_ENABLED => [
        ['type' => 'maxplayers', 'value' => 1, 'message' => totranslate('The solo campaign can only be played with one player')],
        [
          'type' => 'otheroptionisnot',
          'id' => OPTION_COMPETITIVE_LEVEL,
          'value' => OPTION_COMPETITIVE_BEGINNER,
          'message' => totranslate('The solo campaign cannot be played in beginner mode (set Competitive level to Normal or Competitive)'),
        ],
        [
          'type' => 'otheroption',
          'id' => OPTION_DRAFT,
          'value' => OPTION_DRAFT_DISABLED,
          'message' => totranslate('The solo campaign cannot be combined with a draft (set Draft mode to Disabled)'),
        ],
      ],
    ],
    // No displaycondition: BGA never resets a hidden option's value, so hiding this for 2+
    // players left stale Enabled values blocking table starts with no visible checkbox to untick
  ],

  OPTION_DECKS => [
    'name' => totranslate('Card Set'),
    'values' => [
      OPTION_DECKS_ALL => [
        'name' => totranslate('All Cards'),
        'tmdisplay' => totranslate('All Cards'),
      ],
      OPTION_DECKS_BASE => [
        'name' => totranslate('Base Set'),
        'tmdisplay' => totranslate('Base Set'),
      ],
      OPTION_DECKS_CUSTOM => [
        'name' => totranslate('Custom'),
      ],
    ],
    'startcondition' => [
      OPTION_DECKS_BASE => [
        [
          'type' => 'otheroptionisnot',
          'id' => OPTION_DRAFT,
          'value' => OPTION_DRAFT_LIVING_HAND,
          'message' => $livingHandNeedsAllCards,
        ],
      ],
      OPTION_DECKS_CUSTOM => [
        [
          'type' => 'otheroptionisnot',
          'id' => OPTION_DRAFT,
          'value' => OPTION_DRAFT_LIVING_HAND,
          'message' => $livingHandNeedsAllCards,
        ],
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroptionisnot',
        'id' => OPTION_COMPETITIVE_LEVEL,
        'value' => OPTION_COMPETITIVE_BEGINNER,
      ],
    ],
    'default' => OPTION_DECKS_ALL,
  ],

  OPTION_DECK_BASE => [
    'name' => totranslate('Base Set'),
    'values' => [
      OPTION_DECK_DISABLED => [
        'name' => totranslate('Excluded'),
      ],
      OPTION_DECK_ENABLED => [
        'name' => totranslate('Included'),
        'tmdisplay' => totranslate('[Base]'),
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroption',
        'id' => OPTION_DECKS,
        'value' => OPTION_DECKS_CUSTOM,
      ],
    ],
    'default' => OPTION_DECK_ENABLED,
  ],

  OPTION_DECK_A => [
    'name' => totranslate('Artifex'),
    'values' => [
      OPTION_DECK_DISABLED => [
        'name' => totranslate('Excluded'),
      ],
      OPTION_DECK_ENABLED => [
        'name' => totranslate('Included'),
        'tmdisplay' => '[A]',
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroption',
        'id' => OPTION_DECKS,
        'value' => OPTION_DECKS_CUSTOM,
      ],
    ],
    'default' => OPTION_DECK_ENABLED,
  ],

  OPTION_DECK_B => [
    'name' => totranslate('Bubulcus'),
    'values' => [
      OPTION_DECK_DISABLED => [
        'name' => totranslate('Excluded'),
      ],
      OPTION_DECK_ENABLED => [
        'name' => totranslate('Included'),
        'tmdisplay' => '[B]',
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroption',
        'id' => OPTION_DECKS,
        'value' => OPTION_DECKS_CUSTOM,
      ],
    ],
    'default' => OPTION_DECK_ENABLED,
  ],

  OPTION_DECK_C => [
    'name' => totranslate('Corbarius'),
    'values' => [
      OPTION_DECK_DISABLED => [
        'name' => totranslate('Excluded'),
      ],
      OPTION_DECK_ENABLED => [
        'name' => totranslate('Included'),
        'tmdisplay' => '[C]',
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroption',
        'id' => OPTION_DECKS,
        'value' => OPTION_DECKS_CUSTOM,
      ],
    ],
    'default' => OPTION_DECK_ENABLED,
  ],

  OPTION_DECK_D => [
    'name' => totranslate('Dulcinaria'),
    'values' => [
      OPTION_DECK_DISABLED => [
        'name' => totranslate('Excluded'),
      ],
      OPTION_DECK_ENABLED => [
        'name' => totranslate('Included'),
        'tmdisplay' => '[D]',
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroption',
        'id' => OPTION_DECKS,
        'value' => OPTION_DECKS_CUSTOM,
      ],
    ],
    'default' => OPTION_DECK_ENABLED,
  ],

  OPTION_DECK_E => [
    'name' => totranslate('Ephipparius'),
    'values' => [
      OPTION_DECK_DISABLED => [
        'name' => totranslate('Excluded'),
      ],
      OPTION_DECK_ENABLED => [
        'name' => totranslate('Included'),
        'tmdisplay' => '[E]',
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroption',
        'id' => OPTION_DECKS,
        'value' => OPTION_DECKS_CUSTOM,
      ],
    ],
    'default' => OPTION_DECK_ENABLED,
  ],

  OPTION_DECK_CD => [
    'name' => totranslate('Consul Dirigens'),
    'values' => [
      OPTION_DECK_DISABLED => [
        'name' => totranslate('Excluded'),
      ],
      OPTION_DECK_ENABLED => [
        'name' => totranslate('Included'),
        'tmdisplay' => '[CD]',
      ],
    ],
    'displaycondition' => [
      [
        'type' => 'otheroption',
        'id' => OPTION_DECKS,
        'value' => OPTION_DECKS_CUSTOM,
      ],
    ],
    'default' => OPTION_DECK_ENABLED,
  ],
];

$game_preferences = [
  OPTION_AUTOPAY_HARVEST => [
    'name' => totranslate('Auto-pay harvest if enough food (and no special exchanges)'),
    'needReload' => false,
    'values' => [
      OPTION_AUTOPAY_HARVEST_ENABLED => ['name' => totranslate('Pay automatically')],
      OPTION_AUTOPAY_HARVEST_DISABLED => ['name' => totranslate('Always enter "Exchange" state')],
    ],
  ],

  OPTION_CONFIRM => [
    'name' => totranslate('Turn confirmation'),
    'needReload' => false,
    'values' => [
      OPTION_CONFIRM_TIMER => ['name' => totranslate('Enabled with timer')],
      OPTION_CONFIRM_ENABLED => ['name' => totranslate('Enabled')],
      OPTION_CONFIRM_DISABLED => ['name' => totranslate('Disabled')],
    ],
  ],

  OPTION_COLORBLIND => [
    'name' => totranslate('Colorblind-friendly farmers'),
    'needReload' => false,
    'values' => [
      OPTION_COLORBLIND_OFF => ['name' => totranslate('Disabled')],
      OPTION_COLORBLIND_ON => ['name' => totranslate('Enabled')],
    ],
  ],

  OPTION_FONT_DOMINICAN => [
    'name' => totranslate('Text font'),
    'needReload' => false,
    'values' => [
      OPTION_FONT_DOMINICAN_ON => ['name' => totranslate('Dominican (original)')],
      OPTION_FONT_DOMINICAN_OFF => ['name' => totranslate('Roboto (BGA default)')],
    ],
  ],

  OPTION_SMART_REORGANIZE => [
    'name' => totranslate('Auto reorganize'),
    'needReload' => false,
    'values' => [
      OPTION_SMART_REORGANIZE_ON => ['name' => totranslate('Enabled without confirm')],
      OPTION_SMART_REORGANIZE_OFF => ['name' => totranslate('Always manual')],
      OPTION_SMART_REORGANIZE_CONFIRM => ['name' => totranslate('Enabled with confirm')],
    ],
  ],

  OPTION_PLAYER_RESOURCES => [
    'name' => totranslate('Resources near player boards'),
    'needReload' => false,
    'values' => [
      OPTION_PLAYER_RESOURCES_PANNEL => ['name' => totranslate('In the top right panels')],
      OPTION_PLAYER_RESOURCES_BOARD => ['name' => totranslate('Next to the player boards')],
    ],
  ],

  OPTION_PLAYER_BOARDS => [
    'name' => totranslate('Other player boards'),
    'needReload' => false,
    'values' => [
      OPTION_PLAYER_BOARDS_BOTTOM => ['name' => totranslate('Under the central board')],
      OPTION_PLAYER_BOARDS_RIGHT => ['name' => totranslate('Next to the central board')],
    ],
  ],

  OPTION_DISPLAY_CARDS => [
    'name' => totranslate('Display hand cards'),
    'needReload' => false,
    'values' => [
      OPTION_DISPLAY_CARDS_MODAL => ['name' => totranslate('In a modal window')],
      OPTION_DISPLAY_CARDS_BOARD => ['name' => totranslate('Under the board')],
      OPTION_DISPLAY_CARDS_BOTTOM => ['name' => totranslate('Bottom of the screen')],
      OPTION_DISPLAY_CARDS_BOTTOM_LEFT => ['name' => totranslate('Bottom of the screen: align left')],
    ],
  ],

  OPTION_CARD_RULINGS_ICON => [
    'name' => totranslate('Show card rulings icon'),
    'needReload' => false,
    'default' => OPTION_CARD_RULINGS_ICON_ON,
    'values' => [
      OPTION_CARD_RULINGS_ICON_ON => ['name' => totranslate('Enabled')],
      OPTION_CARD_RULINGS_ICON_OFF => ['name' => totranslate('Disabled')],
    ],
  ],

  OPTION_HIGHLIGHT_SHARED_SCORING => [
    'name' => totranslate('Highlight shared scoring cards'),
    'needReload' => false,
    'values' => [
      OPTION_HIGHLIGHT_SHARED_SCORING_OFF => ['name' => totranslate('Disabled')],
      OPTION_HIGHLIGHT_SHARED_SCORING_ON => ['name' => totranslate('Enabled')],
    ],
  ],

  OPTION_ACTION_CARD_DISPLAY => [
    'name' => totranslate('Group player action cards'),
    'needReload' => false,
    'values' => [
      OPTION_ACTION_CARD_DISPLAY_PLAYER_BOARDS => ['name' => totranslate('Under player boards')],
      OPTION_ACTION_CARD_DISPLAY_CENTRAL => ['name' => totranslate('Grouped under central board')],
    ],
  ],
];
