<?php
namespace AGR\Cards\E;

use AGR\Managers\Meeples;

class E59_CombandCutter extends \AGR\Models\MinorImprovement
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E59_CombandCutter';
    $this->name = clienttranslate('Comb and Cutter');
    $this->deck = 'E';
    $this->author = 'guy';
    $this->number = 59;
    $this->category = FOOD;
    $this->desc = [
      clienttranslate(
        'Each time you use the __Day Laborer__ action space, you get 1 additional <FOOD> for each <SHEEP> on the __Sheep Market__ accumulation space, up to a maximum of 4 additional <FOOD>.'
      ),
    ];
    $this->cost = [
      WOOD => 1,
    ];
  }

  public function isListeningTo($event)
  {
    return $this->isActionCardEvent($event, 'DayLaborer');
  }

  public function onPlayerPlaceFarmer($player, $args)
  {
    $SheepNum = Meeples::getResourcesOnCard ('ActionSheepMarket', null, SHEEP)->count();
    $FoodNum = ($SheepNum>4)? 4:$SheepNum;
    if ($FoodNum>0)
      return $this->gainNode([FOOD => $FoodNum]);
  }
}
