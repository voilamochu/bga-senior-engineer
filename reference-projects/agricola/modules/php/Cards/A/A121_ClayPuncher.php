<?php
namespace AGR\Cards\A;

class A121_ClayPuncher extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A121_ClayPuncher';
    $this->name = clienttranslate('Clay Puncher');
    $this->deck = 'A';
    $this->number = 121;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card and each time after you use a __Lessons__ action space or the __Clay Pit__ accumulation space, you get 1 <CLAY>.'
      ),
    ];
    $this->players = '1+';
    $this->isArtifexOrBubulcus = true;
  }
	
  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'PlaceFarmer') &&
      in_array($event['actionCardType'], ['Lessons', 'ClayPit']);
  }
  
  public function onPlayerAfterPlaceFarmer($player, $event)
  {
    return $this->gainNode([CLAY => 1]);
  }	
	
  public function onBuy($player)
  {
    return $this->gainNode([CLAY => 1]);
  }
}
