<?php

/*
 * Game options
 */

const OPTION_COMPETITIVE_LEVEL = 102;
const OPTION_COMPETITIVE_FIRST_GAME = 0;
const OPTION_COMPETITIVE_BEGINNER = 1;
const OPTION_COMPETITIVE_NORMAL = 2;
const OPTION_COMPETITIVE_CUSTOM_SETUP = 3;
const OPTION_COMPETITIVE_CUSTOM_SETUP_NON_BEGINNER = 4;
const OPTION_COMPETITIVE_ALL_SAME_SETUP = 5;

const OPTION_PEACEFUL_MODE = 103;
const OPTION_PEACEFUL_MODE_DISABLED = 0;
const OPTION_PEACEFUL_MODE_ENABLED = 1;

const OPTION_SOLO_DIFFICULTY = 104;
const OPTION_SOLO_DIFFICULTY_BEGINNER = 0;
const OPTION_SOLO_DIFFICULTY_NORMAL = 1;
const OPTION_SOLO_DIFFICULTY_HARD = 2;

const OPTION_SOLO_CHALLENGE = 106;
const OPTION_CHALLENGE_YES = 1;
const OPTION_CHALLENGE_NO = 0;

const OPTION_SAME_MAP_MODE = 105;
const OPTION_SAME_MAP_RANDOM = 0;

const OPTION_MAP_PACK = 107;
const OPTION_MAP_PACK2 = 109;
const OPTION_MAP_PACK_YES = 1;
const OPTION_MAP_PACK_NO = 0;

const OPTION_ALTERNATIVE_MAPS = 108;
const OPTION_ALTERNATIVE_MAPS_NO = 0;
const OPTION_ALTERNATIVE_MAPS_YES = 1;

const OPTION_MARINE_WORLD = 111;
const OPTION_MARINE_WORLD_NO = 0;
const OPTION_MARINE_WORLD_YES = 1;

const BASE = 'bg';
const MW = 'mw';

/*
 * User preferences
 */
const OPTION_CONFIRM = 103;
const OPTION_CONFIRM_DISABLED = 0;
const OPTION_CONFIRM_ENABLED = 2;
const OPTION_CONFIRM_TIMER = 3;

const OPTION_CONFIRM_UNDOABLE = 104;

const OPTION_REMOVE_SNAKE_IMAGES = 105;
const OPTION_REMOVE_SNAKE_IMAGES_DISABLED = 0;
const OPTION_REMOVE_SNAKE_IMAGES_ENABLED = 1;

const OPTION_REDUCED_COSTS = 106;

const OPTION_FOLDER_COSTS = 107;

const OPTION_ENCLOSURE_SIZE = 108;

const OPTION_BUILDING_BORDERS = 109;

const OPTION_HELPER_PLAYABLE = 110;

const OPTION_ANIMATION = 111;

/*
 * State constants
 */
const ST_GAME_SETUP = 1;
const ST_SETUP_BRANCH = 2;

const ST_INITIAL_MAP_SELECTION = 3;
const ST_INITIAL_ACTION_CARD_DRAFT = 4;
const ST_INITIAL_SELECTION = 5;
const ST_INITIAL_ACTION_CARD_KEEP = 8;
const ST_BEFORE_START_OF_TURN = 6;
const ST_TURNACTION = 7;

const ST_BREAK_MULTIACTIVE = 10;
const ST_BREAK_CARDS = 11;
const ST_BREAK_REFILL = 12;
const ST_BREAK_INCOME = 13;
const ST_BREAK_FINISH = 14;

const ST_CHOOSE_ACTION_CARD = 20;
const ST_GAIN = 21;
const ST_PAY = 22;
const ST_ACTIVATE_CARD = 23;
const ST_SPECIAL_EFFECT = 24;
const ST_TAKE_BONUS = 25;
const ST_MOVE_ANIMALS = 26;
const ST_TAKE_IN_RANGE_OR_DECK = 27;
const ST_USE_KEPT_BONUS = 28;

const ST_BUILD = 30;
const ST_ADVANCE_BREAK = 31;
const ST_ANIMALS = 32;
const ST_ASSOCIATION = 33;
const ST_CARDS = 34;
const ST_SPONSORS = 35;
const ST_DISCARD = 36;
const ST_UPGRADE_CARD = 37;
const ST_RELEASE = 38;
const ST_DISCARD_SCORING = 40;

