<?php
namespace AGR\Cards\D;
use AGR\Managers\Farmers;

class D142_PotatoPlanter extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D142_PotatoPlanter';
    $this->name = clienttranslate('Potato Planter');
    $this->deck = 'D';
    $this->number = 142;
    $this->category = CROP_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At the end of each work phase in which you occupy the __Clay Pit__ or __Reed Bank__ accumulation space while the respective other is unoccupied, you get 1 <VEGETABLE>.'
      ),
    ];
    $this->players = '3+';
    $this->isCorbariusOrDulcinaria = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && 
	  $event['type'] == 'EndWorkPhase';
  }
  
  public function onPlayerEndWorkPhase($player, $event)
  {
    $pId = $player->getId();
    
    if (
      (!Farmers::getOnCard('ActionClayPit', $pId)->empty() && 
        Farmers::getOnCard('ActionReedBank')->empty())
      ||
      (!Farmers::getOnCard('ActionReedBank', $pId)->empty() && 
        Farmers::getOnCard('ActionClayPit')->empty()))
    {
      return $this->gainNode([VEGETABLE => 1]);
    }
  }  
}
