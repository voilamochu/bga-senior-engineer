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
 * material.inc.php
 *
 * arnak game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *   
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
 */

$cards = [
  "art" => [
    1 =>  ["cost" => 3, "points" => 1, "travel" => [PLANE],        "varname" => "Artefact_Pathfinders_Sandals", "name" => clienttranslate("Pathfinder's Sandals")],
    2 =>  ["cost" => 4, "points" => 1, "travel" => [PLANE],        "varname" => "Artefact_Pathfinders_Staff",   "name" => clienttranslate("Pathfinder's Staff")],
    3 =>  ["cost" => 3, "points" => 1, "travel" => [PLANE],        "varname" => "Artefact_War_Mask",            "name" => clienttranslate("War Mask")],
    4 =>  ["cost" => 4, "points" => 3, "travel" => [PLANE],        "varname" => "Artefact_Treasure_Chest",      "name" => clienttranslate("Treasure Chest")],
    5 =>  ["cost" => 4, "points" => 2, "travel" => [PLANE],        "varname" => "Artefact_Ritual_Dagger",       "name" => clienttranslate("Ritual Dagger")],
    6 =>  ["cost" => 4, "points" => 2, "travel" => [PLANE],        "varname" => "Artefact_Crystal_Earring",     "name" => clienttranslate("Crystal Earring")],
    7 =>  ["cost" => 3, "points" => 1, "travel" => [PLANE],        "varname" => "Artefact_Mortar",              "name" => clienttranslate("Mortar")],
    8 =>  ["cost" => 3, "points" => 2, "travel" => [PLANE],        "varname" => "Artefact_Serpents_Gold",       "name" => clienttranslate("Serpent's Gold")],
    9 =>  ["cost" => 2, "points" => 1, "travel" => [PLANE],        "varname" => "Artefact_Serpent_Idol",        "name" => clienttranslate("Serpent Idol")],
    10 => ["cost" => 4, "points" => 2, "travel" => [PLANE],        "varname" => "Artefact_Monkey_Medallion",    "name" => clienttranslate("Monkey Medallion")],
    11 => ["cost" => 3, "points" => 1, "travel" => [PLANE],        "varname" => "Artefact_Idol_of_Ara_Anu",     "name" => clienttranslate("Idol of Ara-Anu")],
    12 => ["cost" => 2, "points" => 1, "travel" => [PLANE],        "varname" => "Artefact_Inscribed_Blade",     "name" => clienttranslate("Inscribed Blade")],
    13 => ["cost" => 4, "points" => 2, "travel" => [PLANE, PLANE], "varname" => "Artefact_Guardians_Ocarina",   "name" => clienttranslate("Guardian's Ocarina")],
    14 => ["cost" => 4, "points" => 2, "travel" => [PLANE],        "varname" => "Artefact_Tigerclaw_Hairpin",   "name" => clienttranslate("Tigerclaw Hairpin")],
    15 => ["cost" => 4, "points" => 1, "travel" => [PLANE],        "varname" => "Artefact_War_Club",            "name" => clienttranslate("War Club")],
    16 => ["cost" => 2, "points" => 1, "travel" => [PLANE],        "varname" => "Artefact_Sundial",             "name" => clienttranslate("Sundial")],
    17 => ["cost" => 4, "points" => 2, "travel" => [PLANE],        "varname" => "Artefact_Traders_Scales",      "name" => clienttranslate("Traders' Scales")],
    18 => ["cost" => 4, "points" => 1, "travel" => [PLANE],        "varname" => "Artefact_Hunting_Arrows",      "name" => clienttranslate("Hunting Arrows")],
    19 => ["cost" => 3, "points" => 2, "travel" => [PLANE],        "varname" => "Artefact_Coconut_Flask",       "name" => clienttranslate("Coconut Flask")],
    20 => ["cost" => 3, "points" => 1, "travel" => [PLANE],        "varname" => "Artefact_Cleansing_Cauldron",  "name" => clienttranslate("Cleansing Cauldron")],
    21 => ["cost" => 3, "points" => 1, "travel" => [PLANE],        "varname" => "Artefact_Ancient_Wine",        "name" => clienttranslate("Ancient Wine")],
    22 => ["cost" => 2, "points" => 1, "travel" => [PLANE],        "varname" => "Artefact_Decorated_Horn",      "name" => clienttranslate("Decorated Horn")],
    23 => ["cost" => 4, "points" => 2, "travel" => [PLANE],        "varname" => "Artefact_Ornate_ammer",        "name" => clienttranslate("Ornate Hammer")],
    24 => ["cost" => 4, "points" => 2, "travel" => [PLANE],        "varname" => "Artefact_Star_Charts",         "name" => clienttranslate("Star Charts")],
    25 => ["cost" => 2, "points" => 1, "travel" => [PLANE],        "varname" => "Artefact_Stone_Jar",           "name" => clienttranslate("Stone Jar")],
    26 => ["cost" => 3, "points" => 1, "travel" => [PLANE],        "varname" => "Artefact_Passage_Shell",       "name" => clienttranslate("Passage Shell")],
    27 => ["cost" => 3, "points" => 2, "travel" => [PLANE],        "varname" => "Artefact_Ceremonial_Rattle",   "name" => clienttranslate("Ceremonial Rattle")],
    28 => ["cost" => 4, "points" => 1, "travel" => [PLANE],        "varname" => "Artefact_Sacred_Drum",         "name" => clienttranslate("Sacred Drum")],
    29 => ["cost" => 3, "points" => 1, "travel" => [PLANE],        "varname" => "Artefact_Traders_Coins",       "name" => clienttranslate("Trader's Coins")],
    30 => ["cost" => 3, "points" => 2, "travel" => [PLANE],        "varname" => "Artefact_Stone_Key",           "name" => clienttranslate("Stone Key")],
    31 => ["cost" => 4, "points" => 2, "travel" => [PLANE],        "varname" => "Artefact_Obsidian_Earring",    "name" => clienttranslate("Obsidian Earring")],
    32 => ["cost" => 3, "points" => 1, "travel" => [PLANE],        "varname" => "Artefact_Guiding_Stone",       "name" => clienttranslate("Guiding Stone")],
    33 => ["cost" => 4, "points" => 1, "travel" => [PLANE],        "varname" => "Artefact_Guiding_Skull",       "name" => clienttranslate("Guiding Skull")],
    34 => ["cost" => 4, "points" => 1, "travel" => [PLANE],        "varname" => "Artefact_Runes_of_the_Dead",   "name" => clienttranslate("Runes of the Dead")],
    35 => ["cost" => 4, "points" => 2, "travel" => [PLANE],        "varname" => "Artefact_Guardians_Crown",     "name" => clienttranslate("Guardian's Crown")]
  ],
  "item" => [
    1 =>  ["cost" => 3,  "points" => 1, "travel" => [SHIP, SHIP],   "action" => "complex",    "exileItself" => false, "varname" => "Item_Sea_Turtle",        "name" => clienttranslate("Sea Turtle")],
    2 =>  ["cost" => 3,  "points" => 1, "travel" => [CAR, CAR],     "action" => "complex",    "exileItself" => false, "varname" => "Item_Ostrich",           "name" => clienttranslate("Ostrich")],
    3 =>  ["cost" => 4,  "points" => 1, "travel" => [CAR, CAR],     "action" => "main",       "exileItself" => false, "varname" => "Item_Pack_Donkey",       "name" => clienttranslate("Pack Donkey")],
    4 =>  ["cost" => 4,  "points" => 1, "travel" => [CAR, CAR],     "action" => "main",       "exileItself" => false, "varname" => "Item_Horse",             "name" => clienttranslate("Horse")],
    5 =>  ["cost" => 3,  "points" => 3, "travel" => [SHIP, SHIP],   "action" => "free",       "exileItself" => false, "varname" => "Item_Steam_Boat",        "name" => clienttranslate("Steam Boat")],
    6 =>  ["cost" => 3,  "points" => 3, "travel" => [CAR, CAR],     "action" => "free",       "exileItself" => false, "varname" => "Item_Automobile",        "name" => clienttranslate("Automobile")],
    7 =>  ["cost" => 1,  "points" => 1, "travel" => [CAR, CAR],     "action" => "complex",    "exileItself" => false, "varname" => "Item_Sturdy_Boots",      "name" => clienttranslate("Sturdy Boots")],
    8 =>  ["cost" => 1,  "points" => 1, "travel" => [SHIP, SHIP],   "action" => "free",       "exileItself" => false, "varname" => "Item_Gold_Pan",          "name" => clienttranslate("Gold Pan")],
    9 =>  ["cost" => 1,  "points" => 1, "travel" => [CAR],          "action" => "main",       "exileItself" => false, "varname" => "Item_Trowel",            "name" => clienttranslate("Trowel")],
    10 => ["cost" => 1,  "points" => 1, "travel" => [CAR],          "action" => "main",       "exileItself" => false, "varname" => "Item_Pickaxe",           "name" => clienttranslate("Pickaxe")],
    11 => ["cost" => 2,  "points" => 1, "travel" => [PLANE],        "action" => "complex",    "exileItself" => true,  "varname" => "Item_Hot_Air_Balloon",   "name" => clienttranslate("Hot Air Balloon")],
    12 => ["cost" => 4,  "points" => 3, "travel" => [PLANE, PLANE], "action" => "complex",    "exileItself" => false, "varname" => "Item_Aeroplane",         "name" => clienttranslate("Aeroplane")],
    13 => ["cost" => 3,  "points" => 1, "travel" => [CAR, SHIP],    "action" => "complex",    "exileItself" => true,  "varname" => "Item_Journal",           "name" => clienttranslate("Journal")],
    14 => ["cost" => 2,  "points" => 2, "travel" => [SHIP],         "action" => "main",       "exileItself" => false, "varname" => "Item_Parrot",            "name" => clienttranslate("Parrot")],
    15 => ["cost" => 1,  "points" => 1, "travel" => [SHIP],         "action" => "freeOrPass", "exileItself" => false, "varname" => "Item_Watch",             "name" => clienttranslate("Watch")],
    16 => ["cost" => 3,  "points" => 1, "travel" => [CAR, SHIP],    "action" => "main",       "exileItself" => false, "varname" => "Item_Army_Knife",        "name" => clienttranslate("Army Knife")],
    17 => ["cost" => 4,  "points" => 1, "travel" => [SHIP],         "action" => "complex",    "exileItself" => false, "varname" => "Item_Binoculars",        "name" => clienttranslate("Binoculars")],
    18 => ["cost" => 4,  "points" => 2, "travel" => [CAR],          "action" => "complex",    "exileItself" => false, "varname" => "Item_Tent",              "name" => clienttranslate("Tent")],
    19 => ["cost" => 2,  "points" => 2, "travel" => [SHIP],         "action" => "complex",    "exileItself" => false, "varname" => "Item_Fishing_Rod",       "name" => clienttranslate("Fishing Rod")],
    20 => ["cost" => 4,  "points" => 1, "travel" => [SHIP],         "action" => "complex",    "exileItself" => false, "varname" => "Item_Precision_Compass", "name" => clienttranslate("Precision Compass")],
    21 => ["cost" => 2,  "points" => 2, "travel" => [CAR],          "action" => "main",       "exileItself" => false, "varname" => "Item_Bow_and_Arrows",    "name" => clienttranslate("Bow and Arrows")],
    22 => ["cost" => 2,  "points" => 1, "travel" => [SHIP],         "action" => "free",       "exileItself" => false, "varname" => "Item_Carrier_Pigeon",    "name" => clienttranslate("Carrier Pigeon")],
    23 => ["cost" => 2,  "points" => 1, "travel" => [CAR],          "action" => "complex",    "exileItself" => true,  "varname" => "Item_Whip",              "name" => clienttranslate("Whip")],
    24 => ["cost" => 1,  "points" => 1, "travel" => [SHIP],         "action" => "main",       "exileItself" => true,  "varname" => "Item_Rough_Map",         "name" => clienttranslate("Rough Map")],
    25 => ["cost" => 2,  "points" => 1, "travel" => [PLANE],        "action" => "main",       "exileItself" => true,  "varname" => "Item_Airdrop",           "name" => clienttranslate("Airdrop")],
    26 => ["cost" => 2,  "points" => 1, "travel" => [SHIP],         "action" => "main",       "exileItself" => true,  "varname" => "Item_Flask",             "name" => clienttranslate("Flask")],
    27 => ["cost" => 4,  "points" => 1, "travel" => [CAR],          "action" => "main",       "exileItself" => false, "varname" => "Item_Machete",           "name" => clienttranslate("Machete")],
    28 => ["cost" => 2,  "points" => 2, "travel" => [SHIP],         "action" => "main",       "exileItself" => false, "varname" => "Item_Torch",             "name" => clienttranslate("Torch")],
    29 => ["cost" => 3,  "points" => 1, "travel" => [CAR],          "action" => "main",       "exileItself" => false, "varname" => "Item_Large_Backpack",    "name" => clienttranslate("Large Backpack")],
    30 => ["cost" => 2,  "points" => 1, "travel" => [SHIP],         "action" => "main",       "exileItself" => false, "varname" => "Item_Rope",              "name" => clienttranslate("Rope")],
    31 => ["cost" => 4,  "points" => 1, "travel" => [SHIP, SHIP],   "action" => "main",       "exileItself" => false, "varname" => "Item_Revolver",          "name" => clienttranslate("Revolver")],
    32 => ["cost" => 1,  "points" => 1, "travel" => [SHIP],         "action" => "free",       "exileItself" => false, "varname" => "Item_Hat",               "name" => clienttranslate("Hat")],
    33 => ["cost" => 2,  "points" => 1, "travel" => [CAR],          "action" => "main",       "exileItself" => true,  "varname" => "Item_Bear_Trap",         "name" => clienttranslate("Bear Trap")],
    34 => ["cost" => 2,  "points" => 2, "travel" => [CAR],          "action" => "complex",    "exileItself" => false, "varname" => "Item_Grappling_Hook",    "name" => clienttranslate("Grappling Hook")],
    35 => ["cost" => 3,  "points" => 2, "travel" => [CAR],          "action" => "complex",    "exileItself" => false, "varname" => "Item_Lantern",           "name" => clienttranslate("Lantern")],
    36 => ["cost" => 3,  "points" => 1, "travel" => [CAR],          "action" => "complex",    "exileItself" => false, "varname" => "Item_Dog",               "name" => clienttranslate("Dog")],
    37 => ["cost" => 3,  "points" => 3, "travel" => [CAR],          "action" => "main",       "exileItself" => false, "varname" => "Item_Brush",             "name" => clienttranslate("Brush")],
    38 => ["cost" => 2,  "points" => 2, "travel" => [SHIP],         "action" => "main",       "exileItself" => false, "varname" => "Item_Axe",               "name" => clienttranslate("Axe")],
    39 => ["cost" => 3,  "points" => 2, "travel" => [SHIP, SHIP],   "action" => "freeOrPass", "exileItself" => false, "varname" => "Item_Chronometer",       "name" => clienttranslate("Chronometer")],
    40 => ["cost" => 3,  "points" => 1, "travel" => [SHIP],         "action" => "main",       "exileItself" => false, "varname" => "Item_Theodolite",        "name" => clienttranslate("Theodolite")]
  ],
  "basic" => [
    "fundcar"     => ["points" => 0,  "travel" => [CAR],  "action" => "free", "varname" => "Basic_Funding_Car",  "name" => clienttranslate("Funding")],
    "fundship"    => ["points" => 0,  "travel" => [SHIP], "action" => "free", "varname" => "Basic_Funding_Ship", "name" => clienttranslate("Funding")],
    "explorecar"  => ["points" => 0,  "travel" => [CAR],  "action" => "free", "varname" => "Basic_Explore_Car",  "name" => clienttranslate("Exploration")],
    "exploreship" => ["points" => 0,  "travel" => [SHIP], "action" => "free", "varname" => "Basic_Explore_Ship", "name" => clienttranslate("Exploration")],
    "fear"        => ["points" => -1, "travel" => [BOOT], "action" => "none", "varname" => "Basic_Fear",         "name" => clienttranslate("Fear")]
  ]
];