const ST_SPRINT = 50;
const ST_HUNTER = 51;
const ST_INVENTIVE = 52;
const ST_JUMPING = 53;
const ST_SUNBATHING = 54;
const ST_POUCH = 55;
const ST_DIGGING = 56;
const ST_VENOM = 57;
const ST_VENOM_PAY = 83;
const ST_PILFERING = 58;
const ST_SNAPPING = 59;
const ST_HYPNOSIS = 60;
const ST_SCAVENGING = 61;
const ST_POSTURING = 62;
const ST_PERCEPTION = 63;
const ST_PACK = 64;
const ST_CLEVER = 65;
const ST_BOOST = 66;
const ST_ACTION = 67;
const ST_MULTIPLIER = 68;
const ST_FULL_THROATED = 69;
const ST_ICONIC_ANIMAL = 70;
const ST_RESISTANCE = 71;
const ST_ASSERTION = 72;
const ST_SPONSOR_MAGNET = 73;
const ST_CONSTRICTION = 74;
const ST_DETERMINATION = 75;
const ST_PEACOCKING = 76;
const ST_PETTING_ZOO_ANIMAL = 77;
const ST_DOMINANCE = 78;
const ST_PILFERING_EXECUTE = 79;
const ST_GAIN_PARTNER_ZOO = 80;
const ST_GAIN_UNIVERSITY = 81;
const ST_BUY_SPONSOR = 82;
const ST_MAP4 = 84;
const ST_MAP8 = 85;
const ST_MONEY_INCOME = 86;
const ST_WAZA_SPECIAL = 87;
const ST_MAP9 = 101;
const ST_MAP10 = 102;
const ST_ARCHEOLOGIST_BONUS = 126;
// MW
const ST_MARK = 103;
const ST_TRADE = 104;
const ST_SEA_ANIMAL_MAGNET = 105;
const ST_SYMBIOSIS = 106;
const ST_SEARCH_CARD = 107;
const ST_EXTRA_SHIFT = 108;
const ST_GLIDE = 110;
const ST_SHARK_ATTACK = 111;
const ST_CUT_DOWN = 112;
const ST_CAMOUFLAGE = 113;
const ST_SCUBA_DIVE = 114;
const ST_ADAPT = 115;
const ST_MONKEY_GANG = 116;
const ST_DONATE = 117;
const ST_EXPEDITION = 118;
const ST_SEARCH_PET_DISCARD = 119;
const ST_RECONSTRUCTION_REMOVE = 120;
const ST_RECONSTRUCTION_PLACE_BACK = 121;
const ST_INCREASE_SIZE = 133;
const ST_GAIN_MARKED = 109;

const ST_ANIMALS2_HUNTER = 130;
const ST_ANIMALS3_PAYGAIN = 131;
const ST_SPONSORS_DISCARD_CARD_GET_BONUS = 132;

// MAP PACK 2
const ST_ANIMAL_MAGNET = 123;
const ST_WAVE = 124;
const ST_FREE_PERSON_SPONSOR = 125;
const ST_MAP11_STORE = 127;
const ST_MAP11_UNSTORE = 129;
const ST_MAP13_INCOME = 128;

const ST_CLEANUP = 88;
const ST_RESOLVE_STACK = 90;
const ST_RESOLVE_CHOICE = 91;
const ST_IMPOSSIBLE_MANDATORY_ACTION = 92;
const ST_CONFIRM_TURN = 93;
const ST_CONFIRM_PARTIAL_TURN = 94;

const ST_GENERIC_NEXT_PLAYER = 97;
const ST_PRE_END_OF_GAME = 98;
const ST_END_GAME = 99;

// prettier-ignore
const MAP4_FORBIDDEN = [ST_CONFIRM_PARTIAL_TURN, ST_DISCARD_SCORING, ST_MAP4, ST_BREAK_MULTIACTIVE, ST_BREAK_CARDS, ST_BREAK_REFILL, ST_BREAK_INCOME, ST_BREAK_FINISH, ST_CARDS, ST_DISCARD, ST_SPRINT, ST_INVENTIVE, ST_DIGGING, ST_PILFERING, ST_PILFERING_EXECUTE, ST_HUNTER, ST_SCAVENGING, ST_SNAPPING, ST_SPONSOR_MAGNET, ST_SUNBATHING, ST_PERCEPTION, ST_SCUBA_DIVE];
const MAP11_FORBIDDEN = [ST_MAP11_STORE, ST_MAP11_UNSTORE, ST_DISCARD_SCORING];

/*
 * ENGINE
 */
