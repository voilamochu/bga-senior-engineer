<?php
namespace AGR\Helpers;
use AGR\Managers\PlayerCards;
use AGR\Managers\ActionCards;
use AGR\Managers\Players;

abstract class Utils extends \APP_DbObject
{
  public static function filter(&$data, $filter)
  {
    $data = array_values(array_filter($data, $filter));
  }

  public static function die($args = null)
  {
    if (is_null($args)) {
      throw new \BgaVisibleSystemException(implode('<br>', self::$logmsg));
    }
    throw new \BgaVisibleSystemException(json_encode($args));
  }

  public static function filterExchanges(&$exchanges, $trigger = ANYTIME, $removeAnytime = false)
  {
    self::filter($exchanges, function ($exchange) use ($trigger, $removeAnytime) {
      return (is_null($exchange['triggers']) && ($trigger == ANYTIME || !$removeAnytime)) ||
        (is_array($exchange['triggers']) && in_array($trigger, $exchange['triggers']));
    });
  }

  /**
   * Reduce an array of meeples into a nice associative array $resource => $amount
   */
  public static function reduceResources($meeples)
  {
    $allResources = array_merge(RESOURCES, [FIELD], ROOMS, [SCORE], [FENCE], [STABLE]);
    $t = [];
    foreach ($allResources as $resource) {
      $t[$resource] = 0;
    }

    foreach ($meeples as $meeple) {
      if ($meeple['type'] == 'farmer') {
        continue;
      }

      $t[$meeple['type']]++;
    }

    return $t;
  }

  /**
   * Return a string corresponding to an assoc array of resources
   */
  public static function resourcesToStr($resources)
  {
    $descs = [];
    foreach ($resources as $resource => $amount) {
      if (in_array($resource, ['sources', 'sourcesDesc', 'pId'])) {
        continue;
      }

      if ($amount == 0) {
        continue;
      }
      // strtoupper('roomStone') doesn't match the client's ROOM_STONE log token (Hawktower)
      $descs[] = $amount . '<' . ($resource == 'roomStone' ? 'ROOM_STONE' : strtoupper($resource)) . '>';
    }
    return implode(',', $descs);
  }

  /**
   * Intersect two arrays of obj with keys x,y
   */
  public static function intersectZones($arr1, $arr2)
  {
    return array_values(
      \array_uintersect($arr1, $arr2, function ($a, $b) {
        return $a['x'] == $b['x'] ? $a['y'] - $b['y'] : $a['x'] - $b['x'];
      })
    );
  }

  // $onlyNew allow to distinguish subdivided pastures from fresh new pasture
  public static function diffPastures($newP, $oldP, $onlyNew)
  {
    $pastures = [];
    foreach ($newP as $p1) {
      $found = false;
      foreach ($oldP as $p2) {
        if ($onlyNew || count($p1['nodes']) == count($p2['nodes'])) {
          $allIn = true;
          foreach ($p1['nodes'] as $n) {
            if (!in_array($n, $p2['nodes'])) {
              $allIn = false;
              break;
            }
          }

          if ($allIn) {
            $found = true;
            break;
          }
        }
      }

      if (!$found) {
        $pastures[] = $p1;
      }
    }

    return $pastures;
  }

  public static function formatCost($cost)
  {
    return [
      'trades' => [$cost],
    ];
  }

  public static function formatFee($cost)
  {
    return [
      'fees' => [$cost],
    ];
  }

  public static function addCost(&$costs, $cost, $source = null)
  {
    if ($source != null) {
      $cost['sources'] = [$source];
    }
    $costs['trades'][] = $cost;
  }

  public static function addFees(&$costs, $cost, $source = null)
  {
    if ($source != null) {
      $cost['sources'] = [$source];
    }
    $costs['fees'][] = $cost;
  }

  public static function addBonus(&$costs, $cost, $source = null, $optional = false, $conditions = [])
  {
    if ($source != null) {
      $cost['sources'] = [$source];
    }
    if (!isset($cost['optional'])) {
      $cost['optional'] = $optional;
    }
    if (count($conditions) > 0) {
      $cost['conditions'] = $conditions;
    }

    $costs['bonuses'][] = $cost;
  }

  public static function addBonusChoices(&$costs, $bonuses, $source = null, $optional = false)
  {
    if ($source != null) {
      foreach ($bonuses as &$cost) {
        $cost['sources'] = [$source];
      }
    }
    $costs['bonuses'][] = [
      'optional' => $optional,
      'choices' => $bonuses,
    ];
  }

  /**
   * Given an array [RESOURCE => [RESOURCE => amount, ...] ] , format as a proper exchange
   */
  public static function formatExchange($exchange, $source = '', $triggers = null, $flag = null)
  {
    $key = array_keys($exchange)[0];
    return [
      'source' => $source,
      'flag' => $flag,
      'triggers' => $triggers,
      'max' => $exchange['max'] ?? 9999,
      'from' => [
        $key => $exchange['nb'] ?? 1,
      ],
      'to' => $exchange[$key],
    ];
  }

