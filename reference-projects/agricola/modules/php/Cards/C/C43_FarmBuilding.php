<?php
namespace AGR\Cards\C;
use AGR\Managers\PlayerCards;

class C43_FarmBuilding extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C43_FarmBuilding';
    $this->name = clienttranslate('Farm Building');
    $this->deck = 'C';
    $this->number = 43;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you build a major improvement, place 1 <FOOD> on each of the next 3 round spaces. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->cost = [
      CLAY => 1,
      REED => 1,
    ];
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Improvement');
  }
  
  public function onPlayerAfterImprovement($player, $event)
  {
    $card = PlayerCards::get($event['cardId']);
    
    if ($card->hasType(MAJOR)) {
      return $this->futureMeeplesNode([FOOD => 1], 3);
    }
  }  
}
