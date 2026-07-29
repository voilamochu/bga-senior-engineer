<?php
namespace AGR\Cards\A;

class A80_StoneTongs extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A80_StoneTongs';
    $this->name = clienttranslate('Stone Tongs');
    $this->deck = 'A';
    $this->number = 80;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [clienttranslate('Each time you use a stone accumulation space, you get 1 additional <STONE>.')];
    $this->cost = [
      WOOD => 1,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isBeforeCollectEvent($event, STONE);
  }
  
  public function onPlayerPlaceFarmer($player, $event)
  {
    return $this->gainNode([STONE => 1]);
  }  

  //TODO: remove
  public function onPlayerAfterCollect($player, $event)
  {
    return $this->gainNode([STONE => 1]);
  }
}
