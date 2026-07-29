<?php
namespace AGR\Actions;
use AGR\Core\Globals;
use AGR\Managers\Players;
use AGR\Managers\Meeples;
use AGR\Core\Notifications;
use AGR\Core\Engine;
use AGR\Helpers\Utils;

class PlaceFutureMeeples extends \AGR\Models\Action
{
  public function __construct($row)
  {
    parent::__construct($row);
  }

  public function getState()
  {
    return ST_PLACE_FUTURE_MEEPLES;
  }

// TODO: needs exception for "roomStone" and maybe other weird cases
public function getDescription($ignoreResources = false)
{
  $args = $this->ctx->getArgs();
  $resources = $args['resources'] ?? [];
  $turnsRaw  = $args['turns'] ?? [];

  // Build "amount + icon" string, e.g. "3 <FOOD>, 1 <STONE>"
  $goodsParts = [];
  foreach ($resources as $resType => $amount) {
    $typeTag = '<' . strtoupper($resType) . '>';
    // Show amount explicitly; falls back to just the icon if somehow null/0
    $goodsParts[] = (is_numeric($amount) && $amount > 0 ? $amount . ' ' : '') . $typeTag;
  }
  $goods = implode(', ', $goodsParts);

  $pId = $args['pId'] ?? null;
  $playerColor = $pId ? Players::get($pId)->getColor() : null;

  // If exactly one target round, show "on round Y"
  if (is_array($turnsRaw) && count($turnsRaw) === 1) {
    $round = $turnsRaw[0];

    // Support "+X" notation or absolute round numbers
    if (!\is_int($round)) {
      $round = (int) substr($round, 1) + \AGR\Core\Globals::getTurn();
    }

    return [
      'log' => clienttranslate('Place ${resources_desc} on round ${round}'),
      'args' => [
        'resources_desc' => $goods,
        'round' => $round,
        'playerColor' => $playerColor,
      ],
    ];
  }

  // Fallback (multi-round or unknown): keep existing behaviour
  return [
    'log' => clienttranslate('Place ${resources_desc} for future rounds'),
    'args' => [
      'resources_desc' => $goods,
      'playerColor' => $playerColor,
    ],
  ];
}

  public function isAutomatic($player = null)
  {
    return true;
  }

  public function isIndependent($player = null)
  {
    return true;
  }

  public function stPlaceFutureMeeples()
  {
    $args = $this->ctx->getArgs();
    $resources = $args['resources'];
    $currentTurn = Globals::getTurn();

    // Compute the turns where we are going to add stuff, if any
    $turns = [];
    foreach ($args['turns'] as $turn) {
      if (!\is_int($turn)) {
        // If $x is not int, it's of the form +X
        $turn = (int) substr($turn, 1);
        $turn += $currentTurn;
      }

       if ($turn > $currentTurn && $turn <= 14) {
        $turns[] = $turn;
      }
    }

    if (empty($turns)) {
      $this->resolveAction();
      return;
    }

    $player = Players::get($args['pId']);
    $flagCard = $args['flagCard'] ?? false;
    $cardId = $args['cardId'] ?? null;

    // Create meeples and place them
    $meepleIds = [];
    foreach ($turns as $turn) {
      foreach ($resources as $resType => $amount) {
        array_push(
          $meepleIds,
          ...Meeples::createResourceInLocation(
            $resType,
            'turn_' . $turn,
            $player->getId(),
            $flagCard ? $cardId : null,
            null,
            $amount
          )
        );
      }
    }

    $meeples = Meeples::getMany($meepleIds);
    Notifications::placeMeeplesForFuture($player, $resources, $turns, $meeples);
    $this->resolveAction();
  }
}