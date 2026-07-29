<?php

namespace ARK\Managers;

use ARK\Core\Globals;
use ARK\Core\Meeples;
use ARK\Helpers\Collection;
use ARK\Models\ActionCard;

/* Class to manage all the action cards for Ark Nova */

class ActionCards extends \ARK\Helpers\CachedPieces
{
  protected static string $table = 'actioncards';
  protected static string $prefix = 'card_';
  protected static array $customFields = ['level', 'player_id', 'extra_datas', 'type'];
  protected static bool $autoIncrement = true;
  protected static bool $autoremovePrefix = false;
  protected static ?Collection $datas = null;

  protected static function cast(array|null $card): ActionCard
  {
    return self::getInstance($card['type'], $card);
  }

  protected static function getInstance(string $type, array $row = null): ActionCard
  {
    $className = '\ARK\Cards\Actions\\Action' . $type;
    return new $className($row);
  }
  public static function getInstances(array $types)
  {
    $cards = new Collection();
    foreach ($types as $type) {
      $cards[] = self::getInstance($type);
    }
    return $cards;
  }

  /* Creation of the cards */
  protected static $actionCards = ['Build', 'Cards', 'Animals', 'Association', 'Sponsors'];
  public static function setupPlayer(int $pId, array $marineCards = [])
  {
    $cards = [];
    $rand = range(2, 5);
    shuffle($rand);
    foreach (self::$actionCards as $type) {
      $cards[] = [
        'type' => isset($marineCards[$type]) ? $marineCards[$type] : $type,
        'player_id' => $pId,
        'location' => $type == ANIMALS ? 1 : array_pop($rand),
        'state' => 0,
        'level' => 1,
      ];
    }
    return self::create($cards, null);
  }

  public static function setupNextGame()
  {
    self::DB()
      ->delete()
      ->run();
    self::invalidate();
  }

  public static function getOfPlayer(int $pId): Collection
  {
    return self::getAll()->filter(function ($card) use ($pId) {
      return $card->getPId() == $pId;
    });
  }

  public static function getInPosition(int $pId, int $position): ActionCard
  {
    return self::getOfPlayer($pId)
      ->filter(function ($card) use ($position) {
        return $position == $card->getStrength();
      })
      ->first();
  }

  /**
   * Marine World
   */
  public static array $MWActionCards = [
    'Build1', 'Cards1', 'Animals1', 'Association1', 'Sponsors1',
    'Build2', 'Cards2', 'Animals2', 'Association2', 'Sponsors2',
    'Build3', 'Cards3', 'Animals3', 'Association3', 'Sponsors3',
    'Build4', 'Cards4', 'Animals4', 'Association4', 'Sponsors4',
  ];
}
