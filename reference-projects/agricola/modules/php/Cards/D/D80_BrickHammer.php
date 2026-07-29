<?php
namespace AGR\Cards\D;
use AGR\Managers\PlayerCards;

class D80_BrickHammer extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D80_BrickHammer';
    $this->name = clienttranslate('Brick Hammer');
    $this->deck = 'D';
    $this->number = 80;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate('Each time after you build an improvement costing at least 2 <CLAY>, you get 1 <STONE>.'),
    ];
    $this->costs = [[WOOD => 1], [FOOD => 1]];
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Improvement');
  }
  
  public function onPlayerAfterImprovement($player, $event)
  {
    $card = PlayerCards::get($event['cardId']);
    $costs = $card->getBaseCosts();

    foreach ($costs as $cost) {
      if (isset($cost[CLAY]) && $cost[CLAY] >= 2) {
        return $this->gainNode([STONE => 1]);
      }
    }
  }
}
