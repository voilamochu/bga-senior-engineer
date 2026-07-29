<?php
namespace AGR\Cards\B;

class B58_CrackWeeder extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B58_CrackWeeder';
    $this->name = clienttranslate('Crack Weeder');
    $this->deck = 'B';
    $this->number = 58;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <FOOD>. For each <VEGETABLE> you take from a field in the field phase of a harvest, you also get 1 <FOOD>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->isArtifexOrBubulcus = true;
  }
  
  public function onBuy($player)
  {
    return $this->gainNode([FOOD => 1]);
  }

  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Reap') && (($event['trigger'] ?? null) == HARVEST) && $this->countVegetables($event) > 0;
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
    $n = $this->countVegetables($event);
    return $this->gainNode([FOOD => $n]);
  }  
}
