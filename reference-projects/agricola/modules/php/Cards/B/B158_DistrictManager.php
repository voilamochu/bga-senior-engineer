<?php
namespace AGR\Cards\B;
use AGR\Managers\Farmers;

class B158_DistrictManager extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'B158_DistrictManager';
    $this->name = clienttranslate('District Manager');
    $this->deck = 'B';
    $this->number = 158;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At the end of each work phase, if you used both the __Forest__ and __Grove__ accumulation spaces, you get 5 <FOOD>.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && 
	  $event['type'] == 'EndWorkPhase';
  }
  
  public function onPlayerEndWorkPhase($player, $event)
  {  
    $pId = $player->getId();
    $onForest = !Farmers::getOnCard('ActionForest', $pId)->empty();
    $onGrove = !Farmers::getOnCard('ActionGrove', $pId)->empty();
    
    if ($onForest && $onGrove) {
      return $this->gainNode([FOOD => 5]);
    }
  }
}
