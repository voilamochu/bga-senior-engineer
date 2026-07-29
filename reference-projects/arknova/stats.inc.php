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
 * stats.inc.php
 *
 * Ark Nova game statistics description
 *
 */

require_once 'modules/php/constants.inc.php';

$stats_type = [
  'table' => [
    'breaks' => [
      'id' => STAT_BREAKS,
      'name' => totranslate('Number of breaks'),
      'type' => 'int',
    ],
  ],

  'value_labels' => [
    STAT_POSITION => [
      1 => totranslate('First player'),
      2 => totranslate('Second player'),
      3 => totranslate('Third player'),
      4 => totranslate('Fourth player'),
    ],

    STAT_MAP => [
      100 => totranslate('Map A'),
      0 => totranslate('Map 0'),
      1 => totranslate('Map 1: Observation Tower'),
      2 => totranslate('Map 2: Outdoor Areas'),
      3 => totranslate('Map 3: Silver Lake'),
      4 => totranslate('Map 4: Commercial Harbor'),
      5 => totranslate('Map 5: Park Restaurant'),
      6 => totranslate('Map 6: Research Institute'),
      7 => totranslate('Map 7: Ice Cream Parlors'),
      8 => totranslate('Map 8: Hollywood Hills'),
      9 => totranslate('Map 9: Geographical Zoo'),
      10 => totranslate('Map 10: Rescue Station'),
      11 => totranslate('Map 11: Caves'),
      12 => totranslate('Map 12: Artificial Intelligence'),
      13 => totranslate('Map 13: Drawing Board'),
      14 => totranslate('Map 14: Lagoon'),
      200 => totranslate('Map T1: Tournament 1'),

      101 => totranslate('Map 1a: Observation Tower'),
      102 => totranslate('Map 2a: Outdoor Areas'),
      103 => totranslate('Map 3a: Silver Lake'),
      104 => totranslate('Map 4a: Commercial Harbor'),
      105 => totranslate('Map 5a: Park Restaurant'),
      106 => totranslate('Map 6a: Research Institute'),
      107 => totranslate('Map 7a: Ice Cream Parlors'),
      108 => totranslate('Map 8a: Hollywood Hills'),
    ],

    STAT_END_GAME_TRIGGERED => [
      0 => totranslate('No'),
      1 => totranslate('Yes'),
    ],
    STAT_CARD_UPGRADED_ANIMALS => [
      0 => totranslate('No'),
      1 => totranslate('Yes'),
    ],
    STAT_CARD_UPGRADED_BUILD => [
      0 => totranslate('No'),
      1 => totranslate('Yes'),
    ],
    STAT_CARD_UPGRADED_CARDS => [
      0 => totranslate('No'),
      1 => totranslate('Yes'),
    ],
    STAT_CARD_UPGRADED_SPONSORS => [
      0 => totranslate('No'),
      1 => totranslate('Yes'),
    ],
    STAT_CARD_UPGRADED_ASSOCATION => [
      0 => totranslate('No'),
      1 => totranslate('Yes'),
    ],

    STAT_DRAFTED_1 => [
      0 => '',
      10 => totranslate('Animals 1'),
      11 => totranslate('Animals 2'),
      12 => totranslate('Animals 3'),
      13 => totranslate('Animals 4'),

      20 => totranslate('Build 1'),
      21 => totranslate('Build 2'),
      22 => totranslate('Build 3'),
      23 => totranslate('Build 4'),

      30 => totranslate('Association 1'),
      31 => totranslate('Association 2'),
      32 => totranslate('Association 3'),
      33 => totranslate('Association 4'),

      40 => totranslate('Cards 1'),
      41 => totranslate('Cards 2'),
      42 => totranslate('Cards 3'),
      43 => totranslate('Cards 4'),

      50 => totranslate('Sponsors 1'),
      51 => totranslate('Sponsors 2'),
      52 => totranslate('Sponsors 3'),
      53 => totranslate('Sponsors 4'),
    ],
    STAT_DRAFTED_2 => [
      0 => '',
      10 => totranslate('Animals 1'),
      11 => totranslate('Animals 2'),
      12 => totranslate('Animals 3'),
      13 => totranslate('Animals 4'),

      20 => totranslate('Build 1'),
      21 => totranslate('Build 2'),
      22 => totranslate('Build 3'),
      23 => totranslate('Build 4'),

      30 => totranslate('Association 1'),
      31 => totranslate('Association 2'),
      32 => totranslate('Association 3'),
      33 => totranslate('Association 4'),

      40 => totranslate('Cards 1'),
      41 => totranslate('Cards 2'),
      42 => totranslate('Cards 3'),
      43 => totranslate('Cards 4'),

      50 => totranslate('Sponsors 1'),
      51 => totranslate('Sponsors 2'),
      52 => totranslate('Sponsors 3'),
      53 => totranslate('Sponsors 4'),
    ],
    STAT_DRAFTED_3 => [
      0 => '',
      10 => totranslate('Animals 1'),
      11 => totranslate('Animals 2'),
      12 => totranslate('Animals 3'),
      13 => totranslate('Animals 4'),

      20 => totranslate('Build 1'),
      21 => totranslate('Build 2'),
      22 => totranslate('Build 3'),
      23 => totranslate('Build 4'),

      30 => totranslate('Association 1'),
      31 => totranslate('Association 2'),
      32 => totranslate('Association 3'),
      33 => totranslate('Association 4'),

      40 => totranslate('Cards 1'),
      41 => totranslate('Cards 2'),
      42 => totranslate('Cards 3'),
      43 => totranslate('Cards 4'),

      50 => totranslate('Sponsors 1'),
      51 => totranslate('Sponsors 2'),
      52 => totranslate('Sponsors 3'),
      53 => totranslate('Sponsors 4'),
    ],
  ],

  'player' => [
    'position' => [
      'id' => STAT_POSITION,
      'name' => totranslate('Starting position in first round'),
      'type' => 'int',
    ],
    'turns' => [
      'id' => STAT_TURN,
      'name' => totranslate('Number of turns'),
      'type' => 'int',
    ],
    'breaksTriggered' => [
      'id' => STAT_BREAKS_TRIGGERED,
      'name' => totranslate('Number of breaks triggered'),
      'type' => 'int',
    ],
    'endGameTriggered' => [
      'id' => STAT_END_GAME_TRIGGERED,
      'name' => totranslate('Triggered end of game'),
      'type' => 'int',
    ],

    'map' => [
      'id' => STAT_MAP,
      'name' => totranslate('Map'),
      'type' => 'int',
    ],

    'drafted1' => [
      'id' => STAT_DRAFTED_1,
      'name' => totranslate('First drafted action card'),
      'type' => 'int',
    ],
    'drafted2' => [
      'id' => STAT_DRAFTED_2,
      'name' => totranslate('Second drafted action card'),
      'type' => 'int',
    ],
    'drafted3' => [
      'id' => STAT_DRAFTED_3,
      'name' => totranslate('Third drafted action card'),
      'type' => 'int',
    ],
    'actionCardAnimals' => [
      'id' => STAT_ACTION_CARD_ANIMAL,
      'name' => totranslate('Animals Action Card Number'),
      'type' => 'int',
    ],
    'actionCardBuild' => [
      'id' => STAT_ACTION_CARD_BUILD,
      'name' => totranslate('Build Action Card Number'),
      'type' => 'int',
    ],
    'actionCardAssociation' => [
      'id' => STAT_ACTION_CARD_ASSOCIATION,
      'name' => totranslate('Association Action Card Number'),
      'type' => 'int',
    ],
    'actionCardCards' => [
      'id' => STAT_ACTION_CARD_CARDS,
      'name' => totranslate('Cards Action Card Number'),
      'type' => 'int',
    ],
    'actionCardSponsors' => [
      'id' => STAT_ACTION_CARD_SPONSORS,
      'name' => totranslate('Sponsors Action Card Number'),
      'type' => 'int',
    ],

    'appeal' => [
      'id' => STAT_APPEAL,
      'name' => totranslate('Appeal'),
      'type' => 'int',
    ],
    'conservation' => [
      'id' => STAT_CONSERVATION,
      'name' => totranslate('Conservation'),
      'type' => 'int',
    ],
    'score' => [
      'id' => STAT_SCORE,
      'name' => totranslate('Score'),
      'type' => 'int',
    ],
    'reputation' => [
      'id' => STAT_REPUTATION,
      'name' => totranslate('Reputation'),
      'type' => 'int',
    ],

    'actionBuild' => [
      'id' => STAT_BUILD_ACTION,
      'name' => totranslate('Build actions'),
      'type' => 'int',
    ],
    'actionAnimals' => [
      'id' => STAT_ANIMALS_ACTION,
      'name' => totranslate('Animals actions'),
      'type' => 'int',
    ],
    'actionCards' => [
      'id' => STAT_CARDS_ACTION,
      'name' => totranslate('Cards actions'),
      'type' => 'int',
    ],
    'actionAssociation' => [
      'id' => STAT_ASSOCIATION_ACTION,
      'name' => totranslate('Association actions'),
      'type' => 'int',
    ],
    'actionSponsors' => [
      'id' => STAT_SPONSORS_ACTION,
      'name' => totranslate('Sponsors actions'),
      'type' => 'int',
    ],

    'xTokenGained' => [
      'id' => STAT_XTOKEN_GAINED,
      'name' => totranslate('X-Tokens gained'),
      'type' => 'int',
    ],
    'xTokenGainedInsteadOfAction' => [
      'id' => STAT_XTOKEN_GAINED_ACTION,
      'name' => totranslate('X-Tokens gained instead of action'),
      'type' => 'int',
    ],
    'xTokenUsed' => [
      'id' => STAT_XTOKEN_USED,
      'name' => totranslate('X-Tokens used'),
      'type' => 'int',
    ],

    'moneyGained' => [
      'id' => STAT_MONEY_GAINED,
      'name' => totranslate('Money gained'),
      'type' => 'int',
    ],
    'moneyGainedIncome' => [
      'id' => STAT_INCOME_TOTAL,
      'name' => totranslate('Money gained through income'),
      'type' => 'int',
    ],
    'moneyUsedAnimals' => [
      'id' => STAT_MONEY_USED_ANIMALS,
      'name' => totranslate('Money spent on animals'),
      'type' => 'int',
    ],
    'moneyUsedBuild' => [
      'id' => STAT_MONEY_USED_BUILD,
      'name' => totranslate('Money spent on enclosures'),
      'type' => 'int',
    ],
    'moneyUsedDonations' => [
      'id' => STAT_MONEY_USED_DONATIONS,
      'name' => totranslate('Money spent on donations'),
      'type' => 'int',
    ],
    'moneyUsedFromDisplay' => [
      'id' => STAT_MONEY_USED_FROM_DISPLAY,
      'name' => totranslate('Money spent for playing cards from reputation range'),
      'type' => 'int',
    ],

    'cardsDrawn' => [
      'id' => STAT_CARDS_DRAWN,
      'name' => totranslate('Cards drawn from deck'),
      'type' => 'int',
    ],
    'cardsTaken' => [
      'id' => STAT_CARDS_TAKEN,
      'name' => totranslate('Cards taken from reputation range'),
      'type' => 'int',
    ],
    'cardsSnapped' => [
      'id' => STAT_CARDS_SNAPPED,
      'name' => totranslate('Snapped cards'),
      'type' => 'int',
    ],
    'cardsDiscarded' => [
      'id' => STAT_CARDS_DISCARDED,
      'name' => totranslate('Discarded cards'),
      'type' => 'int',
    ],
    'sponsorsPlayed' => [
      'id' => STAT_SPONSORS_PLAYED,
      'name' => totranslate('Played sponsors'),
      'type' => 'int',
    ],
    'animalsPlayed' => [
      'id' => STAT_ANIMALS_PLAYED,
      'name' => totranslate('Played animals'),
      'type' => 'int',
    ],
    'animalsReleased' => [
      'id' => STAT_ANIMALS_RELEASED,
      'name' => totranslate('Released animals'),
      'type' => 'int',
    ],

    'associationWorkers' => [
      'id' => STAT_ASSOCIATION_WORKERS,
      'name' => totranslate('Association workers'),
      'type' => 'int',
    ],
    'associationDonation' => [
      'id' => STAT_ASSOCIATION_DONATION,
      'name' => totranslate('Donation association tasks'),
      'type' => 'int',
    ],
    'associationReputation' => [
      'id' => STAT_ASSOCIATION_REPUTATION,
      'name' => totranslate('Reputation association tasks'),
      'type' => 'int',
    ],
    'associationPartner' => [
      'id' => STAT_ASSOCIATION_PARTNER,
      'name' => totranslate('Partner zoo association tasks'),
      'type' => 'int',
    ],
    'associationUniversity' => [
      'id' => STAT_ASSOCIATION_UNIVERSITY,
      'name' => totranslate('University association tasks'),
      'type' => 'int',
    ],
    'associationConservation' => [
      'id' => STAT_ASSOCIATION_CONSERVATION,
      'name' => totranslate('Conservation project association tasks'),
      'type' => 'int',
    ],

    'builtEnclosures' => [
      'id' => STAT_BUILD_ENCLOSURES,
      'name' => totranslate('Built enclosures'),
      'type' => 'int',
    ],
    'builtKiosks' => [
      'id' => STAT_BUILD_KIOSKS,
      'name' => totranslate('Built kiosks'),
      'type' => 'int',
    ],
    'builtPavilions' => [
      'id' => STAT_BUILD_PAVILIONS,
      'name' => totranslate('Built pavilions'),
      'type' => 'int',
    ],
    'builtUniqueStructures' => [
      'id' => STAT_BUILD_STRUCTURES,
      'name' => totranslate('Built unique buildings'),
      'type' => 'int',
    ],
    'coveredHexes' => [
      'id' => STAT_COVERED_HEXES,
      'name' => totranslate('Covered hexes'),
      'type' => 'int',
    ],
    'emptyHexes' => [
      'id' => STAT_EMPTY_HEXES,
      'name' => totranslate('Empty hexes'),
      'type' => 'int',
    ],

    'upgradedCards' => [
      'id' => STAT_CARDS_UPGRADED,
      'name' => totranslate('Upgraded action cards'),
      'type' => 'int',
    ],
    'upgradedActionAnimals' => [
      'id' => STAT_CARD_UPGRADED_ANIMALS,
      'name' => totranslate('Upgraded Animals action card'),
      'type' => 'int',
    ],
    'upgradedActionBuild' => [
      'id' => STAT_CARD_UPGRADED_BUILD,
      'name' => totranslate('Upgraded Build action card'),
      'type' => 'int',
    ],
    'upgradedActionCards' => [
      'id' => STAT_CARD_UPGRADED_CARDS,
      'name' => totranslate('Upgraded Cards action card'),
      'type' => 'int',
    ],
    'upgradedActionSponsors' => [
      'id' => STAT_CARD_UPGRADED_SPONSORS,
      'name' => totranslate('Upgraded Sponsors action card'),
      'type' => 'int',
    ],
    'upgradedActionAssociation' => [
      'id' => STAT_CARD_UPGRADED_ASSOCATION,
      'name' => totranslate('Upgraded Association action card'),
      'type' => 'int',
    ],

    'iconAfrica' => [
      'id' => STAT_ICON_AFRICA,
      'name' => totranslate('Africa icons'),
      'type' => 'int',
    ],
    'iconEurope' => [
      'id' => STAT_ICON_EUROPE,
      'name' => totranslate('Europe icons'),
      'type' => 'int',
    ],
    'iconAsia' => [
      'id' => STAT_ICON_ASIA,
      'name' => totranslate('Asia icons'),
      'type' => 'int',
    ],
    'iconAustralia' => [
      'id' => STAT_ICON_AUSTRALIA,
      'name' => totranslate('Australia icons'),
      'type' => 'int',
    ],
    'iconAmericas' => [
      'id' => STAT_ICON_AMERICAS,
      'name' => totranslate('Americas icons'),
      'type' => 'int',
    ],

    'iconBird' => [
      'id' => STAT_ICON_BIRD,
      'name' => totranslate('Bird icons'),
      'type' => 'int',
    ],
    'iconPredator' => [
      'id' => STAT_ICON_PREDATOR,
      'name' => totranslate('Predator icons'),
      'type' => 'int',
    ],
    'iconHerbivore' => [
      'id' => STAT_ICON_HERBIVORE,
      'name' => totranslate('Herbivore icons'),
      'type' => 'int',
    ],
    'iconBear' => [
      'id' => STAT_ICON_BEAR,
      'name' => totranslate('Bear icons'),
      'type' => 'int',
    ],
    'iconReptile' => [
      'id' => STAT_ICON_REPTILE,
      'name' => totranslate('Reptile icons'),
      'type' => 'int',
    ],
    'iconPrimate' => [
      'id' => STAT_ICON_PRIMATE,
      'name' => totranslate('Primate icons'),
      'type' => 'int',
    ],
    'iconPet' => [
      'id' => STAT_ICON_PET,
      'name' => totranslate('Petting Zoo icons'),
      'type' => 'int',
    ],
    'iconSeaAnimal' => [
      'id' => STAT_ICON_SEA_ANIMAL,
      'name' => totranslate('Sea Animal icons'),
      'type' => 'int',
    ],

    'iconWater' => [
      'id' => STAT_ICON_WATER,
      'name' => totranslate('Water icons'),
      'type' => 'int',
    ],
    'iconRock' => [
      'id' => STAT_ICON_ROCK,
      'name' => totranslate('Rock icons'),
      'type' => 'int',
    ],

    'iconScience' => [
      'id' => STAT_ICON_SCIENCE,
      'name' => totranslate('Science icons'),
      'type' => 'int',
    ],
  ],
];