const NODE_SEQ = 'seq';
const NODE_OR = 'or';
const NODE_XOR = 'xor';
const NODE_PARALLEL = 'parallel';
const NODE_LEAF = 'leaf';

const ZOMBIE = 98;
const PASS = 99;

const AFTER_FINISHING_ACTION = 'afterFinishing';
/*
 * Bonuses
 */
const BONUS_SPONSOR = 'bonus-sponsor';
const BONUS_WORKER = 'add-worker';
const BONUS_SIZE_1_ENCLOSURE = 'size-1';
const BONUS_SIZE_2_ENCLOSURE = 'size-2';
const BONUS_SIZE_3_ENCLOSURE = 'size-3';
const BONUS_SIZE_5_ENCLOSURE = 'size-5';
const BONUS_UPGRADE_CARD = 'upgrade-card';
const BONUS_SPECIAL_ENCLOSURES = 'special-enclosures';
const BONUS_SPECIAL_ENCLOSURES_WITH_AQUARIUM = 'special-enclosures-aquarium';
const TAKE_IN_RANGE_OR_DECK = 'take-in-range-or-deck';
const BONUS_KIOSK = 'bonus_kiosk';

const KIOSK = 'kiosk';
const PAVILION = 'pavilion';
const KIOSK_OR_PAVILION = 'kiosk-pavilion';


// MW
const BONUS_IGNORE_CONDITION = 'bonus-ignore-conditions';
const BONUS_RETURN_WORKER = 'bonus-extra-shift';
const BONUS_SNAP_CARDLIMIT = 'bonus-increased-hand';
const BONUS_KIOSK_PAVILION = 'bonus-kiosk-pavilion';
const BONUS_FINAL_SCORING = 'bonus-scoring-cards';
const BONUS_ICON_SUPPORT_PROJECT = 'bonus-icon';
const BONUS_SPONSOR_MONEY_MW = 'bonus-sponsor-gray';

// MAP PACK 2
const BONUS_WAVE = 'wave';
const BONUS_FREE_SPONSOR_PERSON = 'sponsor-person-card';
const BONUS_STRENGTH = 'strength';

const SPONSOR_PERSON = 'sponsor-person';
const MAP_13_INCOME = 'MAP_13_INCOME';

/*
 * Atomic action
 */

const ACTIVATE_CARD = 'ACTIVATE_CARD';
const SPECIAL_EFFECT = 'SPECIAL_EFFECT';
const CHOOSE_ACTION_CARD = 'CHOOSE_ACTION_CARD';
const GAIN = 'GAIN';
const ADVANCE_BREAK = 'ADVANCE_BREAK';
const PAY = 'PAY';
const CLEANUP = 'CLEANUP';
const DISCARD = 'DISCARD';
const UPGRADE_CARD = 'UPGRADE_CARD';
const DISCARD_SCORING = 'DISCARD_SCORING';
const DISCARD_SCORING_MULTI = 'DISCARD_SCORING_MULTI';
const RELEASE = 'RELEASE';
const TAKE_BONUS = 'TAKE_BONUS';
const USE_KEPT_BONUS = 'USE_KEPT_BONUS';
const REMOVE_BONUS = 'REMOVE_BONUS';
const MOVE_ANIMALS = 'MOVE_ANIMALS';
const GAIN_UNIVERSITY = 'GAIN_UNIVERSITY';
const GAIN_PARTNER_ZOO = 'GAIN_PARTNER_ZOO';
const BUY_SPONSOR = 'BUY_SPONSOR';
const VENOM_PAY = 'VENOM_PAY';
const WAZA_SPECIAL = 'WAZA_SPECIAL';
const ARCHEOLOGIST_BONUS = 'ARCHEOLOGIST_BONUS';

// MW
const SEARCH_CARD = 'SEARCH_CARD';
const GAIN_MARKED = 'GAIN_MARKED';
const DONATE = 'DONATE';
const EXPEDITION = 'EXPEDITION';
const SEARCH_PET_DISCARD = 'SEARCH_PET_DISCARD';
const RECONSTRUCTION_REMOVE = 'RECONSTRUCTION_REMOVE';
const RECONSTRUCTION_PLACE_BACK = 'RECONSTRUCTION_PLACE_BACK';
const INCREASE_SIZE = 'INCREASE_SIZE';


