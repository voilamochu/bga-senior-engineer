
-- ------
-- BGA framework: © Gregory Isabelli <gisabelli@boardgamearena.com> & Emmanuel Colin <ecolin@boardgamearena.com>
-- arnak implementation : © Adam Spanel adam.spanel@seznam.cz
-- 
-- This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
-- See http://en.boardgamearena.com/#!doc/Studio for more information.
-- -----

-- dbmodel.sql

-- This is the file where you are describing the database schema of your game
-- Basically, you just have to export from PhpMyAdmin your table structure and copy/paste
-- this export here.
-- Note that the database itself and the standard tables ("global", "stats", "gamelog" and "player") are
-- already created and must not be created here

-- Note: The database schema is created from this file when the game starts. If you modify this file,
--       you have to restart a game to see your changes in database.


CREATE TABLE IF NOT EXISTS `research_bonus` (
  `idresearch_bonus` INT NOT NULL AUTO_INCREMENT,
  `bonus_type` VARCHAR(10),
  `track_pos` INT NOT NULL,
  PRIMARY KEY (`idresearch_bonus`)
)
ENGINE = InnoDB;

ALTER TABLE `player` 
  ADD `coins` INT NOT NULL DEFAULT 0,
  ADD `compass` INT NOT NULL DEFAULT 0,
  ADD `tablet` INT NOT NULL DEFAULT 0,
  ADD `arrowhead` INT NOT NULL DEFAULT 0,
  ADD `jewel` INT NOT NULL DEFAULT 0,
  ADD `idol_slot` INT NOT NULL DEFAULT 0,
  ADD `idol` INT NOT NULL DEFAULT 0,
  ADD `research_glass` INT NOT NULL DEFAULT 0,
  ADD `research_book` INT NOT NULL DEFAULT 0,
  ADD `temple_rank` INT,
  ADD `boots` INT NOT NULL DEFAULT 0,
  ADD `ships` INT NOT NULL DEFAULT 0,
  ADD `cars` INT NOT NULL DEFAULT 0,
  ADD `planes` INT NOT NULL DEFAULT 0,
  ADD `temple_bronze` INT NOT NULL DEFAULT 0,
  ADD `temple_silver` INT NOT NULL DEFAULT 0,
  ADD `temple_gold` INT NOT NULL DEFAULT 0,
  ADD `passed` INT NOT NULL DEFAULT false;

CREATE TABLE IF NOT EXISTS `card` (
  `idcard` INT NOT NULL AUTO_INCREMENT,
  `player` INT NULL,
  `card_position` ENUM('hand', 'deck', 'play', 'discard', 'supply', 'earring', 'keep') NULL,
  `num` INT NULL,
  `card_type` ENUM("fear", "item", "art", "fundship", "fundcar", "exploreship", "explorecar"), 
  `deck_order` INT NULL,
  PRIMARY KEY (`idcard`))
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `board_position` (
  `idboard_position` INT NOT NULL,
  `slot1` INT NULL,
  `slot2` INT NULL,
  `idol_bonus` ENUM("coins", "compass", "tablet", "exile", "upgrade", "refresh") NULL,
  PRIMARY KEY (`idboard_position`))
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `location` (
  `idlocation` INT NOT NULL AUTO_INCREMENT,
  `size` ENUM('basic', 'small', 'big'),
  `num` INT NULL,
  `is_open` TINYINT NULL,
  `is_at_position` INT NULL,
  `deck_order` INT NULL,
  PRIMARY KEY (`idlocation`))
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `assistant` (
  `idassistant` INT NOT NULL AUTO_INCREMENT,
  `gold` TINYINT NOT NULL,
  `ready` TINYINT NOT NULL,
  `num` INT NOT NULL,
  `in_offer` INT NULL,
  `offer_order` INT NULL,
  `in_hand` INT NULL,
  PRIMARY KEY (`idassistant`))
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `guardian` (
  `idguardian` INT NOT NULL AUTO_INCREMENT,
  `num` INT NOT NULL,
  `in_hand` INT NULL,
  `ready` TINYINT NULL,
  `at_location` INT NULL,
  `deckorder` INT NULL,
  PRIMARY KEY (`idguardian`))
ENGINE = InnoDB;

CREATE TABLE IF NOT EXISTS `temple_tile` (
  `idtemple_tile` INT NOT NULL AUTO_INCREMENT,
  `amt` INT NOT NULL,
  PRIMARY KEY (`idtemple_tile`))
ENGINE = InnoDB;
