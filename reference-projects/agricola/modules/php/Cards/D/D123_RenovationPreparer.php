<?php
namespace AGR\Cards\D;

class D123_RenovationPreparer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D123_RenovationPreparer';
    $this->name = clienttranslate('Renovation Preparer');
    $this->deck = 'D';
    $this->number = 123;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [clienttranslate('For each new wood/clay room you build, you get 2 <CLAY>/2 <STONE>.')];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionEvent($event, 'Construct') &&
	  ($event['roomType'] == 'roomWood' || $event['roomType'] == 'roomClay');
  }
  
  public function onPlayerAfterConstruct($player, $event)
  {
    $resource = ($event['roomType'] == 'roomWood') ? CLAY : STONE;
    $n = count($event['rooms']);
	
    return $this->gainNode([$resource => 2 * $n]);
  }
  
}
