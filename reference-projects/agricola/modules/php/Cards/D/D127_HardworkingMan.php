<?php

namespace AGR\Cards\D;

use AGR\Core\Engine;
use AGR\Managers\Players;
use AGR\Models\PlayerActionCard;
use AGR\Helpers\Utils;

class D127_HardworkingMan extends PlayerActionCard
{
  protected $type = OCCUPATION;

  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D127_HardworkingMan';
    $this->name = clienttranslate('Hardworking Man');
    $this->deck = 'D';
    $this->number = 127;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'This card is an action space for you only. If each other player has more rooms than you, it provides the __Day Laborer__, __Building Rooms__, and __Major Improvement__ actions (all three).'
      ),
    ];
    $this->players = '3+';
    $this->isCorbariusOrDulcinaria = true;

    $this->privateSpace = true;
    $this->flow = [
      'action' => SPECIAL_EFFECT,
      'args' => [
        'method' => 'activate',
        'cardId' => $this->id,
        'args' => [],
      ],
    ];
  }

  public function canBePlayed($player, $onlyCheckSpecificPlayer = null, $ignoreResources = true, $ignoreDoable = false)
  {
    $nums = 0;
    foreach (Players::getAll() as $player2) {
      if ($player2->countRooms() <= $player->countRooms()) {
        $nums = $nums + 1;
      }
    }
    if ($nums == 1) {
      return true;
    }
    return false;
  }

  public function activate()
  {
    $flow = [
      'type' => NODE_OR,
      'pId' => $this->pId,
      'childs' => [
        [
          'action' => CONSTRUCT,
          'pId' => $this->pId,
        ],
        [
          'action' => IMPROVEMENT,
          'pId' => $this->pId,
          'args' => [
            'types' => [MAJOR],
            'actionType' => 'Major',
          ],
        ],
        $this->gainNode([FOOD => 2])
      ],
    ];
    Engine::insertAsChild($flow);
  }
}
