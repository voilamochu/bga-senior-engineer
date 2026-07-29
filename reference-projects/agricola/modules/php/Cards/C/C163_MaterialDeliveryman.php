<?php
namespace AGR\Cards\C;

class C163_MaterialDeliveryman extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C163_MaterialDeliveryman';
    $this->name = clienttranslate('Material Deliveryman');
    $this->deck = 'C';
    $this->number = 163;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each time any player (including you) takes 5/6/7/8+ goods from an accumulation space, you get 1 <WOOD>/<CLAY>/<REED>/<STONE> from the general supply.'
      ),
    ];
    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event, null, false, null);
  }
  
  public function onAfterCollect($player, $event)
  {
    $map = [WOOD, CLAY, REED, STONE];
    $n = min(count($event['meeples']), 8);
    
    if ($n < 5) {
      return;
    }
    
    $resource = $map[$n-5];
    return $this->gainNode([$resource => 1]);
  }  
  
  public function onPlayerAfterCollect($player, $event)
  {
    return $this->onAfterCollect($player, $event);
  }
  
  public function onOpponentCollect($player, $event)
  {
    return $this->onAfterCollect($player, $event);
  }
}
