<?php

namespace ARK\Helpers;

use \ARK\Core\Game;

class DB_Manager extends \APP_DbObject
{
  protected static string $table = '';
  protected static string $primary = '';
  protected static ?bool $log = null;
  protected static function cast(array $row): mixed
  {
    return $row;
  }

  public static function DB(?string $table = null): QueryBuilder
  {
    if (is_null($table)) {
      if (is_null(static::$table)) {
        throw new \feException('You must specify the table you want to do the query on');
      }
      $table = static::$table;
    }

    $log = null;
    if (static::$log ?? Game::get()->getGameStateValue('logging') == 1) {
      $log = new Log(static::$table, static::$primary);
    }
    return new QueryBuilder(
      $table,
      function ($row) {
        return static::cast($row);
      },
      static::$primary,
      $log
    );
  }
}
