<?php
namespace AGR\Cards\D;
use AGR\Core\Globals;

class D58_Gritter extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D58_Gritter';
    $this->name = clienttranslate('Gritter');
    $this->deck = 'D';
    $this->number = 58;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At the end of each action in which you sow vegetables in a field, you get 1 <FOOD> for each vegetable field you have (including the new ones).'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->prerequisite = clienttranslate('Play in Round 5 or Later');
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    if (Globals::getTurn() < 5) {
      return false;
    }

    return parent::isBuyable($player, $ignoreResources, $args);
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Sow');
  }

  public function onPlayerAfterSow($player, $event)
  {
    foreach($event['sows'] as $sow) {
      if ($sow['type'] == VEGETABLE) {
        $n = count($player->board()->getVegetableFields());
        if ($n > 0) {
          return $this->gainNode([FOOD => $n]);
        }
      }
    }
  }
}
