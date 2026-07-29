<?php
namespace AGR\Cards\E;

class E147_AnimalDriver extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E147_AnimalDriver';
    $this->name = clienttranslate('Animal Driver');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 147;
    $this->category = 'ANIMALS_-_ALL';
    $this->desc = [
      clienttranslate(
        'At the start of each harvest, if you have 1/2/3+ fenced stables, you get 1 <SHEEP>/<PIG>/<CATTLE>.'
      ),
    ];
    $this->players = '3+';
    $this->implemented = true;
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) 
      && $event['type'] == 'StartHarvest';
  }

  public function onPlayerStartHarvest($player, $event)
  {
    $fencedStables = 0;
    $zones = $player->board()->getAnimalsDropZones();
    foreach ($zones as $zone) {
      if ($zone['type'] != 'pasture') {
          continue;
      }
      $fencedStables += count($zone['stables']);
    }
    $map = [0,SHEEP,PIG,CATTLE,CATTLE];
    if ($fencedStables>=1){
       return $this->gainNode([$map[$fencedStables] => 1]); 
    }    
  }
}
