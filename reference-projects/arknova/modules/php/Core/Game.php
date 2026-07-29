<?php
namespace ARK\Core;
use arknova;

/*
 * Game: a wrapper over table object to allow more generic modules
 */
class Game
{
  public static function get()
  {
    return arknova::get();
  }
}
