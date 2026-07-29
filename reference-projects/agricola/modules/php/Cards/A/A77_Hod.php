<?php
namespace AGR\Cards\A;

class A77_Hod extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A77_Hod';
    $this->name = clienttranslate('Hod');
    $this->deck = 'A';
    $this->number = 77;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <CLAY>. Each time any player (including you) uses the __Pig Market__ accumulation space, you immediately get 2 <CLAY>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
    $this->isArtifexOrBubulcus = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'PigMarket', null);
  }
  
  public function onPlayerPlaceFarmer($player, $event)
  {
    return $this->gainNode([CLAY => 2]);
  }  
  
  public function onOpponentPlaceFarmer($player, $event)
  {
    return $this->gainNode([CLAY => 2]);
  } 

  public function onBuy($player)
  {
    return $this->gainNode([CLAY => 1]);
  }  
}
