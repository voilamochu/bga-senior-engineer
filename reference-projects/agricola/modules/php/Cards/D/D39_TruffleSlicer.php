<?php
namespace AGR\Cards\D;
use AGR\Core\Globals;

class D39_TruffleSlicer extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D39_TruffleSlicer';
    $this->name = clienttranslate('Truffle Slicer');
    $this->deck = 'D';
    $this->number = 39;
    $this->category = POINTS_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use a wood accumulation space, if you have at least 1 <PIG>, you can pay 1 <FOOD> for 1 bonus <SCORE>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->extraVp = true;
    $this->prerequisite = clienttranslate('Play in Round 8 or Later');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $turn = Globals::getTurn();
    if ($turn < 8) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }
  
  public function isListeningTo($event)
  {
    return $this->isBeforeCollectEvent($event, WOOD);
  }
  
  public function onPlayerPlaceFarmer($player, $event)
  {
    $n = $player->getExchangeResources()[PIG];
    if ($n == 0) {
      return null;
    }
    
    return $this->payGainNode([FOOD => 1],[SCORE => 1]);
  }  

  // TODO: remove
  public function onPlayerAfterCollect($player, $event)
  {
    $n = $player->getExchangeResources()[PIG];
    if ($n == 0) {
      return null;
    }
    
    return $this->payGainNode([FOOD => 1],[SCORE => 1]);
  }
}
