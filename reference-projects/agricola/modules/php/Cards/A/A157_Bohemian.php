<?php
namespace AGR\Cards\A;
use AGR\Managers\Farmers;

class A157_Bohemian extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A157_Bohemian';
    $this->name = clienttranslate('Bohemian');
    $this->deck = 'A';
    $this->number = 157;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At the start of each returning home phase, if at least one __Lessons__ action space is unoccupied, you get 1 <FOOD>.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && 
	  $event['type'] == 'StartReturnHome';
  }
  
  public function onPlayerStartReturnHome($player, $event)
  {
    if ((Farmers::getOnCard('ActionLessons')->empty()) || 
        (Farmers::getOnCard('ActionLessons4')->empty()))
    {
      return $this->gainNode([FOOD => 1]);
    }
  }  
}