$assistants = [
  1  => ["silver" => ["ressources" => ["coins" => 2]],                       "gold" => ["ressources" => ["coins" => 3]]],
  2  => ["silver" => ["ressources" => ["tablet" => 1]],                      "gold" => ["ressources" => ["tablet" => 1, "coins" => 1]]],
  3  => ["silver" => ["payboot" => 1, "ressources" => ["arrowhead" => 1]],   "gold" => ["ressources" => ["arrowhead" => 1]]],
  4  => ["silver" => ["ressources" => ["coins" => -1, "arrowhead" => 1]],    "gold" => ["ressourcesChoice" => [["coins" => -1, "arrowhead" => 1], ["coins" => -1, "jewel" => 1]]]],
  5  => ["silver" => ["exile" => 1],                                         "gold" => ["exile" => 1, "ressources" => ["compass" => 1]]],
  6  => ["silver" => ["ressources" => ["card" => 1], "discard" => 1],        "gold" => ["ressources" => ["card" => 1]]],
  7  => ["silver" => ["ressources" => ["coins" => 1], "travel" => [PLANE]],  "gold" => ["ressources" => ["coins" => 2], "travel" => [PLANE, PLANE]]],
  8  => ["silver" => ["ressources" => ["compass" => 1], "travel" => [CAR]],  "gold" => ["ressources" => ["compass" => 1, "coins" => 1], "travel" => [CAR, CAR]]],
  9  => ["silver" => ["ressources" => ["compass" => 1], "travel" => [SHIP]], "gold" => ["ressources" => ["compass" => 1, "coins" => 1], "travel" => [SHIP, SHIP]]],
  10 => ["silver" => ["discount" => 1],                                      "gold" => ["discount" => 2]],
  11 => ["silver" => ["upgrade" => 1],                                       "gold" => ["upgrade" => 1, "ressources" => ["compass" => 1]]],
  12 => ["silver" => ["ressources" => ["compass" => 1]],                     "gold" => ["ressources" => ["compass" => 2]]]
];

