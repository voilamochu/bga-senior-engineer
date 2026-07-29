<?php
namespace AGR\Cards\D;

class D140_Loudmouth extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D140_Loudmouth';
    $this->name = clienttranslate('Loudmouth');
    $this->deck = 'D';
    $this->number = 140;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time you take at least 4 building resources or 4 animals from an accumulation space, you also get 1 <FOOD>.'
      ),
    ];
    $this->players = '3+';
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedWeak3or4p = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isCollectEvent($event);
  }

  public function onPlayerAfterCollect($player, $event)
  {
    $buildingResources = 0;
    $animals = 0;
    
    foreach ($event['meeples'] as $meeple) {
      $type = $meeple['type'];
      
      if (in_array($type, [WOOD, CLAY, STONE, REED])) {$buildingResources++; continue;}
      if (in_array($type, [SHEEP, PIG, CATTLE])) {$animals++; continue;}
    }
    
    if (($buildingResources >= 4) || ($animals >= 4)) {
      return $this->gainNode([FOOD => 1]);
    }
  }
}
