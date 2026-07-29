<?php

namespace ARK\Helpers;

use ARK\Core\Game;

class CachedDB_Manager extends DB_Manager
{
  protected static ?Collection $datas = null;
  public static function fetchIfNeeded()
  {
    if (is_null(static::$datas)) {
      static::$datas = static::DB()->get();
    }
  }

  public static function invalidate()
  {
    static::$datas = null;
  }

  public static function getAll(): Collection
  {
    self::fetchIfNeeded();
    return static::$datas;
  }

  public static function get($id)
  {
    return self::getAll()
      ->filter(function ($obj) use ($id) {
        return $obj->getId() == $id;
      })
      ->first();
  }
}