const ANIMALS2_HUNTER = 'ANIMALS2_HUNTER';
const ANIMALS3_PAYGAIN = 'ANIMALS3_PAYGAIN';
const SPONSORS_DISCARD_CARD_GET_BONUS = 'SPONSORS_DISCARD_CARD_GET_BONUS';

// MAP PACK 2
const STORE = 'STORE';
const UNSTORE = 'UNSTORE';

/*
 * Resources
 */
const XTOKEN = 'xtoken';
const MONEY = 'money';
const SCORE = 'score';
const REPUTATION = 'reputation';
const CONSERVATION = 'conservation';
const APPEAL = 'appeal';
const WORKER = 'worker';
const TOKEN = 'token';

/*
 * MISC
 */
const INCOME = 'income';
const IMMEDIATE = 'immediate';
const ENDGAME = 'endgame';
const PASSIVE = 'passive';
const BONUS = 'bonus';

const MY_ZOO = 'my-zoo';
const ALL_ZOO = 'all-zoo';

const EVERYONE_ELSE = 'everyone-else';

const ACTIVE = 1;
const INACTIVE = 0;

const SPONSOR_CARD_WITH_ICON_BONUS = ['S215_BreedingCooperation', 'S218_BreedingProgram'];

const INFTY = 100;
const CORNER = ['x' => 0, 'y' => 11];

const PRE_ACTION_DONE = 'preActionDone';

const MAX_REP_BONUS_SLOT = 99;

const POUCHED = 100;

/******************
 ****** CARDS ******
 ******************/

// Action Cards
const ASSOCIATION = 'Association';
const ANIMALS = 'Animals';
const SPONSORS = 'Sponsors';
const CARDS = 'Cards';
const BUILD = 'Build';

// Card types
const CARD_ANIMAL = 'animal';
const CARD_SPONSOR = 'sponsor';
const CARD_PROJECT = 'project';
const CARD_BASE_PROJECT = 'baseProject';
const CARD_SCORING = 'scoring';

// Continent
const AFRICA = 'Africa';
const EUROPE = 'Europe';
const ASIA = 'Asia';
const AMERICAS = 'Americas';
const AUSTRALIA = 'Australia';
const CONTINENTS = [AFRICA, EUROPE, ASIA, AMERICAS, AUSTRALIA];

// Animal type
const BIRD = 'Bird';
const PREDATOR = 'Predator';
const HERBIVORE = 'Herbivore';
const BEAR = 'Bear';
const REPTILE = 'Reptile';
const PET = 'Pet';
const PRIMATE = 'Primate';
const SEA_ANIMAL = 'SeaAnimal';
const ANIMAL_TYPES = [BIRD, PREDATOR, HERBIVORE, BEAR, REPTILE, PET, PRIMATE, SEA_ANIMAL];

const CONTINENTS_AND_TYPES = [AFRICA, EUROPE, ASIA, AMERICAS, AUSTRALIA, BIRD, PREDATOR, HERBIVORE, BEAR, REPTILE, PET, PRIMATE, SEA_ANIMAL];

// Enclosure requirement
const ROCK = 'Rock';
const WATER = 'Water';
const REPTILE_HOUSE = 'reptile-house';
const LARGE_BIRD_AVIARY = 'large-bird-aviary';
const PETTING_ZOO = 'petting-zoo';
const AQUARIUM = 'aquarium';
const LARGE_AQUARIUM = 'large-aquarium';
const SMALL_AQUARIUM = 'small-aquarium';

// Prerequisite
const PARTNER_ZOO = 'Partner-Zoo';
const UPGRADED_ANIMALS_CARD = 'AnimalsII';
const UPGRADED_CARDS_CARD = 'CardsII';
const UPGRADED_SPONSORS_CARD = 'SponsorsII';
const MAX_25_APPEAL = 'Appeal';
const SCIENCE = 'Science';
const UNIVERSITY = 'Fac';
const ALL_PREREQUISITES = [
  BIRD,
  PREDATOR,
  HERBIVORE,
  BEAR,
  REPTILE,
  PET,
  PRIMATE,
  AFRICA,
  EUROPE,
  ASIA,
  AMERICAS,
  AUSTRALIA,
  PARTNER_ZOO,
  UPGRADED_ANIMALS_CARD,
  UPGRADED_CARDS_CARD,
  SCIENCE,
  UNIVERSITY,
  ROCK,
  WATER,
  SEA_ANIMAL
];

const SCIENCE_SCIENCE = 'ScienceScience';

