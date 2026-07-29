<?php
namespace AGR\Cards\E;

class E107_LandSurveyor extends \AGR\Models\Occupation
{
  public function __construct($row)
  {
    parent::__construct($row);
    $this->id = 'E107_LandSurveyor';
    $this->name = clienttranslate('Land Surveyor');
    $this->deck = 'E';
    $this->author = 'chris';
    $this->number = 107;
    $this->category = FOOD;
    $this->desc = [
      clienttranslate('In the field phase of each harvest, if you have at least 2/4/6/7 fields, you get 1/2/3/4 <FOOD>.'),
    ];
    $this->players = '1+';
  }

  public function isListeningTo($event)
  {
    return $this->isPlayerEvent($event) && $event['type'] == 'HarvestFieldPhase';
  }

  public function onPlayerHarvestFieldPhase($player)
  {
    $Fields = $player->board()->countFields();
    if ($Fields >= 7){
      return $this->gainNode([FOOD => 4]);
    }
    elseif($Fields >= 6){
      return $this->gainNode([FOOD => 3]);
    }
    elseif($Fields >= 4){
      return $this->gainNode([FOOD => 2]);
    }
    elseif($Fields >= 2){
      return $this->gainNode([FOOD => 1]);
    }
  }
}
