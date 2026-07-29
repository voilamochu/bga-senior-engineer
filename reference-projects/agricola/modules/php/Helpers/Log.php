<?php
namespace AGR\Helpers;
use AGR\Core\Game;
use AGR\Core\Globals;
use AGR\Core\Notifications;
use AGR\Managers\Players;

/**
 * Class that allows to log DB change: useful for undo feature
 *
 * Associated DB table :
 *  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
 *  `move_id` int(10),
 *  `table` varchar(32) NOT NULL,
 *  `primary` varchar(32) NOT NULL,
 *  `type` varchar(32) NOT NULL,
 *  `affected` JSON,
 */

class Log extends \APP_DbObject
{
  public static function enable()
  {
    Game::get()->setGameStateValue('logging', 1);
  }

  public static function disable()
  {
    Game::get()->setGameStateValue('logging', 0);
  }

  // Flush only when notifications are pending — an empty flush would create
  // an extra gamelog packet and a 1s pause in archive replay.
  public static function currentMaxPacketId()
  {
    if (Notifications::hasPending()) {
      Game::get()->sendNotifications();
      Notifications::markFlushed();
    }
    $row = self::getObjectFromDB('SELECT MAX(gamelog_packet_id) AS max_id FROM gamelog');
    return $row['max_id'] ?? 0;
  }

  /**
   * Add an entry
   */
  public static function addEntry($entry)
  {
    if (isset($entry['affected'])) {
      $entry['affected'] = \json_encode($entry['affected']);
    }
    if (!isset($entry['table'])) {
      $entry['table'] = '';
    }
    if (!isset($entry['primary'])) {
      $entry['primary'] = '';
    }

    $entry['move_id'] = self::getUniqueValueFromDB('SELECT global_value FROM global WHERE global_id = 3');
    $query = new QueryBuilder('log', null, 'id');
    return $query->insert($entry);
  }

  // Create a new checkpoint : anything before that checkpoint cannot be undo (unless in studio)
  public static function checkpoint()
  {
    self::clearUndoableStepNotifications();
    return self::addEntry([
      'type' => 'checkpoint',
      'affected' => ['snapshot_packet_id' => self::currentMaxPacketId()],
    ]);
  }

  // Create a new step to allow undo step-by-step
  public static function step()
  {
    return self::addEntry([
      'type' => 'step',
      'affected' => ['snapshot_packet_id' => self::currentMaxPacketId()],
    ]);
  }

  // Log the start of engine to allow "restart turn"
  public static function startEngine()
  {
    if (!Globals::isSolo()) {
      self::checkpoint();
    }

    return self::addEntry([
      'type' => 'engine',
      'affected' => ['snapshot_packet_id' => self::currentMaxPacketId()],
    ]);
  }

  // Find the last checkpoint
  public static function getLastCheckpoint($includeEngineStarts = false)
  {
    $query = new QueryBuilder('log', null, 'id');
    $query = $query->select(['id']);
    if ($includeEngineStarts) {
      $query = $query->whereIn('type', ['checkpoint', 'engine']);
    } else {
      $query = $query->where('type', 'checkpoint');
    }

    $log = $query
      ->orderBy('id', 'DESC')
      ->limit(1)
      ->get()
      ->first();

    return is_null($log) ? 1 : $log['id'];
  }

  // True if a full restart-to-turn-start is still possible. Becomes false once
  // a checkpoint lands after the current turn's startEngine (e.g. cross-player
  // commits during multi-player reactions). Used to gate the abandon-stuck-action
  // escape hatch at ST_IMPOSSIBLE_MANDATORY_ACTION.
  public static function canRestartTurn()
  {
    $lastEngine = (new QueryBuilder('log', null, 'id'))
      ->select(['id'])
      ->where('type', 'engine')
      ->orderBy('id', 'DESC')
      ->limit(1)
      ->get()
      ->first();
    if (is_null($lastEngine)) {
      return false;
    }
    $lastCheckpoint = (new QueryBuilder('log', null, 'id'))
      ->select(['id'])
      ->where('type', 'checkpoint')
      ->orderBy('id', 'DESC')
      ->limit(1)
      ->get()
      ->first();
    if (is_null($lastCheckpoint)) {
      return true;
    }
    return intval($lastEngine['id']) >= intval($lastCheckpoint['id']);
  }