// Universities
const UNIVERSITY_REP_HAND = 'fac-rep-hand';
const UNIVERSITY_SCIENCE_REP = 'fac-science-rep';
const UNIVERSITY_SCIENCE_SCIENCE = 'fac-science-science';
const UNIVERSITIES = [UNIVERSITY_REP_HAND, UNIVERSITY_SCIENCE_REP, UNIVERSITY_SCIENCE_SCIENCE];
const UNIVERSITY_SCIENCE_ANIMAL_GEN = 'fac-generic';
const UNIVERSITIES_MARINE_WORLD = [UNIVERSITY_REP_HAND, UNIVERSITY_SCIENCE_REP, UNIVERSITY_SCIENCE_SCIENCE, UNIVERSITY_SCIENCE_ANIMAL_GEN];
const UNIVERSITY_SCIENCE_BIRD = 'fac-science-bird';
const UNIVERSITY_SCIENCE_PREDATOR = 'fac-science-predator';
const UNIVERSITY_SCIENCE_PRIMATE = 'fac-science-primate';
const UNIVERSITY_SCIENCE_HERBIVORE = 'fac-science-herbivore';
const UNIVERSITY_SCIENCE_REPTILE = 'fac-science-reptile';
const UNIVERSITY_SCIENCE_SEA_ANIMAL = 'fac-science-marine';
const UNIVERSITIES_ANIMALS = [UNIVERSITY_SCIENCE_BIRD, UNIVERSITY_SCIENCE_PREDATOR, UNIVERSITY_SCIENCE_PRIMATE, UNIVERSITY_SCIENCE_HERBIVORE, UNIVERSITY_SCIENCE_REPTILE, UNIVERSITY_SCIENCE_SEA_ANIMAL];

const UNIVERSITIES_ICONS = [
  UNIVERSITY_REP_HAND => [],
  UNIVERSITY_SCIENCE_REP => [SCIENCE => 1],
  UNIVERSITY_SCIENCE_SCIENCE => [SCIENCE => 2],
  UNIVERSITY_SCIENCE_BIRD => [SCIENCE => 1, BIRD => 1],
  UNIVERSITY_SCIENCE_PRIMATE => [SCIENCE => 1, PRIMATE => 1],
  UNIVERSITY_SCIENCE_REPTILE => [SCIENCE => 1, REPTILE => 1],
  UNIVERSITY_SCIENCE_PREDATOR => [SCIENCE => 1, PREDATOR => 1],
  UNIVERSITY_SCIENCE_HERBIVORE => [SCIENCE => 1, HERBIVORE => 1],
  UNIVERSITY_SCIENCE_SEA_ANIMAL => [SCIENCE => 1, SEA_ANIMAL => 1],
];


// Ability
const SPRINT = 'Sprint';
const HUNTER = 'Hunter';
const INVENTIVE = 'Inventive';
const JUMPING = 'Jumping';
const SUNBATHING = 'Sunbathing';
const POUCH = 'Pouch';
const FLOCK_ANIMAL = 'FlockAnimal';
const DIGGING = 'Digging';
const VENOM = 'Venom';
const PILFERING = 'Pilfering';
const PILFERING_EXECUTE = 'PilferingExecute';
const SNAPPING = 'Snapping';
const HYPNOSIS = 'Hypnosis';
const SCAVENGING = 'Scavenging';
const POSTURING = 'Posturing';
const PERCEPTION = 'Perception';
const PACK = 'Pack';
const CLEVER = 'Clever';
const BOOST = 'Boost';
const ACTION = 'Action';
const MULTIPLIER = 'Multiplier';
const FULL_THROATED = 'FullThroated';
const ICONIC_ANIMAL = 'IconicAnimal';
const RESISTANCE = 'Resistance';
const ASSERTION = 'Assertion';
const SPONSOR_MAGNET = 'SponsorMagnet';
const CONSTRICTION = 'Constriction';
const DETERMINATION = 'Determination';
const PEACOCKING = 'Peacocking';
const PETTING_ZOO_ANIMAL = 'PettingZooAnimal';
const DOMINANCE = 'Dominance';
const MAP4 = 'map4';
const MAP8 = 'map8';
const MAP9 = 'map9';
const MAP10 = 'map10';
const MONEY_INCOME = 'MoneyIncome';

