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
 * stats.inc.php
 *
 * arnak game statistics description
 *
 */

/*
  In this file, you are describing game statistics, that will be displayed at the end of the
  game.

  !! After modifying this file, you must use "Reload  statistics configuration" in BGA Studio backoffice
  ("Control Panel" / "Manage Game" / "Your Game")

  There are 2 types of statistics:
  _ table statistics, that are not associated to a specific player (ie: 1 value for each game).
  _ player statistics, that are associated to each players (ie: 1 value for each player in the game).

  Statistics types can be "int" for integer, "float" for floating point values, and "bool" for boolean

  Once you defined your statistics there, you can start using "initStat", "setStat" and "incStat" method
  in your game logic, using statistics names defined below.

  !! It is not a good idea to modify this file when a game is running !!

  If your game is already public on BGA, please read the following before any change:
  http://en.doc.boardgamearena.com/Post-release_phase#Changes_that_breaks_the_games_in_progress

  Notes:
  * Statistic index is the reference used in setStat/incStat/initStat PHP method
  * Statistic index must contains alphanumerical characters and no space. Example: 'turn_played'
  * Statistics IDs must be >=10
  * Two table statistics can't share the same ID, two player statistics can't share the same ID
  * A table statistic can have the same ID than a player statistics
  * Statistics ID is the reference used by BGA website. If you change the ID, you lost all historical statistic data. Do NOT re-use an ID of a deleted statistic
  * Statistic name is the English description of the statistic as shown to players

*/