$birdResearch = [
  "squares" => [
    ["possibilities" => [1, 2],    "step" => 0, "cost" => []],
    ["possibilities" => [3, 4],    "step" => 1, "cost" => ["compass"  => 1, "arrowhead" => 1]],
    ["possibilities" => [4],       "step" => 1, "cost" => ["jewel" => 1]],
    ["possibilities" => [5],       "step" => 2, "cost" => ["jewel" => 1]],
    ["possibilities" => [5],       "step" => 2, "cost" => ["tablet" => 1, "arrowhead" => 1]],
    ["possibilities" => [6, 7, 8], "step" => 3, "cost" => ["tablet" => 2, "arrowhead" => 1]],
    ["possibilities" => [9],       "step" => 4, "cost" => ["tablet" => 1, "arrowhead" => 1, "coins" => 1]],
    ["possibilities" => [9],       "step" => 4, "cost" => ["tablet" => 1, "jewel" => 1]],
    ["possibilities" => [9],       "step" => 4, "cost" => ["arrowhead" => 2]],
    ["possibilities" => [10, 11],  "step" => 5, "cost" => ["coins" => 1, "jewel" => 1]],
    ["possibilities" => [12],      "step" => 6, "cost" => ["compass" => 1, "jewel" => 1]],
    ["possibilities" => [12, 13],  "step" => 6, "cost" => ["tablet" => 2, "arrowhead" => 1]],
    ["possibilities" => [14],      "step" => 7, "cost" => ["tablet" => 1, "arrowhead" => 1, "coins" => 1]],
    ["possibilities" => [14],      "step" => 7, "cost" => ["tablet" => 1, "jewel" => 1]],
    ["possibilities" => [],        "step" => 8, "cost" => ["coins" => 1, "compass" => 1, "jewel" => 1]],
  ],
  "steps" => [
    ["glass" => ["points" => 0,  "bonus" => "" ],        "book" => ["points" => 0,  "bonus" => ""]],
    ["glass" => ["points" => 1,  "bonus" => "coins" ],   "book" => ["points" => 0,  "bonus" => "assistant-silver"]],
    ["glass" => ["points" => 2,  "bonus" => "compass" ], "book" => ["points" => 1,  "bonus" => "assistant-silver"]],
    ["glass" => ["points" => 4,  "bonus" => "compass" ], "book" => ["points" => 2,  "bonus" => "assistant-gold"]],
    ["glass" => ["points" => 6,  "bonus" => "compass" ], "book" => ["points" => 4,  "bonus" => "assistant-gold"]],
    ["glass" => ["points" => 9,  "bonus" => "compass" ], "book" => ["points" => 6,  "bonus" => "3compass"]],
    ["glass" => ["points" => 12, "bonus" => "card" ],    "book" => ["points" => 8,  "bonus" => "free-art"]],
    ["glass" => ["points" => 16, "bonus" => "compass" ], "book" => ["points" => 10, "bonus" => "guard"]]
  ],
  "tiles" => [
    1 => ["color" => "bronze", "points" =>  2, "cost" => ["coins" => 1, "tablet" => 2]],
    2 => ["color" => "bronze", "points" =>  2, "cost" => ["jewel" => 1]],
    3 => ["color" => "bronze", "points" =>  2, "cost" => ["compass" => 1, "arrowhead" => 1]],
    4 => ["color" => "silver", "points" =>  6, "cost" => ["coins" => 1, "tablet" => 2, "jewel" => 1]],
    5 => ["color" => "silver", "points" =>  6, "cost" => ["jewel" => 1, "compass" => 1, "arrowhead" => 1]],
    6 => ["color" => "gold",   "points" => 11, "cost" => ["coins" => 1, "tablet" => 2, "jewel" => 1, "compass" => 1, "arrowhead" => 1]]
  ],
  "templeLastSteps" => [1 => 23, 2 => 21, 3 => 20, 4 => 19]
];

