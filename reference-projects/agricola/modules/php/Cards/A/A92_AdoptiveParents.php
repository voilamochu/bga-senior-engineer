<?php

namespace AGR\Cards\A;

use AGR\Core\Globals;
use AGR\Core\Notifications;
use AGR\Managers\Farmers;
use AGR\Models\Occupation;

class A92_AdoptiveParents extends Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A92_AdoptiveParents';
    $this->name = clienttranslate('Adoptive Parents');
    $this->deck = 'A';
    $this->number = 92;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'For 1 <FOOD>, you can take an action with offspring in the same round you get it. If you do, the offspring does not count as "newborn".'
      ),
    ];
    $this->players = '1+';

    $this->rulings = [
      clienttranslate('This card may be played and triggered any time after growth in the same round\'s work phase.'),
      clienttranslate('If this effect is used in a round that ends with a Harvest, the person must be fed 2 <FOOD> in the Harvest (3 for solo game.)'),
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer');
  }

  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    $pId = $this->getPlayer()->getId();

    //Gate to work phase to avoid weird situations
    if (Globals::getGameFlowPhase() !== 'work') {
      return null;
    }

    if (!Farmers::hasChildren($pId)) {
      return null;
    }

    $childs = [
      $this->payNode([FOOD => 1]),
      [
        'action' => SPECIAL_EFFECT,
        'args' => [
          'cardId' => $this->id,
          'method' => 'growChild',
        ],
      ],
    ];

    // When the labor loop has already moved past this player (skippedPlayers), append an
    // explicit PLACE_FARMER so the offspring actually gets to take its action this round.
    // Fixes bug with Straw Hat 222348
    if (in_array($pId, Globals::getSkippedPlayers())) {
      $childs[] = $this->setTurnIdNode();
      $childs[] = [
        'action' => PLACE_FARMER,
        'willBeDoable' => true,
        'pId' => $pId,
      ];
    }

    return [
      'countAsUse' => true,
      'type' => NODE_SEQ,
      'pId' => $pId,
      'optional' => true,
      'childs' => $childs,
    ];
  }

  public function getGrowChildDescription()
  {
    return clienttranslate('Return child at home');
  }

  public function growChild()
  {
    $child = Farmers::growOneChild($this->getPlayer()->getId());
    Notifications::growChildren($child);
    $this->getPlayer()->returnHomeOne($child[0]);
    Notifications::adoptiveChildren(Farmers::getMany($child));
    Notifications::updateHarvestCosts();
  }

  public function getStartOfRoundChoice($player)
  {
    // Use it option => add the place farmer at the end of the SEQ_NODE
    $nodeUseIt = $this->onPlayerAfterPlaceFarmer($player, []);
    unset($nodeUseIt['optional']);
    $nodeUseIt['childs'][] = $this->setTurnIdNode();
    $nodeUseIt['childs'][] = [
      'action' => PLACE_FARMER,
      'willBeDoable' => true,
      'pId' => $player->getId(),
    ];

    // Don't use it option => EndOfRound action
    $nodeDontUseIt = [
      'action' => SPECIAL_EFFECT,
      'pId' => $player->getId(),
      'args' => [
        'cardId' => $this->id,
        'method' => 'endOfRound',
      ],
    ];

    return [
      'type' => NODE_XOR,
      'pId' => $player->getId(),
      'childs' => [$nodeUseIt, $nodeDontUseIt],
    ];
  }

  public function getEndOfRoundDescription()
  {
    return clienttranslate('End of round');
  }

  public function endOfRound()
  {
    $player = $this->getPlayer();
    $skipped = Globals::getSkippedPlayers();
    $skipped[] = $player->getId();
    Globals::setSkippedPlayers($skipped);
    Notifications::message(
      clienttranslate('${player_name} chose not to use Adoptive Parents effect, their round is now over'),
      [
        'player_name' => $player->getName(),
      ]
    );
  }
}
