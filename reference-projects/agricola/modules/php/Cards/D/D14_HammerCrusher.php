<?php

namespace AGR\Cards\D;

use AGR\Helpers\UserException;
use AGR\Managers\Actions;
use AGR\Managers\Players;

class D14_HammerCrusher extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D14_HammerCrusher';
    $this->name = clienttranslate('Hammer Crusher');
    $this->deck = 'D';
    $this->number = 14;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Immediately before you renovate to stone, you get 2 <CLAY> and 1 <REED> and you can take a __Build Rooms__ action.'
      ),
    ];
    $this->cost = [
      WOOD => '1',
    ];
    $this->isCorbariusOrDulcinaria = true;
  }

  public function onPlayerIsDoable($player, &$args)
  {
    if ($args['isDoable']) {
      return;
    }

    if ($args['action'] == RENOVATION) {
      $renovateAction = Actions::get(RENOVATION);
      $renovationType = $renovateAction->getRenovationType($player, $args);
      if (in_array('roomStone', $renovationType)) {
        $args['isDoable'] = true;
      }
    }
  }

  public function isListeningTo($event)
  {
    if (!$this->isBeforeEvent($event, 'Renovation')) {
      return false;
    }
    $renovateAction = Actions::get(RENOVATION);
    $player = $this->getPlayer();
    $args = $event['args'] ?? [];
    $renovationType = $renovateAction->getRenovationType($player, $args);
    if (in_array('roomStone', $renovationType)) {
      $this->setExtraDatas('costs', $args['costs'] ?? null);
      // Keep the renovation's actionCardId so stoneRenovationCheck prices it with
      // the same cost modifiers (Plumber/Trowel discounts) as the real renovation (bug 142468)
      $this->setExtraDatas('renovationActionCardId', $args['actionCardId'] ?? null);
      return true;
    }
    return false;
  }

  public function onPlayerBeforeRenovation($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        $this->flagCardNode(),
        $this->gainNode([CLAY => 2, REED => 1]),
        [
          'type' => NODE_SEQ,
          'optional' => true,
          'childs' => [
            [
              'action' => CONSTRUCT,
              'args' => [
                'actionCardId' => $this->id,
              ],
            ]
          ]
        ]
      ]
    ];
  }

  public function wrapRenovationCheckNode($flow, $player)
  {
    return [
      'type' => NODE_SEQ,
      'childs' => [
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'pId' => $this->pId,
            'cardId' => $this->id,
            'method' => 'stoneRenovationCheck',
            'args' => [],
          ],
        ],
        $flow,
      ],
    ];
  }

  public function stoneRenovationCheck()
  {
    $player = Players::get($this->pId);
    $cost = $this->getExtraDatas('costs') ?? null;
    $renovateAction = Actions::get(RENOVATION);
    $actionCardId = $this->getExtraDatas('renovationActionCardId') ?? null;
    $canDo = $player->canBuy($renovateAction->getCosts($player, 'roomStone', $cost, $actionCardId), $player->countRooms());
    if (!$canDo) {
      throw new UserException(clienttranslate('You must make sure you have enough resources to renovate to stone before opponents to trigger their cards.'));
    }
  }
}