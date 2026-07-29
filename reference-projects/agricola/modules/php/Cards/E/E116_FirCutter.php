<?php
namespace AGR\Cards\E;

class E116_FirCutter extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E116_FirCutter';
    $this->name = clienttranslate('Fir Cutter');
    $this->deck = 'E';
    $this->author = 'beso';
    $this->number = 116;
    $this->category = 'BUILDING_RESOURCES_-_WOOD';
    $this->desc = [
      clienttranslate(
        'When you play this card, you immediately get 1 <FOOD>. Each time after you use an animal accumulation space with your 1st/2nd/3rd/4th/5th person, you get 1/1/2/2/3 <WOOD>.'
      ),
    ];
    $this->players = '1+';
    $this->implemented = true;
  }
  public function onBuy($player)
  {
    return $this->gainNode([FOOD => 1]);
  }
  public function isListeningTo($event)
  { 
    return ($this->isActionEvent($event, 'PlaceFarmer') && 
    ($event['actionCardType'] == 'SheepMarket'||$event['actionCardType'] == 'PigMarket'||$event['actionCardType'] == 'CattleMarket'));
  }

  public function onPlayerAfterPlaceFarmer($player, $event)
 {
  $map = [null, 1, 1, 2, 2, 3];
  $FoodNum = $map[$player->countPlacedFarmers()];
  if (!is_null($FoodNum)) {
    return $this->gainNode([WOOD => $FoodNum]);
  }
 }
}