// Marine world effects
const REEF = 'Reef';
const ADAPT = 'Adapt';
const CAMOUFLAGE = 'Camouflage';
const CUT_DOWN = 'CutDown';
const EXTRA_SHIFT = 'ExtraShift';
const GLIDE = 'Glide';
const HELPFUL = 'Helpful';
const MARK = 'Mark';
const MARKETING = 'Marketing';
const MONKEY_GANG = 'MonkeyGang';
const SCUBA_DIVE = 'ScubaDive';
const SEA_ANIMAL_MAGNET = 'SeaAnimalMagnet';
const SHARK_ATTACK = 'SharkAttack';
const SYMBIOSIS = 'Symbiosis';
const TRADE = 'Trade';

// MAP PACK 2
const ANIMAL_MAGNET = 'AnimalMagnet';

// Type of conservation project
const PROJECT_ICONS = 'icons';
const PROJECT_DIFFERENT_ICONS = 'differentIcons';
const PROJECT_RELEASE = 'release';
const PROJECT_BREED = 'breed';
const PROJECT_PLAN = 'plan';

/************************
 ****** ENCLOSURES ******
 ************************/
const UPGRADED_BUILD_CARD = 'BuildII';
const SIDE_ENTRANCE = 'entrance';
const UNDERWATER_TUNNEL = 'underwater-tunnel';

const REGULAR_ENCLOSURES = ['size-1', 'size-2', 'size-3', 'size-4', 'size-5'];
const SPECIAL_ENCLOSURES = [LARGE_BIRD_AVIARY, PETTING_ZOO, REPTILE_HOUSE, SMALL_AQUARIUM, LARGE_AQUARIUM, UNDERWATER_TUNNEL];
const ENCLOSURES = ['size-1', 'size-2', 'size-3', 'size-4', 'size-5', LARGE_BIRD_AVIARY, PETTING_ZOO, REPTILE_HOUSE, SMALL_AQUARIUM, LARGE_AQUARIUM, UNDERWATER_TUNNEL];
const UNIQUE_BUILDINGS = [
  'monkey',
  'meerkat',
  'owl',
  'sea-turtle',
  'okapi',
  'adventure',
  'penguin',
  'aquarium',
  'polar-bear',
  'hyena',
  'zoo-school',
  'baboon',
  'water-playground',
  SIDE_ENTRANCE,
  'cable',
];
const BUILDINGS = [
  'size-1' => [[0, 0]],
  'size-2' => [[0, 0], [0, 2]],
  'size-3' => [[0, 0], [0, 2], [1, 1]],
  'size-4' => [[0, 0], [0, -2], [1, -1], [1, 1]],
  'size-5' => [[0, 0], [-1, -1], [-1, 1], [0, -2], [0, 2]],
  LARGE_BIRD_AVIARY => [[0, 0], [-1, -1], [-1, 1], [0, 2], [1, -1]],
  PETTING_ZOO => [[0, 0], [0, 2], [1, -1]],
  REPTILE_HOUSE => [[0, 0], [-1, -1], [-1, 1], [1, -1], [1, 1]],
  'kiosk' => [[0, 0]],
  'monkey' => [[0, 0], [-1, -1], [0, 2], [0, 4]],
  'meerkat' => [[0, 0], [-1, 1], [1, 1]],
  'owl' => [[0, 0], [0, -2], [0, 2]],
  'sea-turtle' => [[0, 0], [-1, 1], [0, -2], [1, 1]],
  'okapi' => [[0, 0], [-1, 1], [-2, 0], [1, 1]],
  'adventure' => [[0, 0], [0, 2]],
  'pavilion' => [[0, 0]],
  'penguin' => [[0, 0], [-1, -1], [1, -1], [1, 1]],
  'aquarium' => [[0, 0], [-1, 1], [1, 1], [1, 3]],
  'polar-bear' => [[0, 0], [-1, 1], [1, 1], [2, 0]],
  'hyena' => [[0, 0], [0, 2], [0, 4], [1, -1]],
  'zoo-school' => [[0, 0], [-1, 1], [1, 1]],
  'baboon' => [[0, 0], [-1, 1], [0, -2], [0, 2]],
  'water-playground' => [[0, 0], [1, 1]],
  SIDE_ENTRANCE => [[0, 0], [1, 1]],
  'cable' => [[0, 0], [0, -2], [0, 2], [0, 4]],
  'arcade' => [[0, 0]],
  'victory' => [[0, 0]],

  LARGE_AQUARIUM => [[0, 0], [1, -3], [1, -1], [1, 1], [1, 3],],
  SMALL_AQUARIUM => [[0, 0], [0, 2],],
  'mascot' => [[0, 0],],
  'amazon' => [[0, 0], [-1, -1], [1, -1], [1, 1], [2, -2],],
  'excavation' => [[0, 0], [-1, 1], [1, 1], [2, 0], [2, -2],],
  UNDERWATER_TUNNEL => [[0, 0], [0, 2],],

  // FAKE TYPES FOR BUILD1 and BUILD2
  'pavilion1' => [],
  'kiosk2' => [],
];

