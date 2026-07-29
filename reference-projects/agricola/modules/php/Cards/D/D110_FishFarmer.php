<?php
namespace AGR\Cards\D;
use AGR\Managers\Meeples;

class D110_FishFarmer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'D110_FishFarmer';
    $this->name = clienttranslate('Fish Farmer');
    $this->deck = 'D';
    $this->number = 110;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'Each time there is 1/2/3+ food on the __Fishing__ accumulation space, you get an additional 2 <FOOD> on the __Reed Bank__/ __Clay Pit__/ __Forest__ accumulation spaces.'
      ),
    ];
    $this->players = '1+';
    $this->isCorbariusOrDulcinaria = true;
    $this->bannedStrong1or2p = true;
  }
  
  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'ReedBank') || 
      $this->isActionCardEvent($event, 'ClayPit') ||
      $this->isActionCardEvent($event, 'Forest');
  }
  
  public function onPlayerPlaceFarmer($player, $event)
  {
    $fishingFood = Meeples::getResourcesOnCard('ActionFishing', null, FOOD)->count();
    
    if (
      ($fishingFood == 1 && $this->isActionCardEvent($event, 'ReedBank')) ||
      ($fishingFood == 2 && $this->isActionCardEvent($event, 'ClayPit')) ||
      ($fishingFood >= 3 && $this->isActionCardEvent($event, 'Forest'))) 
    {
      return $this->gainNode([FOOD => 2]);
    }
  }
}
