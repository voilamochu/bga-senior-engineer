<?php

namespace AGR\Cards\B;

use AGR\Core\Globals;
use AGR\Models\MinorImprovement;

class B24_Lasso extends MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B24_Lasso';
    $this->name = clienttranslate('Lasso');
    $this->deck = 'B';
    $this->number = 24;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'You can place exactly two people immediately after one another if at least one of them uses the __Sheep Market__, __Pig Market__, or __Cattle Market__ accumulation space.'
      ),
    ];
    $this->cost = [
      REED => 1,
    ];
    $this->players = '2+';
  }

  // Lasso should not react to a placement Lasso itself made. We track which placedFarmers
  // were placed by Lasso, checked at queue-time and activation-time.

  public function isListeningTo($event)
  {
    if (!$this->isActionEvent($event, 'PlaceFarmer')) {
      return false;
    }
    
    //legacy check only
    if ($this->isFlagged()) {
      return false;
      
    }
    return !$this->isLassoPlacedFarmer($event['fId'] ?? null);
  }

  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    if (!($event['farmerPlaced'] ?? true)) {
      return null;
    }

    $cards = ['ActionSheepMarket', 'ActionPigMarket', 'ActionCattleMarket'];

    return [
      'countAsUse' => true,
      'type' => NODE_SEQ,
      'optional' => true,
      'childs' => [
        [
          'action' => SPECIAL_EFFECT,
          'args' => [
            'cardId' => $this->id,
            'method' => 'recordLassoPlacement',
          ],
        ],
        $this->setTurnIdNode(),
        [
          'action' => PLACE_FARMER,
          'pId' => $player->getId(),
          'args' => [
            'constraints' => in_array($event['actionCardId'], $cards) ? null : $cards,
          ],
          'source' => $this->name,
        ],
      ],
    ];
  }

  public function isRecordLassoPlacementDoable($player = null, $ignoreResources = false)
  {
    $placed = Globals::getPlacedFarmers()[$this->getPlayer()->getId()] ?? [];
    if (empty($placed)) {
      return true;
    }
    $lastFId = $placed[count($placed) - 1];
    return !$this->isLassoPlacedFarmer($lastFId);
  }

  public function recordLassoPlacement()
  {
    $pId = $this->getPlayer()->getId();
    $all = Globals::getLassoPlacedFarmers() ?? [];
    $all[$pId][] = count(Globals::getPlacedFarmers()[$pId] ?? []);
    Globals::setLassoPlacedFarmers($all);
  }

  public function isIndependentRecordLassoPlacement()
  {
    return true;
  }

  private function isLassoPlacedFarmer($fId)
  {
    if ($fId === null) {
      return false;
    }
    $pId = $this->getPlayer()->getId();
    $index = array_search($fId, Globals::getPlacedFarmers()[$pId] ?? []);
    return $index !== false && in_array($index, Globals::getLassoPlacedFarmers()[$pId] ?? []);
  }
}