const BUILDINGS_BY_SHAPES = [
  ['size-1', KIOSK, PAVILION, 'arcade', 'victory', 'mascot'],
  ['size-2', 'adventure', 'entrance', SMALL_AQUARIUM, UNDERWATER_TUNNEL, 'water-playground'],
  ['size-3'],
  ['size-4'],
  ['size-5'],
  [LARGE_BIRD_AVIARY],
  [PETTING_ZOO, 'meerkat', 'zoo-school'],
  [REPTILE_HOUSE],
  ['monkey'],
  ['owl'],
  ['sea-turtle'],
  ['okapi'],
  ['penguin'],
  ['aquarium'],
  ['polar-bear'],
  ['hyena'],
  ['baboon'],
  ['cable'],
  [LARGE_AQUARIUM],
  ['amazon'],
  ['excavation']
];


const BUILDINGS_CONSTRAINTS = [
  'meerkat' => [ROCK => 1],
  'sea-turtle' => [WATER => 1],
  'adventure' => [ROCK => 1],
  'penguin' => [WATER => 1],
  'aquarium' => [WATER => 2],
  'polar-bear' => [WATER => 1],
  'hyena' => [ROCK => 1],
  'baboon' => [ROCK => 1],
  'water-playground' => [WATER => 1],
  'cable' => [ROCK => 2],
  'small-aquarium' => [WATER => 1],
  'large-aquarium' => [WATER => 1],

  'amazon' => [ROCK => 1, WATER => 1],
];


// ENCLOSURE TYPES
// const AQUARIUMS = [LARGE_AQUARIUM, SMALL_AQUARIUM, 'underwater-tunnel'];
const REGULAR_ENCLOSURE_TYPE = 'regular';
const SPECIAL_ENCLOSURE_TYPE = 'special';
const ENCLOSURE_TYPES_MAP = [
  REGULAR_ENCLOSURE_TYPE => REGULAR_ENCLOSURES,
  LARGE_BIRD_AVIARY => [LARGE_BIRD_AVIARY],
  PETTING_ZOO => [PETTING_ZOO],
  REPTILE_HOUSE => [REPTILE_HOUSE],
  AQUARIUM => [LARGE_AQUARIUM, SMALL_AQUARIUM, UNDERWATER_TUNNEL]
];

/******************
 *** BONUS TILES ***
 ******************/
const BONUS_TILES = [
  [MONEY => 10],
  [BONUS_SIZE_3_ENCLOSURE => 1],
  [REPUTATION => 2],
  [XTOKEN => 3],
  [TAKE_IN_RANGE_OR_DECK => 3],
  [PARTNER_ZOO => 1],
  [UNIVERSITY => 1],
  [MULTIPLIER => 1],
  [BONUS_SPONSOR => 1],
];

const BONUS_TILES_MARINE  = [
  [MONEY => 10],
  [BONUS_SIZE_3_ENCLOSURE => 1],
  [REPUTATION => 2],
  [XTOKEN => 3],
  [TAKE_IN_RANGE_OR_DECK => 3],
  [PARTNER_ZOO => 1],
  [UNIVERSITY => 1],
  [MULTIPLIER => 1],
  //  [BONUS_SPONSOR => 1], => REPLACED IN MW BY THE NEXT ONE!
  [BONUS_SPONSOR_MONEY_MW => 1],
  [BONUS_IGNORE_CONDITION => 3],
  [BONUS_RETURN_WORKER => 1],
  [BONUS_ICON_SUPPORT_PROJECT => 1],
  [BONUS_KIOSK_PAVILION => 3],
  [BONUS_FINAL_SCORING => 3],
  [BONUS_SNAP_CARDLIMIT => 1],
];

const KEEPER_BONUS_TILES = [
  BONUS_SPONSOR_MONEY_MW,
  BONUS_IGNORE_CONDITION,
  BONUS_RETURN_WORKER,
  BONUS_ICON_SUPPORT_PROJECT,
  BONUS_SNAP_CARDLIMIT,
];


/******************
 ****** MAPS ******
 ******************/