  /**
   * Wrapper for getting action card : either use actionCards (for usual cases) or playerCards (for C104_Collector)
   */
  public static function getActionCard($id)
  {
    if (strpos($id, '_') === false) {
      return ActionCards::get($id);
    } else {
      return PlayerCards::get($id);
    }
  }

  /**
   * Whether an id (card id or meeple location) refers to an action space
   */
  public static function isActionSpace($id)
  {
    if ($id === null) {
      return false;
    }
    try {
      $card = self::getActionCard($id);
    } catch (\Exception $e) {
      return false;
    }
    if ($card instanceof \AGR\Models\ActionCard) {
      return true;
    }
    if ($card instanceof \AGR\Models\PlayerCard) {
      return $card->isActionCard();
    }
    return false;
  }

  public static function topological_sort($nodeids, $edges)
  {
    $L = $S = $nodes = [];
    foreach ($nodeids as $id) {
      $nodes[$id] = ['in' => [], 'out' => []];
      foreach ($edges as $e) {
        if ($id == $e[0]) {
          $nodes[$id]['out'][] = $e[1];
        }
        if ($id == $e[1]) {
          $nodes[$id]['in'][] = $e[0];
        }
      }
    }
    foreach ($nodes as $id => $n) {
      if (empty($n['in'])) {
        $S[] = $id;
      }
    }
    while (!empty($S)) {
      $L[] = $id = array_shift($S);
      foreach ($nodes[$id]['out'] as $m) {
        $nodes[$m]['in'] = array_diff($nodes[$m]['in'], [$id]);
        if (empty($nodes[$m]['in'])) {
          $S[] = $m;
        }
      }
      $nodes[$id]['out'] = [];
    }
    foreach ($nodes as $n) {
      if (!empty($n['in']) or !empty($n['out'])) {
        return null; // not sortable as graph is cyclic
      }
    }
    return $L;
  }

  public static function tagTree($t, $tags)
  {
    foreach ($tags as $tag => $v) {
      $t[$tag] = $v;
    }

    if (isset($t['childs'])) {
      $t['childs'] = array_map(function ($child) use ($tags) {
        return self::tagTree($child, $tags);
      }, $t['childs']);
    }
    return $t;
  }

  // Flow-tag cardIds of action spaces ('ActionGrainUtilization', ...) vs player cards ('A122_...') or untagged (null)
  public static function isActionSpaceId($cardId)
  {
    return substr($cardId ?? '', 0, 6) == 'Action';
  }

  public static function changeToNoNeedRoomRecursively(&$flow, $removeOptional = false, $cardLocation = null)
  {
    if (isset($flow['childs'])) {
      foreach ($flow['childs'] as &$child) {
        self::changeToNoNeedRoomRecursively($child, $removeOptional, $cardLocation);
      }
    } else if (isset($flow['action'])) {
      if ($flow['action'] == WISHCHILDREN) {
        if (isset($flow['args']) && is_array($flow['args'])) {
          if ($cardLocation != null) {
            $flow['args']['cardLocation'] = $cardLocation;
          }
          if (isset($flow['args']['constraints']) && is_array($flow['args']['constraints'])) {
            $flow['args']['constraints'] = array_diff($flow['args']['constraints'], ['freeRoom']);
          }
        }
        if ($removeOptional) {
          unset($flow['optional']);
        }
      }
    }
  }

  public static function checkNeedRoomConstraintRecursively($flow)
  {
    if (isset($flow['childs'])) {
      $value = false;
      foreach ($flow['childs'] as &$child) {
        $value |= self::checkNeedRoomConstraintRecursively($child);
      }
      return $value;
    } else if (isset($flow['action'])) {
      if ($flow['action'] == WISHCHILDREN) {
        if (isset($flow['args']) && is_array($flow['args'])) {
          if (isset($flow['args']['constraints']) && is_array($flow['args']['constraints'])) {
            if (in_array('freeRoom', $flow['args']['constraints'])) {
              return true;
            }
          }
        }
      }
      return false;
    }
  }

  public static function wrapOptional($flow)
  {
    $wrapped = [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $flow,
      ]
    ];
    if (isset($flow['pId'])) {
      $wrapped['pId'] = $flow['pId'];
    }
    if (!empty($flow['countAsUse'])) {
      $wrapped['countAsUse'] = true;
    }
    return $wrapped;
  }

  public static function hasReplace($actionId) {
    // No current player when args are computed from a spectator/zombie request
    $player = Players::getCurrent();
    return $player != null && count(PlayerCards::getCardsHasMethod('onPlayerComputeReplace' . $actionId, $player->getId())) > 0;
  }
}
