<?php
namespace AGR\Cards\A;

use AGR\Helpers\UsedText;

class A168_AnimalTeacher extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A168_AnimalTeacher';
    $this->name = clienttranslate('Animal Teacher');
    $this->deck = 'A';
    $this->number = 168;
    $this->category = LIVESTOCK_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Immediately after each time you use a __Lessons__ action space, you can also buy 1 <SHEEP>/<PIG>/<CATTLE> for 0/1/2 <FOOD>.'
      ),
    ];
    $this->players = '4+';
    $this->isArtifexOrBubulcus = true;
    $this->usedText = UsedText::get('ANIMALS_RECEIVED');
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer') &&
	  $event['actionCardType'] == 'Lessons';
  }

  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    return [
      'type' => NODE_XOR,
      'optional' => true,
      'countAsUse' => true,
      'childs' => [
	    $this->gainNode([SHEEP => 1]),
		$this->payGainNode([FOOD => 1],[PIG => 1], null, false),
		$this->payGainNode([FOOD => 2],[CATTLE => 1], null, false),
	  ],
    ];
  }
  
}