$snakeResearch = [
  "squares" => [
    ["possibilities" => [1, 2],   "step" => 0, "cost" => []],
    ["possibilities" => [3, 4],   "step" => 1, "cost" => ["compass" => 1, "tablet" => 2]],
    ["possibilities" => [4, 5],   "step" => 1, "cost" => ["jewel" => 1]],
    ["possibilities" => [6],      "step" => 2, "cost" => ["coins" => 1, "compass" => 1, "arrowhead" => 1]],
    ["possibilities" => [6, 7],   "step" => 2, "cost" => ["tablet" => 1, "jewel" => 1]],
    ["possibilities" => [7],      "step" => 2, "cost" => ["arrowhead" => 2]],
    ["possibilities" => [8],      "step" => 3, "cost" => ["tablet" => 2, "arrowhead" => 1]],
    ["possibilities" => [8],      "step" => 3, "cost" => ["coins" => 1, "jewel" => 1]],
    ["possibilities" => [9, 10],  "step" => 4, "cost" => ["idol" => 1]],
    ["possibilities" => [11, 12], "step" => 5, "cost" => ["arrowhead" => 2]],
    ["possibilities" => [12],     "step" => 5, "cost" => ["tablet" => 1, "jewel" => 1]],
    ["possibilities" => [13],     "step" => 6, "cost" => ["tablet" => 1, "jewel" => 1]],
    ["possibilities" => [13],     "step" => 6, "cost" => ["compass" => 1, "tablet" => 3]],
    ["possibilities" => [14],     "step" => 7, "cost" => ["coins" => 1, "tablet" => 1, "arrowhead" => 1]],
    ["possibilities" => [],       "step" => 8, "cost" => ["compass" => 1, "arrowhead" => 1, "jewel" => 1]]
  ],
  "steps" => [
    ["glass" => ["points" => 0,  "bonus" => ""],                  "book" => ["points" => 0,  "bonus" => ""]],
    ["glass" => ["points" => 1,  "bonus" => "coins"],             "book" => ["points" => 0,  "bonus" => "assistant-silver"]],
    ["glass" => ["points" => 2,  "bonus" => "2coins"],            "book" => ["points" => 3,  "bonus" => "exile"]],
    ["glass" => ["points" => 3,  "bonus" => "card"],              "book" => ["points" => 4,  "bonus" => "assistant-gold"]],
    ["glass" => ["points" => 4,  "bonus" => "assistant-special"], "book" => ["points" => 5,  "bonus" => "free-art"]],
    ["glass" => ["points" => 5,  "bonus" => "assistant-gold"],    "book" => ["points" => 8,  "bonus" => "assistant-refresh"]],
    ["glass" => ["points" => 10, "bonus" => "fear"],              "book" => ["points" => 12, "bonus" => "card"]],
    ["glass" => ["points" => 15, "bonus" => "fear"],              "book" => ["points" => 15, "bonus" => "jewel"]]
  ],
  "tiles" => [
    1 => ["color" => "bronze", "points" =>  2, "cost" => ["compass" => 1, "tablet" => 2]],
    2 => ["color" => "bronze", "points" =>  2, "cost" => ["jewel" => 1]],
    3 => ["color" => "bronze", "points" =>  2, "cost" => ["coins" => 1, "arrowhead" => 1]],
    4 => ["color" => "silver", "points" =>  6, "cost" => ["compass" => 1, "tablet" => 2, "jewel" => 1]],
    5 => ["color" => "silver", "points" =>  6, "cost" => ["jewel" => 1, "coins" => 1, "arrowhead" => 1]],
    6 => ["color" => "gold",   "points" => 11, "cost" => ["compass" => 1, "tablet" => 2, "jewel" => 1, "coins" => 1, "arrowhead" => 1]]
  ],
  "lastSteps" => [1 => 23, 2 => 21, 3 => 20, 4 => 19]
];

