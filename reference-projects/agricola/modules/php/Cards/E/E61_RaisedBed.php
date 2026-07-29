<?php
namespace AGR\Cards\E;

class E61_RaisedBed extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E61_RaisedBed';
    $this->name = clienttranslate('Raised Bed');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 61;
    $this->category = 'FOOD_-_GRAIN';
    $this->desc = [clienttranslate('At the start of each harvest, you get 4 <FOOD>.')];
    $this->vp = 1;
    $this->cost = [
      CLAY => 2,
      STONE => 2,
    ];
    $this->prerequisite = clienttranslate('2 Grain Fields');
  }

  public function isBuyable($player, $ignoreResources = false, $args = [])
  {
    $grainFields = $player->board()->getGrainFields();
    if (count($grainFields) < 2) {
      return false;
    }
    return parent::isBuyable($player, $ignoreResources, $args);
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'StartHarvest';
  }

  public function onPlayerStartHarvest($player, $event)
  {    
   return $this->gainNode([FOOD => 4]);
  }
}