const ADVANCED_MAPS = [1, 2, 3, 4, 5, 6, 7, 8];
const ALL_MAPS = ['A', 0, 1, 2, 3, 4, 5, 6, 7, 8];

const ALTERNATIVE_MAPS = ['1a', '2a', '3a', '4a', '5a', '6a', '7a', '8a'];

/******************
 ****** STATS ******
 ******************/

const STAT_BREAKS = 10;

const STAT_POSITION = 11;
const STAT_TURN = 12;
const STAT_BREAKS_TRIGGERED = 13;
const STAT_END_GAME_TRIGGERED = 14;
const STAT_MAP = 15;

const STAT_APPEAL = 16;
const STAT_CONSERVATION = 17;
const STAT_SCORE = 18;
const STAT_REPUTATION = 19;

// Number of times each action was taken
const STAT_BUILD_ACTION = 20;
const STAT_ANIMALS_ACTION = 21;
const STAT_CARDS_ACTION = 22;
const STAT_ASSOCIATION_ACTION = 23;
const STAT_SPONSORS_ACTION = 24;

// X-tokens gained, spent
const STAT_XTOKEN_GAINED = 25;
const STAT_XTOKEN_GAINED_ACTION = 26;
const STAT_XTOKEN_USED = 27;

// Money gained, used
const STAT_MONEY_GAINED = 30;
const STAT_INCOME_TOTAL = 31;
const STAT_MONEY_USED_ANIMALS = 32;
const STAT_MONEY_USED_BUILD = 33;
const STAT_MONEY_USED_DONATIONS = 34;
const STAT_MONEY_USED_FROM_DISPLAY = 35;

// Card drawn, played, discarded
const STAT_CARDS_DRAWN = 40;
const STAT_CARDS_TAKEN = 41;
const STAT_CARDS_SNAPPED = 42;
const STAT_CARDS_DISCARDED = 43;
const STAT_SPONSORS_PLAYED = 44;
const STAT_ANIMALS_PLAYED = 45;
const STAT_ANIMALS_RELEASED = 46;

// Number of association workes, numbers of association taks of each type
const STAT_ASSOCIATION_WORKERS = 50;
const STAT_ASSOCIATION_DONATION = 51;
const STAT_ASSOCIATION_REPUTATION = 52;
const STAT_ASSOCIATION_PARTNER = 53;
const STAT_ASSOCIATION_UNIVERSITY = 54;
const STAT_ASSOCIATION_CONSERVATION = 55;

// Map stats
const STAT_BUILD_ENCLOSURES = 60;
const STAT_BUILD_KIOSKS = 61;
const STAT_BUILD_PAVILIONS = 62;
const STAT_BUILD_STRUCTURES = 63;
const STAT_COVERED_HEXES = 64;
const STAT_EMPTY_HEXES = 65;

// Upgraded action cards
const STAT_CARDS_UPGRADED = 70;
const STAT_CARD_UPGRADED_ANIMALS = 71;
const STAT_CARD_UPGRADED_BUILD = 72;
const STAT_CARD_UPGRADED_CARDS = 73;
const STAT_CARD_UPGRADED_SPONSORS = 74;
const STAT_CARD_UPGRADED_ASSOCATION = 75;

// Icons
const STAT_ICON_AFRICA = 76;
const STAT_ICON_EUROPE = 77;
const STAT_ICON_ASIA = 78;
const STAT_ICON_AUSTRALIA = 79;
const STAT_ICON_AMERICAS = 80;
const STAT_ICON_BIRD = 81;
const STAT_ICON_PREDATOR = 82;
const STAT_ICON_HERBIVORE = 83;
const STAT_ICON_BEAR = 84;
const STAT_ICON_REPTILE = 85;
const STAT_ICON_PRIMATE = 86;
const STAT_ICON_PET = 97;
const STAT_ICON_WATER = 88;
const STAT_ICON_ROCK = 89;
const STAT_ICON_SCIENCE = 90;
const STAT_ICON_SEA_ANIMAL = 91;

// MW DRAFT
const STAT_DRAFTED_1 = 92;
const STAT_DRAFTED_2 = 93;
const STAT_DRAFTED_3 = 94;
const STAT_ACTION_CARD_ANIMAL = 95;
const STAT_ACTION_CARD_BUILD = 96;
const STAT_ACTION_CARD_ASSOCIATION = 87;
const STAT_ACTION_CARD_CARDS = 98;
const STAT_ACTION_CARD_SPONSORS = 99;
