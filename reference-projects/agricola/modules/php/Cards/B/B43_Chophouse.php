<?php
namespace AGR\Cards\B;

class B43_Chophouse extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B43_Chophouse';
    $this->name = clienttranslate('Chophouse');
    $this->deck = 'B';
    $this->number = 43;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Grain/Vegetable Seeds__ action space, place 1 <FOOD> on each of the next 3/2 round spaces. At the start of these rounds, you get the <FOOD>.'
      ),
    ];
    $this->vp = 1;
    $this->costs = [[WOOD => 2], [CLAY => 2]];
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'GrainSeeds') ||
	  $this->isActionCardEvent($event, 'VegetableSeeds');
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
	$n = $this->isActionCardEvent($event, 'GrainSeeds') ? 3 : 2;
    
    return $this->futureMeeplesNode([FOOD => 1], $n);
  }
}
