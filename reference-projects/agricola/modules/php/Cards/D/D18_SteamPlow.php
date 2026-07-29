<?php
namespace AGR\Cards\D;

use AGR\Core\Engine;

class D18_SteamPlow extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D18_SteamPlow';
    $this->name = clienttranslate('Steam Plow');
    $this->deck = 'D';
    $this->number = 18;
    $this->category = FARM_PLANNER;
    $this->desc = [
      clienttranslate(
        'Immediately after each returning home phase, you can pay 2 <WOOD> and 1 <FOOD> to use the __Farmland__ action space without placing a person.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      WOOD => 1,
      FOOD => 1,
    ];
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && 
      $event['type'] == 'ReturnHome' &&
      $this->isDoable();
  }

  public function onPlayerReturnHome($player, $event)
  {
    return [
      'type' => NODE_SEQ,
      'optional' => true,
      'countAsUse' => true,
      'childs' => [
        $this->payNode([WOOD => 2, FOOD => 1]),
        $this->useActionSpaceNode('ActionFarmland'),
      ]
    ];
  }

  public function isDoable()
  {
    $player = $this->getPlayer();      
    $flow = $this->getActionSpaceFlow('ActionFarmland');
    $flowTree = Engine::buildTree($flow);    

    return $flow != [] && $flowTree->isDoable($player, false);
  }
}
