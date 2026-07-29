<?php
namespace AGR\Cards\C;
use AGR\Managers\Meeples;

class C159_FishermansFriend extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'C159_FishermansFriend';
    $this->name = clienttranslate("Fisherman's Friend");
    $this->deck = 'C';
    $this->number = 159;
    $this->category = FOOD_PROVIDER;
    $this->desc = [
      clienttranslate(
        'At the start of each round, if there is more <FOOD> on the __Traveling Players__ than on the __Fishing__ accumulation space, you get the difference from the general supply.'
      ),
    ];
    $this->players = '4+';
    $this->isCorbariusOrDulcinaria = true;
  }
  
   public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'StartOfTurn';
  }

  public function onPlayerStartOfTurn($player, $event)
  {
    $travelingFood = Meeples::getResourcesOnCard('ActionTravelingPlayers', null, FOOD)->count();
    $fishingFood = Meeples::getResourcesOnCard('ActionFishing', null, FOOD)->count();

    $diff = $travelingFood - $fishingFood;
    
    if ($diff > 0) {
      $node = $this->gainNode([FOOD => $diff]);
      if ($player->hasPlayedCard('E152_BargainHunter')) {
        $node['forceConfirmation'] = true;
      }
      return $node;
    }
  }
 
}
