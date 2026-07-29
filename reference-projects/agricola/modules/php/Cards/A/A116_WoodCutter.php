<?php
namespace AGR\Cards\A;
use AGR\Managers\ActionCards;
use AGR\Managers\Meeples;
use AGR\Helpers\Utils;

class A116_WoodCutter extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'A116_WoodCutter';
    $this->name = clienttranslate('Wood Cutter');
    $this->deck = 'A';
    $this->number = 116;
    $this->category = BUILDING_RESOURCE_PROVIDER;
    $this->desc = [clienttranslate('Each time you use a wood accumulation space, you get 1 additional <WOOD>.')];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isBeforeCollectEvent($event, WOOD);
  }

  public function onPlayerPlaceFarmer($player, $event)
  {
    return $this->gainNode([WOOD => 1]);
  }
  
  // TODO: remove
  public function onPlayerAfterCollect($player, $event)
  {  
    return $this->gainNode([WOOD => 1]);
  }  
}
