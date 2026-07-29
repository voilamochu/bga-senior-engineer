<?php
namespace AGR\Cards\E;

class E117_PipeSmoker extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E117_PipeSmoker';
    $this->name = clienttranslate('Pipe Smoker');
    $this->deck = 'E';
    $this->author = 'tom';
    $this->number = 117;
    $this->category = 'BUILDING_RESOURCES_-_WOOD';
    $this->desc = [
      clienttranslate('At the start of each harvest, if you have at least 1 grain field, you get 2 <WOOD>.'),
    ];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) 
      && $event['type'] == 'StartHarvest';
  }

  public function onPlayerStartHarvest($player, $event)
  {
    $grainFields = $player->board()->getGrainFields();
    if (count($grainFields) >= 1) {
      return $this->gainNode([WOOD => 2]);
    }
  }
}