$stats_type = array(

  // Statistics global to table
  "table" => array(

/*
  Examples:

  "table_teststat1" => array( "id"=> 10,
              "name" => totranslate("table test stat 1"), 
              "type" => "int" ),

  "table_teststat2" => array( "id"=> 11,
              "name" => totranslate("table test stat 2"), 
              "type" => "float" )
*/  
  ),
  
  // Statistics existing for each player
  "player" => array(

    "score-research" => array("id"=> 10,
          "name" => totranslate("Score for research"),
          "type" => "int" ),
    "score-temple" => array("id"=> 11,
          "name" => totranslate("Score for temple"),
          "type" => "int" ),
    "score-cards" => array("id"=> 12,
          "name" => totranslate("Score for cards"),
          "type" => "int" ),
    "score-item" => array("id"=> 13,
          "name" => totranslate("Score for items"),
          "type" => "int" ),
    "score-art" => array("id"=> 14,
          "name" => totranslate("Score for artifacts"),
          "type" => "int" ),
    "score-fear" => array("id"=> 15,
          "name" => totranslate("Score for fear"),
          "type" => "int" ),
    "score-idols" => array("id"=> 16,
          "name" => totranslate("Score for idols"),
          "type" => "int" ),
    "score-guardians" => array("id"=> 17,
          "name" => totranslate("Score for guardians"),
          "type" => "int" ),
    "gained-coins" => array("id"=> 18,
          "name" => totranslate("Coins gained"),
          "type" => "int" ),
    "gained-compass" => array("id"=> 19,
          "name" => totranslate("Compasses gained"),
          "type" => "int" ),
    "gained-tablet" => array("id"=> 20,
          "name" => totranslate("Tablets gained"),
          "type" => "int" ),
    "gained-arrowhead" => array("id"=> 21,
          "name" => totranslate("Arrowheads gained"),
          "type" => "int" ),
    "gained-jewel" => array("id"=> 22,
          "name" => totranslate("Jewels gained"),
          "type" => "int" ),
    "gained-idol" => array("id"=> 23,
          "name" => totranslate("Idols gained"),
          "type" => "int" ),
    "idol-bonus" => array("id"=> 24,
          "name" => totranslate("Idol bonuses used"),
          "type" => "int" ),
    "idol-jewel" => array("id"=> 25,
          "name" => totranslate("Idol jewel bonuses used"),
          "type" => "int" ),
    "idol-arrowhead" => array("id"=> 26,
          "name" => totranslate("Idol arrowhead bonuses used"),
          "type" => "int" ),
    "idol-tablet" => array("id"=> 27,
          "name" => totranslate("Idol tablet bonuses used"),
          "type" => "int" ),
    "idol-coins" => array("id"=> 28,
          "name" => totranslate("Idol coin + compass bonuses used"),
          "type" => "int" ),
    "idol-card" => array("id"=> 29,
          "name" => totranslate("Idol card bonuses used"),
          "type" => "int" ),
    "gained-item" => array("id"=> 30,
          "name" => totranslate("Items gained"),
          "type" => "int" ),
    "gained-art" => array("id"=> 31,
          "name" => totranslate("Artifacts gained"),
          "type" => "int" ),
    "gained-card" => array("id"=> 32,
          "name" => totranslate("Cards gained"),
          "type" => "int" ),
    "gained-draw" => array("id"=> 33,
          "name" => totranslate("Cards drawn"),
          "type" => "int" ),
    "discarded" => array("id"=> 34,
          "name" => totranslate("Cards discarded"),
          "type" => "int" ),
    "played" => array("id"=> 35,
          "name" => totranslate("Cards played"),
          "type" => "int" ),
    "cost-art" => array("id"=> 36,
          "name" => totranslate("Total cost of artifacts"),
          "type" => "int" ),
    "cost-item" => array("id"=> 37,
          "name" => totranslate("Total cost of items"),
          "type" => "int" ),
    "guardians" => array("id"=> 38,
          "name" => totranslate("Guardians overcame"),
          "type" => "int" ),
    "guardians-1" => array("id"=> 39,
          "name" => totranslate("Guardians overcame in round 1"),
          "type" => "int" ),
    "guardians-2" => array("id"=> 40,
          "name" => totranslate("Guardians overcame in round 2"),
          "type" => "int" ),
    "guardians-3" => array("id"=> 41,
          "name" => totranslate("Guardians overcame in round 3"),
          "type" => "int" ),
    "guardians-4" => array("id"=> 42,
          "name" => totranslate("Guardians overcame in round 4"),
          "type" => "int" ),
    "guardians-5" => array("id"=> 43,
          "name" => totranslate("Guardians overcame in round 5"),
          "type" => "int" ),
    "sites-activated" => array("id"=> 44,
          "name" => totranslate("Total sites activated"),
          "type" => "int" ),
    "sites-activated-basic" => array("id"=> 45,
          "name" => totranslate("Camp sites activated"),
          "type" => "int" ),
    "sites-activated-small" => array("id"=> 46,
          "name" => totranslate("Level 1 sites activated"),
          "type" => "int" ),
    "sites-activated-big" => array("id"=> 47,
          "name" => totranslate("Level 2 sites activated"),
          "type" => "int" ),
    "sites-discovered" => array("id"=> 48,
          "name" => totranslate("Sites discovered"),
          "type" => "int" ),
    "sites-discovered-small" => array("id"=> 49,
          "name" => totranslate("Level 1 sites discovered"),
          "type" => "int" ),
    "sites-discovered-big" => array("id"=> 50,
          "name" => totranslate("Level 2 sites discovered"),
          "type" => "int" ),
    "tokens-used" => array("id"=> 51,
          "name" => totranslate("Research tokens used"),
          "type" => "int" ),
    "book-step" => array("id"=> 52,
          "name" => totranslate("Notebook steps"),
          "type" => "int" ),
    "glass-step" => array("id"=> 53,
          "name" => totranslate("Magnifying glass steps"),
          "type" => "int" ),
    "temple" => array("id"=> 54,
          "name" => totranslate("Temple tiles"),
          "type" => "int" ),
    "temple-bronze" => array("id"=> 55,
          "name" => totranslate("Bronze temple tiles"),
          "type" => "int" ),
    "temple-silver" => array("id"=> 56,
          "name" => totranslate("Silver temple tiles"),
          "type" => "int" ),
    "temple-gold" => array("id"=> 57,
          "name" => totranslate("Gold temple tiles"),
          "type" => "int" ),
    "assistant-activated" => array("id"=> 58,
          "name" => totranslate("Assistant activations"),
          "type" => "int" ),
    "assistant-activated-silver" => array("id"=> 59,
          "name" => totranslate("Silver assistant activations"),
          "type" => "int" ),
    "assistant-activated-gold" => array("id"=> 60,
          "name" => totranslate("Gold assistant activations"),
          "type" => "int" ),
    "exiled" => array("id"=> 61,
          "name" => totranslate("Cards exiled"),
          "type" => "int" ),
    "gained-fear" => array("id"=> 62,
          "name" => totranslate("Fear gained"),
          "type" => "int" ),
  )

);
