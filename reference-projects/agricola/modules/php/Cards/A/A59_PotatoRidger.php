<?php
namespace AGR\Cards\A;

class A59_PotatoRidger extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A59_PotatoRidger';
    $this->name = clienttranslate('Potato Ridger');
    $this->deck = 'A';
    $this->number = 59;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time after you harvest 1+ <VEGETABLE>, if you then have 3+ <VEGETABLE> in your supply, you can turn exactly 1 <VEGETABLE> into 6 <FOOD>. With 4+ <VEGETABLE>, you must do so.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Reap') && $this->countVegetables($event) > 0;
  }

  public function countVegetables($event)
  {
    $n = 0;
    foreach($event['crops'] as $meeple){
      $n += $meeple['type'] == \VEGETABLE? 1 : 0;
    }
    return $n;
  }

  public function onPlayerAfterReap($player, $event)
  {
    $veg = $player->countReserveResource(VEGETABLE);
    $optional = $veg < 4 ? true : false;
    
    if ($veg >= 3) {
      return $this->payGainNode([VEGETABLE => 1], [FOOD => 6], null, $optional);
    }
  }  
}
