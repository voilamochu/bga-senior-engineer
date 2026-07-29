<?php
namespace AGR\Cards\A;

use AGR\Managers\Farmers;

class A152_NightSchoolStudent extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A152_NightSchoolStudent';
    $this->name = clienttranslate('Night-School Student');
    $this->deck = 'A';
    $this->number = 152;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'Each returning home phase in which no player returns a person from a __Lessons__ action space, you can play an occupation for an occupation cost of 1 <FOOD>.'
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
    if ((Farmers::getOnCard('ActionLessons')->empty()) && 
        (Farmers::getOnCard('ActionLessons4')->empty()))
    {
      return [
        'action' => OCCUPATION,
        'cardId' => $this->id,
        'optional' => true,
        'args' => ['max' => 1, 'cost' => [FOOD => 1]],
      ];
    }
  }
}
