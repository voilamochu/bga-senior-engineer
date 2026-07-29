<?php
namespace AGR\Cards\E;
use AGR\Core\Globals;
use AGR\Managers\Players;
use AGR\Managers\Farmers;

class E143_Hewer extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E143_Hewer';
    $this->name = clienttranslate('Hewer');
    $this->deck = 'E';
    $this->author = 'azwandahlan';
    $this->number = 143;
    $this->category = 'BUILDING_RESOURCES_-_CLAY_OR_STONE';
    $this->desc = [
      clienttranslate(
        'From round 3 on, at the end of each work phase in which all clay accumulation spaces are unoccupied, you get 1 <STONE> and 1 <FOOD>.'
      ),
    ];
    $this->players = '3+';
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'EndWorkPhase' && Globals::getTurn() >= 3 &&
      Farmers::getOnCard('ActionClayPit')->empty() &&
      ((Players::count() == 4 && Farmers::getOnCard('ActionHollow4')->empty()) || 
      (Players::count() == 3 && Farmers::getOnCard('ActionHollow')->empty()));
  }


  public function onPlayerEndWorkPhase($player, $event)
  {
    return $this->gainNode([STONE => 1,FOOD =>1]);
  }

  //LEGACY
  public function onPlayerAfterWorkPhase($player, $event)
  {
    return $this->gainNode([STONE => 1,FOOD =>1]);
  }
}
