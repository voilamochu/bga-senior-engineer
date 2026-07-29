<?php
namespace AGR\Cards\A;

class A145_Ropemaker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A145_Ropemaker';
    $this->name = clienttranslate('Ropemaker');
    $this->deck = 'A';
    $this->number = 145;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [clienttranslate('At the end of each harvest, you get 1 <REED> from the general supply.')];
    $this->players = '3+';
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'EndHarvest';
  }

  public function onPlayerEndHarvest($player)
  {
    return $this->gainNode([REED => 1]);
  }  
}