  // Find all the moments available to undo
  public static function getUndoableSteps($onlyIds = true)
  {
    $checkpoint = self::getLastCheckpoint();
    $query = new QueryBuilder('log', null, 'id');
    $logs = $query
      ->select(['id', 'move_id'])
      ->where('type', 'step')
      ->where('id', '>', $checkpoint)
      ->orderBy('id', 'DESC')
      ->get();
    return $onlyIds ? $logs->getIds() : $logs;
  }

  /**
   * Revert all the way to the last checkpoint or the last start of turn
   */
  public static function undoTurn()
  {
    $checkpoint = static::getLastCheckpoint(true);
    return self::revertTo($checkpoint);
  }

  /**
   * Revert to a given step (checking first that it exists)
   */
  public static function undoToStep($stepId)
  {
    $query = new QueryBuilder('log', null, 'id');
    $step = $query
      ->where('id', '=', $stepId)
      ->get()
      ->first();
    if (is_null($step)) {
      // Stale client: the undo point was already pruned by a confirm or earlier undo
      throw new \BgaUserException(clienttranslate('You can no longer undo to this step'));
    }

    self::revertTo($stepId - 1);
  }

  /**
   * Revert all the logged changes up to an id.
   *
   * Gamelog cancellation uses a snapshot of MAX(gamelog_packet_id) recorded
   * on the boundary entry — see currentMaxPacketId() for why move_id is
   * unsafe. Legacy entries without a snapshot fall back to the original
   * move_id-based cancel/delete.
   */
  public static function revertTo($id)
  {
    // Find the boundary entry's snapshot. undoTurn passes the
    // checkpoint/engine id directly; undoToStep passes $stepId - 1, so we
    // look forward for the next checkpoint/engine/step entry.
    $boundary = (new QueryBuilder('log', null, 'id'))
      ->whereIn('type', ['checkpoint', 'engine', 'step'])
      ->where('id', '>=', $id)
      ->orderBy('id', 'ASC')
      ->limit(1)
      ->get()
      ->first();

    $snapshotPacketId = null;
    if ($boundary && !empty($boundary['affected'])) {
      $affected = json_decode($boundary['affected'], true);
      $snapshotPacketId = $affected['snapshot_packet_id'] ?? null;
    }

    $logs = (new QueryBuilder('log', null, 'id'))
      ->select(['id', 'table', 'primary', 'type', 'affected', 'move_id'])
      ->where('id', '>', $id)
      ->orderBy('id', 'DESC')
      ->get();

    $moveIds = [];
    foreach ($logs as $log) {
      if (in_array($log['type'], ['step', 'engine'])) {
        continue;
      }

      $log['affected'] = json_decode($log['affected'], true);
      $moveIds[] = intval($log['move_id']);

      foreach ($log['affected'] as $row) {
        $q = new QueryBuilder($log['table'], null, $log['primary']);

        if ($log['type'] != 'create') {
          foreach ($row as $key => $val) {
            if (isset($row[$key])) {
              $row[$key] = str_replace("'", "\\'", \stripcslashes($val));
            }
          }
        }

        // UNDO UPDATE -> NEW UPDATE
        if ($log['type'] == 'update') {
          $q->update($row)->run($row[$log['primary']]);
        }
        // UNDO DELETE -> CREATE
        elseif ($log['type'] == 'delete') {
          $q->insert($row);
        }
        // UNDO CREATE -> DELETE
        elseif ($log['type'] == 'create') {
          $q->delete()->run($row);
        }
      }
    }

    // Clear logs
    $query = new QueryBuilder('log', null, 'id');
    $query
      ->where('id', '>', $id)
      ->delete()
      ->run();

    // Upper bound of the rolled-back range. Recovery notifications emitted
    // below get fresh packet_ids > oldMaxPacketId and must survive.
    $oldMaxPacketId = self::currentMaxPacketId();

    // Cancel the game notifications
    $query = new QueryBuilder('gamelog', null, 'gamelog_packet_id');
    if ($snapshotPacketId !== null) {
      $query
        ->update(['cancel' => 1])
        ->where('gamelog_packet_id', '>', $snapshotPacketId)
        ->where('gamelog_packet_id', '<=', $oldMaxPacketId)
        ->run();
      $notifIds = self::getCanceledNotifIds();
      Notifications::clearTurn(Players::getCurrent(), $notifIds);
    } else if (!empty($moveIds)) {
      $query
        ->update(['cancel' => 1])
        ->whereIn('gamelog_move_id', $moveIds)
        ->run();
      $notifIds = self::getCanceledNotifIds();
      Notifications::clearTurn(Players::getCurrent(), $notifIds);
    }

    Globals::fetch();

    // Notify
    $datas = Game::get()->getAllDatas();
    Notifications::refreshUI($datas);
    foreach (Players::getAll() as $player) {
      Notifications::refreshHand($player, $player->getHand()->ui());
    }

    // Force notif flush to be able to delete "restart turn" notif
    Game::get()->sendNotifications();

    if ($snapshotPacketId !== null) {
      (new QueryBuilder('gamelog', null, 'gamelog_packet_id'))
        ->delete()
        ->where('gamelog_packet_id', '>', $snapshotPacketId)
        ->where('gamelog_packet_id', '<=', $oldMaxPacketId)
        ->run();
    } else if (!empty($moveIds)) {
      $query = new QueryBuilder('gamelog', null, 'gamelog_packet_id');
      $query
        ->delete()
        ->where('gamelog_move_id', '>=', min($moveIds))
        ->run();
    }
  }

