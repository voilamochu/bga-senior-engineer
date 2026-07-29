<?php
namespace AGR\Cards\A;
use AGR\Managers\Farmers;

class A141_TurnipFarmer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A141_TurnipFarmer';
    $this->name = clienttranslate('Turnip Farmer');
    $this->deck = 'A';
    $this->number = 141;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At the start of the returning home phase of each round, if both the __Day Laborer__ and __Grain Seeds__ action spaces are occupied, you get 1 <VEGETABLE>.'
      ),
    ];
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && 
	  $event['type'] == 'StartReturnHome' &&
	  !Farmers::getOnCard('ActionDayLaborer')->empty() &&
      !Farmers::getOnCard('ActionGrainSeeds')->empty();
  }

  public function onPlayerStartReturnHome($player, $event)
  {
    return $this->gainNode([VEGETABLE => 1]);
  }  
}
