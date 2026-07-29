<?php

namespace ARK\Helpers;

use ARK\Core\Globals;
use ARK\Managers\Players;

// Allow to use a short flow description syntax
abstract class FlowConvertor
{
  /**
   * getFlow: given an array of bonuses, return the list of corresponding actions
   *  - array of bonuses follow this format :
   *    [
   *      [BONUS_TYPE => BONUS_N],
   *      [BONUS_TYPE => BONUS_N]
   *    ]
   */
  public static function getFlow($bonuses, $source = '', $sourceType = null, $sourceId = null)
  {
    $immediateBonuses = [];
    $afterFinishingBonuses = [];
    foreach ($bonuses as $bonus) {
      // If bonus is already a node, no conversion needed
      if (isset($bonus['action']) || (isset($bonus['type']) && isset($bonus['childs']))) {
        if ($bonus['afterFinishing'] ?? false) {
          $afterFinishingBonuses[] = $bonus;
        } else {
          $immediateBonuses[] = $bonus;
        }
        continue;
      }

      $type = array_keys($bonus)[0];
      $n = $bonus[$type];
      $node = self::getFlowSingleBonus($type, $n, $bonus['source'] ?? $source, $bonus['sourceId'] ?? $sourceId, $bonus);
      if (is_null($node)) {
        continue;
      }

      // If the bonuses come from a bonusTile / incomeBonusSpace, wrap flow inside a TAKE_BONUS action for nicer UI
      if (
        ($sourceType == 'bonusTile' || $sourceType == 'incomeBonusSpace') &&
        is_null($bonus['sourceId'] ?? $sourceId) &&
        is_null($bonus['source'] ?? null)
      ) {
        $node = [
          'action' => TAKE_BONUS,
          'args' => [
            'type' => $type,
            'n' => $bonus[$type],
            'sourceType' => $sourceType,
            'source' => $source,
            'sourceId' => $sourceId,
          ],
        ];
      }

      if (in_array($type, [CLEVER, BOOST, ACTION, DETERMINATION, MARK]) || ($bonus['afterFinishing'] ?? false)) {
        $afterFinishingBonuses[] = $node;
      } else {
        $immediateBonuses[] = $node;
      }
    }
    return [$immediateBonuses, $afterFinishingBonuses];
  }

  /**
   * getFlowSingleBonus: given a bonus with its type and n, return the corresponding action
   */
  public static function getFlowSingleBonus($type, $n, $source = '', $sourceId = null, $args = [])
  {
    $flow = self::getFlowSingleBonusAux($type, $n, $args);
    $data = [];
    if (is_null($flow)) {
      return null;
    }

    if (!is_null($sourceId) && !isset($data['sourceId'])) {
      $data['sourceId'] = $sourceId;
    } elseif (!is_null($source) && $source != '' && !isset($data['source'])) {
      $data['source'] = $source;
    }

    return Utils::tagTree($flow, $data);
  }

