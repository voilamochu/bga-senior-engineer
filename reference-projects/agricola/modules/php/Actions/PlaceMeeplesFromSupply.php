<?php
namespace AGR\Actions;
use AGR\Managers\Meeples;
use AGR\Managers\Players;
use AGR\Managers\PlayerCards;
use AGR\Core\Notifications;
use AGR\Helpers\Utils;

class PlaceMeeplesFromSupply extends \AGR\Models\Action
{
  public function __construct($row)
  {
    parent::__construct($row);
  }

  public function getState()
  {
    return ST_PLACE_MEEPLES_FROM_SUPPLY;
  }

  public function getDescription($ignoreResources = false)
  {
    $args = $this->getCtxArgs();
    if ($args == []) {
      return '';
    }

    $resources = $args['resources'] ?? null;
    $location = $args['location'] ?? null;
    $goods = '';
    $spaces = $this->formatLocation($location);

    foreach ($resources as $resType => $amount) {
      $amount = $amount > 1 ? $amount : '';
      $type = '<' . strtoupper($resType) . '>';
      $goods = $goods . $amount . $type . ", ";
    }
    $goods = substr($goods, 0, -2);

    $pId = $args['pId'] ?? null;
    $playerColor = $pId ? Players::get($pId)->getColor() : null;

    return [
      'log' => clienttranslate('Place ${resources_desc} on ${spaces}'),
      'args' => [
        'resources_desc' => $goods,
        'spaces' => $spaces,
        'playerColor' => $playerColor,
      ],
    ];
  }

  public function formatLocation($location)
  {
    $spaces = is_array($location) ? '' : Utils::getActionCard($location)->getName();

    if ($spaces == '') {
      foreach ($location as $space) {
        $spaces = $spaces . Utils::getActionCard($space)->getName() . ", ";
      }
      $spaces = substr($spaces, 0, -2);
    }

    return $spaces;
  }

  public function isDoable($player, $ignoreResources = false)
  {
    $args = $this->getCtxArgs();
    if ($args == []) {
      return false;
    }

    $resources = $this->getCtxArgs()['resources'] ?? [];
    $hasResources = true;

    foreach ($resources as $resType => $amount) {
      if ($player->countReserveResource($resType) < $amount) {
        $hasResources = false;
      }
    }

    return ($ignoreResources || $hasResources);
  }

  public function isAutomatic($player = null)
  {
    return true;
  }

  public function isIndependent($player = null)
  {
    return true;
  }

  public function stPlaceMeeplesFromSupply()
  {
    $args = $this->getCtxArgs();
    if ($args == []) {
      $this->resolveAction();
      return false;
    }    

    $pId = $args['pId'] ?? Players::getActiveId();
    $player = Players::get($pId);
    $resources = $args['resources'] ?? null;
    $location = $args['location'] ?? null;
    $cardId = $args['cardId'] ?? null;

    if (is_null($cardId)) {
      return;
    }

    $source = PlayerCards::get($cardId)->getName();
    $spaces = $this->formatLocation($location);

    $meepleIds = [];
    $multipleSpaces = is_array($location);

    foreach ($resources as $type => $amount) {
      $meeples = Meeples::getReserveResource($player->getId(), $type);
      $n = is_array($location) ? count($location) : 1;
      $i = 0;
      $j = 0;

      foreach ($meeples as $meeple) {
        if ($j >= $n) {
            break;
        }
        
        $space = $multipleSpaces ? $location[$j] : $location;
        $meepleIds[] = $meeple['id'];
        Meeples::moveToCoords($meeple['id'], $space);
        $i++;

        // only increment location index after the right amount of meeples have been moved
        if ($i % $amount == 0) {
          $j++;
        }
      }
    }
    $meeples = Meeples::getMany($meepleIds);

    Notifications::placeMeeplesFromSupply($player, $resources, $meeples, $spaces, $source);
    $this->resolveAction();
  }
}
