<?php
namespace AGR\Cards\E;

use AGR\Core\Notifications;
use AGR\Helpers\Utils;
use AGR\Managers\Meeples;

class E123_ResourceHoarder extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E123_ResourceHoarder';
    $this->name = clienttranslate('Resource Hoarder');
    $this->deck = 'E';
    $this->author = 'inoshishi';
    $this->number = 123;
    $this->category = 'BUILDING_RESOURCES_-_CLAY_AND/OR_STONE';
    $this->desc = [
      clienttranslate(
        'Pile resources as depicted on this card. You can use the top item(s) when building a room, playing/building an improvement, or renovating. (From bottom to top: <STONE>, <CLAY>, <STONE>, <REED>, <WOOD>, <CLAY>)'
      ),
    ];
    $this->players = '1+';
    $this->holder = true;
  }

  public function onBuy($player)
  {
    // Bottom -> Top: Stone, Clay, Stone, Reed, Wood, Clay
    $types = [STONE, CLAY, STONE, REED, WOOD, CLAY];

    $created = [];
    foreach ($types as $i => $type) {
      $created = array_merge(
        $created,
        Meeples::createResourceInLocation($type, $this->id, $player->getId(), null, null, 1, $i)
      );
    }

    Notifications::accumulate(Meeples::getMany($created), true);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Pay');
  }

  /**
   * Build all "use top k" options from the pile, including k = 0 so use is optional.
   */
  function onComputeBonus($player, &$args)
  {
    // Ordered bottom..top (Meeples::getResourcesOnCard orders by state asc)
    $goods = Meeples::getResourcesOnCard($this->id);

    $types = [];
    foreach ($goods as $g) {
      $types[] = $g['type'];
    }

    if (empty($types)) {
      return;
    }

    $choices = [];
    $bonus = [];

    // k = 1..N from top down: [-top], [-top,-2nd], etc.
    for ($i = count($types) - 1; $i >= 0; $i--) {
      $bonus[] = $types[$i];
      $choices[] = self::buildChoice($bonus);
    }

    // Explicit "use 0" option at index 0 so default == do not use RH
    array_unshift($choices, []); // index 0 => use none

    Utils::addBonusChoices($args['costs'], $choices, $this->id, true);
  }

  // read all resources type from $bonus array, return an array of resources => count.
  // for example $bonus = [CLAY, STONE, CLAY] will return [CLAY => -2, STONE => -1]
  public static function buildChoice($bonus)
  {
    $count = [];
    foreach ($bonus as $type) {
      if (!isset($count[$type])) {
        $count[$type] = 0;
      }
      $count[$type]--;
    }
    return $count;
  }

  public function onPlayerComputeCardCosts($player, &$args)        { $this->onComputeBonus($player, $args); }
  public function onPlayerComputeCostsRenovation($player, &$args)  { $this->onComputeBonus($player, $args); }
  public function onPlayerComputeCostsConstruct($player, &$args)   { $this->onComputeBonus($player, $args); }

  public function orderComputeCostsRenovation()
  {
    return [['>', 'B145_BrushwoodCollector'], ['>', 'A123_FrameBuilder'], ['>', 'A143_Stonecutter']];
  }

  public function orderComputeCostsConstruct()
  {
    return [['>', 'B145_BrushwoodCollector'], ['>', 'A123_FrameBuilder'], ['>', 'A143_Stonecutter']];
  }

  /**
   * Map the chosen bonus option to "how many top items were used".
   *
   * With the choices we build:
   *   index 0 => []
   *   index 1 => [-top]
   *   index 2 => [-top, -2nd]
   * so "number of top resources used" is exactly the index.
   */
  function topResoucesUsed($event)
  {
    if (isset($event['cost'])) {
      $cost = $event['cost'];
      if (isset($cost['bonusChoiceIndex'])) {
        $indexes = $cost['bonusChoiceIndex'];
        if (isset($indexes[$this->id])) {
          return (int)$indexes[$this->id]; // 0 means "use none"; k means "use top k"
        }
      }
    }
    return 0;
  }

  public function onPlayerAfterPay($player, $event)
  {
    $n = $this->topResoucesUsed($event);
    if ($n > 0) {
      return [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'useNextResource',
          'args' => [$n],
        ],
      ];
    }
    // n == 0 => player chose not to use RH
    return null;
  }

  public function useNextResource($n)
  {
    // Convert collection to array for slicing; Meeples::getResourcesOnCard is bottom..top
    $goods = Meeples::getResourcesOnCard($this->id);
    $goodsArr = [];
    foreach ($goods as $g) {
      $goodsArr[] = $g;
    }

    $available = count($goodsArr);
    $n = max(0, min((int) $n, $available));
    if ($n === 0) {
      return;
    }

    // Take the top n items
    $toDelete = array_slice($goodsArr, -$n);

    Meeples::deleteResources($toDelete);
    Notifications::payResourcesFromCards($this->getPlayer(), $toDelete, $this->name);
  }

  public function getUseNextResourceDescription($n)
  {
    return [
      'log' => clienttranslate('Use top ${resources_desc} goods from ${card}'),
      'args' => [
        'resources_desc' => strval($n),
        'card' => $this->name,
      ],
    ];
  }
}