$birdTravelCost = [
  [[BOOT], [BOOT, BOOT]],
  [[BOOT], [BOOT, BOOT]],
  [[BOOT], [BOOT, BOOT]],
  [[BOOT], [BOOT, BOOT]],
  [[BOOT], [BOOT, BOOT]],

  [[CAR]],
  [[CAR]],
  [[SHIP]],
  [[SHIP]],
  [[CAR]],
  [[CAR]],
  [[SHIP]],
  [[SHIP]],

  [[CAR, CAR]],
  [[CAR, CAR]],
  [[SHIP, SHIP]],
  [[SHIP, SHIP]]
];

$snakeTravelCost = [
  [[BOOT], [BOOT, BOOT]],
  [[BOOT], [BOOT, BOOT]],
  [[BOOT], [BOOT, BOOT]],
  [[BOOT], [BOOT, BOOT]],
  [[BOOT], [BOOT, BOOT]],

  [[CAR]],
  [[CAR]],
  [[SHIP]],
  [[SHIP]],
  [[CAR]],
  [[BOOT, BOOT]],
  [[PLANE]],
  [[SHIP]],

  [[CAR, CAR]],
  [[BOOT, PLANE]],
  [[SHIP, CAR]],
  [[SHIP, SHIP]]
];

$sites = [
  "basic" => [
    0 => ["coins" => 2], 
    1 => ["compass" => 2], 
    2 => ["tablet" => 2], 
    3 => ["arrowhead" => 1], 
    4 => ["discardforjewel" => 1]
  ],
  "small" => [
    1  => ["buyfreeitem" => 1],
    2  => ["coins" => 1, "arrowhead" => 1],
    3  => ["card" => 1, "coins" => 1, "tablet" => 1],
    4  => ["compass" => 1, "arrowhead" => 1],
    5  => ["coins" => 1, "tablet" => 2],
    6  => ["arrowhead" => 1, "tablet" => 1],
    7  => ["arrowhead" => 1, "card" => 1],
    8  => ["fear" => 1, "tablet" => 1, "jewel" => 1],
    9  => ["fear" => 1, "compass" => 1, "jewel" => 1],
    10 => ["jewel" => 1, "discard" => 1]
  ],
  "big" => [ 
    1 => ["compass" => 2, "jewel" => 1],
    2 => ["coins" => 1, "compass" => 1, "tablet" => 1, "arrowhead" => 1],
    3 => ["arrowhead" => 1, "jewel" => 1],
    4 => ["card" => 1, "tablet" => 1, "jewel" => 1],
    5 => ["tablet" => 2, "jewel" => 1],
    6 => ["fear" => 1, "tablet" => 2, "arrowhead" => 2]
  ]
];

