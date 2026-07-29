<?php

namespace ARK\Managers;

/* Class to manage all the Buildings for ArkNova */

class Buildings extends \ARK\Helpers\Pieces
{
  protected static string $table = 'buildings';
  protected static string $prefix = 'building_';
  protected static array $customFields = ['type', 'player_id', 'x', 'y', 'rotation'];

  protected static function cast(array $building): array
  {
    return [
      'id' => (int) $building['id'],
      'location' => $building['location'],
      'state' => $building['state'],
      'pId' => (int) $building['player_id'],
      'type' => $building['type'],
      'x' => (int) $building['x'],
      'y' => (int) $building['y'],
      'rotation' => (int) $building['rotation'],
    ];
  }

  public static function setupNextGame()
  {
    // deletion of all
    self::DB()
      ->delete()
      ->run();
  }

  public static function getUiData()
  {
    return self::getAll()->toArray();
  }

  public static function getOfPlayer($pId)
  {
    return self::getSelectQuery()
      ->wherePlayer($pId)
      ->get();
  }

  public static function add($pId, $type, $pos, $rotation)
  {
    return self::singleCreate([
      'location' => 'board',
      'player_id' => $pId,
      'type' => $type,
      'x' => $pos['x'],
      'y' => $pos['y'],
      'rotation' => $rotation,
    ]);
  }

  public static function remove($bId)
  {
    self::DB()->delete()->where('building_id', $bId)->run();
  }

  public static function moveOnHold($buildingIds)
  {
    self::getUpdateQuery($buildingIds)->update(['building_location' => 'hold', 'player_id' => null])->run();
  }

  public static function placeBack(int $buildingId, int $pId, array $pos, int $rotation)
  {
    self::getUpdateQuery($buildingId)->update([
      'building_location' => 'board',
      'player_id' => $pId,
      'x' => $pos['x'],
      'y' => $pos['y'],
      'rotation' => $rotation,
    ])->run();
    return self::getSingle($buildingId);
  }
}