  /**
   * getCancelMoveIds : get all cancelled notifs IDs from BGA gamelog, used for styling the notifications on page reload
   */
  protected static function extractNotifIds($notifications)
  {
    $notificationUIds = [];
    foreach ($notifications as $packet) {
      $data = \json_decode($packet['gamelog_notification'], true);
      foreach ($data as $notification) {
        array_push($notificationUIds, $notification['uid']);
      }
    }
    return $notificationUIds;
  }

  public static function getCanceledNotifIds()
  {
    $query = new QueryBuilder('gamelog', null, 'gamelog_packet_id');
    return self::extractNotifIds($query->where('cancel', 1)->get());
  }

  /**
   * clearUndoableStepNotifications : extract and remove all notifications of type 'newUndoableStep' in the gamelog
   */
  public static function clearUndoableStepNotifications($clearAll = false)
  {
    // Get move ids corresponding to last step
    if ($clearAll) {
      $minMoveId = 1;
    } else {
      $moveIds = [];
      foreach (self::getUndoableSteps(false) as $step) {
        $moveIds[] = (int) $step['move_id'];
      }
      if (empty($moveIds)) {
        return;
      }
      $minMoveId = min($moveIds);
    }

    // Get packets
    $query = new QueryBuilder('gamelog', null, 'gamelog_packet_id');
    $packets = $query->where('gamelog_move_id', '>=', $minMoveId)->get();
    foreach ($packets as $packet) {
      $id = $packet['gamelog_packet_id'];

      // Filter notifs based on type
      $data = \json_decode($packet['gamelog_notification'], true);
      $notifs = [];
      $ignored = 0;
      foreach ($data as $notification) {
        if ($notification['type'] != 'newUndoableStep') {
          $notifs[] = $notification;
        } else {
          $ignored++;
        }
      }
      if ($ignored == 0) {
        continue;
      }

      $query = new QueryBuilder('gamelog', null, 'gamelog_packet_id');

      // Delete or update
      if (empty($notifs)) {
        $query->delete($id);
      } else {
        $query->update(['gamelog_notification' => addslashes(json_encode($notifs))], $id);
      }
    }
  }
}