  public static function getFlowSingleBonusAux($type, $n, $args = [])
  {
    // Basic resources via GAIN action
    if (in_array($type, [MONEY, XTOKEN, REPUTATION, APPEAL, CONSERVATION])) {
      // Handle the case of giving stuff to everyone else
      if (($args['pId'] ?? null) == \EVERYONE_ELSE) {
        $childs = [];
        $player = Players::getActive();
        foreach (Players::getAll() as $pId => $player2) {
          if ($pId == $player->getId()) {
            continue;
          }
          $childs[] = [
            'action' => GAIN,
            'args' => [$type => $n, 'pId' => $pId],
          ];
        }

        return [
          'type' => \NODE_SEQ,
          'childs' => $childs,
        ];
      }

      // Normal gain
      $data = [
        'action' => GAIN,
        'args' => [$type => $n],
      ];
      if (isset($args['pId'])) {
        $data['args']['pId'] = $args['pId'];
      }
      if ($args['income'] ?? false) {
        $data['args']['income'] = true;
      }
      if (isset($args['map'])) {
        $data['args']['map'] = $args['map'];
      }
      if (isset($args['source'])) {
        $data['source'] = $args['source'];
      }
      return $data;
    }
    // Addition worker => same as "Full Throated" animal effect
    elseif ($type == \BONUS_WORKER) {
      return ['action' => FULL_THROATED, 'args' => ['n' => 1]];
    }
    // Move animals => only for the specific case where you build a special enclosure and you can move animals inside it
    elseif ($type == MOVE_ANIMALS) {
      return [
        'action' => MOVE_ANIMALS,
        'args' => ['buildingType' => $n],
        'optional' => true,
      ];
    }
    // Upgrade an action card
    elseif ($type == BONUS_UPGRADE_CARD) {
      return ['action' => UPGRADE_CARD];
    }
    // Gain a free university from the association board
    elseif ($type == UNIVERSITY) {
      return ['action' => GAIN_UNIVERSITY];
    }
    // Gain a free partner zoo from the association board
    elseif ($type == PARTNER_ZOO) {
      return ['action' => GAIN_PARTNER_ZOO];
    }
    // Build enclosure
    elseif (in_array($type, array_merge(ENCLOSURES, [KIOSK, PAVILION, KIOSK_OR_PAVILION]))) {
      if ($type == KIOSK_OR_PAVILION) {
        $type = [KIOSK, PAVILION];
      }
      if ($n > 1) {
        $nodes = [];
        for ($i = 0; $i < $n; $i++) {
          $nodes[] = ['action' => BUILD, 'args' => ['free' => true, 'freeBuilding' => $type, 'canPass' => true]];
        }
        return [
          'type' => NODE_SEQ,
          'childs' => $nodes,
          'customDescription' => [
            'log' => clienttranslate('Build for free x${n}'),
            'args' => ['n' => $n],
          ]
        ];
      }

      return [
        'action' => BUILD,
        'args' => ['free' => true, 'freeBuilding' => $type, 'canPass' => true],
      ];
    } elseif ($type == BONUS_SPECIAL_ENCLOSURES) {
      return [
        'action' => BUILD,
        'args' => ['free' => true, 'freeBuilding' => [\LARGE_BIRD_AVIARY, \REPTILE_HOUSE], 'canPass' => true],
      ];
    } else if ($type == BONUS_SPECIAL_ENCLOSURES_WITH_AQUARIUM) {
      $buildings = [\LARGE_BIRD_AVIARY, \REPTILE_HOUSE];
      if (Globals::isMarineWorld()) {
        $buildings[] = LARGE_AQUARIUM;
      }
      return [
        'action' => BUILD,
        'args' => ['free' => true, 'freeBuilding' => $buildings, 'canPass' => true],
      ];
    }
    // Build unique building
    elseif ($type == BUILD) {
      return [
        'action' => BUILD,
        'args' => ['free' => true, 'freeBuilding' => $n, 'unique' => true],
      ];
    } elseif ($type == MULTIPLIER) {
      return ['action' => MULTIPLIER, 'args' => ['n' => 'all']];
    }
    // Pay a sponsor card with money instead of strength
    elseif ($type == BONUS_SPONSOR) {
      return ['action' => BUY_SPONSOR, 'optional' => true];
    }
    // Everyone need to discard a scoring card
    elseif ($type == DISCARD_SCORING) {
      $player = Players::getActive();
      return [
        'action' => DISCARD_SCORING,
        'args' => ['current' => $player->getId()],
        'pId' => 'all',
      ];
    } elseif ($type == POUCH) {
      return ['action' => POUCH, 'args' => ['n' => $n]];
    }
    // ACTIONS THAT NEEDS TO BE TAKEN X-TIMES
    elseif (in_array($type, [CLEVER, INCREASE_SIZE]) && ($n ?? 1) > 1) {
      $nodes = [];
      for ($i = 0; $i < $n; $i++) {
        $nodes[] = ['action' => $type];
      }
      return ['type' => NODE_SEQ, 'childs' => $nodes];
    }
    // HELPFUL => null node
    else if ($type == HELPFUL) {
      return null;
    }
    // BONUS STRENGTH of MAP 12 => null node
    else if ($type == BONUS_STRENGTH) {
      return null;
    }
    // Default behavior : action name = bonus name
    else {
      $node = ['action' => $type, 'args' => ['n' => $n]];
      if ($args['optional'] ?? false) {
        $node['optional'] = true;
      }
      return $node;
    }

    die('TakeBonus : bonus type flow not found for ' . $type);
  }

  /**
   * Return the conservation bonuses rewarded at a given conservation
   */
  public static function getConservationBonusesXORNode($conservation)
  {
    // Any bonus here ?
    $bonusMap = Globals::getBonusTiles();
    $slot = $bonusMap[$conservation] ?? null;
    if (is_null($slot)) {
      return null;
    }

    // Compute node for each of them
    $childs = [];
    foreach ($slot as $i => $data) {
      $bonus = $data['bonus'];
      $type = array_keys($bonus)[0];
      $childs[] = [
        'action' => TAKE_BONUS,
        'args' => [
          'type' => $type,
          'n' => $bonus[$type],
          'remove' => $data['permanent'] ? '' : "$conservation-$i",
          'income' => false,
        ],
      ];
    }

    return empty($childs)
      ? null
      : (count($childs) == 1
        ? $childs[0]
        : [
          'type' => \NODE_XOR,
          'childs' => $childs,
        ]);
  }
}
