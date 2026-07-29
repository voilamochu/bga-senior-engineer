<?php

namespace ARK\Models;

use ARK\Managers\Actions;
use ARK\Core\Engine;
use ARK\Helpers\Utils;
use ARK\Managers\Meeples;
use ARK\Managers\Players;
use ARK\Core\Globals;

/*
 * Action Card
 */

class ActionCard extends \ARK\Helpers\DB_Model
{
  protected string $table = 'actioncards';
  protected string $primary = 'card_id';

  protected array $attributes = [
    'id' => ['card_id', 'int'],
    'strength' => ['card_location', 'int'],
    'pId' => ['player_id', 'int'],
    'extraDatas' => ['extra_datas', 'obj'],
    'type' => ['type', 'str'],
    'status' => ['card_state', 'int'],
    'level' => ['level', 'int'],
  ];
  protected ?int $id;
  protected ?string $type;
  protected ?string $pId;

  protected array $staticAttributes = ['actionType', ['number', 'int'], 'name', 'descI', 'descII', 'tooltip'];
  protected string $actionType;
  protected int $number = 0;
  protected string $name;
  protected array $descI;
  protected array $descII;
  protected array $tooltip = [];

  public function getAction($ctx = null): Action
  {
    return Actions::get($this->actionType, $ctx);
  }

  public function getCurrentStrength(): int
  {
    $strength = $this->getStrength();
    $player = Players::get($this->pId);

    // MAP 12
    if ($player->getMapId() == 12) {
      $status = $player->getMapStatus();
      $n = $status['bonusStrength'];
      if ($n >= 1 && $strength == 1) $strength = 2;
      if ($n >= 2 && $strength <= 2) $strength = 3;
      if ($n >= 3 && $strength == 4) $strength = 5;
      if ($n >= 4 && $strength == 5) $strength = 6;
    }

    // MAP 14
    if (Globals::isActiveT1Effect()) {
      $strength++;
    }

    // Constriction
    $strength -= count($this->getMeeplesOnIt(CONSTRICTION)) * 2;

    return $strength;
  }

  public function getPlayableStrengths($player, $ignoreXTokens = false): array
  {
    $baseStrength = $this->getCurrentStrength();
    $maxStrength = $ignoreXTokens ? 10 : $baseStrength + $player->countXTokens();

    $strengths = [];
    for ($strength = $baseStrength; $strength <= $maxStrength; $strength++) {
      // If the card strength reduce below 1 with constriction, cannot play this strength
      if ($strength < 1) {
        continue;
      }

      if ($this->canBePlayed($player, $strength)) {
        $strengths[$strength] = $strength - $baseStrength;
      }
    }

    return $strengths;
  }

  public function canBePlayed($player, $strength = null)
  {
    $strength = $strength ?? $this->getStrength();
    return $this->getAction(['strength' => $strength, 'lvl' => $this->getLevel(), 'number' => $this->number])->isDoable($player);
  }

  public function getFlow($strength = null)
  {
    $strength = $strength ?? $this->getStrength();
    return [
      'action' => $this->actionType,
      'args' => [
        'strength' => $strength,
        'lvl' => $this->getLevel(),
        'number' => $this->number,
      ],
    ];
  }

  public function getTaggedFlow($player, $strength = null)
  {
    // Add card context for listeners
    return Utils::tagTree($this->getFlow($strength), [
      'pId' => $player->getId(),
      'cardId' => $this->id,
    ]);
  }

  public function getMeeplesOnIt($type = null, $state = null)
  {
    return Meeples::getMeeplesOnActionCard($type, $this->id, $state);
  }

  public function getAfterFinishingFlow($strength = null)
  {
    return [];
  }

  public function getAfterFinishingTaggedFlow($player, $strength = null)
  {
    $flow = $this->getAfterFinishingFlow($strength);
    return empty($flow) ? [] : Utils::tagTree($flow, [
      'pId' => $player->getId(),
      'cardId' => $this->id,
    ]);
  }
}
