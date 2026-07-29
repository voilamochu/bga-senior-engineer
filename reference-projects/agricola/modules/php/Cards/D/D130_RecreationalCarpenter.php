<?php
namespace AGR\Cards\D;
use AGR\Managers\Farmers;
use AGR\Core\Globals;

class D130_RecreationalCarpenter extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D130_RecreationalCarpenter';
    $this->name = clienttranslate('Recreational Carpenter');
    $this->deck = 'D';
    $this->number = 130;
    $this->category = ACTIONS_BOOSTER;
    $this->desc = [
      clienttranslate(
        'At the end of each work phase in which you did not use the __Meeting Place__ action space, you can take a __Build Rooms__ action without placing a person.'
      ),
    ];
    $this->players = '3+';
    $this->isCorbariusOrDulcinaria = true;
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) &&
	  $event['type'] == 'EndWorkPhase';
  }

  public function onPlayerEndWorkPhase($player, $event)
  {
    $pId = $player->getId();
    
    if (Farmers::getOnCard('ActionMeetingPlace', $pId)->empty()) {
      return [
        'countAsUse' => true,
        'action' => CONSTRUCT,
        'optional' => true,
      ];
    }
  }  
}