$guardians = [
    1 =>  ["cost" => ["compass" => 1, "coins" => 1, "arrowhead" => 1],      "reward" => ["travel" => [SHIP]]],
    2 =>  ["cost" => ["discard" => 1, "coins" => 1, "arrowhead" => 1],      "reward" => ["exile" => 1]],
    3 =>  ["cost" => ["travel" => [BOOT], "coins" => 1, "arrowhead" => 1],  "reward" => ["travel" => [CAR]]],
    4 =>  ["cost" => ["coins" => 2, "arrowhead" => 1],                      "reward" => ["travel" => [CAR]]],
    5 =>  ["cost" => ["travel" => [BOOT], "tablet" => 1, "arrowhead" => 1], "reward" => ["exile" => 1]],
    6 =>  ["cost" => ["travel" => [PLANE], "arrowhead" => 1],               "reward" => ["exile" => 1]],
    7 =>  ["cost" => ["compass" => 1, "discard" => 1, "arrowhead" => 1],    "reward" => ["card" => 1]],
    8 =>  ["cost" => ["coins" => 4],                                        "reward" => ["upgrade" => 1]],
    9 =>  ["cost" => ["travel" => [SHIP], "arrowhead" => 1],                "reward" => ["travel" => [CAR]]],
    10 => ["cost" => ["travel" => [CAR], "arrowhead" => 1],                 "reward" => ["travel" => [SHIP]]],
    11 => ["cost" => ["travel" => [BOOT, BOOT], "compass" => 1],            "reward" => ["exile" => 1]],
    12 => ["cost" => ["travel" => [BOOT], "jewel" => 1],                    "reward" => ["travel" => [PLANE]]],
    13 => ["cost" => ["tablet" => 3],                                       "reward" => ["travel" => [PLANE]]],
    14 => ["cost" => ["travel" => [PLANE], "arrowhead" => 1],               "reward" => ["exile" => 1]],
    15 => ["cost" => ["compass" => 2, "arrowhead" => 1],                    "reward" => ["travel" => [SHIP]]]
];

$this->material = [
  "cards" => $cards,
  "assistants" => $assistants,
  "birdResearch" => $birdResearch,
  "snakeResearch" => $snakeResearch,
  "birdTravelCost" => $birdTravelCost,
  "snakeTravelCost" => $snakeTravelCost,
  "sites" => $sites,
  "guardians" => $guardians
];

?>
